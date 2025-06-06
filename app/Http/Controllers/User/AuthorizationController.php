<?php

namespace App\Http\Controllers\User;

use App\Constants\GlobalConst;
use App\Http\Controllers\Controller;
use App\Models\Admin\SetupKyc;
use App\Models\UserAuthorization;
use App\Models\UserKycData;
use App\Notifications\User\Auth\SendAuthorizationCode;
use App\Providers\Admin\BasicSettingsProvider;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Traits\ControlDynamicInputFields;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AuthorizationController extends Controller
{
    use ControlDynamicInputFields;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function showMailFrom($token) {
        $page_title = setPageTitle("Mail Authorization");
        return view('user.auth.authorize.verify-mail',compact("page_title","token"));
    } 
    /**
     * Verify authorizaation code.
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function mailVerify(Request $request,$token) {
        $request->merge(['token' => $token]);
        $request->validate([
            'token'     => "required|string|exists:user_authorizations,token",
            'code'      => "required|integer|exists:user_authorizations,code",
        ]);
        $otp_exp_sec = BasicSettingsProvider::get()->otp_exp_seconds ?? GlobalConst::DEFAULT_TOKEN_EXP_SEC;
        $auth_column = UserAuthorization::where("token",$request->token)->where("code",$request->code)->first();
        if($auth_column->created_at->addSeconds($otp_exp_sec) < now()) {
            $this->authLogout($request);
            return redirect()->route('user.login')->with(['error' => ['Session expired. Please try again']]);
        } 
        try{
            $auth_column->user->update([
                'email_verified'    => true,
            ]);
            $auth_column->delete();
        }catch(Exception $e) {
            $this->authLogout($request);
            return redirect()->route('user.login')->with(['error' => [__('Something went wrong! Please try again')]]);
        } 
        return redirect()->intended(route("user.dashboard"))->with(['success' => [__('Account successfully verified')]]);
    }
    public function resendCode($token) {
        $resend_code = UserAuthorization::where('token',$token)->first();
        if(!$resend_code) return back()->with(['error' => [__('Request token is invalid')]]);
        if(Carbon::now() <= $resend_code->created_at->addMinutes(GlobalConst::USER_PASS_RESEND_TIME_MINUTE)) {
            throw ValidationException::withMessages([
                'code'      => 'You can resend verification code after '.Carbon::now()->diffInSeconds($resend_code->created_at->addMinutes(GlobalConst::USER_PASS_RESEND_TIME_MINUTE)). ' seconds',
            ]);
        } 
        DB::beginTransaction();
        try{
            $update_data = [
                'code'          => generate_random_code(),
                'created_at'    => now(),
                'token'         => $token,
            ];
            DB::table('user_authorizations')->where('token',$token)->update($update_data);
            $resend_code->user->notify(new SendAuthorizationCode((object) $update_data));
            DB::commit();
        }catch(Exception $e) {
            DB::rollback();
            return back()->with(['error' => [__('Something went worng. please try again')]]);
        }
        return redirect()->route('user.authorize.mail',$token)->with(['success' => [__('Varification code resend success!')]]); 
    }
    public function authLogout(Request $request) {
        auth()->guard("web")->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    } 
    public function showKycFrom() {
        $user = auth()->user(); 
        $page_title = "KYC Verification";
        $user_kyc = SetupKyc::userKyc()->first();
        if(!$user_kyc) return back();
        $kyc_data = $user_kyc->fields;
        $kyc_fields = [];
        if($kyc_data) {
            $kyc_fields = array_reverse($kyc_data);
        }
        return view('user.auth.authorize.verify-kyc',compact("page_title","kyc_fields"));
    } 
    public function kycSubmit(Request $request) { 
        $user = auth()->user();
        if($user->kyc_verified == GlobalConst::VERIFIED) return back()->with(['success' => [__('You are already KYC Verified User')]]); 
        $user_kyc_fields = SetupKyc::userKyc()->first()->fields ?? [];
        $validation_rules = $this->generateValidationRules($user_kyc_fields); 
        $validated = Validator::make($request->all(),$validation_rules)->validate();
        $get_values = $this->placeValueWithFields($user_kyc_fields,$validated); 
        $create = [
            'user_id'       => auth()->user()->id,
            'data'          => json_encode($get_values),
            'created_at'    => now(),
        ]; 
        DB::beginTransaction();
        try{
            DB::table('user_kyc_data')->updateOrInsert(["user_id" => $user->id],$create);
            $user->update([
                'kyc_verified'  => GlobalConst::PENDING,
            ]);
            DB::commit();
        }catch(Exception $e) {
            DB::rollBack();
            $user->update([
                'kyc_verified'  => GlobalConst::DEFAULT,
            ]);
            $this->generatedFieldsFilesDelete($get_values);
            return back()->with(['error' => [__('Something went wrong! Please try again')]]);
        } 
        return redirect()->back()->with(['success' => [__('KYC information successfully submited')]]);
    }
    public function showGoogle2FAForm() {
        $page_title =  "Authorize Google Two Factor";
        return view('user.auth.authorize.verify-google-2fa',compact('page_title'));
    } 
    public function google2FASubmit(Request $request) { 
        $request->validate([
            'code'      => "required|array",
            'code.*'    => "required|numeric",
        ]);
        $code = $request->code;
        $code = implode("",$code); 
        $user = auth()->user(); 
        if(!$user->two_factor_secret) {
            return back()->with(['warning' => [__('Your secret key not stored properly. Please contact with system administrator')]]);
        } 
        if(google_2fa_verify($user->two_factor_secret,$code)) { 
            $user->update([
                'two_factor_verified'   => true, 
            ]); 
            return redirect()->intended(route('user.dashboard'));
        } 
        return back()->with(['warning' => [__('Faild to login. Please try again')]]);
    }
}
