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
        'http://localhost:3000','https://dukaapp.com'
    ];

    public function handle($request, Closure $next)
    {
        // Add the following lines to exclude a specific domain from CSRF protection
        if (strpos($request->headers->get('origin'), 'https://dukaapp.com') !== false) {
            return $next($request);
        }elseif (strpos($request->headers->get('origin'), 'http://localhost:3000') !== false){
            return $next($request);
        }

        return parent::handle($request, $next);
    }
}
