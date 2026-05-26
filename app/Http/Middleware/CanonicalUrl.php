<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CanonicalUrl
{
    public function handle(Request $request, Closure $next): Response
    {
        if (str_starts_with($request->getHost(), 'www.')) {
            $url = 'https://sysabc.org' . $request->getRequestUri();
            return redirect($url, 301);
        }

        return $next($request);
    }
}
