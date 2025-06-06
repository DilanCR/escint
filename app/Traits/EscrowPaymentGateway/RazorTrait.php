<?php

namespace App\Traits\EscrowPaymentGateway;

use Exception;
use Razorpay\Api\Api;
use App\Models\TemporaryData;
use App\Http\Helpers\Api\Helpers;
use App\Http\Helpers\PaymentGateway;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use App\Constants\PaymentGatewayConst;
use App\Http\Helpers\EscrowPaymentGateway;
use Illuminate\Http\Client\RequestException;
use App\Http\Helpers\Api\Helpers as ApiResponse; 

trait RazorTrait
{
    private $razorpay_gateway_credentials;
    private $request_credentials;
    private $razorpay_api_base_url  = "https://api.razorpay.com/";
    private $razorpay_api_v1        = "v1";
    private $razorpay_btn_pay       = true;

    public function razorInit($escrow_data) {  
        // dd($escrow_data);

        $request_credentials = $this->getRazorpayRequestCredentials($escrow_data);
      
        try{
            if($this->razorpay_btn_pay) {
                // create link for btn pay
                return $this->razorpayCreateLinkForBtnPay($escrow_data);
            } 
        }catch(Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    public function razorInitApi($escrow_data) {   
        $request_credentials = $this->getRazorpayRequestCredentials($escrow_data);
 
        try{
            if($this->razorpay_btn_pay) { 
                // create link for btn pay
                return $this->razorpayCreateLinkForBtnPayApi($escrow_data);
            } 
        }catch(Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    public function getRazorpayRequestCredentials($escrow_data = null)
    { 
        $gateway = $escrow_data->gateway_currency->gateway; 
        if(!$this->razorpay_gateway_credentials) $this->getRazorpayCredentials($gateway);
        $credentials = $this->razorpay_gateway_credentials; 

        $request_credentials = [];
        $request_credentials['key_id']          = $credentials->key_id;
        $request_credentials['secret_key']      = $credentials->secret_key;

        $this->request_credentials = (object) $request_credentials;    
        return (object) $request_credentials;
    }
       /**
     * Create Link for Button Pay (JS Checkout)
     */
    public function razorpayCreateLinkForBtnPay($escrow_data)
    { 
        if (isset($escrow_data->payment_type) && $escrow_data->payment_type == "approvalPending") {
            $temp_data = $this->razorPayJunkUpdateApprovalPending($escrow_data,"web"); // create temporary information
            $temp_identifier    = $escrow_data->identifier; 
            return redirect()->route('user.escrow-action.payment.btn.pay',PaymentGatewayConst::RAZORPAY."?token=".$temp_identifier);
        }else {
            $temp_data = $this->razorPayJunkUpdate($escrow_data,"web"); // create temporary information
            $temp_identifier    = $escrow_data->trx;
            return redirect()->route('user.my-escrow.payment.btn.pay',PaymentGatewayConst::RAZORPAY."?token=".$temp_identifier);
        }
    }
    public function razorpayCreateLinkForBtnPayApi($escrow_data)
    {  
        if (isset($escrow_data->payment_type) && $escrow_data->payment_type == "approvalPending") {
            $temp_data = $this->razorPayJunkUpdateApprovalPending($escrow_data,"api"); // create temporary information
            $temp_identifier    = $escrow_data->identifier;
            $btn_link = route('api.v1.api-escrow-action.payment.btn.pay',PaymentGatewayConst::RAZORPAY."?token=".$temp_identifier);
        }else {
            $temp_data = $this->razorPayJunkUpdate($escrow_data,"api"); // create temporary information
            $temp_identifier    = $escrow_data->trx;
            $btn_link = route('api.v1.my-escrow.payment.btn.pay',PaymentGatewayConst::RAZORPAY."?token=".$temp_identifier);
        }
        $data['link'] =  $btn_link;
        $data['trx'] =  $temp_identifier; 
        
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
    public function getRazorpayCredentials($gateway)
    { 
        if(!$gateway) throw new Exception("Payment gateway not available");

        $key_id             = ['public key','razorpay public key','key id','razorpay public', 'public'];
        $secret_key_sample  = ['secret','secret key','razorpay secret','razorpay secret key'];
        
        $key_id             = EscrowPaymentGateway::getValueFromGatewayCredentials($gateway,$key_id);
        $secret_key         = EscrowPaymentGateway::getValueFromGatewayCredentials($gateway,$secret_key_sample);
       
        $mode = $gateway->env;
        $gateway_register_mode = [
            PaymentGatewayConst::ENV_SANDBOX => PaymentGatewayConst::ENV_SANDBOX,
            PaymentGatewayConst::ENV_PRODUCTION => PaymentGatewayConst::ENV_PRODUCTION,
        ];
        
        if(array_key_exists($mode,$gateway_register_mode)) {
            $mode = $gateway_register_mode[$mode];
        }else {
            $mode = PaymentGatewayConst::ENV_SANDBOX;
        }

        $credentials = (object) [
            'key_id'                    => $key_id,
            'secret_key'                => $secret_key,
            'mode'                      => $mode
        ];

        $this->razorpay_gateway_credentials = $credentials;
        
        return $credentials;
    }

    public function razorPayJunkUpdate($escrow_data,$type=null)
    {
        $url_parameter = "token=" . $escrow_data->trx;
        if ($type == "web") {
            $redirection = [
                "return_url"   => "user.my-escrow.payment.success.razorpay",
                "cancel_url"   => "user.my-escrow.payment.cancel",
            ];
        }else {
            $redirection = [
                "return_url"   => "api.v1.my-escrow.payment.success.razorpay",
                "cancel_url"   => "api.v1.my-escrow.payment.cancel",
            ];
        }
      

        $data = [
            'callback_url'  => $this->setGatewayRoute($redirection['return_url'],PaymentGatewayConst::RAZORPAY,$url_parameter),
            'cancel_url'    => $this->setGatewayRoute($redirection['cancel_url'],PaymentGatewayConst::RAZORPAY,$url_parameter),
        ];

        $tempData = TemporaryData::where('identifier',$escrow_data->trx)->first();
        
        if($tempData != null) {
            $update_temp_data = json_decode(json_encode($tempData->data),true);
            $update_temp_data['callback_url'] = $data['callback_url'];
            $update_temp_data['cancel_url'] = $data['cancel_url'];
        }

        $tempData->data = $update_temp_data;
        $tempData->type = PaymentGatewayConst::RAZORPAY; 
        $tempData->save();  
        return $tempData; 
    }
    public function razorPayJunkUpdateApprovalPending($escrow_data,$type=null)
    {
        $url_parameter = "token=" . $escrow_data->identifier;
        if ($type == "web") {
            $redirection = [
                "return_url"   => "user.escrow-action.payment.success.razorpay",
                "cancel_url"   => "user.escrow-action.payment.cancel",
            ];
        }else {
            $redirection = [
                "return_url"   => "api.v1.api-escrow-action.payment.success.razorpay",
                "cancel_url"   => "api.v1.my-escrow.payment.cancel",
            ];
        }
      

        $data = [
            'callback_url'  => $this->setGatewayRoute($redirection['return_url'],PaymentGatewayConst::RAZORPAY,$url_parameter),
            'cancel_url'    => $this->setGatewayRoute($redirection['cancel_url'],PaymentGatewayConst::RAZORPAY,$url_parameter),
        ];

        $tempData = TemporaryData::where('identifier',$escrow_data->identifier)->first();
        
        if($tempData != null) {
            $update_temp_data = json_decode(json_encode($tempData->data),true);
            $update_temp_data['callback_url'] = $data['callback_url'];
            $update_temp_data['cancel_url'] = $data['cancel_url'];
        }

        $tempData->data = $update_temp_data;
        $tempData->type = PaymentGatewayConst::RAZORPAY; 
        $tempData->save();  
        return $tempData; 
    }

       /**
     * Button Pay page redirection with necessary data
     */
    public function razorpayBtnPay($temp_data)
    { 
        $output = [];
        $data = $temp_data->data;
        
        if (isset($data->payment_type) && $data->payment_type == "approvalPending") {
            $gatewayCurrency = $data->gateway_currency->currency_code;
        }else{
            $gatewayCurrency = $data->escrow->gateway_currency;
        }
        if(!isset($data->razorpay_order)) { // is order is not created the create new order
            // Need to create order
            $order = $this->razorpayCreateOrder([
                'amount'            =>intval($data->escrow->buyer_amount),
                'currency'          => $gatewayCurrency,
                'receipt'           => $temp_data->identifier,
                'partial_payment'   => false,
            ],$data);

            // Update TempData
            $update_data = json_decode(json_encode($data), true);
            $update_data['razorpay_order'] = $order;

            $temp_data->update([
                'data'  => $update_data,
            ]);

            $temp_data->refresh();
        }
        $outputData = (object)[
            'name' => $data->gateway_currency->name,
            'currency_code' => $gatewayCurrency,
            'total_payable_amount' => $data->escrow->buyer_amount,
        ];
        // dd($outputData);
        $data = $temp_data->data; // update the data variable
        $order = $data->razorpay_order;
        
        $order_id                   = $order->id;
        $request_credentials        = $this->getRazorpayRequestCredentials($data);
        $output['order_id']         = $order_id;
        $output['gateway_currency'] = $outputData;
        $output['amount']           = $outputData;
        $output['key']              = $request_credentials->key_id;
        $output['callback_url']     = $data->callback_url;
        $output['cancel_url']       = $data->cancel_url;
        $output['user']             = auth()->guard(get_auth_guard())->user();
         
        return view('payment-gateway.btn-pay.razorpay', compact('output'));
    }
        /**
     * Create order for receive payment
     */
    public function razorpayCreateOrder($request_data, $data = null)
    {

        $endpoint = $this->razorpay_api_base_url . $this->razorpay_api_v1 . "/orders";
        
        $request_credentials = $this->getRazorpayRequestCredentials($data);
        
        $key_id = $request_credentials->key_id;
        $secret_key = $request_credentials->secret_key;
        // dd($request_credentials);
        $response = Http::withBasicAuth($key_id, $secret_key)->withHeaders([
            'Content-Type' => 'application/json',
        ])->post($endpoint, $request_data)->throw(function(Response $response, RequestException $exception) {
            $response_body = json_decode(json_encode($response->json()), true);
            throw new Exception($response_body['error']['description'] ?? "");
        })->json();
        // dd('res');
        return $response;
    }




















    // public function razorInit($escrow_data = null){
    //     $page_title = "Pay Now";
    //     $credentials = $this->getCredentials($escrow_data->gateway_currency);
    //     $api_key = $credentials->public_key;
    //     $api_secret = $credentials->secret_key;
    //     $api = new Api($api_key, $api_secret);
    //     $order = $api->order->create([
    //         'amount' => intval($escrow_data->escrow->buyer_amount),
    //         'currency' => 'INR',
    //         'payment_capture' => 1,
    //     ]);
    //     $orderId = $order['id'];
    //     $data = array(
    //         "order_id" =>$orderId,
    //         "public_key" =>$api_key,
    //     );
        
    //     if (isset($escrow_data->payment_type) && $escrow_data->payment_type == "approvalPending") {
    //         return view('user.my-escrow.razorpay-payment-approval', compact('page_title','escrow_data','data','orderId'));
    //     }else {
    //         return view('user.my-escrow.razorpay-payment', compact('page_title','escrow_data','data','orderId'));
    //     }
    // } 
     // Get Flutter wave credentials
     public function getCredentials($escrow_data) {
        $gateway = $escrow_data->gateway ?? null;
        if(!$gateway) throw new Exception("Payment gateway not available");

        $public_key_sample = ['api key','api_key','client id','primary key', 'public key'];
        $secret_key_sample = ['client_secret','client secret','secret','secret key','secret id'];

        $public_key = '';
        $outer_break = false;

        foreach($public_key_sample as $item) {
            if($outer_break == true) {
                break;
            }
            $modify_item = $this->flutterwavePlainText($item);
            foreach($gateway->credentials ?? [] as $gatewayInput) {
                $label = $gatewayInput->label ?? "";
                $label = $this->flutterwavePlainText($label);
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
            $modify_item = $this->flutterwavePlainText($item);
            foreach($gateway->credentials ?? [] as $gatewayInput) {
                $label = $gatewayInput->label ?? "";
                $label = $this->flutterwavePlainText($label);

                if($label == $modify_item) {
                    $secret_key = $gatewayInput->value ?? "";
                    $outer_break = true;
                    break;
                }
            }
        }
 
        $outer_break = false;

        return (object) [
            'public_key'     => $public_key,
            'secret_key'     => $secret_key,
        ];

    }
    
    // ********* For API **********
    // public function razorInitApi($escrow_data = null) {
    //     $credentials = $this->getCredentials($escrow_data->gateway_currency);
    //     $api_key = $credentials->public_key;
    //     $api_secret = $credentials->secret_key;
    //     $api = new Api($api_key, $api_secret);
    //     $order = $api->order->create([
    //         'amount' => intval($escrow_data->escrow->buyer_amount),
    //         'currency' => 'INR',
    //         'payment_capture' => 1,
    //     ]);
    //     $orderId = $order['id']; 
    //     $payment_data = [ 
    //         "order_id" =>$orderId,
    //         "public_key" =>$api_key,
    //         'trx' => $escrow_data->trx,
    //     ];
    //     if (isset($escrow_data->payment_type) && $escrow_data->payment_type == "approvalPending") {
    //         $paymentUrl = route('api.v1.api-escrow-action.payment.approval.razorPayLinkCreate',$payment_data);
    //     }else {
    //         $paymentUrl = route('api.v1.my-escrow.razor-pay.linkCreate',$payment_data);
    //     }
    //     $payment_informations = [
    //         'trx'                   => $escrow_data->trx,
    //         'gateway_currency_name' => $escrow_data->gateway_currency->name,
    //         'request_amount'        => get_amount($escrow_data->escrow->amount, $escrow_data->escrow->escrow_currency),
    //         'exchange_rate'         => "1".' '.$escrow_data->escrow->escrow_currency.' = '. get_amount($escrow_data->escrow->gateway_exchange_rate, $escrow_data->escrow->gateway_currency),
    //         'total_charge'          => get_amount($escrow_data->escrow->escrow_total_charge, $escrow_data->escrow->escrow_currency),
    //         'charge_payer'          => $escrow_data->escrow->charge_payer,
    //         'seller_get'            => get_amount($escrow_data->escrow->seller_amount, $escrow_data->escrow->escrow_currency),
    //         'payable_amount'        => get_amount($escrow_data->escrow->buyer_amount, $escrow_data->escrow->gateway_currency),
    //    ];
    //    $data =[
    //         "order_id" =>$orderId,
    //         "public_key" =>$api_key,
    //         'gategay_type'          => $escrow_data->gateway_currency->gateway->type,
    //         'gateway_currency_name' => $escrow_data->gateway_currency->name,
    //         'alias'                 => $escrow_data->gateway_currency->alias,
    //         'identify'              => $escrow_data->gateway_currency->gateway->name,
    //         'payment_informations'  => $payment_informations,
    //         'url'                   => $paymentUrl,
    //         'method'                => "get",
    //    ];
    //    $message = ['success'=>['Escrow Payment Gateway Captured Successful']];
    //    return ApiResponse::success($message, $data);  
    // }
    public function razorInitApid($escrow_data = null) {
        if(!$escrow_data) $escrow_data = $this->request_data->data;
        $credentials = $this->getCredentials($escrow_data->gateway_currency);
        $api_key = $credentials->public_key;
        $api_secret = $credentials->secret_key;
        $amount = $escrow_data->escrow->buyer_amount ? number_format($escrow_data->escrow->buyer_amount,2,'.','') : 0;
        if(auth()->guard(get_auth_guard())->check()){
            $user = auth()->guard(get_auth_guard())->user();
            $user_email = $user->email;
            $user_phone = $user->full_mobile ?? '';
            $user_name = $user->firstname.' '.$user->lastname ?? '';
        }
        if (isset($escrow_data->payment_type) && $escrow_data->payment_type == "approvalPending") {
            $returnUrl = route('api.v1.user.api-escrow-action.payment.approval.success',['gateway' => PaymentGatewayConst::PAYPAL, 'trx' => $escrow_data->trx],"?r-source=".PaymentGatewayConst::APP);
        }else {
            $returnUrl = route('api.v1.my-escrow.razor.callback', "r-source=".PaymentGatewayConst::APP);
        }
        $return_url = $returnUrl;

        $payment_link = "https://api.razorpay.com/v1/payment_links";

        // Enter the details of the payment
        $data = array(
            "amount" => $amount * 100,
            "currency" => $escrow_data->gateway_currency->currency_code,
            "accept_partial" => false,
            "first_min_partial_amount" => 100,
            "reference_id" =>getTrxNum(),
            "description" => "Create Escrow",
            "customer" => array(
                "name" => $user_name ,
                "contact" => $user_phone,
                "email" =>  $user_email
            ),
            "notify" => array(
                "sms" => true,
                "email" => true
            ),
            "reminder_enable" => true,
            "notes" => array(
                "policy_name"=> "AdEscrow"
            ),
            "callback_url"=> $return_url,
            "callback_method"=> "get"
        );

        $payment_data_string = json_encode($data);
        $payment_ch = curl_init($payment_link);
        curl_setopt($payment_ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($payment_ch, CURLOPT_POSTFIELDS, $payment_data_string);
        curl_setopt($payment_ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($payment_ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($api_key . ':' . $api_secret)
        ));

        $payment_response = curl_exec($payment_ch);
        $payment_data = json_decode($payment_response, true);
        if(isset($payment_data['error'])){
            $message = ['error' => [$payment_data['error']['description']]];
            Helpers::error($message);
        } 
        $data['short_url'] = $payment_data['short_url'];

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
            'url'                   => $payment_data['short_url'],
            'method'                => "get",
       ];
       $message = ['success'=>['Escrow Payment Gateway Captured Successful']];
       return ApiResponse::success($message, $data);  
    }
}
