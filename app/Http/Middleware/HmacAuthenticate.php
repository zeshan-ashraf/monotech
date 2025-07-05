<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class HmacAuthenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Get headers
        $apiKey = $request->header('X-API-Key-ID');
        $signature = $request->header('X-HMAC-Signature');
        $timestamp = $request->header('X-Timestamp') ?? "testTimestamp" ;
        $nonce = $request->header('X-Nonce') ?? "testNonce";
        
        // Check if required headers exist
        if (!$apiKey || !$signature || !$timestamp || !$nonce) {
            return response()->json(['error' => 'Missing authentication headers'], 401);
        }
        
        // 2. Find user by API key
        $user = User::where('api_key', $apiKey)->first();
        if (!$user) {
            return response()->json(['error' => 'Invalid API key'], 401);
        }
        

        /* for the time being
        // 3. Check timestamp freshness (prevent replay attacks)
        $currentTime = time();
        if (abs($currentTime - (int)$timestamp) > 300) { // 5 minutes window
            return response()->json(['error' => 'Request expired'], 401);
        }
        
        // 4. Check nonce uniqueness (prevent replay attacks)
        $nonceKey = "api_nonce:{$user->id}:{$nonce}";
        if (cache()->has($nonceKey)) {
            return response()->json(['error' => 'Duplicate request detected'], 401);
        }
        cache()->put($nonceKey, true, 3600); // Store for 1 hour
        
        */
        
        // 5. Recreate the signature
        $requestMethod = $request->method();
        $requestPath = $request->path();
        $requestBody = $request->getContent();
        
        // Include all request parameters in the signature
        $queryParams = $request->query();
        ksort($queryParams); // Sort for consistency
        $queryString = http_build_query($queryParams);
        
        /*
        // Create canonical request string
        $dataToSign = $requestMethod . "\n" . 
                      $requestPath . "\n" . 
                      $queryString . "\n" . 
                     // $timestamp . "\n" . 
                     // $nonce . "\n" . 
                      $requestBody;
        */

        $dataToSign = $requestBody;

        // Sign with the API secret
        $expectedSignature = hash_hmac('sha256', $dataToSign, $user->api_secret);
        
        // 6. Verify signature
        if (!hash_equals($expectedSignature, $signature)) {
            Log::warning('HMAC authentication failed', [
                'user_id' => $user->id,
                'request_path' => $requestPath,
                'expectedSignature' => $expectedSignature,
                'signature' => $signature,
                '$requestBody' => $requestBody,


            ]);
            return response()->json(['error' => 'Invalid signature'], 401);
        }
        
        // Add user to request for use in controller
        $request->user = $user;
        
        return $next($request);
    }
}
