<?php
namespace App\Http\Helpers;

use Exception;
use App\Models\UserWallet;
use App\Models\Transaction;
use Illuminate\Support\Str;
use App\Models\TemporaryData;
use App\Models\Admin\Currency;
use Illuminate\Support\Facades\DB;
use App\Traits\PaymentGateway\Tatum;
use Illuminate\Support\Facades\Auth;
use App\Traits\PaymentGateway\Manual;
use App\Traits\PaymentGateway\Paypal;
use App\Traits\PaymentGateway\Stripe;
use Illuminate\Support\Facades\Route;
use App\Constants\PaymentGatewayConst;
use App\Traits\PaymentGateway\CoinGate;
use App\Traits\PaymentGateway\QrpayTrait;
use App\Traits\PaymentGateway\RazorTrait;
use Illuminate\Support\Facades\Validator;
use App\Traits\PaymentGateway\PerfectMoney;
use App\Traits\PaymentGateway\PaystackTrait;
use App\Models\Admin\PaymentGatewayCurrency;
use App\Traits\PaymentGateway\PagaditoTrait;
use App\Traits\PaymentGateway\SslcommerzTrait;
use Illuminate\Validation\ValidationException;
use App\Traits\PaymentGateway\FlutterwaveTrait;
use App\Http\Helpers\Api\Helpers as ApiResponse;
use App\Models\Admin\PaymentGateway as PaymentGatewayModel;

class PaymentGateway { 
    use Paypal,Stripe,Manual,FlutterwaveTrait,RazorTrait,SslcommerzTrait,QrpayTrait,Tatum,CoinGate,PagaditoTrait,PerfectMoney,PaystackTrait; 
    protected $request_data;
    protected $output;
    protected $currency_input_name = "gateway_currency";
    protected $amount_input = "amount";
    protected $sender_currency_input = "sender_currency";
    protected $wallet_currency_input    = "wallet_currency"; // user wallet currency
    protected $predefined_user_wallet;
    protected $project_currency = PaymentGatewayConst::PROJECT_CURRENCY_MULTIPLE;
    protected $predefined_guard;
    protected $predefined_user;

    public function __construct(array $request_data)
    {
        $this->request_data = $request_data;
    } 
    public static function init(array $data) {
        return new PaymentGateway($data);
    } 
     public function setProjectCurrency(string $type) {
        $this->project_currency = $type;
        return $this;
    }
    public function gateway() {    
        $request_data = $this->request_data;
        if(empty($request_data)) throw new Exception("Gateway Information is not available. Please provide payment gateway currency alias");
        $validated = $this->validator($request_data)->validate();
        $gateway_currency = PaymentGatewayCurrency::where("alias",$validated[$this->currency_input_name])->first();

        if(!$gateway_currency || !$gateway_currency->gateway) {
            throw ValidationException::withMessages([
                $this->currency_input_name = "Gateway not available",
            ]);
        }
        // get user wallet
        $sender_currency = Currency::where('code', $validated[$this->sender_currency_input])->first();  
    
         if($gateway_currency->gateway->alias == 'coingate'){
            $sender_wallet = $this->getUserWallet($sender_currency);
        }else if($gateway_currency->gateway->alias == 'paystack'){
            $sender_wallet = $this->getUserWallet($sender_currency);
        }else{
            $sender_wallet = UserWallet::where(['user_id' => auth()->user()->id, 'currency_id' => $sender_currency->id])->first();
        } 
        
        if(!$sender_wallet) {
            throw ValidationException::withMessages([
                $this->currency_input_name = "User wallet not found!",
            ]);
        }

        if($gateway_currency->gateway->isAutomatic()) {
            $this->output['type']   = PaymentGatewayConst::TYPEADDMONEY;
            $this->output['gateway']    = $gateway_currency->gateway;
            $this->output['gateway_currency']   = $gateway_currency;
            $this->output['sender_currency']   = $sender_currency;
            $this->output['amount']     = $this->amount();
            $this->output['wallet']     = $sender_wallet;
            $this->output['distribute'] = $this->gatewayDistribute($gateway_currency->gateway);
        }elseif($gateway_currency->gateway->isManual()){
            $this->output['type']   = PaymentGatewayConst::TYPEADDMONEY;
            $this->output['gateway']    = $gateway_currency->gateway;
            $this->output['gateway_currency']   = $gateway_currency;
            $this->output['sender_currency']   = $sender_currency;
            $this->output['amount']     = $this->amount();
            $this->output['wallet']     = $sender_wallet;
            $this->output['distribute'] = $this->gatewayDistribute($gateway_currency->gateway);
        } 
        // limit validation
        $this->limitValidation($this->output);

        return $this;
    }

