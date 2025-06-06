<?php

namespace App\Traits\EscrowPaymentGateway;
use Exception; 
use Illuminate\Support\Str; 
use Illuminate\Support\Carbon; 
use App\Constants\PaymentGatewayConst;
use Illuminate\Support\Facades\Config; 
use KingFlamez\Rave\Facades\Rave as Flutterwave;
use App\Http\Helpers\Api\Helpers as ApiResponse; 

trait FlutterwaveTrait
{
    public function flutterwaveInit($escrow_data = null) {
        if(!$escrow_data) $escrow_data = $this->request_data->data; 
        $credentials = $this->getFlutterCredentials($escrow_data->gateway_currency);
        $this->flutterwaveSetSecreteKey($credentials);
        //This generates a payment reference
        $reference = Flutterwave::generateReference();
        $amount = $escrow_data->escrow->buyer_amount ? number_format($escrow_data->escrow->buyer_amount,2,'.','') : 0;
        if(auth()->guard(get_auth_guard())->check()){
            $user = auth()->guard(get_auth_guard())->user();
            $user_email = $user->email;
            $user_phone = $user->full_mobile ?? '';
            $user_name = $user->firstname.' '.$user->lastname ?? '';
        }

        if (isset($escrow_data->payment_type) && $escrow_data->payment_type == "approvalPending") {
            $return_url = route('user.escrow-action.payment.approval.success.flutterWave',PaymentGatewayConst::FLUTTER_WAVE);
        }else {
            $return_url =  route('user.my-escrow.flutterwave.callback', ['gateway' => PaymentGatewayConst::FLUTTER_WAVE, 'trx' => $escrow_data->trx]);
        } 
        // Enter the details of the payment
        $data = [
            'payment_options' => 'card,banktransfer',
            'amount'          => $amount,
            'email'           => $user_email,
            'tx_ref'          => $reference,
            'currency'        => $escrow_data->gateway_currency->currency_code ?? "",
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

        $payment = Flutterwave::initializePayment($data);
        if( $payment['status'] == "error"){
            throw new Exception($payment['message']);
        };

        if ($payment['status'] !== 'success') {
            return;
        }

        return redirect($payment['data']['link']);
    }

    // Get Flutter wave credentials
    public function getFlutterCredentials($escrow_data) {
        $gateway = $escrow_data->gateway ?? null;
        if(!$gateway) throw new Exception("Payment gateway not available");

        $public_key_sample = ['api key','api_key','client id','primary key', 'public key'];
        $secret_key_sample = ['client_secret','client secret','secret','secret key','secret id'];
        $encryption_key_sample = ['encryption_key','encryption secret','secret hash', 'encryption id'];

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

        $encryption_key = '';
        $outer_break = false;
        foreach($encryption_key_sample as $item) {
            if($outer_break == true) {
                break;
            }
            $modify_item = $this->flutterwavePlainText($item);
            foreach($gateway->credentials ?? [] as $gatewayInput) {
                $label = $gatewayInput->label ?? "";
                $label = $this->flutterwavePlainText($label);

                if($label == $modify_item) {
                    $encryption_key = $gatewayInput->value ?? "";
                    $outer_break = true;
                    break;
                }
            }
        }

        return (object) [
            'public_key'     => $public_key,
            'secret_key'     => $secret_key,
            'encryption_key' => $encryption_key,
        ];

    }

    public function flutterwavePlainText($string) {
        $string = Str::lower($string);
        return preg_replace("/[^A-Za-z0-9]/","",$string);
    }

    public function flutterwaveSetSecreteKey($credentials){
        Config::set('flutterwave.secretKey',$credentials->secret_key);
        Config::set('flutterwave.publicKey',$credentials->public_key);
        Config::set('flutterwave.secretHash',$credentials->encryption_key);
    }

    public function updateWalletBalanceFlutterWave($output) {
        $update_amount = $output['wallet']->balance + $output['amount']->requested_amount;

        $output['wallet']->update([
            'balance'   => $update_amount,
        ]);
    }

    // ********* For API **********
    public function flutterwaveInitApi($escrow_data = null) {
        if(!$escrow_data) $escrow_data = $this->request_data->data; 
        $credentials = $this->getFlutterCredentials($escrow_data->gateway_currency);
        $this->flutterwaveSetSecreteKey($credentials);
        //This generates a payment reference
        $reference = Flutterwave::generateReference();
        $amount = $escrow_data->escrow->buyer_amount ? number_format($escrow_data->escrow->buyer_amount,2,'.','') : 0;
        if(auth()->guard(get_auth_guard())->check()){
            $user = auth()->guard(get_auth_guard())->user();
            $user_email = $user->email;
            $user_phone = $user->full_mobile ?? '';
            $user_name = $user->firstname.' '.$user->lastname ?? '';
        }
        if (isset($escrow_data->payment_type) && $escrow_data->payment_type == "approvalPending") {
            $return_url = route('api.v1.user.api-escrow-action.payment.approval.success.flutterWave',['gateway' => PaymentGatewayConst::PAYPAL, 'trx' => $escrow_data->trx],"r-source=".PaymentGatewayConst::APP);
        }else {
            $return_url =  route('api.v1.my-escrow.flutterwave.callback', ['gateway' => PaymentGatewayConst::FLUTTER_WAVE, 'trx' => $escrow_data->trx],"r-source=".PaymentGatewayConst::APP);
        } 

        // Enter the details of the payment
        $data = [
            'payment_options' => 'card,banktransfer',
            'amount'          => $amount,
            'email'           => $user_email,
            'tx_ref'          => $reference,
            'currency'        => $escrow_data->gateway_currency->currency_code ?? "",
            'redirect_url'    => $return_url,
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
        $payment = Flutterwave::initializePayment($data);
        $data['link'] = $payment['data']['link'];
        $data['trx'] = $data['tx_ref'];
        if ($payment['status'] !== 'success') { 
            return;
        } 
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
