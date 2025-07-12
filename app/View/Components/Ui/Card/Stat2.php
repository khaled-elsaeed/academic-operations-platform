<?php

namespace App\View\Components\Ui\Card;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Stat2 extends Component
{
    public $color;
    public $icon;
    public $label;
    public $id;

    /**
     * Create a new component instance.
     */
    public function __construct($color = 'primary', $icon = 'bx bx-user', $label = '', $id = null)
    {
        $this->color = $color;
        $this->icon = $icon;
        $this->label = $label;
        $this->id = $id;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.ui.card.stat2');
    }
} 