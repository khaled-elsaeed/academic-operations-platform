<?php

namespace App\Services;

use App\Models\Level;

class LevelService
{
    /**
     * Get all levels (for dropdown and forms).
     *
     * @return array
     */
    public function getAll(): array
    {
        return Level::orderBy('name')->get()->map(function ($level) {
            return [
                'id' => $level->id,
                'name' => $level->name,
                'code' => $level->code ?? '',
            ];
        })->toArray();
    }

    /**
     * Get levels for index.
     *
     * @return array
     */
    public function getLevels(): array
    {
        return Level::all()->toArray();
    }
} 