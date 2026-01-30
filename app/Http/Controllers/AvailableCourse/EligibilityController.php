<?php

namespace App\Http\Controllers\AvailableCourse;

use App\Http\Controllers\Controller;
use App\Services\AvailableCourse\EligibilityService;
use Illuminate\Http\{Request, JsonResponse};
use App\Exceptions\BusinessValidationException;
use Exception;

class EligibilityController extends Controller
{
    /**
     * EligibilityController constructor.
     *
     * @param EligibilityService $eligibilityService
     */
    public function __construct(protected EligibilityService $eligibilityService)
    {}

    /**
     * Get eligibility datatable for edit page.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function datatable($id): JsonResponse
    {
        try {
            return $this->eligibilityService->getEligibilitiesDatatable($id);
        } catch (Exception $e) {
            logError('EligibilityController@datatable', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
    * Get all eligibilities for available course.
    *
    * @param int $id
    * @return JsonResponse
    */
    public function getAvailableCourseEligibilities($id): JsonResponse
    {
        try {
            $eligibilities = $this->eligibilityService->getAvailableCourseEligibilities($id);
            return successResponse('Eligibilities retrieved successfully.', $eligibilities);
        } catch (Exception $e) {
            logError('EligibilityController@getAvailableCourseEligibilities', $e, ['id' => $id]);
            return errorResponse('Internal server error.', [], 500);
        }
    }


    /**
     * Store new eligibility for available course.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function store(Request $request, $id): JsonResponse
    {
        $request->validate([
            'program_id' => 'required|exists:programs,id',
            'level_id' => 'required|exists:levels,id',
            'group_numbers' => 'required|array|min:1',
            'group_numbers.*' => 'integer|min:1|max:30',
        ]);

        try {
            $eligibility = $this->eligibilityService->storeEligibility($id, $request->all());
            return successResponse('Eligibility added successfully.', $eligibility);
        } catch (BusinessValidationException $e) {
            return errorResponse($e->getMessage(), [], $e->getCode());
        } catch (Exception $e) {
            logError('EligibilityController@store', $e, ['id' => $id, 'request' => $request->all()]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Delete eligibility for available course.
     *
     * @param int $availableCourseId
     * @param int $eligibilityId
     * @return JsonResponse
     */
    public function delete($availableCourseId, $eligibilityId): JsonResponse
    {
        try {
            $this->eligibilityService->deleteEligibility($eligibilityId);
            return successResponse('Eligibility deleted successfully.');
        } catch (BusinessValidationException $e) {
            return errorResponse($e->getMessage(), [], $e->getCode());
        } catch (Exception $e) {
            logError('EligibilityController@delete', $e, ['availableCourseId' => $availableCourseId, 'eligibilityId' => $eligibilityId]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Show eligibility details.
     *
     * @param int $availableCourseId
     * @param int $eligibilityId
     * @return JsonResponse
     */
    public function show($availableCourseId, $eligibilityId): JsonResponse
    {
        try {
            $eligibility = $this->eligibilityService->getEligibility($eligibilityId);
            return successResponse('Eligibility retrieved successfully.', $eligibility);
        } catch (Exception $e) {
            logError('EligibilityController@show', $e, ['availableCourseId' => $availableCourseId, 'eligibilityId' => $eligibilityId]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Update eligibility for available course.
     *
     * @param Request $request
     * @param int $availableCourseId
     * @param int $eligibilityId
     * @return JsonResponse
     */
    public function update(Request $request, $availableCourseId, $eligibilityId): JsonResponse
    {
        $request->validate([
            'program_id' => 'required|exists:programs,id',
            'level_id' => 'required|exists:levels,id',
            'group_numbers' => 'required|array|min:1|max:1', // Assuming we only edit one group at a time
            'group_numbers.0' => 'required|integer|min:1|max:30',
        ]);

        try {
            // Transform input to match service expectation (single group)
            $data = $request->all();
            $data['group'] = $data['group_numbers'][0];

            $eligibility = $this->eligibilityService->updateEligibility($eligibilityId, $data);
            return successResponse('Eligibility updated successfully.', $eligibility);
        } catch (BusinessValidationException $e) {
            return errorResponse($e->getMessage(), [], $e->getCode());
        } catch (Exception $e) {
            logError('EligibilityController@update', $e, ['availableCourseId' => $availableCourseId, 'eligibilityId' => $eligibilityId, 'request' => $request->all()]);
            return errorResponse('Internal server error.', [], 500);
        }
    }
}