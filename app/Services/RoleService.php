<?php

namespace App\Services;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\Facades\DataTables;
use App\Exceptions\BusinessValidationException;

class RoleService
{
    /**
     * Get role statistics.
     *
     * @return array
     */
    public function getStats(): array
    {
        $totalRoles = Role::count();
        $totalPermissions = Permission::count();
        $rolesWithUsers = Role::withCount('users')->get();
        return [
            'total' => [
                'total' => formatNumber($totalRoles),
                'lastUpdateTime' => formatDate(now(), 'Y-m-d H:i:s')
            ],
            'permissions' => [
                'total' => formatNumber($totalPermissions),
                'lastUpdateTime' => formatDate(now(), 'Y-m-d H:i:s')
            ],
            'rolesWithUsers' => $rolesWithUsers
        ];
    }

    /**
     * Get role data for DataTables.
     *
     * @return JsonResponse
     */
    public function getDatatable(): JsonResponse
    {
        $roles = Role::withCount(['permissions', 'users']);
        return DataTables::of($roles)
            ->addColumn('permissions', fn($role) => '<button type="button" class="btn btn-sm btn-info show-permissions" data-role-id="' . $role->id . '" data-role="' . htmlspecialchars($role->name, ENT_QUOTES, 'UTF-8') . '"><i class="bx bx-key"></i> <span class="badge bg-light text-dark ms-1">' . $role->permissions_count . '</span></button>')
            ->addColumn('users_count', fn($role) => $role->users_count)
            ->addColumn('actions', fn($role) => $this->renderActionButtons($role))
            ->rawColumns(['permissions', 'actions'])
            ->make(true);
    }

    /**
     * Render action buttons for datatable rows.
     *
     * @param Role $role
     * @return string
     */
    protected function renderActionButtons($role): string
    {
        return '
            <div class="dropdown">
                <button type="button" class="btn btn-primary btn-icon rounded-pill dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                    <i class="bx bx-dots-vertical-rounded"></i>
                </button>
                <div class="dropdown-menu">
                    <a class="dropdown-item viewRoleBtn" href="javascript:void(0);" data-id="' . $role->id . '">
                        <i class="bx bx-show me-1"></i> View
                    </a>
                    <a class="dropdown-item editRoleBtn" href="javascript:void(0);" data-id="' . $role->id . '">
                        <i class="bx bx-edit-alt me-1"></i> Edit
                    </a>
                    <a class="dropdown-item deleteRoleBtn" href="javascript:void(0);" data-id="' . $role->id . '">
                        <i class="bx bx-trash text-danger me-1"></i> Delete                    </a>
                </div>
            </div>';
    }

    /**
     * Create a new role.
     *
     * @param array $data
     * @return Role
     */
    public function createRole(array $data): Role
    {
        $role = Role::create(['name' => $data['name']]);
        if (isset($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }
        return $role;
    }

    /**
     * Get role details.
     *
     * @param Role $role
     * @return Role
     */
    public function getRole(Role $role): Role
    {
        return $role->load(['permissions', 'users']);
    }

    /**
     * Update an existing role.
     *
     * @param Role $role
     * @param array $data
     * @return Role
     */
    public function updateRole(Role $role, array $data): Role
    {
        $role->update(['name' => $data['name']]);
        if (isset($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }
        return $role;
    }

    /**
     * Delete a role.
     *
     * @param Role $role
     * @return void
     * @throws BusinessValidationException
     */
    public function deleteRole(Role $role): void
    {
        if ($role->name === 'admin') {
            throw new BusinessValidationException('Cannot delete admin role.');
        }
        if ($role->users()->count() > 0) {
            throw new BusinessValidationException('Cannot delete role that has assigned users.');
        }
        $role->delete();
    }

    /**
     * Get all permissions for dropdown.
     *
     * @return array
     */
    public function getPermissions(): array
    {
        return Permission::all()->toArray();
    }
} 