<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FcmConfigController extends Controller
{
    public function getConfig()
    {
        try {
            // Only provide config to authenticated users
            if (!Auth::check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            // Get Firebase configuration from environment variables
            $firebaseConfig = [
                'apiKey' => env('FIREBASE_API_KEY'),
                'authDomain' => env('FIREBASE_AUTH_DOMAIN'),
                'projectId' => env('FIREBASE_PROJECT_ID'),
                'storageBucket' => env('FIREBASE_STORAGE_BUCKET'),
                'messagingSenderId' => env('FIREBASE_MESSAGING_SENDER_ID'),
                'appId' => env('FIREBASE_APP_ID')
            ];

            // Validate that all required configuration values are present
            $requiredFields = ['projectId', 'apiKey', 'authDomain', 'messagingSenderId', 'appId'];
            $missingFields = [];

            foreach ($requiredFields as $field) {
                if (empty($firebaseConfig[$field])) {
                    $missingFields[] = $field;
                }
            }

            if (!empty($missingFields)) {
                Log::warning('Firebase configuration missing required fields: ' . implode(', ', $missingFields));
                return response()->json([
                    'success' => false,
                    'message' => 'Firebase configuration is incomplete. Missing: ' . implode(', ', $missingFields),
                    'missing_fields' => $missingFields
                ], 500);
            }

            // Return Firebase configuration
            return response()->json([
                'success' => true,
                'config' => $firebaseConfig
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting FCM config: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving configuration'
            ], 500);
        }
    }
}
