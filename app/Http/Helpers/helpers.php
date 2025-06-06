<?php

use App\Models\UserWallet;
use App\Models\Admin\Admin;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Constants\GlobalConst;
use App\Models\Admin\Currency;
use App\Models\Admin\Language;
use Illuminate\Support\Carbon;
use App\Imports\LanguageImport;
use App\Constants\LanguageConst;
use App\Models\UserNotification;
use App\Constants\AdminRoleConst;
use App\Constants\ExtensionConst;
use App\Models\UserAuthorization;
use Illuminate\Http\UploadedFile;
use App\Constants\EscrowConstants;
use App\Models\Admin\AdminHasRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use App\Constants\NotificationConst;
use App\Models\Admin\PaymentGateway;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Maatwebsite\Excel\Facades\Excel;
use App\Constants\SupportTicketConst;
use Illuminate\Support\Facades\Route;
use Intervention\Image\Facades\Image;
use App\Constants\PaymentGatewayConst;
use Buglinjo\LaravelWebp\Facades\Webp;

use App\Models\Admin\AdminNotification;
use App\Providers\Admin\CurrencyProvider;
use Maatwebsite\Excel\Concerns\FromArray;
use App\Models\Admin\PaymentGatewayCurrency;
use App\Providers\Admin\BasicSettingsProvider;
use Illuminate\Validation\ValidationException;
use Pusher\PushNotifications\PushNotifications;
use App\Models\Admin\LiveExchangeRateApiSetting;
use App\Notifications\User\Auth\SendAuthorizationCode;

function setRoute($route_name, $param = null)
{
    if (Route::has($route_name)) {
        if ($param) {
            return route($route_name, $param);
        } else {
            return route($route_name);
        }
    } else {
        if (env('APP_ENV') != 'production') {
            if ($param) {
                return route($route_name, $param);
            } else {
                return route($route_name);
            }
        }
        return "javascript:void(0)";
    }
}

function get_all_countries($item = [])
{
    $countries = json_decode(file_get_contents(resource_path('world/countries.json')), true);

    $countries = array_map(function ($array) {
        return [
            'id'                    => $array['id'],
            'name'                  => $array['name'],
            'mobile_code'           => $array['phone_code'],
            'currency_name'         => $array['currency_name'],
            'currency_code'         => $array['currency'],
            'currency_symbol'       => $array['currency_symbol'],
            'iso2'                  => $array['iso2'],
        ];
    }, $countries);

    return json_decode(json_encode($countries));
}

function get_country_phone_code($country) {
    $countries = json_decode(file_get_contents(resource_path('world/countries.json')), true);
    $phone_code = "";
    foreach($countries as $item) {
        if($item['name'] == $country) {
            $phone_code = $item['phone_code'];
        }
    }
    if($phone_code == "") {
        throw new Exception("Sorry, country (" . $country . ") is not available in our list");
    }
    $phone_code = str_replace("+","",$phone_code);
    return $phone_code;
}

function get_all_timezones()
{
    $countries = json_decode(file_get_contents(resource_path('world/countries.json')), true);

    $timezones = array_map(function ($array) {
        return [
            'name'  => $array['timezones'][0]['zoneName'],
        ];
    }, $countries);

    return json_decode(json_encode($timezones));
}

function get_country_states($country_id)
{

    $all_states = json_decode(file_get_contents(resource_path('world/states.json')), true);
    $states = [];

    foreach ($all_states as $item_array) {
        if (array_key_exists($item_array['country_id'], $all_states)) {
            if ($item_array['country_id'] == $country_id) {
                $states[] = [
                    'country_id'    => $item_array['country_id'],
                    'name'          => $item_array['name'],
                    'id'            => $item_array['id'],
                    'state_code'    => $item_array['state_code'],
                ];
            }
        }
    }

    return $states;
}

function get_state_cities($state_id)
{
    $all_cities = json_decode(file_get_contents(resource_path('world/cities.json')), true);

    $cities = [];

    foreach ($all_cities as $item_array) {
        if (array_key_exists($item_array['state_id'], $all_cities)) {
            if ($item_array['state_id'] == $state_id) {
                $cities[] = [
                    'name'          => $item_array['name'],
                    'id'            => $item_array['id'],
                    'state_code'    => $item_array['state_code'],
                    'state_name'    => $item_array['state_name'],
                ];
            }
        }
    }

    return $cities;
}

function get_files_from_fileholder($request, $file_input_name)
{
    $keyword = "fileholder";
    $fileholder_stored_file_path = public_path('fileholder/img');

    $files_link = [];
    if ($request->hasFile($file_input_name)) {
        $input_name = $keyword . "-" . $file_input_name;
        $file_name_array = explode(',', $request->$input_name);

        foreach ($file_name_array as $item) {
            $file_link = $fileholder_stored_file_path . "/" . $item;
            if (File::isFile($file_link)) {
                array_push($files_link, $file_link);
            } else {
                throw ValidationException::withMessages([
                    $file_input_name => "Uploaded file is not a proper file. Please upload valid file.",
                ]);
            }
        }
    } else {
        throw ValidationException::withMessages([
            $file_input_name => $file_input_name . " is required.",
        ]);
    }

    return $files_link;
}

function delete_files_from_fileholder(array $files_link)
{
    foreach($files_link as $item) {
        delete_file($item);
    }
    return true;
}

function upload_files_from_path_dynamic($files_path, $destination_path, $old_files = null)
{
    $output_files_name = [];
    foreach ($files_path as $path) {
        $file_name      = File::name($path);
        $file_extension = File::extension($path);
        $file_base_name = $file_name . "." . $file_extension;
        $file_mime_type = File::mimeType($path);
        $file_size      = File::size($path);

        $save_path = get_files_path($destination_path);

        $file_mime_type_array = explode('/', $file_mime_type);
        if (array_shift($file_mime_type_array) == "image" && $file_extension != "svg") { // If Image

            $file = Image::make($path)->orientate();

            $width = $file->width();
            $height = $file->height();

            $resulation_break_point = [2048, 2340, 2730, 3276, 4096, 5460, 8192];
            $reduce_percentage = [12.5, 25, 37.5, 50, 62.5, 75];

            // Dynamically Image Resizing & Move to Targeted folder
            if ($width > 0 && $width < 2048) {
                $new_width = $width;
                try {
                    $file->resize($new_width, null, function ($constraint) {
                        $constraint->aspectRatio();
                    })->save($path, 70);
                } catch (\Exception $e) {
                    return back()->with(['error' => ['Image Upload Failed!']]);
                }
            }
            if ($width > 5460 && $width <= 6140) {
                $new_width = 2048;
                try {
                    $file->resize($new_width, null, function ($constraint) {
                        $constraint->aspectRatio();
                    })->save($path, 70);
                } catch (\Exception $e) {
                    return back()->with(['error' => ['Image Upload Failed!']]);
                }
            } else {
                for ($i = 0; $i < count($resulation_break_point); $i++) {
                    if ($i != count($resulation_break_point) - 1) {
                        if ($width >= $resulation_break_point[$i] && $width <= $resulation_break_point[$i + 1]) {
                            $new_width = ceil($width - (($width * $reduce_percentage[$i]) / 100));
                            try {
                                $file->resize($new_width, null, function ($constraint) {
                                    $constraint->aspectRatio();
                                })->save($path, 70);
                            } catch (\Exception $e) {
                                return back()->with(['error' => ['Image Upload Failed!']]);
                            }
                        }
                    }
                }
                if ($width > 8192) {
                    $new_width = 2048;
                    try {
                        $file->resize($new_width, null, function ($constraint) {
                            $constraint->aspectRatio();
                        })->save($path, 70);
                    } catch (\Exception $e) {
                        return back()->with(['error' => ['Image Upload Failed!']]);
                    }
                }
            }

            $file_instance = new UploadedFile(
                $path,
                $file_base_name,
                $file_mime_type,
                $file_size,
            );

            $store_file_name = $file_name . ".webp";
            try {
    
                if ($file_extension != "webp") {
                    $webp = Webp::make($file_instance)->save($save_path . "/" . $store_file_name);
                    array_push($output_files_name, $store_file_name);
                } else {
                    File::move($file_instance, $save_path . "/" . $file_base_name);
                    array_push($output_files_name, $file_base_name);
                }
            } catch (Exception $e) {
                return back()->with(['error' => ['Something went wrong! Failed to upload file.']]);
            }
        } else { // IF Other Files
            $file_instance = new UploadedFile(
                $path,
                $file_base_name,
                $file_mime_type,
                $file_size,
            );

            try {
                File::move($file_instance, $save_path . "/" . $file_base_name);
                array_push($output_files_name, $file_base_name);
            } catch (Exception $e) {
                return back()->with(['error' => ['Something went wrong! Failed to upload file.']]);
            }
        }

        // Delete Old Files if exists
        try {
            if ($old_files) {
                if (is_array($old_files)) {
                    // Delete Multiple File
                    foreach ($old_files as $item) {
                        $file_link = $save_path . "/" . $item;
                        delete_file($item);
                    }
                } else if (is_string($old_files)) {
                    // Delete Single File
                    $file_link = $save_path . "/" . $old_files;
                    delete_file($file_link);
                }
            }
        } catch (Exception $e) {
            return back()->with(['error' => ['Something went wrong! Failed to delete old file.']]);
        }
    }

    if (count($output_files_name) == 1) {
        return $output_files_name[0];
    }
    // delete_files_from_fileholder($output_files_name);
    return $output_files_name;
}

