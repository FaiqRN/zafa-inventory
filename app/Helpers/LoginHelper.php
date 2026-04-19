<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;
use App\Models\User;

class LoginHelper
{

    public static function logLogin(int $userId, string $message, array $metadata = []): void
    {
        try {
            $user = User::find($userId);
            
            if ($user) {
                $logMessage = sprintf(
                    "User %s (%s) logged in successfully",
                    $user->firstname . ' ' . $user->lastname,
                    $user->email
                );
                
                $logData = array_merge([
                    'user_id' => $userId,
                    'username' => $user->username,
                    'email' => $user->email,
                    'name' => $user->firstname . ' ' . $user->lastname,
                ], $metadata);
                
                Log::info($logMessage, $logData);
            } else {
                Log::warning("Login attempt for non-existent user ID: {$userId}");
            }
        } catch (\Exception $e) {
            Log::error("Failed to log login activity: " . $e->getMessage());
        }
    }

    public static function logLogout(int $userId, string $message, array $metadata = []): void
    {
        try {
            $user = User::find($userId);
            
            if ($user) {
                $logMessage = sprintf(
                    "User %s (%s) logged out",
                    $user->firstname . ' ' . $user->lastname,
                    $user->email
                );
                
                $logData = array_merge([
                    'user_id' => $userId,
                    'username' => $user->username,
                    'email' => $user->email,
                    'name' => $user->firstname . ' ' . $user->lastname,
                ], $metadata);
                
                Log::info($logMessage, $logData);
            } else {
                Log::warning("Logout attempt for non-existent user ID: {$userId}");
            }
        } catch (\Exception $e) {
            Log::error("Failed to log logout activity: " . $e->getMessage());
        }
    }
}
