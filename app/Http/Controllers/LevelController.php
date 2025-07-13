<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use App\Services\LevelService;
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

    /**
     * Get all levels (for dropdown and forms)
     */
    public function all(): JsonResponse
    {
        try {
            $levels = $this->levelService->getAll();
            return successResponse('Levels fetched successfully.', $levels);
        } catch (Exception $e) {
            logError('LevelController@all', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }
} 