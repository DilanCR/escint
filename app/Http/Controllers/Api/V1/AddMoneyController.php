<?php

namespace App\Http\Controllers\Api\V1;
 
use Exception;
use App\Models\UserWallet;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\TemporaryData;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use App\Models\Admin\PaymentGateway;
use Illuminate\Support\Facades\Auth;
use App\Traits\PaymentGateway\Manual;
use App\Constants\PaymentGatewayConst; 
use App\Models\Admin\CryptoTransaction;
use Illuminate\Support\Facades\Validator;
use App\Models\Admin\PaymentGatewayCurrency;
use App\Traits\PaymentGateway\SslcommerzTrait;
use App\Http\Helpers\Api\Helpers as ApiResponse;
use KingFlamez\Rave\Facades\Rave as Flutterwave;
use App\Http\Helpers\PaymentGateway as PaymentGatewayHelper;

class AddMoneyController extends Controller
{
    use Manual, SslcommerzTrait;
    public function index(){
        $user = auth()->user();
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
        //add money transactions 
        $transactions = Transaction::where('user_id',auth()->user()->id)->addMoney()->latest()->take(5)->get()->map(function($item){
            return[
                'id'                    => $item->id,
                'trx_id'                => $item->trx_id,
                'gateway_currency'      => $item->gateway_currency->name,
                'transaction_type'      => $item->type,
                'sender_request_amount' => $item->sender_request_amount,
                'sender_currency_code'  => $item->sender_currency_code,
                'total_payable'         => $item->total_payable,
                'gateway_currency_code' => $item->gateway_currency->currency_code,
                'exchange_rate'         => $item->exchange_rate,
                'fee'                   => $item->transaction_details->total_charge,
                'rejection_reason'      => $item->reject_reason ?? null,
                'created_at'            => $item->created_at,
            ];
        });
        $data =[
            'base_curr'         => get_default_currency_code(),
            'base_curr_rate'    => get_amount(1),
            'default_image'     => "public/backend/images/default/default.webp",
            'image_path'        => "public/backend/images/payment-gateways",
            'base_url'          => url('/'),
            'userWallet'        => (object)$userWallet,
            'gatewayCurrencies' => $gatewayCurrencies,
            'transactionss'     => $transactions,
        ];
        $message = ['success'=>[__('Add Money Information')]];
        return ApiResponse::success($message, $data);
    }
    //add money submit 
    public function submit(Request $request){ 
        try{  
            $instance = PaymentGatewayHelper::init($request->all())->gateway()->api()->get();   
            $trx = $instance['response']['id']??$instance['response']['trx']??$instance['response']['reference_id']??$instance['response']['tokenValue']?? $instance['response']['url'] ?? $instance['response']['temp_identifier']??$instance['order_id']??$instance['response']??"";
           
            $temData = TemporaryData::where('identifier',$trx)->first(); 
            
            if(!$temData){
                $error = ['error'=>["Invalid Request"]];
                return ApiResponse::onlyError($error);
            }
            $payment_gateway_currency = PaymentGatewayCurrency::where('id', $temData->data->currency)->first();
            $payment_gateway          = PaymentGateway::where('id', $temData->data->gateway)->first();
            $payment_informations = [
                'trx'                   => $temData->identifier,
                'gateway_currency_name' => $payment_gateway_currency->name,
                'request_amount'        => get_amount($temData->data->amount->requested_amount,$temData->data->amount->sender_currency),
                'exchange_rate'         => "1".' '.$temData->data->amount->sender_currency.' = '.get_amount($temData->data->amount->exchange_rate,$temData->data->amount->gateway_cur_code),
                'total_charge'          => get_amount($temData->data->amount->gateway_total_charge,$temData->data->amount->gateway_cur_code),
                'will_get'              => get_amount($temData->data->amount->requested_amount,$temData->data->amount->sender_currency),
                'payable_amount'        => get_amount($temData->data->amount->total_payable_amount,$temData->data->amount->gateway_cur_code),
           ];

            if($payment_gateway->type == "AUTOMATIC") {
                if($temData->type == PaymentGatewayConst::STRIPE) {
                 $data =[
                     'gateway_type'          => $payment_gateway->type,
                     'gateway_currency_name' => $payment_gateway_currency->name,
                     'alias'                 => $payment_gateway_currency->alias,
                     'identify'              => $temData->type,
                     'payment_informations'  => $payment_informations,
                     'url'                   => @$temData->data->response->link."?prefilled_email=".@auth()->user()->email,
                     'method'                => "get",
                 ];
                 $message = ['success'=>[__('Add Money Inserted Successfully')]];
                 return ApiResponse::success($message, $data);
                }else if($temData->type == PaymentGatewayConst::PAYPAL) {
                    $data =[
                         'gategay_type'          => $payment_gateway->type,
                         'gateway_currency_name' => $payment_gateway_currency->name,
                         'alias'                 => $payment_gateway_currency->alias,
                         'identify'              => $temData->type,
                         'payment_informations'  => $payment_informations,
                         'url'                   => @$temData->data->response->links,
                         'method'                => "get",
                    ];
                    $message = ['success'=>[__('Add Money Inserted Successfully')]];
                    return ApiResponse::success($message, $data);
 
                }else if($temData->type == PaymentGatewayConst::FLUTTER_WAVE) {
                    $data =[
                        'gateway_type'          => $payment_gateway->type,
                        'gateway_currency_name' => $payment_gateway_currency->name,
                        'alias'                 => $payment_gateway_currency->alias,
                        'identify'              => $temData->type,
                        'payment_informations'  => $payment_informations,
                        'url'                   => @$temData->data->response->link,
                        'method'                => "get",
                    ];
                    $message = ['success'=>[__('Add Money Inserted Successfully')]];
                    return ApiResponse::success($message, $data);
                }else if($temData->type == PaymentGatewayConst::SSLCOMMERZ) {
                    $data =[
                        'gateway_type'          => $payment_gateway->type,
                        'gateway_currency_name' => $payment_gateway_currency->name,
                        'alias'                 => $payment_gateway_currency->alias,
                        'identify'              => $temData->type,
                        'payment_informations'  => $payment_informations,
                        'url'                   => @$temData->data->response->link,
                        'method'                => "get",
                    ];
                    $message = ['success'=>[__('Add Money Inserted Successfully')]];
                    return ApiResponse::success($message, $data);
                }else if($temData->type == PaymentGatewayConst::RAZORPAY) {
                    $data =[
                        'gateway_type'          => $payment_gateway->type,
                        'gateway_currency_name' => $payment_gateway_currency->name,
                        'alias'                 => $payment_gateway_currency->alias,
                        'identify'              => $temData->type,
                        'payment_informations'  => $payment_informations,
                        'url'                   => $instance['response']['redirect_url'],
                        'method'                => "get",
                    ];
                    $message = ['success'=>[__('Add Money Inserted Successfully')]];
                    return ApiResponse::success($message, $data);
                }else if($temData->type == PaymentGatewayConst::QRPAY) {
                    $data =[
                        'gateway_type'          => $payment_gateway->type,
                        'gateway_currency_name' => $payment_gateway_currency->name,
                        'alias'                 => $payment_gateway_currency->alias,
                        'identify'              => $temData->type,
                        'payment_informations'  => $payment_informations,
                        'url'                   => @$instance['response']['link'],
                        'method'                => "get",
                    ];
                    $message = ['success'=>[__('Add Money Inserted Successfully')]];
                    return ApiResponse::success($message, $data);
                }else if($temData->type == PaymentGatewayConst::PAGADITO) {
                    $data =[
                        'gateway_type'          => $payment_gateway->type,
                        'gateway_currency_name' => $payment_gateway_currency->name,
                        'alias'                 => $payment_gateway_currency->alias,
                        'identify'              => $temData->type,
                        'payment_informations'  => $payment_informations,
                        'url'                   => @$temData->data->response->value,
                        'method'                => "get",
                    ];
                    $message = ['success'=>[__('Add Money Inserted Successfully')]];
                    return ApiResponse::success($message, $data);
                }else if($temData->type == PaymentGatewayConst::COINGATE) {
                    $data =[
                        'gateway_type'          => $payment_gateway->type,
                        'gateway_currency_name' => $payment_gateway_currency->name,
                        'alias'                 => $payment_gateway_currency->alias,
                        'identify'              => $temData->type,
                        'payment_informations'  => $payment_informations,
                        'url'                   => @$instance['response']['link'],
                        'method'                => "get",
                    ];
                    $message = ['success'=>[__('Add Money Inserted Successfully')]];
                    return ApiResponse::success($message, $data);
                }else if($temData->type == 'perfectmoney') {
                    $data =[
                        'gateway_type'          => $payment_gateway->type,
                        'gateway_currency_name' => $payment_gateway_currency->name,
                        'alias'                 => $payment_gateway_currency->alias,
                        'identify'              => $temData->type,
                        'payment_informations'  => $payment_informations,
                        'url'                   => @$instance['response']['link'],
                        'method'                => "get",
                    ];
                    $message = ['success'=>[__('Add Money Inserted Successfully')]];
                    return ApiResponse::success($message, $data);
                }elseif($temData->type == PaymentGatewayConst::TATUM) { 
                    $data =[
                        'gateway_type'          => $payment_gateway->type,
                        'gateway_currency_name' => $payment_gateway_currency->name,
                        'alias'                 => $payment_gateway_currency->alias,
                        'identify'              => $temData->type,
                        'payment_informations'  => $payment_informations,  
                        'action_type'           => $instance['response']['type']  ?? false,
                        'address_info'          => $instance['response']['address_info'] ?? [],
                        'url'                   => $instance['response']['redirect_url'],
                        'method'                => "get",
                    ];
                    $message =  ['success'=>['Add Money Inserted Successfully']];
                    return ApiResponse::success($message,$data);
                }elseif($temData->type == PaymentGatewayConst::PAYSTACK) { 
                    $data =[
                        'gateway_type'          => $payment_gateway->type,
                        'gateway_currency_name' => $payment_gateway_currency->name,
                        'alias'                 => $payment_gateway_currency->alias,
                        'identify'              => $temData->type,
                        'payment_informations'  => $payment_informations,  
                        'action_type'           => $instance['response']['type']  ?? false,
                        'address_info'          => $instance['response']['address_info'] ?? [],
                        'url'                   => $instance['response']['link'],
                        'method'                => "get",
                    ];
                    $message =  ['success'=>['Add Money Inserted Successfully']];
                    return ApiResponse::success($message,$data);
                }
            }elseif($payment_gateway->type == "MANUAL"){
                    $data =[
                        'gategay_type'          => $payment_gateway->type,
                        'gateway_currency_name' => $payment_gateway_currency->name,
                        'alias'                 => $payment_gateway_currency->alias,
                        'identify'              => $temData->type,
                        'details'               => strip_tags($payment_gateway->desc) ??null,
                        'input_fields'          => $payment_gateway->input_fields??null,
                        'payment_informations'  => $payment_informations,
                        'url'                   => route('api.v1.user.add-money.manual.payment.confirmed'),
                        'method'                => "post",
                        ];
                        $message = ['success'=>[__('Add Money Inserted Successfully')]];
                        return ApiResponse::success($message, $data);
            }else{
                $error = ['error'=>[__("Something is wrong")]];
                return ApiResponse::onlyError($error);
            }
 
        }catch(Exception $e) {
            $error = ['error'=>[$e->getMessage()]];
            return ApiResponse::onlyError($error);
        }
    }
    //api payment success 
    public function apiPaymentSuccess(Request $request, $gateway)
    {
        $requestData   = $request->all();
        $token         = $requestData['token'] ?? "";
        $checkTempData = TemporaryData::where("type", $gateway)->where("identifier", $token)->first();
        if (!$checkTempData){
            $message = ['error' => [__("Transaction failed. Record didn\'t saved properly. Please try again")]];
            return ApiResponse::onlyError($message);
        }
 
        $checkTempData = $checkTempData->toArray();
        try {
            PaymentGatewayHelper::init($checkTempData)->type(PaymentGatewayConst::TYPEADDMONEY)->responseReceiveApi();
        } catch (Exception $e) {
            $message = ['error' => [$e->getMessage()]];
            return ApiResponse::onlyError($message);
        }
        $message = ['success' => [__("Payment successful, please go back your app")]];
        return ApiResponse::onlySuccess($message);
    }
    public function postSuccess(Request $request, $gateway)
    {
        try{
            $token = PaymentGatewayHelper::getToken($request->all(),$gateway);
            $temp_data = TemporaryData::where("identifier",$token)->first();
            if($temp_data && $temp_data->data->creator_guard != 'api') {
                Auth::guard($temp_data->data->creator_guard)->loginUsingId($temp_data->data->creator_id);
            }
        }catch(Exception $e) {
            $message = ['error' => [$e->getMessage()]];
            return ApiResponse::onlyError($message);
        }

        return $this->successGlobal($request, $gateway);
    }

