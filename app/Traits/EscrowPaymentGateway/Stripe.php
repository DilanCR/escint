<?php

namespace App\Traits\EscrowPaymentGateway;

use Exception; 
use Illuminate\Support\Str; 
use Illuminate\Support\Carbon; 
use App\Constants\PaymentGatewayConst; 
use App\Providers\Admin\BasicSettingsProvider;
use App\Http\Helpers\Api\Helpers as ApiResponse; 

trait Stripe
{
    public function stripeInit($escrow_data = null) {

        $basic_settings = BasicSettingsProvider::get();
        if(!$escrow_data) $escrow_data = $this->request_data->data;
        $credentials = $this->getStripeCredentials($escrow_data->gateway_currency);
        $reference = generateTransactionReference();
        $amount = $escrow_data->escrow->buyer_amount ? number_format($escrow_data->escrow->buyer_amount,2,'.','') : 0;
        $currency = $escrow_data->gateway_currency->currency_code ?? "";

        if(auth()->guard(get_auth_guard())->check()){
            $user = auth()->guard(get_auth_guard())->user();
            $user_email = $user->email;
            $user_phone = $user->full_mobile ?? '';
            $user_name = $user->firstname.' '.$user->lastname ?? '';
        }

        if (isset($escrow_data->payment_type) && $escrow_data->payment_type == "approvalPending") {
            $returnUrl = route('user.escrow-action.payment.approval.success',PaymentGatewayConst::STRIPE);
        }else {
            $returnUrl =  route('user.my-escrow.stripe.success',['gateway' => PaymentGatewayConst::STRIPE, 'trx' => $escrow_data->trx]);
        }

        $return_url = $returnUrl;

        // Enter the details of the payment
         $data = [
            'payment_options' => 'card',
            'amount'          => $amount,
            'email'           => $user_email,
            'tx_ref'          => $reference,
            'currency'        =>  $currency,
            'redirect_url'    => $return_url,
            'customer'        => [
                'email'        => $user_email,
                "phone_number" => $user_phone,
                "name"         => $user_name
            ],
            "customizations" => [
                "title"       => "Create Escrow",
                "description" => dateFormat('d M Y', Carbon::now()),
            ]
        ];

       //start stripe pay link
       $stripe = new \Stripe\StripeClient($credentials->secret_key);

       //create product for Product Id
       try{
            $product_id = $stripe->products->create([
                'name' => 'Create Escrow( '.$basic_settings->site_name.' )',
            ]);
       }catch(Exception $e){
            throw new Exception($e->getMessage());
       }
       //create price for Price Id
       try{
            $price_id =$stripe->prices->create([
                'currency' =>  $currency,
                'unit_amount' => $amount * 100,
                'product' => $product_id->id??""
            ]);
       }catch(Exception $e){
            throw new Exception("Something Is Wrong, Please Contact With Owner");
       }

       //create payment live links
       try{
            $payment_link = $stripe->paymentLinks->create([
                'line_items' => [
                [
                    'price' => $price_id->id,
                    'quantity' => 1,
                ],
                ],
                'after_completion' => [
                    'type' => 'redirect',
                    'redirect' => ['url' => $return_url],
                ],
            ]);
        }catch(Exception $e){
            throw new Exception("Something Is Wrong, Please Contact With Owner");
        }
 
        return redirect($payment_link->url."?prefilled_email=".@$user->email); 
    }

