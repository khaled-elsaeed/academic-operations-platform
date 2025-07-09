<?php

namespace App\View\Components\Ui;

use Illuminate\View\Component;

class PageHeader extends Component
{
    public $title;
    public $description;
    public $icon;

    public function __construct($title, $description = null, $icon = null)
    {
        $this->title = $title;
        $this->description = $description;
        $this->icon = $icon;
    }

    public function render()
    {
        return view('components.ui.page-header');
    }
} 