function get_files_path($slug)
{
    $data = files_path($slug);
    $path = $data->path;
    create_asset_dir($path);

    return public_path($path);
}

function create_asset_dir($path)
{
    $path = "public/" . $path;
    if (file_exists($path)) return true;
    return mkdir($path, 0755, true);
}

function get_image($image_name, $path_type = null, $image_type = null, $size = null)
{

    if ($image_type == 'profile') {
        $image =  asset('public/' . files_path('profile-default')->path);
    } else {
        $image =  asset('public/' . files_path('default')->path);
    }
    if ($image_name != null) {
        if ($path_type != null) {
            $image_path = files_path($path_type)->path;
            $image_link = $image_path . "/" . $image_name;
            if (file_exists(public_path($image_link))) {
                $image = asset('public/' . $image_link);
            }
        }
    }

    return $image;
}

function get_storage_image($image_name, $path_type = null, $image_type = null, $size = null)
{

    if ($image_type == 'profile') {
        $image =  asset(files_path('profile-default')->path);
    } else {
        $image =  asset(files_path('default')->path);
    }
    if ($image_name != null) {
        if ($path_type != null) {
            $image_path = files_path($path_type)->path;
            $image_link = $image_path . "/" . $image_name;

            if (file_exists(storage_path($image_link))) {
                // if(file_exists(public_path($image_link))) {
                $image = asset($image_link);
            }
        }
    }

    return $image;
}

function files_path($slug)
{
    $data = [
        'admin-profile'         => [
            'path'              => 'backend/images/admin/profile',
            'width'             => 800,
            'height'            => 800,
        ],
        'default'               => [
            'path'              => 'backend/images/default/default.webp',
            'width'             => 800,
            'height'            => 800,
        ],
        'profile-default'       => [
            'path'              => 'backend/images/default/profile-default.webp',
            'width'             => 800,
            'height'            => 800,
        ],
        'currency-flag'         => [
            'path'              => 'backend/images/currency-flag',
            'width'             => 400,
            'height'            => 400,
        ],
        'image-assets'          => [
            'path'              => 'backend/images/web-settings/image-assets',
        ],
        'seo'                   => [
            'path'              => 'backend/images/seo',
        ],
        'app-images'            => [
            'path'              => 'backend/images/app',
            'width'             => 414,
            'height'            => 896,
        ],
        'payment-gateways'      => [
            'path'              => 'backend/images/payment-gateways',
        ],
        'extensions'      => [
            'path'              => 'backend/images/extensions',
        ],
        'user-profile'      => [
            'path'              => 'frontend/user',
        ],
        'language-file'     => [
            'path'          => 'backend/files/language',
        ],
        'site-section'         => [
            'path'          => 'frontend/images/site-section',
        ],
        'support-attachment'    => [
            'path'          => 'frontend/images/support-ticket/attachment',
        ],
        'escrow-temp-file'    => [
            'path'          => 'frontend/escrow/temp',
        ],
        'escrow-conversation'    => [
            'path'          => 'frontend/escrow/conversation',
        ],
        'kyc-files'         => [
            'path'          => 'backend/files/kyc-files'
        ],
        'junk-files'        => [
            'path'      => 'backend/files/junk-files',
        ], 
        'blog'        => [
            'path'      => 'frontend/images/blog',
        ],
        'support-conversation'        => [
            'path'      => 'frontend/images/support',
        ],
        'error-images'   => [
            'path'      => 'error-images',
        ],

    ];

    return (object) $data[$slug];
}

function files_asset_path($slug)
{
    $files_path = files_path($slug)->path;
    return asset('public/' . $files_path);
}
function get_amount($amount, $currency = null, $precision = null)
{
    if (!is_numeric($amount)) return "Not Number";
    $digitShow = 2;
    if ($currency != null) { 
        $digitShow = 2;
        $selectedCurrency = Currency::where('code', $currency)->first(); 
        if ($selectedCurrency != null) {
            $digitShow = $selectedCurrency->type == "CRYPTO" ? 8 : 2 ;
        }  
    }
    $amount = ($precision) ? number_format($amount, $precision, ".", ",") : number_format($amount, $digitShow, ".", ",");
    if (!$currency) return $amount;
    $amount = $amount . " " . $currency;
    return $amount;
}

function get_logo($basic_settings, $type = null)
{
    $logo = "";
    if ($type == 'white') {
        if (!$basic_settings->site_logo) {
            $logo = files_asset_path('default');
        } else {
            $logo = files_asset_path('image-assets') . "/" . $basic_settings->site_logo;
        }
    }

    if ($type == 'dark') {
        if (!$basic_settings->site_logo_dark) {
            $logo = files_asset_path('default');
        } else {
            $logo = files_asset_path('image-assets') . "/" . $basic_settings->site_logo_dark;
        }
    }

    if ($type == null) {
        if (!$basic_settings->site_logo) {
            if (!$basic_settings->site_logo_dark) {
                $logo = files_asset_path('default');
            } else {
                $logo = files_asset_path('image-assets') . "/" . $basic_settings->site_logo_dark;
            }
        } else {
            $logo = files_asset_path('image-assets') . "/" . $basic_settings->site_logo;
        }
    }

    return $logo;
}

