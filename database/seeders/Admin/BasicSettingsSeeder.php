<?php

namespace Database\Seeders\Admin;

use App\Models\Admin\BasicSettings;
use Illuminate\Database\Seeder;

class BasicSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            'site_name'       => "Escroc",
            'site_title'      => "Money Transfer with Escrow",
            'base_color'      => "#44a08d",
            'web_version'         => '1.5.0',
            'otp_exp_seconds' => "240",
            'timezone'        => "Asia/Dhaka",
            'broadcast_config'  => [
                "method" => "pusher", 
                "app_id" => "1539602", 
                "primary_key" => "39079c30de823f783dbe", 
                "secret_key" => "78b81e5e7e0357aee3df", 
                "cluster" => "ap2" 
            ],
            'push_notification_config'  => [
                "method" => "pusher", 
                "instance_id" => "809313fc-1f5c-4d0b-90bc-1c6751b83bbd", 
                "primary_key" => "58C901DC107584D2F1B78E6077889F1C591E2BC39E9F5C00B4362EC9C642F03F"
            ],
            'kyc_verification'  => true,
            'mail_config'       => [
                "method" => "smtp",
                "host" => "appdevs.net",
                "port" => "465",
                "encryption" => "ssl",
                "username" => "system@appdevs.net",
                "password" => "QP2fsLk?80Ac",
                "from" => "noreply@appdevs.net",
                "app_name" => "Escroc",
            ],
            'email_verification'    => true,
            'user_registration'     => true,
            'agree_policy'          => true,
            'email_notification'    => true,
            'site_logo_dark'        => "3c2fd3d2-bd03-476d-9f21-5b6372280c28.webp",
            'site_logo'             => "06f76b4d-2a9a-4a4b-bb30-f745fbcbb863.webp",
            'site_fav_dark'         => "8546378c-d00b-4e81-be16-b01d20e13b3a.webp",
            'site_fav'              => "9283cd8d-6d67-44c1-b341-e3fb0f7d2ff5.webp",
        ];

        BasicSettings::firstOrCreate($data);
    }
}
