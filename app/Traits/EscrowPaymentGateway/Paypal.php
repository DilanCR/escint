<?php

namespace App\Traits\EscrowPaymentGateway;
 
use App\Constants\PaymentGatewayConst; 
use App\Http\Helpers\Api\Helpers as ApiResponse; 
use Exception; 
use Srmklive\PayPal\Services\PayPal as PayPalClient;
use Illuminate\Support\Str; 

trait Paypal
{
    public function paypalInit($escrow_data = null) { 
        if(!$escrow_data) $escrow_data = $this->request_data->data;
        $credentials    = $this->getPaypalCredentials($escrow_data->gateway_currency);
        $config         = $this->paypalConfig($credentials,$escrow_data->gateway_currency);
        $paypalProvider = new PayPalClient;
        $paypalProvider->setApiCredentials($config);
        $paypalProvider->getAccessToken();
        if (isset($escrow_data->payment_type) && $escrow_data->payment_type == "approvalPending") {
            $returnUrl = route('user.escrow-action.payment.approval.success',PaymentGatewayConst::PAYPAL);
        }else {
            $returnUrl = route('user.my-escrow.payment.success',['gateway' => PaymentGatewayConst::PAYPAL, 'trx' => $escrow_data->trx]);
        }
        $response = $paypalProvider->createOrder([
            "intent"              => "CAPTURE",
            "application_context" => [
                "return_url" => $returnUrl,
                "cancel_url" => route('user.my-escrow.index',PaymentGatewayConst::PAYPAL),
            ],
            "purchase_units" => [
                0 => [
                    "amount" => [
                        "currency_code" => $escrow_data->gateway_currency->currency_code ?? '',
                        "value"         => $escrow_data->escrow->buyer_amount ? number_format($escrow_data->escrow->buyer_amount,2,'.','') : 0,
                    ]
                ]
            ]
        ]); 
        if(isset($response['id']) && $response['id'] != "" && isset($response['status']) && $response['status'] == "CREATED" && isset($response['links']) && is_array($response['links'])) {
            foreach($response['links'] as $item) {
                if($item['rel'] == "approve") {
                    return redirect()->away($item['href']);
                    break;
                }
            }
        }

        if(isset($response['error']) && is_array($response['error'])) {
            throw new Exception($response['error']['message']);
        }

        throw new Exception("Something went worng! Please try again.");
    }
    public function paypalInitApi($escrow_data = null) {  
        if(!$escrow_data) $escrow_data = $this->request_data->data;
           $credentials                = $this->getPaypalCredentials($escrow_data->gateway_currency);
           $config                     = $this->paypalConfig($credentials,$escrow_data->gateway_currency);
           $paypalProvider             = new PayPalClient;
            $paypalProvider->setApiCredentials($config);
            $paypalProvider->getAccessToken();
            if (isset($escrow_data->payment_type) && $escrow_data->payment_type == "approvalPending") {
                $returnUrl = route('api.v1.api-escrow-action.payment.approval.success',['gateway' => PaymentGatewayConst::PAYPAL, 'trx' => $escrow_data->trx],"?r-source=".PaymentGatewayConst::APP);
            }else {
                $returnUrl = route('api.v1.my-escrow.payment.success',['gateway' => PaymentGatewayConst::PAYPAL, 'trx' => $escrow_data->trx],"?r-source=".PaymentGatewayConst::APP);
            }
        $response = $paypalProvider->createOrder([
            "intent" => "CAPTURE",
            "application_context" => [
                "return_url" => $returnUrl,
                "cancel_url" => route('api.v1.user.my-escrow.index',PaymentGatewayConst::PAYPAL."?r-source=".PaymentGatewayConst::APP),
            ],
            "purchase_units" => [
                0 => [
                    "amount" => [
                        "currency_code" => $escrow_data->gateway_currency->currency_code ?? '',
                        "value"         => $escrow_data->escrow->buyer_amount ? number_format($escrow_data->escrow->buyer_amount,2,'.','') : 0,
                    ]
                ]
            ]
        ]);  
        if(isset($response['id']) && $response['id'] != "" && isset($response['status']) && $response['status'] == "CREATED" && isset($response['links']) && is_array($response['links'])) {
            foreach($response['links'] as $item) {
                if($item['rel'] == "approve") { 
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
                        'url'                   => $response['links'],
                        'method'                => "get",
                   ];
                   $message = ['success'=>['Escrow Payment Gateway Captured Successful']];
                   return ApiResponse::success($message, $data); 
                    break;
                }
            }
        }