    public function validator($data) {
        return Validator::make($data,[
            $this->currency_input_name  => "required|exists:payment_gateway_currencies,alias",
            $this->amount_input         => "required|numeric",
            $this->sender_currency_input     => "required",
        ]);
    }

    public function limitValidation($output) {
        $gateway_currency = $output['gateway_currency'];
        $requested_amount = $output['amount']->requested_amount;
        $exchange_rate = $output['amount']->exchange_rate; 
        if($requested_amount < ($gateway_currency->min_limit/$exchange_rate) || $requested_amount > ($gateway_currency->max_limit/$exchange_rate)) {
            throw ValidationException::withMessages([
                $this->amount_input = "Please follow the transaction limit",
            ]);
        }
    }

    public function get() {
        return $this->output;
    }

    public function gatewayDistribute($gateway = null) {
        if(!$gateway) $gateway = $this->output['gateway'];
        $alias = Str::lower($gateway->alias);
        if($gateway->type == PaymentGatewayConst::AUTOMATIC){
            $method = PaymentGatewayConst::register($alias);
        }elseif($gateway->type == PaymentGatewayConst::MANUAL){
            $method = PaymentGatewayConst::register(strtolower($gateway->type));
        }

        if(method_exists($this,$method)) {
            return $method;
        }
        throw new Exception("Gateway(".$gateway->name.") Trait or Method (".$method."()) does not exists");
    }

    public function amount() {
        $gateway_currency = $this->output['gateway_currency'] ?? null;
        $sender_currency = $this->output['sender_currency'] ?? null;
   
        if(!$gateway_currency) throw new Exception("Gateway currency not found");
        return $this->chargeCalculate($gateway_currency, $sender_currency);
    }

    public function chargeCalculate($gateway_currency, $sender_currency = null, $receiver_currency = null) {
        $amount = $this->request_data[$this->amount_input];
        $gateway_currency_rate = $gateway_currency->rate;
        $sender_currency_rate = $sender_currency->rate;

        ($gateway_currency_rate == "" || $gateway_currency_rate == null) ? $gateway_currency_rate = 0 : $gateway_currency_rate;
        ($amount == "" || $amount == null) ? $amount = 0 : $amount;
        ($sender_currency_rate == "" || $sender_currency_rate == null) ? $sender_currency_rate = 0 : $sender_currency_rate;

        //calculate exchange rate
        $sender_currency = Currency::where('code', $sender_currency->code)->first();
        $exchange_rate =  (1/$sender_currency_rate)*$gateway_currency_rate;

        if($gateway_currency != null) {
            $fixed_charges = $gateway_currency->fixed_charge;
            $percent_charges = $gateway_currency->percent_charge;
        }else {
            $fixed_charges = 0;
            $percent_charges = 0;
        }

        $fixed_charge_calc = ( $fixed_charges);
        $percent_charge_calc = ($amount*$exchange_rate)*($percent_charges/100);

        $total_charge = $fixed_charge_calc + $percent_charge_calc;  
        $total_amount = ($amount * $exchange_rate);
        $total_payable_amount = $total_amount + $total_charge;

        $data = [
            'requested_amount'          => $amount,
            'gateway_cur_code'           => $gateway_currency->currency_code,
            'gateway_cur_rate'           => $gateway_currency_rate ?? 0,
            'gateway_fixed_charge'              => $fixed_charge_calc,
            'gateway_percent_charge'            => $percent_charge_calc,
            'gateway_total_charge'              => $total_charge,
            'exchange_rate'             => $exchange_rate, 
            'total_payable_amount'              => $total_payable_amount,
            'sender_currency'             => $sender_currency->code,
        ];


        return (object) $data;
    }

    public function render() { 
        $output = $this->output;
        if(!is_array($output)) throw new Exception("Render Faild! Please call with valid gateway/credentials");

        $common_keys = ['gateway','gateway_currency','sender_currency','amount','wallet','distribute'];
        foreach($output as $key => $item) {
            if(!array_key_exists($key,$common_keys)) {
                $this->gateway();
                break;
            }
        }
        
        $distributeMethod = $this->output['distribute'];
        return $this->$distributeMethod($output) ?? throw new Exception("Something went worng! Please try again.");
    }
    // api render
    public function api() {
        $output               = $this->output;
        $output['distribute'] = $this->gatewayDistribute() . "Api";
        $method               = $output['distribute'];
        $response             = $this->$method($output);
        $output['response']   = $response;
        $this->output         = $output;
        return $this;
    }

