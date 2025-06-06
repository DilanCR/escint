<?php

namespace App\Traits\PaymentGateway;

use Exception;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Escrow;
use App\Models\Transaction;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;
use App\Models\EscrowDetails;
use App\Models\TemporaryData;
use App\Models\UserNotification; 
use App\Constants\EscrowConstants;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Constants\NotificationConst;
use App\Models\Admin\BasicSettings; 
use Illuminate\Support\Facades\Auth;
use App\Constants\PaymentGatewayConst;
use App\Models\Admin\AdminNotification; 
use App\Notifications\Escrow\EscrowRequest;
use App\Models\Admin\PaymentGatewayCurrency;
use App\Notifications\User\AddMoney\ApprovedMail;
use App\Events\User\NotificationEvent as UserNotificationEvent;


trait PaystackTrait
{ 
    public function paystackInit($output = null) { 
        if(!$output) $output = $this->output;
        $credentials = $this->getPaystackCredentials($output);
        return  $this->setupPaystackInitAddMoney($output,$credentials);
      
    }
    public function setupPaystackInitAddMoney($output,$credentials){
        $amount = $output['amount']->total_payable_amount ? number_format($output['amount']->total_payable_amount,2,'.','') : 0;
        $currency = $output['amount']->gateway_cur_code;

        if(auth()->guard(get_auth_guard())->check()){
            $user = auth()->guard(get_auth_guard())->user();
            $user_email = $user->email;
        }
        $temp_record_token = generate_unique_string('temporary_datas', 'identifier', 60);
        $return_url = route('user.add.money.paystack.payment.success',PaymentGatewayConst::PAYSTACK);
     
        $url = "https://api.paystack.co/transaction/initialize";

        $fields             = [
            'email'         => $user_email,
            'amount'        => get_amount($amount, null, 2) * 100,
            'currency'        => $currency,
            'callback_url'  => $return_url,
            'reference'     => $temp_record_token,
        ];
        
        $fields_string = http_build_query($fields);
        //open connection
        $ch = curl_init();

        //set the url, number of POST vars, POST data
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_POST, true);
        curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Authorization: Bearer $credentials->secret_key",
            "Cache-Control: no-cache",
        ));

        //So that curl_exec returns the contents of the cURL; rather than echoing it
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);

        //execute post
        $result = curl_exec($ch);
        $response   = json_decode($result);
        if($response->status == true) {
            $this->paystackJunkInsert($response,$temp_record_token,$credentials);
            return redirect($response->data->authorization_url)->with('output',$output);
        }else{
            throw new Exception($response->message??" "."Something Is Wrong, Please Contact With Owner");
        }

    } 
    public function getPaystackCredentials($output) {
        $gateway = $output['gateway'] ?? null;
        if(!$gateway) throw new Exception(__("Payment gateway not available"));
        $public_key_sample = ['public_key','Public Key','public-key'];
        $secret_key_sample = ['secret_key','Secret Key','secret-key'];

        $public_key = '';
        $outer_break = false;
        foreach($public_key_sample as $item) {
            if($outer_break == true) {
                break;
            }
            $modify_item = $this->paystackPlainText($item);
            foreach($gateway->credentials ?? [] as $gatewayInput) {
                $label = $gatewayInput->label ?? "";
                $label = $this->paystackPlainText($label);

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
            $modify_item = $this->paystackPlainText($item);
            foreach($gateway->credentials ?? [] as $gatewayInput) {
                $label = $gatewayInput->label ?? "";
                $label = $this->paystackPlainText($label);

                if($label == $modify_item) {
                    $secret_key = $gatewayInput->value ?? "";
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
            'public_key'     => $public_key,
            'secret_key' => $secret_key,
            'mode'          => $mode,

        ];

    }

    public function paystackPlainText($string) {
        $string = Str::lower($string);
        return preg_replace("/[^A-Za-z0-9]/","",$string);
    }

    public function paystackJunkInsert($response,$temp_record_token,$credentials) {
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
            'type'       => PaymentGatewayConst::TYPEADDMONEY,
            'identifier'    => $temp_record_token,
            'data'       => $data,
        ]);
    }

    public function paystackSuccess($output = null) {  
        if(!$output) $output = $this->output;
        $token = $this->output['tempData']['identifier'] ?? "";
        if(empty($token)) throw new Exception(__("Transaction Failed. The record didn't save properly. Please try again"));
        $status = PaymentGatewayConst::STATUSPENDING;
        return $this->createTransactionPaystack($output,$status);
      
    }

    public function createTransactionPaystack($output,$status) {
        $basic_setting = BasicSettings::first();
        if($this->predefined_user) {
            $user = $this->predefined_user;
        }else {
            $user = auth()->guard('web')->user();
        }
        $trx_id = 'AM'.getTrxNum(); 
        $inserted_id = $this->insertRecordPaystack($output,$trx_id,$status);
        $this->insertChargesPaystack($output,$inserted_id); 
        $this->insertDevicePaystack($output,$inserted_id);
        $this->removeTempDataPaystack($output); 
        if($this->requestIsApiUser()) {
            // logout user
            $api_user_login_guard = $this->output['api_login_guard'] ?? null;
            if($api_user_login_guard != null) {
                auth()->guard($api_user_login_guard)->logout();
            }
        }
        try{
            if( $basic_setting->email_notification == true){
                $user->notify(new ApprovedMail($user,$output,$trx_id));
            }
        }catch(Exception $e){}
    }

    public function insertRecordPaystack($output,$trx_id,$status) { 
        $trx_id = $trx_id; 
        DB::beginTransaction();
        try{
            if($this->predefined_user) {
                $user = $this->predefined_user;
            }else {
                $user = auth()->guard('web')->user();
            }
            if(Auth::guard(get_auth_guard())->check()){
                $user_id = auth()->guard(get_auth_guard())->user()->id;
            }
            $id = DB::table("transactions")->insertGetId([
                'user_id'                     => auth()->user()->id??$user->id,
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
                'details'                     => PaymentGatewayConst::PAYSTACK." payment successful",
                'callback_ref'                     => $output['callback_ref']??null,
                'status'                      => true,
                'attribute'                   => PaymentGatewayConst::SEND,
                'created_at'                  => now(),
            ]); 
            $this->updateWalletBalancePaystack($output);
            DB::commit();
        }catch(Exception $e) { 
            DB::rollBack(); 
            throw new Exception(__("Something went wrong! Please try again."));
        } 
        return $id;
    }

    public function updateWalletBalancePaystack($output) {
        $update_amount = $output['wallet']->balance + $output['amount']->requested_amount;

        $output['wallet']->update([
            'balance' => $update_amount,
        ]);
    }

    public function insertChargesPaystack($output,$id) {
        if($this->predefined_user) {
            $user = $this->predefined_user;
        }else {
            $user = auth()->guard('web')->user();
        }
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
                'user_id' => auth()->user()->id??$user->id,
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
            throw new Exception(__("Something went wrong! Please try again."));
        }
    }

    public function insertDevicePaystack($output,$id) {
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
            throw new Exception(__("Something went wrong! Please try again."));
        }
    }

    public function removeTempDataPaystack($output) {
        TemporaryData::where("identifier",$output['tempData']['identifier'])->delete();
    }
    public function isPayStack($gateway)
    {
        $search_keyword = ['Paystack','paystack','payStack','pay-stack','paystack gateway', 'paystack payment gateway'];
        $gateway_name = $gateway->name;

        $search_text = Str::lower($gateway_name);
        $search_text = preg_replace("/[^A-Za-z0-9]/","",$search_text);
        foreach($search_keyword as $keyword) {
            $keyword = Str::lower($keyword);
            $keyword = preg_replace("/[^A-Za-z0-9]/","",$keyword);
            if($keyword == $search_text) {
                return true;
                break;
            }
        }
        return false;
    }
    //for api
    public function paystackInitApi($output = null) {
        if(!$output) $output = $this->output;
        $credentials = $this->getPaystackCredentials($output);;
        $amount = $output['amount']->total_payable_amount ? number_format($output['amount']->total_payable_amount,2,'.','') : 0;
        $currency = $output['amount']->gateway_cur_code;
        if(auth()->guard(get_auth_guard())->check()){
            $user = auth()->guard(get_auth_guard())->user();
            $user_email = $user->email;
        }

        $return_url =route('api.v1.add-money.paystack.payment.success',PaymentGatewayConst::PAYSTACK."?r-source=".PaymentGatewayConst::APP);
        $url = "https://api.paystack.co/transaction/initialize";

        $fields             = [
            'email'         => $user_email,
            'amount'        => get_amount($amount, null, 2) * 100,
            'currency'        => $currency,
            'callback_url'  => $return_url
        ];


        $fields_string = http_build_query($fields);
        //open connection
        $ch = curl_init();

        //set the url, number of POST vars, POST data
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_POST, true);
        curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Authorization: Bearer $credentials->secret_key",
            "Cache-Control: no-cache",
        ));

        //So that curl_exec returns the contents of the cURL; rather than echoing it
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);

        //execute post
        $result = curl_exec($ch);
        $response   = json_decode($result);
        if($response->status == true) {
            $this->paystackJunkInsert($response,$response->data->reference,$credentials);
            $data['link'] = $response->data->authorization_url;
            $data['trx'] =  $response->data->reference;

            return $data;
        }else{
            throw new Exception($response->message??" "."Something Is Wrong, Please Contact With Owner");
        }

    }
        /**
     * paystack webhook response
     * @param array $response_data
     * @param \App\Models\Admin\PaymentGateway $gateway
     */
    public function paystackCallbackResponse(array $response_data, $gateway)
    { 
        try{
            $output = $this->output;
            $event_type = $response_data['event'] ?? "";

            if ($event_type == "charge.success") {
                $reference = $response_data['data']['reference'];
                // temp data
                $temp_data = TemporaryData::where('identifier', $reference)->first(); 
                if($temp_data->type == "add-escrow") {
                    if(isset($temp_data->data->payment_type) && $temp_data->data->payment_type == "approvalPending") {
                        $this->escrowPaymentApprovalSuccess($temp_data);
                    }else{
                        try{
                            if(Escrow::where('callback_ref',$reference)->first()==null){
                                $this->createEscrow($temp_data,$reference);
                            }
                            return true;
                        }catch(Exception $e) {
                            Log::info($e);
                        }
                        return true; 
                    }
                }else{
                    // if transaction is already exists need to update status, balance & response data
                    $transaction = Transaction::where('callback_ref', $reference)->first();
                
                    $status = PaymentGatewayConst::STATUSSUCCESS;

                    if ($temp_data) {
                        $gateway_currency_id = $temp_data->data->currency ?? null;
                        $gateway_currency = PaymentGatewayCurrency::find($gateway_currency_id);
                        if ($gateway_currency) {

                            $requested_amount = $temp_data['data']->amount->requested_amount ?? 0;
                            $validator_data = [
                                $this->currency_input_name  => $gateway_currency->alias,
                                $this->amount_input         => $requested_amount,
                                $this->sender_currency_input  => $temp_data['data']->amount->sender_currency
                            ];

                            $get_wallet_model = PaymentGatewayConst::registerWallet()[$temp_data->data->creator_guard];
                            $user_wallet = $get_wallet_model::find($temp_data->data->wallet_id);
                            $this->predefined_user_wallet = $user_wallet;
                            $this->predefined_guard = $user_wallet->user->modelGuardName();
                            $this->predefined_user = $user_wallet->user;

                            $this->output['tempData'] = $temp_data;
                        }

                        $this->request_data = $validator_data;
                        $this->gateway();
                    }

                    $output                     = $this->output;
                    $output['callback_ref']     = $reference;
                    $output['capture']          = $response_data;

                    if ($transaction && $transaction->status != PaymentGatewayConst::STATUSSUCCESS) {

                        $update_data                        = json_decode(json_encode($transaction->details), true);
                        $update_data['gateway_response']    = $response_data;

                        // update information
                        $transaction->update([
                            'status'    => $status,
                            'details'   => $update_data
                        ]);
                        // update balance
                        $this->updateWalletBalance($output);
                    }
                    if(!$transaction) {
                        // create new transaction with success
                        $this->createTransactionPaystack($output, $status);
                    }
                }
            }
        }catch(Exception $e) {
            Log::info($e);
        }
    }

      //insert escrow data
      public function createEscrow($tempData, $additionalData = null,$setStatus = null) {
        $escrowData = $tempData->data->escrow;
        if ($setStatus == null) {
            $status = 0;
            if ($escrowData->role == "seller") {
                $status = EscrowConstants::APPROVAL_PENDING;
            }else if($escrowData->role == "buyer" && $escrowData->payment_gateway_currency_id != null){ 
                if ($tempData->data->gateway_currency->gateway->type == PaymentGatewayConst::AUTOMATIC) {
                    $status = EscrowConstants::ONGOING;
                }else if($tempData->data->gateway_currency->gateway->type == PaymentGatewayConst::MANUAL){
                    $status         = EscrowConstants::PAYMENT_PENDING;
                    $additionalData = json_encode($additionalData);
                }
            }else if($escrowData->role == "buyer" && $escrowData->payment_type == EscrowConstants::MY_WALLET){
                $status = EscrowConstants::ONGOING;
            }
        }else{
            $status = $setStatus;
        }
     
        DB::beginTransaction();
        try{ 
           $escrowCreate = Escrow::create([
                'user_id'                     => $escrowData->user_id,
                'escrow_category_id'          => $escrowData->escrow_category_id,
                'payment_gateway_currency_id' => $escrowData->payment_gateway_currency_id ?? null,
                'escrow_id'                   => 'EC'.getTrxNum(),
                'payment_type'                => $escrowData->payment_type,
                'role'                        => $escrowData->role,
                'who_will_pay'                => $escrowData->charge_payer,
                'buyer_or_seller_id'          => $escrowData->buyer_or_seller_id,
                'amount'                      => $escrowData->amount,
                'escrow_currency'             => $escrowData->escrow_currency,
                'title'                       => $escrowData->title,
                'remark'                      => $escrowData->remarks,
                'file'                        => json_decode($tempData->data->attachment),
                'status'                      => $status,
                'details'                     => $additionalData,
                'created_at'                  => now(),
                'callback_ref'                  => $tempData->identifier,
            ]);
            EscrowDetails::create([
                'escrow_id'             => $escrowCreate->id ?? 0,
                'fee'                   => $escrowData->escrow_total_charge,
                'seller_get'            => $escrowData->seller_amount,
                'buyer_pay'             => $escrowData->buyer_amount,
                'gateway_exchange_rate' => $escrowData->gateway_exchange_rate,
                'created_at'            => now(),
            ]);
            
            TemporaryData::where("identifier", $tempData->identifier)->delete();
            DB::commit();
            //send user notification
            $byerOrSeller = User::findOrFail($escrowData->buyer_or_seller_id);
            $notification_content = [
                'title'   => "Escrow Request",
                'message' => "A user created an escrow with you",
                'time'    => Carbon::now()->diffForHumans(),
                'image'   => files_asset_path('profile-default'),
            ];
            UserNotification::create([
                'type'    => NotificationConst::ESCROW_CREATE,
                'user_id' => $escrowData->buyer_or_seller_id,
                'message' => $notification_content,
            ]);
            //Push Notifications
            $basic_setting = BasicSettings::first();
            try{ 
                $byerOrSeller->notify(new EscrowRequest($byerOrSeller,$escrowCreate));
                
                if($basic_setting->push_notification == true){
                    event(new UserNotificationEvent($notification_content,$byerOrSeller));
                    send_push_notification(["user-".$byerOrSeller->id],[
                        'title'     => $notification_content['title'],
                        'body'      => $notification_content['message'],
                        'icon'      => $notification_content['image'],
                    ]);
                }
            }catch(Exception $e) {

            }
          
        }catch(Exception $e) { 
            DB::rollBack();
            logger($e->getMessage());
            throw new Exception($e->getMessage());
        } 
    }
    //payment approval success 
    public function escrowPaymentApprovalSuccess($tempData) {
        
        $escrow        = Escrow::findOrFail($tempData->data->escrow->escrow_id);
        $escrowDetails = EscrowDetails::where('escrow_id', $escrow->id)->first();
        $escrow->payment_gateway_currency_id = $tempData->data->gateway_currency->id;
        $escrow->payment_type                = EscrowConstants::GATEWAY;
        $escrow->status                      = EscrowConstants::ONGOING; 
        $escrowDetails->buyer_pay             = $tempData->data->escrow->buyer_amount;
        $escrowDetails->gateway_exchange_rate = $tempData->data->escrow->eschangeRate; 
        DB::beginTransaction();
        try{ 
            $escrow->save();
            $escrowDetails->save();
            DB::commit();
            $this->approvelNotificationSend($escrow->user, $escrow);
        }catch(Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage());
        }
    }
      //escrow approvel payment mail send 
      public function approvelNotificationSend($user, $escrow){
        $notification_content = [
            'title'   => "Escrow Approvel Payment",
            'message' => "A user has paid your escrow",
            'time'    => Carbon::now()->diffForHumans(),
            'image'   => files_asset_path('profile-default'),
        ];
        UserNotification::create([
            'type'    => NotificationConst::ESCROW_CREATE,
            'user_id' => $user->id,
            'message' => $notification_content,
        ]);
        //Push Notifications
        $basic_setting = BasicSettings::first();
        try{
            $user->notify(new EscrowApprovel($user,$escrow));
            if( $basic_setting->push_notification == true){
                event(new UserNotificationEvent($notification_content,$user));
                send_push_notification(["user-".$user->id],[
                    'title'     => $notification_content['title'],
                    'body'      => $notification_content['message'],
                    'icon'      => $notification_content['image'],
                ]);
            }
        }catch(Exception $e){
          
        }
     
    } 

}
