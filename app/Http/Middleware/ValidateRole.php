<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ValidateRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $role)
    {
        if($request->user()->role != $role){
            return response()->json(['message' => "You don't have access to this part of the app!"],401);
        }

        return $next($request);
    }
}
