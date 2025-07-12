<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use App\Services\Admin\PermissionService;
use Exception;

class PermissionController extends Controller
{
    /**
     * PermissionController constructor.
     *
     * @param PermissionService $permissionService
     */
    public function __construct(protected PermissionService $permissionService)
    {}

    /**
     * Display the permission management page
     */
    public function index(): View
    {
        return view('admin.permission');
    }

    /**
     * Get permission statistics
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = $this->permissionService->getStats();
            return successResponse('Stats fetched successfully.', $stats);
        } catch (Exception $e) {
            logError('PermissionController@stats', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Get permission data for DataTables
     */
    public function datatable(): JsonResponse
    {
        try {
            return $this->permissionService->getDatatable();
        } catch (Exception $e) {
            logError('PermissionController@datatable', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Show permission details
     */
    public function show(Permission $permission): JsonResponse
    {
        try {
            $permission = $this->permissionService->getPermission($permission);
            return successResponse('Permission details fetched successfully.', $permission);
        } catch (Exception $e) {
            logError('PermissionController@show', $e, ['permission_id' => $permission->id]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Get all roles for dropdown
     */
    public function getRoles(): JsonResponse
    {
        try {
            $roles = $this->permissionService->getRoles();
            return successResponse('Roles fetched successfully.', $roles);
        } catch (Exception $e) {
            logError('PermissionController@getRoles', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }
} 