        if(isset($response['error']) && is_array($response['error'])) {
            throw new Exception($response['error']['message']);
        }

        $message = ['error' => ["Something went wrong"]];
        return ApiResponse::onlyError($message); 
    }

    public function getPaypalCredentials($escrow_data) { 
        $gateway = $escrow_data->gateway ?? null;
        if(!$gateway) throw new Exception("Payment gateway not available");
        $client_id_sample     = ['api key','api_key','client id','primary key'];
        $client_secret_sample = ['client_secret','client secret','secret','secret key','secret id'];
        $client_id            = '';
        $outer_break          = false;
        foreach($client_id_sample as $item) {
            if($outer_break == true) {
                break;
            }
            $modify_item = $this->paypalPlainText($item);
            foreach($gateway->credentials ?? [] as $gatewayInput) {
                $label = $gatewayInput->label ?? "";
                $label = $this->paypalPlainText($label);
                if($label == $modify_item) {
                    $client_id   = $gatewayInput->value ?? "";
                    $outer_break = true;
                    break;
                }
            }
        }
        $secret_id   = '';
        $outer_break = false;
        foreach($client_secret_sample as $item) {
            if($outer_break == true) {
                break;
            }
            $modify_item = $this->paypalPlainText($item);
            foreach($gateway->credentials ?? [] as $gatewayInput) {
                $label = $gatewayInput->label ?? "";
                $label = $this->paypalPlainText($label);
                if($label == $modify_item) {
                    $secret_id   = $gatewayInput->value ?? "";
                    $outer_break = true;
                    break;
                }
            }
        }
        $mode = $gateway->env;

        $paypal_register_mode = [
            PaymentGatewayConst::ENV_SANDBOX    => "sandbox",
            PaymentGatewayConst::ENV_PRODUCTION => "live",
        ];
        if(array_key_exists($mode,$paypal_register_mode)) {
            $mode = $paypal_register_mode[$mode];
        }else {
            $mode = "sandbox";
        }
        return (object) [
            'client_id'     => $client_id,
            'client_secret' => $secret_id,
            'mode'          => $mode,
        ];
    }

    public function paypalPlainText($string) {
        $string = Str::lower($string);
        return preg_replace("/[^A-Za-z0-9]/","",$string);
    }


    public static function paypalConfig($credentials, $gateway_currency)
    {
        $config = [
            'mode'    => $credentials->mode ?? 'sandbox',
            'sandbox' => [
                'client_id'     => $credentials->client_id ?? "",
                'client_secret' => $credentials->client_secret ?? "",
                'app_id'        => "APP-80W284485P519543T",
            ],
            'live' => [
                'client_id'     => $credentials->client_id ?? "",
                'client_secret' => $credentials->client_secret ?? "",
                'app_id'        => "",
            ],
            'payment_action' => 'Sale',                                   // Can only be 'Sale', 'Authorization' or 'Order'
            'currency'       => $gateway_currency->currency_code ?? "",
            'notify_url'     => "",                                       // Change this accordingly for your application.
            'locale'         => 'en_US',                                  // force gateway language  i.e. it_IT, es_ES, en_US ... (for express checkout only)
            'validate_ssl'   => true,                                     // Validate SSL when creating api client.
        ];
        return $config;
    }

    public function paypalSuccess($output = null) {
        if(!$output) $output = $this->output;
           $token            = $this->output['tempData']['identifier'] ?? "";

        $credentials    = $this->getPaypalCredentials($output);
        $config         = $this->paypalConfig($credentials,$output['amount']);
        $paypalProvider = new PayPalClient;
        $paypalProvider->setApiCredentials($config);
        $paypalProvider->getAccessToken();
        $response = $paypalProvider->capturePaymentOrder($token);

        if(isset($response['status']) && $response['status'] == 'COMPLETED') {
            return $this->paypalPaymentCaptured($response,$output);
        }else {
            throw new Exception('Transaction faild. Payment captured faild.');
        }

        if(empty($token)) throw new Exception('Transaction faild. Record didn\'t saved properly. Please try again.');
    }
}
