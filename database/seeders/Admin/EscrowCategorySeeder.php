<?php

namespace Database\Seeders\Admin;

use App\Models\EscrowCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EscrowCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $escrow_categories = array(
            array('added_by' => '1','name' => 'Domain','slug' => 'domain','status' => '1','created_at' => '2023-10-07 07:16:28','updated_at' => '2023-10-07 11:31:28'),
            array('added_by' => '1','name' => 'General Merchandise','slug' => 'general-merchandise','status' => '1','created_at' => '2023-10-07 09:10:49','updated_at' => '2023-10-07 10:10:36'),
            array('added_by' => '1','name' => 'Motor Vehicle','slug' => 'motor-vehicle','status' => '1','created_at' => '2023-10-07 10:11:27','updated_at' => '2023-10-07 10:11:27'),
            array('added_by' => '1','name' => 'Web Design','slug' => 'web-design','status' => '1','created_at' => '2023-10-07 11:31:57','updated_at' => '2023-10-07 11:31:57'),
            array('added_by' => '1','name' => 'Web Development','slug' => 'web-development','status' => '1','created_at' => '2023-10-07 11:32:09','updated_at' => '2023-10-07 11:32:09'),
            array('added_by' => '1','name' => 'Content Writing','slug' => 'content-writing','status' => '1','created_at' => '2023-10-07 11:32:22','updated_at' => '2023-10-07 11:32:22'),
            array('added_by' => '1','name' => 'App Development','slug' => 'app-development','status' => '1','created_at' => '2023-10-07 11:32:37','updated_at' => '2023-10-07 11:32:37')
          );
        EscrowCategory::insert($escrow_categories);
    }
}
