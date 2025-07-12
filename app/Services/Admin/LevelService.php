<?php

namespace App\Services\Admin;

use App\Models\Level;

class LevelService
{
    /**
     * Get all levels.
     *
     * @return array
     */
    public function getLevels(): array
    {
        return Level::all()->toArray();
    }
} 