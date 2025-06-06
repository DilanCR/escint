<?php

namespace Database\Seeders\Update;

use Exception;
use App\Models\Admin\Language;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $languages      = Language::get()->pluck("code")->toArray();
        if(count($languages) > 0) {
            $files          = File::files(base_path('lang'));
            $json_files      = array_filter($files, function ($file) {
                return $file->getExtension() === 'json' && $file->getBasename() != "predefined_keys.json"; 
            });
            $file_names      = array_map(function ($file) {
                return pathinfo($file->getFilename(), PATHINFO_FILENAME);
            }, $json_files);
            $diff_items = array_diff($file_names, $languages);
            foreach($diff_items as $item){
                $file_link = base_path('lang/' . $item . ".json");
                if(file_exists($file_link)) {
                    File::delete($file_link);
                }
            }
        }
        
        $languages = array(
            array('name' => 'Hindi','code' => 'hi','status' => '0','last_edit_by' => '1','created_at' => '2024-10-25 08:59:58','updated_at' => '2024-10-25 08:59:58','dir' => 'ltr'),
            array('name' => 'French','code' => 'fr','status' => '0','last_edit_by' => '1','created_at' => '2024-10-25 09:04:40','updated_at' => '2024-10-25 09:04:40','dir' => 'ltr')

        );

        foreach ($languages as $language) {
            // Check if the language code already exists
            if (!Language::where('code', $language['code'])->exists()) {
                // Insert the language if it doesn't exist
                Language::create($language);
            }
        }

        // update language keys
        try{
            update_project_localization_data();
        }catch(Exception $e) {
            // handle error
        }
     
    }
}
