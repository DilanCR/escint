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
use App\Providers\Admin\BasicSettingsProvider;
use App\Http\Helpers\Api\Helpers as ApiResponse; 
use App\Notifications\User\AddMoney\ApprovedMail;
use App\Events\User\NotificationEvent as UserNotificationEvent;

trait Stripe
{

    public function stripeInit($output = null) {
           $basic_settings   = BasicSettingsProvider::get();
        if(!$output) $output = $this->output;
           $credentials      = $this->getStripeCredetials($output);
           $reference        = generateTransactionReference();
           $amount           = $output['amount']->total_payable_amount ? number_format($output['amount']->total_payable_amount,2,'.','') : 0;
           $currency         = $output['currency']['currency_code']??"USD";

        if(auth()->guard(get_auth_guard())->check()){
            $user       = auth()->guard(get_auth_guard())->user();
            $user_email = $user->email;
            $user_phone = $user->full_mobile ?? '';
            $user_name  = $user->firstname.' '.$user->lastname ?? '';
        }

        $return_url = route('user.add.money.stripe.payment.success', $reference);

          // Enter the details of the payment
         $data = [
            'payment_options' => 'card',
            'amount'          => $amount,
            'email'           => $user_email,
            'tx_ref'          => $reference,
            'currency'        => $currency,
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

         //start stripe pay link
       $stripe = new \Stripe\StripeClient($credentials->secret_key);

         //create product for Product Id
       try{
            $product_id = $stripe->products->create([
                'name' => 'Add Money( '.$basic_settings->site_name.' )',
            ]);
       }catch(Exception $e){
            throw new Exception($e->getMessage());
       }
         //create price for Price Id
       try{
            $price_id =$stripe->prices->create([
                'currency'    => $currency,
                'unit_amount' => $amount * 100,
                'product'     => $product_id->id??""
            ]);
       }catch(Exception $e){
            throw new Exception("Something Is Wrong, Please Contact With Owner");
       }

         //create payment live links
       try{
            $payment_link = $stripe->paymentLinks->create([
                'line_items' => [
                [
                    'price'    => $price_id->id,
                    'quantity' => 1,
                ],
                ],
                'after_completion' => [
                    'type'     => 'redirect',
                    'redirect' => ['url' => $return_url],
                ],
            ]);
        }catch(Exception $e){
            throw new Exception("Something Is Wrong, Please Contact With Owner");
        }
         
        $this->stripeJunkInsert($data); 
        return redirect($payment_link->url."?prefilled_email=".@$user->email);

    }
     //for api
    public function stripeInitApi($output = null) {
        $basic_settings   = BasicSettingsProvider::get();
        if(!$output) $output = $this->output;
           $credentials      = $this->getStripeCredetials($output);
           $reference        = generateTransactionReference();
           $amount           = $output['amount']->total_payable_amount ? number_format($output['amount']->total_payable_amount,2,'.','') : 0;
           $currency         = $output['currency']['currency_code']??"USD";

        if(auth()->guard(get_auth_guard())->check()){
            $user       = auth()->guard(get_auth_guard())->user();
            $user_email = $user->email;
            $user_phone = $user->full_mobile ?? '';
            $user_name  = $user->firstname.' '.$user->lastname ?? '';
        }

        $return_url = route('api.v1.add-money.paymentSuccessStripeAutomatic', $reference."?r-source=".PaymentGatewayConst::APP);


          // Enter the details of the payment
        $data = [
            'payment_options' => 'card',
            'amount'          => $amount,
            'email'           => $user_email,
            'tx_ref'          => $reference,
            'currency'        => $currency,
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

      //start stripe pay link
        $stripe = new \Stripe\StripeClient($credentials->secret_key);

        //create product for Product Id
        try{
                $product_id = $stripe->products->create([
                    'name' => 'Add Money( '.$basic_settings->site_name.' )',
                ]);
        }catch(Exception $e){
                $error = ['error'=>[$e->getMessage()]];
                return ApiResponse::error($error);
        }
        //create price for Price Id
        try{
                $price_id =$stripe->prices->create([
                    'currency'    => $currency,
                    'unit_amount' => $amount*100,
                    'product'     => $product_id->id??""
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
                        'price'    => $price_id->id,
                        'quantity' => 1,
                    ],
                    ],
                    'after_completion' => [
                    'type'     => 'redirect',
                    'redirect' => ['url' => $return_url],
                    ],
                ]);
            }catch(Exception $e){
                $error = ['error'=>["Something Is Wrong, Please Contact With Owner"]];
                return ApiResponse::error($error);
            }
            $data['link'] = $payment_link->url;
            $data['trx']  = $reference;
            
            $this->stripeJunkInsert($data);
            return $data;
    }

