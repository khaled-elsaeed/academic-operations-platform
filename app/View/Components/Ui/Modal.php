<?php

namespace App\View\Components\Ui;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Modal extends Component
{
    public string $id;
    public string $title;
    public string $size;
    public bool $scrollable;
    public string $class;

    /**
     * Create a new component instance.
     */
    public function __construct(
        string $id = 'modalScrollable',
        string $title = '',
        string $size = '',
        bool $scrollable = false,
        string $class = ''
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->size = $size;
        $this->scrollable = $scrollable;
        $this->class = $class;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.ui.modal');
    }
}
