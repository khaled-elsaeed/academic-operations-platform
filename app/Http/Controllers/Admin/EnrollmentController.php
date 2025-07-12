<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use App\Services\Admin\EnrollmentService;
use App\Rules\AcademicAdvisorAccessRule;
use Exception;
use App\Exceptions\BusinessValidationException;

class EnrollmentController extends Controller
{
    /**
     * EnrollmentController constructor.
     *
     * @param EnrollmentService $enrollmentService
     */
    public function __construct(protected EnrollmentService $enrollmentService)
    {}

    /**
     * Display the enrollment index page.
     */
    public function index(): View
    {
        return view('admin.enrollment.index');
    }

    /**
     * Store new enrollments for a student.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'student_id' => ['required', 'exists:students,id', new AcademicAdvisorAccessRule()],
            'term_id' => 'required|exists:terms,id',
            'available_course_ids' => 'required|array|min:1',
            'available_course_ids.*' => 'exists:available_courses,id',
        ]);

        try {
            $validated = $request->all();
            $results = $this->enrollmentService->createEnrollments($validated);
            return successResponse('Enrollments created successfully.', $results);
        } catch (BusinessValidationException $e) {
            return errorResponse($e->getMessage(), [], $e->getCode());
        } catch (Exception $e) {
            logError('EnrollmentController@store', $e, ['request' => $request->all()]);
            return errorResponse('Internal server error.', [], 500);
        }
    }



    /**
     * Delete an enrollment.
     */
    public function destroy(Enrollment $enrollment): JsonResponse
    {
        try {
            $this->enrollmentService->deleteEnrollment($enrollment);
            return successResponse('Enrollment deleted successfully.');
        } catch (Exception $e) {
            logError('EnrollmentController@destroy', $e, ['enrollment_id' => $enrollment->id]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Get datatable data for enrollments.
     */
    public function datatable(): JsonResponse
    {
        try {
            return $this->enrollmentService->getDatatable();
        } catch (Exception $e) {
            logError('EnrollmentController@datatable', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Get enrollment statistics.
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = $this->enrollmentService->getStats();
            return successResponse('Stats fetched successfully.', $stats);
        } catch (Exception $e) {
            logError('EnrollmentController@stats', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Show the add enrollment page.
     */
    public function add(): View
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

        try {
            $student = $this->enrollmentService->findStudent($request->identifier);
            return successResponse('Student found successfully.', $student);
        } catch (BusinessValidationException $e) {
            return errorResponse($e->getMessage(), [], $e->getCode());
        } catch (Exception $e) {
            logError('EnrollmentController@findStudent', $e, ['identifier' => $request->identifier]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Get available courses for a student and term.
     */
    public function availableCourses(Request $request): JsonResponse
    {
        $request->validate([
            'student_id' => ['required', 'exists:students,id', new AcademicAdvisorAccessRule()],
            'term_id' => 'required|exists:terms,id',
        ]);

        try {
            $availableCourses = \App\Models\AvailableCourse::with(['course', 'eligibilities.program', 'eligibilities.level'])
                ->whereHas('eligibilities', function ($query) use ($request) {
                    $query->where('program_id', function ($subQuery) use ($request) {
                        $subQuery->select('program_id')
                            ->from('students')
                            ->where('id', $request->student_id);
                    })->where('level_id', function ($subQuery) use ($request) {
                        $subQuery->select('level_id')
                            ->from('students')
                            ->where('id', $request->student_id);
                    });
                })
                ->where('term_id', $request->term_id)
                ->get()
                ->map(function ($availableCourse) {
                    return [
                        'id' => $availableCourse->id,
                        'name' => $availableCourse->course->name,
                        'course_code' => $availableCourse->course->code,
                        'min_capacity' => $availableCourse->min_capacity,
                        'max_capacity' => $availableCourse->max_capacity,
                    ];
                });

            return successResponse('Available courses fetched successfully.', $availableCourses);
        } catch (Exception $e) {
            logError('EnrollmentController@availableCourses', $e, ['request' => $request->all()]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Get all enrollments for a student.
     */
    public function studentEnrollments(Request $request): JsonResponse
    {
        $request->validate([
            'student_id' => ['required', 'exists:students,id', new AcademicAdvisorAccessRule()],
        ]);

        try {
            $enrollments = $this->enrollmentService->getStudentEnrollments($request->student_id);
            return successResponse('Student enrollments fetched successfully.', $enrollments);
        } catch (Exception $e) {
            logError('EnrollmentController@studentEnrollments', $e, ['student_id' => $request->student_id]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Import enrollments from an uploaded file.
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'enrollments_file' => 'required|file|mimes:xlsx,xls'
        ]);
        
        try {
            $result = $this->enrollmentService->importEnrollments($request->file('enrollments_file'));
            
            return successResponse($result['message'], [
                'imported_count' => $result['imported_count'],
                'created_count' => $result['created_count'],
                'skipped_count' => $result['skipped_count'],
                'errors' => $result['errors']
            ]);
        } catch (BusinessValidationException $e) {
            return errorResponse($e->getMessage(), [], 422);
        } catch (Exception $e) {
            logError('EnrollmentController@import', $e, ['request' => $request->all()]);
            return errorResponse('Failed to import enrollments.', 500);
        }
    }

    /**
     * Download the enrollments import template.
     */
    public function downloadTemplate()
    {
        try {
            return $this->enrollmentService->downloadTemplate();
        } catch (Exception $e) {
            logError('EnrollmentController@downloadTemplate', $e);
            return errorResponse('Failed to download template.', 500);
        }
    }
} 