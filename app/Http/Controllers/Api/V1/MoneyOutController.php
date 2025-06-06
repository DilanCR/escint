<?php

namespace App\Http\Controllers\Api\V1;

use Exception;
use App\Models\UserWallet;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\TemporaryData;
use App\Models\Admin\Currency;
use App\Models\UserNotification;
use Illuminate\Support\Facades\DB;
use App\Models\Admin\BasicSettings;
use App\Constants\NotificationConst;
use App\Http\Controllers\Controller;
use App\Models\Admin\PaymentGateway;
use Illuminate\Support\Facades\Auth;
use App\Constants\PaymentGatewayConst;
use App\Traits\ControlDynamicInputFields;
use Illuminate\Support\Facades\Validator;
use App\Models\Admin\PaymentGatewayCurrency;
use App\Http\Helpers\Api\Helpers as ApiResponse;
use Jenssegers\Agent\Agent;

class MoneyOutController extends Controller
{
    use ControlDynamicInputFields;

    public function index(){

        $user = auth()->user();
           // user wallet
           $userWallet = UserWallet::with('currency')->where('user_id',$user->id)->get()->map(function($data){
                return[
                    'name'                  => $data->currency->name,
                    'balance'               => $data->balance,
                    'currency_code'         => $data->currency->code,
                    'currency_symbol'       => $data->currency->symbol,
                    'currency_type'         => $data->currency->type,
                    'rate'                  => $data->currency->rate,
                    'flag'                  => $data->currency->flag,
                    'image_path'            => get_files_public_path('currency-flag'),
                ];
            });
            //add money payment gateways currencys
            $gatewayCurrencies = PaymentGatewayCurrency::whereHas('gateway', function ($gateway) {
                $gateway->where('slug', PaymentGatewayConst::money_out_slug());
                $gateway->where('status', 1);
            })->get()->map(function($data){
                return[
                    'id'                 => $data->id,
                    'payment_gateway_id' => $data->payment_gateway_id,
                    'type'               => $data->gateway->type,
                    'name'               => $data->name,
                    'alias'              => $data->alias,
                    'currency_code'      => $data->currency_code,
                    'currency_symbol'    => $data->currency_symbol,
                    'image'              => $data->image,
                    'min_limit'          => getAmount($data->min_limit, 8),
                    'max_limit'          => getAmount($data->max_limit, 8),
                    'percent_charge'     => getAmount($data->percent_charge, 8),
                    'fixed_charge'       => getAmount($data->fixed_charge, 8),
                    'rate'               => getAmount($data->rate, 8),
                    'created_at'         => $data->created_at,
                    'updated_at'         => $data->updated_at,
                ];
            });
 
            //add money transactions 
            $transactions = Transaction::where('user_id',auth()->user()->id)->moneyOut()->latest()->take(5)->get()->map(function($item){
                $statusInfo = [
                    "success" =>      1,
                    "pending" =>      2,
                    "rejected" =>     4,
                ];
                return[
                    'id'                            => $item->id,
                    'trx_id'                        => $item->trx_id,
                    'gateway_currency'              => $item->gateway_currency->name,
                    'transaction_type'              => $item->type,
                    'sender_request_amount'         => $item->sender_request_amount,
                    'sender_currency_code'          => $item->sender_currency_code,
                    'total_payable'                 => $item->total_payable,
                    'gateway_currency_code'         => $item->gateway_currency->currency_code,
                    'exchange_rate'                 => $item->exchange_rate,
                    'fee'                           => $item->transaction_details->total_charge,
                    'rejection_reason'              => $item->reject_reason ?? null,
                    'created_at'                    => $item->created_at,
                ];
            });
            $data =[
                'base_curr'                 => get_default_currency_code(),
                'base_curr_rate'            => get_amount(1),
                'default_image'             => "public/backend/images/default/default.webp",
                'image_path'                => "public/backend/images/payment-gateways",
                'base_url'                  => url('/'),
                'userWallet'                => (object)$userWallet,
                'gatewayCurrencies'         => $gatewayCurrencies,
                'transactionss'             => $transactions,
            ];
            $message =  ['success'=>[__('Money Out Information!')]];
            return ApiResponse::success($message, $data);

    }
    public function submit(Request $request){
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|gt:0',
            'gateway_currency' => 'required',
            'sender_currency' => 'required'
        ]);
        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return ApiResponse::validation($error);
        }
        $basic_setting = BasicSettings::first();
        $user = auth()->user();
        if($basic_setting->kyc_verification){
            if( $user->kyc_verified == 0){
                $error = ['error'=>['Please submit kyc information!']];
                return ApiResponse::error($error);
            }elseif($user->kyc_verified == 2){
                $error = ['error'=>['Please wait before admin approved your kyc information']];
                return ApiResponse::error($error);
            }elseif($user->kyc_verified == 3){
                $error = ['error'=>['Admin rejected your kyc information, Please re-submit again']];
                return ApiResponse::error($error);
            }
        }

        $sender_currency = Currency::where('code', $request->sender_currency)->first();
        $userWallet = UserWallet::where(['user_id' => $user->id, 'currency_id' => $sender_currency->id, 'status' => 1])->first(); 
        $gate =PaymentGatewayCurrency::whereHas('gateway', function ($gateway) {
            $gateway->where('slug', PaymentGatewayConst::money_out_slug());
            $gateway->where('status', 1);
        })->where('alias',$request->gateway_currency)->first();
        if (!$gate) {
            $error = ['error'=>[__('Invalid Gateway!')]];
            return ApiResponse::error($error);
        }

        if (!$sender_currency) {
            $error = ['error'=>[__('Currency Not Setup Yet!')]];
            return ApiResponse::error($error);
        }
        $amount = $request->amount; 
        $exchange_rate =  (1/$sender_currency->rate)*$gate->rate;

        $min_limit =  $gate->min_limit / $exchange_rate;
        $max_limit =  $gate->max_limit / $exchange_rate;
  
        if($amount < $min_limit || $amount > $max_limit) {
            $error = ['error'=>[__('Please follow the transaction limit')]];
            return ApiResponse::error($error);
        }
        //gateway charge
        $fixedCharge = $gate->fixed_charge;
        $percent_charge =  ($amount*$exchange_rate)*($gate->percent_charge/100);
        $charge = $fixedCharge + $percent_charge;

        $conversion_amount = $amount * $exchange_rate; 
        $will_get = $conversion_amount -  $charge;
        //base_cur_charge
        $baseFixedCharge = $gate->fixed_charge *  $sender_currency->rate;
        $basePercent_charge = ($amount / 100) * $gate->percent_charge;
        $base_total_charge = $baseFixedCharge + $basePercent_charge;
        $reduceAbleTotal = $amount;

        if( $reduceAbleTotal > $userWallet->balance){
            $error = ['error'=>[__('Insufficient Balance!')]];
            return ApiResponse::error($error);
        }

        $insertData = [
            'user_id'                => $user->id,
            'gateway_name'           => strtolower($gate->gateway->name),
            'gateway_type'           => $gate->gateway->type,
            'wallet_id'              => $userWallet->id,
            'trx_id'                 => 'MO'.getTrxNum(),
            'amount'                 => $amount,
            'base_cur_charge'        => $base_total_charge,
            'base_cur_rate'          => $sender_currency->rate,
            'gateway_id'             => $gate->gateway->id,
            'gateway_currency_id'    => $gate->id,
            'gateway_currency'       => strtoupper($gate->currency_code),
            'gateway_percent_charge' => $percent_charge,
            'gateway_fixed_charge'   => $fixedCharge,
            'gateway_charge'         => $charge,
            'gateway_rate'           => $gate->rate,
            'exchange_rate'           => $exchange_rate,
            'conversion_amount'      => $conversion_amount,
            'sender_currency'      => $request->sender_currency,
            'will_get'               => $will_get,
            'payable'                => $reduceAbleTotal,
        ];
        $identifier = generate_unique_string("transactions","trx_id",16);
        $inserted = TemporaryData::create([
            'user_id'       => Auth::guard(get_auth_guard())->user()->id,
            'type'          => PaymentGatewayConst::TYPEMONEYOUT,
            'identifier'    => $identifier,
            'data'          => $insertData,
        ]);
        if($inserted){
            $payment_gateway = PaymentGateway::where('id',$gate->payment_gateway_id)->first();
            $payment_informations =[
                'trx' =>  $identifier,
                'gateway_currency_name' =>  $gate->name,
                'request_amount' => get_amount($request->amount,$request->sender_currency),
                'exchange_rate' => "1".' '.$request->sender_currency.' = '.get_amount($exchange_rate,$gate->currency_code,3),
                'conversion_amount' =>  get_amount($conversion_amount,$gate->currency_code),
                'total_charge' => get_amount($charge,$gate->currency_code),
                'will_get' => get_amount($will_get,$gate->currency_code),
                'payable' => get_amount($reduceAbleTotal,$request->sender_currency),

            ];
            $url = route('api.v1.user.money-out.manual.confirmed');
                $data =[
                        'payment_informations' => $payment_informations,
                        'gateway_type' => $payment_gateway->type,
                        'gateway_currency_name' => $gate->name,
                        'alias' => $gate->alias,
                        'details' => strip_tags($payment_gateway->desc) ?? null,
                        'input_fields' => $payment_gateway->input_fields??null,
                        'url' => $url??'',
                        'method' => "post",
                    ];
            $message =  ['success'=>[__('Money out Inserted Successfully')]];

            return ApiResponse::success($message, $data);


        }else{
            $error = ['error'=>[__('Something is wrong')]];
            return ApiResponse::error($error);
        }
    }
    //manual confirmed
    public function moneyOutManualConfirmed(Request $request){
        $validator = Validator::make($request->all(), [
            'trx'  => "required",
        ]);
        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return ApiResponse::validation($error);
        }
        $track = TemporaryData::where('identifier',$request->trx)->where('type',PaymentGatewayConst::TYPEMONEYOUT)->first();
        if(!$track){
            $error = ['error'=>[__("Sorry, your payment information is invalid")]];
            return ApiResponse::error($error);

        }
        $moneyOutData =  $track->data;
        $gateway = PaymentGateway::where('id', $moneyOutData->gateway_id)->first();
        if($gateway->type != "MANUAL"){
            $error = ['error'=>[__("Invalid request, it is not manual gateway request")]];
            return ApiResponse::error($error);
        }
        $payment_fields = $gateway->input_fields ?? [];
        $validation_rules = $this->generateValidationRules($payment_fields);
        $validator2 = Validator::make($request->all(), $validation_rules);
        if ($validator2->fails()) {
            $message =  ['error' => $validator2->errors()->all()];
            return ApiResponse::error($message);
        }
        $validated = $validator2->validate();
        $get_values = $this->placeValueWithFields($payment_fields, $validated);
            try{
                $inserted_id = $this->insertRecordManual($moneyOutData,$gateway,$get_values);
                $this->insertChargesManual($moneyOutData,$inserted_id);
                // $this->insertDeviceManual($moneyOutData,$inserted_id);
                $track->delete();
                $message =  ['success'=>[__('Money out request send to admin successfully')]];
                return ApiResponse::onlysuccess($message);
            }catch(Exception $e) {
                $error = ['error'=>[$e->getMessage()]];
                return ApiResponse::error($error);
            }

    }
    public function insertRecordManual($moneyOutData,$gateway,$get_values) {
        if($moneyOutData->gateway_type == "AUTOMATIC"){
            $status = 1;
        }else{
            $status = 2;
        }
        $trx_id = $moneyOutData->trx_id ??'MO'.getTrxNum();
        $authWallet = UserWallet::where('id',$moneyOutData->wallet_id)->where('user_id',$moneyOutData->user_id)->first();
        $availableBalance = $authWallet->balance - $moneyOutData->amount;
        DB::beginTransaction();
        try{
            $id = DB::table("transactions")->insertGetId([
                'user_id'                       => auth()->user()->id,
                'user_wallet_id'                => $moneyOutData->wallet_id,
                'payment_gateway_currency_id'   => $moneyOutData->gateway_currency_id,
                'type'                          => PaymentGatewayConst::TYPEMONEYOUT,
                'trx_id'                        => $trx_id,
                'sender_request_amount'                => $moneyOutData->amount,
                'sender_currency_code'             => $moneyOutData->sender_currency, 
                'exchange_rate'                       => $moneyOutData->exchange_rate,
                'total_payable'                       => $moneyOutData->will_get,
                'available_balance'             => $availableBalance,
                'remark'                        => ucwords(remove_speacial_char(PaymentGatewayConst::TYPEMONEYOUT," ")) . " by " .$gateway->name,
                'details'                       => json_encode($get_values),
                'status'                        => $status,
                'created_at'                    => now(),
        
            ]);
            $this->updateWalletBalanceManual($authWallet,$availableBalance);

            DB::commit();
        }catch(Exception $e) {
            DB::rollBack();
            $error = ['error'=>[__("Sorry,something is wrong")]];
            return ApiResponse::error($error);
        }
        return $id;
    }

    public function updateWalletBalanceManual($authWalle,$availableBalance) {
        $authWalle->update([
            'balance'   => $availableBalance,
        ]);
    }
    public function insertChargesManual($moneyOutData,$id) {
        DB::beginTransaction();
        try{
            DB::table('transaction_details')->insert([
                'transaction_id'    => $id,
                'percent_charge'    => $moneyOutData->gateway_percent_charge,
                'fixed_charge'      => $moneyOutData->gateway_fixed_charge,
                'total_charge'      => $moneyOutData->gateway_charge,
                'created_at'        => now(),
            ]);
            DB::commit();

            //notification
            $notification_content = [
                'title'         => "Money Out",
                'message'       => "Your Money Out request send to admin " .$moneyOutData->amount.' '.get_default_currency_code()." successful",
                'image'         => files_asset_path('profile-default'),
            ];

            UserNotification::create([
                'type'      => NotificationConst::MONEY_OUT,
                'user_id'  =>  auth()->user()->id,
                'message'   => $notification_content,
            ]);
            DB::commit();
        }catch(Exception $e) {
            DB::rollBack();
            $error = ['error'=>[__("Sorry,something is wrong")]];
            return ApiResponse::error($error);
        }
    }

    public function insertDeviceManual($output,$id) {
        $client_ip = request()->ip() ?? false;
        $location = geoip()->getLocation($client_ip);
        $agent = new Agent();

        // $mac = exec('getmac');
        // $mac = explode(" ",$mac);
        // $mac = array_shift($mac);
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
            $error = ['error'=>[__("Sorry,something is wrong")]];
            return ApiResponse::error($error);
        }
    }
}
