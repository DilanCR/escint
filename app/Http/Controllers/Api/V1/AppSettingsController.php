<?php

namespace App\Http\Controllers\Api\V1;
 
use Exception;
use App\Http\Helpers\Api\Helpers;
use App\Models\Admin\AppSettings;
use App\Models\Admin\BasicSettings;
use App\Http\Controllers\Controller; 
use Illuminate\Support\Facades\Route;
use App\Models\Admin\AppOnboardScreens; 
use App\Http\Helpers\Api\Helpers as ApiResponse; 

class AppSettingsController extends Controller
{

    /**
     * Basic Settings Data Fetch
     *
     * @method GET
     * @return \Illuminate\Http\Response
    */

    public function basicSettings()
    {
        $image_path = get_files_public_path('app-images');
        $logo_image_path = get_files_public_path('image-assets');
        $default_logo = get_files_public_path('default');
        $all_logo = BasicSettings::select('site_logo_dark', 'site_logo', 'site_fav_dark', 'site_fav')->first();

        $data = [
            'default_logo'    => $default_logo,
            'logo_image_path' => $logo_image_path,
            'image_path'      => $image_path,
            'web_links'         => [
                'privacy-policy'    => url('/page/privacy-policy'),
                'about-us'          => Route::has('about-us') ? route('about-us') : url('/'),
                'contact-us'        => Route::has('contact.us') ? route('contact.us') : url('/'),
            ],
            'all_logo'        => $all_logo,
        ];

        $message = ['success' =>  [__('Data fetched successfully')]];
        return ApiResponse::success($message, $data);
    }
    public function appSettings(){
        $splash_screen = AppSettings::get()->map(function($splash_screen){
            return[
                'id' => $splash_screen->id,
                'splash_screen_image' => $splash_screen->splash_screen_image,
                'version' => $splash_screen->version,
                'created_at' => $splash_screen->created_at,
                'updated_at' => $splash_screen->updated_at,
            ];
        })->first();
        $app_url = AppSettings::get()->map(function($url){
            return[
                'id' => $url->id,
                'android_url' => $url->android_url,
                'iso_url' => $url->iso_url,
                'created_at' => $url->created_at,
                'updated_at' => $url->updated_at,
            ];
        })->first();
        $onboard_screen = AppOnboardScreens::orderByDesc('id')->where('status',1)->get()->map(function($data){
            return[
                'id' => $data->id,
                'title' => $data->title,
                'sub_title' => $data->sub_title,
                'image' => $data->image,
                'status' => $data->status,
                'created_at' => $data->created_at,
                'updated_at' => $data->updated_at,
            ];

        });
        $basic_settings = BasicSettings::first();
        $all_logo = [
            "site_logo_dark" =>  @$basic_settings->site_logo_dark,
            "site_logo" =>  @$basic_settings->site_logo,
            "site_fav_dark" =>  @$basic_settings->site_fav_dark,
            "site_fav" =>  @$basic_settings->site_fav,
        ];
        $data =[
            "site_name"          => @$basic_settings->site_name,
            "default_image"          => "public/backend/images/default/default.webp",
            "image_path"            =>  "public/backend/images/app",
            'onboard_screen'        => $onboard_screen,
            'splash_screen'         => (object)$splash_screen,
            'app_url'               =>   (object)$app_url,
            'all_logo'              =>   (object)$all_logo,
            'kyc_verification'      =>   @$basic_settings->kyc_verification,
            "logo_image_path"       => "public/backend/images/web-settings/image-assets"

        ];
        $message =  ['success'=>[__('Data fetched successfully')]];
        return Helpers::success($message, $data);

    }
    public function languages()
    {
        try{
            $api_languages = get_api_languages();
        }catch(Exception $e) {
            $error = ['error'=>[$e->getMessage()]];
            return Helpers::error($error);
        }
        $data =[
            'languages' => $api_languages,
        ];
        $message =  ['success'=>[__('Language Data Fetch Successfully!')]];
        return Helpers::success($message, $data);
    }
}
