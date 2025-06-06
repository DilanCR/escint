<?php

namespace App\Traits;

use Exception;

trait ControlDynamicInputFields {

    protected $file_store_location = "kyc-files";

    public function generateValidationRules($kyc_fields) {
        $validation_rules = [];
        foreach($kyc_fields ?? [] as $item) {
            $validation_rules[$item->name] = ($item->required) ? "required" : "nullable";
            $min = $item->validation->min ?? 0;
            $max = $item->validation->max ?? 0;
            if($item->type == "text" || $item->type == "textarea") {
                $validation_rules[$item->name]  .= "|string|min:". $min ."|max:". $max;
            }elseif($item->type == "file") {
                $max = $max * 1024;
                $mimes = $item->validation->mimes ?? [];
                $mimes = implode(",",$mimes);
                $mimes = remove_spaces($mimes);
                $validation_rules[$item->name]  .= "|file|mimes:". $mimes ."|max:".$max;
            }
        }
        return $validation_rules;
    }

    public function placeValueWithFields($kyc_fields,$form_data) {
        $fields_with_value = [];
        foreach($kyc_fields ?? [] as $key => $item) {
            if($item->type == "text" || $item->type == "textarea") {
                $value = $form_data[$item->name] ?? "";
            }elseif($item->type == "file") {
                $form_file = $form_data[$item->name] ?? "";
                if(is_file($form_file)) {
                    $get_file_link = upload_file($form_file,"junk-files");
                    $upload_file = upload_files_from_path_dynamic([$get_file_link['dev_path']],$this->file_store_location);
                    delete_file($get_file_link['dev_path']);
                    $value = $upload_file;
                }
            }elseif($item->type == "select") {
                $value = $form_data[$item->name] ?? "";
            }

            if(isset($form_data[$item->name])) {
                $fields_with_value[$key] = json_decode(json_encode($item),true);
                $fields_with_value[$key]['value'] = $value;
            }
        }

        try{
            $this->removeUserKycFiles();
        }catch(Exception $e) {
            // Handle Error
        }

        return $fields_with_value;
    }

    public function generatedFieldsFilesDelete($kyc_fields_with_value) {

        $files_link = [];
        $files_path = get_files_path($this->file_store_location);
        foreach($kyc_fields_with_value as $item) {
            if($item['type'] == "file") {
                $link = $files_path . "/" . $item['value'] ?? "";
                array_push($files_link,$link);
            }
        }
        delete_files($files_link);
    }

    public function removeUserKycFiles() {
        $user_kyc = auth()->user()->kyc;
        if($user_kyc) {
            if($user_kyc->data) {
                foreach($user_kyc->data ?? [] as $item) {
                    if($item->type == "file") {
                        $file_name = $item->value ?? "";
                        $file_path = get_files_path($this->file_store_location);
                        if(!empty($file_name)) {
                            $file_link = $file_path . "/" . $file_name;
                            delete_file($file_link);
                        }
                    }
                }
            }
        }
    }
}