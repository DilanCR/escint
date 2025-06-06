<?php
namespace App\Http\Helpers;

use Exception;
use Illuminate\Support\Str;
use App\Models\TemporaryData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Constants\PaymentGatewayConst;
use App\Traits\EscrowPaymentGateway\Tatum;
use App\Traits\EscrowPaymentGateway\Manual;
use App\Traits\EscrowPaymentGateway\Paypal;
use App\Traits\EscrowPaymentGateway\Stripe;
use App\Traits\EscrowPaymentGateway\CoinGate;
use App\Traits\EscrowPaymentGateway\QrpayTrait;
use App\Traits\EscrowPaymentGateway\RazorTrait;
use App\Traits\EscrowPaymentGateway\PagaditoTrait;
use App\Traits\EscrowPaymentGateway\PaystackTrait;
use App\Traits\EscrowPaymentGateway\PerfectMoney; 
use App\Traits\EscrowPaymentGateway\FlutterwaveTrait;
use App\Traits\EscrowPaymentGateway\SslcommerzTrait; 

class EscrowPaymentGateway {
    use Paypal, Stripe, FlutterwaveTrait, Manual, RazorTrait, SslcommerzTrait,QrpayTrait,CoinGate,PagaditoTrait,PerfectMoney,Tatum,PaystackTrait;

    protected $request_data;

    public function __construct($request_data) {
        $this->request_data = $request_data;
    }
    public static function init($data) {
        return new EscrowPaymentGateway($data);
    } 
    public function gateway() { 
        $escrow_data = $this->request_data->data;
        $distributeMethod = $this->gatewayDistribute($escrow_data->gateway_currency->gateway);
        return $this->$distributeMethod($escrow_data) ?? throw new Exception("Something went worng! Please try again.");
    }
    public function gatewayDistribute($gateway = null) {
        if(!$gateway) $gateway = $this->request_data->data->gateway_currency->gateway;
           $alias              = Str::lower($gateway->alias);
        if($gateway->type == PaymentGatewayConst::AUTOMATIC){
            $method = PaymentGatewayConst::register($alias);
        }elseif($gateway->type == PaymentGatewayConst::MANUAL){
            $method = PaymentGatewayConst::register(strtolower($gateway->type));
        }
         
        return $method; 
        throw new Exception("Gateway(".$gateway->name.") Trait or Method (".$method."()) does not exists");
    }
     public function requestIsApiUser() {
        $request_source = request()->get('r-source');
        if($request_source != null && $request_source == PaymentGatewayConst::APP) return true;
        if(request()->routeIs('api.*')) return true;
        return false;
    }
    function removeSpacialChar($string, $replace_string = "") {
        return preg_replace("/[^A-Za-z0-9]/",$replace_string,$string);
    }
    //api gateway 
    public function apiGateway(){
        $escrow_data = $this->request_data->data;
        $distributeMethod = $this->gatewayDistribute($escrow_data->gateway_currency->gateway). "Api"; 
        return $this->$distributeMethod($escrow_data) ?? throw new Exception("Something went worng! Please try again.");
    }

    public function setGatewayRoute($route_name, $gateway, $params = null) {
        if(!Route::has($route_name)) throw new Exception('Route name ('.$route_name.') is not defined');
        if($params) {
            return route($route_name,$gateway."?".$params);
        }
        return route($route_name,$gateway);
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
            // $this->output['api_login_guard'] = $api_user_login_guard;
            Auth::guard($api_user_login_guard)->loginUsingId($creator->id);
        }
    }
    public function generateLinkForRedirectForm($token, $gateway)
    {
        $redirection = $this->getRedirection();
        $form_redirect_route = $redirection['redirect_form'];
        return route($form_redirect_route, [$gateway, 'token' => $token]);
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

}