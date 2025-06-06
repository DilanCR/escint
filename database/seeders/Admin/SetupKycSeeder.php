<?php

namespace Database\Seeders\Admin;

use Illuminate\Database\Seeder;
use App\Models\Admin\SetupKyc;

class SetupKycSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $setup_kycs = array(
            array(
                'slug' => 'user','user_type' => 'USER','fields' => '[{"type":"text","label":"NID","name":"nid","required":true,"validation":{"max":"30","mimes":[],"min":"10","options":[],"required":true}},{"type":"text","label":"Passport","name":"passport","required":true,"validation":{"max":"30","mimes":[],"min":"10","options":[],"required":true}},{"type":"text","label":"Driving Licensee","name":"driving_licensee","required":true,"validation":{"max":30,"mimes":[],"min":10,"options":[],"required":true}}]','status' => '1','last_edit_by' => '1','created_at' => now(),'updated_at' => now())
            );
        SetupKyc::insert($setup_kycs);
    }
}
