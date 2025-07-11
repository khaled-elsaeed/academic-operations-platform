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
            'permission.create',
            'permission.edit',
            'permission.delete',
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
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $advisorRole = Role::firstOrCreate(['name' => 'advisor']);
        
        // Admin gets all permissions
        $adminRole->givePermissionTo(Permission::all());
        
        // Advisor gets limited permissions
        $advisorPermissions = [
            'student.view',
            'enrollment.view',
            'enrollment.create',
            'enrollment.edit',
        ];
        
        $advisorRole->givePermissionTo($advisorPermissions);
    }
} 