function get_logo_public_path($basic_settings, $type = null)
{
    $logo = "";
    if ($type == 'white') {
        if (!$basic_settings->site_logo) {
            $logo = get_files_path('default');
        } else {
            $logo = get_files_path('image-assets') . "/" . $basic_settings->site_logo;
        }
    }

    if ($type == 'dark') {
        if (!$basic_settings->site_logo_dark) {
            $logo = get_files_path('default');
        } else {
            $logo = get_files_path('image-assets') . "/" . $basic_settings->site_logo_dark;
        }
    }

    if ($type == null) {
        if (!$basic_settings->site_logo) {
            if (!$basic_settings->site_logo_dark) {
                $logo = get_files_path('default');
            } else {
                $logo = get_files_path('image-assets') . "/" . $basic_settings->site_logo_dark;
            }
        } else {
            $logo = get_files_path('image-assets') . "/" . $basic_settings->site_logo;
        }
    }

    return $logo;
}

function get_fav($basic_settings, $type = null)
{
    $fav = "";
    if ($type == 'white') {
        if (!$basic_settings->site_fav) {
            $fav = files_asset_path('default');
        } else {
            $fav = files_asset_path('image-assets') . "/" . $basic_settings->site_fav;
        }
    }

    if ($type == 'dark') {
        if (!$basic_settings->site_fav_dark) {
            $fav = files_asset_path('default');
        } else {
            $fav = files_asset_path('image-assets') . "/" . $basic_settings->site_fav_dark;
        }
    }

    if ($type == null) {
        if (!$basic_settings->site_fav) {
            if (!$basic_settings->site_fav_dark) {
                $fav = files_asset_path('default');
            } else {
                $fav = files_asset_path('image-assets') . "/" . $basic_settings->site_fav_dark;
            }
        } else {
            $fav = files_asset_path('image-assets') . "/" . $basic_settings->site_fav;
        }
    }

    return $fav;
}
function get_file_info(string $path,$destination_path) {
    $file_info = [];
    if(File::exists($path)) {
        $file_name      = File::name($path);
        $file_extension = File::extension($path);
        $file_base_name = $file_name . "." . $file_extension;
        $file_mime_type = File::mimeType($path);
        $file_size      = File::size($path);
        $file = new UploadedFile(
            $path,
            $file_base_name,
            $file_mime_type,
            $file_size,
        );
        $save_path = get_files_path($destination_path);
        $file_extension = $file->getClientOriginalExtension();
        $file_type = File::mimeType($file);
        $file_size = File::size($file);
        $file_original_name = $file->getClientOriginalName();
        $file_base_name = explode(".",$file_original_name);
        array_pop($file_base_name);
        $file_base_name = implode("-",$file_base_name);
        $file_name = Str::uuid() . "." . $file_extension;
        $file_public_link   = $save_path . "/" . $file_name;
        $file_asset_link    = files_asset_path($destination_path) . "/" . $file_name;
        $file_info = [
            'name'                  => $file_name,
            'type'                  => $file_type,
            'extension'             => $file_extension,
            'size'                  => $file_size,
            'file_link'             => $file_asset_link,
            'dev_path'              => $file_public_link,
            'original_name'         => $file_original_name,
            'original_base_name'    => $file_base_name,
            'path_source'           => files_asset_path($destination_path),
        ];
    }
    return $file_info;
}
function upload_files_from_path_static($files_path, $destination_path, $old_files = null, $crop = true, $compress = false, $crop_position = "center")
{
    $output_files_name = [];
    foreach ($files_path as $path) {
        $file_name      = File::name($path);
        $file_extension = File::extension($path);
        $file_base_name = $file_name . "." . $file_extension;
        $file_mime_type = File::mimeType($path);
        $file_size      = File::size($path);

        $save_path = get_files_path($destination_path);

        $file_mime_type_array = explode('/', $file_mime_type);
        if (array_shift($file_mime_type_array) == "image" && $file_extension != "svg") { // If Image

            $file = Image::make($path)->orientate();

            $width = $file->width();
            $height = $file->height();

            $resulation_break_point = [2048, 2340, 2730, 3276, 4096, 5460, 8192];
            $reduce_percentage = [12.5, 25, 37.5, 50, 62.5, 75];

            // Dynamically Image Resizing
            if ($compress === true) {
                if ($width > 0 && $width < 2048) {
                    $new_width = $width;
                    try {
                        $file->resize($new_width, null, function ($constraint) {
                            $constraint->aspectRatio();
                        });
                    } catch (\Exception $e) {
                        return back()->with(['error' => ['Image Upload Failed!']]);
                    }
                }
                if ($width > 5460 && $width <= 6140) {
                    $new_width = 2048;
                    try {
                        $file->resize($new_width, null, function ($constraint) {
                            $constraint->aspectRatio();
                        });
                    } catch (\Exception $e) {
                        return back()->with(['error' => ['Image Upload Failed!']]);
                    }
                } else {
                    for ($i = 0; $i < count($resulation_break_point); $i++) {
                        if ($i != count($resulation_break_point) - 1) {
                            if ($width >= $resulation_break_point[$i] && $width <= $resulation_break_point[$i + 1]) {
                                $new_width = ceil($width - (($width * $reduce_percentage[$i]) / 100));
                                try {
                                    $file->resize($new_width, null, function ($constraint) {
                                        $constraint->aspectRatio();
                                    });
                                } catch (\Exception $e) {
                                    return back()->with(['error' => ['Image Upload Failed!']]);
                                }
                            }
                        }
                    }
                    if ($width > 8192) {
                        $new_width = 2048;
                        try {
                            $file->resize($new_width, null, function ($constraint) {
                                $constraint->aspectRatio();
                            });
                        } catch (\Exception $e) {
                            return back()->with(['error' => ['Image Upload Failed!']]);
                        }
                    }
                }
            }

            // Crop Image
            if ($crop === true) {
                $image_settings = files_path('app-images');
                $crop_width     = $image_settings->width ?? false;
                $crop_height    = $image_settings->height ?? false;

                if ($crop_width != false && $crop_height != false) {
                    $file->fit($crop_width, $crop_height, null, $crop_position);
                }

                if ($crop_width != false && $crop_height == false) {
                    $file->resize($crop_width, null, function ($constraint) {
                        $constraint->aspectRatio();
                    });
                }
            }

            // Save File
            try {
                $file->save($path, 70);
            } catch (Exception $e) {
                return back()->with(['error' => ['Something went wrong! Failed to save file.']]);
            }

            $file_instance = new UploadedFile(
                $path,
                $file_base_name,
                $file_mime_type,
                $file_size,
            );

            $store_file_name = $file_name . ".webp";
            try {
                if ($file_extension != "webp") {
                    $webp = Webp::make($file_instance)->save($save_path . "/" . $store_file_name);
                    array_push($output_files_name, $store_file_name);
                } else {
                    File::move($file_instance, $save_path . "/" . $file_base_name);
                    array_push($output_files_name, $file_base_name);
                }
            } catch (Exception $e) {
                return back()->with(['error' => ['Something went wrong! Failed to upload file.']]);
            }
        } else { // IF Other Files
            $file_instance = new UploadedFile(
                $path,
                $file_base_name,
                $file_mime_type,
                $file_size,
            );

            try {
                File::move($file_instance, $save_path . "/" . $file_base_name);
                array_push($output_files_name, $file_base_name);
            } catch (Exception $e) {
                return back()->with(['error' => ['Something went wrong! Failed to upload file.']]);
            }
        }

        // Delete Old Files if exists
        try {
            if ($old_files) {
                if (is_array($old_files)) {
                    // Delete Multiple File
                    foreach ($old_files as $item) {
                        $file_link = $save_path . "/" . $item;
                        delete_file($item);
                    }
                } else if (is_string($old_files)) {
                    // Delete Single File
                    $file_link = $save_path . "/" . $old_files;
                    delete_file($file_link);
                }
            }
        } catch (Exception $e) {
            return back()->with(['error' => ['Something went wrong! Failed to delete old file.']]);
        }
    }

    if (count($output_files_name) == 1) {
        return $output_files_name[0];
    }
    // delete_files_from_fileholder($output_files_name);
    return $output_files_name;
}

