<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsVendor
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check() || ! auth()->user()->vendor) {
            abort(403, 'Access denied. You must be an approved vendor on TradeCore.');
        }

        if (auth()->user()->vendor->status !== 'approved') {
            abort(403, 'Your vendor store is pending approval or suspended.');
        }

        return $next($request);
    }
}