    public function responseReceive($type = null) {    
        $tempData = $this->request_data; 
        if(empty($tempData) || empty($tempData['type'])) throw new Exception('Transaction faild. Record didn\'t saved properly. Please try again.');
        $method_name = $tempData['type']."Success";
        if($this->requestIsApiUser()) {
            $creator_table = $tempData['data']->creator_table ?? null;
            $creator_id = $tempData['data']->creator_id ?? null;
            $creator_guard = $tempData['data']->creator_guard ?? null;
            $api_authenticated_guards = PaymentGatewayConst::apiAuthenticateGuard();
            if(!array_key_exists($creator_guard,$api_authenticated_guards)) throw new Exception('Request user doesn\'t save properly. Please try again');
            if($creator_table == null || $creator_id == null || $creator_guard == null) throw new Exception('Request user doesn\'t save properly. Please try again');
            $creator = DB::table($creator_table)->where("id",$creator_id)->first();
            if(!$creator) throw new Exception("Request user doesn\'t save properly. Please try again");
            $api_user_login_guard = $api_authenticated_guards[$creator_guard];
            $this->output['api_login_guard'] = $api_user_login_guard;
            Auth::guard($api_user_login_guard)->loginUsingId($creator->id);
        }
        $currency_id = $tempData['data']->currency ?? "";
        $gateway_currency = PaymentGatewayCurrency::find($currency_id);
        if(!$gateway_currency) throw new Exception('Transaction faild. Gateway currency not available.');
        $requested_amount = $tempData['data']->amount->requested_amount ?? 0;
        $validator_data = [
            $this->currency_input_name      => $gateway_currency->alias,
            $this->amount_input             => $requested_amount,
            $this->sender_currency_input    => $tempData['data']->amount->sender_currency,
        ];
        $this->request_data = $validator_data;
        $this->gateway();
        $this->output['tempData'] = $tempData; 
       
        if($type == 'flutterWave'){
            if(method_exists(FlutterwaveTrait::class,$method_name)) {
                return $this->$method_name($this->output);
            }
        }elseif($type == 'stripe'){
            if(method_exists(Stripe::class,$method_name)) {
                return $this->$method_name($this->output);
            }
        }elseif($type == 'razorpay'){ 
            if(method_exists(RazorTrait::class,$method_name)) { 
                return $this->$method_name($this->output);
            } 
        }elseif($type == 'sslcommerz'){
            if(method_exists(SslcommerzTrait::class,$method_name)) {
                return $this->$method_name($this->output);
            }
        }elseif($type == 'qrpay'){
            if(method_exists(QrpayTrait::class,$method_name)) {
                return $this->$method_name($this->output);
            }
        }elseif($type == 'coingate'){
            if(method_exists(CoinGate::class,$method_name)) {
                return $this->$method_name($this->output);
            }
        }elseif($type == 'pagadito'){
            if(method_exists(PagaditoTrait::class,$method_name)) {
                return $this->$method_name($this->output);
            }
        }elseif($type == 'perfectmoney'){ 
            if(method_exists(PerfectMoney::class,$method_name)) {
                return $this->$method_name($this->output);
            }
        }elseif($type == 'paystack'){  
            if(method_exists(PaystackTrait::class,$method_name)) {
                return $this->$method_name($this->output);
            } 
        }
        else{
            if(method_exists(Paypal::class,$method_name)) {
                return $this->$method_name($this->output);
            }
        }
        throw new Exception("Response method ".$method_name."() does not exists.");
    }

    