    public function getStripeCredentials($escrow_data) {
        $gateway = $escrow_data->gateway ?? null;
        if(!$gateway) throw new Exception("Payment gateway not available");
        $client_id_sample = ['publishable_key','publishable key','publishable-key'];
        $client_secret_sample = ['secret id','secret-id','secret_id'];

        $client_id = '';
        $outer_break = false;
        foreach($client_id_sample as $item) {
            if($outer_break == true) {
                break;
            }
            $modify_item = $this->stripePlainText($item);
            foreach($gateway->credentials ?? [] as $gatewayInput) {
                $label = $gatewayInput->label ?? "";
                $label = $this->stripePlainText($label);

                if($label == $modify_item) {
                    $client_id = $gatewayInput->value ?? "";
                    $outer_break = true;
                    break;
                }
            }
        }


        $secret_id = '';
        $outer_break = false;
        foreach($client_secret_sample as $item) {
            if($outer_break == true) {
                break;
            }
            $modify_item = $this->stripePlainText($item);
            foreach($gateway->credentials ?? [] as $gatewayInput) {
                $label = $gatewayInput->label ?? "";
                $label = $this->stripePlainText($label);

                if($label == $modify_item) {
                    $secret_id = $gatewayInput->value ?? "";
                    $outer_break = true;
                    break;
                }
            }
        }

        return (object) [
            'publish_key'     => $client_id,
            'secret_key' => $secret_id,

        ];

    } 
    public function stripePlainText($string) {
        $string = Str::lower($string);
        return preg_replace("/[^A-Za-z0-9]/","",$string);
    }
    //for api
    public function stripeInitApi($escrow_data = null) {
        $basic_settings = BasicSettingsProvider::get();
        if(!$escrow_data) $escrow_data = $this->request_data->data;
        $credentials = $this->getStripeCredentials($escrow_data->gateway_currency);
        $reference = generateTransactionReference();
        $amount = $escrow_data->escrow->buyer_amount ? number_format($escrow_data->escrow->buyer_amount,2,'.','') : 0;
        $currency = $escrow_data->gateway_currency->currency_code ?? "";


        if(auth()->guard(get_auth_guard())->check()){
            $user = auth()->guard(get_auth_guard())->user();
            $user_email = $user->email;
            $user_phone = $user->full_mobile ?? '';
            $user_name = $user->firstname.' '.$user->lastname ?? '';
        }
        if (isset($escrow_data->payment_type) && $escrow_data->payment_type == "approvalPending") {
            $returnUrl = route('api.v1.api-escrow-action.payment.approval.success',['gateway' => PaymentGatewayConst::STRIPE, 'trx' => $escrow_data->trx],"?r-source=".PaymentGatewayConst::APP);
        }else {
            $returnUrl =  route('api.v1.my-escrow.stripe.payment.success',['gateway' => PaymentGatewayConst::STRIPE, 'trx' => $escrow_data->trx],"?r-source=".PaymentGatewayConst::APP);
        }
        $return_url = $returnUrl; 
         // Enter the details of the payment
         $data = [
            'payment_options' => 'card',
            'amount'          => $amount,
            'email'           => $user_email,
            'tx_ref'          => $reference,
            'currency'        =>  $currency,
            'redirect_url'    => $return_url,
            'customer'        => [
                'email'        => $user_email,
                "phone_number" => $user_phone,
                "name"         => $user_name
            ],
            "customizations" => [
                "title"       => "Escrow Create",
                "description" => dateFormat('d M Y', Carbon::now()),
            ]
        ];

       //start stripe pay link
       $stripe = new \Stripe\StripeClient($credentials->secret_key);

       //create product for Product Id
       try{
            $product_id = $stripe->products->create([
                'name' => 'Escrow Create( '.$basic_settings->site_name.' )',
            ]);
       }catch(Exception $e){
            $error = ['error'=>[$e->getMessage()]];
            return ApiResponse::error($error);
       }
       //create price for Price Id
       try{
            $price_id =$stripe->prices->create([
                'currency' =>  $currency,
                'unit_amount' => $amount*100,
                'product' => $product_id->id??""
              ]);
       }catch(Exception $e){
            $error = ['error'=>["Something Is Wrong, Please Contact With Owner"]];
            return ApiResponse::error($error);
       }
       //create payment live links
       try{
            $payment_link = $stripe->paymentLinks->create([
                'line_items' => [
                [
                    'price' => $price_id->id,
                    'quantity' => 1,
                ],
                ],
                'after_completion' => [
                'type' => 'redirect',
                'redirect' => ['url' => $return_url],
                ],
            ]);
        }catch(Exception $e){
            $error = ['error'=>["Something Is Wrong, Please Contact With Owner"]];
            return ApiResponse::error($error);
        }
        $data['link'] =  $payment_link->url;
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
            'method'                => "get",
       ];
       $message = ['success'=>['Escrow Payment Gateway Captured Successful']];
       return ApiResponse::success($message, $data);  
    }

}
