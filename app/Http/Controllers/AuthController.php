<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{

    public function __construct(protected AuthService $authService)
    {}

    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
        $result = $this->authService->login($validated);
        if ($result) {
            $user = auth()->user();
            if ($user->hasRole('admin')) {
                return redirect()->intended(route('admin.home'));
            } elseif ($user->hasRole('advisor')) {
                return redirect()->intended(route('advisor.home'));
            } elseif ($user->hasRole('schedules_committee')) {
                return redirect()->intended(route('schedules_committee.home'));
            }
        }
        return back()->withErrors(['email' => 'Invalid credentials.']);
    }

    public function logout(Request $request)
    {
        $this->authService->logout();
        return redirect('/login');
    }
}
