<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Admin\HomeService;

class HomeController extends Controller
{
    public function home(HomeService $homeService)
    {
        return view('admin.home');
    }

    public function stats(HomeService $homeService)
    {
        $stats = $homeService->getDashboardStats();
        return successResponse('Dashboard stats fetched successfully.', $stats);
    }
} 