function delete_file($file_link)
{
    if (File::exists($file_link)) {
        try {
            File::delete($file_link);
        } catch (Exception $e) {
            return false;
        }
    }
    return true;
}

function get_default_currency_code($default_currency = null)
{
    if($default_currency == null) $default_currency = CurrencyProvider::default();
    if ($default_currency != false) {
        return $default_currency->code;
    }
    return "";
}
function get_default_currency_symbol($default_currency = null)
{
    if($default_currency == null) $default_currency = CurrencyProvider::default();
    if ($default_currency != false) {
        return $default_currency->symbol;
    }
    return "";
}

function replace_array_key($array, $remove_keyword, $replace_keyword = "")
{
    $filter = [];
    foreach ($array as $key => $value) {
        $update_key = preg_replace('/' . $remove_keyword . '/i', $replace_keyword, $key);
        $filter[$update_key] = $value;
    }
    return $filter;
}


function get_paginate($data)
{
    try {
        return $data->onEachSide(2)->links();
    } catch (Exception $e) {
        return "";
    }
}
function showDate($date, $format = 'd-m-Y')
{
    $lang = session()->get('lang');
    Carbon::setlocale($lang);
    return Carbon::parse($date)->translatedFormat($format);
}

function set_payment_gateway_code($last_record_of_code)
{
    return intval($last_record_of_code + 5);
}

function make_input_name($string)
{
    $string         = preg_replace('/[^A-Za-z0-9]/', ' ', $string);
    $string         = preg_replace("/ /i", "_", $string);
    $string         = Str::lower($string);
    return $string;
}

/**
 * Function for Making Input field array with all information that comes from Frontend Form
 * @param array $validated
 * @return array $input_fields
 */
function decorate_input_fields($validated)
{

    $input_fields = [];

    $field_necessity_list = [
        '1'             => true,
        '0'             => false,
    ];
    $file_array_key = 0;
    $select_array_key = 0;
    $global_array_key = 0;
    foreach ($validated['input_type'] ?? [] as $key => $item) {
        $field_necessity = $validated['field_necessity'][$key] ?? "";

        $validation_rules = ['min' => 0, 'mimes' => []];

        if ($item == "file") {
            $extensions = $validated['file_extensions'][$file_array_key] ?? "";
            $extensions = explode(",", $extensions);

            $validation_rules = [
                'max'       => $validated['file_max_size'][$file_array_key] ?? 0,
                'mimes'     => $extensions,
                'min'       => 0,
                'options'  => [],
            ];

            $file_array_key++;
        } else if ($item == "select") {
            $options = $validated['select_options'][$select_array_key] ?? "";
            $options = explode(",", $options);

            $validation_rules = [
                'max'       => 0,
                'min'       => 0,
                'mimes'     => [],
                'options'   => $options,
            ];

            $select_array_key++;
        } else {
            $validation_rules = [
                'max'      => $validated['max_char'][$global_array_key] ?? 0,
                'mimes'    => [],
                'min'      => $validated['min_char'][$global_array_key] ?? 0,
                'options'  => [],
            ];
            $global_array_key++;
        }

        $validation_rules['required'] = $field_necessity_list[$field_necessity] ?? false;

        $input_fields[]     = [
            'type'          => $item,
            'label'         => $validated['label'][$key] ?? "",
            'name'          => make_input_name($validated['label'][$key] ?? ""),
            'required'      => $field_necessity_list[$field_necessity] ?? false,
            'validation'    => $validation_rules,
        ];
    }

    return $input_fields;
}


/**
 * Function for replace ENV Value based on key
 * @param array $replace_array
 */
function modifyEnv($replace_array = [])
{
    $array_going_to_modify  = $replace_array;
    if (count($array_going_to_modify) == 0) {
        return false;
    }
    $env_file_content_string = File::get(App::environmentFilePath());
    $lines = array_values(array_filter(explode("\n", $env_file_content_string)));
    $env_content = [];
    foreach($lines as $line) {
        $line = trim($line);
        if ($line) {
            list($key, $value) = explode('=', $line, 2);
            // Remove any quotes from the value
            $value = trim($value, '"');
            // Store the key-value pair in the array
            $env_content[$key] = $value;
        }
    }
    $update_array = ["APP_ENV" => App::environment()];
    foreach ($env_content as $key => $value) {
        foreach ($array_going_to_modify as $modify_key => $modify_value) {
            if(!array_key_exists($modify_key,$env_content) && !array_key_exists($modify_key,$update_array)) {
                $update_array[$modify_key] = setEnvValue($modify_key,$modify_value);
                break;
            }
            if ($key == $modify_key) {
                $update_array[$key] = setEnvValue($key,$modify_value);
                break;
            } else {
                $update_array[$key] = setEnvValue($key,$value);
            }
        }
    }
    $string_content = "";
    foreach ($update_array as $key => $item) {
        $line = $key . "=" . $item;
        $string_content .= $line . "\r\n\n";
    }
    $env_file = App::environmentFilePath();
    File::put($env_file, $string_content);
}
function setEnvValue($key,$value) {
    if($key == "APP_KEY") {
        return $value;
    }
    return '"' .$value . '"';
}

// Role Permission START 

function permission_skip()
{
    return [
        'admin.logout',
        'admin.languages.switch',
        'admin.currency.search',
        'admin.notifications.clear',
        'admin.users.search',
        'admin.admins.search',
        'admin.users.sms.unverified'
    ];
}

function get_role_permission_routes()
{
    $routes_info = Route::getRoutes()->get();
    $routes_name = [];
    foreach ($routes_info as $key => $item) {
        if (isset($item->action['as'])) {
            if (Str::is("admin.*", $item->action['as'])) {
                if (Str::is("admin.login*", $item->action['as'])) {
                    continue;
                } else if (Str::is("admin.profile*", $item->action['as'])) {
                    continue;
                } else if (Str::is("admin.password*", $item->action['as'])) {
                    continue;
                } else if (in_array($item->action['as'], permission_skip())) {
                    continue;
                }
                $routes_name[] = $item->action['as'];
            }
        }
    }

    $readable_route_text = [];
    foreach ($routes_name as $item) {
        $make_title = str_replace('admin.', "", $item);
        $make_title = str_replace('.', " ", $make_title);
        $make_title = ucwords($make_title);
        $readable_route_text[] = [
            'route'     => $item,
            'text'      => $make_title,
        ];
    }

    return $readable_route_text;
}
function userGuard() {
    if(auth()->guard('web')->check()){
        $user = auth()->guard('web')->user();
        $userType = 'USER';
        $guard = "1";
    } else if(auth()->guard('api')->check()){
        $user = auth()->guard('api')->user();
        $userType = 'USER';
        $guard = "2";
    } else if(auth()->guard('merchant')->check()){
        $user = auth()->guard('merchant')->user();
        $userType = 'MERCHANT';
        $guard = "4";
    }else if(auth()->guard('merchant_api')->check()){
        $user = auth()->guard('merchant_api')->user();
        $userType = 'MERCHANT';
        $guard = "4";
    }

    return [
        'user'=>$user,
        'type'=> $userType,
        'guard'=>$guard
    ];
}

function get_route_info($route_name)
{
    $route_info = Route::getRoutes()->getByName($route_name);
    return $route_info;
}

