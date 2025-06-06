<?php

namespace App\Traits\PaymentGateway;
use Exception;
use KingFlamez\Rave\Rave;
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
use Illuminate\Support\Facades\Config;
use App\Models\Admin\AdminNotification;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Session;
use KingFlamez\Rave\Facades\Rave as Flutterwave;
use App\Notifications\User\AddMoney\ApprovedMail;
use App\Events\User\NotificationEvent as UserNotificationEvent;

trait FlutterwaveTrait
{
    public function flutterwaveInit($output = null) {
        if(!$output) $output = $this->output;

        $credentials = $this->getFlutterCredentials($output);

        $this->flutterwaveSetSecreteKey($credentials);

          //This generates a payment reference
        $reference = Flutterwave::generateReference();

        $amount = $output['amount']->total_payable_amount ? number_format($output['amount']->total_payable_amount,2,'.','') : 0;

        if(auth()->guard(get_auth_guard())->check()){
            $user       = auth()->guard(get_auth_guard())->user();
            $user_email = $user->email;
            $user_phone = $user->full_mobile ?? '';
            $user_name  = $user->firstname.' '.$user->lastname ?? '';
        }
        $return_url = route('user.add.money.flutterwave.callback');

          // Enter the details of the payment
        $data = [
            'payment_options' => 'card,banktransfer',
            'amount'          => $amount,
            'email'           => $user_email,
            'tx_ref'          => $reference,
            'currency'        => $output['gateway_currency']['currency_code']??"NGN",
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
        if( $payment['status'] == "error"){
            throw new Exception($payment['message']);
        };

        $this->flutterWaveJunkInsert($data);

        if ($payment['status'] !== 'success') {
            return;
        }

        return redirect($payment['data']['link']);
    }

      // ********* For API **********
    public function flutterwaveInitApi($output = null) {
        if(!$output) $output = $this->output;
           $credentials      = $this->getFlutterCredentials($output);
        $this->flutterwaveSetSecreteKey($credentials);

          //This generates a payment reference
        $reference = Flutterwave::generateReference();

        $amount = $output['amount']->total_payable_amount ? number_format($output['amount']->total_payable_amount,2,'.','') : 0;

        if(auth()->guard(get_auth_guard())->check()){
            $user       = auth()->guard(get_auth_guard())->user();
            $user_email = $user->email;
            $user_phone = $user->full_mobile ?? '';
            $user_name  = $user->firstname.' '.$user->lastname ?? '';
        }

        $return_url = route('api.v1.add-money.paymentSuccessFlutterWaveAutomatic', "r-source=".PaymentGatewayConst::APP);

          // Enter the details of the payment
        $data = [
            'payment_options' => 'card,banktransfer',
            'amount'          => $amount,
            'email'           => $user_email,
            'tx_ref'          => $reference,
            'currency'        => $output['gateway_currency']['currency_code']??"NGN",
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

        $payment      = Flutterwave::initializePayment($data);
        $data['link'] = $payment['data']['link'];
        $data['trx']  = $data['tx_ref'];

        $this->flutterWaveJunkInsert($data);

        if ($payment['status'] !== 'success') {
              // notify something went wrong
            return;
        }

        return $data;
    }
    
    public function flutterWaveJunkInsert($response) {
        $output        = $this->output;
        $user          = auth()->guard(get_auth_guard())->user();
        $creator_table = $creator_id = $wallet_table = $wallet_id = null;

        $creator_table = auth()->guard(get_auth_guard())->user()->getTable();
        $creator_id    = auth()->guard(get_auth_guard())->user()->id;
        $wallet_table  = $output['wallet']->getTable();
        $wallet_id     = $output['wallet']->id;

            $data = [
                'gateway'       => $output['gateway']->id,
                'currency'      => $output['gateway_currency']->id,
                'amount'        => json_decode(json_encode($output['amount']),true),
                'response'      => $response,
                'wallet_table'  => $wallet_table,
                'wallet_id'     => $wallet_id,
                'creator_table' => $creator_table,
                'creator_id'    => $creator_id,
                'creator_guard' => get_auth_guard(),
            ];


        Session::put('identifier',$response['tx_ref']);
        Session::put('output',$output);

        return TemporaryData::create([
            'type'       => PaymentGatewayConst::FLUTTER_WAVE,
            'identifier' => $response['tx_ref'],
            'data'       => $data,
        ]);
    }



      // Get Flutter wave credentials
    public function getFlutterCredentials($output) {
        $gateway = $output['gateway'] ?? null;
        if(!$gateway) throw new Exception("Payment gateway not available");

        $public_key_sample     = ['api key','api_key','client id','primary key', 'public key'];
        $secret_key_sample     = ['client_secret','client secret','secret','secret key','secret id'];
        $encryption_key_sample = ['encryption_key','encryption secret','secret hash', 'encryption id'];

        $public_key  = '';
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
                    $public_key  = $gatewayInput->value ?? "";
                    $outer_break = true;
                    break;
                }
            }
        }

        $secret_key  = '';
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
                    $secret_key  = $gatewayInput->value ?? "";
                    $outer_break = true;
                    break;
                }
            }
        }

        $encryption_key = '';
        $outer_break    = false;
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
                    $outer_break    = true;
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

    public function flutterwaveSuccess($output = null) {
        if(!$output) $output = $this->output;
           $token            = $this->output['tempData']['identifier'] ?? "";
        if(empty($token)) throw new Exception('Transaction faild. Record didn\'t saved properly. Please try again.');
        return $this->createTransactionFlutterwave($output);
    }

    public function createTransactionFlutterwave($output) {
        $inserted_id = $this->insertRecordFlutterwave($output);
        $this->insertChargesFlutterwace($output,$inserted_id);
        $this->insertDeviceFlutterWave($output,$inserted_id);
        $this->removeTempDataFlutterWave($output);

        if($this->requestIsApiUser()) {
                // logout user
            $api_user_login_guard = $this->output['api_login_guard'] ?? null;
            if($api_user_login_guard != null) {
                auth()->guard($api_user_login_guard)->logout();
            }
        } 
    }

    public function updateWalletBalanceFlutterWave($output) {
        $update_amount = $output['wallet']->balance + $output['amount']->requested_amount;

        $output['wallet']->update([
            'balance' => $update_amount,
        ]);
    }

    public function insertRecordFlutterwave($output) {
        $basic_setting = BasicSettings::first();
        $user = auth()->user();
        $token = $this->output['tempData']['identifier'] ?? "";
        DB::beginTransaction();
        try{
            if(Auth::guard(get_auth_guard())->check()){
                $user_id = auth()->guard(get_auth_guard())->user()->id;
            }

            // Add money
            $trx_id = 'AM'.getTrxNum();
            $id     = DB::table("transactions")->insertGetId([
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
                'details'                     => 'Flutter Wave Payment Successfull',
                'status'                      => true,
                'attribute'                   => PaymentGatewayConst::SEND,
                'created_at'                  => now(),
            ]);
            $this->updateWalletBalanceFlutterWave($output);
         
            DB::commit();
        }catch(Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage());
        }
        try{
            if($basic_setting->email_notification == true){
                $user->notify(new ApprovedMail($user,$output,$trx_id));
            }
        }catch(Exception $e){

        }
        return $id;
    }

    public function insertChargesFlutterwace($output,$id) {
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

    public function insertDeviceFlutterWave($output,$id) {
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

    public function removeTempDataFlutterWave($output) {
        TemporaryData::where("identifier",$output['tempData']['identifier'])->delete();
    }




}
