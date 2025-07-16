<?php

namespace App\Http\Controllers;

use Illuminate\Http\{Request, JsonResponse};
use Illuminate\View\View;
use App\Services\UserService;
use App\Models\User;
use Illuminate\Validation\Rule;
use App\Exceptions\BusinessValidationException;
use Exception;

class UserController extends Controller
{
    /**
     * UserController constructor.
     *
     * @param UserService $userService
     */
    public function __construct(protected UserService $userService)
    {}

    /**
     * Display the user management page.
     *
     * @return View
     */
    public function index(): View
    {
        return view('user.index');
    }

    /**
     * Get user statistics.
     *
     * @return JsonResponse
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = $this->userService->getStats();
            return successResponse('Stats fetched successfully.', $stats);
        } catch (Exception $e) {
            logError('UserController@stats', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Get user data for DataTables.
     *
     * @return JsonResponse
     */
    public function datatable(): JsonResponse
    {
        try {
            return $this->userService->getDatatable();
        } catch (Exception $e) {
            logError('UserController@datatable', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Store a new user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'roles' => 'array|exists:roles,name',
            'gender' => 'required|in:male,female',
        ]);

        try {
            $validated = $request->all();
            $user = $this->userService->createUser($validated);
            return successResponse('User created successfully.', $user);
        } catch (Exception $e) {
            logError('UserController@store', $e, ['request' => $request->all()]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Show user details.
     *
     * @param User $user
     * @return JsonResponse
     */
    public function show(User $user): JsonResponse
    {
        try {
            $user = $this->userService->getUser($user);
            return successResponse('User details fetched successfully.', $user);
        } catch (Exception $e) {
            logError('UserController@show', $e, ['user_id' => $user->id]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Update user.
     *
     * @param Request $request
     * @param User $user
     * @return JsonResponse
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8|confirmed',
            'roles' => 'array|exists:roles,name',
            'gender' => 'required|in:male,female',
        ]);

        try {
            $validated = $request->all();
            $user = $this->userService->updateUser($user, $validated);
            return successResponse('User updated successfully.', $user);
        } catch (Exception $e) {
            logError('UserController@update', $e, ['user_id' => $user->id, 'request' => $request->all()]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Delete user.
     *
     * @param User $user
     * @return JsonResponse
     */
    public function destroy(User $user): JsonResponse
    {
        try {
            $this->userService->deleteUser($user);
            return successResponse('User deleted successfully.');
        } catch (BusinessValidationException $e) {
            return errorResponse($e->getMessage(), [], $e->getCode());
        } catch (Exception $e) {
            logError('UserController@destroy', $e, ['user_id' => $user->id]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Get all roles for dropdown.
     *
     * @return JsonResponse
     */
    public function getRoles(): JsonResponse
    {
        try {
            $roles = $this->userService->getRoles();
            return successResponse('Roles fetched successfully.', $roles);
        } catch (Exception $e) {
            logError('UserController@getRoles', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }
} 