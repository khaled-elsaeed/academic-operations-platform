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
                $filteredGroup = $this->filterMenuItem($group, $user);
                if ($filteredGroup !== null) {
                    $this->menuItems[] = $filteredGroup;
                }
            }
        }
    }

    public function render(): View|Closure|string
    {
        return view('components.navigation.sidebar');
    }

    /**
     * Recursively filter menu items and their children by permissions.
     */
    private function filterMenuItem(array $item, $user)
    {
        // If the item has a permission, check it
        if (isset($item['permission']) && !$user->can($item['permission'])) {
            return null;
        }

        // If the item has children, filter them recursively
        if (isset($item['children'])) {
            $filteredChildren = [];
            foreach ($item['children'] as $child) {
                $filteredChild = $this->filterMenuItem($child, $user);
                if ($filteredChild !== null) {
                    $filteredChildren[] = $filteredChild;
                }
            }
            if (count($filteredChildren)) {
                $item['children'] = $filteredChildren;
                return $item;
            } else {
                // If no children left, don't show this group
                return null;
            }
        }

        // Otherwise, return the item
        return $item;
    }

    private function getGroupedMenu(): array
    {
        return [
            // Dashboard - Always first
            [
                'title' => 'Dashboard',
                'icon' => 'bx bx-home-circle',
                'route' => route('home'),
                'active' => in_array(request()->route()->getName(), ['home', 'admin.home', 'advisor.home']),
            ],

            // Academic Management - Core academic structure
            [
                'title' => 'Academic Management',
                'icon' => 'bx bx-building-house',
                'route' => '#',
                'type' => 'group',
                'active' => $this->isActiveGroup(['faculties', 'programs', 'terms', 'courses', 'available_courses']),
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
                    [
                        'title' => 'Available Courses',
                        'icon' => 'bx bx-book-bookmark',
                        'route' => '#',
                        'active' => request()->routeIs('available_courses.*'),
                        'children' => [
                            [
                                'title' => 'View Available Courses',
                                'icon' => 'bx bx-table',
                                'route' => route('available_courses.index'),
                                'active' => request()->routeIs('available_courses.index'),
                                'permission' => 'available_course.view',
                            ],
                            [
                                'title' => 'Add Available Course',
                                'icon' => 'bx bx-plus',
                                'route' => route('available_courses.create'),
                                'active' => request()->routeIs('available_courses.create'),
                                'permission' => 'available_course.create',
                            ],
                        ],
                    ],
                ]
            ],

            // Scheduling - Time-based management
            [
                'title' => 'Scheduling',
                'icon' => 'bx bx-calendar',
                'route' => '#',
                'type' => 'group',
                'active' => $this->isActiveGroup(['schedules', 'schedule-slots']),
                'children' => [
                    [
                        'title' => 'Schedules',
                        'icon' => 'bx bx-calendar-alt',
                        'route' => route('schedules.index'),
                        'active' => request()->routeIs('schedules.index'),
                        'permission' => 'schedule.view',
                    ],
                    [
                        'title' => 'Time Slots',
                        'icon' => 'bx bx-time-five',
                        'route' => route('schedule-slots.index'),
                        'active' => request()->routeIs('schedule-slots.index'),
                        'permission' => 'schedule.view',
                    ],
                ],
            ],

            // Student Management - All student-related activities
            [
                'title' => 'Student Management',
                'icon' => 'bx bx-user-plus',
                'route' => '#',
                'type' => 'group',
                'active' => $this->isActiveGroup(['students', 'enrollments', 'credit-hours-exceptions']),
                'children' => [
                    [
                        'title' => 'Students',
                        'icon' => 'bx bx-user',
                        'route' => route('students.index'),
                        'active' => request()->routeIs('students.*'),
                        'permission' => 'student.view',
                    ],
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
                            [
                                'title' => 'Add Enrollment (Old)',
                                'icon' => 'bx bx-history',
                                'route' => route('enrollments.add.old'),
                                'active' => request()->routeIs('enrollments.add.old'),
                                'permission' => 'enrollment.create-old',
                            ],
                            [
                                'title' => 'Export Documents',
                                'icon' => 'bx bx-download',
                                'route' => route('enrollments.exportDocuments.page'),
                                'active' => request()->routeIs('enrollments.exportDocuments*'),
                                'permission' => 'enrollment.export',
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
                ],
            ],

            // User & Access Management - Combined user management and system admin
            [
                'title' => 'User & Access Management',
                'icon' => 'bx bx-shield',
                'route' => '#',
                'type' => 'group',
                'active' => $this->isActiveGroup(['users', 'roles', 'permissions', 'academic_advisor_access']),
                'children' => [
                    [
                        'title' => 'Users',
                        'icon' => 'bx bx-user-circle',
                        'route' => route('users.index'),
                        'active' => request()->routeIs('users.*'),
                        'permission' => 'user.view',
                    ],
                    [
                        'title' => 'Roles & Permissions',
                        'icon' => 'bx bx-shield-quarter',
                        'route' => '#',
                        'active' => request()->routeIs('roles.*') || request()->routeIs('permissions.*'),
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
                        ],
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

            // Account Settings - Always last
            [
                'title' => 'Account Settings',
                'icon' => 'bx bx-cog',
                'route' => route('account-settings.index'),
                'active' => request()->routeIs('account-settings.*'),
            ],
        ];
    }

    /**
     * Helper method to check if any route in a group is active
     */
    private function isActiveGroup(array $routePrefixes): bool
    {
        foreach ($routePrefixes as $prefix) {
            if (request()->routeIs($prefix . '.*')) {
                return true;
            }
        }
        return false;
    }
}