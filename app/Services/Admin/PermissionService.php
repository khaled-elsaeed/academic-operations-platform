<?php

namespace App\Services\Admin;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\Facades\DataTables;

class PermissionService
{
    /**
     * Get permission statistics.
     *
     * @return array
     */
    public function getStats(): array
    {
        $totalPermissions = Permission::count();
        $totalRoles = Role::count();
        $permissionsWithRoles = Permission::withCount('roles')->get();

        return [
            'total' => [
                'total' => $totalPermissions,
                'lastUpdateTime' => formatDate(now(), 'Y-m-d H:i:s')
            ],
            'roles' => [
                'total' => $totalRoles,
                'lastUpdateTime' => formatDate(now(), 'Y-m-d H:i:s')
            ],
            'permissionsWithRoles' => $permissionsWithRoles
        ];
    }

    /**
     * Get permission data for DataTables.
     *
     * @return JsonResponse
     */
    public function getDatatable(): JsonResponse
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
                return $this->renderActionButtons($permission);
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    /**
     * Render action buttons for datatable rows.
     *
     * @param Permission $permission
     * @return string
     */
    protected function renderActionButtons($permission): string
    {
        return '
            <div class="dropdown">
                <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                    <i class="bx bx-dots-vertical-rounded"></i>
                </button>
                <div class="dropdown-menu">
                    <a class="dropdown-item" href="javascript:void(0);" onclick="viewPermission(' . $permission->id . ')">
                        <i class="bx bx-show me-1"></i> View
                    </a>
                </div>
            </div>';
    }

    /**
     * Get permission details.
     *
     * @param Permission $permission
     * @return Permission
     */
    public function getPermission(Permission $permission): Permission
    {
        return $permission->load('roles');
    }

    /**
     * Get all roles for dropdown.
     *
     * @return array
     */
    public function getRoles(): array
    {
        return Role::all()->toArray();
    }
} 