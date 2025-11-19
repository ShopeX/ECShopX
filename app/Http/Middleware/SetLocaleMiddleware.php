<?php

namespace App\Http\Middleware;

use Closure;

class SetLocaleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $country_code = $request->input('country_code');
        if ($country_code) {
            app()->setLocale($country_code);
        }else{
            app()->setLocale('zh-CN');
        }
        return $next($request);
    }

}
