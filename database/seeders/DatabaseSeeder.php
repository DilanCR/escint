<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Database\Seeders\User\UserSeeder;
use Database\Seeders\Admin\BlogSeeder;
use Database\Seeders\Admin\RoleSeeder;
use Database\Seeders\Admin\AdminSeeder;
use Database\Seeders\Admin\CurrencySeeder;
use Database\Seeders\Admin\LanguageSeeder;
use Database\Seeders\Admin\SetupKycSeeder;
use Database\Seeders\Admin\SetupSeoSeeder;
use Database\Seeders\Admin\ExtensionSeeder;
use Database\Seeders\Admin\SetupPageSeeder;
use Database\Seeders\User\UserWalletSeeder;
use Database\Seeders\Admin\AppSettingsSeeder;
use Database\Seeders\Admin\AdminHasRoleSeeder;
use Database\Seeders\Admin\BlogCategorySeeder;
use Database\Seeders\Admin\SiteSectionsSeeder;
use Database\Seeders\Admin\BasicSettingsSeeder;
use Database\Seeders\Admin\OnboardScreenSeeder;
use Database\Seeders\Admin\EscrowCategorySeeder;
use Database\Seeders\Admin\PaymentGatewaySeeder;
use Database\Seeders\Admin\LiveExchangeRateSeeder;
use Database\Seeders\Admin\SystemMaintenanceSeeder;
use Database\Seeders\Admin\TransactionSettingSeeder;
use Database\Seeders\Fresh\ExtensionSeeder as FreshExtensionSeeder;
use Database\Seeders\Fresh\BasicSettingsSeeder as FreshBasicSettingsSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {

        //demo
        // $this->call([
        //     AdminSeeder::class,
        //     CurrencySeeder::class,
        //     AppSettingsSeeder::class,
        //     OnboardScreenSeeder::class,
        //     RoleSeeder::class,
        //     AdminHasRoleSeeder::class,
        //     BasicSettingsSeeder::class,
        //     TransactionSettingSeeder::class,
        //     LanguageSeeder::class,
        //     SetupSeoSeeder::class,
        //     BlogCategorySeeder::class,
        //     BlogSeeder::class,
        //     SiteSectionsSeeder::class,
        //     SetupKycSeeder::class,
        //     ExtensionSeeder::class,
        //     SetupPageSeeder::class,
        //     EscrowCategorySeeder::class,

        //     PaymentGatewaySeeder::class,

        //     //user seeder
        //     UserSeeder::class,
        //     UserWalletSeeder::class,
        //     SystemMaintenanceSeeder::class,
        //     LiveExchangeRateSeeder::class,
        // ]);

        //fresh
        $this->call([
            AdminSeeder::class,
            CurrencySeeder::class,
            AppSettingsSeeder::class,
            OnboardScreenSeeder::class,
            RoleSeeder::class,
            AdminHasRoleSeeder::class,
            FreshBasicSettingsSeeder::class,
            TransactionSettingSeeder::class,
            LanguageSeeder::class,
            SetupSeoSeeder::class,
            BlogCategorySeeder::class,
            BlogSeeder::class,
            SiteSectionsSeeder::class,
            SetupKycSeeder::class,
            FreshExtensionSeeder::class,
            SetupPageSeeder::class,
            EscrowCategorySeeder::class,
            PaymentGatewaySeeder::class,

            SystemMaintenanceSeeder::class,
            LiveExchangeRateSeeder::class,
        ]);
    }
}
