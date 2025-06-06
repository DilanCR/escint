<?php

namespace App\Traits\EscrowPaymentGateway;
 
use App\Constants\PaymentGatewayConst; 
use Carbon\Carbon;
use Exception; 
use Illuminate\Support\Str;
use App\Http\Helpers\Api\Helpers as ApiResponse;  


trait SslcommerzTrait
{
    public function sslcommerzInit($escrow_data = null) { 
        if(!$escrow_data) $escrow_data = $this->request_data->data;
           $credentials                = $this->getSslCredentials($escrow_data->gateway_currency);
           $reference                  = generateTransactionReference();
           $amount                     = $escrow_data->escrow->buyer_amount ? number_format($escrow_data->escrow->buyer_amount,2,'.','') : 0;
           $currency                   = $escrow_data->gateway_currency->currency_code??"BDT";

        if(auth()->guard(get_auth_guard())->check()){
            $user       = auth()->guard(get_auth_guard())->user();
            $user_email = $user->email;
            $user_phone = $user->full_mobile ?? '';
            $user_name  = $user->firstname.' '.$user->lastname ?? '';
        }
        if (isset($escrow_data->payment_type) && $escrow_data->payment_type == "approvalPending") {
            $returnSuccessUrl = route('escrow-action.ssl.success');
            $returnFailUrl    = route('escrow-action.ssl.fail');
            $returnCancelUrl  = route('escrow-action.ssl.cancel');
            $tran_id          = session()->get('identifier');
        }else {
            $returnSuccessUrl = route('my-escrow.ssl.success');
            $returnFailUrl    = route('my-escrow.ssl.fail');
            $returnCancelUrl  = route('my-escrow.ssl.cancel');
            $tran_id          = $escrow_data->trx;
        }
        $post_data                 = array();
        $post_data['store_id']     = $credentials->store_id??"";
        $post_data['store_passwd'] = $credentials->store_password??"";
        $post_data['total_amount'] = $amount;
        $post_data['currency']     = $currency;
        $post_data['tran_id']      = $tran_id;
        $post_data['success_url']  = $returnSuccessUrl;
        $post_data['fail_url']     = $returnFailUrl;
        $post_data['cancel_url']   = $returnCancelUrl;
        # $post_data['multi_card_name'] = "mastercard,visacard,amexcard";  # DISABLE TO DISPLAY ALL AVAILABLE

        # EMI INFO
        $post_data['emi_option']          = "1";
        $post_data['emi_max_inst_option'] = "9";
        $post_data['emi_selected_inst']   = "9";

        # CUSTOMER INFORMATION
        $post_data['cus_name']     = $user->fullname??"Test Customer";
        $post_data['cus_email']    = $user->email??"test@test.com";
        $post_data['cus_add1']     = $user->address->country??"Dhaka";
        $post_data['cus_add2']     = $user->address->address??"Dhaka";
        $post_data['cus_city']     = $user->address->city??"Dhaka";
        $post_data['cus_state']    = $user->address->state??"Dhaka";
        $post_data['cus_postcode'] = $user->address->zip??"1000";
        $post_data['cus_country']  = $user->address->country??"Bangladesh";
        $post_data['cus_phone']    = $user->full_mobile??"01711111111";
        $post_data['cus_fax']      = "";



        # PRODUCT INFORMATION
        $post_data['product_name']     = "Escrow Create";
        $post_data['product_category'] = "Escrow Create";
        $post_data['product_profile']  = "Escrow Create";
        # SHIPMENT INFORMATION
        $post_data['shipping_method'] = "NO";

         $data = [
            'request_data' => $post_data,
            'amount'       => $amount,
            'email'        => $user_email,
            'tx_ref'       => $reference,
            'currency'     => $currency,
            'customer'     => [
                'email'        => $user_email,
                "phone_number" => $user_phone,
                "name"         => $user_name
            ],
            "customizations" => [
                "title"       => "Escrow Create",
                "description" => dateFormat('d M Y', Carbon::now()),
            ]
        ];

        if( $credentials->mode == Str::lower(PaymentGatewayConst::ENV_SANDBOX)){
            $link_url = $credentials->sandbox_url;
        }else{
            $link_url = $credentials->live_url;
        }
        # REQUEST SEND TO SSLCOMMERZ
        $direct_api_url = $link_url."/gwprocess/v4/api.php";

        $handle = curl_init();
        curl_setopt($handle, CURLOPT_URL, $direct_api_url );
        curl_setopt($handle, CURLOPT_TIMEOUT, 30);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($handle, CURLOPT_POST, 1 );
        curl_setopt($handle, CURLOPT_POSTFIELDS, http_build_query($post_data));
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, FALSE); # KEEP IT FALSE IF YOU RUN FROM LOCAL PC


