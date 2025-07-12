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
            $items = $this->getAdminMenuItems();
            foreach ($items as $item) {
                // Check permission for parent or children
                if (isset($item['permission'])) {
                    if ($user->can($item['permission'])) {
                        $this->menuItems[] = $item;
                    }
                } elseif (isset($item['children'])) {
                    $children = [];
                    foreach ($item['children'] as $child) {
                        if (!isset($child['permission']) || $user->can($child['permission'])) {
                            $children[] = $child;
                        }
                    }
                    if (count($children)) {
                        $item['children'] = $children;
                        $this->menuItems[] = $item;
                    }
                } else {
                    // No permission specified, show by default
                    $this->menuItems[] = $item;
                }
            }
        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.navigation.sidebar');
    }

    /**
     * Get Admin menu items
     */
    private function getAdminMenuItems(): array
    {
        $dashboardRoutes = ['home.redirect', 'admin.home', 'advisor.home'];
        return [
            [
                'title' => 'Home',
                'icon' => 'bx bx-home-circle',
                'route' => route('home.redirect'),
                'active' => in_array(request()->route()->getName(), $dashboardRoutes),
            ],
            [
                'title' => 'Students',
                'icon' => 'bx bx-user',
                'route' => route('admin.students.index'),
                'active' => request()->routeIs('admin.students.*'),
                'permission' => 'student.view',
            ],
            [
                'title' => 'Faculties',
                'icon' => 'bx bx-building',
                'route' => route('admin.faculties.index'),
                'active' => request()->routeIs('admin.faculties.*'),
                'permission' => 'faculty.view',
            ],
            [
                'title' => 'Programs',
                'icon' => 'bx bx-book-open',
                'route' => route('admin.programs.index'),
                'active' => request()->routeIs('admin.programs.*'),
                'permission' => 'program.view',
            ],
            [
                'title' => 'Courses',
                'icon' => 'bx bx-book',
                'route' => route('admin.courses.index'),
                'active' => request()->routeIs('admin.courses.*'),
                'permission' => 'course.view',
            ],
            [
                'title' => 'Enrollments',
                'icon' => 'bx bx-list-check',
                'route' => '#',
                'active' => request()->routeIs('admin.enrollments.*'),
                'children' => [
                    [
                        'title' => 'View Enrollments',
                        'icon' => 'bx bx-table',
                        'route' => route('admin.enrollments.index'),
                        'active' => request()->routeIs('admin.enrollments.index'),
                        'permission' => 'enrollment.view',
                    ],
                    [
                        'title' => 'Add Enrollment',
                        'icon' => 'bx bx-plus',
                        'route' => route('admin.enrollments.add'),
                        'active' => request()->routeIs('admin.enrollments.add'),
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
                'permission' => 'enrollment.view',
            ],
            [
                'title' => 'Users',
                'icon' => 'bx bx-user-circle',
                'route' => route('admin.users.index'),
                'active' => request()->routeIs('admin.users.*'),
                'permission' => 'user.view',
            ],
            [
                'title' => 'Roles & Permissions',
                'icon' => 'bx bx-shield',
                'route' => '#',
                'active' => request()->routeIs('admin.roles.*') || request()->routeIs('admin.permissions.*') || request()->routeIs('admin.academic_advisor_access.*'),
                'children' => [
                    [
                        'title' => 'Roles',
                        'icon' => 'bx bx-shield-quarter',
                        'route' => route('admin.roles.index'),
                        'active' => request()->routeIs('admin.roles.*'),
                        'permission' => 'role.view',
                    ],
                    [
                        'title' => 'Permissions',
                        'icon' => 'bx bx-key',
                        'route' => route('admin.permissions.index'),
                        'active' => request()->routeIs('admin.permissions.*'),
                        'permission' => 'permission.view',
                    ],
                    [
                        'title' => 'Advisor Access',
                        'icon' => 'bx bx-user-check',
                        'route' => route('admin.academic_advisor_access.index'),
                        'active' => request()->routeIs('admin.academic_advisor_access.*'),
                        'permission' => 'user.view',
                    ],
                ],
            ],
        ];
    }
}