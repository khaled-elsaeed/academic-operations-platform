<?php

namespace App\Services;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\Facades\DataTables;
use App\Exceptions\BusinessValidationException;

class UserService
{
    /**
     * Get user statistics.
     *
     * @return array
     */
    public function getStats(): array
    {
        $totalUsers = User::count();
        $activeUsers = User::whereNotNull('email_verified_at')->count();
        $adminUsers = User::role('admin')->count();

        return [
            'total' => [
                'total' => $totalUsers,
                'lastUpdateTime' => formatDate(now(), 'Y-m-d H:i:s')
            ],
            'active' => [
                'total' => $activeUsers,
                'lastUpdateTime' => formatDate(now(), 'Y-m-d H:i:s')
            ],
            'admin' => [
                'total' => $adminUsers,
                'lastUpdateTime' => formatDate(now(), 'Y-m-d H:i:s')
            ]
        ];
    }

    /**
     * Get user data for DataTables.
     *
     * @return JsonResponse
     */
    public function getDatatable(): JsonResponse
    {
        $users = User::with('roles');

        return DataTables::of($users)
            ->addColumn('name', function ($user) {
                return $user->name;
            })
            ->addColumn('roles', function ($user) {
                return $user->roles->pluck('name')->implode(', ');
            })
            ->addColumn('actions', function ($user) {
                return $this->renderActionButtons($user);
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    /**
     * Render action buttons for datatable rows.
     *
     * @param User $user
     * @return string
     */
    protected function renderActionButtons($user): string
    {
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
    }

    /**
     * Create a new user.
     *
     * @param array $data
     * @return User
     */
    public function createUser(array $data): User
    {
        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'gender' => $data['gender'],
            'email_verified_at' => now(),
        ]);

        if (isset($data['roles'])) {
            $user->assignRole($data['roles']);
        }

        return $user;
    }

    /**
     * Get user details.
     *
     * @param User $user
     * @return User
     */
    public function getUser(User $user): User
    {
        return $user->load('roles');
    }

    /**
     * Update an existing user.
     *
     * @param User $user
     * @param array $data
     * @return User
     */
    public function updateUser(User $user, array $data): User
    {
        $user->update([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'gender' => $data['gender'],
        ]);

        if (isset($data['password']) && $data['password']) {
            $user->update(['password' => Hash::make($data['password'])]);
        }

        if (isset($data['roles'])) {
            $user->syncRoles($data['roles']);
        }

        return $user;
    }

    /**
     * Delete a user.
     *
     * @param User $user
     * @return void
     * @throws BusinessValidationException
     */
    public function deleteUser(User $user): void
    {
        if ($user->id === auth()->id()) {
            throw new BusinessValidationException('You cannot delete your own account.');
        }

        $user->delete();
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