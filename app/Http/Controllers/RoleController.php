<?php

namespace App\Http\Controllers;

use Illuminate\Http\{Request, JsonResponse};
use Illuminate\View\View;
use App\Services\RoleService;
use Spatie\Permission\Models\Role;
use App\Exceptions\BusinessValidationException;
use Exception;

class RoleController extends Controller
{
    /**
     * RoleController constructor.
     *
     * @param RoleService $roleService
     */
    public function __construct(protected RoleService $roleService)
    {}

    /**
     * Display the role management page.
     *
     * @return View
     */
    public function index(): View
    {
        return view('role.index');
    }

    /**
     * Get role statistics.
     *
     * @return JsonResponse
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = $this->roleService->getStats();
            return successResponse('Stats fetched successfully.', $stats);
        } catch (Exception $e) {
            logError('RoleController@stats', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Get role data for DataTables.
     *
     * @return JsonResponse
     */
    public function datatable(): JsonResponse
    {
        try {
            return $this->roleService->getDatatable();
        } catch (Exception $e) {
            logError('RoleController@datatable', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Store a new role.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles',
            'permissions' => 'array|exists:permissions,name'
        ]);

        try {
            $validated = $request->all();
            $role = $this->roleService->createRole($validated);
            return successResponse('Role created successfully.', $role);
        } catch (Exception $e) {
            logError('RoleController@store', $e, ['request' => $request->all()]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Show role details.
     *
     * @param Role $role
     * @return JsonResponse
     */
    public function show(Role $role): JsonResponse
    {
        try {
            $role = $this->roleService->getRole($role);
            return successResponse('Role details fetched successfully.', $role);
        } catch (Exception $e) {
            logError('RoleController@show', $e, ['role_id' => $role->id]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Update role.
     *
     * @param Request $request
     * @param Role $role
     * @return JsonResponse
     */
    public function update(Request $request, Role $role): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'permissions' => 'array|exists:permissions,name'
        ]);

        try {
            $validated = $request->all();
            $role = $this->roleService->updateRole($role, $validated);
            return successResponse('Role updated successfully.', $role);
        } catch (Exception $e) {
            logError('RoleController@update', $e, ['role_id' => $role->id, 'request' => $request->all()]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Delete role.
     *
     * @param Role $role
     * @return JsonResponse
     */
    public function destroy(Role $role): JsonResponse
    {
        try {
            $this->roleService->deleteRole($role);
            return successResponse('Role deleted successfully.');
        } catch (BusinessValidationException $e) {
            return errorResponse($e->getMessage(), [], $e->getCode());
        } catch (Exception $e) {
            logError('RoleController@destroy', $e, ['role_id' => $role->id]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Get all permissions for dropdown.
     *
     * @return JsonResponse
     */
    public function getPermissions(): JsonResponse
    {
        try {
            $permissions = $this->roleService->getPermissions();
            return successResponse('Permissions fetched successfully.', $permissions);
        } catch (Exception $e) {
            logError('RoleController@getPermissions', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }
} 