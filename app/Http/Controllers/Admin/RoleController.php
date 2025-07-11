<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\Facades\DataTables;

class RoleController extends Controller
{
    /**
     * Display the role management page
     */
    public function index()
    {
        return view('admin.role');
    }

    /**
     * Get role statistics
     */
    public function stats(): JsonResponse
    {
        $totalRoles = Role::count();
        $totalPermissions = Permission::count();
        $rolesWithUsers = Role::withCount('users')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total' => [
                    'total' => $totalRoles,
                    'lastUpdateTime' => now()->format('Y-m-d H:i:s')
                ],
                'permissions' => [
                    'total' => $totalPermissions,
                    'lastUpdateTime' => now()->format('Y-m-d H:i:s')
                ],
                'rolesWithUsers' => $rolesWithUsers
            ]
        ]);
    }

    /**
     * Get role data for DataTables
     */
    public function datatable(): JsonResponse
    {
        $roles = Role::with(['permissions', 'users']);

        return DataTables::of($roles)
            ->addColumn('permissions', function ($role) {
                return $role->permissions->pluck('name')->implode(', ');
            })
            ->addColumn('users_count', function ($role) {
                return $role->users_count ?? $role->users->count();
            })
            ->addColumn('actions', function ($role) {
                return '
                    <div class="dropdown">
                        <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                            <i class="bx bx-dots-vertical-rounded"></i>
                        </button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="javascript:void(0);" onclick="viewRole(' . $role->id . ')">
                                <i class="bx bx-show me-1"></i> View
                            </a>
                            <a class="dropdown-item" href="javascript:void(0);" onclick="editRole(' . $role->id . ')">
                                <i class="bx bx-edit-alt me-1"></i> Edit
                            </a>
                            <a class="dropdown-item" href="javascript:void(0);" onclick="deleteRole(' . $role->id . ')">
                                <i class="bx bx-trash me-1"></i> Delete
                            </a>
                        </div>
                    </div>';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    /**
     * Store a new role
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles',
            'permissions' => 'array|exists:permissions,name'
        ]);

        $role = Role::create(['name' => $request->name]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return response()->json([
            'success' => true,
            'message' => 'Role created successfully.',
            'data' => $role
        ]);
    }

    /**
     * Show role details
     */
    public function show(Role $role): JsonResponse
    {
        $role->load(['permissions', 'users']);
        
        return response()->json([
            'success' => true,
            'data' => $role
        ]);
    }

    /**
     * Update role
     */
    public function update(Request $request, Role $role): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'permissions' => 'array|exists:permissions,name'
        ]);

        $role->update(['name' => $request->name]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return response()->json([
            'success' => true,
            'message' => 'Role updated successfully.',
            'data' => $role
        ]);
    }

    /**
     * Delete role
     */
    public function destroy(Role $role): JsonResponse
    {
        if ($role->name === 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete admin role.'
            ], 422);
        }

        if ($role->users()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete role that has assigned users.'
            ], 422);
        }

        $role->delete();

        return response()->json([
            'success' => true,
            'message' => 'Role deleted successfully.'
        ]);
    }

    /**
     * Get all permissions for dropdown
     */
    public function getPermissions(): JsonResponse
    {
        $permissions = Permission::all();
        
        return response()->json([
            'success' => true,
            'data' => $permissions
        ]);
    }
} 