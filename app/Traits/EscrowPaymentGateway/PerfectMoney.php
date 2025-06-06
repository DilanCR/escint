<?php 

namespace App\Traits\EscrowPaymentGateway;

use Exception;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;
use App\Models\TemporaryData;
use Illuminate\Support\Carbon;
use App\Models\UserNotification;
use Illuminate\Support\Facades\DB;
use App\Models\Admin\BasicSettings;
use App\Constants\NotificationConst;
use App\Http\Helpers\PaymentGateway;
use Illuminate\Support\Facades\Auth;
use App\Constants\PaymentGatewayConst;
use App\Models\Admin\AdminNotification;
use App\Providers\Admin\BasicSettingsProvider;
use App\Notifications\User\AddMoney\ApprovedMail;
use App\Events\User\NotificationEvent as UserNotificationEvent;

trait PerfectMoney {
    
    private $perfect_money_credentials;
    private $perfect_money_request_credentials;

    public function perfectMoneyInit($escrow_data = null)
    {  
        $credentialsData = [];
        if(!$escrow_data) $escrow_data = $this->request_data->data;

        $gateway_credentials = $this->perfectMoneyGatewayCredentials($escrow_data->gateway_currency);
        $request_credentials = $this->perfectMoneyRequestCredentials($gateway_credentials,$escrow_data->gateway_currency->gateway, $escrow_data->gateway_currency);
        $credentialsData['request_credentials'] = $request_credentials;

        if($gateway_credentials->passphrase == "") {
            throw new Exception("You must set Alternate Passphrase under Settings section in your Perfect Money account before starting receiving payment confirmations.");
        }

        // need to insert junk for temporary data 
        if (isset($escrow_data->payment_type) && $escrow_data->payment_type == "approvalPending") {
            $temp_record        = $this->perfectMoneyJunkInsertApprovalPending($escrow_data,$credentialsData);
            $temp_identifier    = $temp_record->identifier;
        }else{
            $this->perfectMoneyJunkInsertUpdate($escrow_data,$credentialsData);
            $temp_identifier    = $escrow_data->trx;
        }

        $link_for_redirect_form = $this->generateLinkForRedirectForm($temp_identifier, PaymentGatewayConst::PERFECT_MONEY);

        if(request()->expectsJson()) {
            $this->output['redirection_response']   = [];
            $this->output['redirect_links']         = [];
            $this->output['redirect_url']           = $link_for_redirect_form;
            return $this->get();
        }

        return redirect()->away($link_for_redirect_form);
    }

    /**
     * Get payment gateway credentials for both sandbox and production
     */
    public function perfectMoneyGatewayCredentials($escrow_data)
    {
        $gateway = $escrow_data->gateway ?? null;
        if(!$gateway) throw new Exception("Oops! Payment Gateway Not Found!");

        $usd_account_sample     = ['usd account','usd','usd wallet','account usd'];
        $eur_account_sample     = ['eur account','eur','eur wallet', 'account eur'];
        $pass_phrase_sample     = ['alternate passphrase' ,'passphrase', 'perfect money alternate passphrase', 'alternate passphrase perfect money' , 'alternate phrase' , 'alternate pass'];

        $usd_account            = PaymentGateway::getValueFromGatewayCredentials($gateway,$usd_account_sample);
        $eur_account            = PaymentGateway::getValueFromGatewayCredentials($gateway,$eur_account_sample);
        $pass_phrase            = PaymentGateway::getValueFromGatewayCredentials($gateway,$pass_phrase_sample);

        $credentials = (object) [
            'usd_account'   => $usd_account,
            'eur_account'   => $eur_account,
            'passphrase'    => $pass_phrase, // alternate passphrase
        ];

        $this->perfect_money_credentials = $credentials;

        return $credentials;
    }

