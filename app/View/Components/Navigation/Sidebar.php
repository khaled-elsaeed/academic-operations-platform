<?php

namespace App\View\Components\Navigation;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Sidebar extends Component
{
    public array $menuItems;

    /**
     * Create a new component instance.
     */
    public function __construct(array $menuItems = [])
    {
        $user = auth()->user();
        $this->menuItems = [];

        if ($user) {
            $groups = $this->getGroupedMenu();

            foreach ($groups as $group) {
                // Check if it's a single item (like Dashboard)
                if (isset($group['permission'])) {
                    if ($user->can($group['permission'])) {
                        $this->menuItems[] = $group;
                    }
                } elseif (isset($group['children'])) {
                    $children = [];
                    
                    // Filter children based on permissions
                    foreach ($group['children'] as $child) {
                        if (isset($child['children'])) {
                            // Handle nested children (like existing enrollments submenu)
                            $nestedChildren = [];
                            foreach ($child['children'] as $nestedChild) {
                                if (!isset($nestedChild['permission']) || $user->can($nestedChild['permission'])) {
                                    $nestedChildren[] = $nestedChild;
                                }
                            }
                            if (count($nestedChildren)) {
                                $child['children'] = $nestedChildren;
                                $children[] = $child;
                            }
                        } else {
                            // Handle direct children
                            if (!isset($child['permission']) || $user->can($child['permission'])) {
                                $children[] = $child;
                            }
                        }
                    }
                    
                    // Only add the group if it has accessible children
                    if (count($children)) {
                        $group['children'] = $children;
                        $this->menuItems[] = $group;
                    }
                } else {
                    // Items without permissions or children (like Dashboard)
                    $this->menuItems[] = $group;
                }
            }
        }
    }

    public function render(): View|Closure|string
    {
        return view('components.navigation.sidebar');
    }

