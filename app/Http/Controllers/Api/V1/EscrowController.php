<?php

namespace App\Http\Controllers\Api\V1;

use Exception;
use App\Models\User;
use App\Models\Escrow;
use App\Models\UserWallet;
use Illuminate\Http\Request;
use App\Models\EscrowDetails;
use App\Models\TemporaryData;
use App\Models\Admin\Currency;
use App\Models\EscrowCategory;
use Illuminate\Support\Carbon;
use App\Models\UserNotification;
use App\Constants\EscrowConstants;
use Illuminate\Support\Facades\DB;
use App\Constants\NotificationConst;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Constants\PaymentGatewayConst; 
use App\Models\Admin\CryptoTransaction;
use App\Models\Admin\TransactionSetting;
use App\Traits\ControlDynamicInputFields;
use Illuminate\Support\Facades\Validator;
use App\Http\Helpers\EscrowPaymentGateway; 
use App\Notifications\Escrow\EscrowRequest;
use App\Models\Admin\PaymentGatewayCurrency;
use App\Http\Helpers\Api\Helpers as ApiResponse;
use App\Models\Admin\PaymentGateway as PaymentGatewayModel; 

class EscrowController extends Controller
{
    use ControlDynamicInputFields;
    public function index() {
        $escrowData = Escrow::with('escrowCategory','escrowDetails')->where('user_id', auth()->user()->id)->orWhere('buyer_or_seller_id',auth()->user()->id)->latest()->get()->map(function($data){
            return[
                'id'              => $data->id,
                'user_id'              => $data->user_id,
                'escrow_id'       => $data->escrow_id,
                'title'           => $data->title,
                'role'            => $data->opposite_role,
                'amount'          => get_amount($data->amount, $data->escrow_currency),
                'escrow_currency' => $data->escrow_currency,
                'category'        => $data->escrowCategory->name,
                'total_charge'    => get_amount($data->escrowDetails->fee, $data->escrow_currency),
                'charge_payer'    => $data->string_who_will_pay->value,
                'status'          => $data->status,
                'remarks'          => $data->remark,
                'attachments' => collect($data->file)->map(function ($data) {
                    return [
                        'file_name' => $data->attachment,
                        'file_type' => json_decode($data->attachment_info)->type,
                        'file_path' => files_asset_path('escrow-temp-file'),
                    ];
                })->toArray(),
                'created_at'      => $data->created_at,
            ];
        }); 
        $data =[
            'escrow_data'         => $escrowData,  
            'base_url'          => url('/'), 
        ];
        $message = ['success'=>[__('Escrow Information')]];
        return ApiResponse::success($message, $data);
    }
    public function create() {
        $user = auth()->user();
        //escrow categories
        $escrowCategories = EscrowCategory::where('status', true)->latest()->get()->map(function($data){
            return[
                'id' => $data->id,
                'name' => $data->name, 
            ];
        });
        // user wallet
        $userWallet = UserWallet::with('currency')->where('user_id',$user->id)->get()->map(function($data){
            return[
                'name'            => $data->currency->name,
                'balance'         => $data->balance,
                'currency_code'   => $data->currency->code,
                'currency_symbol' => $data->currency->symbol,
                'currency_type'   => $data->currency->type,
                'rate'            => $data->currency->rate,
                'flag'            => $data->currency->flag,
                'image_path'      => get_files_public_path('currency-flag'),
            ];
        });
        //add money payment gateways currencys
        $gatewayCurrencies = PaymentGatewayCurrency::whereHas('gateway', function ($gateway) {
            $gateway->where('slug', PaymentGatewayConst::add_money_slug());
            $gateway->where('status', 1);
        })->get()->map(function($data){
            return[
                'id'                 => $data->id,
                'payment_gateway_id' => $data->payment_gateway_id,
                'type'               => $data->gateway->type,
                'name'               => $data->name,
                'alias'              => $data->alias,
                'currency_code'      => $data->currency_code,
                'currency_symbol'    => $data->currency_symbol,
                'image'              => $data->image,
                'min_limit'          => getAmount($data->min_limit, 8),
                'max_limit'          => getAmount($data->max_limit, 8),
                'percent_charge'     => getAmount($data->percent_charge, 8),
                'fixed_charge'       => getAmount($data->fixed_charge, 8),
                'rate'               => getAmount($data->rate, 8),
                'created_at'         => $data->created_at,
                'updated_at'         => $data->updated_at,
            ];
        });
        $data =[
            'user_type'          => $user->type,
            'escrow_categories'  => $escrowCategories,
            'user_wallet'        => $userWallet,
            'gateway_currencies' => $gatewayCurrencies,
            'base_url'           => url('/'),
        ];
        $message = ['success'=>[__('Escrow Create Data')]];
        return ApiResponse::success($message, $data);
    }
    //escrow submit
    public function submit(Request $request) {
        $validator = Validator::make($request->all(), [
            'title'                 => 'required|string',
            'escrow_category'       => 'required|integer',
            'role'                  => 'required|string',
            'who_will_pay_options'  => 'required|string',
            'buyer_seller_identify' => 'required',
            'amount'                => 'required|numeric',
            'escrow_currency'       => 'required|string',
            'payment_gateway'       => 'nullable',
            'remarks'               => 'nullable|string',
            'file.*'                => "nullable|file|max:100000|mimes:jpg,jpeg,png,pdf,zip",
        ]);
        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return ApiResponse::validation($error);
        }
        $validated            = $validator->validate();
        $escrowCategory       = EscrowCategory::find($validated['escrow_category']);
        $getEscrowChargeLimit = TransactionSetting::find(1);
        $sender_currency      = Currency::where('code', $validated['escrow_currency'])->first();
        $digitShow            = $sender_currency->type == "CRYPTO" ? 6 : 2 ;
        $user                 = User::where('username',$validated['buyer_seller_identify'])->orWhere('email',$validated['buyer_seller_identify'])->first();
        //user check 
        if(empty($user) || $user->email == auth()->user()->email) return ApiResponse::validation(['error' => [__('User not found')]]);
        //get payment method
        $payment_type = EscrowConstants::DID_NOT_PAID;
        $payment_gateways_currencies = null;
        $gateway_currency = null;
        if ($validated['role'] == "buyer") {
            if ($validated['payment_gateway'] == "myWallet") {
                $user_wallets = UserWallet::where(['user_id' => auth()->user()->id, 'currency_id' => $sender_currency->id])->first();
                if(empty($user_wallets)) return ApiResponse::onlyError(['error' => ['Wallet not found.']]);
                if($user_wallets->balance == 0 || $user_wallets->balance < 0 || $user_wallets->balance < $validated['amount']) return ApiResponse::onlyError(['error' => [__('Insuficiant Balance')]]);
                $payment_method        = "My Wallet";
                $gateway_currency      = $validated['escrow_currency'];
                $gateway_exchange_rate = 1;
                $payment_type          = EscrowConstants::MY_WALLET;
            } else{
                $payment_gateways_currencies = PaymentGatewayCurrency::with('gateway')->find($validated['payment_gateway']);
                if(!$payment_gateways_currencies || !$payment_gateways_currencies->gateway) { 
                    return ApiResponse::onlyError(['error' => ['Payment gateway not found.']]);
                }
                $payment_method   = $payment_gateways_currencies->name;
                $gateway_currency = $payment_gateways_currencies->currency_code;
                //calculate gateway exchange rate 
                $gateway_exchange_rate =  (1/$sender_currency->rate)*$payment_gateways_currencies->rate;  //this currency is converted in payment gateway currency
                $payment_type = EscrowConstants::GATEWAY;
            }
        }
        $request_amount = $validated['amount'];
        //convert escrow currency amount into default currency
        $usd_exchange_amount = (1/$sender_currency->rate)*$request_amount;
        //charge calculate in USD currency 
        $usd_fixed_charge   = $getEscrowChargeLimit->fixed_charge;
        $usd_percent_charge = ($getEscrowChargeLimit->percent_charge/100)*$usd_exchange_amount;
        $usd_total_charge   = $usd_fixed_charge+$usd_percent_charge;
        //final charge in escrow currency
        $escrow_total_charge = $usd_total_charge*$sender_currency->rate;
        //limit check 
        if($getEscrowChargeLimit->min_limit > $usd_exchange_amount || $getEscrowChargeLimit->max_limit < $usd_exchange_amount) return ApiResponse::validation(['error' => [__('Please follow the escrow limit')]]);
        //calculate seller amount 
        $seller_amount = 0;
        if ($validated['who_will_pay_options'] == "seller") {
            $seller_amount = $request_amount - $escrow_total_charge;
        }else if($validated['who_will_pay_options'] == "half"){
            $seller_amount = $request_amount - ($escrow_total_charge/2);
        }else if($validated['who_will_pay_options'] == "me" && $validated['role'] == "seller"){
            $seller_amount = $request_amount - $escrow_total_charge;
        }else{
            $seller_amount = $request_amount;
        } 
        //calculate buyer amount 
        $buyer_amount = 0;
        if ($validated['role'] == "buyer") {
            if ($validated['who_will_pay_options'] == "buyer") {
                $buyer_amount = ($request_amount+$escrow_total_charge)*$gateway_exchange_rate;
            }else if($validated['who_will_pay_options'] == "half"){
                $buyer_amount = ($request_amount + ($escrow_total_charge/2))*$gateway_exchange_rate;
            }else if($validated['who_will_pay_options'] == "me" && $validated['role'] == "buyer"){
                $buyer_amount = ($request_amount+$escrow_total_charge)*$gateway_exchange_rate;
            }else{
                $buyer_amount = $request_amount*$gateway_exchange_rate;
            }
            if ($validated['payment_gateway'] == "myWallet") {
                if($user_wallets->balance == 0 || $user_wallets->balance < 0 || $user_wallets->balance < $buyer_amount) return ApiResponse::onlyError(['error' => [__('Insuficiant Balance.Here escrow charge will be substack with your wallet. Your escrow charge is') .$escrow_total_charge.' '.$validated['escrow_currency']]]);
            }
        } 
        $oldData = (object) [ 
            'buyer_or_seller_id'          => $user->id,
            'escrow_category_id'          => $validated['escrow_category'],
            'payment_type'                => $payment_type,
            'payment_gateway_currency_id' => $payment_type == EscrowConstants::GATEWAY ? $payment_gateways_currencies->id : null,

            'user_id'         => auth()->user()->id,
            'title'           => $validated['title'],
            'role'            => $validated['role'],
            'product_type'    => $escrowCategory->name,
            'amount'          => $validated['amount'],
            'escrow_currency' => $validated['escrow_currency'],
            'charge_payer'    => $validated['who_will_pay_options'],

            'escrow_total_charge'   => $escrow_total_charge,
            'seller_amount'         => $seller_amount ?? 0,
            'gateway_currency'      => $gateway_currency ?? "null",
            'payment_method'        => $payment_method ?? "null",
            'gateway_exchange_rate' => $gateway_exchange_rate ?? 0,
            'buyer_amount'          => $buyer_amount ?? 0,

            'remarks' => $validated['remarks'] ?? null,
            'file'    => null,
        ];
             //file upload
             $attachment = [];
             if($request->hasFile('file')) {
                 $validated_files = $request->file("file");
                 $attachment      = [];
                 $files_link      = [];
                 foreach($validated_files as $item) {
                     $upload_file = upload_file($item,'escrow-temp-file');
                     chmod($upload_file['dev_path'], 0644);
                     if($upload_file != false) {
                         $attachment[] = [
                             'attachment'      => $upload_file['name'],
                             'attachment_info' => json_encode($upload_file),
                             'created_at'      => now(),
                         ];
                     }
     
                     $files_link[] = get_files_path('escrow-temp-file') . "/". $upload_file['name'];
                 } 
                 try{
                     $attachment = $attachment;
                 }catch(Exception $e) {
                     delete_files($files_link); 
                     return ApiResponse::validation(['error' => [__('Opps! Failed to upload attachment. Please try again')]]);
                 }
             }
             $identifier = generate_unique_string("escrows","escrow_id",16);
             $tempData = [
                'trx'              => $identifier,
                'escrow'           => $oldData,
                'gateway_currency' => $payment_gateways_currencies ?? null,
                'attachment'       => json_encode($attachment) ?? null,
                'creator_table' => auth()->guard(get_auth_guard())->user()->getTable(),
                'creator_id'    => auth()->guard(get_auth_guard())->user()->id,
                'creator_guard' => get_auth_guard(),                       //for sscommerz relogin after payment
             ];
             $this->addEscrowTempData($identifier, $tempData);
             $previewData = [ 
                'trx'           => $identifier,
                'title'         => $validated['title'],
                'category'      => $escrowCategory->name,
                'my_role'       => $validated['role'],
                'total_amount'  => get_amount($validated['amount'],$validated['escrow_currency']),
                'charge_payer'  => $validated['who_will_pay_options'],
                'fee'           => get_amount($escrow_total_charge, $validated['escrow_currency']),
                'seller_amount' => get_amount($seller_amount, $validated['escrow_currency'])  ?? 0,
                'pay_with'      => $payment_method ?? "null", 
                'exchange_rate' => "1".' '.$validated['escrow_currency'].' = '.get_amount($gateway_exchange_rate ?? 0 , $gateway_currency ?? "USD"),
                'buyer_amount'  => get_amount($buyer_amount, $gateway_currency ?? "USD") ?? 0,
             ];
             $data = [
                'escrow_information' => $previewData,
                'return_url'         => route('api.v1.user.my-escrow.confirm'),
                'method'             => "post",
            ];
            $message = ['success'=>[__('Escrow Inserted Successfully')]];
            return ApiResponse::success($message, $data);
    }
    //escrow temp data insert
    public function addEscrowTempData($identifier,$data) {  
        return TemporaryData::create([
            'type'       => "add-escrow",
            'identifier' => $identifier,
            'data'       => $data,
        ]);
    }
    //escrow confirm 
    public function successConfirm(Request $request) {
        $requestData   = $request->all();
        $token         = $requestData['trx'] ?? "";
        $tempData   = TemporaryData::where("identifier",$token)->first();
        if (!$tempData){
            $message = ['error' => [__("Escrow failed. Record didn\'t saved properly. Please try again")]];
            return ApiResponse::onlyError($message);
        }
        if ($tempData->data->escrow->role == EscrowConstants::SELLER_TYPE) { 
            $this->createEscrow($tempData);
            $message = ['success' => [__("Escrow created Successful, Please Go Back Your App")]];
            return ApiResponse::onlysuccess($message);
        }
        //escrow wallet payment 
        if ($tempData->data->escrow->payment_type == EscrowConstants::MY_WALLET) {
            $this->escrowWalletPayment($tempData);
            $this->createEscrow($tempData);
            $message = ['success' => [__("Escrow created Successful, Please Go Back Your App")]];
            return ApiResponse::onlysuccess($message);
        }
        //escrow payment by payment gateway
        if ($tempData->data->escrow->payment_type == EscrowConstants::GATEWAY) {
            try{
                $instance = EscrowPaymentGateway::init($tempData)->apiGateway(); 
                return $instance;
            }catch(Exception $e) {
                $message = ['error' => [$e->getMessage()]];
                return ApiResponse::onlyError($message); 
            } 
        } 
        $message = ['error' => [__("Something went wrong")]];
        return ApiResponse::onlyError($message); 
    }
    public function razorPayLinkCreate(){
        $request_data = request()->all(); 
        $temData = TemporaryData::where('identifier',$request_data['trx'])->first(); 
        if(!$temData) {
            $message = ['error' => [__('Transaction faild. Record didn\'t saved properly. Please try again')]];
             ApiResponse::error($message);
        }
        return view('user.my-escrow.razorpay-payment-api',compact('temData','request_data'));
    }
    //api payment success 
    public function apiPaymentSuccess(Request $request, $gateway = null, $trx = null) {
        try{
            $identifier = $trx;
            $tempData   = TemporaryData::where("identifier",$identifier)->first();

            $creator_table = $tempData->data->creator_table ?? null;
            $creator_id = $tempData->data->creator_id ?? null;
            $creator_guard = $tempData->data->creator_guard ?? null;
            $api_authenticated_guards = PaymentGatewayConst::apiAuthenticateGuard();
            if($creator_table != null && $creator_id != null && $creator_guard != null) {
                if(!array_key_exists($creator_guard,$api_authenticated_guards)) throw new Exception(__('Request user doesn\'t save properly. Please try again'));
                $creator = DB::table($creator_table)->where("id",$creator_id)->first();
                if(!$creator) throw new Exception(__("Request user doesn\'t save properly. Please try again"));
                $api_user_login_guard = $api_authenticated_guards[$creator_guard];
                Auth::guard($api_user_login_guard)->loginUsingId($creator->id);
            }

            $this->createEscrow($tempData);
            $message = ['success' => [__("Escrow created Successful, Please Go Back Your App")]];
            return ApiResponse::onlysuccess($message); 
        }catch(Exception $e) {
            $message = ['error' => [$e->getMessage()]];
            return ApiResponse::onlyError($message); 
        }
    }
    //razor payment callback
    public function razorCallback() {
        try{
            $requestData   =request()->all(); 
            $token         = $requestData['trx'] ?? "";
            $tempData   = TemporaryData::where("identifier",$token)->first();

            $creator_table = $tempData->data->creator_table ?? null;
            $creator_id = $tempData->data->creator_id ?? null;
            $creator_guard = $tempData->data->creator_guard ?? null;
            $api_authenticated_guards = PaymentGatewayConst::apiAuthenticateGuard();
            if($creator_table != null && $creator_id != null && $creator_guard != null) {
                if(!array_key_exists($creator_guard,$api_authenticated_guards)) throw new Exception(__('Request user doesn\'t save properly. Please try again'));
                $creator = DB::table($creator_table)->where("id",$creator_id)->first();
                if(!$creator) throw new Exception(__("Request user doesn\'t save properly. Please try again"));
                $api_user_login_guard = $api_authenticated_guards[$creator_guard];
                Auth::guard($api_user_login_guard)->loginUsingId($creator->id);
            }

            $this->createEscrow($tempData);
            $message = ['success' => [__("Escrow created Successful, Please Go Back Your App")]];
            return ApiResponse::onlysuccess($message); 
        }catch(Exception $e) {
            $message = ['error' => [$e->getMessage()]];
            return ApiResponse::onlyError($message); 
        }
    }
    //stripe payment success 
    public function stripePaymentSuccess(Request $request, $gateway = null, $trx = null){
        try{
            $identifier = $trx;
            $tempData   = TemporaryData::where("identifier",$identifier)->first();
            $this->createEscrow($tempData);
            $message = ['success' => [__("Escrow created Successful, Please Go Back Your App")]];
            return ApiResponse::onlysuccess($message);  
        }catch(Exception $e) {
            $message = ['error' => [$e->getMessage()]];
            return ApiResponse::onlyError($message); 
        }
    }
    //flutter wave payment success 
    public function flutterwaveCallback(Request $request, $gateway = null, $trx = null){
        $status = request()->status;  
        //if payment is successful
        if ($status ==  'successful' || $status ==  'completed') { 
            try{
                $identifier = $trx;
                $tempData   = TemporaryData::where("identifier",$identifier)->first();
                $this->createEscrow($tempData);
                $message = ['success' => [__("Escrow created Successful, Please Go Back Your App")]];
                return ApiResponse::onlysuccess($message);  
            }catch(Exception $e) {
                $message = ['error' => [$e->getMessage()]];
                return ApiResponse::onlyError($message); 
            }
        }else{
            $message = ['error' => [__("Escrow Create Cancel")]];
            return ApiResponse::onlyError($message); 
        }
    }
    //escrow sslcommerz data insert
    public function successEscrowSslcommerz(Request $request) {  
        $tempData = TemporaryData::where("identifier",$request->tran_id)->first();

        $creator_table = $tempData->data->creator_table ?? null;
        $creator_id = $tempData->data->creator_id ?? null;
        $creator_guard = $tempData->data->creator_guard ?? null;
        $api_authenticated_guards = PaymentGatewayConst::apiAuthenticateGuard();
        if($creator_table != null && $creator_id != null && $creator_guard != null) {
            if(!array_key_exists($creator_guard,$api_authenticated_guards)) throw new Exception(__('Request user doesn\'t save properly. Please try again'));
            $creator = DB::table($creator_table)->where("id",$creator_id)->first();
            if(!$creator) throw new Exception(__("Request user doesn\'t save properly. Please try again"));
            $api_user_login_guard = $api_authenticated_guards[$creator_guard];
            Auth::guard($api_user_login_guard)->loginUsingId($creator->id);
        }
        
        if( $request->status != "VALID"){ 
            $message = ['error' => [__("Escrow Create Failed")]];
            return ApiResponse::onlyError($message); 
        }
        $this->createEscrow($tempData); 
        $message = ['success' => [__("Escrow created Successful, Please Go Back Your App")]];
        return ApiResponse::onlysuccess($message);
    } 
    //qrpay payment success
    public function qrpayPaymentSuccess(Request $request, $gateway = null, $trx = null) {
        try{
            $identifier = $trx;
            $tempData   = TemporaryData::where("identifier",$identifier)->first();

            $creator_table = $tempData->data->creator_table ?? null;
            $creator_id = $tempData->data->creator_id ?? null;
            $creator_guard = $tempData->data->creator_guard ?? null;
            $api_authenticated_guards = PaymentGatewayConst::apiAuthenticateGuard();
            if($creator_table != null && $creator_id != null && $creator_guard != null) {
                if(!array_key_exists($creator_guard,$api_authenticated_guards)) throw new Exception(__('Request user doesn\'t save properly. Please try again'));
                $creator = DB::table($creator_table)->where("id",$creator_id)->first();
                if(!$creator) throw new Exception(__("Request user doesn\'t save properly. Please try again"));
                $api_user_login_guard = $api_authenticated_guards[$creator_guard];
                Auth::guard($api_user_login_guard)->loginUsingId($creator->id);
            }

            $this->createEscrow($tempData);
            $message = ['success' => [__("Escrow created Successful, Please Go Back Your App")]];
            return ApiResponse::onlysuccess($message); 
        }catch(Exception $e) {
            $message = ['error' => [$e->getMessage()]];
            return ApiResponse::onlyError($message); 
        }
    }
    //coingate payment success
    public function coingatePaymentSuccess(Request $request) {
        try{
            $identifier = $request->get('trx');
            $escrowData = Escrow::where('callback_ref',$identifier)->first();
            if($escrowData == null) {
                $tempData   = TemporaryData::where("identifier",$identifier)->first();
                $this->createEscrow($tempData,null,EscrowConstants::PAYMENT_PENDING);
            } 

            $creator_table = $tempData->data->creator_table ?? null;
            $creator_id = $tempData->data->creator_id ?? null;
            $creator_guard = $tempData->data->creator_guard ?? null;
            $api_authenticated_guards = PaymentGatewayConst::apiAuthenticateGuard(); 
            if($creator_table != null && $creator_id != null && $creator_guard != null) {
                if(!array_key_exists($creator_guard,$api_authenticated_guards)) throw new Exception(__('Request user doesn\'t save properly. Please try again'));
                $creator = DB::table($creator_table)->where("id",$creator_id)->first();
                if(!$creator) throw new Exception(__("Request user doesn\'t save properly. Please try again"));
                $api_user_login_guard = $api_authenticated_guards[$creator_guard];
                Auth::guard($api_user_login_guard)->loginUsingId($creator->id);
            }
            $message = ['success' => [__("Escrow created Successful, Please Go Back Your App")]];
            return ApiResponse::onlysuccess($message); 
        }catch(Exception $e) {
            $message = ['error' => [$e->getMessage()]];
            return ApiResponse::onlyError($message); 
        }
    }
    public function manualPaymentConfirmedApi(Request $request){
        $validator = Validator::make($request->all(), [
            'trx' => 'required',
        ]);
        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return ApiResponse::validation($error);
        }
        $trx = $request->trx;
        $hasData = TemporaryData::where('identifier', $trx)->first();
        if(!$hasData){
            $message = ['error'=>[__("Sorry, your payment information is invalid")]];
            return ApiResponse::onlyError($message); 
        }
        $gateway = PaymentGatewayModel::manual()->where('slug',PaymentGatewayConst::add_money_slug())->where('id',$hasData->data->gateway_currency->gateway->id)->first();
        $payment_fields = $gateway->input_fields ?? [];

        $validation_rules = $this->generateValidationRules($payment_fields);
        $validator2 = Validator::make($request->all(), $validation_rules);

        if ($validator2->fails()) {
            $message =  ['error' => $validator2->errors()->all()];
            return ApiResponse::onlyError($message); 
        }
        $validated = $validator2->validate();
        $get_values = $this->placeValueWithFields($payment_fields, $validated);  
        try{
            $this->createEscrow($hasData, $get_values);
            $message = ['success' => [__("Escrow created Successful, Please Go Back Your App")]];
            return ApiResponse::onlysuccess($message);
        }catch(Exception $e) {
            $message = ['error'=>[$e->getMessage()]];
            return ApiResponse::onlyError($message); 
        } 
    }
    //escrow wallet payment
    public function escrowWalletPayment($escrowTempData) {
        $sender_currency       = Currency::where('code', $escrowTempData->data->escrow->escrow_currency)->first();
        $user_wallet           = UserWallet::where(['user_id' => auth()->user()->id, 'currency_id' => $sender_currency->id])->first();
        $user_wallet->balance -= $escrowTempData->data->escrow->buyer_amount;
        $user_wallet->save();
    }
    public function paymentCancel(Request $request) {
        $token = $request->identifier ?? null;
        if($token){
            TemporaryData::where("identifier",$token)->delete();
        }
        $message = ['error'=>[__('You have canceled the payment. Please Go Back Your App')]];
        return ApiResponse::onlyError($message);  
    }
    //insert escrow data
    public function createEscrow($tempData, $additionalData = null, $setStatus = null) {
        $escrowData = $tempData->data->escrow;
        if ($setStatus == null) {
            $status = 0;
            if ($escrowData->role == "seller") {
                $status = EscrowConstants::APPROVAL_PENDING;
            }else if($escrowData->role == "buyer" && $escrowData->payment_gateway_currency_id != null){ 
                if ($tempData->data->gateway_currency->gateway->type == PaymentGatewayConst::AUTOMATIC) {
                    $status = EscrowConstants::ONGOING;
                }else if($tempData->data->gateway_currency->gateway->type == PaymentGatewayConst::MANUAL){
                    $status         = EscrowConstants::PAYMENT_PENDING;
                    $additionalData = json_encode($additionalData);
                }
            }else if($escrowData->role == "buyer" && $escrowData->payment_type == EscrowConstants::MY_WALLET){
                $status = EscrowConstants::ONGOING;
            }
        }else{
            $status = $setStatus;
        }
        DB::beginTransaction();
        try{ 
            $escrowCreate = Escrow::create([
                'user_id'                     => $escrowData->user_id,
                'escrow_category_id'          => $escrowData->escrow_category_id,
                'payment_gateway_currency_id' => $escrowData->payment_gateway_currency_id ?? null,
                'escrow_id'                   => 'EC'.getTrxNum(),
                'payment_type'                => $escrowData->payment_type,
                'role'                        => $escrowData->role,
                'who_will_pay'                => $escrowData->charge_payer,
                'buyer_or_seller_id'          => $escrowData->buyer_or_seller_id,
                'amount'                      => $escrowData->amount,
                'escrow_currency'             => $escrowData->escrow_currency,
                'title'                       => $escrowData->title,
                'remark'                      => $escrowData->remarks,
                'file'                        => json_decode($tempData->data->attachment),
                'status'                      => $status,
                'details'                     => $additionalData,
                'created_at'                  => now(),
            ]);
            EscrowDetails::create([
                'escrow_id'             => $escrowCreate->id ?? 0,
                'fee'                   => $escrowData->escrow_total_charge,
                'seller_get'            => $escrowData->seller_amount,
                'buyer_pay'             => $escrowData->buyer_amount,
                'gateway_exchange_rate' => $escrowData->gateway_exchange_rate,
                'created_at'            => now(),
            ]);
            DB::commit();
            //send user notification
            $byerOrSeller = User::findOrFail($escrowData->buyer_or_seller_id);
            try {
                $byerOrSeller->notify(new EscrowRequest($byerOrSeller,$escrowCreate));
            } catch (\Throwable $th) {
                //throw $th;
            }
            
            $notification_content = [
                'title'   => "Escrow Request",
                'message' => "A user created an escrow with you",
                'time'    => Carbon::now()->diffForHumans(),
                'image'   => files_asset_path('profile-default'),
            ];
            UserNotification::create([
                'type'    => NotificationConst::ESCROW_CREATE,
                'user_id' => $escrowData->buyer_or_seller_id,
                'message' => $notification_content,
            ]); 
            TemporaryData::where("identifier", $tempData->identifier)->delete();
        }catch(Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage());
        } 
    } 
    //escrow sslcommerz fail
    public function escrowSllCommerzFails(Request $request){ 
        $tempData = TemporaryData::where("identifier",$request->tran_id)->first();
        $creator_id    = $tempData->data->creator_id ?? null;
        $creator_guard = $tempData->data->creator_guard ?? null;
        $user          = Auth::guard($creator_guard)->loginUsingId($creator_id);
        if($request->status == "FAILED"){
            TemporaryData::destroy($tempData->id); 
            $message = ['error' => [__("Escrow Create Failed")]];
            return ApiResponse::onlyError($message); 
        }
    } 
    //escrow sslcommerz cancel
    public function escrowSllCommerzCancel(Request $request){ 
        $tempData = TemporaryData::where("identifier",$request->tran_id)->first();
        $creator_id    = $tempData->data->creator_id ?? null;
        $creator_guard = $tempData->data->creator_guard ?? null;
        $user          = Auth::guard($creator_guard)->loginUsingId($creator_id);
        if($request->status == "FAILED"){
            TemporaryData::destroy($tempData->id); 
            $message = ['error' => [__("Escrow Create Cancel")]];
            return ApiResponse::onlyError($message); 
        }
    } 
    // check user
    public function userCheck(Request $request){ 
        $getUser = User::where('status', true)->where('username', $request->userCheck)->orWhere('email',$request->userCheck)->first();
        if($getUser != null){
            if($getUser->id == auth()->user()->id){
                return ApiResponse::success(['error'=>[__('Invalid User')]], ['user_check' => false]);
            } 
            return ApiResponse::success(['success'=>[__('Valid User')]], ['user_check' => true]);
        }
        return ApiResponse::success(['error'=>[__('User not found')]], ['user_check' => false]);
    } 
    public function tatumUserTransactionRequirements($trx_type = null) {
        $requirements = [
            PaymentGatewayConst::TYPEADDMONEY => [
                [
                    'type'          => 'text',
                    'label'         =>  "Txn Hash",
                    'placeholder'   => "Enter Txn Hash",
                    'name'          => "txn_hash",
                    'required'      => true,
                    'validation'    => [
                        'min'           => "0",
                        'max'           => "250",
                        'required'      => true,
                    ]
                ]
            ],
        ];

        if($trx_type) {
            if(!array_key_exists($trx_type, $requirements)) throw new Exception("User Transaction Requirements Not Found!");
            return $requirements[$trx_type];
        }

        return $requirements;
    }
    public function cryptoPaymentAddress(Request $request, $escrow_id) {  
        $escrowData = Escrow::where('escrow_id',$escrow_id)->first();
        if($escrowData->paymentGatewayCurrency->gateway->isCrypto() && $escrowData->details?->payment_info?->receiver_address ?? false) {
            $data =[
                'escrow_data'         => $escrowData,
                'address_info'      => [
                    'coin'          => $escrowData->details?->payment_info?->currency ?? "",
                    'address'       => $escrowData->details?->payment_info?->receiver_address ?? "",
                    'input_fields'  => $this->tatumUserTransactionRequirements(PaymentGatewayConst::TYPEADDMONEY),
                    'submit_url'    => route('api.v1.my-escrow.payment.crypto.confirm',$escrow_id),
                    'method'        => "post",
                ],
                'base_url'          => url('/'),
            ];
            $message = ['success'=>[__('Crypto Information')]];
            return ApiResponse::success($message, $data);
        }

        return ApiResponse::error(['error' => ['Something went wrong! Please try again']]);
    }
    public function cryptoPaymentConfirm(Request $request, $escrow_id)
    {
        $escrowData = Escrow::where('escrow_id',$escrow_id)->first();
        
        $dy_input_fields = $escrowData->details->payment_info->requirements ?? [];
        $validation_rules = $this->generateValidationRules($dy_input_fields);
 
        $validated = [];
        if(count($validation_rules) > 0) {
            $validated = Validator::make($request->all(), $validation_rules)->validate();
        }

        if(!isset($validated['txn_hash'])) return ApiResponse::error(['error' => ['Transaction hash is required for verify']]);

        $receiver_address = $escrowData->details->payment_info->receiver_address ?? "";

        
        // check hash is valid or not
        $crypto_transaction = CryptoTransaction::where('txn_hash', $validated['txn_hash'])
                                                ->where('receiver_address', $receiver_address)
                                                ->where('asset',$escrowData->paymentGatewayCurrency->currency_code)
                                                ->where(function($query) {
                                                    return $query->where('transaction_type',"Native")
                                                                ->orWhere('transaction_type', "native");
                                                })
                                                ->where('status',PaymentGatewayConst::NOT_USED)
                                                ->first();
       
        if(!$crypto_transaction) return ApiResponse::error(['error' => ['Transaction hash is not valid! Please input a valid hash']]);

        if($crypto_transaction->amount >= $escrowData->escrowDetails->buyer_pay == false) {
            if(!$crypto_transaction) return ApiResponse::error(['error' => ['Insufficient amount added. Please contact with system administrator']]);
        }

        DB::beginTransaction();
        try{

            // update crypto transaction as used
            DB::table($crypto_transaction->getTable())->where('id', $crypto_transaction->id)->update([
                'status'        => PaymentGatewayConst::USED,
            ]);

            // update transaction status
            $transaction_details = json_decode(json_encode($escrowData->details), true);
            $transaction_details['payment_info']['txn_hash'] = $validated['txn_hash'];

            DB::table($escrowData->getTable())->where('id', $escrowData->id)->update([
                'details'       => $transaction_details,
                'status'        => EscrowConstants::ONGOING,
                'payment_type'        => EscrowConstants::GATEWAY,
            ]);

            DB::commit();

        }catch(Exception $e) { 
            DB::rollback();
            return ApiResponse::error(['error' => ['Something went wrong! Please try again']]);
        }

        return ApiResponse::onlySuccess(['error' => ['Payment Confirmation Success!']]);
    }
    /**
     * Redirect Users for collecting payment via Button Pay (JS Checkout)
     */
    public function redirectBtnPay(Request $request, $gateway)
    { 
        try{ 
            return EscrowPaymentGateway::init([])->handleBtnPay($gateway, $request->all());
        }catch(Exception $e) { 
            return redirect()->route('user.my-escrow.index')->with(['error' => [$e->getMessage()]]);
        }
    }
    public function escrowPaymentSuccessRazorpayPost(Request $request, $gateway) {
        try{
            $identifier = $request->token ;
            $tempData   = TemporaryData::where("identifier",$identifier)->first();
            $this->createEscrow($tempData);
        }catch(Exception $e) {
            return back()->with(['error' => [$e->getMessage()]]);
        }
        $message = ['success' => [__("Escrow created Successful, Please Go Back Your App")]];
        return ApiResponse::onlysuccess($message);
    }
}
