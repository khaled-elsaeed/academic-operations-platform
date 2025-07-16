<?php

namespace App\Http\Controllers;

use Illuminate\Http\{Request, JsonResponse};
use Illuminate\View\View;
use App\Services\FacultyService;
use App\Models\Faculty;
use App\Exceptions\BusinessValidationException;
use Exception;

class FacultyController extends Controller
{
    /**
     * FacultyController constructor.
     *
     * @param FacultyService $facultyService
     */
    public function __construct(protected FacultyService $facultyService)
    {}

    /**
     * Display the faculty management page.
     *
     * @return View
     */
    public function index(): View
    {
        return view('faculty.index');
    }

    /**
     * Get faculty statistics.
     *
     * @return JsonResponse
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = $this->facultyService->getStats();
            return successResponse('Stats fetched successfully.', $stats);
        } catch (Exception $e) {
            logError('FacultyController@stats', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Get faculty data for DataTables.
     *
     * @return JsonResponse
     */
    public function datatable(): JsonResponse
    {
        try {
            return $this->facultyService->getDatatable();
        } catch (Exception $e) {
            logError('FacultyController@datatable', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Store a newly created faculty.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:faculties,name'
        ]);

        try {
            $validated = $request->all();
            $faculty = $this->facultyService->createFaculty($validated);
            return successResponse('Faculty created successfully.', $faculty);
        } catch (Exception $e) {
            logError('FacultyController@store', $e, ['request' => $request->all()]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Display the specified faculty.
     *
     * @param Faculty $faculty
     * @return JsonResponse
     */
    public function show(Faculty $faculty): JsonResponse
    {
        try {
            $faculty = $this->facultyService->getFaculty($faculty);
            return successResponse('Faculty details fetched successfully.', $faculty);
        } catch (Exception $e) {
            logError('FacultyController@show', $e, ['faculty_id' => $faculty->id]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Update the specified faculty.
     *
     * @param Request $request
     * @param Faculty $faculty
     * @return JsonResponse
     */
    public function update(Request $request, Faculty $faculty): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:faculties,name,' . $faculty->id
        ]);

        try {
            $validated = $request->all();
            $faculty = $this->facultyService->updateFaculty($faculty, $validated);
            return successResponse('Faculty updated successfully.', $faculty);
        } catch (Exception $e) {
            logError('FacultyController@update', $e, ['faculty_id' => $faculty->id, 'request' => $request->all()]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Remove the specified faculty.
     *
     * @param Faculty $faculty
     * @return JsonResponse
     */
    public function destroy(Faculty $faculty): JsonResponse
    {
        try {
            $this->facultyService->deleteFaculty($faculty);
            return successResponse('Faculty deleted successfully.');
        } catch (BusinessValidationException $e) {
            return errorResponse($e->getMessage(), [], $e->getCode());
        } catch (Exception $e) {
            logError('FacultyController@destroy', $e, ['faculty_id' => $faculty->id]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Get all faculties (for dropdown and forms).
     *
     * @return JsonResponse
     */
    public function all(): JsonResponse
    {
        try {
            $faculties = $this->facultyService->getAll();
            return successResponse('Faculties fetched successfully.', $faculties);
        } catch (Exception $e) {
            logError('FacultyController@all', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }
} 