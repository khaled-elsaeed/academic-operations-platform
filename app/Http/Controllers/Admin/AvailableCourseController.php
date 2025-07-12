<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Admin\AvailableCourseService;
use App\Http\Requests\StoreAvailableCourseRequest;
use App\Http\Requests\UpdateAvailableCourseRequest;
use App\Exceptions\BusinessValidationException;
use App\Models\AvailableCourse;
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
            if (!$result['success']) {
                return errorResponse($result['message'], [], 422);
            }
            return successResponse($result['message'], $result['data'] ?? null);
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
} 