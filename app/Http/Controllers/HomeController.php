<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use App\Services\HomeService;
use Exception;

class HomeController extends Controller
{
    /**
     * HomeController constructor.
     *
     * @param HomeService $homeService
     */
    public function __construct(protected HomeService $homeService)
    {}

    /**
     * Display the main home page (redirects based on user role).
     */
    public function __invoke(): View
    {
        if (auth()->user()->hasRole('admin')) {
            return $this->adminHome();
        } elseif (auth()->user()->hasRole('advisor')) {
            return $this->advisorHome();
        }
        
        // Default fallback
        return $this->adminHome();
    }

    /**
     * Display the admin home page.
     */
    public function adminHome(): View
    {
        return view('home.admin');
    }

    /**
     * Display the advisor home page.
     */
    public function advisorHome(): View
    {
        return view('home.advisor');
    }

    /**
     * Get admin dashboard statistics.
     */
    public function adminStats(): JsonResponse
    {
        try {
            $stats = $this->homeService->getAdminDashboardStats();
            return successResponse('Admin dashboard stats fetched successfully.', $stats);
        } catch (Exception $e) {
            logError('HomeController@adminStats', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Get advisor dashboard statistics.
     */
    public function advisorStats(): JsonResponse
    {
        try {
            $stats = $this->homeService->getAdvisorDashboardStats();
            return successResponse('Advisor dashboard stats fetched successfully.', $stats);
        } catch (Exception $e) {
            logError('HomeController@advisorStats', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }
} 