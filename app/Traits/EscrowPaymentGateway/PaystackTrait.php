<?php

namespace App\Traits\EscrowPaymentGateway;

use Exception; 
use Illuminate\Support\Str; 
use App\Constants\PaymentGatewayConst;
use App\Http\Helpers\Api\Helpers as ApiResponse;  


trait PaystackTrait
{ 
    public function paystackInit($escrow_data = null) { 
        if(!$escrow_data) $escrow_data = $this->request_data->data;
        $credentials = $this->getPaystackCredentials($escrow_data->gateway_currency);
        return  $this->setupPaystackInitEscrowCreate($credentials,$escrow_data);
      
    }
    public function setupPaystackInitEscrowCreate($credentials,$escrow_data){
        $amount = $escrow_data->escrow->buyer_amount ? number_format($escrow_data->escrow->buyer_amount,2,'.','') : 0;
        $currency = $escrow_data->gateway_currency->currency_code;

        if(auth()->guard(get_auth_guard())->check()){
            $user = auth()->guard(get_auth_guard())->user();
            $user_email = $user->email;
        }
        if (isset($escrow_data->payment_type) && $escrow_data->payment_type == "approvalPending") {
            $returnUrl = route('user.escrow-action.payment.approval.success',PaymentGatewayConst::PAYSTACK);
            $reference = $escrow_data->identifier; 
        }else {
            $returnUrl = route('user.my-escrow.payment.success',['gateway' => PaymentGatewayConst::PAYSTACK, 'trx' => $escrow_data->trx]);
            $reference = $escrow_data->trx;
        } 
     
        $url = "https://api.paystack.co/transaction/initialize";

        $fields             = [
            'email'         => $user_email,
            'amount'        => $amount * 100,
            'currency'        => $currency,
            'callback_url'  => $returnUrl,
            'reference'     => $reference
        ];
        
        $fields_string = http_build_query($fields);
        //open connection
        $ch = curl_init();

        //set the url, number of POST vars, POST data
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_POST, true);
        curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Authorization: Bearer $credentials->secret_key",
            "Cache-Control: no-cache",
        ));

        //So that curl_exec returns the contents of the cURL; rather than echoing it
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);

        //execute post
        $result = curl_exec($ch);
        $response   = json_decode($result);
        if($response->status == true) {
            return redirect()->away($response->data->authorization_url);
        }else{
            return redirect()->route('user.my-escrow.index')->with(['error' => [$response->message??" "."Something Is Wrong, Please Contact With Owner"]]); 
        }

    } 
    public function getPaystackCredentials($escrow_data) {
        $gateway = $escrow_data->gateway ?? null;
        if(!$gateway) throw new Exception(__("Payment gateway not available"));
        $public_key_sample = ['public_key','Public Key','public-key'];
        $secret_key_sample = ['secret_key','Secret Key','secret-key'];

        $public_key = '';
        $outer_break = false;
        foreach($public_key_sample as $item) {
            if($outer_break == true) {
                break;
            }
            $modify_item = $this->paystackPlainText($item);
            foreach($gateway->credentials ?? [] as $gatewayInput) {
                $label = $gatewayInput->label ?? "";
                $label = $this->paystackPlainText($label);

                if($label == $modify_item) {
                    $public_key = $gatewayInput->value ?? "";
                    $outer_break = true;
                    break;
                }
            }
        }


        $secret_key = '';
        $outer_break = false;
        foreach($secret_key_sample as $item) {
            if($outer_break == true) {
                break;
            }
            $modify_item = $this->paystackPlainText($item);
            foreach($gateway->credentials ?? [] as $gatewayInput) {
                $label = $gatewayInput->label ?? "";
                $label = $this->paystackPlainText($label);

                if($label == $modify_item) {
                    $secret_key = $gatewayInput->value ?? "";
                    $outer_break = true;
                    break;
                }
            }
        }



        $mode = $gateway->env;

        $paypal_register_mode = [
            PaymentGatewayConst::ENV_SANDBOX => "sandbox",
            PaymentGatewayConst::ENV_PRODUCTION => "live",
        ];
        if(array_key_exists($mode,$paypal_register_mode)) {
            $mode = $paypal_register_mode[$mode];
        }else {
            $mode = "sandbox";
        }

        return (object) [
            'public_key'     => $public_key,
            'secret_key' => $secret_key,
            'mode'          => $mode,

        ];

    }

    public function paystackPlainText($string) {
        $string = Str::lower($string);
        return preg_replace("/[^A-Za-z0-9]/","",$string);
    }
    public function isPayStack($gateway)
    {
        $search_keyword = ['Paystack','paystack','payStack','pay-stack','paystack gateway', 'paystack payment gateway'];
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
    public function paystackInitApi($escrow_data = null) {
        if(!$escrow_data) $escrow_data = $this->request_data->data;
        $credentials = $this->getPaystackCredentials($escrow_data->gateway_currency);;
        $amount = $escrow_data->escrow->buyer_amount ? number_format($escrow_data->escrow->buyer_amount,2,'.','') : 0;
        $currency = $escrow_data->gateway_currency->currency_code;
        if(auth()->guard(get_auth_guard())->check()){
            $user = auth()->guard(get_auth_guard())->user();
            $user_email = $user->email;
        }
        if (isset($escrow_data->payment_type) && $escrow_data->payment_type == "approvalPending") {
            $returnUrl = route('api.v1.api-escrow-action.payment.approval.success.paystack',['gateway' => PaymentGatewayConst::PAYSTACK, 'trx' => $escrow_data->trx],"?r-source=".PaymentGatewayConst::APP);
        }else {
            $returnUrl = route('api.v1.my-escrow.payment.success',['gateway' => PaymentGatewayConst::PAYSTACK, 'trx' => $escrow_data->trx],"?r-source=".PaymentGatewayConst::APP);
        } 
        $url = "https://api.paystack.co/transaction/initialize";

        $fields             = [
            'email'         => $user_email,
            'amount'        => $amount * 100,
            'currency'        => $currency,
            'callback_url'  => $returnUrl
        ];


        $fields_string = http_build_query($fields);
        //open connection
        $ch = curl_init();

        //set the url, number of POST vars, POST data
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_POST, true);
        curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Authorization: Bearer $credentials->secret_key",
            "Cache-Control: no-cache",
        ));

        //So that curl_exec returns the contents of the cURL; rather than echoing it
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);

        //execute post
        $result = curl_exec($ch);
        $response   = json_decode($result);
        if($response->status == true) {
            $data['link'] = $response->data->authorization_url;
            $data['trx'] =  $response->data->reference;

            $payment_informations = [
                'trx'                   => $response->data->reference,
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
        }else{
            $message = ['error' => [$response->message??" "."Something Is Wrong, Please Contact With Owner"]];
            return ApiResponse::onlyError($message);  
        }

    }

}
