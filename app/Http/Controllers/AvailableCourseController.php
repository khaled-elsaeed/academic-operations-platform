<?php

namespace App\Http\Controllers;

use App\Models\AvailableCourse;
use App\Models\Student;
use App\Models\Enrollment;
use App\Models\Term;
use App\Models\CreditHoursException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AvailableCourseController extends Controller
{
    /**
     * Display a listing of the available courses for a given student and term.
     * Returns only the related course models, excluding courses the student is already enrolled in.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Validate request input
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'term_id'    => ['required', 'exists:terms,id'],
        ]);

        // Retrieve student and relevant IDs
        $student = Student::findOrFail($validated['student_id']);
        $programId = $student->program_id;
        $levelId   = $student->level_id;
        $termId    = $validated['term_id'];
        $studentId = $validated['student_id'];

        // Query available courses for the student's program, level, and term
        // Exclude courses the student is already enrolled in for this term
        $availableCourses = AvailableCourse::available($programId, $levelId, $termId)
            ->notEnrolled($studentId, $termId)
            ->with('course')
            ->get();

        // Map to course data structure
        $courses = $availableCourses->map(function ($availableCourse) {
            return [
                'id'                 => $availableCourse->course->id,
                'name'               => $availableCourse->course->name,
                'code'               => $availableCourse->course->code,
                'credit_hours'       => $availableCourse->course->credit_hours,
                'available_course_id'=> $availableCourse->id,
                'remaining_capacity' => $availableCourse->remaining_capacity,
            ];
        });

        // Return JSON response
        return response()->json([
            'success' => true,
            'courses' => $courses,
        ]);
    }

    /**
     * Get remaining credit hours for a student in a specific term.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function getRemainingCreditHours(Request $request): JsonResponse
    {
        // Validate request input
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'term_id'    => ['required', 'exists:terms,id'],
        ]);

        $studentId = $validated['student_id'];
        $termId = $validated['term_id'];

        // Get student and term data
        $student = Student::findOrFail($studentId);
        $term = Term::findOrFail($termId);

        // Calculate current enrollment credit hours
        $currentEnrollmentHours = $this->getCurrentEnrollmentHours($studentId, $termId);

        // Calculate maximum allowed credit hours
        $maxAllowedHours = $this->getMaxCreditHours($student, $term);

        // Calculate remaining credit hours
        $remainingHours = $maxAllowedHours - $currentEnrollmentHours;

        // Get additional hours from admin exception
        $exceptionHours = $this->getAdminExceptionHours($studentId, $termId);

        return response()->json([
            'success' => true,
            'data' => [
                'current_enrollment_hours' => $currentEnrollmentHours,
                'max_allowed_hours' => $maxAllowedHours,
                'remaining_hours' => max(0, $remainingHours), // Ensure non-negative
                'exception_hours' => $exceptionHours,
                'student_cgpa' => $student->cgpa,
                'term_season' => $term->season,
                'term_year' => $term->year,
            ],
        ]);
    }

    /**
     * Get current enrollment credit hours for the student in this term
     */
    private function getCurrentEnrollmentHours(int $studentId, int $termId): int
    {
        return Enrollment::where('student_id', $studentId)
            ->where('term_id', $termId)
            ->join('courses', 'enrollments.course_id', '=', 'courses.id')
            ->sum('courses.credit_hours');
    }

    /**
     * Get the maximum allowed credit hours based on CGPA and semester
     */
    private function getMaxCreditHours(Student $student, Term $term): int
    {
        $semester = strtolower($term->season);
        $cgpa = $student->cgpa;
        
        $baseHours = $this->getBaseHours($semester, $cgpa);
        $graduationBonus = 0; // TODO: Implement graduation check logic
        $adminException = $this->getAdminExceptionHours($student->id, $term->id);

        return $baseHours + $graduationBonus + $adminException;
    }

    /**
     * Get base credit hours based on semester and CGPA
     */
    private function getBaseHours(string $semester, float $cgpa): int
    {
        // Summer semester has fixed 9 hours regardless of CGPA
        if ($semester === 'summer') {
            return 9;
        }

        // Fall and Spring semesters have CGPA-based limits
        if (in_array($semester, ['fall', 'spring'])) {
            if ($cgpa < 2.0) {
                return 14;
            } elseif ($cgpa >= 2.0 && $cgpa < 3.0) {
                return 18;
            } elseif ($cgpa >= 3.0) {
                return 21;
            }
        }

        // Default fallback (shouldn't reach here with valid semesters)
        return 14;
    }

    /**
     * Get additional hours from admin exception for this student and term
     */
    private function getAdminExceptionHours(int $studentId, int $termId): int
    {
        $exception = CreditHoursException::where('student_id', $studentId)
            ->where('term_id', $termId)
            ->active()
            ->first();

        return $exception ? $exception->getEffectiveAdditionalHours() : 0;
    }
}