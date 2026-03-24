<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FCMController extends Controller
{
    /**
     * Store the FCM token for the authenticated user.
     */
    public function saveToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $user = Auth::user();
        if ($user) {
            $user->update([
                'fcm_token' => $request->token,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Token saved successfully.',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'User not authenticated.',
        ], 401);
    }
}