function system_super_admin()
{
    if (AdminHasRole::whereHas('role', function ($query) {
        $query->where("name", AdminRoleConst::SUPER_ADMIN);
    })->exists()) return true;
    return false;
}

function admin_role_const()
{
    return AdminRoleConst::class;
}

function auth_admin_roles()
{
    return auth()->guard("admin")->user()->getRolesCollection();
}

function auth_admin_permissions()
{
    $auth_admin_roles = Auth::user()->roles;
    $permissions = [];
    foreach ($auth_admin_roles as $item) {
        if ($item->permission != null && $item->permission->hasPermissions != null) {
            foreach ($item->permission->hasPermissions as $innerItem) {
                array_push($permissions, $innerItem->route);
            }
        }
    }
    return array_unique($permissions);
}

function auth_is_super_admin()
{
    $auth_admin_roles = auth_admin_roles();
    if (in_array(AdminRoleConst::SUPER_ADMIN, $auth_admin_roles)) return true;
    return false;
}

function permission_protected()
{
    $permissions = get_role_permission_routes();
    $permissions = Arr::pluck($permissions, ["route"]);
    return $permissions;
}

function auth_admin_incomming_permission()
{
    $incomming_access = Route::currentRouteName();
    $auth_admin_permissions = auth_admin_permissions();
  
    if (auth_is_super_admin() == true) return true;
    if (!in_array($incomming_access, permission_protected())) return true;
    if (in_array($incomming_access, $auth_admin_permissions)) return true;
    return false;
}

function admin_permission_by_name($name)
{
    if (auth_is_super_admin()) return true;
    if (in_array($name, auth_admin_permissions())) return true;
    return false;
}

function auth_has_no_role()
{
    if (count(auth_admin_roles()) == 0) {
        return true;
    }
    return false;
}

function auth_has_role()
{
    if (count(auth_admin_roles()) > 0) {
        return true;
    }
    return false;
}


function admin_permission_by_name_array($names)
{
    $auth_admin_permissions = auth_admin_permissions();
    if (auth_is_super_admin()) return true;
    $match = array_intersect($auth_admin_permissions, $names);
    if (count($match) > 0) {
        return true;
    }
    return false;
}
function get_auth_guard() {
    if(auth()->guard("web")->check()) {
        return "web";
    }else if(auth()->guard("admin")->check()) {
        return "admin";
    }else if(auth()->guard("api")->check()) {
        return "api";
    }
    return "";
}

// Role Permission END
function remove_spaces($string)
{
    return str_replace(' ', "", $string);
}


function get_admin_notifications()
{
    $notifications = AdminNotification::auth()->where('clear_at',null)->latest()->get();
    return $notifications;
}

function language_const()
{
    return LanguageConst::class;
}

function addMoneyChargeCalc($amount, $charges)
{
    $rate = $charges->rate ?? 0;
    if ($charges != null) {
        $fixed_charges = $charges->fixed_charge;
        $percent_charges = $charges->percent_charge;
    } else {
        $fixed_charges = 0;
        $percent_charges = 0;
    }
    $fixed_charge_calc = ($rate * $fixed_charges);
    $percent_charge_calc = ($amount / 100) * $percent_charges;
    $total_charge = $fixed_charge_calc + $percent_charge_calc;
    $total_amount = $amount + $total_charge;
    $data = [
        'requested_amount'  => $amount,
        'total_amount'      => $total_amount,
        'total_charges'     => $total_charge,
        'fixed_charge'      => $fixed_charge_calc,
        'percent_charges'   => $percent_charge_calc,
    ];
    return (object) $data;
}

function create_file($path, $mode = "w")
{
    return fopen($path, $mode);
}


function get_first_file_from_dir($dir) {
    $files = scandir($dir);
    if(is_array($files) && count($files) > 2) return $files[2];
    return false;
}

function language_file_exists() {
    $file_path = get_files_path('language-file');
    $files = scandir($file_path);
    if(is_array($files) && count($files) > 2) return true;
    return false;
}

function get_default_language_code() {
    return App::currentLocale();
}
function get_api_languages(){

    $lang = Language::get()->map(function($data,$index){

        if(file_exists(base_path('lang/') . $data->code . '.json') == false) return false;

        $json = json_decode(file_get_contents(base_path('lang/') . $data->code . '.json'),true);
        $lan_key_values = [];
        if($json != null) {
            foreach($json as $lan_key=>$item) {
                $lan_key_original = $lan_key;
                $lan_key = preg_replace('/[^A-Za-z]/i',' ',strtolower($lan_key));
                if(strlen($lan_key) > 30) {
                    // $lan_key = substr($lan_key,0,20);
                    $word_array = explode(" ",$lan_key);
                    $count_char = 0;
                    foreach($word_array as $word_key => $word) {
                        $count_char += strlen($word);
                        if($count_char > 30) {
                            $get_limit_val = array_splice($word_array,0,$word_key);
                            $lan_key = implode(" ",$get_limit_val);
                            $count_char = 0;
                            break;
                        }
                    }
                }

                // Make Key Readable
                $var_array = explode(" ",$lan_key);
                foreach($var_array as $key=>$var) {
                    if($key > 0) {
                        $var_array[$key] = ucwords($var);
                    }
                }

                $lan_key = implode("",$var_array);

                if(array_key_exists($lan_key,$lan_key_values) && $lan_key_values[$lan_key] != $item) {
                    throw new Exception("Duplicate Key Found! Please check/update this key [$lan_key_original]");
                }

                ($lan_key != "") ? $lan_key_values[$lan_key] = $item : "";
            }
        }

        return [
            'name'                  => $data->name,
            'code'                  => $data->code,
            'status'                => $data->status,
            'dir'                   => $data->dir,
            'translate_key_values'  =>$lan_key_values,
        ];
    })->reject(function($value) {
        return $value == false;
    });

    return $lang;
}
function get_admin($username) {
    $admin = Admin::where("username",$username)->first();
    return $admin;
}

function setPageTitle(string $title) {
    $basic_settings = BasicSettingsProvider::get();
    return $basic_settings->site_name . " - " . $title;
}

function make_username($first_name, $last_name, $table = "users") {
    // Normalize the names to NFC form
    $first_name = normalizer_normalize($first_name, Normalizer::FORM_C);
    $last_name = normalizer_normalize($last_name, Normalizer::FORM_C);
    // Remove unwanted characters (only allowing letters and digits)
    $first_name = preg_replace('/[^\p{L}\p{N}]/u', '', $first_name);
    $last_name = preg_replace('/[^\p{L}\p{N}]/u', '', $last_name);
    $generate_name_with_count = "";
    do {
        // Generate username
        $firstName = $first_name;
        $lastName = $last_name;
        if ($generate_name_with_count == "") {
            if (mb_strlen($firstName) >= 6) {
                $generate_name = filter_string_lower($firstName);
            } else {
                $modfy_last_name = explode(' ', $lastName);
                $lastName = filter_string_lower($modfy_last_name[0]);
                $firstName = filter_string_lower($firstName);
                $generate_name = $firstName . $lastName;
                if (mb_strlen($generate_name) < 6) {
                    $firstName = filter_string_lower($firstName);
                    $lastName = filter_string_lower($lastName);
                    $generate_name = $firstName . $lastName;
                    if (mb_strlen($generate_name) < 6) {
                        $getCurrentLen = mb_strlen($generate_name);
                        $dueChar = 6 - $getCurrentLen;
                        $generate_due_char = strtolower(generate_random_string($dueChar));
                        $generate_name = $generate_name . $generate_due_char;
                    }
                }
            }
        } else {
            $generate_name = $generate_name_with_count;
        }
        // Find if the user already exists
        $chekUser = DB::table($table)->where('username', $generate_name)->first();
        if ($chekUser == null) {
            $loop = false;
        } else {
            $generate_name_with_count = $generate_name;
            $split_string = array_reverse(preg_split('//u', $generate_name_with_count, -1, PREG_SPLIT_NO_EMPTY));
            $username_string_part = "";
            $last_numeric_values = "";
            $numeric_close = false;
            foreach ($split_string as $character) {
                if ($numeric_close == false) {
                    if (is_numeric($character)) {
                        $last_numeric_values .= $character;
                    } else {
                        $numeric_close = true;
                    }
                }
                if ($numeric_close == true) {
                    $username_string_part .= $character;
                }
            }
            if ($last_numeric_values == "") { // If has no number in username string;
                $last_numeric_values = 1;
            }
            $username_string_part = implode('', array_reverse(preg_split('//u', $username_string_part, -1, PREG_SPLIT_NO_EMPTY))); // username back to original order;
            $last_numeric_values = strrev($last_numeric_values); // last number back to original order;
            $generate_name_with_count = $username_string_part . ($last_numeric_values + 1);
            $loop = true;
        }
    } while ($loop);
    return $generate_name;
}