    public function responseReceiveApi($type = null) {
        $tempData = $this->request_data;  
        if(empty($tempData) || empty($tempData['type'])){
            $error = ['error'=>['Transaction faild. Record didn\'t saved properly. Please try again.']];
            return ApiResponse::onlyError($error);
        }

        if($this->requestIsApiUser()) {
            $creator_table = $tempData['data']->creator_table ?? null;
            $creator_id = $tempData['data']->creator_id ?? null;
            $creator_guard = $tempData['data']->creator_guard ?? null;
            $api_authenticated_guards = PaymentGatewayConst::apiAuthenticateGuard();
            if($creator_table != null && $creator_id != null && $creator_guard != null) {
                if(!array_key_exists($creator_guard,$api_authenticated_guards)) throw new Exception('Request user doesn\'t save properly. Please try again');
                $creator = DB::table($creator_table)->where("id",$creator_id)->first();
                if(!$creator) throw new Exception("Request user doesn\'t save properly. Please try again");
                $api_user_login_guard = $api_authenticated_guards[$creator_guard];
                $this->output['api_login_guard'] = $api_user_login_guard;
                Auth::guard($api_user_login_guard)->loginUsingId($creator->id);
            }
        } 
        
        $method_name = $tempData['type']."Success";

        
        $currency_id = $tempData['data']->currency ?? "";
        $gateway_currency = PaymentGatewayCurrency::find($currency_id);
        if(!$gateway_currency){
            $error = ['error'=>['Transaction faild. Gateway currency not available.']];
            return ApiResponse::onlyError($error);
        }
        
        $requested_amount = $tempData['data']->amount->requested_amount ?? 0;
        $validator_data = [
            $this->currency_input_name => $gateway_currency->alias,
            $this->amount_input        => $requested_amount,
            $this->sender_currency_input    => $tempData['data']->amount->sender_currency,
        ];
        $this->request_data = $validator_data;
        
        // dd('check');
        $this->gateway();
        $this->output['tempData'] = $tempData; 
        
        if($type == 'flutterWave'){
            if(method_exists(FlutterwaveTrait::class,$method_name)) {
                return $this->$method_name($this->output);
            }
        }elseif($type == 'stripe'){
            if(method_exists(Stripe::class,$method_name)) {
                return $this->$method_name($this->output);
            }
        }elseif($type == 'razorpay'){
            if(method_exists(RazorTrait::class,$method_name)) {
                return $this->$method_name($this->output);
            }
        }elseif($type == 'sslcommerz'){
            if(method_exists(SslcommerzTrait::class,$method_name)) {
                return $this->$method_name($this->output);
            }
        }elseif($type == 'qrpay'){
            if(method_exists(QrpayTrait::class,$method_name)) {
                return $this->$method_name($this->output);
            }
        }elseif($type == 'pagadito'){ 
            if(method_exists(PagaditoTrait::class,$method_name)) {
                return $this->$method_name($this->output);
            }
        }elseif($type == 'coingate'){
            if(method_exists(CoinGate::class,$method_name)) {
                return $this->$method_name($this->output);
            }
        }elseif($type == 'paystack'){  
            if(method_exists(PaystackTrait::class,$method_name)) {
                return $this->$method_name($this->output);
            } 
        }
        else{  
            if(method_exists(Paypal::class,$method_name)) {
                return $this->$method_name($this->output);
            }
        }

        $error = ['error'=>["Response method ".$method_name."() does not exists."]];
        return ApiResponse::onlyError($error);

    }
    public function handleCallback($reference,$callback_data,$gateway_name) {

        if($reference == PaymentGatewayConst::CALLBACK_HANDLE_INTERNAL) {
            $gateway = PaymentGatewayModel::gateway($gateway_name)->first();
            $callback_response_receive_method = $this->getCallbackResponseMethod($gateway);
            return $this->$callback_response_receive_method($callback_data, $gateway);
        }

        $transaction = Transaction::where('callback_ref',$reference)->first();
        $this->output['callback_ref']       = $reference;
        $this->output['capture']            = $callback_data;

        if($transaction) {
            $gateway_currency = $transaction->gateway_currency;
            $gateway = $gateway_currency->gateway;

            $requested_amount = $transaction->sender_request_amount;
            $requested_currency = $transaction->sender_currency_code;
            $validator_data = [
                $this->currency_input_name  => $gateway_currency->alias,
                $this->amount_input         => $requested_amount,
                $this->sender_currency_input  => $requested_currency
            ];

            $user_wallet = $transaction->user_wallets;
            $this->predefined_user_wallet = $user_wallet;
            $this->predefined_guard = $transaction->creator->modelGuardName();
            $this->predefined_user = $transaction->creator;

            $this->output['transaction']    = $transaction;

        }else {
            // find reference on temp table
            $tempData = TemporaryData::where('identifier',$reference)->first();
            if($tempData) {
                $gateway_currency_id = $tempData->data->currency ?? null;
                $gateway_currency = PaymentGatewayCurrency::find($gateway_currency_id);
                if($gateway_currency) {
                    $gateway = $gateway_currency->gateway;

                    $requested_amount = $tempData['data']->amount->requested_amount ?? 0;
                    $requested_currency = $tempData['data']->amount->sender_currency;
                    $validator_data = [
                        $this->currency_input_name  => $gateway_currency->alias,
                        $this->amount_input         => $requested_amount,
                        $this->sender_currency_input  => $tempData['data']->amount->sender_currency
                    ];

                    $get_wallet_model = PaymentGatewayConst::registerWallet()[$tempData->data->creator_guard];
                    $user_wallet = $get_wallet_model::find($tempData->data->wallet_id);
                    $this->predefined_user_wallet = $user_wallet;
                    $this->predefined_guard = $user_wallet->user->modelGuardName(); // need to update
                    $this->predefined_user = $user_wallet->user;

                    $this->output['tempData'] = $tempData;
                }
            }
        }


        if(isset($gateway)) {
            
            $this->request_data = $validator_data;
            $this->gateway();

            $callback_response_receive_method = $this->getCallbackResponseMethod($gateway); 
            return $this->$callback_response_receive_method($reference, $callback_data, $this->output);
        }

        logger("Gateway not found!!" , [
            "reference"     => $reference,
        ]);
    }

