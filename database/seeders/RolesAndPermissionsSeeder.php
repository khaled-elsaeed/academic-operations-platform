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
            // Student Permissions
            'student.view',
            'student.create',
            'student.edit',
            'student.delete',
            'student.download',
            'student.import',
            'student.export', 
            // Faculty Permissions
            'faculty.view',
            'faculty.create',
            'faculty.edit',
            'faculty.delete',
            // Program Permissions
            'program.view',
            'program.create',
            'program.edit',
            'program.delete',
            // Course Permissions
            'course.view',
            'course.create',
            'course.edit',
            'course.delete',
            // Available
            'available_course.import', 
            // Enrollment Permissions
            'enrollment.view',
            'enrollment.create',
            'enrollment.edit',
            'enrollment.delete',
            'enrollment.import', 
            'enrollment.export',
            // Term Permissions
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
            // Account Settings Permissions
            'account_settings.view',
            'account_settings.edit',
            'account_settings.password',
            // Academic Advisor Access Permissions
            'academic_advisor_access.view',
            'academic_advisor_access.create',
            'academic_advisor_access.edit',
            'academic_advisor_access.delete',
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
            'student.download',
            'enrollment.view',
            'enrollment.create',
            'home.view',
            'home.advisor',
            // Account Settings Permissions for advisor
            'account_settings.view',
            'account_settings.edit',
            'account_settings.password',
        ];
        $advisorCurrentPermissions = $advisorRole->permissions->pluck('name')->toArray();
        $advisorNewPermissions = array_diff($advisorPermissions, $advisorCurrentPermissions);
        if (!empty($advisorNewPermissions)) {
            $advisorRole->givePermissionTo($advisorNewPermissions);
        }
    }
} 