        $content = curl_exec($handle );
        $result  = json_decode( $content,true);
        if( $result['status']  != "SUCCESS"){
            throw new Exception($result['failedreason']);
        }
          // $this->sslJunkInsert($data);
        return redirect($result['GatewayPageURL']);

    }

    public function getSslCredentials($escrow_data) {
        $gateway = $escrow_data->gateway ?? null;
        if(!$gateway) throw new Exception("Payment gateway not available");
        $store_id_sample       = ['store_id','Store Id','store-id'];
        $store_password_sample = ['Store Password','store-password','store_password'];
        $sandbox_url_sample    = ['Sandbox Url','sandbox-url','sandbox_url'];
        $live_url_sample       = ['Live Url','live-url','live_url'];

        $store_id = '';
        $outer_break = false;
        foreach($store_id_sample as $item) {
            if($outer_break == true) {
                break;
            }
            $modify_item = $this->sllPlainText($item);
            foreach($gateway->credentials ?? [] as $gatewayInput) {
                $label = $gatewayInput->label ?? "";
                $label = $this->sllPlainText($label);

                if($label == $modify_item) {
                    $store_id = $gatewayInput->value ?? "";
                    $outer_break = true;
                    break;
                }
            }
        }


        $store_password = '';
        $outer_break = false;
        foreach($store_password_sample as $item) {
            if($outer_break == true) {
                break;
            }
            $modify_item = $this->sllPlainText($item);
            foreach($gateway->credentials ?? [] as $gatewayInput) {
                $label = $gatewayInput->label ?? "";
                $label = $this->sllPlainText($label);

                if($label == $modify_item) {
                    $store_password = $gatewayInput->value ?? "";
                    $outer_break = true;
                    break;
                }
            }
        }
        $sandbox_url = '';
        $outer_break = false;
        foreach($sandbox_url_sample as $item) {
            if($outer_break == true) {
                break;
            }
            $modify_item = $this->sllPlainText($item);
            foreach($gateway->credentials ?? [] as $gatewayInput) {
                $label = $gatewayInput->label ?? "";
                $label = $this->sllPlainText($label);

                if($label == $modify_item) {
                    $sandbox_url = $gatewayInput->value ?? "";
                    $outer_break = true;
                    break;
                }
            }
        }
        $live_url = '';
        $outer_break = false;
        foreach($live_url_sample as $item) {
            if($outer_break == true) {
                break;
            }
            $modify_item = $this->sllPlainText($item);
            foreach($gateway->credentials ?? [] as $gatewayInput) {
                $label = $gatewayInput->label ?? "";
                $label = $this->sllPlainText($label);

                if($label == $modify_item) {
                    $live_url = $gatewayInput->value ?? "";
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
            'store_id'       => $store_id,
            'store_password' => $store_password,
            'sandbox_url'    => $sandbox_url,
            'live_url'       => $live_url,
            'mode'           => $mode,

        ];

    } 
    public function sllPlainText($string) {
        $string = Str::lower($string);
        return preg_replace("/[^A-Za-z0-9]/","",$string);
    } 
    //for api
    public function sslcommerzInitApi($escrow_data = null) {
        if(!$escrow_data) $escrow_data = $this->request_data->data;
           $credentials                = $this->getSslCredentials($escrow_data->gateway_currency);
           $reference                  = generateTransactionReference();
           $amount                     = $escrow_data->escrow->buyer_amount ? number_format($escrow_data->escrow->buyer_amount,2,'.','') : 0;
           $currency                   = $escrow_data->gateway_currency->currency_code??"BDT";

        if(auth()->guard(get_auth_guard())->check()){
            $user = auth()->guard(get_auth_guard())->user();
            $user_email = $user->email;
            $user_phone = $user->full_mobile ?? '';
            $user_name = $user->firstname.' '.$user->lastname ?? '';
        }
        if (isset($escrow_data->payment_type) && $escrow_data->payment_type == "approvalPending") {
            $returnSuccessUrl = route('api-escrow-action.ssl.success');
            $returnFailUrl    = route('api-escrow-action.ssl.fail');
            $returnCancelUrl  = route('api-escrow-action.ssl.cancel');
            $tran_id          = $escrow_data->trx;
        }else {
            $returnSuccessUrl = route('api.my-escrow.ssl.success',"r-source=".PaymentGatewayConst::APP);
            $returnFailUrl    = route('api.my-escrow.ssl.fail',"r-source=".PaymentGatewayConst::APP);
            $returnCancelUrl  = route('api.my-escrow.ssl.cancel',"r-source=".PaymentGatewayConst::APP);
            $tran_id          = $escrow_data->trx;
        }
        $post_data = array();
        $post_data['store_id'] = $credentials->store_id??"";
        $post_data['store_passwd'] = $credentials->store_password??"";
        $post_data['total_amount'] =$amount;
        $post_data['currency'] = $currency;
        $post_data['tran_id'] =  $tran_id ;
        $post_data['success_url'] = $returnSuccessUrl;
        $post_data['fail_url'] = $returnFailUrl;
        $post_data['cancel_url'] = $returnCancelUrl;
        # $post_data['multi_card_name'] = "mastercard,visacard,amexcard";  # DISABLE TO DISPLAY ALL AVAILABLE

        # EMI INFO
        $post_data['emi_option'] = "1";
        $post_data['emi_max_inst_option'] = "9";
        $post_data['emi_selected_inst'] = "9";

        # CUSTOMER INFORMATION
        $post_data['cus_name'] = $user->fullname??"Test Customer";
        $post_data['cus_email'] = $user->email??"test@test.com";
        $post_data['cus_add1'] = $user->address->country??"Dhaka";
        $post_data['cus_add2'] = $user->address->address??"Dhaka";
        $post_data['cus_city'] = $user->address->city??"Dhaka";
        $post_data['cus_state'] = $user->address->state??"Dhaka";
        $post_data['cus_postcode'] = $user->address->zip??"1000";
        $post_data['cus_country'] = $user->address->country??"Bangladesh";
        $post_data['cus_phone'] = $user->full_mobile??"01711111111";
        $post_data['cus_fax'] = "";



        # PRODUCT INFORMATION
        $post_data['product_name']     = "Escrow Create";
        $post_data['product_category'] = "Escrow Create";
        $post_data['product_profile']  = "Escrow Create";
        # SHIPMENT INFORMATION
        $post_data['shipping_method'] = "NO";

         $data = [
            'request_data'    => $post_data,
            'amount'          => $amount,
            'email'           => $user_email,
            'tx_ref'          => $reference,
            'currency'        =>  $currency,
            'customer'        => [
                'email'        => $user_email,
                "phone_number" => $user_phone,
                "name"         => $user_name
            ],
            "customizations" => [
                "title"       => "Add Money",
                "description" => dateFormat('d M Y', Carbon::now()),
            ]
        ];

        if( $credentials->mode == Str::lower(PaymentGatewayConst::ENV_SANDBOX)){
            $link_url =  $credentials->sandbox_url;
        }else{
            $link_url =  $credentials->live_url;
        }
        # REQUEST SEND TO SSLCOMMERZ
        $direct_api_url = $link_url."/gwprocess/v4/api.php";

        $handle = curl_init();
        curl_setopt($handle, CURLOPT_URL, $direct_api_url );
        curl_setopt($handle, CURLOPT_TIMEOUT, 30);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($handle, CURLOPT_POST, 1 );
        curl_setopt($handle, CURLOPT_POSTFIELDS, http_build_query($post_data));
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, FALSE); # KEEP IT FALSE IF YOU RUN FROM LOCAL PC


        $content = curl_exec($handle );
        $result = json_decode( $content,true);
        if( $result['status']  != "SUCCESS"){
            throw new Exception($result['failedreason']);
        }

        $data['link'] = $result['GatewayPageURL'];
        $data['trx'] =  $reference;

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
            'method'                => "post",
       ];
       $message = ['success'=>['Escrow Payment Gateway Captured Successful']];
       return ApiResponse::success($message, $data);  
    }

}
