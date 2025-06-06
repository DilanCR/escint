<?php

namespace Database\Seeders\Admin;

use App\Models\Admin\TransactionSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TransactionSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $transaction_settings = array(
            array('admin_id' => '1','slug' => 'escrow','title' => 'Escrow Charges','fixed_charge' => '1.00','percent_charge' => '2.00','min_limit' => '1.00','max_limit' => '50000.00','monthly_limit' => '50000.00','daily_limit' => '5000.00','status' => '1','created_at' => NULL,'updated_at' => '2023-10-30 05:58:12'),
            array('admin_id' => '1','slug' => 'MONEY-EXCHANGE','title' => 'Exchange Charges','fixed_charge' => '5.00','percent_charge' => '2.00','min_limit' => '2.00','max_limit' => '50000.00','monthly_limit' => '50000.00','daily_limit' => '5000.00','status' => '1','created_at' => NULL,'updated_at' => '2023-10-18 06:22:08')
          );
        TransactionSetting::insert($transaction_settings);
    }
}
