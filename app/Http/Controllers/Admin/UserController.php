<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\Facades\DataTables;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display the user management page
     */
    public function index()
    {
        return view('admin.user');
    }

    /**
     * Get user statistics
     */
    public function stats(): JsonResponse
    {
        $totalUsers = User::count();
        $activeUsers = User::whereNotNull('email_verified_at')->count();
        $adminUsers = User::role('admin')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total' => [
                    'total' => $totalUsers,
                    'lastUpdateTime' => now()->format('Y-m-d H:i:s')
                ],
                'active' => [
                    'total' => $activeUsers,
                    'lastUpdateTime' => now()->format('Y-m-d H:i:s')
                ],
                'admin' => [
                    'total' => $adminUsers,
                    'lastUpdateTime' => now()->format('Y-m-d H:i:s')
                ]
            ]
        ]);
    }

    /**
     * Get user data for DataTables
     */
    public function datatable(): JsonResponse
    {
        $users = User::with('roles');

        return DataTables::of($users)
            ->addColumn('name', function ($user) {
                return $user->name;
            })
            ->addColumn('roles', function ($user) {
                return $user->roles->pluck('name')->implode(', ');
            })
            ->addColumn('status', function ($user) {
                return $user->email_verified_at ? 
                    '<span class="badge bg-success">Active</span>' : 
                    '<span class="badge bg-warning">Pending</span>';
            })
            ->addColumn('actions', function ($user) {
                return '
                    <div class="dropdown">
                        <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                            <i class="bx bx-dots-vertical-rounded"></i>
                        </button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="javascript:void(0);" onclick="viewUser(' . $user->id . ')">
                                <i class="bx bx-show me-1"></i> View
                            </a>
                            <a class="dropdown-item" href="javascript:void(0);" onclick="editUser(' . $user->id . ')">
                                <i class="bx bx-edit-alt me-1"></i> Edit
                            </a>
                            <a class="dropdown-item" href="javascript:void(0);" onclick="deleteUser(' . $user->id . ')">
                                <i class="bx bx-trash me-1"></i> Delete
                            </a>
                        </div>
                    </div>';
            })
            ->rawColumns(['status', 'actions'])
            ->make(true);
    }

    /**
     * Store a new user
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'roles' => 'array|exists:roles,name'
        ]);

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'email_verified_at' => now(),
        ]);

        if ($request->has('roles')) {
            $user->assignRole($request->roles);
        }

        return response()->json([
            'success' => true,
            'message' => 'User created successfully.',
            'data' => $user
        ]);
    }

    /**
     * Show user details
     */
    public function show(User $user): JsonResponse
    {
        $user->load('roles');
        
        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    /**
     * Update user
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8|confirmed',
            'roles' => 'array|exists:roles,name'
        ]);

        $user->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
        ]);

        if ($request->filled('password')) {
            $user->update(['password' => Hash::make($request->password)]);
        }

        if ($request->has('roles')) {
            $user->syncRoles($request->roles);
        }

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully.',
            'data' => $user
        ]);
    }

    /**
     * Delete user
     */
    public function destroy(User $user): JsonResponse
    {
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot delete your own account.'
            ], 422);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully.'
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
} 