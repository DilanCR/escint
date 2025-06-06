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
use Illuminate\Support\Facades\Http;
use App\Constants\PaymentGatewayConst;
use App\Models\Admin\AdminNotification;
use Illuminate\Support\Facades\Session;
use App\Providers\Admin\BasicSettingsProvider; 
use App\Notifications\User\AddMoney\ApprovedMail;
use App\Events\User\NotificationEvent as UserNotificationEvent;

trait QrpayTrait
{ 

    public function qrpayInit($output = null)
    {
        if (!$output) $output = $this->output;
        $credentials = $this->getQrpayCredetials($output);

        $access = $this->accessTokenQrpay($credentials);
        $identifier = generate_unique_string("transactions", "trx_id", 16);

        $this->QrpayJunkInsert($identifier);

        $return_url = route('user.add.money.qrpay.callback');
        $cancel_url = route('user.add.money.qrpay.cancel', $identifier);

        $token = $access->data->access_token;
        // Payment Url Request

        $amount = $output['amount']->total_payable_amount ? number_format($output['amount']->total_payable_amount, 2, '.', '') : 0;

        if (PaymentGatewayConst::ENV_SANDBOX == $credentials->mode) {
            $base_url = $credentials->base_url_sandbox;
        } elseif (PaymentGatewayConst::ENV_PRODUCTION == $credentials->mode) {
            $base_url = $credentials->base_url_production;
        }

        $response = Http::withToken($token)->post($base_url . '/payment/create', [
            'amount'     => $amount,
            'currency'   => $output['amount']->gateway_cur_code ?? '',
            'return_url' => $return_url,
            'cancel_url' => $cancel_url,
            'custom'   => $identifier,
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
    public function qrpayInitApi($output = null)
    {
        if (!$output) $output = $this->output;

        $credentials = $this->getQrpayCredetials($output);
        $access = $this->accessTokenQrpay($credentials);
        $identifier = generate_unique_string("transactions", "trx_id", 16);

        $this->QrpayJunkInsert($identifier);


        $return_url = route('api.v1.add-money.qrpay.callback',PaymentGatewayConst::QRPAY."?r-source=".PaymentGatewayConst::APP);
        $cancel_url =route('api.v1.add-money.payment.cancel',PaymentGatewayConst::QRPAY."?r-source=".PaymentGatewayConst::APP);

        $token = $access->data->access_token;
        // Payment Url Request

        $amount = $output['amount']->total_payable_amount ? number_format($output['amount']->total_payable_amount, 2, '.', '') : 0;

        if (PaymentGatewayConst::ENV_SANDBOX == $credentials->mode) {
            $base_url = $credentials->base_url_sandbox;
        } elseif (PaymentGatewayConst::ENV_PRODUCTION == $credentials->mode) {
            $base_url = $credentials->base_url_production;
        }


        $response = Http::withToken($token)->post($base_url . '/payment/create', [
            'amount'     => $amount,
            'currency'   => $output['amount']->gateway_cur_code ?? '',
            'return_url' => $return_url,
            'cancel_url' => $cancel_url,
            'custom'   => $identifier,
        ]);

        $statusCode = $response->getStatusCode();
        $content    = json_decode($response->getBody()->getContents());

        if ($content->type == 'error') {
            $errors = implode($content->message->error);
            throw new Exception($errors);
        }
        $data['link'] = $content->data->payment_url;
        $data['trx'] = $identifier;

        return $data;

    }
    public function getQrpayCredetials($output)
    {
        $gateway = $output['gateway'] ?? null;

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

    public function qrpayJunkInsert($response)
    {
        $output = $this->output;

        $user = auth()->guard(get_auth_guard())->user();
        $creator_table = $creator_id = $wallet_table = $wallet_id = null;

        if ($user != null) {
            $creator_table = auth()->guard(get_auth_guard())->user()->getTable();
            $creator_id = auth()->guard(get_auth_guard())->user()->id;
            $wallet_table = $output['wallet']->getTable();
            $wallet_id = $output['wallet']->id;
        }


        $data = [
            'gateway'      => $output['gateway']->id,
            'currency'     => $output['gateway_currency']->id,
            'amount'       => json_decode(json_encode($output['amount']),true),
            'response'     => $response,
            'wallet_table'  => $wallet_table,
            'wallet_id'     => $wallet_id,
            'creator_table' => $creator_table,
            'creator_id'    => $creator_id,
            'creator_guard' => get_auth_guard(),
        ];

        Session::put('identifier', $response);
        Session::put('output', $output);

        return TemporaryData::create([
            'user_id'       => Auth::id(),
            'type'          => PaymentGatewayConst::QRPAY,
            'identifier'    => $response,
            'data'          => $data,
        ]);
    }

    public function qrpaySuccess($output = null)
    {
        if (!$output) $output = $this->output;
        $token = $this->output['tempData']['identifier'] ?? "";
        if (empty($token)) throw new Exception('Transaction failed. Record didn\'t saved properly. Please try again.');
        return $this->createTransactionQrpay($output);
    }

    public function createTransactionQrpay($output)
    {
        $basic_setting = BasicSettingsProvider::get();
        $user = auth()->user();
        $trx_id = generateTrxString("transactions","trx_id",'AM',8);
        $inserted_id = $this->insertRecordQrpay($output, $trx_id);
        $this->createTransactionChargeRecordQrpay($output, $inserted_id);
        $this->insertDeviceQrpay($output,$inserted_id);
        $this->removeTempDataQrpay($output);

        if ($this->requestIsApiUser()) {
            // logout user
            $api_user_login_guard = $this->output['api_login_guard'] ?? null;
            if ($api_user_login_guard != null) {
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

    public function updateWalletBalanceQrpay($output)
    {
        $update_amount = $output['wallet']->balance + $output['amount']->requested_amount;
        $output['wallet']->update([
            'balance'   => $update_amount,
        ]);
    }

    public function insertRecordQrpay($output, $trx_id)
    {
        DB::beginTransaction();
        try {
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
                'details'                     => 'QrPay Payment Successful',
                'status'                      => true,
                'attribute'                   => PaymentGatewayConst::SEND,
                'created_at'                  => now(),
            ]);
            $this->updateWalletBalanceQrpay($output);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage());
        }
        return $id;
    }
    public function createTransactionChargeRecordQrpay($output,$id) {
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
    public function insertDeviceQrpay($output,$id) {
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
    public function removeTempDataQrpay($output)
    {
        TemporaryData::where("identifier", $output['tempData']['identifier'])->delete();
    }
}
