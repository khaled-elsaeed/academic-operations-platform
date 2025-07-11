<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\AvailableCourse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\DataTables;
use App\Services\Admin\EnrollmentService;
use App\Rules\AcademicAdvisorAccessRule;

class EnrollmentController extends Controller
{
    /**
     * EnrollmentController constructor.
     */
    public function __construct(protected EnrollmentService $enrollmentService)
    {
    }

    /**
     * Display the enrollment index page.
     */
    public function index()
    {
        return view('admin.enrollment.index');
    }

    /**
     * Store new enrollments for a student.
     */
    public function store(Request $request): JsonResponse
    {
        \Log::info('Request data', $request->all());
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id', new AcademicAdvisorAccessRule()],
            'term_id' => 'required|exists:terms,id',
            'available_course_ids' => 'required|array|min:1',
            'available_course_ids.*' => 'exists:available_courses,id',
        ]);

        $results = [];
        foreach ($validated['available_course_ids'] as $availableCourseId) {
            $availableCourse = \App\Models\AvailableCourse::findOrFail($availableCourseId);
            $results[] = $this->enrollmentService->createEnrollment([
                'student_id' => $validated['student_id'],
                'course_id' => $availableCourse->course_id,
                'term_id' => $availableCourse->term_id,
            ]);
        }

        return response()->json([
            'success' => true,
            'enrollments' => $results,
        ]);
    }

    /**
     * Update an existing enrollment.
     */
    public function update(Request $request, Enrollment $enrollment): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id', new AcademicAdvisorAccessRule()],
            'course_id' => 'required|exists:courses,id',
            'term_id' => 'required|exists:terms,id',
        ]);

        $enrollment = $this->enrollmentService->updateEnrollment($enrollment, $validated);

        return response()->json([
            'success' => true,
            'enrollment' => $enrollment,
        ]);
    }

    /**
     * Delete an enrollment.
     */
    public function destroy(Enrollment $enrollment): JsonResponse
    {
        $this->enrollmentService->deleteEnrollment($enrollment);
        return response()->json(['success' => true]);
    }

    /**
     * Get datatable data for enrollments.
     */
    public function datatable()
    {
        return $this->enrollmentService->getDatatable();
    }

    /**
     * Get enrollment statistics.
     */
    public function stats()
    {
        $stats = $this->enrollmentService->getStats();
        return response()->json($stats);
    }

    /**
     * Show the add enrollment page.
     */
    public function add()
    {
        return view('admin.enrollment.add');
    }

    /**
     * Find a student by national or academic ID.
     */
    public function findStudent(Request $request): JsonResponse
    {
        $request->validate([
            'identifier' => 'required|string',
        ]);

        $student = Student::withoutGlobalScopes()
            ->where('national_id', $request->identifier)
            ->orWhere('academic_id', $request->identifier)
            ->with('program')
            ->first();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found.',
            ], 404);
        }

        // Validate access using the custom validation rule
        $validator = \Validator::make(
            ['student_id' => $student->id],
            ['student_id' => ['required', 'exists:students,id', new AcademicAdvisorAccessRule()]]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first('student_id'),
            ], 403);
        }

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first('student_id'),
            ], 403);
        }

        return response()->json([
            'success' => true,
            'student' => $student,
        ]);
    }

    /**
     * Get available courses for a student in a term.
     */
    public function availableCourses(Request $request): JsonResponse
    {
        $request->validate([
            'student_id' => ['required', 'exists:students,id', new AcademicAdvisorAccessRule()],
            'term_id' => 'required|exists:terms,id',
        ]);

        $student = Student::with('program')->findOrFail($request->student_id);
        $programId = $student->program_id;
        $level = $student->level;
        $termId = $request->term_id;

        $availableCourses = AvailableCourse::available($programId, $level, $termId)
            ->with('course')
            ->get();

        $courses = $availableCourses->map(function ($availableCourse) {
            return [
                'id' => $availableCourse->course->id,
                'name' => $availableCourse->course->name,
                'available_course_id' => $availableCourse->id,
                'remaining_capacity' => $availableCourse->remaining_capacity,
            ];
        });

        return response()->json([
            'success' => true,
            'courses' => $courses,
        ]);
    }

    /**
     * Get all enrollments for a student.
     */
    public function studentEnrollments(Request $request): JsonResponse
    {
        $request->validate([
            'student_id' => ['required', 'exists:students,id', new AcademicAdvisorAccessRule()],
        ]);

        $enrollments = $this->enrollmentService->getStudentEnrollments($request->student_id);

        return response()->json([
            'success' => true,
            'enrollments' => $enrollments,
        ]);
    }
} 