function filter_string_lower($string) {
    // Convert to lowercase, considering multibyte characters
    return mb_strtolower($string, 'UTF-8');
}
function generate_random_string($length) {
    // Generate a random string with the specified length
    $characters = 'abcdefghijklmnopqrstuvwxyz';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= mb_substr($characters, rand(0, mb_strlen($characters) - 1), 1);
    }
    return $randomString;
}

function get_gateway_image($gateway_id){
    $gateway = PaymentGateway::where('id',$gateway_id)->first();
    $image = get_image($gateway->image,"payment-gateways");
    return $image;

}
function files_asset_path_basename($slug) {
    return "public/" . files_path($slug)->path;
}
function generate_random_string_number($length = 12)
{
    $characters = 'ABCDEFGHJKMNOPQRSTUVWXYZ123456789abcdefghijklmnopqrstuvwxyz';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}
function generateTrxString($table,$column,$prefix = '',$length = 8) {
    do{
       $generate_number = generate_random_number($length);
       $generate_number = $prefix.''.$generate_number;
       $unique = DB::table($table)->where($column,$generate_number)->exists();
       $loop = false;
       if($unique) {
        $loop = true;
       }
       $unique_number = $generate_number;
    }while($loop);

    return $unique_number;
}
function generate_unique_string($table,$column,$length = 10) {
    do{
       $generate_rand_string = generate_random_string_number($length);
       $unique = DB::table($table)->where($column,$generate_rand_string)->exists();
       $loop = false;
       if($unique) {
        $loop = true;
       }
       $unique_string = $generate_rand_string;
    }while($loop);

    return $unique_string;
}
function generate_random_number($length = 12)
{
    $numbers = '0123456789';
    $numbersLength = strlen($numbers);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $numbers[rand(0, $numbersLength - 1)];
    }
    return $randomString;
}
function upload_file($file,$destination_path,$old_file = null) {
    if(File::isFile($file)) {
        $save_path = get_files_path($destination_path);
        $file_extension = $file->getClientOriginalExtension();
        $file_type = File::mimeType($file);
        $file_size = File::size($file);
        $file_original_name = $file->getClientOriginalName();

        $file_base_name = explode(".",$file_original_name);
        array_pop($file_base_name);
        $file_base_name = implode("-",$file_base_name);

        $file_name = Str::uuid() . "." . $file_extension;

        $file_public_link   = $save_path . "/" . $file_name;
        $file_asset_link    = files_asset_path($destination_path) . "/" . $file_name;

        $file_info = [
            'name'                  => $file_name,
            'type'                  => $file_type,
            'extension'             => $file_extension,
            'size'                  => $file_size,
            'file_link'             => $file_asset_link,
            'dev_path'              => $file_public_link,
            'original_name'         => $file_original_name,
            'original_base_name'    => $file_base_name,
        ];

        try{

            if($old_file) {
                $old_file_link = $save_path . "/" . $old_file;
                delete_file($old_file_link);
            }

            File::move($file,$file_public_link);
        }catch(Exception $e) {
            return false;
        }
        
        return $file_info;
    }

    return false;
}

function delete_files($files_link)
{   
    if(is_array($files_link)) {
        foreach($files_link as $item) {
            if (File::exists($item)) {
                try {
                    File::delete($item);
                } catch (Exception $e) {
                    // return false;
                }
            }
        }
    }
}

function support_ticket_const() {
    return SupportTicketConst::class;
}

function get_percentage_from_two_number($total,$available,$result_type = "int") {
    if(is_numeric($total) && is_numeric($available)) {
        $one_percent = $total / 100;
        $result = 0;
        if($one_percent > 0) $result = $available / $one_percent;
        if($result_type == "int") return (int) ceil($result);
        return number_format($result, 2, ".", ",");
    }
}

function remove_speacial_char($string) {
    return preg_replace("/[^A-Za-z0-9]/"," ",$string);
}

function check_email($string) {
    if(filter_var($string,FILTER_VALIDATE_EMAIL)) {
        return true;
    }
    return false;
}

function generate_random_code($length = 6) {
    $numbers = '123456789';
    $numbersLength = strlen($numbers);
    $randNumber = '';
    for ($i = 0; $i < $length; $i++) {
        $randNumber .= $numbers[rand(0, $numbersLength - 1)];
    }
    return $randNumber;
}

function mailVerificationTemplate($user) {
    $basic_settings = BasicSettingsProvider::get();
    $data = [
        'user_id'       => $user->id,
        'code'          => generate_random_code(),
        'token'         => generate_unique_string("user_authorizations","token",200),
        'created_at'    => now(),
    ];

    DB::beginTransaction();
    try{

        if( $basic_settings->email_notification == true){
            $user->notify(new SendAuthorizationCode((object) $data));
        }
        UserAuthorization::where("user_id",$user->id)->delete();
        DB::table("user_authorizations")->insert($data);
        DB::commit();
    }catch(Exception $e) {
        DB::rollBack();
        return back()->with(['error' => [__("Something went wrong! Please try again.")]]);
    }
    return redirect()->route('user.authorize.mail',$data['token'])->with(['warning' => [__("Please verify your mail address. Check your mail inbox to get verification code")]]);
}

function extension_const() {
    return ExtensionConst::class;
}

function global_const() {
    return GlobalConst::class;
}

function imageExtenstions() {
    return ['png','jpg','jpeg','svg','webp','gif'];
}

function its_image(string $string) {
    if(!is_string($string)) return false;
    $extension = explode(".",$string);
    $extension = strtolower(end($extension));
    if(in_array($extension,imageExtenstions())) return true;
    return false;
}

function get_file_link($path_source, $name = null) {
    if($name == null) return false;
    $path = files_asset_path($path_source);
    $link = $path . "/" . $name;
    $dev_link = get_files_path($path_source) . "/" . $name;
    if(is_file($dev_link)) return $link;
    return false;
}

function get_file_basename_ext_from_link(string $link) {
    $link = $link;
    $file_name = explode("/",$link);
    $file_name = end($file_name);
    $file_base = explode(".",$file_name);
    $extension = end($file_base);
    array_pop($file_base);
    $file_base = implode(".",$file_base);
    return (object) ['base_name' => $file_base, 'extension' => $extension];
}

