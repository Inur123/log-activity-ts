<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Application;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        // Get API Key from header or query
        $apiKey = $request->header('X-API-Key') ?? $request->input('api_key');

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'API Key is required'
            ], 401);
        }

        // Validate API Key
        $application = Application::where('api_key', $apiKey)
            ->where('is_active', true)
            ->first();

        if (!$application) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or inactive API Key'
            ], 401);
        }

        // Attach application to request
        $request->merge(['application' => $application]);
        $request->attributes->set('application', $application);

        return $next($request);
    }
}
