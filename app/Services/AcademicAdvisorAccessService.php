<?php

namespace App\Services;

use App\Models\AcademicAdvisorAccess;
use App\Models\User;
use App\Models\Level;
use App\Models\Program;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\Facades\DataTables;
use App\Exceptions\BusinessValidationException;

class AcademicAdvisorAccessService
{
    /**
     * Get advisor access statistics.
     *
     * @return array
     */
    public function getStats(): array
    {
        $total = AcademicAdvisorAccess::count();
        $active = AcademicAdvisorAccess::where('is_active', true)->count();
        $inactive = AcademicAdvisorAccess::where('is_active', false)->count();
        $uniqueAdvisors = AcademicAdvisorAccess::distinct('advisor_id')->count();

        $lastCreatedAtTotal = AcademicAdvisorAccess::max('created_at');
        $lastCreatedAtActive = AcademicAdvisorAccess::where('is_active', true)->max('created_at');
        $lastCreatedAtInactive = AcademicAdvisorAccess::where('is_active', false)->max('created_at');

        return [
            'total' => [
                'total' => $total,
                'lastUpdateTime' => formatDate($lastCreatedAtTotal)
            ],
            'active' => [
                'total' => $active,
                'lastUpdateTime' => formatDate($lastCreatedAtActive)
            ],
            'inactive' => [
                'total' => $inactive,
                'lastUpdateTime' => formatDate($lastCreatedAtInactive)
            ],
            'uniqueAdvisors' => [
                'total' => $uniqueAdvisors,
                'lastUpdateTime' => formatDate($lastCreatedAtTotal)
            ]
        ];
    }

    /**
     * Get advisor access data for DataTable.
     *
     * @return JsonResponse
     */
    public function getDatatable(): JsonResponse
    {
        $query = AcademicAdvisorAccess::with(['advisor', 'level', 'program']);

        return DataTables::of($query)
            ->addColumn('advisor_name', function ($access) {
                return $access->advisor ? $access->advisor->name : 'N/A';
            })
            ->addColumn('level_name', function ($access) {
                return $access->level ? $access->level->name : 'N/A';
            })
            ->addColumn('program_name', function ($access) {
                return $access->program ? $access->program->name : 'N/A';
            })
            ->addColumn('status', function ($access) {
                return $access->is_active 
                    ? '<span class="badge bg-success">Active</span>'
                    : '<span class="badge bg-danger">Inactive</span>';
            })
            ->addColumn('actions', function ($access) {
                return $this->renderActionButtons($access);
            })
            ->addColumn('created_at', function ($access) {
                return formatDate($access->created_at, 'M d, Y H:i');
            })
            ->rawColumns(['status', 'actions'])
            ->make(true);
    }

    /**
     * Get all advisors for dropdown.
     *
     * @return array
     */
    public function getAll(): array
    {
        return User::whereHas('roles', function ($query) {
                $query->where('name', 'advisor');
            })
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                ];
            })
            ->toArray();
    }

    /**
     * Render action buttons for datatable rows.
     *
     * @param AcademicAdvisorAccess $access
     * @return string
     */
    protected function renderActionButtons($access): string
    {
        return '
            <div class="dropdown">
                <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                    <i class="bx bx-dots-vertical-rounded"></i>
                </button>
                <div class="dropdown-menu">
                    <a class="dropdown-item" href="javascript:void(0);" onclick="viewAccess(' . $access->id . ')">
                        <i class="bx bx-show me-1"></i> View
                    </a>
                    <a class="dropdown-item" href="javascript:void(0);" onclick="editAccess(' . $access->id . ')">
                        <i class="bx bx-edit-alt me-1"></i> Edit
                    </a>
                    <a class="dropdown-item" href="javascript:void(0);" onclick="deleteAccess(' . $access->id . ')">
                        <i class="bx bx-trash me-1"></i> Delete
                    </a>
                </div>
            </div>';
    }

    /**
     * Store a new advisor access (with bulk support for all programs or all levels).
     *
     * @param array $data
     * @return array
     * @throws BusinessValidationException
     */
    public function createAccess(array $data): array
    {
        $advisorId = $data['advisor_id'];
        $isActive = $data['is_active'];
        $created = [];

        if (isset($data['all_programs']) && $data['all_programs']) {
            $programs = Program::pluck('id');
            foreach ($programs as $programId) {
                $exists = AcademicAdvisorAccess::where([
                    'advisor_id' => $advisorId,
                    'level_id' => $data['level_id'],
                    'program_id' => $programId,
                ])->exists();
                if (!$exists) {
                    $created[] = AcademicAdvisorAccess::create([
                        'advisor_id' => $advisorId,
                        'level_id' => $data['level_id'],
                        'program_id' => $programId,
                        'is_active' => $isActive,
                    ]);
                }
            }
        } elseif (isset($data['all_levels']) && $data['all_levels']) {
            $levels = Level::pluck('id');
            foreach ($levels as $levelId) {
                $exists = AcademicAdvisorAccess::where([
                    'advisor_id' => $advisorId,
                    'level_id' => $levelId,
                    'program_id' => $data['program_id'],
                ])->exists();
                if (!$exists) {
                    $created[] = AcademicAdvisorAccess::create([
                        'advisor_id' => $advisorId,
                        'level_id' => $levelId,
                        'program_id' => $data['program_id'],
                        'is_active' => $isActive,
                    ]);
                }
            }
        } else {
            $exists = AcademicAdvisorAccess::where([
                'advisor_id' => $advisorId,
                'level_id' => $data['level_id'],
                'program_id' => $data['program_id'],
            ])->exists();
            if ($exists) {
                throw new BusinessValidationException('Access rule already exists for this advisor, level, and program combination.');
            }
            $created[] = AcademicAdvisorAccess::create([
                'advisor_id' => $advisorId,
                'level_id' => $data['level_id'],
                'program_id' => $data['program_id'],
                'is_active' => $isActive,
            ]);
        }

        return [
            'message' => count($created) > 1 ? 'Bulk access rules created successfully.' : 'Access rule created successfully.',
            'data' => collect($created)->map->load(['advisor', 'level', 'program'])
        ];
    }

    /**
     * Show advisor access details.
     *
     * @param AcademicAdvisorAccess $access
     * @return AcademicAdvisorAccess
     */
    public function getAccess(AcademicAdvisorAccess $access): AcademicAdvisorAccess
    {
        return $access->load(['advisor', 'level', 'program']);
    }

    /**
     * Update advisor access.
     *
     * @param AcademicAdvisorAccess $access
     * @param array $data
     * @return AcademicAdvisorAccess
     */
    public function updateAccess(AcademicAdvisorAccess $access, array $data): AcademicAdvisorAccess
    {
        $access->update([
            'advisor_id' => $data['advisor_id'],
            'level_id' => $data['level_id'],
            'program_id' => $data['program_id'],
            'is_active' => $data['is_active'] ?? true,
        ]);

        return $access->load(['advisor', 'level', 'program']);
    }

    /**
     * Delete advisor access.
     *
     * @param AcademicAdvisorAccess $access
     * @return void
     */
    public function deleteAccess(AcademicAdvisorAccess $access): void
    {
        $access->delete();
    }
} 