    public function getStripeCredetials($output) {
        $gateway = $output['gateway'] ?? null;
        if(!$gateway) throw new Exception("Payment gateway not available");
        $client_id_sample     = ['publishable_key','publishable key','publishable-key'];
        $client_secret_sample = ['secret id','secret-id','secret_id'];

        $client_id   = '';
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
            $modify_item = $this->stripePlainText($item);
            foreach($gateway->credentials ?? [] as $gatewayInput) {
                $label = $gatewayInput->label ?? "";
                $label = $this->stripePlainText($label);

                if($label == $modify_item) {
                    $secret_id   = $gatewayInput->value ?? "";
                    $outer_break = true;
                    break;
                }
            }
        }

        return (object) [
            'publish_key' => $client_id,
            'secret_key'  => $secret_id,

        ];

    }

    public function stripePlainText($string) {
        $string = Str::lower($string);
        return preg_replace("/[^A-Za-z0-9]/","",$string);
    }

    public function stripeJunkInsert($response) {

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

        return TemporaryData::create([
            'type'       => PaymentGatewayConst::STRIPE,
            'user_id'    => auth()->user()->id,
            'identifier' => $response['tx_ref'],
            'data'       => $data,
        ]);
    }

    public function stripeSuccess($output = null) { 
        if(!$output) $output = $this->output;
           $token            = $this->output['tempData']['identifier'] ?? "";
        if(empty($token)) throw new Exception('Transaction failed. Record didn\'t saved properly. Please try again.');
        $trx_id = 'AM'.getTrxNum();
        return $this->createTransactionStripe($output, $trx_id);
    }
    public function createTransactionStripe($output, $trx_id) {
        $basic_setting = BasicSettings::first();
        $user = auth()->user();
        $trx_id      = $trx_id;
        $inserted_id = $this->insertRecordStripe($output,$trx_id);
        $this->insertChargesStripe($output,$inserted_id);
        $this->insertDeviceStripe($output,$inserted_id);
        $this->removeTempDataStripe($output);
        try{
            if($basic_setting->email_notification == true){
                $user->notify(new ApprovedMail($user,$output,$trx_id));
            }
        }catch(Exception $e){

        }
    }

    public function insertRecordStripe($output, $trx_id) {

        $trx_id = $trx_id;
        DB::beginTransaction();
        try{
            $id = DB::table("transactions")->insertGetId([
                'user_id'                     => auth()->user()->id,
                'user_wallet_id'              => $output['wallet']->id,
                'payment_gateway_currency_id' => $output['gateway_currency']->id,
                'type'                        => PaymentGatewayConst::TYPEADDMONEY,
                'trx_id'                      => $trx_id,
                'sender_request_amount'       => $output['amount']->requested_amount,
                'sender_currency_code'        => $output['amount']->sender_currency,
                'total_payable'               => $output['amount']->total_payable_amount,
                'exchange_rate'               => $output['amount']->exchange_rate,
                'available_balance'           => $output['wallet']->balance + $output['amount']->requested_amount,
                'remark'                      => ucwords(remove_speacial_char(PaymentGatewayConst::TYPEADDMONEY," ")) . " With " . $output['gateway']->name,
                'details'                     => "Strip Payment Successful",
                'status'                      => true,
                'attribute'                   => PaymentGatewayConst::SEND,
                'created_at'                  => now(),
            ]);

            $this->updateWalletBalanceStripe($output);
            DB::commit();
        }catch(Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage());
        }
        return $id;
    }

    public function updateWalletBalanceStripe($output) {
        $update_amount = $output['wallet']->balance + $output['amount']->requested_amount;
        $output['wallet']->update([
            'balance' => $update_amount,
        ]);
    }

    public function insertChargesStripe($output,$id) {
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

    public function insertDeviceStripe($output,$id) {
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

    public function removeTempDataStripe($output) {
       TemporaryData::where("identifier",$output['tempData']['identifier'])->delete();
    }

  

}