function payment_gateway_const() {
    return PaymentGatewayConst::class;
}
function escrow_const() {
    return EscrowConstants::class;
}
function authWalletBalance(){
    if(auth()->guard('web')->check()){
        $wallet = UserWallet::where('user_id',auth()->user()->id)->first();
        return number_format($wallet->balance,2);
    }



}
function getAmount($amount, $length = 8)
{
    $amount = round($amount, $length);
    return $amount + 0;
}
function getTrxNum($length = 8)
{
    $characters = '123456789';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}


function get_user_notifications($quantity = 100)
{
    if(auth()->guard('web')->check()){ 
        $notifications = UserNotification::where('user_id', auth()->user()->id)->latest()->take($quantity)->get();
        return $notifications;
    }

}

function selectedLang(){
    $default_language = Language::where('status',GlobalConst::ACTIVE)->first();
    $default_language_code = $default_language->code ?? LanguageConst::NOT_REMOVABLE;
    return session()->get('local')?? $default_language_code;
}
function generate_google_2fa_auth_qr() {
    $google2FA = new \PragmaRX\Google2FA\Google2FA();
    $secret_key = $google2FA->generateSecretKey();
    $user = auth()->user();
    $site_url = App::make('url')->to('/');
    if($user->two_factor_secret) {
        $generate_text = $google2FA->getQRCodeUrl($site_url,$user->username,$user->two_factor_secret);
    }else {
        $generate_text = $google2FA->getQRCodeUrl($site_url,$user->username,$secret_key);
        $user->update([
            'two_factor_secret' => $secret_key,
        ]);
    }
    // $qr_image = 'https://chart.googleapis.com/chart?cht=qr&chs=350x350&chl='.$generate_text;
    $qr_image = 'https://qrcode.tec-it.com/API/QRCode?data='.$generate_text;
    return $qr_image;
}
function googleTwoFactorVerificationTemplate($user) {
    return redirect()->route('user.authorize.google.2fa')->with(['error' => ['Please verify two factor authentication']]);
} 

function google_2fa_verify($secret_key,$code) {
    $google2FA = new \PragmaRX\Google2FA\Google2FA();
    if($google2FA->verifyKey($secret_key, $code,0) == false) {
        throw ValidationException::withMessages([
            'code'       => "Invalid authentication code",
        ]);
        return false;
    }
    return true;
}
function google_2fa_verify_api($secret_key,$code) {
    $google2FA = new \PragmaRX\Google2FA\Google2FA();
    if($google2FA->verifyKey($secret_key, $code,0) == false) {
        // throw ValidationException::withMessages([
        //     'code'       => "Invalid authentication code",
        // ]);
        return false;
    }
    return true;
}
if(!function_exists('dateFormat')){
    function dateFormat($format, $date){
        return date($format, strtotime($date));
    }
}
function textLength($string, $length = 120)
{
    return Illuminate\Support\Str::limit($string, $length);
}
function generateTransactionReference()
{
    return 'TXREF_' . time();
}
function get_files_public_path($slug)
{
    $files_path = files_path($slug)->path ?? "";
    return "public/" . $files_path;
}
function getDynamicAmount($amount, $currency = null)
{
    if (!is_numeric($amount)) return "Not Number";

    $amount = doubleval($amount);

    if (strpos($amount, '.') !== false) {
        $amount = rtrim(rtrim($amount, '0'), '.');
        $explode = explode('.', $amount);
        if(strlen($explode[1]) == 1){
            $amount = number_format($amount, 2, ".", "");
        }
    } else {
        $amount = $amount . '.00';
    }

    if (!$currency) return $amount;
    $amount = $amount . " " . $currency;
    return $amount;
}
function getTotalAmountOnBaseCurr($transactions)
{
    $totalAmount = 0;
    foreach ($transactions as $transaction) {
        $requestAmount = $transaction->sender_request_amount;
        $walletRate = $transaction->user_wallets->currency->rate;
        $result = $requestAmount / $walletRate;
        $totalAmount += $result;
    }
    return $totalAmount??0 ;
}
function getTotalProfitOnBaseCurr($transactions)
{
    $totalAmount = 0;
    foreach ($transactions as $transaction) {
        $totalCharge = $transaction->transaction_details->total_charge??0;
        $walletRate = $transaction->user_wallets->currency->rate;
        $result = (floatval($totalCharge) *floatval( $walletRate)); 
        $totalAmount += $result;
    }
    return $totalAmount??0 ;
}
function send_push_notification(array $users,array $data) {
    $basic_settings = BasicSettingsProvider::get();
    if(!$basic_settings) {
        return false;
    }
    $notification_config = $basic_settings->push_notification_config;
    if(!$notification_config) {
        return false;
    }
    $instance_id    = $notification_config->instance_id ?? null;
    $primary_key    = $notification_config->primary_key ?? null;
    if($instance_id == null || $primary_key == null) {
        return false;
    }
    $notification = new PushNotifications(
        array(
            "instanceId" => $notification_config->instance_id,
            "secretKey" => $notification_config->primary_key,
        )
    );

    $notification_data = $data;

    $response = $notification->publishToUsers(
        $users,
        [
            "web"   => [
                "notification"      => $notification_data,
            ],
        ],
    );

    return $response;
}
function selectedLangDir(){
    if(session()->get('local')){
    $default_language = Language::where('code',session()->get('local'))->first();
    $default_language_dir = $default_language->dir ?? LanguageConst::NOT_REMOVABLE;
    }else{
        $default_language = Language::where('status',GlobalConst::ACTIVE)->first();
        $default_language_dir = $default_language->dir ?? LanguageConst::NOT_REMOVABLE;
    }
    return $default_language_dir;
}
function remove_special_char($string,$replace_string = "") {
    return preg_replace("/[^A-Za-z0-9]/",$replace_string,$string);
}
//flutterwave automatic withdrawal helper functions
function getFlutterwaveBanks($iso2){

    $cardApi = PaymentGateway::where('type',"AUTOMATIC")->where('alias','flutterwave-money-out')->first();
    $secretKey = getPaymentCredentials($cardApi->credentials,'Secret key');
    $base_url =getPaymentCredentials($cardApi->credentials,'Base Url');
    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL =>  $base_url.'/banks'.'/'.$iso2,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => array(
        "Authorization: Bearer ". $secretKey
      ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);
    $banks = json_decode($response,true);
    return $banks['data'];
}
function getPaymentCredentials($credentials,$label){
    $data = null;
    foreach ($credentials as $object) {
        $object = (object)$object;
        if ($object->label === $label) {
            $data = $object;
            break;
        }
    }
    return $data->value;
}
function checkBankAccount($account_number,$bank_code){
    $cardApi = PaymentGateway::where('type',"AUTOMATIC")->where('alias','flutterwave-money-out')->first();
    $secretKey = getPaymentCredentials($cardApi->credentials,'Secret key');
    $base_url =getPaymentCredentials($cardApi->credentials,'Base Url');
    $ch = curl_init();
    $url =   $base_url.'/accounts/resolve';
    $data = [
        "account_number" => $account_number,
        "account_bank" => $bank_code
    ];

    $headers = [
        "Authorization: Bearer ". $secretKey,
        'Content-Type: application/json'
    ];

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        return curl_errno($ch);
    } else {
        $data = json_decode($response,true);
        return $data;
    }

    curl_close($ch);
}
function currenciesCode(){
    $currencies = Currency::active()->get();
    $currencyCodes = $currencies->pluck('code')->unique()->implode(',');
    return $currencyCodes;
}
function gatewayCurrenciesCode(){
    $currencies =   PaymentGatewayCurrency::whereHas('gateway', function ($gateway) {
        $gateway->where('status', 1);
    })->get();
    $currencyCodes = $currencies->pluck('currency_code')->unique()->implode(',');
    return $currencyCodes;
}
function systemCurrenciesCode(){
    $setup_currency_code = currenciesCode();
    $payment_gateway_code = gatewayCurrenciesCode();

   $setup_currency_array = explode(',', $setup_currency_code);
   $payment_gateway_array = explode(',', $payment_gateway_code);

   $merged_array = array_merge($setup_currency_array, $payment_gateway_array);
   $unique_currency_codes = array_unique($merged_array);
   $result = implode(',', $unique_currency_codes);

  return $result??[];

}
function filterValidCurrencies($currencies) {
    $currency_array = explode(',', $currencies);
    $filtered_array = array_filter($currency_array, function($code) {
        return preg_match('/^[A-Za-z]{3}$/', $code);
    });
    return implode(',', $filtered_array);
}
function systemCurrenciesCodeArray(){
    $setup_currency_code = currenciesCode();
    $payment_gateway_code = gatewayCurrenciesCode();

    $setup_currency_array = explode(',', $setup_currency_code);
    $payment_gateway_array = explode(',', $payment_gateway_code);

    $merged_array = array_merge($setup_currency_array, $payment_gateway_array);
    $unique_currency_codes = array_unique($merged_array);

    return $unique_currency_codes??[];
}
function updateAbleCurrency(){
    $live_exchange_api = LiveExchangeRateApiSetting::where('slug',GlobalConst::CURRENCY_LAYER)->first();

    $api_currency_list = $live_exchange_api->value?->supported_currencies ?? [];
    $system_currency_list = systemCurrenciesCodeArray()??[];

     // Find out which system currencies are missing in the API list
     $missing_currencies = array_diff($system_currency_list, $api_currency_list);
     // Output the missing currencies for update rate
     $missing_currencies;

     // Find out which system currencies are present in the API list
    $matching_currencies = array_intersect($system_currency_list, $api_currency_list);
    // Output the matching currencies for update rate
    $matching_currencies;

    $data =[
        'missing_currencies' => $missing_currencies??[],
        'matching_currencies' => $matching_currencies??[],
    ];

    return  $data??[];

}

