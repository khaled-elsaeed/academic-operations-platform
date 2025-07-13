<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Exception;
use App\Exceptions\BusinessValidationException;

class AccountSettingsController extends Controller
{
    /**
     * Display the account settings page.
     */
    public function index(): View
    {
        $user = auth()->user();
        return view('account-settings.index', compact('user'));
    }

    /**
     * Update user account settings.
     */
    public function update(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'gender' => 'required|in:male,female',
        ]);

        try {
            $user->update([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'gender' => $request->gender,
            ]);

            return successResponse('Account settings updated successfully.');

        } catch (Exception $e) {
            logError('AccountSettingsController@update', $e, ['request' => $request->all()]);
            return errorResponse('Failed to update account settings.', [], 500);
        }
    }

    /**
     * Update user password.
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        try {
            // Check current password
            if (!Hash::check($request->current_password, $user->password)) {
                throw new BusinessValidationException('Current password is incorrect.');
            }

            // Update password
            $user->update([
                'password' => Hash::make($request->new_password)
            ]);

            return successResponse('Password updated successfully.');

        } catch (BusinessValidationException $e) {
            return errorResponse($e->getMessage(), [], $e->getCode());
        } catch (Exception $e) {
            logError('AccountSettingsController@updatePassword', $e, ['request' => $request->all()]);
            return errorResponse('Failed to update password.', [], 500);
        }
    }


} 