    public function postCancel(Request $request, $gateway)
    {
        try{
            $token = PaymentGatewayHelper::getToken($request->all(),$gateway);
            $temp_data = TemporaryData::where("identifier",$token)->first();
            if($temp_data && $temp_data->data->creator_guard != 'api') {
                Auth::guard($temp_data->data->creator_guard)->loginUsingId($temp_data->data->creator_id);
            }
        }catch(Exception $e) {
            $message = ['error' => [$e->getMessage()]];
            return ApiResponse::onlyError($message);
        }

        return $this->cancel($request, $gateway);
    }
      //stripe success
    public function stripePaymentSuccess($trx){
        $token = $trx;
        $checkTempData = TemporaryData::where("type",PaymentGatewayConst::STRIPE)->where("identifier",$token)->first();
        $message       = ['error' => [__('Transaction Failed. Record didn\'t saved properly. Please try again')]];

        if(!$checkTempData) return ApiResponse::error($message);
        $checkTempData = $checkTempData->toArray();
        
        $creator_table = $checkTempData['data']->creator_table ?? null;
        $creator_id = $checkTempData['data']->creator_id ?? null;
        $creator_guard = $checkTempData['data']->creator_guard ?? null;
        $api_authenticated_guards = PaymentGatewayConst::apiAuthenticateGuard();
        if($creator_table != null && $creator_id != null && $creator_guard != null) {
            if(!array_key_exists($creator_guard,$api_authenticated_guards)) throw new Exception(__('Request user doesn\'t save properly. Please try again'));
            $creator = DB::table($creator_table)->where("id",$creator_id)->first();
            if(!$creator) throw new Exception(__("Request user doesn\'t save properly. Please try again"));
            $api_user_login_guard = $api_authenticated_guards[$creator_guard];
            Auth::guard($api_user_login_guard)->loginUsingId($creator->id);
        }

        try{
            PaymentGatewayHelper::init($checkTempData)->type(PaymentGatewayConst::TYPEADDMONEY)->responseReceiveApi('stripe');
        }catch(Exception $e) {
            $message = ['error' => ["Something Is Wrong..."]];
            ApiResponse::error($message);
        }
        $message = ['success' => [__("Payment Successful, Please Go Back Your App")]];
        return ApiResponse::onlysuccess($message);

    }
      //flutter wave paynebt syccess
    public function flutterWavePaymentSuccess(){
        $status = request()->status; 
        if ($status ==  'successful' || $status ==  'completed') {
 
            $transactionID = Flutterwave::getTransactionIDFromCallback();
            $data          = Flutterwave::verifyTransaction($transactionID);
 
            $requestData = request()->tx_ref;
 
            $token = $requestData;
 
            $checkTempData = TemporaryData::where("type",'flutterwave')->where("identifier",$token)->first();
 
            $message = ['error' => [__('Transaction faild. Record didn\'t saved properly. Please try again')]];
 
            if(!$checkTempData) return ApiResponse::error($message);
 
            $checkTempData = $checkTempData->toArray();
            try{
                 PaymentGatewayHelper::init($checkTempData)->type(PaymentGatewayConst::TYPEADDMONEY)->responseReceive('flutterWave');
            }catch(Exception $e) {
                 $message = ['error' => [$e->getMessage()]];
                 ApiResponse::error($message);
            }
             $message = ['success' => [__("Payment successful, Please Go Back Your App")]];
             return ApiResponse::onlySuccess($message);
        }
        elseif ($status ==  'cancelled'){
             $message = ['error' => ['Payment Cancelled']];
             ApiResponse::error($message);
        }
        else{
             $message = ['error' => ['Payment Failed']];
             ApiResponse::error($message);
        }
    }
    //razor payment link create 
    public function razorPaymentLink($trx){ 
        $temData = TemporaryData::where('identifier',$trx)->first(); 
        if(!$temData) {
            $message = ['error' => [__('Transaction faild. Record didn\'t saved properly. Please try again')]];
             ApiResponse::error($message);
        }
        return view('user.sections.add-money.automatic.razor-api',compact('temData'));
    }
    //razor pay callback  
    public function razorCallback(){
        $request_data = request()->all();
        //if payment is successful
        $token = $request_data['razorpay_order_id']; 
        $checkTempData = TemporaryData::where("type",PaymentGatewayConst::RAZORPAY)->where("identifier",$token)->first();
        $message       = ['error' => [__('Transaction Failed. Record didn\'t saved properly. Please try again')]];

        if(!$checkTempData) return ApiResponse::error($message);
        $checkTempData = $checkTempData->toArray();
        
        $creator_table = $checkTempData['data']->creator_table ?? null;
        $creator_id = $checkTempData['data']->creator_id ?? null;
        $creator_guard = $checkTempData['data']->creator_guard ?? null;
        $api_authenticated_guards = PaymentGatewayConst::apiAuthenticateGuard();
        if($creator_table != null && $creator_id != null && $creator_guard != null) {
            if(!array_key_exists($creator_guard,$api_authenticated_guards)) throw new Exception(__('Request user doesn\'t save properly. Please try again'));
            $creator = DB::table($creator_table)->where("id",$creator_id)->first();
            if(!$creator) throw new Exception(__("Request user doesn\'t save properly. Please try again"));
            $api_user_login_guard = $api_authenticated_guards[$creator_guard];
            Auth::guard($api_user_login_guard)->loginUsingId($creator->id);
        }

        try{
            PaymentGatewayHelper::init($checkTempData)->type(PaymentGatewayConst::TYPEADDMONEY)->responseReceiveApi('razorpay');
        }catch(Exception $e) {
            $message = ['error' => [__("Something Is Wrong")]];
            ApiResponse::error($message);
        }
        $message = ['success' => [__("Payment Successful, Please Go Back Your App")]];
        return ApiResponse::onlysuccess($message);

    }
    // Qrpay Call Back
    public function qrpayCallback(Request $request)
    { 
        if ($request->type ==  'success') {

            $requestData = $request->all();

            $checkTempData = TemporaryData::where("type", 'qrpay')->where("identifier", $requestData['data']['custom'])->first();

            if (!$checkTempData) return redirect()->route('user.add.money.index')->with(['error' => ['Transaction faild. Record didn\'t saved properly. Please try again.']]);

            $checkTempData = $checkTempData->toArray();

            try { 
                PaymentGatewayHelper::init($checkTempData)->type(PaymentGatewayConst::TYPEADDMONEY)->responseReceiveApi('qrpay');
            } catch (Exception $e) { 
                ApiResponse::error($e->getMessage());
            }
            $message = ['success' => [__("Payment Successful, Please Go Back Your App")]];
            return ApiResponse::onlysuccess($message);
        } else {
            ApiResponse::error('Transaction failed'); 
        }
    }
    public function coinGateSuccess(Request $request, $gateway){
        try{
            $token = $request->token;
            $checkTempData = TemporaryData::where("type",PaymentGatewayConst::COINGATE)->where("identifier",$token)->first();
            if(Transaction::where('callback_ref', $token)->exists()) {
                if(!$checkTempData){
                    $message = ['error' => ["Transaction request sended successfully!"]];
                    return ApiResponse::error($message);
                }
            }else {
                if(!$checkTempData){
                    $message = ['error' => ["Transaction failed. Record didn\'t saved properly. Please try again."]];
                    return ApiResponse::error($message);
                }
            }
            $update_temp_data = json_decode(json_encode($checkTempData->data),true);
            $update_temp_data['callback_data']  = $request->all();
            $checkTempData->update([
                'data'  => $update_temp_data,
            ]);
            $temp_data = $checkTempData->toArray();
            PaymentGatewayHelper::init($temp_data)->type(PaymentGatewayConst::TYPEADDMONEY)->responseReceiveApi('coingate');
        }catch(Exception $e) {
            $message = ['error' => [$e->getMessage()]];
            return ApiResponse::error($message);
        }
        $message = ['success' => ["Add Money Successful, Please Go Back Your App"]];
        return ApiResponse::onlySuccess($message);
    }
    public function paystactSuccess(Request $request, $gateway){
        try{ 
            $token = $request->trxref;
            $checkTempData = TemporaryData::where("type",PaymentGatewayConst::PAYSTACK)->where("identifier",$token)->first();
            if(!$checkTempData){
                $message = ['error' => ["Add Money Successful, Please Go Back Your App"]];
                return ApiResponse::onlySuccess($message);
            }
            $update_temp_data = json_decode(json_encode($checkTempData->data),true);
            $update_temp_data['callback_data']  = $request->all();
            $checkTempData->update([
                'data'  => $update_temp_data,
            ]);
            $temp_data = $checkTempData->toArray();
            PaymentGatewayHelper::init($temp_data)->type(PaymentGatewayConst::TYPEADDMONEY)->responseReceiveApi('paystack');
        }catch(Exception $e) {
            $message = ['error' => [$e->getMessage()]];
            return ApiResponse::error($message);
        }
        $message = ['success' => ["Add Money Successful, Please Go Back Your App"]];
        return ApiResponse::onlySuccess($message);
    }
      //sslcommerz success
      public function sllCommerzSuccess(Request $request){
        $data = $request->all();
        $token = $data['tran_id'];
        $checkTempData = TemporaryData::where("type",PaymentGatewayConst::SSLCOMMERZ)->where("identifier",$token)->first();
        $message = ['error' => [__('Transaction Failed. Record didn\'t saved properly. Please try again')]];
        if(!$checkTempData) return ApiResponse::error($message);
        $checkTempData = $checkTempData->toArray();

        $creator_table = $checkTempData['data']->creator_table ?? null;
        $creator_id = $checkTempData['data']->creator_id ?? null;
        $creator_guard = $checkTempData['data']->creator_guard ?? null;
        $api_authenticated_guards = PaymentGatewayConst::apiAuthenticateGuard();
        if($creator_table != null && $creator_id != null && $creator_guard != null) {
            if(!array_key_exists($creator_guard,$api_authenticated_guards)) throw new Exception(__('Request user doesn\'t save properly. Please try again'));
            $creator = DB::table($creator_table)->where("id",$creator_id)->first();
            if(!$creator) throw new Exception(__("Request user doesn\'t save properly. Please try again"));
            $api_user_login_guard = $api_authenticated_guards[$creator_guard];
            Auth::guard($api_user_login_guard)->loginUsingId($creator->id);
        }
        if( $data['status'] != "VALID"){
            $message = ['error' => ["Added Money Failed"]];
            return ApiResponse::error($message);
        }
        try{
            PaymentGatewayHelper::init($checkTempData)->type(PaymentGatewayConst::TYPEADDMONEY)->responseReceiveApi('sslcommerz');
        }catch(Exception $e) { 
            $message = ['error' => [$e->getMessage()]];
            return ApiResponse::error($message);
        }
        $message = ['success' => [__("Payment Successful, Please Go Back Your App")]];
        return ApiResponse::onlysuccess($message);
    }
    //sslCommerz fails
    public function sllCommerzFails(Request $request){
        $data = $request->all(); 
        $token = $data['tran_id'];
        $checkTempData = TemporaryData::where("type",PaymentGatewayConst::SSLCOMMERZ)->where("identifier",$token)->first();
        $message = ['error' => [__('Transaction Failed. Record didn\'t saved properly. Please try again')]];
        if(!$checkTempData) return ApiResponse::error($message);
        $checkTempData = $checkTempData->toArray();

        $creator_table = $checkTempData['data']->creator_table ?? null;
        $creator_id = $checkTempData['data']->creator_id ?? null;
        $creator_guard = $checkTempData['data']->creator_guard ?? null;

        $api_authenticated_guards = PaymentGatewayConst::apiAuthenticateGuard();
        if($creator_table != null && $creator_id != null && $creator_guard != null) {
            if(!array_key_exists($creator_guard,$api_authenticated_guards)) throw new Exception(__('Request user doesn\'t save properly. Please try again'));
            $creator = DB::table($creator_table)->where("id",$creator_id)->first();
            if(!$creator) throw new Exception(__("Request user doesn\'t save properly. Please try again"));
            $api_user_login_guard = $api_authenticated_guards[$creator_guard];
            Auth::guard($api_user_login_guard)->loginUsingId($creator->id);
        }
        if( $data['status'] == "FAILED"){
            TemporaryData::destroy($checkTempData['id']);
            $message = ['error' => ["Added Money Failed"]];
            return ApiResponse::error($message);
        }

    }
    //sslCommerz canceled
    public function sllCommerzCancel(Request $request){
        $data = $request->all();
        $token = $data['tran_id'];
        $checkTempData = TemporaryData::where("type",PaymentGatewayConst::SSLCOMMERZ)->where("identifier",$token)->first();
        $message = ['error' => [__('Transaction Failed. Record didn\'t saved properly. Please try again')]];
        if(!$checkTempData) return ApiResponse::error($message);
        $checkTempData = $checkTempData->toArray();


        $creator_table = $checkTempData['data']->creator_table ?? null;
        $creator_id = $checkTempData['data']->creator_id ?? null;
        $creator_guard = $checkTempData['data']->creator_guard ?? null;

        $api_authenticated_guards = PaymentGatewayConst::apiAuthenticateGuard();
        if($creator_table != null && $creator_id != null && $creator_guard != null) {
            if(!array_key_exists($creator_guard,$api_authenticated_guards)) throw new Exception(__('Request user doesn\'t save properly. Please try again'));
            $creator = DB::table($creator_table)->where("id",$creator_id)->first();
            if(!$creator) throw new Exception(__("Request user doesn\'t save properly. Please try again"));
            $api_user_login_guard = $api_authenticated_guards[$creator_guard];
            Auth::guard($api_user_login_guard)->loginUsingId($creator->id);
        }
        if( $data['status'] != "VALID"){
            TemporaryData::destroy($checkTempData['id']);
            $message = ['error' => [__("Added Money Canceled")]];
            return ApiResponse::error($message);
        }
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
    public function cryptoPaymentAddress(Request $request, $trx_id) { 
        $transaction = Transaction::where('trx_id', $trx_id)->first();
        $transactionData = [
            'id'                    => $transaction->id,
            'trx_id'                => $transaction->trx_id,
            'gateway_currency'      => $transaction->gateway_currency->name,
            'transaction_type'      => $transaction->type,
            'sender_request_amount' => $transaction->sender_request_amount,
            'sender_currency_code'  => $transaction->sender_currency_code,
            'total_payable'         => $transaction->total_payable,
            'gateway_currency_code' => $transaction->gateway_currency->currency_code,
            'exchange_rate'         => $transaction->exchange_rate,
            'fee'                   => $transaction->transaction_details->total_charge,
            'rejection_reason'      => $transaction->reject_reason ?? null,
            'created_at'            => $transaction->created_at,
        ];
        if($transaction->gateway_currency->gateway->isCrypto() && $transaction->details?->payment_info?->receiver_address ?? false) {
            $data =[
                'transaction'         => $transactionData,
                'address_info'      => [
                    'coin'          => $transaction->details?->payment_info?->currency ?? "",
                    'address'       => $transaction->details?->payment_info?->receiver_address ?? "",
                    'input_fields'  => $this->tatumUserTransactionRequirements(PaymentGatewayConst::TYPEADDMONEY),
                    'submit_url'    => route('api.v1.add-money.payment.crypto.confirm',$trx_id),
                    'method'        => "post",
                ],
                'base_url'          => url('/'),
            ];
            $message = ['success'=>[__('Add Money Information')]];
            return ApiResponse::success($message, $data);
        }

        return ApiResponse::error(['error' => ['Something went wrong! Please try again']]);
    }
    public function cryptoPaymentConfirm(Request $request, $trx_id)
    {
        $transaction = Transaction::where('trx_id',$trx_id)->where('status', PaymentGatewayConst::STATUSWAITING)->firstOrFail();

        $dy_input_fields = $transaction->details->payment_info->requirements ?? [];
        $validation_rules = $this->generateValidationRules($dy_input_fields);

        $validated = [];
        if(count($validation_rules) > 0) {
            $validated = Validator::make($request->all(), $validation_rules)->validate();
        }

        if(!isset($validated['txn_hash'])) return ApiResponse::error(['error' => ['Transaction hash is required for verify']]);

        $receiver_address = $transaction->details->payment_info->receiver_address ?? "";

        // check hash is valid or not
        $crypto_transaction = CryptoTransaction::where('txn_hash', $validated['txn_hash'])
                                                ->where('receiver_address', $receiver_address)
                                                ->where('asset',$transaction->gateway_currency->currency_code)
                                                ->where(function($query) {
                                                    return $query->where('transaction_type',"Native")
                                                                ->orWhere('transaction_type', "native");
                                                })
                                                ->where('status',PaymentGatewayConst::NOT_USED)
                                                ->first();

        if(!$crypto_transaction) return ApiResponse::error(['error' => ['Transaction hash is not valid! Please input a valid hash']]);

        if($crypto_transaction->amount >= $transaction->total_payable == false) {
            if(!$crypto_transaction) ApiResponse::error(['error' => ['Insufficient amount added. Please contact with system administrator']]);
        }

        DB::beginTransaction();
        try{

            // Update user wallet balance
            DB::table($transaction->user_wallet->getTable())
                ->where('id',$transaction->user_wallet->id)
                ->increment('balance',$transaction->request_amount);

            // update crypto transaction as used
            DB::table($crypto_transaction->getTable())->where('id', $crypto_transaction->id)->update([
                'status'        => PaymentGatewayConst::USED,
            ]);

            // update transaction status
            $transaction_details = json_decode(json_encode($transaction->details), true);
            $transaction_details['payment_info']['txn_hash'] = $validated['txn_hash'];

            DB::table($transaction->getTable())->where('id', $transaction->id)->update([
                'details'       => json_encode($transaction_details),
                'status'        => PaymentGatewayConst::STATUSSUCCESS,
            ]);

            DB::commit();

        }catch(Exception $e) {
            DB::rollback();
            return ApiResponse::error(['error' => ['Something went wrong! Please try again']]);
        }

        return ApiResponse::onlySuccess(['error' => ['Payment Confirmation Success!']]);
    }
    public function redirectBtnPay(Request $request, $gateway)
    { 
        try{
            return PaymentGatewayHelper::init([])->handleBtnPay($gateway, $request->all());
        }catch(Exception $e) { 
            $message = ['error' => [$e->getMessage()]];
            return ApiResponse::error($message);
        }
    }
    public function successGlobal(Request $request, $gateway){
        try{
            $token = PaymentGatewayHelper::getToken($request->all(),$gateway);
            $temp_data = TemporaryData::where("identifier",$token)->first();

            if(!$temp_data) {
                if(Transaction::where('callback_ref',$token)->exists()) {
                    $message = ['error' => [__('Transaction request sended successfully!')]];
                    return ApiResponse::error($message);
                }else {
                    $message = ['error' => [__('Transaction failed. Record didn\'t saved properly. Please try again')]];
                    return ApiResponse::error($message);
                }
            }

            $update_temp_data = json_decode(json_encode($temp_data->data),true);
            $update_temp_data['callback_data']  = $request->all();
            $temp_data->update([
                'data'  => $update_temp_data,
            ]);
            $temp_data = $temp_data->toArray();
            $instance = PaymentGatewayHelper::init($temp_data)->type(PaymentGatewayConst::TYPEADDMONEY)->responseReceive($temp_data['type']);

            // return $instance;
        }catch(Exception $e) { 
            $message = ['error' => [$e->getMessage()]];
            return ApiResponse::error($message);
        }
        $message = ['success' => [__('Successfully Added Money')]];
        return ApiResponse::onlysuccess($message);
    }
}
