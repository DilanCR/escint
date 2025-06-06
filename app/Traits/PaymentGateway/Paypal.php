<?php

namespace App\Traits\PaymentGateway;

use Exception; 
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent; 
use App\Models\TemporaryData;
use Illuminate\Support\Carbon;
use App\Models\UserNotification;
use Illuminate\Support\Facades\DB;
use App\Models\Admin\BasicSettings;
use App\Constants\NotificationConst;
use Illuminate\Support\Facades\Auth;
use App\Constants\PaymentGatewayConst;
use App\Models\Admin\AdminNotification; 
use App\Notifications\User\AddMoney\ApprovedMail;
use Srmklive\PayPal\Services\PayPal as PayPalClient;
use App\Events\User\NotificationEvent as UserNotificationEvent;

trait Paypal
{
    public function paypalInit($output = null) {  
        if(!$output) $output = $this->output;
           $credentials      = $this->getPaypalCredentials($output);
        
        $config         = $this->paypalConfig($credentials,$output['amount']);
        $paypalProvider = new PayPalClient;
        $paypalProvider->setApiCredentials($config);
        $paypalProvider->getAccessToken();

        $response = $paypalProvider->createOrder([
            "intent"              => "CAPTURE",
            "application_context" => [
                "return_url" => route('user.add.money.payment.success',PaymentGatewayConst::PAYPAL),
                "cancel_url" => route('user.add.money.payment.cancel',PaymentGatewayConst::PAYPAL),
            ],
            "purchase_units" => [
                0 => [
                    "amount" => [
                        "currency_code" => $output['amount']->gateway_cur_code ?? '',
                        "value"         => $output['amount']->total_payable_amount ? number_format($output['amount']->total_payable_amount,2,'.','') : 0,
                    ]
                ]
            ]
        ]); 
        if(isset($response['id']) && $response['id'] != "" && isset($response['status']) && $response['status'] == "CREATED" && isset($response['links']) && is_array($response['links'])) {
            foreach($response['links'] as $item) {
                if($item['rel'] == "approve") {
                    $this->paypalJunkInsert($response);
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
    public function paypalInitApi($output = null) {
        if(!$output) $output = $this->output;
        $credentials      = $this->getPaypalCredentials($output);

        $config         = $this->paypalConfig($credentials,$output['amount']);
        $paypalProvider = new PayPalClient;
        $paypalProvider->setApiCredentials($config);
        $paypalProvider->getAccessToken();

        $response = $paypalProvider->createOrder([
            "intent"              => "CAPTURE",
            "application_context" => [
                "return_url" => route('api.v1.add-money.payment.success',PaymentGatewayConst::PAYPAL."?r-source=".PaymentGatewayConst::APP),
                "cancel_url" => route('api.v1.add-money.payment.cancel',PaymentGatewayConst::PAYPAL."?r-source=".PaymentGatewayConst::APP),
            ],
            "purchase_units" => [
                0 => [
                    "amount" => [
                        "currency_code" => $output['amount']->gateway_cur_code ?? '',
                        "value"         => $output['amount']->total_payable_amount ? number_format($output['amount']->total_payable_amount,2,'.','') : 0,
                    ]
                ]
            ]
        ]);
        if(isset($response['id']) && $response['id'] != "" && isset($response['status']) && $response['status'] == "CREATED" && isset($response['links']) && is_array($response['links'])) {
            foreach($response['links'] as $item) {
                if($item['rel'] == "approve") {
                    $this->paypalJunkInsert($response);
                    return $response;
                    break;
                }
            }
        }

        if(isset($response['error']) && is_array($response['error'])) {
            throw new Exception($response['error']['message']);
        }

        throw new Exception("Something went worng! Please try again.");
    }

    public function getPaypalCredentials($output) {
        $gateway = $output['gateway'] ?? null;
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
    public static function paypalConfig($credentials, $amount_info)
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
            'payment_action' => 'Sale',                                 // Can only be 'Sale', 'Authorization' or 'Order'
            'currency'       => $amount_info->gateway_cur_code ?? "",
            'notify_url'     => "",                                     // Change this accordingly for your application.
            'locale'         => 'en_US',                                // force gateway language  i.e. it_IT, es_ES, en_US ... (for express checkout only)
            'validate_ssl'   => true,                                   // Validate SSL when creating api client.
        ];
        return $config;
    }

    public function paypalJunkInsert($response) { 
        $output = $this->output;

        $data = [
            'gateway'       => $output['gateway']->id,
            'currency'      => $output['gateway_currency']->id,
            'amount'        => json_decode(json_encode($output['amount']),true),
            'response'      => $response,
            'wallet_table'  => $output['wallet']->getTable(),
            'wallet_id'     => $output['wallet']->id,
            'creator_table' => auth()->guard(get_auth_guard())->user()->getTable(),
            'creator_id'    => auth()->guard(get_auth_guard())->user()->id,
            'creator_guard' => get_auth_guard(),
        ]; 
        return TemporaryData::create([
            'type'       => PaymentGatewayConst::PAYPAL,
            'identifier' => $response['id'],
            'data'       => $data,
        ]);
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
        return $this->paypalPaymentCaptured($response,$output);
        if(isset($response['status']) && $response['status'] == 'COMPLETED') {
            return $this->paypalPaymentCaptured($response,$output);
        }else {
            throw new Exception('Transaction faild. Payment captured faild.');
        }

        if(empty($token)) throw new Exception('Transaction faild. Record didn\'t saved properly. Please try again.');
    }

    public function paypalPaymentCaptured($response,$output) {
          // payment successfully captured record saved to database
        $output['capture'] = $response;
        try{
            $trx_id = 'AM'.getTrxNum();
            $user = auth()->user();
            $this->createTransaction($output, $trx_id);
        }catch(Exception $e) {
            throw new Exception($e->getMessage());
        }
        return true;
    }
    public function createTransaction($output, $trx_id) {
        $basic_setting = BasicSettings::first();
        $user = auth()->user();
        $trx_id      = $trx_id;
        $inserted_id = $this->insertRecord($output, $trx_id);
        $this->insertCharges($output,$inserted_id);
          $this->insertDevice($output,$inserted_id);
        $this->removeTempData($output);
        if($this->requestIsApiUser()) {
              // logout user
            $api_user_login_guard = $this->output['api_login_guard'] ?? null;
            if($api_user_login_guard != null) {
                auth()->guard($api_user_login_guard)->logout();
            }
        }
        try{
            if($basic_setting->email_notification == true){
                $user->notify(new ApprovedMail($user,$output,$trx_id));
            }
        }catch(Exception $e){

        }

    }
    public function insertRecord($output, $trx_id) {
        $trx_id = $trx_id;
        $token  = $this->output['tempData']['identifier'] ?? "";
        DB::beginTransaction();
        try{
            $id = DB::table("transactions")->insertGetId([
                'user_id'                     => auth()->user()->id,
                'user_wallet_id'              => $output['wallet']->id,
                'payment_gateway_currency_id' => $output['gateway_currency']->id,
                'type'                        => $output['type'],
                'trx_id'                      => $trx_id,
                'sender_request_amount'       => $output['amount']->requested_amount,
                'sender_currency_code'        => $output['amount']->sender_currency,
                'total_payable'               => $output['amount']->total_payable_amount,
                'exchange_rate'               => $output['amount']->exchange_rate,
                'available_balance'           => $output['wallet']->balance + $output['amount']->requested_amount,
                'remark'                      => ucwords(remove_speacial_char($output['type']," ")) . " With " . $output['gateway']->name,
                'details'                     => json_encode($output['capture']),
                'status'                      => true,
                'attribute'                   => PaymentGatewayConst::SEND,
                'created_at'                  => now(),
            ]);

            $this->updateWalletBalance($output);
            DB::commit();
        }catch(Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage());
        }
        return $id;
    }

    public function updateWalletBalance($output) {
        $update_amount = $output['wallet']->balance + $output['amount']->requested_amount;

        $output['wallet']->update([
            'balance' => $update_amount,
        ]);
    }

    public function insertCharges($output,$id) {
        if(Auth::guard(get_auth_guard())->check()){
            $user = auth()->guard(get_auth_guard())->user();
        }
        DB::beginTransaction();
        try{
            DB::table('transaction_details')->insert([
                'transaction_id' => $id,
                'percent_charge' => $output['amount']->gateway_percent_charge,
                'fixed_charge'   => $output['amount']->gateway_fixed_charge,
                'total_charge'   => $output['amount']->gateway_total_charge,
                'created_at'     => now(),
            ]);
            DB::commit();

              // notification
            $notification_content = [
                'title'   => "Add Money",
                'message' => "Your Wallet (".$output['wallet']->currency->code.") balance  has been added ".$output['amount']->requested_amount.' '. $output['wallet']->currency->code,
                'time'    => Carbon::now()->diffForHumans(),
                'image'   => files_asset_path('profile-default'),
            ];

            UserNotification::create([
                'type'    => NotificationConst::BALANCE_ADDED,
                'user_id' => auth()->user()->id,
                'message' => $notification_content,
            ]);
            //Push Notifications
            $basic_setting = BasicSettings::first();
            try {
                if( $basic_setting->push_notification == true){
                    event(new UserNotificationEvent($notification_content,$user));
                    send_push_notification(["user-".$user->id],[
                        'title'     => $notification_content['title'],
                        'body'      => $notification_content['message'],
                        'icon'      => $notification_content['image'],
                    ]);
                }
            } catch (\Throwable $th) {
                //throw $th;
            }
            //admin create notifications
             $notification_content['title'] = 'Add Money '.$output['amount']->requested_amount.' '.$output['wallet']->currency->code.' By '. $output['gateway_currency']->name.' ('.$user->username.')';
            AdminNotification::create([
                'type'      => NotificationConst::BALANCE_ADDED,
                'admin_id'  => 1,
                'message'   => $notification_content,
            ]);
        }catch(Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage());
        }
    } 
    public function insertDevice($output,$id) {
        $client_ip = request()->ip() ?? false;
        $location = geoip()->getLocation($client_ip);
        $agent = new Agent(); 
        $mac = "";

        DB::beginTransaction();
        try{
            DB::table("transaction_devices")->insert([
                'transaction_id'=> $id,
                'ip'            => $client_ip,
                'mac'           => $mac,
                'city'          => $location['city'] ?? "",
                'country'       => $location['country'] ?? "",
                'longitude'     => $location['lon'] ?? "",
                'latitude'      => $location['lat'] ?? "",
                'timezone'      => $location['timezone'] ?? "",
                'browser'       => $agent->browser() ?? "",
                'os'            => $agent->platform() ?? "",
            ]);
            DB::commit();
        }catch(Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage());
        }
    }
    public function removeTempData($output) { 
        TemporaryData::where("identifier",$output['tempData']['identifier'])->delete();
    }
}
