<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AcademicAdvisorAccess;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use App\Services\AcademicAdvisorAccessService;
use Exception;
use App\Exceptions\BusinessValidationException;

class AcademicAdvisorAccessController extends Controller
{
    /**
     * AcademicAdvisorAccessController constructor.
     *
     * @param AcademicAdvisorAccessService $academicAdvisorAccessService
     */
    public function __construct(protected AcademicAdvisorAccessService $academicAdvisorAccessService)
    {}

    /**
     * Display the advisor access management page.
     */
    public function index(): View
    {
        return view('academic_advisor_access.index');
    }

    /**
     * Get advisor access statistics.
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = $this->academicAdvisorAccessService->getStats();
            return successResponse('Stats fetched successfully.', $stats);
        } catch (Exception $e) {
            logError('AcademicAdvisorAccessController@stats', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Get advisor access data for DataTable.
     */
    public function datatable(): JsonResponse
    {
        try {
            return $this->academicAdvisorAccessService->getDatatable();
        } catch (Exception $e) {
            logError('AcademicAdvisorAccessController@datatable', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Get all advisors for dropdown.
     */
    public function all(): JsonResponse
    {
        try {
            $advisors = $this->academicAdvisorAccessService->getAll();
            return successResponse('Advisors fetched successfully.', $advisors);
        } catch (Exception $e) {
            logError('AcademicAdvisorAccessController@all', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Store a new advisor access (with bulk support for all programs or all levels).
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'advisor_id' => 'required|exists:users,id',
            'level_id' => 'required_without:all_levels|nullable|exists:levels,id',
            'program_id' => 'required_without:all_programs|nullable|exists:programs,id',
            'is_active' => 'required|boolean',
            'pairs' => 'nullable|array',
            'pairs.*.level_id' => 'nullable|exists:levels,id',
            'pairs.*.program_id' => 'nullable|exists:programs,id',
        ]);

        try {
            $validated = $request->all();
            $result = $this->academicAdvisorAccessService->createAccess($validated);
            return successResponse($result['message'], $result['data']);
        } catch (BusinessValidationException $e) {
            return errorResponse($e->getMessage(), [], $e->getCode());
        } catch (Exception $e) {
            logError('AcademicAdvisorAccessController@store', $e, ['request' => $request->all()]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Show advisor access details.
     */
    public function show(AcademicAdvisorAccess $academicAdvisorAccess): JsonResponse
    {
        try {
            $access = $this->academicAdvisorAccessService->getAccess($academicAdvisorAccess);
            return successResponse('Access details fetched successfully.', $access);
        } catch (Exception $e) {
            logError('AcademicAdvisorAccessController@show', $e, ['access_id' => $academicAdvisorAccess->id]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Update advisor access.
     */
    public function update(Request $request, AcademicAdvisorAccess $academicAdvisorAccess): JsonResponse
    {
        $request->validate([
            'advisor_id' => 'required|exists:users,id',
            'level_id' => 'nullable|exists:levels,id',
            'program_id' => 'nullable|exists:programs,id',
            'is_active' => 'boolean',
            'pairs' => 'nullable|array',
            'pairs.*.level_id' => 'nullable|exists:levels,id',
            'pairs.*.program_id' => 'nullable|exists:programs,id',
            'all_levels' => 'nullable|boolean',
            'all_programs' => 'nullable|boolean'
        ]);

        try {
            $validated = $request->all();
            $access = $this->academicAdvisorAccessService->updateAccess($academicAdvisorAccess, $validated);
            return successResponse('Advisor access updated successfully.', $access);
        } catch (BusinessValidationException $e) {
            return errorResponse($e->getMessage(), [], $e->getCode());
        } catch (Exception $e) {
            logError('AcademicAdvisorAccessController@update', $e, ['access_id' => $academicAdvisorAccess->id, 'request' => $request->all()]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Delete advisor access.
     */
    public function destroy(AcademicAdvisorAccess $academicAdvisorAccess): JsonResponse
    {
        try {
            $this->academicAdvisorAccessService->deleteAccess($academicAdvisorAccess);
            return successResponse('Advisor access deleted successfully.');
        } catch (Exception $e) {
            logError('AcademicAdvisorAccessController@destroy', $e, ['access_id' => $academicAdvisorAccess->id]);
            return errorResponse('Internal server error.', [], 500);
        }
    }
} 