<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdvisorStudentAccessController extends Controller
{
    /**
     * Display a listing of advisors.
     */
    public function index(): JsonResponse
    {
        $advisors = User::whereHas('roles', function ($query) {
                $query->where('name', 'advisor');
            })
            ->select('id', 'first_name', 'last_name')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => "{$user->first_name} {$user->last_name}",
                ];
            });

        return response()->json(['data' => $advisors]);
    }
}