    private function getGroupedMenu(): array
    {
        return array_merge(
            $this->getDashboardMenu(),
            [
                [
                    'title' => 'User Management',
                    'icon' => 'bx bx-group',
                    'route' => '#',
                    'type' => 'group',
                    'active' => request()->routeIs('students.*') || request()->routeIs('users.*'),
                    'children' => [
                        [
                            'title' => 'Students',
                            'icon' => 'bx bx-user',
                            'route' => route('students.index'),
                            'active' => request()->routeIs('students.*'),
                            'permission' => 'student.view',
                        ],
                        [
                            'title' => 'Users',
                            'icon' => 'bx bx-user-circle',
                            'route' => route('users.index'),
                            'active' => request()->routeIs('users.*'),
                            'permission' => 'user.view',
                        ],
                    ]
                ],
                [
                    'title' => 'Academic Structure',
                    'icon' => 'bx bx-building-house',
                    'route' => '#',
                    'type' => 'group',
                    'active' => request()->routeIs('faculties.*') || request()->routeIs('programs.*') || request()->routeIs('terms.*') || request()->routeIs('courses.*'),
                    'children' => [
                        [
                            'title' => 'Faculties',
                            'icon' => 'bx bx-building',
                            'route' => route('faculties.index'),
                            'active' => request()->routeIs('faculties.*'),
                            'permission' => 'faculty.view',
                        ],
                        [
                            'title' => 'Programs',
                            'icon' => 'bx bx-book-open',
                            'route' => route('programs.index'),
                            'active' => request()->routeIs('programs.*'),
                            'permission' => 'program.view',
                        ],
                        [
                            'title' => 'Terms',
                            'icon' => 'bx bx-calendar',
                            'route' => route('terms.index'),
                            'active' => request()->routeIs('terms.*'),
                            'permission' => 'term.view',
                        ],
                        [
                            'title' => 'Courses',
                            'icon' => 'bx bx-book',
                            'route' => route('courses.index'),
                            'active' => request()->routeIs('courses.*'),
                            'permission' => 'course.view',
                        ],
                    ]
                ],
                [
                    'title' => 'Course Management',
                    'icon' => 'bx bx-library',
                    'route' => '#',
                    'type' => 'group',
                    'active' => request()->routeIs('enrollments.*') || request()->routeIs('available_courses.*') || request()->routeIs('credit-hours-exceptions.*'),
                    'children' => [
                        [
                            'title' => 'Enrollments',
                            'icon' => 'bx bx-list-check',
                            'route' => '#',
                            'active' => request()->routeIs('enrollments.*'),
                            'children' => [
                                [
                                    'title' => 'View Enrollments',
                                    'icon' => 'bx bx-table',
                                    'route' => route('enrollments.index'),
                                    'active' => request()->routeIs('enrollments.index'),
                                    'permission' => 'enrollment.view',
                                ],
                                [
                                    'title' => 'Add Enrollment',
                                    'icon' => 'bx bx-plus',
                                    'route' => route('enrollments.add'),
                                    'active' => request()->routeIs('enrollments.add'),
                                    'permission' => 'enrollment.create',
                                ],
                            ],
                        ],
                        [
                            'title' => 'Available Courses',
                            'icon' => 'bx bx-book',
                            'route' => '#',
                            'active' => request()->routeIs('available_courses.*'),
                            'children' => [
                                [
                                    'title' => 'View Available Courses',
                                    'icon' => 'bx bx-table',
                                    'route' => route('available_courses.index'),
                                    'active' => request()->routeIs('available_courses.index'),
                                    'permission' => 'course.view',
                                ],
                                [
                                    'title' => 'Add Available Course',
                                    'icon' => 'bx bx-plus',
                                    'route' => route('available_courses.create'),
                                    'active' => request()->routeIs('available_courses.create'),
                                    'permission' => 'course.create',
                                ],
                            ],
                        ],
                        [
                            'title' => 'Credit Hours Exceptions',
                            'icon' => 'bx bx-time',
                            'route' => route('credit-hours-exceptions.index'),
                            'active' => request()->routeIs('credit-hours-exceptions.*'),
                            'permission' => 'credit_hours_exception.view',
                        ],
                    ]
                ],
                [
                    'title' => 'System Administration',
                    'icon' => 'bx bx-cog',
                    'route' => '#',
                    'type' => 'group',
                    'active' => request()->routeIs('roles.*') || request()->routeIs('permissions.*') || request()->routeIs('academic_advisor_access.*'),
                    'children' => [
                        [
                            'title' => 'Roles & Permissions',
                            'icon' => 'bx bx-shield',
                            'route' => '#',
                            'active' => request()->routeIs('roles.*') || request()->routeIs('permissions.*') || request()->routeIs('academic_advisor_access.*'),
                            'children' => [
                                [
                                    'title' => 'Roles',
                                    'icon' => 'bx bx-shield-quarter',
                                    'route' => route('roles.index'),
                                    'active' => request()->routeIs('roles.*'),
                                    'permission' => 'role.view',
                                ],
                                [
                                    'title' => 'Permissions',
                                    'icon' => 'bx bx-key',
                                    'route' => route('permissions.index'),
                                    'active' => request()->routeIs('permissions.*'),
                                    'permission' => 'permission.view',
                                ],
                                [
                                    'title' => 'Advisor Access',
                                    'icon' => 'bx bx-user-check',
                                    'route' => route('academic_advisor_access.index'),
                                    'active' => request()->routeIs('academic_advisor_access.*'),
                                    'permission' => 'user.view',
                                ],
                            ],
                        ],
                    ]
                ],
                [
                    'title' => 'Account Settings',
                    'icon' => 'bx bx-user-circle',
                    'route' => route('account-settings.index'),
                    'active' => request()->routeIs('account-settings.*'),
                ],
            ]
        );
    }

    private function getDashboardMenu(): array
    {
        return [[
            'title' => 'Dashboard',
            'icon' => 'bx bx-home-circle',
            'route' => route('home'),
            'active' => in_array(request()->route()->getName(), ['home', 'admin.home', 'advisor.home']),
        ]];
    }
}