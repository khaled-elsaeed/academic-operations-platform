<?php

namespace App\Http\Controllers;

use Illuminate\Http\{Request, JsonResponse};
use Illuminate\View\View;
use App\Services\ProgramService;
use App\Models\Program;
use App\Exceptions\BusinessValidationException;
use Exception;

class ProgramController extends Controller
{
    /**
     * ProgramController constructor.
     *
     * @param ProgramService $programService
     */
    public function __construct(protected ProgramService $programService)
    {}

    /**
     * Display the program management page.
     *
     * @return View
     */
    public function index(): View
    {
        return view('program.index');
    }

    /**
     * Get program statistics.
     *
     * @return JsonResponse
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = $this->programService->getStats();
            return successResponse('Stats fetched successfully.', $stats);
        } catch (Exception $e) {
            logError('ProgramController@stats', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Get program data for DataTables.
     *
     * @return JsonResponse
     */
    public function datatable(): JsonResponse
    {
        try {
            return $this->programService->getDatatable();
        } catch (Exception $e) {
            logError('ProgramController@datatable', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Store a newly created program.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:programs,code',
            'faculty_id' => 'required|exists:faculties,id'
        ]);

        try {
            $validated = $request->all();
            $program = $this->programService->createProgram($validated);
            return successResponse('Program created successfully.', $program);
        } catch (Exception $e) {
            logError('ProgramController@store', $e, ['request' => $request->all()]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Display the specified program.
     *
     * @param Program $program
     * @return JsonResponse
     */
    public function show(Program $program): JsonResponse
    {
        try {
            $program = $this->programService->getProgram($program);
            return successResponse('Program details fetched successfully.', $program);
        } catch (Exception $e) {
            logError('ProgramController@show', $e, ['program_id' => $program->id]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Update the specified program.
     *
     * @param Request $request
     * @param Program $program
     * @return JsonResponse
     */
    public function update(Request $request, Program $program): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:programs,code,' . $program->id,
            'faculty_id' => 'required|exists:faculties,id'
        ]);

        try {
            $validated = $request->all();
            $program = $this->programService->updateProgram($program, $validated);
            return successResponse('Program updated successfully.', $program);
        } catch (Exception $e) {
            logError('ProgramController@update', $e, ['program_id' => $program->id, 'request' => $request->all()]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Remove the specified program.
     *
     * @param Program $program
     * @return JsonResponse
     */
    public function destroy(Program $program): JsonResponse
    {
        try {
            $this->programService->deleteProgram($program);
            return successResponse('Program deleted successfully.');
        } catch (BusinessValidationException $e) {
            return errorResponse($e->getMessage(), [], $e->getCode());
        } catch (Exception $e) {
            logError('ProgramController@destroy', $e, ['program_id' => $program->id]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Get all faculties for dropdown.
     *
     * @return JsonResponse
     */
    public function getFaculties(): JsonResponse
    {
        try {
            $faculties = $this->programService->getFaculties();
            return successResponse('Faculties fetched successfully.', $faculties);
        } catch (Exception $e) {
            logError('ProgramController@getFaculties', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Get all programs (for dropdown and forms).
     *
     * @return JsonResponse
     */
    public function all(): JsonResponse
    {
        try {
            $programs = $this->programService->getAll();
            return successResponse('Programs fetched successfully.', $programs);
        } catch (Exception $e) {
            logError('ProgramController@all', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }
} 