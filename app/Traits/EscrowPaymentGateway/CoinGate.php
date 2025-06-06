<?php

namespace App\Traits\EscrowPaymentGateway;

use Exception;
use App\Models\Escrow;
use Illuminate\Support\Str; 
use App\Http\Helpers\Api\Helpers;
use Illuminate\Support\Facades\DB; 
use Illuminate\Support\Facades\Http;
use App\Constants\PaymentGatewayConst;
use App\Http\Helpers\Api\Helpers as ApiResponse;  

trait CoinGate
{ 

    private $coinGate_gateway_credentials;
    private $coinGate_access_token;
    private $coinGate_status_paid = "paid";

    public function coingateInit($escrow_data = null) { 
        if(!$escrow_data) $escrow_data = $this->request_data->data;
        $credentials = $this->getCoinGateCredentials($escrow_data->gateway_currency);
        $request_credentials = $this->getCoinGateRequestCredentials($escrow_data->gateway_currency);
        return $this->coinGateCreateOrder($request_credentials, $escrow_data);

    }
    public function getCoinGateCredentials($escrow_data) {
        $gateway = $escrow_data->gateway ?? null;
        if(!$gateway) throw new Exception("Payment gateway not available");

        $production_url_sample = ['live','live url','live env','live environment', 'coin gate live url','coin gate live','production url', 'production link'];
        $production_app_token_sample = ['production token','production app token','production auth token','live token','live app token','live auth token'];
        $sandbox_url_sample = ['sandbox','sandbox url','sandbox env', 'test url', 'test', 'sandbox environment', 'coin gate sandbox url', 'coin gate sandbox' , 'coin gate test'];
        $sandbox_app_token_sample = ['sandbox token','sandbox app token','test app token','test token','test auth token','sandbox auth token'];

        $production_url = $this->getValueFromGatewayCredentials($gateway,$production_url_sample);
        $production_app_token = $this->getValueFromGatewayCredentials($gateway,$production_app_token_sample);
        $sandbox_url = $this->getValueFromGatewayCredentials($gateway,$sandbox_url_sample);
        $sandbox_app_token = $this->getValueFromGatewayCredentials($gateway,$sandbox_app_token_sample);

        $mode = $gateway->env;
        
        $gateway_register_mode = [
            PaymentGatewayConst::ENV_SANDBOX => "sandbox",
            PaymentGatewayConst::ENV_PRODUCTION => "production",
        ];

        if(array_key_exists($mode,$gateway_register_mode)) {
            $mode = $gateway_register_mode[$mode];
        }else {
            $mode = "sandbox";
        }

        $credentials = (object) [
            'production_url'    => $production_url,
            'production_token'  => $production_app_token,
            'sandbox_url'       => $sandbox_url,
            'sandbox_token'     => $sandbox_app_token,
            'mode'              => $mode,
        ];

        $this->coinGate_gateway_credentials = $credentials;
         
        return $credentials;
    }
    public function getCoinGateRequestCredentials($escrow_data = null) {
        $credentials = $this->coinGate_gateway_credentials;
        $gateway = $escrow_data->gateway ?? null;

        $request_credentials = [];
        if($gateway->env == PaymentGatewayConst::ENV_PRODUCTION) {
            $request_credentials['url']     = $credentials->production_url;
            $request_credentials['token']   = $credentials->production_token;
        }else {
            $request_credentials['url']     = $credentials->sandbox_url;
            $request_credentials['token']   = $credentials->sandbox_token;
        }
        return (object) $request_credentials;
    }
    public function registerCoinGateEndpoints() {
        return [
            'createOrder'       => 'orders',
        ];
    }

    public function getCoinGateEndpoint($name) {
        $endpoints = $this->registerCoinGateEndpoints();
        if(array_key_exists($name,$endpoints)) {
            return $endpoints[$name];
        }
        throw new Exception("Oops! Request endpoint not registered!");
    }

