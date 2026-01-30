<?php

namespace App\Services\AvailableCourse;

use App\Models\CourseEligibility;
use App\Exceptions\BusinessValidationException;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\DataTables;

class EligibilityService
{
    /**
     * Get eligibilities datatable for edit page.
     *
     * @param int $availableCourseId
     * @return JsonResponse
     */
    public function getEligibilitiesDatatable(int $availableCourseId): JsonResponse
    {
        $query = CourseEligibility::with(['program', 'level'])
            ->where('available_course_id', $availableCourseId);

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('program', function ($eligibility) {
                return $eligibility->program?->name ?? '-';
            })
            ->addColumn('level', function ($eligibility) {
                return $eligibility->level?->name ?? '-';
            })
            ->addColumn('groups', function ($eligibility) {
                return $eligibility->group ?? '-';
            })
            ->addColumn('action', function ($eligibility) {
                return $this->renderActionButtons($eligibility);
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    /**
     * Render action buttons.
     *
     * @param CourseEligibility $eligibility
     * @return string
     */
    protected function renderActionButtons(CourseEligibility $eligibility): string
    {
        $user = auth()->user();
        if (!$user) {
            return '';
        }

        $singleActions = $this->buildSingleActions($user, $eligibility);

        if (empty($singleActions)) {
            return '';
        }

        return view('components.ui.datatable.table-actions', [
            'mode' => 'single',
            'actions' => [],
            'id' => $eligibility->id,
            'type' => 'Eligibility',
            'singleActions' => $singleActions,
        ])->render();
    }

    /**
     * Build single actions.
     *
     * @param mixed $user
     * @param CourseEligibility $eligibility
     * @return array<int, array{action: string, icon: string, class: string, label: string, data: array}>
     */
    protected function buildSingleActions($user, CourseEligibility $eligibility): array
    {
        $actions = [];

        if ($user->hasPermissionTo('available_course.edit')) {
            $actions[] = [
                'action' => 'edit',
                'icon' => 'bx bx-edit',
                'class' => 'btn-warning',
                'label' => __('Edit'),
                'data' => ['id' => $eligibility->id],
            ];
        }

        if ($user->hasPermissionTo('available_course.delete')) {
            $actions[] = [
                'action' => 'delete',
                'icon' => 'bx bx-trash',
                'class' => 'btn-danger',
                'label' => __('Delete'),
                'data' => ['id' => $eligibility->id],
            ];
        }

        return $actions;
    }

     /**
     * Get Aavailable Course eligibilities.
     */
    public function getAvailableCourseEligibilities(int $availableCourseId): array
    {
        $eligibilities = CourseEligibility::with(['program', 'level'])
            ->where('available_course_id', $availableCourseId)
            ->get();

        return $eligibilities->toArray();
    }

    /**
     * Store new eligibility for available course.
     *
     * @param int $availableCourseId
     * @param array $data
     * @return array
     * @throws BusinessValidationException
     */
    public function storeEligibility(int $availableCourseId, array $data): array
    {
        $availableCourse = \App\Models\AvailableCourse::findOrFail($availableCourseId);
        $results = [];

        foreach ($data['group_numbers'] as $group) {
            // Check for existing eligibility
            $exists = CourseEligibility::where('available_course_id', $availableCourseId)
                ->where('program_id', $data['program_id'])
                ->where('level_id', $data['level_id'])
                ->where('group', $group)
                ->exists();

            if (!$exists) {
                $eligibility = CourseEligibility::create([
                    'available_course_id' => $availableCourseId,
                    'program_id' => $data['program_id'],
                    'level_id' => $data['level_id'],
                    'group' => $group,
                ]);
                $results[] = $eligibility->load('program', 'level');
            }
        }

        return $results;
    }

    /**
     * Delete eligibility for available course.
     *
     * @param int $eligibilityId
     * @return void
     * @throws BusinessValidationException
     */
    public function deleteEligibility(int $eligibilityId): void
    {
        $eligibility = CourseEligibility::findOrFail($eligibilityId);
        $eligibility->delete();
    }
    
    /**
     * Get eligibility details.
     *
     * @param int $eligibilityId
     * @return CourseEligibility
     */
    public function getEligibility(int $eligibilityId): CourseEligibility
    {
        return CourseEligibility::with(['program', 'level'])->findOrFail($eligibilityId);
    }

    /**
     * Update eligibility.
     *
     * @param int $eligibilityId
     * @param array $data
     * @return CourseEligibility
     * @throws BusinessValidationException
     */
    public function updateEligibility(int $eligibilityId, array $data): CourseEligibility
    {
        $eligibility = CourseEligibility::findOrFail($eligibilityId);

        // Check for duplicates
        $exists = CourseEligibility::where('available_course_id', $eligibility->available_course_id)
            ->where('program_id', $data['program_id'])
            ->where('level_id', $data['level_id'])
            ->where('group', $data['group'])
            ->where('id', '!=', $eligibilityId)
            ->exists();

        if ($exists) {
            throw new BusinessValidationException('This eligibility configuration already exists.');
        }

        $eligibility->update([
            'program_id' => $data['program_id'],
            'level_id' => $data['level_id'],
            'group' => $data['group'],
        ]);

        return $eligibility->load(['program', 'level']);
    }
}