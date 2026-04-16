<?php

namespace App\Http\Controllers;

use App\Models\PrerequisiteException;
use App\Models\Student;
use App\Models\Course;
use App\Models\Term;
use App\Services\PrerequisiteExceptionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Exception;
use App\Exceptions\BusinessValidationException;
use Maatwebsite\Excel\Facades\Excel;

class PrerequisiteExceptionController extends Controller
{
    /**
     * PrerequisiteExceptionController constructor.
     *
     * @param PrerequisiteExceptionService $exceptionService
     */
    public function __construct(protected PrerequisiteExceptionService $exceptionService)
    {}

    /**
     * Display the prerequisite exceptions index page.
     */
    public function index(): View
    {
        return view('prerequisite_exceptions.index');
    }

    /**
     * Store a new prerequisite exception.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'course_id' => 'required|exists:courses,id',
            'prerequisite_id' => 'required|exists:courses,id',
            'term_id' => 'required|exists:terms,id',
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $exception = $this->exceptionService->createException(
                $request->all(),
                auth()->user()
            );

            return successResponse('Prerequisite exception created successfully.', $exception);
        } catch (BusinessValidationException $e) {
            return errorResponse($e->getMessage(), [], $e->getCode());
        } catch (Exception $e) {
            logError('PrerequisiteExceptionController@store', $e, ['request' => $request->all()]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Update an existing prerequisite exception.
     */
    public function update(Request $request, PrerequisiteException $exception): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        try {
            $exception = $this->exceptionService->updateException($exception, $request->all());
            return successResponse('Prerequisite exception updated successfully.', $exception);
        } catch (Exception $e) {
            logError('PrerequisiteExceptionController@update', $e, ['request' => $request->all()]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Deactivate a prerequisite exception.
     */
    public function deactivate(PrerequisiteException $exception): JsonResponse
    {
        try {
            $this->exceptionService->deactivateException($exception);
            return successResponse('Prerequisite exception deactivated successfully.');
        } catch (Exception $e) {
            logError('PrerequisiteExceptionController@deactivate', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Activate a prerequisite exception.
     */
    public function activate(PrerequisiteException $exception): JsonResponse
    {
        try {
            $this->exceptionService->activateException($exception);
            return successResponse('Prerequisite exception activated successfully.');
        } catch (BusinessValidationException $e) {
            return errorResponse($e->getMessage(), [], $e->getCode());
        } catch (Exception $e) {
            logError('PrerequisiteExceptionController@activate', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Delete a prerequisite exception.
     */
    public function destroy(PrerequisiteException $exception): JsonResponse
    {
        try {
            $exception->delete();
            return successResponse('Prerequisite exception deleted successfully.');
        } catch (Exception $e) {
            logError('PrerequisiteExceptionController@destroy', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Get the datatable for prerequisite exceptions.
     */
    public function datatable(): JsonResponse
    {
        try {
            return $this->exceptionService->getDatatable();
        } catch (Exception $e) {
            logError('PrerequisiteExceptionController@datatable', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Get students for dropdown.
     */
    public function getStudents(): JsonResponse
    {
        try {
            $students = Student::select('id', 'name_en', 'academic_id')
                ->orderBy('name_en')
                ->get()
                ->map(function ($student) {
                    return [
                        'id' => $student->id,
                        'text' => "{$student->name_en} ({$student->academic_id})"
                    ];
                });

            return successResponse('Students retrieved successfully.', $students);
        } catch (Exception $e) {
            logError('PrerequisiteExceptionController@getStudents', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Get courses for dropdown.
     */
    public function getCourses(): JsonResponse
    {
        try {
            $courses = Course::select('id', 'code', 'title')
                ->has('prerequisites')
                ->orderBy('code')
                ->get()
                ->map(function ($course) {
                    return [
                        'id' => $course->id,
                        'text' => "{$course->title} ({$course->code})"
                    ];
                });

            return successResponse('Courses retrieved successfully.', $courses);
        } catch (Exception $e) {
            logError('PrerequisiteExceptionController@getCourses', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Get prerequisites for a specific course.
     */
    public function getPrerequisites(Course $course): JsonResponse
    {
        try {
            $prerequisites = $course->prerequisites()
                ->select('courses.id', 'courses.code', 'courses.title')
                ->get()
                ->map(function ($prereq) {
                    return [
                        'id' => $prereq->id,
                        'text' => "{$prereq->title} ({$prereq->code})"
                    ];
                });

            return successResponse('Prerequisites retrieved successfully.', $prerequisites);
        } catch (Exception $e) {
            logError('PrerequisiteExceptionController@getPrerequisites', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Get terms for dropdown.
     */
    public function getTerms(): JsonResponse
    {
        try {
            $terms = Term::select('id', 'season', 'year', 'is_active')
                ->where('is_active', true)
                ->orderBy('year', 'desc')
                ->orderBy('season')
                ->get()
                ->map(function ($term) {
                    return [
                        'id' => $term->id,
                        'text' => "{$term->season} {$term->year}"
                    ];
                });

            return successResponse('Terms retrieved successfully.', $terms);
        } catch (Exception $e) {
            logError('PrerequisiteExceptionController@getTerms', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Get exception details for editing.
     */
    public function show(PrerequisiteException $exception): JsonResponse
    {
        try {
            $exception->load([
                'student:id,name_en,academic_id',
                'course:id,code,title',
                'prerequisite:id,code,title',
                'term:id,season,year',
                'grantedBy:id,first_name,last_name'
            ]);
            return successResponse('Exception details retrieved successfully.', $exception);
        } catch (Exception $e) {
            logError('PrerequisiteExceptionController@show', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Get statistics for prerequisite exceptions.
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = $this->exceptionService->getStats();
            return successResponse('Statistics retrieved successfully.', $stats);
        } catch (Exception $e) {
            logError('PrerequisiteExceptionController@stats', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Import prerequisite exceptions from uploaded file.
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'exceptions_file' => 'required|file|mimes:xlsx,xls'
        ]);

        try {
            $result = $this->exceptionService->importExceptions($request->file('exceptions_file'));
            return successResponse($result['message'], [
                'imported_count' => $result['imported_count'],
                'errors' => $result['errors']
            ]);
        } catch (BusinessValidationException $e) {
            return errorResponse($e->getMessage(), [], 422);
        } catch (Exception $e) {
            logError('PrerequisiteExceptionController@import', $e, ['request' => $request->all()]);
            return errorResponse('Failed to import prerequisite exceptions.', [], 500);
        }
    }

    /**
     * Download the import template for prerequisite exceptions.
     */
    public function downloadTemplate()
    {
        try {
            return $this->exceptionService->downloadTemplate();
        } catch (Exception $e) {
            logError('PrerequisiteExceptionController@downloadTemplate', $e);
            return errorResponse('Failed to download template.', [], 500);
        }
    }
}
