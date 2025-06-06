<?php

namespace App\Traits\EscrowPaymentGateway;

use Exception;  
use Illuminate\Support\Str; 
use Illuminate\Support\Facades\Http;
use App\Constants\PaymentGatewayConst; 
use App\Http\Helpers\Api\Helpers as ApiResponse; 

trait QrpayTrait
{ 

    public function qrpayInit($escrow_data = null)
    { 
        if(!$escrow_data) $escrow_data = $this->request_data->data;
        $credentials = $this->getQrpayCredetials($escrow_data->gateway_currency);

        $access = $this->accessTokenQrpay($credentials);
        $identifier = generate_unique_string("transactions", "trx_id", 16);
 

        if (isset($escrow_data->payment_type) && $escrow_data->payment_type == "approvalPending") {
            $returnUrl = route('user.escrow-action.payment.approval.success',PaymentGatewayConst::QRPAY);
        }else {
            $returnUrl = route('user.my-escrow.qrpay.success',['gateway' => PaymentGatewayConst::QRPAY, 'trx' => $escrow_data->trx]);
        }
        $cancel_url = route('user.my-escrow.qrpay.cancel', $escrow_data->trx);

        $token = $access->data->access_token;
        // Payment Url Request

        $amount = $escrow_data->escrow->buyer_amount ? number_format($escrow_data->escrow->buyer_amount,2,'.','') : 0;

        if (PaymentGatewayConst::ENV_SANDBOX == $credentials->mode) {
            $base_url = $credentials->base_url_sandbox;
        } elseif (PaymentGatewayConst::ENV_PRODUCTION == $credentials->mode) {
            $base_url = $credentials->base_url_production;
        }

        $response = Http::withToken($token)->post($base_url . '/payment/create', [
            'amount'     => $amount,
            'currency'   => $escrow_data->gateway_currency->currency_code ?? '',
            'return_url' => $returnUrl,
            'cancel_url' => $cancel_url,
            'custom'   => $escrow_data->trx,
        ]);


        $statusCode = $response->getStatusCode();
        $content    = json_decode($response->getBody()->getContents());

        if ($content->type == 'error') {
            $errors = implode($content->message->error);
            throw new Exception($errors);
        } 
        return redirect()->away($content->data->payment_url);
    }
    // ********* For API **********
    public function qrpayInitApi($escrow_data = null)
    { 
        if(!$escrow_data) $escrow_data = $this->request_data->data;

        $credentials = $this->getQrpayCredetials($escrow_data->gateway_currency);
        $access = $this->accessTokenQrpay($credentials);
        $identifier = generate_unique_string("transactions", "trx_id", 16);


        if (isset($escrow_data->payment_type) && $escrow_data->payment_type == "approvalPending") {
            $returnUrl = route('api.v1.api-escrow-action.payment.approval.success.qrpay',['gateway' => PaymentGatewayConst::QRPAY, 'trx' => $escrow_data->trx],"r-source=".PaymentGatewayConst::APP);
        }else {
            $returnUrl = route('api.v1.my-escrow.qrpay.payment.success',['gateway' => PaymentGatewayConst::QRPAY, 'trx' => $escrow_data->trx],"?r-source=".PaymentGatewayConst::APP);
        }
        $cancel_url = route('api.v1.my-escrow.payment.cancel', $escrow_data->trx);

        $token = $access->data->access_token;
        // Payment Url Request

        $amount = $escrow_data->escrow->buyer_amount ? number_format($escrow_data->escrow->buyer_amount,2,'.','') : 0;

        if (PaymentGatewayConst::ENV_SANDBOX == $credentials->mode) {
            $base_url = $credentials->base_url_sandbox;
        } elseif (PaymentGatewayConst::ENV_PRODUCTION == $credentials->mode) {
            $base_url = $credentials->base_url_production;
        }


        $response = Http::withToken($token)->post($base_url . '/payment/create', [
            'amount'     => $amount,
            'currency'   => $escrow_data->gateway_currency->currency_code ?? '',
            'return_url' => $returnUrl,
            'cancel_url' => $cancel_url,
            'custom'   => $escrow_data->trx,
        ]);

        $statusCode = $response->getStatusCode();
        $content    = json_decode($response->getBody()->getContents());

        if ($content->type == 'error') {
            $errors = implode($content->message->error);
            throw new Exception($errors);
        }
        $data['link'] = $content->data->payment_url; 
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
    public function getQrpayCredetials($escrow_data)
    {
        $gateway = $escrow_data->gateway ?? null;

        if (!$gateway) throw new Exception("Payment gateway not available");
        $client_id_sample = ['api key', 'api_key', 'client id', 'primary key'];
        $client_secret_sample = ['client_secret', 'client secret', 'secret', 'secret key', 'secret id'];
        $base_url_sandbox = ['base_url', 'base url', 'base-url', 'url', 'base-url-sandbox', 'sandbox', 'sendbox-base-url', 'sandbox url'];
        $base_url_production = ['base_url', 'base url', 'base-url', 'url', 'base-url-production', 'production'. 'live-base-url', 'live base url','production url'];

        $client_id = '';
        $outer_break = false;
        foreach ($client_id_sample as $item) {
            if ($outer_break == true) {
                break;
            }
            $modify_item = $this->qrpayPlainText($item);
            foreach ($gateway->credentials ?? [] as $gatewayInput) {
                $label = $gatewayInput->label ?? "";
                $label = $this->qrpayPlainText($label);

                if ($label == $modify_item) {
                    $client_id = $gatewayInput->value ?? "";
                    $outer_break = true;
                    break;
                }
            }
        }


        $secret_id = '';
        $outer_break = false;
        foreach ($client_secret_sample as $item) {
            if ($outer_break == true) {
                break;
            }
            $modify_item = $this->qrpayPlainText($item);
            foreach ($gateway->credentials ?? [] as $gatewayInput) {
                $label = $gatewayInput->label ?? "";
                $label = $this->qrpayPlainText($label);

                if ($label == $modify_item) {
                    $secret_id = $gatewayInput->value ?? "";
                    $outer_break = true;
                    break;
                }
            }
        }

        $sandbox_url = '';
        $outer_break = false;
        foreach ($base_url_sandbox as $item) {
            if ($outer_break == true) {
                break;
            }
            $modify_item = $this->qrpayPlainText($item);
            foreach ($gateway->credentials ?? [] as $gatewayInput) {
                $label = $gatewayInput->label ?? "";
                $label = $this->qrpayPlainText($label);

                if ($label == $modify_item) {
                    $sandbox_url = $gatewayInput->value ?? "";
                    $outer_break = true;
                    break;
                }
            }
        }

        $production_url = '';
        $outer_break = false;
        foreach ($base_url_production as $item) {
            if ($outer_break == true) {
                break;
            }
            $modify_item = $this->qrpayPlainText($item);
            foreach ($gateway->credentials ?? [] as $gatewayInput) {
                $label = $gatewayInput->label ?? "";
                $label = $this->qrpayPlainText($label);

                if ($label == $modify_item) {
                    $production_url = $gatewayInput->value ?? "";
                    $outer_break = true;
                    break;
                }
            }
        }


        return (object) [
            'client_id'     => $client_id,
            'client_secret' => $secret_id,
            'base_url_sandbox' => $sandbox_url,
            'base_url_production' => $production_url,
            'mode'          => $gateway->env,

        ];
    }

    public function qrpayPlainText($string)
    {
        $string = Str::lower($string);
        return preg_replace("/[^A-Za-z0-9]/", "", $string);
    }

    public function accessTokenQrpay($credentials)
    {

        if (PaymentGatewayConst::ENV_SANDBOX == $credentials->mode) {
            $base_url = $credentials->base_url_sandbox;
        } elseif (PaymentGatewayConst::ENV_PRODUCTION == $credentials->mode) {
            $base_url = $credentials->base_url_production;
        }

        $response = Http::post($base_url . '/authentication/token', [
            'client_id' => $credentials->client_id,
            'secret_id' => $credentials->client_secret,
        ]);


        $statusCode = $response->getStatusCode();
        $content = $response->getBody()->getContents();

        if ($statusCode != 200) {
            throw new Exception("Access token capture failed");
        }

        return json_decode($content);
    }

}
