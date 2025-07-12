<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class HomeController extends Controller
{
    public function __invoke()
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            Log::info('User not authenticated, redirecting to login');
            return redirect()->route('login');
        }
        
        $user = Auth::user();
        
        // Double-check that user exists and has roles
        if (!$user || !method_exists($user, 'hasRole')) {
            // Log the user out and redirect to login
            Auth::logout();
            return redirect()->route('login')->with('error', 'Authentication error. Please log in again.');
        }
        
        if ($user->hasRole('admin')) {
            return redirect()->route('admin.home');
        } elseif ($user->hasRole('advisor')) {
            return redirect()->route('advisor.home');
        }
        
        Auth::logout();
        return redirect()->route('login')->with('error', 'No valid role assigned. Please contact administrator.');
    }
} 