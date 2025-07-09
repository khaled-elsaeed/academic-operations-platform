<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Level;
use Illuminate\Http\JsonResponse;

class LevelController extends Controller
{
    public function index(): JsonResponse
    {
        $levels = Level::all();
        return response()->json([
            'message' => 'Levels fetched successfully.',
            'data' => $levels
        ]);
    }
} 