<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\Facades\DataTables;

class PermissionController extends Controller
{
    /**
     * Display the permission management page
     */
    public function index()
    {
        return view('admin.permission');
    }

    /**
     * Get permission statistics
     */
    public function stats(): JsonResponse
    {
        $totalPermissions = Permission::count();
        $totalRoles = Role::count();
        $permissionsWithRoles = Permission::withCount('roles')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total' => [
                    'total' => $totalPermissions,
                    'lastUpdateTime' => now()->format('Y-m-d H:i:s')
                ],
                'roles' => [
                    'total' => $totalRoles,
                    'lastUpdateTime' => now()->format('Y-m-d H:i:s')
                ],
                'permissionsWithRoles' => $permissionsWithRoles
            ]
        ]);
    }

    /**
     * Get permission data for DataTables
     */
    public function datatable(): JsonResponse
    {
        $permissions = Permission::with('roles');

        return DataTables::of($permissions)
            ->addColumn('roles', function ($permission) {
                return $permission->roles->pluck('name')->implode(', ');
            })
            ->addColumn('roles_count', function ($permission) {
                return $permission->roles_count ?? $permission->roles->count();
            })
            ->addColumn('actions', function ($permission) {
                return '
                    <div class="dropdown">
                        <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                            <i class="bx bx-dots-vertical-rounded"></i>
                        </button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="javascript:void(0);" onclick="viewPermission(' . $permission->id . ')">
                                <i class="bx bx-show me-1"></i> View
                            </a>
                            <a class="dropdown-item" href="javascript:void(0);" onclick="editPermission(' . $permission->id . ')">
                                <i class="bx bx-edit-alt me-1"></i> Edit
                            </a>
                            <a class="dropdown-item" href="javascript:void(0);" onclick="deletePermission(' . $permission->id . ')">
                                <i class="bx bx-trash me-1"></i> Delete
                            </a>
                        </div>
                    </div>';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    /**
     * Store a new permission
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:permissions',
            'guard_name' => 'string|max:255'
        ]);

        $permission = Permission::create([
            'name' => $request->name,
            'guard_name' => $request->guard_name ?? 'web'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Permission created successfully.',
            'data' => $permission
        ]);
    }

    /**
     * Show permission details
     */
    public function show(Permission $permission): JsonResponse
    {
        $permission->load('roles');
        
        return response()->json([
            'success' => true,
            'data' => $permission
        ]);
    }

    /**
     * Update permission
     */
    public function update(Request $request, Permission $permission): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name,' . $permission->id,
            'guard_name' => 'string|max:255'
        ]);

        $permission->update([
            'name' => $request->name,
            'guard_name' => $request->guard_name ?? 'web'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Permission updated successfully.',
            'data' => $permission
        ]);
    }

    /**
     * Delete permission
     */
    public function destroy(Permission $permission): JsonResponse
    {
        if ($permission->roles()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete permission that is assigned to roles.'
            ], 422);
        }

        $permission->delete();

        return response()->json([
            'success' => true,
            'message' => 'Permission deleted successfully.'
        ]);
    }

    /**
     * Get all roles for dropdown
     */
    public function getRoles(): JsonResponse
    {
        $roles = Role::all();
        
        return response()->json([
            'success' => true,
            'data' => $roles
        ]);
    }

    /**
     * Bulk create permissions
     */
    public function bulkCreate(Request $request): JsonResponse
    {
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'string|max:255'
        ]);

        $createdPermissions = [];
        $existingPermissions = [];

        foreach ($request->permissions as $permissionName) {
            $permission = Permission::firstOrCreate(['name' => $permissionName]);
            
            if ($permission->wasRecentlyCreated) {
                $createdPermissions[] = $permission;
            } else {
                $existingPermissions[] = $permissionName;
            }
        }

        $message = 'Permissions processed successfully.';
        if (!empty($existingPermissions)) {
            $message .= ' Some permissions already existed: ' . implode(', ', $existingPermissions);
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'created' => $createdPermissions,
                'existing' => $existingPermissions
            ]
        ]);
    }
} 