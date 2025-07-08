<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect('/login');
        }
        if ($user->hasRole('admin')) {
            return redirect('/admin/home');
        }
        return redirect('/login');
    }
} 