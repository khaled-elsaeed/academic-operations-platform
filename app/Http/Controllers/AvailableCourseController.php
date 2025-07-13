<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\AvailableCourseService;
use App\Http\Requests\StoreAvailableCourseRequest;
use App\Http\Requests\UpdateAvailableCourseRequest;
use App\Exceptions\BusinessValidationException;
use App\Models\AvailableCourse;
use App\Models\Student;
use App\Models\Enrollment;
use App\Models\Term;
use App\Models\CreditHoursException;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use App\Exports\AvailableCoursesTemplateExport;
use Maatwebsite\Excel\Facades\Excel;
use Exception;

class AvailableCourseController extends Controller
{
    /**
     * AvailableCourseController constructor.
     *
     * @param AvailableCourseService $availableCourseService
     */
    public function __construct(protected AvailableCourseService $availableCourseService)
    {}

    /**
     * Display the available courses page.
     */
    public function index(): View
    {
        return view('available_course.index');
    }

    /**
     * Return data for DataTable AJAX requests.
     */
    public function datatable(): JsonResponse
    {
        try {
            return $this->availableCourseService->getDatatable();
        } catch (Exception $e) {
            logError('AvailableCourseController@datatable', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Show the form for creating a new available course.
     */
    public function create(): View
    {
        return view('available_course.create');
    }

    /**
     * Store a new available course.
     */
    public function store(StoreAvailableCourseRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $availableCourse = $this->availableCourseService->createAvailableCourse($validated);
            return successResponse('Available course created successfully.', $availableCourse);
        } catch (BusinessValidationException $e) {
            return errorResponse($e->getMessage(), [], $e->getCode());
        } catch (Exception $e) {
            logError('AvailableCourseController@store', $e, ['request' => $request->all()]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Show the form for editing the specified available course.
     */
    public function edit($id): View
    {
        $availableCourse = AvailableCourse::findOrFail($id);
        return view('available_course.edit', compact('availableCourse'));
    }

    /**
     * Update the specified available course in storage.
     */
    public function update(UpdateAvailableCourseRequest $request, $id): JsonResponse
    {
        try {
            $validated = $request->validated();
            $this->availableCourseService->updateAvailableCourseById($id, $validated);
            return successResponse('Available course updated successfully.');
        } catch (BusinessValidationException $e) {
            return errorResponse($e->getMessage(), [], $e->getCode());
        } catch (Exception $e) {
            logError('AvailableCourseController@update', $e, ['id' => $id, 'request' => $request->all()]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Delete an available course.
     */
    public function destroy($id): JsonResponse
    {
        try {
            $this->availableCourseService->deleteAvailableCourse($id);
            return successResponse('Available course deleted successfully.');
        } catch (BusinessValidationException $e) {
            return errorResponse($e->getMessage(), [], $e->getCode());
        } catch (Exception $e) {
            logError('AvailableCourseController@destroy', $e, ['id' => $id]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Display the specified available course.
     */
    public function show($id): JsonResponse
    {
        try {
            $availableCourse = $this->availableCourseService->getAvailableCourse($id);
            return successResponse('Available course retrieved successfully.', $availableCourse);
        } catch (BusinessValidationException $e) {
            return errorResponse($e->getMessage(), [], $e->getCode());
        } catch (Exception $e) {
            logError('AvailableCourseController@show', $e, ['id' => $id]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Import available courses from an Excel file.
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'courses_file' => 'required|file|mimes:xlsx,xls',
        ]);

        try {
            $result = $this->availableCourseService->importAvailableCoursesFromFile($request->file('courses_file'));
            return successResponse($result['message'], [
                'imported_count' => $result['imported_count'] ?? 0,
                'errors' => $result['errors'] ?? [],
            ]);
        } catch (Exception $e) {
            logError('AvailableCourseController@import', $e, ['request' => $request->all()]);
            return errorResponse('Import failed. Please check your file.', [], 500);
        }
    }

    /**
     * Download the available courses import template as an Excel file.
     */
    public function template(): BinaryFileResponse
    {
        try {
            return Excel::download(new AvailableCoursesTemplateExport, 'available_courses_template.xlsx');
        } catch (Exception $e) {
            logError('AvailableCourseController@template', $e);
            throw $e;
        }
    }

    public function all(Request $request): JsonResponse
    {
        try {
            // Check if this is a legacy request (student-specific)
            if ($request->has('student_id') && $request->has('term_id')) {
                return $this->getStudentAvailableCourses($request);
            }
            
            // Admin request - return all available courses
            $courses = $this->availableCourseService->getAll();
            return successResponse('Available courses retrieved successfully.', $courses);
        } catch (Exception $e) {
            logError('AvailableCourseController@all', $e);
            return errorResponse('Failed to retrieve available courses.', [], 500);
        }
    }

    /**
     * Get available courses for a specific student and term (legacy functionality).
     *
     * @param Request $request
     * @return JsonResponse
     */
    private function getStudentAvailableCourses(Request $request): JsonResponse
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
        try {
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
        } catch (Exception $e) {
            logError('AvailableCourseController@getRemainingCreditHours', $e);
            return errorResponse('Failed to get remaining credit hours.', [], 500);
        }
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