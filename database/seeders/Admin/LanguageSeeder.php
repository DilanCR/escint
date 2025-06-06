<?php

namespace Database\Seeders\Admin;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Admin\Language;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            [
                'name'          => "English",
                'code'          => "en",
                'status'        => 1,
                'last_edit_by'  => 1,
                'dir'           => 'ltr',
                'created_at'    => now(),

            ],
            [
                'name'          => "Spanish",
                'code'          => "es",
                'status'        => 0,
                'last_edit_by'  => 1,
                'dir'           => 'ltr',
                'created_at'    => now(),

            ],
            [
                'name'          => "Arabic",
                'code'          => "ar",
                'status'        => 0,
                'last_edit_by'  => 1,
                'dir'           => 'rtl',
                'created_at'    => now(),

            ],
            [
                'name'          => "French",
                'code'          => "fr",
                'status'        => false,
                'last_edit_by'  => 1,
                'created_at'    => now(),
                'dir'           => "ltr",
            ],
            [
                'name'          => "Hindi",
                'code'          => "hi",
                'status'        => false,
                'last_edit_by'  => 1,
                'created_at'    => now(),
                'dir'           => "ltr",
            ]
        ];

        Language::truncate();
        Language::insert($data);
    }
}
