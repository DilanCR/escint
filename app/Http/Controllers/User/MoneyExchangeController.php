<?php

namespace App\Http\Controllers\User;

use Exception;
use Carbon\Carbon;
use App\Models\UserWallet;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Constants\GlobalConst;
use App\Models\UserNotification;
use Illuminate\Support\Facades\DB;
use App\Models\Admin\BasicSettings;
use App\Constants\NotificationConst;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller; 
use App\Constants\PaymentGatewayConst;
use App\Models\Admin\TransactionSetting; 
use Illuminate\Support\Facades\Validator;
use App\Notifications\User\ExchangeMoney\ExchangeMoney;
use App\Events\User\NotificationEvent as UserNotificationEvent;

class MoneyExchangeController extends Controller
{ 
    public function index(Request $request) {
        $page_title   = "Money Exchange";
        $user_wallets = UserWallet::where('user_id', auth()->user()->id)->whereHas('currency',function($q){
            $q->where('status',GlobalConst::ACTIVE);
        })->with('currency:id,code,name,flag,rate,type,symbol')->get(); 
        $transactions = Transaction::where(['user_id' => auth()->user()->id, 'type' => PaymentGatewayConst::TYPEMONEYEXCHANGE])->latest()->take(10)->get();
        $charges      = TransactionSetting::where("slug",GlobalConst::MONEY_EXCHANGE)->first(); 
        return view('user.sections.money-exchange.index',compact('page_title','user_wallets','charges','transactions'));
    }
    public function moneyExchangeSubmit(Request $request) {
        if(Auth::guard(get_auth_guard())->check()){
            $user = auth()->guard(get_auth_guard())->user();
        }
        $basic_setting = BasicSettings::first();
        $validator = Validator::make($request->all(),[
            'exchange_from_amount'   => 'required|numeric|gt:0',
            'exchange_from_currency' => 'required|string|exists:currencies,code',
            'exchange_to_currency'   => 'required|string|exists:currencies,code',
            'exchange_to_amount'     => 'required|numeric|gt:0',
        ]);
        $validated = $validator->validate(); 
        $user_from_wallet = UserWallet::where('user_id', auth()->user()->id)->whereHas("currency",function($q) use ($validated){
            $q->where("code",$validated['exchange_from_currency']);
        })->first(); 
        if(!$user_from_wallet) return back()->with(['error' => ['From wallet('.$validated['exchange_from_currency'].') doesn\'t exists']]); 
        $user_to_wallet = UserWallet::where('user_id', auth()->user()->id)->whereHas("currency",function($q) use ($validated){
            $q->where("code",$validated['exchange_to_currency']);
        })->first(); 
        if(!$user_to_wallet) return back()->with(['error' => ['To exchange wallet('.$validated['exchange_to_currency'].') doesn\'t exists']]); 
        $charges = TransactionSetting::where("slug",GlobalConst::MONEY_EXCHANGE)->first();
        if(!$charges) return back()->with(['error' => [__('Exchange money isn\'t available right now')]]); 
        if($user_from_wallet->id === $user_to_wallet->id) {
            return back()->with(['error' => [__('Can\'t exchange money with same currency')]]);
        } 
        $chargeCalculate = $this->exchangeChargeCalc($validated['exchange_from_amount'],$charges,$user_from_wallet,$user_to_wallet); 
        // Check transaction limit 
        $sender_currency_rate = $user_from_wallet->currency->rate;
        $min_amount           = $charges->min_limit * $sender_currency_rate;
        $max_amount           = $charges->max_limit * $sender_currency_rate;
        
        if($validated['exchange_from_amount'] < $min_amount || $validated['exchange_from_amount'] > $max_amount) {
            return back()->with(['error' => ['Please follow the transaction limit. (Min '.$min_amount . ' ' . $user_from_wallet->currency->code .' - Max '.$max_amount. ' ' . $user_from_wallet->currency->code . ')']]);
        }
        if($chargeCalculate->payable > $chargeCalculate->from_wallet_balance) return back()->with(['error' => [__('You don\'t have sufficient balance')]]);
        $trx_id = 'ME'.getTrxNum();
        // Transaction Start
        DB::beginTransaction();
        try{
            $inserted_id = DB::table("transactions")->insertGetId([
                'user_id'               => auth()->user()->id,
                'user_wallet_id'        => $user_from_wallet->id,
                'type'                  => PaymentGatewayConst::TYPEMONEYEXCHANGE,
                'trx_id'                => $trx_id,
                'sender_request_amount' => $chargeCalculate->request_amount,
                'sender_currency_code'  => $validated['exchange_from_currency'],
                'exchange_rate'         => $chargeCalculate->exchange_rate,
                'total_payable'         => $chargeCalculate->payable,
                'available_balance'     => $user_from_wallet->balance - $chargeCalculate->payable,
                'details'               => json_encode(['charges' => $chargeCalculate]),
                'status'                => true,
                'created_at'            => now(),
            ]);
            DB::table('transaction_details')->insert([
                'transaction_id' => $inserted_id,
                'percent_charge' => $chargeCalculate->percent_charge,
                'fixed_charge'   => $chargeCalculate->fixed_charge,
                'total_charge'   => $chargeCalculate->total_charge,
                'created_at'     => now(),
            ]);
                 // notification
                 $notification_content = [
                    'title'   => "Exchange Money",
                    'message' => "Exchange Money From ".$chargeCalculate->request_amount. ' ' .$validated['exchange_from_currency']." to ".$chargeCalculate->payable.' '. $validated['exchange_to_currency'],
                    'time'    => Carbon::now()->diffForHumans(),
                    'image'   => files_asset_path('profile-default'),
                ];
    
                UserNotification::create([
                    'type'    => NotificationConst::BALANCE_ADDED,
                    'user_id' => auth()->user()->id,
                    'message' => $notification_content,
                ]);
                $output = [
                    'chargeCalculate' => $chargeCalculate, 
                    'requestData' => $validated, 
                ];
                if( $basic_setting->email_notification == true){
                    $user->notify(new ExchangeMoney($user,$output,$trx_id));
                }
                //Push Notifications
                try {
                    if($basic_setting->push_notification == true){
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
                
            $user_from_wallet->balance -= $chargeCalculate->payable;
            $user_from_wallet->save();
            $user_to_wallet->balance += $chargeCalculate->exchange_amount;
            $user_to_wallet->save();
            DB::commit();
        }catch(Exception $e) {
            DB::rollBack();
            return back()->with(['error' => [$e->getMessage()]]);
            return back()->with(['error' => ['Something went wrong! Please try again']]);
        }
        return back()->with(['success' => ['Money exchange successful']]);
    }
    public function exchangeChargeCalc($enter_amount,$charges,$from_wallet,$to_wallet) {
        $exchange_rate         = $to_wallet->currency->rate / $from_wallet->currency->rate;
        $data['exchange_rate'] = $exchange_rate;
        //request amount
        $data['request_amount']    = $enter_amount;
        $data['request_currency']  = $from_wallet->currency->code;
        $data['exchange_currency'] = $to_wallet->currency->code;
        //exchange charge calculate
        $percent_charge         = $charges->percent_charge ?? 0;
        $data['percent_charge'] = ($enter_amount / 100) * $percent_charge;
        $fixed_charge           = $charges->fixed_charge ?? 0;
        $data['fixed_charge']   = $from_wallet->currency->rate * $fixed_charge;
        $data['total_charge']   = $data['percent_charge'] + $data['fixed_charge'];
        //user wallet check
        $data['from_wallet_balance'] = $from_wallet->balance;
        $data['to_wallet_balance']   = $to_wallet->balance;
        //exchange amount
        $data['payable']                 = $enter_amount + $data['total_charge'];
        $data['exchange_amount']         = $enter_amount * $data['exchange_rate'];
        $data['default_currency_amount'] = ($enter_amount / $from_wallet->currency->rate);
        $data['sender_currency_rate']    = $from_wallet->currency->rate;

        return (object) $data;
    }
}
