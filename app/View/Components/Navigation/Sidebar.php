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
        if (!empty($menuItems)) {
            $this->menuItems = $menuItems;
        } else {
            $user = auth()->user();
            if ($user && $user->hasRole('admin')) {
                $this->menuItems = $this->getAdminMenuItems();
            } else {
                $this->menuItems = [];
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
     * Get Admin menu items (only Dashboard)
     */
    private function getAdminMenuItems(): array
    {
        return [
            [
                'title' => 'Home',
                'icon' => 'bx bx-home-circle',
                'route' => route('admin.home'),
                'active' => request()->routeIs('admin.home'),
            ],
            [
                'title' => 'Students',
                'icon' => 'bx bx-user',
                'route' => route('admin.students.index'),
                'active' => request()->routeIs('admin.students.*'),
            ],
            [
                'title' => 'Available Courses',
                'icon' => 'bx bx-book',
                'route' => route('admin.available_courses.index'),
                'active' => request()->routeIs('admin.available_courses.*'),
            ],
        ];
    }
}