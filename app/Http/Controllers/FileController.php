<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Http\Helpers\Response;

class FileController extends Controller
{
    public function storeFile(Request $request) {
        $data = [];
        if($request->hasFile('fileholder_files')) {

            $validator = Validator::make($request->all(),[
                'fileholder_files' => 'required|mimes:'.$request->mimes,
            ]);

            if($validator->fails()) {
                $data['error']  = $validator->errors()->all();
                $data['status'] = false;
                return response()->json($data,400);
            }

            $validated = $validator->safe()->all();

            $file_holder_files = $validated['fileholder_files'];
            $file_ext = $file_holder_files->getClientOriginalExtension();
            $file_store_name = Str::uuid() . "." . $file_ext;
            $data['path']   = asset('public/fileholder/img/');
            $data['file_name']  =  $file_store_name;
            $data['file_link']  = $data['path'] . "/" . $data['file_name'];
            $data['file_type']  = $file_holder_files->getClientMimeType();
            $data['file_old_name']  = $file_holder_files->getClientOriginalName();

            $data['status'] = true;
            try{
                File::move($file_holder_files,public_path('/fileholder/img/'.$file_store_name));
                chmod(public_path('/fileholder/img/'.$file_store_name), 0644);
            }catch(Exception $e) {
                return print_r($e);
                $data['status'] = false;
            }
        }else {
            $data['status'] = false;
            $data['error'] = __("Something went wrong! File is not detected");
        }
        return response()->json($data,200);
    }
    public function removeFile(Request $request) {
        $validator = Validator::make($request->all(),[
            'file_info' => 'required|json',
        ]);

        if($validator->fails()) {
            $data['error']  = $validator->errors()->all();
            $data['status'] = false;
            return response()->json($data,400);
        }

        $validated = $validator->safe()->all();

        $file_path = '/fileholder/img';

        $file_info = json_decode($validated['file_info']);
        $data['status'] = true;
        try {
            FIle::delete(public_path($file_path.'/'.$file_info->file_name));
            $data['message'] = "File Deleted Successfully!";
        }catch(Exception $e) {
            $data['status'] = false;
            $data['error'] = $e;
            $data['message'] = __("Something Went wrong! Please try again");
        }

        $data['file_info'] = $file_info;

        return response()->json($data,200);

    }
    public function conversationFileStore(Request $request) {

        $validator = Validator::make($request->all(),[
            'file'      => "required|file|mimes:png,jpg,webp,svg,txt,jpeg,zip,doc,pdf,mp4,mov,wmv,avi,mkv,webm|max:200000",
            'order'     => "nullable",
        ]);

        if($validator->fails()) {
            return Response::error($validator->errors()->all(),[],400);
        }

        try{
            $validated = $validator->validate();

            if($request->hasFile("file")) {
                $upload_file = upload_file($validated['file'],'junk-files');
                chmod($upload_file['dev_path'], 0644);
                $upload_file['order'] = $validated['order'];
                return Response::success(['File store successfully!'],$upload_file,200);
            }
        }catch(Exception $e) {
            return Response::error([__('Oops! Failed to process file')],[],500);
        }

        return Response::error([__('Something went wrong! Please try again')],[],500);
    }
    public function conversationFileRemove(Request $request) {
        $validator = Validator::make($request->all(),[
            'path'      => "required|string"
        ]);

        if($validator->fails()) {
            return Response::error($validator->errors()->all(),[],400);
        }

        $validated = $validator->validate();

        if(File::exists($validated['path'])) {
            try{
                File::delete($validated['path']);
            }catch(Exception $e) {
                return Response::error([__('Failed to remove file. Please try again')],[],500);
            }
        }

        return Response::success([__('File successfully removed')],['status' => 'success'],200);
    }
}
