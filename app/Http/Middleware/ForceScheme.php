<?php

namespace App\Http\Middleware;

use App\Providers\Admin\BasicSettingsProvider;
use Closure;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class ForceScheme
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        try{
            if(!$request->secure() && App::environment('production')) {
                $query = $request->getQueryString() ? '?'. $request->getQueryString() : "";
                $secure_redirect = $request->path() . $query;
                if(BasicSettingsProvider::get()->force_ssl) return redirect()->secure($secure_redirect);
            }
        }catch(Exception $e) {
            // handle error
        }

        return $next($request);
    }
}
