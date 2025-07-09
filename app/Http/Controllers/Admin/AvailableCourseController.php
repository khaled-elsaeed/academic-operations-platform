<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Admin\AvailableCourseService;
use App\Http\Requests\StoreAvailableCourseRequest;
use App\Http\Requests\UpdateAvailableCourseRequest;
use AppExceptions\BusinessValidationException;
use App\Models\AvailableCourse;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AvailableCourseController extends Controller
{
    /**
     * AvailableCourseController constructor.
     *
     * @param AvailableCourseService $service
     */
    public function __construct(protected AvailableCourseService $service)
    {}

    /**
     * Display the available courses page.
     */
    public function index(): View
    {
        return view('admin.available_course.index');
    }

    /**
     * Return data for DataTable AJAX requests.
     */
    public function datatable(): JsonResponse
    {
        try {
            return $this->service->getDatatable();
        } catch (Exception $e) {
            logError('AvailableCourseController@datatable', $e);
            return errorResponse('Internal server error.', 500);
        }
    }

    /**
     * Store a new available course.
     */
    public function store(StoreAvailableCourseRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $availableCourse = $this->service->createAvailableCourse($data);
            return successResponse('Available course created successfully.', $availableCourse);
        } catch (BusinessValidationException $e) {
            return errorResponse($e->getMessage(), 422);
        } catch (Exception $e) {
            logError('AvailableCourseController@store', $e, ['request' => $request->all()]);
            return errorResponse('Internal server error.', 500);
        }
    }

    /**
     * Show the form for editing the specified available course.
     */
    public function edit($id): View
    {
        $availableCourse = AvailableCourse::findOrFail($id);
        return view('admin.available_course.edit', compact('availableCourse'));
    }

    /**
     * Update the specified available course in storage.
     *
     * @param UpdateAvailableCourseRequest $request
     * @param int $id
     */
    public function update(UpdateAvailableCourseRequest $request, $id): JsonResponse
    {
        try {
            $data = $request->validated();
            $this->service->updateAvailableCourseById($id, $data);
            return successResponse('Available course updated successfully.');
        } catch (BusinessValidationException $e) {
            return errorResponse($e->getMessage(), 422);
        } catch (Exception $e) {
            logError('AvailableCourseController@update', $e, ['id' => $id, 'request' => $request->all()]);
            return errorResponse('Internal server error.', 500);
        }
    }

    /**
     * Delete an available course.
     *
     * @param int $id
     */
    public function destroy($id): JsonResponse
    {
        try {
            $this->service->deleteAvailableCourse($id);
            return successResponse(null, 'Available course deleted successfully.');
        } catch (BusinessValidationException $e) {
            return errorResponse($e->getMessage(), 422);
        } catch (Exception $e) {
            logError('AvailableCourseController@destroy', $e, ['id' => $id]);
            return errorResponse('Internal server error.', 500);
        }
    }


    /**
     * Display the specified available course.
     *
     * @param int $id
     */
    public function show($id): JsonResponse
    {
        try {
            $availableCourse = $this->service->getAvailableCourseWithEligibilities($id);
            return successResponse(null, $availableCourse);
        } catch (Exception $e) {
            logError('AvailableCourseController@show', $e, ['id' => $id]);
            return errorResponse('Internal server error.', 500);
        }
    }
} 