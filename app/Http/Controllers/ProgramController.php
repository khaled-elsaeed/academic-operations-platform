<?php

namespace App\Http\Controllers;

use App\Models\Program;
use Illuminate\Http\JsonResponse;

class ProgramController extends Controller
{
    public function index(): \Illuminate\Http\JsonResponse
    {
        $programs = Program::all();
        return successResponse('Programs fetched successfully.', $programs);
    }
} 