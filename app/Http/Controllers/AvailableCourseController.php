<?php

namespace App\Http\Controllers;

use Illuminate\Http\{Request, JsonResponse};
use Illuminate\View\View;
use App\Services\AvailableCourseService;
use App\Services\CreateAvailableCourseService;
use App\Exports\AvailableCoursesTemplateExport;
use App\Http\Requests\{StoreAvailableCourseRequest, UpdateAvailableCourseRequest};
use App\Models\AvailableCourse;
use App\Models\Student;
use App\Models\Term;
use App\Exceptions\BusinessValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
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
     *
     * @return View
     */
    public function index(): View
    {
        return view('available_course.index');
    }

    /**
     * Return data for DataTable AJAX requests.
     *
     * @return JsonResponse
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
     *
     * @return View
     */
    public function create(): View
    {
        return view('available_course.create');
    }

    /**
     * Store a new available course.
     *
     * @param StoreAvailableCourseRequest $request
     * @param CreateAvailableCourseService $createAvailableCourseService
     * @return JsonResponse
     */
    public function store(StoreAvailableCourseRequest $request, CreateAvailableCourseService $createAvailableCourseService): JsonResponse
    {
        try {
            $validated = $request->validated();
            $availableCourse = $createAvailableCourseService->createAvailableCourseSingle($validated);
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
     *
     * @param int $id
     * @return View
     */
    public function edit($id): View
    {
        $availableCourse = AvailableCourse::findOrFail($id);
        return view('available_course.edit', compact('availableCourse'));
    }

    /**
     * Update the specified available course in storage.
     *
     * @param UpdateAvailableCourseRequest $request
     * @param int $id
     * @return JsonResponse
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
     *
     * @param int $id
     * @return JsonResponse
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
     *
     * @param int $id
     * @return JsonResponse
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
     *
     * @param Request $request
     * @return JsonResponse
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
     *
     * @return BinaryFileResponse
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

    /**
     * Get all available courses (admin and legacy student-specific).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function all(Request $request): JsonResponse
    {
        try {
            if ($request->has('student_id') && $request->has('term_id')) {
                return $this->getStudentAvailableCourses($request);
            }
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
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'term_id'    => ['required', 'exists:terms,id'],
        ]);
        $student = Student::findOrFail($validated['student_id']);
        $programId = $student->program_id;
        $levelId   = $student->level_id;
        $termId    = $validated['term_id'];
        $studentId = $validated['student_id'];
        $availableCourses = AvailableCourse::available($programId, $levelId, $termId)
            ->notEnrolled($studentId, $termId)
            ->with('course')
            ->get();
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
        return response()->json([
            'success' => true,
            'courses' => $courses,
        ]);
    }

    /**
     * Get available course statistics for stat2 cards.
     *
     * @return JsonResponse
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = $this->availableCourseService->getStats();
            return successResponse('Stats fetched successfully.', $stats);
        } catch (Exception $e) {
            logError('AvailableCourseController@stats', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }
} 