    public function type($type) {
        $this->output['type']  = $type;
        return $this;
    } 
    public function requestIsApiUser() {
        $request_source = request()->get('r-source');
        if($request_source != null && $request_source == PaymentGatewayConst::APP) return true;
        if(request()->routeIs('api.*')) return true;
        return false;
    }
    public static function getValueFromGatewayCredentials($gateway, $keywords) {
        $result = "";
        $outer_break = false;
        foreach($keywords as $item) {
            if($outer_break == true) {
                break;
            }
            $modify_item = PaymentGateway::makePlainText($item);
            foreach($gateway->credentials ?? [] as $gatewayInput) {
                $label = $gatewayInput->label ?? "";
                $label = PaymentGateway::makePlainText($label);

                if($label == $modify_item) {
                    $result = $gatewayInput->value ?? "";
                    $outer_break = true;
                    break;
                }
            }
        }
        return $result;
    }
    public static function makePlainText($string) {
        $string = Str::lower($string);
        return preg_replace("/[^A-Za-z0-9]/","",$string);
    }
    public function setUrlParams(string $url_params) {
        $output = $this->output;
        if(!$output) throw new Exception("Something went wrong! Gateway render failed. Please call gateway() method before calling api() method");
        if(isset($output['url_params'])) {
            // if already param has
            $params = $this->output['url_params'];
            $update_params = $params . "&" . $url_params;
            $this->output['url_params'] = $update_params; // Update/ reassign URL Parameters
        }else {
            $this->output['url_params']  = $url_params; // add new URL Parameters;
        }
    }
    public function getUrlParams() {
        $output = $this->output;
        if(!$output || !isset($output['url_params'])) $params = "";
        $params = $output['url_params'] ?? "";
        return $params;
    }
    public function setGatewayRoute($route_name, $gateway, $params = null) {
        if(!Route::has($route_name)) throw new Exception('Route name ('.$route_name.') is not defined');
        if($params) {
            return route($route_name,$gateway."?".$params);
        }
        return route($route_name,$gateway);
    }
    public function searchWithReferenceInTransaction($reference) {
        $transaction = DB::table('transactions')->where('callback_ref',$reference)->first();
        if($transaction) {
            return $transaction;
        }
        return false;
    }
    public function getRedirection() {
        $redirection = PaymentGatewayConst::registerRedirection();
        $guard = get_auth_guard();
        if(!array_key_exists($guard,$redirection)) {
            throw new Exception("Gateway Redirection URLs/Route Not Registered. Please Register in PaymentGatewayConst::class");
        }
        $gateway_redirect_route = $redirection[$guard];
        return $gateway_redirect_route;
    }
    public function generateLinkForRedirectForm($token, $gateway)
    {
        $redirection = $this->getRedirection();
        $form_redirect_route = $redirection['redirect_form'];
        return route($form_redirect_route, [$gateway, 'token' => $token]);
    }
    public function getCallbackResponseMethod($gateway) {

        $gateway_is = PaymentGatewayConst::registerGatewayRecognization();
        foreach($gateway_is as $method => $gateway_name) {
            if(method_exists($this,$method)) {
                if($this->$method($gateway)) {
                    return $this->generateCallbackMethodName($gateway_name);
                    break;
                }
            }
        }

    }
    public function generateCallbackMethodName(string $name) {
        $name = $this->removeSpacialChar($name,"");
        return $name . "CallbackResponse";
    }
    function removeSpacialChar($string, $replace_string = "") {
        return preg_replace("/[^A-Za-z0-9]/",$replace_string,$string);
    }
    public function getUserWallet($gateway_currency) {

        if($this->predefined_user_wallet) return $this->predefined_user_wallet;

        $guard = get_auth_guard();
        $register_wallets = PaymentGatewayConst::registerWallet();
        if(!array_key_exists($guard,$register_wallets)) {
            throw new Exception("Wallet Not Registered. Please register user wallet in PaymentGatewayConst::class with user guard name");
        }
        $wallet_model = $register_wallets[$guard];
        $user_wallet = $wallet_model::auth()->whereHas("currency",function($q) use ($gateway_currency){
            $q->where("code",$gateway_currency->code);
        })->first();

        if(!$user_wallet) {
            if(request()->acceptsJson()) throw new Exception(__("User wallet not found!"));
            throw ValidationException::withMessages([
                $this->currency_input_name = __("User wallet not found!"),
            ]);
        }

        return $user_wallet;
    }
    public function authenticateTempData()
    {
        $tempData = $this->request_data;
        if(empty($tempData) || empty($tempData['type'])) throw new Exception(__("Transaction Failed. Record didn\'t saved properly. Please try again"));
        if($this->requestIsApiUser()) {
            $creator_table = $tempData['data']->creator_table ?? null;
            $creator_id = $tempData['data']->creator_id ?? null;
            $creator_guard = $tempData['data']->creator_guard ?? null;
            $api_authenticated_guards = PaymentGatewayConst::apiAuthenticateGuard();
            if(!array_key_exists($creator_guard,$api_authenticated_guards)) throw new Exception('Request user doesn\'t save properly. Please try again');
            if($creator_table == null || $creator_id == null || $creator_guard == null) throw new Exception('Request user doesn\'t save properly. Please try again');
            $creator = DB::table($creator_table)->where("id",$creator_id)->first();
            if(!$creator) throw new Exception("Request user doesn\'t save properly. Please try again");
            $api_user_login_guard = $api_authenticated_guards[$creator_guard];
            $this->output['api_login_guard'] = $api_user_login_guard;
            Auth::guard($api_user_login_guard)->loginUsingId($creator->id);
        }

        $currency_id = $tempData['data']->currency ?? "";
        $gateway_currency = PaymentGatewayCurrency::find($currency_id);
        if(!$gateway_currency) throw new Exception('Transaction Failed. Gateway currency not available.');
        $requested_amount = $tempData['data']->amount->requested_amount ?? 0;
        $validator_data = [
            $this->currency_input_name  => $gateway_currency->alias,
            $this->amount_input         => $requested_amount,
            $this->sender_currency_input    => $tempData['data']->amount->sender_currency,
        ];

        $this->request_data = $validator_data;
        $this->gateway();
        $this->output['tempData'] = $tempData;
    }
        /**
     * Link generation for button pay (JS checkout)
     */
    public function generateLinkForBtnPay($token, $gateway)
    {
        $redirection = $this->getRedirection();
        $form_redirect_route = $redirection['btn_pay'];
        return route($form_redirect_route, [$gateway, 'token' => $token]);
    }
    public function generateBtnPayResponseMethod(string $gateway)
    {
        $name = $this->removeSpacialChar($gateway,"");
        return $name . "BtnPay";
    }
    /**
     * Handle Button Pay (JS Checkout) Redirection
     */
    public function handleBtnPay($gateway, $request_data)
    { 
        if(!array_key_exists('token', $request_data)) throw new Exception("Requested with invalid token");
        $temp_token = $request_data['token'];

        $temp_data = TemporaryData::where('identifier', $temp_token)->first();
        if(!$temp_data) throw new Exception("Requested with invalid token");
        
        $this->request_data = $temp_data->toArray(); 
        $this->authenticateTempData();
      
        $method = $this->generateBtnPayResponseMethod($gateway);  
        
        if(method_exists($this, $method)) { 
            return $this->$method($temp_data);
        }

        throw new Exception("Button Pay response method [" . $method ."()] not available in this gateway");
    }
    
    public static function getToken(array $response, string $gateway) {
        switch($gateway) {
            case PaymentGatewayConst::PERFECT_MONEY:
                return $response['PAYMENT_ID'] ?? "";
                break;
            case PaymentGatewayConst::RAZORPAY:
                return $response['token'] ?? "";
                break;
            case PaymentGatewayConst::PAGADITO:
                return $response['param1'] ?? "";
                break;
            default:
                throw new Exception("Oops! Gateway not registered in getToken method");
        }
        throw new Exception("Gateway token not found!");
    }

}
