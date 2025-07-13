<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Create all permissions
        $permissions = [
            'user.view',
            'user.create',
            'user.edit',
            'user.delete',
            'role.view',
            'role.create',
            'role.edit',
            'role.delete',
            'permission.view',
            'student.view',
            'student.create',
            'student.edit',
            'student.delete',
            'faculty.view',
            'faculty.create',
            'faculty.edit',
            'faculty.delete',
            'program.view',
            'program.create',
            'program.edit',
            'program.delete',
            'course.view',
            'course.create',
            'course.edit',
            'course.delete',
            'enrollment.view',
            'enrollment.create',
            'enrollment.edit',
            'enrollment.delete',
            'term.view',
            'term.create',
            'term.edit',
            'term.delete',
            // Credit Hours Exception Permissions
            'credit_hours_exception.view',
            'credit_hours_exception.create',
            'credit_hours_exception.edit',
            'credit_hours_exception.delete',
            // Home Dashboard Permissions
            'home.view',
            'home.admin',
            'home.advisor',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $advisorRole = Role::firstOrCreate(['name' => 'advisor']);

        // Admin gets all permissions, but only assign those not already assigned
        $allPermissions = Permission::all();
        $adminCurrentPermissions = $adminRole->permissions->pluck('name')->toArray();
        $adminNewPermissions = $allPermissions->filter(function ($perm) use ($adminCurrentPermissions) {
            return !in_array($perm->name, $adminCurrentPermissions);
        });
        if ($adminNewPermissions->count() > 0) {
            $adminRole->givePermissionTo($adminNewPermissions);
        }

        // Advisor gets limited permissions, but only assign those not already assigned
        $advisorPermissions = [
            'student.view',
            'enrollment.view',
            'enrollment.create',
            'home.view',
            'home.advisor',
        ];
        $advisorCurrentPermissions = $advisorRole->permissions->pluck('name')->toArray();
        $advisorNewPermissions = array_diff($advisorPermissions, $advisorCurrentPermissions);
        if (!empty($advisorNewPermissions)) {
            $advisorRole->givePermissionTo($advisorNewPermissions);
        }
    }
} 