/**
 * update project localization data
 */
function update_project_localization_data()
{
    $update_keys_path       = base_path("update_lang/keys.json");
    $update_lang_file_path  = base_path('update_lang/file.xlsx');
    $predefined_keys_path   = base_path('lang/predefined_keys.json');

    $existing_lang_file_dir = get_files_path('language-file');
    $directory_files        = File::files($existing_lang_file_dir);

    // Sort files by last modified date in descending order
    usort($directory_files, function ($a, $b) {
        return $b->getMTime() <=> $a->getMTime();
    });

    $existing_lang_file = $directory_files[0] ?? null;

    if(!file_exists($update_keys_path) || !file_exists($update_lang_file_path) || !$existing_lang_file) return false;

    $update_keys_array          = json_decode(file_get_contents($update_keys_path));
    $update_lang_file_array     = (new LanguageImport)->toArray($update_lang_file_path)->columnData()->getArray();
    $existing_lang_file_array   = (new LanguageImport)->toArray($existing_lang_file)->columnData()->getArray();

    sleep(2);

    $languages = Language::get();

    // update predefined keys
    $predefined_keys_array  = json_decode(file_get_contents($predefined_keys_path));
    $new_unique_keys        = array_diff($update_keys_array, $predefined_keys_array);
    // $new_unique_keys        = $update_keys_array;

   

    $predefined_keys_array  = array_merge($predefined_keys_array, $new_unique_keys); // added new keys
    // update predefined keys file
    file_put_contents($predefined_keys_path,json_encode($predefined_keys_array));

    // add new language into existing languages
    foreach($languages as $lang) {
        $lang_file = base_path('lang/' . strtolower($lang->code). ".json");

        if(file_exists($lang_file)) {
            // update new keys and values
            $lang_contents_array = json_decode(file_get_contents($lang_file), true);

            // looping new language keys
            foreach($new_unique_keys as $new_key) {
                $update_lang_keys_array = $update_lang_file_array["Key"] ?? $update_lang_file_array["key"];
                $get_value_key_no       = array_search($new_key, $update_lang_keys_array);

                $get_update_value       = $update_lang_file_array[$lang->code][$get_value_key_no] ??
                                            $update_lang_file_array["en"][$get_value_key_no] ??
                                            $new_key;

                $lang_contents_array[$new_key] = $get_update_value;
            }

            // write new file with updated values
            file_put_contents($lang_file,json_encode($lang_contents_array));
        }else {
            // add new language file
            $update_lang_keys_array = $update_lang_file_array["Key"] ?? $update_lang_file_array["key"];
            $update_lang_array      = $update_lang_file_array[strtolower($lang->code)];

            $file_content = array_combine($update_lang_keys_array, $update_lang_array);

            create_file($lang_file);

            // write new file with updated values
            file_put_contents($lang_file,json_encode($file_content));
        }
    }

    sleep(2);

    // new lang file logic
    $file_unique_keys = array_diff($update_lang_file_array['Key'] ?? $update_lang_file_array['key'], $existing_lang_file_array["Key"] ??$existing_lang_file_array["key"]);

    foreach($file_unique_keys as $key_no => $key) {
        foreach($update_lang_file_array as $lang_code => $values) {
            if(array_key_exists($lang_code, $existing_lang_file_array)) {
                if($lang_code == "Key" || $lang_code == "key") {
                    $existing_lang_file_array["Key"][] = $key;
                }else {
                    // get values
                    $value     = $update_lang_file_array[$lang_code][$key_no] ?? "";

                    $existing_lang_file_array[$lang_code][] = $value;
                }
            }
        }
    }

    // add new language with new translated value
    foreach($update_lang_file_array as $lang_key => $lang_values) {
        if(!array_key_exists($lang_key, $existing_lang_file_array)) {
            $existing_lang_keys = $existing_lang_file_array["Key"];
            foreach($existing_lang_keys as $existing_key) {
                $update_key_no = array_search($existing_key, $update_lang_file_array['Key'] ?? $update_lang_file_array['key']);
                $update_key_value = $update_lang_file_array[$lang_key][$update_key_no] ?? "";

                $existing_lang_file_array[$lang_key][] = $update_key_value;
            }
        }

    }

    $excel_file_array = [
        array_merge(['Key'], $languages->pluck("code")->toArray()),
    ];
    $excel_keys = $existing_lang_file_array["Key"];

    foreach($excel_keys as $key => $value) {
        $key_mod = (int) $key + count($excel_file_array);

        foreach($existing_lang_file_array as $item) {
            $excel_file_array[$key_mod][] = $item[$key];
        }
    }

    sleep(2);

    // update language excel file
    $update_lang_file_name = "language-" . date("Y-m-d") . "-" . Str::uuid() . ".xlsx";
    $file_store_path = rtrim(files_path('language-file')->path, "/") . "/" . $update_lang_file_name;

    Excel::store(new class(array_values($excel_file_array)) implements FromArray {
        protected $data;

        public function __construct(array $data)
        {
            $this->data = $data;
        }

        public function array(): array
        {
            return $this->data;
        }
    }, $file_store_path,'public_path');

    sleep(2);

    File::delete($existing_lang_file);

    // delete update_lang dir
    File::deleteDirectory(base_path("update_lang"));

    return true;
}