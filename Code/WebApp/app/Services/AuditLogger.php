<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    public static function log($action, $resourceType, $resourceId, $resourceName, $details = [])
    {
        try {
            $user = Auth::user();
            
            $userId = $user ? $user->id : 'system';
            $userName = $user ? $user->name : 'System / Guest';
            $userEmail = $user ? $user->email : 'system@example.com';
            $userRole = $user ? $user->role : 'guest';

            ActivityLog::create([
                'user_id' => $userId,
                'user_name' => $userName,
                'user_email' => $userEmail,
                'user_role' => $userRole,
                'action' => strtoupper($action),
                'resource_type' => strtolower($resourceType),
                'resource_id' => $resourceId,
                'resource_name' => $resourceName,
                'details' => $details,
                'ip_address' => Request::ip() ?? '127.0.0.1',
                'user_agent' => Request::userAgent() ?? 'N/A',
            ]);
        } catch (\Exception $e) {
            // Silently log exception to prevent breaking the main request
            \Illuminate\Support\Facades\Log::error('Audit log failed: ' . $e->getMessage());
        }
    }
}
