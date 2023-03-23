<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        //
        'localhost:3000','https://single-page-iota.vercel.app'
    ];

    public function handle($request, Closure $next)
    {
        $headers = [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'POST, GET, OPTIONS, PUT, DELETE',
            'Access-Control-Allow-Headers' => 'Content-Type, X-Auth-Token, Origin,Authorization',
            'Access-Control-Allow-Credentials' => 'true'
        ];
        // Add the following lines to exclude a specific domain from CSRF protection
        if (strpos($request->headers->get('origin'), 'http://localhost:3000') !== false) {
            return $next($request);
        }

        return parent::handle($request, $next);
    }

//    public function handle($request, Closure $next)
//    {
//        $allowedOrigins = ['http://localhost:3000','https://single-page-iota.vercel.app'];
//
//        $origin = $request->headers->get('origin');
//
//        if (in_array($origin, $allowedOrigins)) {
//            $headers = [
//                'Access-Control-Allow-Origin' => $origin,
//                'Access-Control-Allow-Methods' => 'POST, GET, OPTIONS, PUT, DELETE',
//                'Access-Control-Allow-Headers' => 'Content-Type, X-Auth-Token, Origin,Authorization',
//                'Access-Control-Allow-Credentials' => 'true'
//            ];
//
//            if ($request->getMethod() == "OPTIONS") {
//                return response()->json('OK', 200, $headers);
//            }
//
//            $response = $next($request);
//            foreach ($headers as $key => $value) {
//                $response->headers($key, $value);
//            }
//
//            return $response;
//        }
//
//        return response()->json('Unauthorized', 401);
//    }
}
