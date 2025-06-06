<?php

namespace App\Http\Controllers\Api\V1;

use Exception;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\User\UserResouce;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use App\Providers\Admin\BasicSettingsProvider;
use App\Http\Helpers\Api\Helpers as ApiResponse;

class ProfileController extends Controller
{
    /**
     * Profile Get Data
     *
     * @method GET
     * @return \Illuminate\Http\Response
    */

    public function profile(){
        $user = Auth::user();

        $data =[
            'default_image' => "public/backend/images/default/profile-default.webp",
            "image_path"    => "public/frontend/user",
            "base_ur"       => url('/'),
            'user'          => $user,
            'countries'     => get_all_countries(),
        ];

        $message =  ['success'=>[__('User Profile')]];

        return ApiResponse::success($message,$data);
    }

    /**
     * Profile Update
     *
     * @method POST
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
    */
    public function profileUpdate(Request $request){

        $validator = Validator::make($request->all(),[
            'firstname'     => "required|string|max:60",
            'lastname'      => "required|string|max:60",
            'country'       => "nullable|string|max:50", 
            'phone_code'    => "required|string|max:6", 
            'phone'         => "nullable|string|max:20",
            'state'         => "nullable|string|max:50",
            'city'          => "nullable|string|max:50",
            'zip_code'      => "nullable|string|max:50",
            'address'       => "nullable|string|max:250",
            'image'         => "nullable|image|mimes:jpg,png,svg,webp|max:10240",
        ]);

        $user = auth()->guard(get_auth_guard())->user();

        if ($validator->fails()) {
            $message =  ['error' => $validator->errors()->all()];
            return ApiResponse::error($message);
        }

        $validated = $validator->validated();

        $validated['mobile']        = remove_speacial_char($validated['phone']);
        $validated['mobile_code']   = remove_speacial_char($validated['phone_code']);
        $complete_phone             = $validated['mobile_code'].$validated['mobile'];
        $validated['full_mobile']   = $complete_phone;
        $validated                  = Arr::except($validated,['agree','phone']); 
        $validated['address']       = [
            'country'   =>$validated['country'] ?? "",
            'state'     => $validated['state'] ?? "", 
            'city'      => $validated['city'] ?? "", 
            'zip'       => $validated['zip_code'] ?? "", 
            'address'   => $validated['address'] ?? "",
        ];

        if($request->hasFile('image')){

            if($user->image == null){
                $oldImage = null;
            }else{
                $oldImage = $user->image;
            }

            $image = upload_file($validated['image'],'user-profile', $oldImage);
            $upload_image = upload_files_from_path_dynamic([$image['dev_path']],'user-profile');
            delete_file($image['dev_path']);
            $validated['image']     = $upload_image;
        }

        try {
            $user->update($validated);
        } catch (\Throwable $th) {
            $error = ['error'=>[__('Something went worng! Please try again')]];
            return ApiResponse::error($error);
        }

        $message =  ['success'=>[__('Profile successfully updated!')]];
        return ApiResponse::onlySuccess($message);
    }

    /**
     * Password Update
     *
     * @method POST
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
    */
    public function passwordUpdate(Request $request){
        $basic_settings = BasicSettingsProvider::get();

        $passowrd_rule = 'required|string|min:6|confirmed';

        if($basic_settings->secure_password) {
            $passowrd_rule = ["required",Password::min(8)->letters()->mixedCase()->numbers()->symbols()->uncompromised(),"confirmed"];
        }

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string|min:6',
            'password' =>$passowrd_rule,
        ]);

        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return ApiResponse::validation($error);
        }

        $validated = $validator->validate();

        if (!Hash::check($request->current_password, auth()->guard(get_auth_guard())->user()->password)) {
            $message = ['error' =>  [__('Current password didn\'t match')]];
            return ApiResponse::error($message);
        }
        try {
            Auth::guard(get_auth_guard())->user()->update(['password' => Hash::make($validated['password'])]);
            $message = ['success' =>  [__('Password updated successfully!')]];
            return ApiResponse::onlySuccess($message);
        } catch (Exception $ex) {
            info($ex);
            $message = ['error' =>  [__('Something went wrong! Please try again')]];
            return ApiResponse::error($message);
        }
    }

    /**
     * Account Delete
     *
     * @method POST
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
    */
    public function deleteAccount(Request $request){

        $user = Auth::guard(get_auth_guard())->user();
        if(!$user){
            $message = ['success' =>  ['No user found']];
            return ApiResponse::error($message, []);
        }

        try {
            $user->status            = 0;
            $user->deleted_at        = now();
            $user->save();
        } catch (\Throwable $th) {
            $message = ['success' =>  [__('Something went wrong, please try again!')]];
            return ApiResponse::error($message, []);
        }

        $message = ['success' =>  [__('User deleted successfull')]];
        return ApiResponse::success($message, $user);
    }

    /**
     * Get Google 2FA
     *
     * @method Get
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
    */
    public function google2FA(){

        $user = Auth::guard(get_auth_guard())->user();

        $qr_code = generate_google_2fa_auth_qr();
        $qr_secrete = $user->two_factor_secret;
        $qr_status = $user->two_factor_status;

        $data = [
            'qr_code'    => $qr_code,
            'qr_secrete' => $qr_secrete,
            'qr_status'  => intval($qr_status),
            'alert'      => __("Don't forget to add this application in your google authentication app. Otherwise, you can't login to your account"),
        ]; 
        return ApiResponse::success(['success' => [__('Data fetch Successfully')]], $data);
    } 
    public function google2FAStatusUpdate(Request $request){
        $validator = Validator::make($request->all(),[
            'status'        => "required|numeric",
        ]);

        if($validator->fails()){
            return ApiResponse::onlyValidation(['error' => $validator->errors()->all()]);
        }

        $validated = $validator->validated();

        $user = Auth::guard(get_auth_guard())->user();


        try{
            $user->update([
                'two_factor_status'         => $validated['status'],
                'two_factor_verified'       => true,
            ]);
        }catch(Exception $e) {
           return ApiResponse::onlyError(['error' => [__('Something went wrong! Please try again')]]);
        }

        return ApiResponse::onlySuccess(['success' => [__('Google 2FA Updated Successfully!')]]);
    }
    //user profile type update ajax call 
    public function profileTypeUpdate(){
        $user = User::find(auth()->user()->id); 
        ($user->type == 'buyer') ? $user->type = 'seller' : $user->type = 'buyer'; 
        
        if($user->update()){ 
            return ApiResponse::onlySuccess(['success' => [__('Your profile type updated successfully')]]);
        }
        
    }
}
