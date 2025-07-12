<?php

namespace App\Http\Controllers\Advisor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Advisor\HomeService;

class HomeController extends Controller
{
    public function home(HomeService $homeService)
    {
        return view('advisor.home');
    }

    public function stats(HomeService $homeService)
    {
        $stats = $homeService->getDashboardStats();
        return successResponse('Advisor dashboard stats fetched successfully.', $stats);
    }
} 