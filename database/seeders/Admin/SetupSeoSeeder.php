<?php

namespace Database\Seeders\Admin;

use App\Models\Admin\SetupSeo;
use Illuminate\Database\Seeder;

class SetupSeoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $setup_seos = array(
            array('id' => '1','slug' => 'seo_data','title' => 'Escroc-Money Transfer with Escrow','desc' => 'Introducing Escroc, the all-encompassing Money Transfer with Escrow Full Solution available on CodeCanyon. This comprehensive package includes a website, versatile cross-platform Android and iOS apps, and a user-friendly admin panel for seamless management.','tags' => '["Add money", "admin panel", "android", "create escrow", "cross-platform", "escrow", "exchange currency", "flutter app", "ios", "money transfer", "multi-currency", "multiple categories", "P2P live chat", "payment solution", "wallet"]','image' => 'seeder/seo_image.webp','last_edit_by' => '1','created_at' => now(),'updated_at' => now())
          );


        SetupSeo::insert($setup_seos);
    }
}