    public function coinGateCreateOrder($credentials, $escrow_data) { 
        // dd($escrow_data);
        $request_base_url       = $credentials->url;
        $request_access_token   = $credentials->token;   

        if (isset($escrow_data->payment_type) && $escrow_data->payment_type == "approvalPending") {
            $redirection = [
                "return_url"   => "user.escrow-action.coingate.payment.approval.success",
                "cancel_url"   => "user.escrow-action.payment.cancel",
                "callback_url" => "user.escrow-action.coingate.payment.approval.callback"
            ];
            $escrowUpdate = Escrow::find($escrow_data->escrow->escrow_id);
            if(!$escrowUpdate) throw new Exception("Something went wrong! Please try again");
            $escrowUpdate->callback_ref = $escrow_data->identifier;
            $escrowUpdate->save();
            $url_parameter = "trx=" . $escrow_data->identifier; 
        }else { 
            $redirection = [
                "return_url"   => "user.my-escrow.coingate.success",
                "cancel_url"   => "user.my-escrow.coingate.cancel",
                "callback_url" => "user.my-escrow.coingate.callback"
            ];
            $url_parameter = "trx=" . $escrow_data->trx; 
        } 
        $endpoint = $request_base_url . "/" . $this->getCoinGateEndpoint('createOrder');

        $response = Http::withToken($request_access_token)->post($endpoint,[
            'order_id'          => Str::uuid(),
            'price_amount'      => $escrow_data->escrow->buyer_amount ? number_format($escrow_data->escrow->buyer_amount,2,'.','') : 0,
            'price_currency'    => $escrow_data->gateway_currency->currency_code ?? '',
            'receive_currency'  =>  $escrow_data->escrow->escrow_currency,
            'callback_url'      => $this->setGatewayRoute($redirection['callback_url'],PaymentGatewayConst::COINGATE,$url_parameter),
            'cancel_url'        => $this->setGatewayRoute($redirection['cancel_url'],$escrow_data->trx),
            'success_url'       => $this->setGatewayRoute($redirection['return_url'],PaymentGatewayConst::COINGATE,$url_parameter),
        ]);

        if($response->failed()) {
            $message = json_decode($response->body(),true);
            throw new Exception($message['message']);
        }
        if($response->successful()) {
            $response_array = json_decode($response->body(),true);

            if(isset($response_array['payment_url'])) { 
                return redirect()->away($response_array['payment_url']);
            }
        }

        throw new Exception("Something went wrong! Please try again");

    } 

 
    public static function isCoinGate($gateway) {
        $search_keyword = ['coingate','coinGate','coingate gateway','coingate crypto gateway','crypto gateway coingate'];
        $gateway_name = $gateway->name;

        $search_text = Str::lower($gateway_name);
        $search_text = preg_replace("/[^A-Za-z0-9]/","",$search_text);
        foreach($search_keyword as $keyword) {
            $keyword = Str::lower($keyword);
            $keyword = preg_replace("/[^A-Za-z0-9]/","",$keyword);
            if($keyword == $search_text) {
                return true;
                break;
            }
        }
        return false;
    }
    //for api
    public function coingateInitApi($escrow_data = null) {
        if(!$escrow_data) $escrow_data = $this->request_data->data;
        $credentials = $this->getCoinGateCredentials($escrow_data->gateway_currency);
        $request_credentials = $this->getCoinGateRequestCredentials($escrow_data->gateway_currency);
        return $this->coinGateCreateOrderApi($request_credentials, $escrow_data);

    }

