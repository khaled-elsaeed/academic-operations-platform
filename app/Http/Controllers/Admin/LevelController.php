<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use App\Services\Admin\LevelService;
use Exception;

class LevelController extends Controller
{
    /**
     * LevelController constructor.
     *
     * @param LevelService $levelService
     */
    public function __construct(protected LevelService $levelService)
    {}

    public function index(): JsonResponse
    {
        try {
            $levels = $this->levelService->getLevels();
            return successResponse('Levels fetched successfully.', $levels);
        } catch (Exception $e) {
            logError('LevelController@index', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }
} 