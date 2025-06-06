<?php

namespace App\Http\Middleware\Admin;

use Closure;
use Illuminate\Http\Request;
use App\Http\Helpers\Response;
use App\Constants\AdminRoleConst;
use App\Http\Helpers\Api\Helpers;
use App\Models\Admin\SystemMaintenances;

class SystemMaintenanceApi
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
        $system_maintenance = SystemMaintenances::first();
        if( $system_maintenance->status == 1){
            $data =[
                'base_url'      => url("/"),
                'image_path'    => files_asset_path_basename("error-images"),
                "image"         => "maintenance-mode.webp",
                'status'        => $system_maintenance->status,
                'title'         => $system_maintenance->title,
                'details'       => strip_tags($system_maintenance->details),
            ];
            $message = ['error'=>[__($system_maintenance->title??"")]];
            return Response::maintenance($message,$data);

        }
        return $next($request);

    }
}