    /**
     * Get payment gateway credentials for making api request
     */
    public function perfectMoneyRequestCredentials($gateway_credentials, $payment_gateway, $gateway_currency)
    {
        if($gateway_currency->currency_code == "EUR") {
            $request_credentials = [
                'account'   => $gateway_credentials->eur_account
            ];
        }else if($gateway_currency->currency_code == "USD") {
            $request_credentials = [
                'account'   => $gateway_credentials->usd_account
            ];
        }

        $request_credentials = (object) $request_credentials;

        $this->perfect_money_request_credentials = $request_credentials;

        return $request_credentials;
    }
    public function perfectMoneyJunkInsertUpdate($escrow_data,$credentialsData)
    {
        $action_type = PaymentGatewayConst::REDIRECT_USING_HTML_FORM;

        $payment_id = Str::uuid() . '-' . time();
        // $this->setUrlParams("token=" . $payment_id); // set Parameter to URL for identifying when return success/cancel

        $redirect_form_data = $this->makingPerfectMoneyRedirectFormData($escrow_data, $credentialsData,$payment_id);
        $form_action_url    = "https://perfectmoney.com/api/step1.asp";
        $form_method        = "POST"; 

        $tempData = TemporaryData::where('identifier',$escrow_data->trx)->first();
        
        if($tempData != null) {
            $update_temp_data = json_decode(json_encode($tempData->data),true);
            $update_temp_data['action_type'] =$action_type;
            $update_temp_data['redirect_form_data'] = $redirect_form_data;
            $update_temp_data['action_url'] = $form_action_url;
            $update_temp_data['form_method'] = $form_method;
        }

        $tempData->data = $update_temp_data;  
        $tempData->save();  
        return $tempData;
    }
    public function perfectMoneyJunkInsertApprovalPending($escrow_data,$credentialsData)
    {
        $action_type = PaymentGatewayConst::REDIRECT_USING_HTML_FORM;

        $payment_id = Str::uuid() . '-' . time();
        // $this->setUrlParams("token=" . $payment_id); // set Parameter to URL for identifying when return success/cancel

        $redirect_form_data = $this->makingPerfectMoneyRedirectFormData($escrow_data, $credentialsData,$payment_id);
        $form_action_url    = "https://perfectmoney.com/api/step1.asp";
        $form_method        = "POST";

        $data = [ 
            'creator_table'         => auth()->guard(get_auth_guard())->user()->getTable(),
            'creator_id'            => auth()->guard(get_auth_guard())->user()->id,
            'creator_guard'         => get_auth_guard(),
            'action_type'           => $action_type,
            'redirect_form_data'    => $redirect_form_data,
            'action_url'            => $form_action_url,
            'form_method'           => $form_method,
        ];

        return TemporaryData::create([
            'type'          => 'perfectmoney',
            'identifier'    => $payment_id,
            'data'          => $data,
        ]);
    }

    public function makingPerfectMoneyRedirectFormData($escrow_data, $credentialsData,$payment_id)
    {
        $basic_settings = BasicSettingsProvider::get();

        if (isset($escrow_data->payment_type) && $escrow_data->payment_type == "approvalPending") {
            $returnUrl = route('user.escrow-action.payment.approval.success',PaymentGatewayConst::PERFECT_MONEY);
            $cancel_url = route('user.escrow-action.payment.cancel');
        }else {
            $returnUrl = route('user.my-escrow.perfect.money.payment.success',['gateway' => PaymentGatewayConst::PERFECT_MONEY, 'trx' => $escrow_data->trx]);
            $cancel_url = route('user.my-escrow.payment.cancel',$escrow_data->trx);
        }

        return [
            [
                'name'  => 'PAYEE_ACCOUNT',
                'value' => $credentialsData['request_credentials']->account,
            ],
            [
                'name'  => 'PAYEE_NAME',
                'value' => $basic_settings->site_name,
            ],
            [
                'name'  => 'PAYMENT_AMOUNT',
                'value' => $escrow_data->escrow->buyer_amount ? number_format($escrow_data->escrow->buyer_amount,2,'.','') : 0,
            ],
            [
                'name'  => 'PAYMENT_UNITS',
                'value' => $escrow_data->gateway_currency->currency_code ?? '',
            ],
            [
                'name'  => 'PAYMENT_ID',
                'value' => $payment_id,
            ],
            [
                'name'  => 'STATUS_URL',
                'value' => $returnUrl,
            ],
            [
                'name'  => 'PAYMENT_URL',
                'value' => $returnUrl,
            ],
            [
                'name'  => 'PAYMENT_URL_METHOD',
                'value' => 'GET',
            ],
            [
                'name'  => 'NOPAYMENT_URL',
                'value' => $cancel_url,
            ],
            [
                'name'  => 'NOPAYMENT_URL_METHOD',
                'value' => 'GET',
            ],
            [
                'name'  => 'BAGGAGE_FIELDS',
                'value' => '',
            ],
            [
                'name'  => 'INTERFACE_LANGUAGE',
                'value' => 'en_US',
            ],
            [
                'name'  => 'r-source',
                'value' => 'APP',
            ],
        ];
    }