    public function coinGateCreateOrderApi($credentials, $escrow_data) { 
        $request_base_url       = $credentials->url;
        $request_access_token   = $credentials->token; 

        if (isset($escrow_data->payment_type) && $escrow_data->payment_type == "approvalPending") {
            $redirection = [
                "return_url"   => "api.v1.api-escrow-action.payment.approval.success.coingate",
                "cancel_url"   => "api.v1.my-escrow.payment.cancel",
                "callback_url" => "api.v1.api-escrow-action.coingate.payment.approval.callback"
            ];
            $escrowUpdate = Escrow::find($escrow_data->escrow->escrow_id);
            if(!$escrowUpdate) throw new Exception("Something went wrong! Please try again");
            $escrowUpdate->callback_ref = $escrow_data->identifier;
            $escrowUpdate->save();
            $url_parameter = "trx=" . $escrow_data->identifier;  
        }else { 
            $redirection = [
                "return_url"   => "api.v1.my-escrow.coingate.payment.success",
                "cancel_url"   => "api.v1.my-escrow.payment.cancel",
                "callback_url" => "user.my-escrow.coingate.callback"
            ];
            $url_parameter = "trx=" . $escrow_data->trx; 
        } 
        $endpoint = $request_base_url . "/" . $this->getCoinGateEndpoint('createOrder');

        $response = Http::withToken($request_access_token)->post($endpoint,[
            'order_id'          => Str::uuid(),
            'price_amount'      => $escrow_data->escrow->buyer_amount ? number_format($escrow_data->escrow->buyer_amount,2,'.','') : 0,
            'price_currency'    => $escrow_data->gateway_currency->currency_code ?? '',
            'receive_currency'  =>  $escrow_data->escrow->escrow_currency,
            'callback_url'      => $this->setGatewayRoute($redirection['callback_url'],PaymentGatewayConst::COINGATE,$url_parameter),
            'cancel_url'        => $this->setGatewayRoute($redirection['cancel_url'],$escrow_data->trx),
            'success_url'       => $this->setGatewayRoute($redirection['return_url'],PaymentGatewayConst::COINGATE,$url_parameter),
        ]);

        if($response->failed()) {
            $message = json_decode($response->body(),true);
            $error = ['error'=>[$message['message']]];
            return Helpers::error($error);
        }
        if($response->successful()) {
            $response_array = json_decode($response->body(),true);

            if(isset($response_array['payment_url'])) { 
                $data['link'] =  $response_array['payment_url']; 
                $payment_informations = [
                    'trx'                   => $escrow_data->trx,
                    'gateway_currency_name' => $escrow_data->gateway_currency->name,
                    'request_amount'        => get_amount($escrow_data->escrow->amount, $escrow_data->escrow->escrow_currency),
                    'exchange_rate'         => "1".' '.$escrow_data->escrow->escrow_currency.' = '. get_amount($escrow_data->escrow->gateway_exchange_rate, $escrow_data->escrow->gateway_currency),
                    'total_charge'          => get_amount($escrow_data->escrow->escrow_total_charge, $escrow_data->escrow->escrow_currency),
                    'charge_payer'          => $escrow_data->escrow->charge_payer,
                    'seller_get'            => get_amount($escrow_data->escrow->seller_amount, $escrow_data->escrow->escrow_currency),
                    'payable_amount'        => get_amount($escrow_data->escrow->buyer_amount, $escrow_data->escrow->gateway_currency),
               ];
               $data =[
                    'gategay_type'          => $escrow_data->gateway_currency->gateway->type,
                    'gateway_currency_name' => $escrow_data->gateway_currency->name,
                    'alias'                 => $escrow_data->gateway_currency->alias,
                    'identify'              => $escrow_data->gateway_currency->gateway->name,
                    'payment_informations'  => $payment_informations,
                    'url'                   => $data['link'],
                    'method'                => "get",
               ];
               $message = ['success'=>['Escrow Payment Gateway Captured Successful']];
               return ApiResponse::success($message, $data);   
            }
        }
        $error = ['error'=>["Something went wrong! Please try again"]];
        return Helpers::error($error);


    } 
    public function getValueFromGatewayCredentials($gateway, $keywords) {
        $result = "";
        $outer_break = false;
    
        foreach ($keywords as $item) {
            if ($outer_break == true) {
                break;
            }
    
            $modify_item = $this->makePlainText($item);
    
            foreach ($gateway->credentials ?? [] as $gatewayInput) {
                $label = $gatewayInput->label ?? "";
                $label = $this->makePlainText($label);
    
                if ($label == $modify_item) {
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

}
