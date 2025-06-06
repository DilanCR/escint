<?php

namespace App\Traits\EscrowPaymentGateway;

use Exception; 
use Illuminate\Support\Str; 
use App\Models\TemporaryData;
use App\Http\Helpers\Pagadito; 
use App\Http\Helpers\Api\Helpers; 
use App\Constants\PaymentGatewayConst;
use Illuminate\Support\Facades\Config; 
use App\Providers\Admin\BasicSettingsProvider;  
use App\Http\Helpers\Api\Helpers as ApiResponse; 


trait PagaditoTrait
{
    public function pagaditoInit($escrow_data = null) {
        $basic_settings = BasicSettingsProvider::get();
        if(!$escrow_data) $escrow_data = $this->request_data->data;
        $credentials = $this->getPagaditoCredentials($escrow_data->gateway_currency);
        $this->pagaditoSetSecreteKey($credentials);
        $uid = $credentials->uid;
        $wsk = $credentials->wsk;
        $mode = $credentials->mode;
        $Pagadito = new Pagadito($uid,$wsk,$credentials,$escrow_data->gateway_currency->currency_code ?? '');
        $Pagadito->config( $credentials,$escrow_data->gateway_currency->currency_code ?? '');

        if ($mode == "sandbox") {
            $Pagadito->mode_sandbox_on();
        }
        $title = 'Wallet Add';
        if ($Pagadito->connect()) {
            $Pagadito->add_detail(1,"Please Pay For ".$basic_settings->site_name." ".$title. " Balance", $escrow_data->escrow->buyer_amount ? number_format($escrow_data->escrow->buyer_amount,2,'.','') : 0);
            $Pagadito->set_custom_param("param1", "Valor de param1");
            $Pagadito->set_custom_param("param2", "Valor de param2");
            $Pagadito->set_custom_param("param3", "Valor de param3");
            $Pagadito->set_custom_param("param4", "Valor de param4");
            $Pagadito->set_custom_param("param5", "Valor de param5");

            $Pagadito->enable_pending_payments();
            $getUrls = (object)$Pagadito->exec_trans($Pagadito->get_rs_code());

            if($getUrls->code == "PG1002" ){
                $parts = parse_url($getUrls->value);
                parse_str($parts['query'], $query);
                // Extract the token value
                if (isset($query['token'])) {
                    $tokenValue = $query['token'];
                } else {
                    $tokenValue = '';
                } 
                if (isset($escrow_data->payment_type) && $escrow_data->payment_type == "approvalPending") {
                    $this->pagaditoJunkUpdateApprovalPending($escrow_data,$tokenValue,"escrowApprovalPendingweb");
                }else{
                    $this->pagaditoJunkUpdate($escrow_data,$tokenValue,"escrowcreateweb");
                }
 
                return redirect($getUrls->value);

            }
            $ern = rand(1000, 2000);
            if (!$Pagadito->exec_trans($ern)) {
                switch($Pagadito->get_rs_code())
                {
                    case "PG2001":
                        /*Incomplete data*/
                    case "PG3002":
                        /*Error*/
                    case "PG3003":
                        /*Unregistered transaction*/
                    case "PG3004":
                        /*Match error*/
                    case "PG3005":
                        /*Disabled connection*/
                    default:
                        throw new Exception($Pagadito->get_rs_code().": ".$Pagadito->get_rs_message());
                        break;
                }
            }

            return redirect($Pagadito->exec_trans($Pagadito->get_rs_code()));
        } else {
            switch($Pagadito->get_rs_code())
            {
                case "PG2001":
                    /*Incomplete data*/
                case "PG3001":
                    /*Problem connection*/
                case "PG3002":
                    /*Error*/
                case "PG3003":
                    /*Unregistered transaction*/
                case "PG3005":
                    /*Disabled connection*/
                case "PG3006":
                    /*Exceeded*/
                default:
                    throw new Exception($Pagadito->get_rs_code().": ".$Pagadito->get_rs_message());
                    break;
            }

        }


    }
    // Get Pagadito credentials
    public function getPagaditoCredentials($escrow_data) {
        $gateway = $escrow_data->gateway ?? null;
        if(!$gateway) throw new Exception("Payment gateway not available");

        $uid_sample = ['UID','uid','u_id'];
        $wsk_sample = ['WSK','wsk','w_sk'];
        $live_base_url_sample = ['Live Base URL','live_base_url','live-base-url', 'live base url'];
        $sandbox_base_url_sample = ['Sandbox Base URL','sandbox_base_url','sandbox-base-url', 'sandbox base url'];

        $uid = '';
        $outer_break = false;
        foreach($uid_sample as $item) {
            if($outer_break == true) {
                break;
            }
            $modify_item = $this->pagaditoPlainText($item);
            foreach($gateway->credentials ?? [] as $gatewayInput) {
                $label = $gatewayInput->label ?? "";
                $label = $this->pagaditoPlainText($label);
                if($label == $modify_item) {
                    $uid = $gatewayInput->value ?? "";
                    $outer_break = true;
                    break;
                }
            }
        }

        $wsk = '';
        $outer_break = false;
        foreach($wsk_sample as $item) {
            if($outer_break == true) {
                break;
            }
            $modify_item = $this->pagaditoPlainText($item);
            foreach($gateway->credentials ?? [] as $gatewayInput) {
                $label = $gatewayInput->label ?? "";
                $label = $this->pagaditoPlainText($label);

                if($label == $modify_item) {
                    $wsk = $gatewayInput->value ?? "";
                    $outer_break = true;
                    break;
                }
            }
        }

        $base_url_live = '';
        $outer_break = false;
        foreach($live_base_url_sample as $item) {
            if($outer_break == true) {
                break;
            }
            $modify_item = $this->pagaditoPlainText($item);
            foreach($gateway->credentials ?? [] as $gatewayInput) {
                $label = $gatewayInput->label ?? "";
                $label = $this->pagaditoPlainText($label);

                if($label == $modify_item) {
                    $base_url_live = $gatewayInput->value ?? "";
                    $outer_break = true;
                    break;
                }
            }
        }

        $base_url_sandbox = '';
        $outer_break = false;
        foreach($sandbox_base_url_sample as $item) {
            if($outer_break == true) {
                break;
            }
            $modify_item = $this->pagaditoPlainText($item);
            foreach($gateway->credentials ?? [] as $gatewayInput) {
                $label = $gatewayInput->label ?? "";
                $label = $this->pagaditoPlainText($label);

                if($label == $modify_item) {
                    $base_url_sandbox = $gatewayInput->value ?? "";
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

        switch ($mode) {
            case 'sandbox':
                $base_url = $base_url_sandbox;
                break;

            default:
                $base_url = $base_url_live;
                break;
        }

        return (object) [
            'uid'      => $uid,
            'wsk'      => $wsk,
            'base_url' => $base_url,
            'mode'     => $mode,
        ];

    }

    public function pagaditoPlainText($string) {
        $string = Str::lower($string);
        return preg_replace("/[^A-Za-z0-9]/","",$string);
    }

    public function pagaditoSetSecreteKey($credentials){
        Config::set('pagadito.UID',$credentials->uid);
        Config::set('pagadito.WSK',$credentials->wsk);
        if($credentials->mode == "sandbox"){
            Config::set('pagadito.SANDBOX',true);
        }else{
            Config::set('pagadito.SANDBOX',false);
        }

    } 
    public function pagaditoJunkUpdate($escrow_data,$tokenValue,$env) {
        $tempData = TemporaryData::where('identifier',$escrow_data->trx)->first();
        
        if($tempData != null) {
            $update_temp_data = json_decode(json_encode($tempData->data),true);
            $update_temp_data['env_type'] = $env;
        }

        $tempData->data = $update_temp_data;
        $tempData->identifier = $tokenValue;
        $tempData->type = PaymentGatewayConst::PAGADITO;
        $tempData->save();  
        return $tempData;
    }
    public function pagaditoJunkUpdateApprovalPending($escrow_data,$tokenValue,$env) { 
        $tempData = TemporaryData::where('identifier',$escrow_data->identifier)->first();
        
        if($tempData != null) {
            $update_temp_data = json_decode(json_encode($tempData->data),true);
            $update_temp_data['env_type'] = $env;
        }

        $tempData->data = $update_temp_data;
        $tempData->identifier = $tokenValue;
        $tempData->type = PaymentGatewayConst::PAGADITO;
        $tempData->save();  
        return $tempData;
    }
     // ********* For API **********
     public function pagaditoInitApi($escrow_data = null) {
        $basic_settings = BasicSettingsProvider::get();
        if(!$escrow_data) $escrow_data = $this->request_data->data;
        $credentials = $this->getPagaditoCredentials($escrow_data->gateway_currency);
        $this->pagaditoSetSecreteKey($credentials);
        $uid = $credentials->uid;
        $wsk = $credentials->wsk;
        $mode = $credentials->mode;
        $Pagadito = new Pagadito($uid,$wsk,$credentials,$escrow_data->gateway_currency->currency_code ?? '');
        $Pagadito->config( $credentials,$escrow_data->gateway_currency->currency_code ?? '');

        if ($mode == "sandbox") {
            $Pagadito->mode_sandbox_on();
        }
        $title = 'Wallet Add';
        if ($Pagadito->connect()) {
            $Pagadito->add_detail(1,"Please Pay For ".$basic_settings->site_name." ".$title." Balance", $escrow_data->escrow->buyer_amount ? number_format($escrow_data->escrow->buyer_amount,2,'.','') : 0);
            $Pagadito->set_custom_param("param1", "Valor de param1");
            $Pagadito->set_custom_param("param2", "Valor de param2");
            $Pagadito->set_custom_param("param3", "Valor de param3");
            $Pagadito->set_custom_param("param4", "Valor de param4");
            $Pagadito->set_custom_param("param5", "Valor de param5");

            $Pagadito->enable_pending_payments();
            $getUrls = (object)$Pagadito->exec_trans($Pagadito->get_rs_code());

            if($getUrls->code == "PG1002" ){
                $parts = parse_url($getUrls->value);
                parse_str($parts['query'], $query);
                // Extract the token value
                if (isset($query['token'])) {
                    $tokenValue = $query['token'];
                } else {
                    $tokenValue = '';
                }
                
                if (isset($escrow_data->payment_type) && $escrow_data->payment_type == "approvalPending") { 
                    $this->pagaditoJunkUpdateApprovalPending($escrow_data,$tokenValue,"escrowApprovalPendingapi");
                }else{
                    $this->pagaditoJunkUpdate($escrow_data,$tokenValue,"escrowcreateapi");
                } 

                $payment_informations = [
                    'trx'                   => $tokenValue,
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
                    'url'                   => $getUrls->value,
                    'method'                => "get",
               ];
               $message = ['success'=>['Escrow Payment Gateway Captured Successful']];
               return ApiResponse::success($message, $data); 

            }
            $ern = rand(1000, 2000);
            if (!$Pagadito->exec_trans($ern)) {
                switch($Pagadito->get_rs_code())
                {
                    case "PG2001":
                        /*Incomplete data*/
                    case "PG3002":
                        /*Error*/
                    case "PG3003":
                        /*Unregistered transaction*/
                    case "PG3004":
                        /*Match error*/
                    case "PG3005":
                        /*Disabled connection*/
                    default:
                    $message = ['error' => [$Pagadito->get_rs_code().": ".$Pagadito->get_rs_message()]];
                    Helpers::error($message);
                        break;
                }
            }

            return redirect($Pagadito->exec_trans($Pagadito->get_rs_code()));
        } else {
            switch($Pagadito->get_rs_code())
            {
                case "PG2001":
                    /*Incomplete data*/
                case "PG3001":
                    /*Problem connection*/
                case "PG3002":
                    /*Error*/
                case "PG3003":
                    /*Unregistered transaction*/
                case "PG3005":
                    /*Disabled connection*/
                case "PG3006":
                    /*Exceeded*/
                default:
                    $message = ['error' => [$Pagadito->get_rs_code().": ".$Pagadito->get_rs_message()]];
                    Helpers::error($message);
                    break;
            }

        }
    }
}
