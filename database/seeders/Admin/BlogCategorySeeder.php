<?php

namespace Database\Seeders\Admin;

use App\Models\BlogCategory;
use Illuminate\Database\Seeder;

class BlogCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $blog_categories = array(
            array('admin_id' => '1','name' => 'Trends','slug' => 'trends','status' => '1','created_at' => '2023-10-04 09:59:10','updated_at' => '2023-11-25 08:59:28'),
            array('admin_id' => '1','name' => 'Security','slug' => 'security','status' => '1','created_at' => '2023-10-04 10:00:31','updated_at' => '2023-11-25 08:59:11'),
            array('admin_id' => '1','name' => 'Stories','slug' => 'stories','status' => '1','created_at' => '2023-11-25 09:00:03','updated_at' => '2023-11-25 09:00:03'),
            array('admin_id' => '1','name' => 'Perspectives','slug' => 'perspectives','status' => '1','created_at' => '2023-11-25 09:00:16','updated_at' => '2023-11-25 09:00:16')
          );
        BlogCategory::insert($blog_categories);  
    }
}
