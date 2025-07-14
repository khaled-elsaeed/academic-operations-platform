<?php

namespace App\Services;

use App\Models\User;
use App\Exceptions\BusinessValidationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

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
        $lastUpdatedAt = User::max('updated_at');

        return [
            'total' => [
                'total' => $totalUsers,
                'lastUpdateTime' => formatDate($lastUpdatedAt)
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
        $query = User::with('roles');
        $request = request();

        $this->applySearchFilters($query, $request);

        return DataTables::of($query)
            ->addColumn('name', function ($user) {
                return $user->name;
            })
            ->addColumn('roles', function ($user) {
                return $user->roles->pluck('name')->implode(', ');
            })
            ->addColumn('created_at', function ($user) {
                return formatDate($user->created_at);
            })
            ->addColumn('actions', function ($user) {
                return $this->renderActionButtons($user);
            })
            ->orderColumn('name', 'first_name $1, last_name $1')
            ->orderColumn('roles', function ($query, $direction) {
                $query->orderBy(
                    DB::table('model_has_roles')
                        ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                        ->selectRaw('MIN(roles.name)')
                        ->whereColumn('model_has_roles.model_id', 'users.id'),
                    $direction
                );
            })
            ->orderColumn('created_at', 'created_at $1')
            ->rawColumns(['actions'])
            ->make(true);
    }

    /**
     * Apply search filters to the query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    private function applySearchFilters($query, $request): void
    {
        $searchName = $request->input('search_name');
        if (!empty($searchName)) {
            $query->where(function ($q) use ($searchName) {
                $q->whereRaw('LOWER(first_name) LIKE ?', ['%' . mb_strtolower($searchName) . '%'])
                  ->orWhereRaw('LOWER(last_name) LIKE ?', ['%' . mb_strtolower($searchName) . '%'])
                  ->orWhereRaw('LOWER(CONCAT(first_name, " ", last_name)) LIKE ?', ['%' . mb_strtolower($searchName) . '%']);
            });
        }

        $searchEmail = $request->input('search_email');
        if (!empty($searchEmail)) {
            $query->whereRaw('LOWER(email) LIKE ?', ['%' . mb_strtolower($searchEmail) . '%']);
        }

        $searchRole = $request->input('search_role');
        if (!empty($searchRole)) {
            $query->whereHas('roles', function ($q) use ($searchRole) {
                $q->where('name', $searchRole);
            });
        }
    }

    /**
     * Render action buttons for datatable rows.
     *
     * @param User $user
     * @return string
     */
    protected function renderActionButtons(User $user): string
    {
        return '
            <div class="dropdown">
                <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                    <i class="bx bx-dots-vertical-rounded"></i>
                </button>
                <div class="dropdown-menu">
                    <a class="dropdown-item viewUserBtn" href="javascript:void(0);" data-id="' . $user->id . '">
                        <i class="bx bx-show me-1"></i> View
                    </a>
                    <a class="dropdown-item editUserBtn" href="javascript:void(0);" data-id="' . $user->id . '">
                        <i class="bx bx-edit-alt me-1"></i> Edit
                    </a>
                    <a class="dropdown-item deleteUserBtn" href="javascript:void(0);" data-id="' . $user->id . '">
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