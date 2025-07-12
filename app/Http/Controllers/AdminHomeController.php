<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use App\Services\AdminHomeService;
use Exception;

class AdminHomeController extends Controller
{
    /**
     * HomeController constructor.
     *
     * @param AdminHomeService $homeService
     */
    public function __construct(protected AdminHomeService $homeService)
    {}

    /**
     * Display the admin home page.
     */
    public function home(): View
    {
        return view('home.admin');
    }

    /**
     * Get dashboard statistics.
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = $this->homeService->getDashboardStats();
            return successResponse('Dashboard stats fetched successfully.', $stats);
        } catch (Exception $e) {
            logError('HomeController@stats', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }
} 