<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicAdvisorAccess;
use App\Models\User;
use App\Models\Level;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\Facades\DataTables;

class AcademicAdvisorAccessController extends Controller
{
    /**
     * Display the advisor access management page.
     */
    public function index()
    {
        return view('admin.academic_advisor_access');
    }

    /**
     * Get advisor access data for DataTable.
     */
    public function datatable(): JsonResponse
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
            })
            ->addColumn('created_at', function ($access) {
                return $access->created_at->format('M d, Y H:i');
            })
            ->rawColumns(['status', 'actions'])
            ->make(true);
    }

    /**
     * Get statistics for advisor access.
     */
    public function stats(): JsonResponse
    {
        $total = AcademicAdvisorAccess::count();
        $active = AcademicAdvisorAccess::where('is_active', true)->count();
        $inactive = AcademicAdvisorAccess::where('is_active', false)->count();
        $uniqueAdvisors = AcademicAdvisorAccess::distinct('advisor_id')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total' => [
                    'total' => $total,
                    'lastUpdateTime' => now()->format('M d, Y H:i')
                ],
                'active' => [
                    'total' => $active,
                    'lastUpdateTime' => now()->format('M d, Y H:i')
                ],
                'inactive' => [
                    'total' => $inactive,
                    'lastUpdateTime' => now()->format('M d, Y H:i')
                ],
                'uniqueAdvisors' => [
                    'total' => $uniqueAdvisors,
                    'lastUpdateTime' => now()->format('M d, Y H:i')
                ]
            ]
        ]);
    }

    /**
     * Get advisors for dropdown.
     */
    public function getAdvisors(): JsonResponse
    {
        $advisors = User::whereHas('roles', function ($query) {
            $query->where('name', 'advisor');
        })->select('id', 'name')->get();

        return response()->json($advisors);
    }



    /**
     * Store a new advisor access.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'advisor_id' => 'required|exists:users,id',
            'level_id' => 'required|exists:levels,id',
            'program_id' => 'required|exists:programs,id',
            'is_active' => 'boolean'
        ]);

        // Check if access already exists
        $existing = AcademicAdvisorAccess::where([
            'advisor_id' => $request->advisor_id,
            'level_id' => $request->level_id,
            'program_id' => $request->program_id
        ])->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Access rule already exists for this advisor, level, and program combination.'
            ], 422);
        }

        $access = AcademicAdvisorAccess::create([
            'advisor_id' => $request->advisor_id,
            'level_id' => $request->level_id,
            'program_id' => $request->program_id,
            'is_active' => $request->is_active ?? true
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Advisor access created successfully.',
            'data' => $access->load(['advisor', 'level', 'program'])
        ]);
    }

    /**
     * Show advisor access details.
     */
    public function show(AcademicAdvisorAccess $academicAdvisorAccess): JsonResponse
    {
        $access = $academicAdvisorAccess->load(['advisor', 'level', 'program']);
        
        return response()->json([
            'success' => true,
            'data' => $access
        ]);
    }

    /**
     * Update advisor access.
     */
    public function update(Request $request, AcademicAdvisorAccess $academicAdvisorAccess): JsonResponse
    {
        $request->validate([
            'advisor_id' => 'required|exists:users,id',
            'level_id' => 'required|exists:levels,id',
            'program_id' => 'required|exists:programs,id',
            'is_active' => 'boolean'
        ]);

        // Check if access already exists (excluding current record)
        $existing = AcademicAdvisorAccess::where([
            'advisor_id' => $request->advisor_id,
            'level_id' => $request->level_id,
            'program_id' => $request->program_id
        ])->where('id', '!=', $academicAdvisorAccess->id)->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Access rule already exists for this advisor, level, and program combination.'
            ], 422);
        }

        $academicAdvisorAccess->update([
            'advisor_id' => $request->advisor_id,
            'level_id' => $request->level_id,
            'program_id' => $request->program_id,
            'is_active' => $request->is_active ?? true
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Advisor access updated successfully.',
            'data' => $academicAdvisorAccess->load(['advisor', 'level', 'program'])
        ]);
    }

    /**
     * Delete advisor access.
     */
    public function destroy(AcademicAdvisorAccess $academicAdvisorAccess): JsonResponse
    {
        $academicAdvisorAccess->delete();

        return response()->json([
            'success' => true,
            'message' => 'Advisor access deleted successfully.'
        ]);
    }
} 