    public function isPerfectMoney($gateway)
    {
        $search_keyword = ['perfectmoney','perfect money','perfectmoney','perfect money gateway', 'perfect money payment gateway'];
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

    public function getPerfectMoneyAlternatePassphrase($gateway)
    {
        $gateway_credentials = $this->perfectMoneyGatewayCredentials($gateway);
        return $gateway_credentials->passphrase;
    }

    public function perfectmoneySuccess($output) {  
        // dd($output);
        $reference              = $output['tempData']['identifier'];
        $output['capture']      = $output['tempData']['data']->callback_data ?? "";
        $output['callback_ref'] = $reference;

        $pass_phrase = strtoupper(md5($this->getPerfectMoneyAlternatePassphrase($output['gateway'])));

        if($output['capture'] != "") {

            $concat_string = $output['capture']->PAYMENT_ID . ":" . $output['capture']->PAYEE_ACCOUNT . ":" . $output['capture']->PAYMENT_AMOUNT . ":" . $output['capture']->PAYMENT_UNITS . ":" . $output['capture']->PAYMENT_BATCH_NUM . ":" . $output['capture']->PAYER_ACCOUNT . ":" . $pass_phrase . ":" . $output['capture']->TIMESTAMPGMT;

            $md5_string = strtoupper(md5($concat_string));

            $v2_hash = $output['capture']->V2_HASH;

            if($md5_string == $v2_hash) {
                // this transaction is success
                if(!$this->searchWithReferenceInTransaction($reference)) {
                    // need to insert new transaction in database
                    try{ 
                        $this->createTransactionPerfectMoney($output, PaymentGatewayConst::STATUSSUCCESS);
                    }catch(Exception $e) {
                        throw new Exception($e->getMessage());
                    }
                }else{
                    return back()->with(['error' => ['Something went wrong! Please try again p']]);
                }
            }
        } 
    }


    public function perfectmoneyCallbackResponse($reference,$callback_data, $output = null) {

        if(!$output) $output = $this->output;
        $pass_phrase = strtoupper(md5($this->getPerfectMoneyAlternatePassphrase($output['gateway'])));

        if(is_array($callback_data) && count($callback_data) > 0) {
            $concat_string = $callback_data['PAYMENT_ID'] . ":" . $callback_data['PAYEE_ACCOUNT'] . ":" . $callback_data['PAYMENT_AMOUNT'] . ":" . $callback_data['PAYMENT_UNITS'] . ":" . $callback_data['PAYMENT_BATCH_NUM'] . ":" . $callback_data['PAYER_ACCOUNT'] . ":" . $pass_phrase . ":" . $callback_data['TIMESTAMPGMT'];

            $md5_string = strtoupper(md5($concat_string));
            $v2_hash = $callback_data['V2_HASH'];

            if($md5_string != $v2_hash) {
                return false;
                logger("Transaction hash did not match. ref: $reference", [$callback_data]);
            }
        }else {
            return false;
            logger("Invalid callback data. ref: $reference", [$callback_data]);
        }

        if(isset($output['transaction']) && $output['transaction'] != null && $output['transaction']->status != PaymentGatewayConst::STATUSSUCCESS) { // if transaction already created & status is not success

            // Just update transaction status and update user wallet if needed
            $transaction_details                        = json_decode(json_encode($output['transaction']->details),true) ?? [];
            $transaction_details['gateway_response']    = $callback_data;

            // update transaction status
            DB::beginTransaction();

            try{
                DB::table($output['transaction']->getTable())->where('id',$output['transaction']->id)->update([
                    'status'        => PaymentGatewayConst::STATUSSUCCESS,
                    'details'       => json_encode($transaction_details),
                    'callback_ref'  => $reference,
                ]);

                $this->updateWalletBalancePerfectMoney($output);
                DB::commit();
                
            }catch(Exception $e) {
                DB::rollBack();
                logger($e);
                throw new Exception($e);
            }
        }else { // need to create transaction and update status if needed

            $status = PaymentGatewayConst::STATUSSUCCESS;

            $this->createTransactionPerfectMoney($output, $status, false);
        }

        logger("Transaction Created Successfully! ref: " . $reference);
    }
    public function createTransactionPerfectMoney($output, $status) {
        $basic_setting = BasicSettings::first();
        $user = auth()->user();
        $trx_id = 'AM'.getTrxNum();
        $inserted_id = $this->insertRecordPerfectMoney($output, $trx_id);
        $this->insertChargesPerfectMoney($output,$inserted_id);
        $this->insertDevicePerfectMoney($output,$inserted_id);
        $this->removeTempDataPerfectMoney($output);
        if($this->requestIsApiUser()) {
              // logout user
            $api_user_login_guard = $this->output['api_login_guard'] ?? null;
            if($api_user_login_guard != null) {
                auth()->guard($api_user_login_guard)->logout();
            }
        }
        if( $basic_setting->email_notification == true){
            $user->notify(new ApprovedMail($user,$output,$trx_id));
        }

    }
    public function insertRecordPerfectMoney($output, $trx_id) {
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

            $this->updateWalletBalancePerfectMoney($output);
            DB::commit();
        }catch(Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage());
        }
        return $id;
    }

    public function updateWalletBalancePerfectMoney($output) {
        $update_amount = $output['wallet']->balance + $output['amount']->requested_amount;

        $output['wallet']->update([
            'balance' => $update_amount,
        ]);
    }

    public function insertChargesPerfectMoney($output,$id) {
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
            //admin create notifications
             $notification_content['title'] = 'Add Money '.$output['amount']->requested_amount.' '.$output['wallet']->currency->code.' By '. $output['gateway_currency']->name.' ('.$user->username.')';
            AdminNotification::create([
                'type'      => NotificationConst::BALANCE_ADDED,
                'admin_id'  => 1,
                'message'   => $notification_content,
            ]);
            DB::commit();
        }catch(Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage());
        }
    } 
    public function insertDevicePerfectMoney($output,$id) {
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
    public function removeTempDataPerfectMoney($output) { 
        TemporaryData::where("identifier",$output['tempData']['identifier'])->delete();
    }
}