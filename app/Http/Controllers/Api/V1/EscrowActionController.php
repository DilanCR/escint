<?php

namespace App\Http\Controllers\Api\V1;

use Exception;
use App\Models\User;
use App\Models\Escrow;
use App\Models\EscrowChat;
use App\Models\UserWallet;
use Illuminate\Http\Request;
use App\Models\EscrowDetails;
use App\Models\TemporaryData;
use App\Models\Admin\Currency;
use Illuminate\Support\Carbon;
use App\Models\UserNotification;
use App\Constants\EscrowConstants;
use Illuminate\Support\Facades\DB;
use App\Constants\NotificationConst;
use App\Http\Controllers\Controller;
use App\Models\Admin\PaymentGateway;
use Illuminate\Support\Facades\Auth;
use App\Constants\PaymentGatewayConst;
use App\Events\EscrowConversationEvent;
use App\Models\Admin\CryptoTransaction;
use App\Traits\ControlDynamicInputFields;
use Illuminate\Support\Facades\Validator;
use App\Http\Helpers\EscrowPaymentGateway;
use App\Models\Admin\PaymentGatewayCurrency;
use App\Models\EscrowConversationAttachment;
use App\Notifications\Escrow\EscrowApprovel;
use App\Notifications\Escrow\EscrowReleased;;
use App\Http\Helpers\Api\Helpers as ApiResponse;
use App\Notifications\Escrow\EscrowDisputePayment;
use App\Notifications\Escrow\EscrowReleasedRequest; 

class EscrowActionController extends Controller
{
    use ControlDynamicInputFields;
    public function paymentApprovalPending($id) {
        $escrow = Escrow::findOrFail($id);
        $previewData = [  
            'id'              => $escrow->id,
            'user_id'         => $escrow->user_id,
            'title'           => $escrow->title,
            'role'            => $escrow->opposite_role,
            'amount'          => get_amount($escrow->amount, $escrow->escrow_currency),
            'escrow_currency' => $escrow->escrow_currency,
            'category'        => $escrow->escrowCategory->name,
            'charge_payer'    => $escrow->string_who_will_pay->value,
            'total_charge'    => get_amount($escrow->escrowDetails->fee, $escrow->escrow_currency),
            'status'          => $escrow->status,
            'created_at'      => $escrow->created_at,
        ];
        //escrow payment gateways currencys
        $gatewayCurrencies = PaymentGatewayCurrency::whereHas('gateway', function ($gateway) {
            $gateway->where('slug', PaymentGatewayConst::add_money_slug());
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

        $user_wallet = UserWallet::where(['user_id' => auth()->user()->id, 'currency_id' => $escrow->escrowCurrency->id])->first();
        $walletData = [  
            'amount'           => get_amount($user_wallet->balance, $user_wallet->currency->code), 
        ];
        $data = [
            'escrow_information' => $previewData,
            'user_wallet'        => $walletData,
            'gateway_currencies' => $gatewayCurrencies,
            'return_url'         => route('api.v1.user.api-escrow-action.paymentApprovalSubmit',$escrow->id),
            'method'             => "post",
        ];
        $message = ['success'=>[__('Escrow approval pending information')]];
        return ApiResponse::success($message, $data);
    }
    //payment approvel submit when seller will send payment request to buyer 
    public function paymentApprovalSubmit(Request $request, $id) {
        $validator = Validator::make($request->all(),[ 
            'payment_gateway' => 'required',
        ]);
        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return ApiResponse::validation($error);
        }
        $validated = $validator->validate();
        $escrow    = Escrow::findOrFail($id);
        if ($escrow->status != EscrowConstants::APPROVAL_PENDING) { 
            $error =  ['error' => ['Something went wrong']];
            return ApiResponse::validation($error);
        } 
        if ($validated['payment_gateway'] == "myWallet") { 
            $sender_currency = Currency::where('code', $escrow->escrow_currency)->first();
            $user_wallet     = UserWallet::where(['user_id' => auth()->user()->id, 'currency_id' => $sender_currency->id])->first();
               //buyer amount calculation
            $amount = $escrow->amount;
            if ($escrow->role == EscrowConstants::SELLER_TYPE && $escrow->who_will_pay == EscrowConstants::ME) {
                $buyer_amount = $amount;
            }else if($escrow->role == EscrowConstants::SELLER_TYPE && $escrow->who_will_pay == EscrowConstants::BUYER){
                $buyer_amount = $amount + $escrow->escrowDetails->fee;
            }else if($escrow->role == EscrowConstants::SELLER_TYPE && $escrow->who_will_pay == EscrowConstants::HALF){
                $buyer_amount = $amount + ($escrow->escrowDetails->fee/2);
            }
            if ($user_wallet->balance == 0 || $user_wallet->balance < 0 || $user_wallet->balance < $buyer_amount) { 
                $error =  ['error' => ['Insuficiant Balance']];
                return ApiResponse::validation($error);
            }
            $this->escrowWalletPayment($escrow);
            $escrow->payment_type = EscrowConstants::MY_WALLET;
            $escrow->status       = EscrowConstants::ONGOING; 
        }else{
            $payment_gateways_currencies = PaymentGatewayCurrency::with('gateway')->find($validated['payment_gateway']);
            if(!$payment_gateways_currencies || !$payment_gateways_currencies->gateway) { 
                $error =  ['error' => ['Payment gateway not found.']];
                return ApiResponse::validation($error);
            }
            //buyer amount calculation
            $amount       = $escrow->amount;
            $eschangeRate = (1/$escrow->escrowCurrency->rate)*$payment_gateways_currencies->rate;
            if ($escrow->role == EscrowConstants::SELLER_TYPE && $escrow->who_will_pay == EscrowConstants::ME) {
                $buyer_amount = $amount*$eschangeRate;
            }else if($escrow->role == EscrowConstants::SELLER_TYPE && $escrow->who_will_pay == EscrowConstants::BUYER){
                $total_amount = $amount + $escrow->escrowDetails->fee;
                $buyer_amount = $total_amount*$eschangeRate;
            }else if($escrow->role == EscrowConstants::SELLER_TYPE && $escrow->who_will_pay == EscrowConstants::HALF){
                $total_amount = $amount + ($escrow->escrowDetails->fee/2);
                $buyer_amount = $total_amount*$eschangeRate;
            }
            
            $identifier = generate_unique_string("escrows","escrow_id",16);
            $tempData = (object)[
                'data'   => (object)[
                    'trx'     => $identifier,
                    'identifier'    => $identifier,
                    'escrow' => (object)[
                        'escrow_id'    => $escrow->id,
                        'amount'    => $escrow->amount,
                        'escrow_currency'    => $escrow->escrow_currency,
                        'gateway_currency'    => $payment_gateways_currencies->currency_code,
                        'gateway_exchange_rate' => $eschangeRate,
                        'escrow_total_charge' => $escrow->escrowDetails->fee,
                        'charge_payer' => $escrow->who_will_pay,
                        'seller_amount' => $escrow->escrowDetails->seller_get,
                        'buyer_amount' => $buyer_amount,
                    ],
                    'gateway_currency' => $payment_gateways_currencies ?? null,
                    'payment_type'     => "approvalPending",
                    'creator_table' => auth()->guard(get_auth_guard())->user()->getTable(),
                    'creator_id'    => auth()->guard(get_auth_guard())->user()->id,
                    'creator_guard' => get_auth_guard(),
                ] 
            ];
            
            $this->addEscrowTempData($identifier, $tempData->data);
            $instance = EscrowPaymentGateway::init($tempData)->apiGateway();
            return $instance;
        }
        DB::beginTransaction();
        try{ 
            $escrow->save();
            DB::commit();
        }catch(Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage());
        } 
        $message = ['success' => [__("Escrow payment Successful, Please Go Back Your App")]];
        return ApiResponse::onlysuccess($message); 
    }
    //escrow wallet payment
    public function escrowWalletPayment($escrowData) {  
        $sender_currency = Currency::where('code', $escrowData->escrow_currency)->first();
        $user_wallet     = UserWallet::where(['user_id' => auth()->user()->id, 'currency_id' => $sender_currency->id])->first();
        //buyer amount calculation
        $amount = $escrowData->amount;
        if ($escrowData->role == EscrowConstants::SELLER_TYPE && $escrowData->who_will_pay == EscrowConstants::ME) {
            $buyer_amount = $amount;
        }else if($escrowData->role == EscrowConstants::SELLER_TYPE && $escrowData->who_will_pay == EscrowConstants::BUYER){
            $buyer_amount = $amount + $escrowData->escrowDetails->fee;
        }else if($escrowData->role == EscrowConstants::SELLER_TYPE && $escrowData->who_will_pay == EscrowConstants::HALF){
            $buyer_amount = $amount + ($escrowData->escrowDetails->fee/2);
        }
        $user_wallet->balance -= $buyer_amount;
        $escrowData->escrowDetails->buyer_pay = $buyer_amount;
        
        DB::beginTransaction();
        try{ 
            $user_wallet->save();
            $escrowData->escrowDetails->save();
            DB::commit();
            $this->approvelNotificationSend($escrowData->user, $escrowData);
        }catch(Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage());
        } 
    }
    //escrow temp data insert
    public function addEscrowTempData($identifier,$data) {  
        return TemporaryData::create([
            'type'       => "add-escrow",
            'identifier' => $identifier,
            'data'       => $data,
        ]);
    }
    //payment approval success 
    public function razorPayLinkCreate(){
        $request_data = request()->all();
        $temData = TemporaryData::where('identifier',$request_data['trx'])->first();
        if(!$temData) {
            $message = ['error' => [__('Transaction faild. Record didn\'t saved properly. Please try again')]];
             ApiResponse::error($message);
        }
        return view('user.my-escrow.razorpay-payment-approvel-api',compact('temData','request_data'));
    }
    public function razorCallback() {
        $requestData   =request()->all(); 
        $token         = $requestData['trx'] ?? "";
        $tempData      = TemporaryData::where("identifier",$token)->first();
        if(!$tempData) return ApiResponse::onlyError(['error' => [__('Transaction Failed. Record didn\'t saved properly. Please try again')]]);
        
        $creator_table = $tempData->data->creator_table ?? null;
        $creator_id = $tempData->data->creator_id ?? null;
        $creator_guard = $tempData->data->creator_guard ?? null;
        $api_authenticated_guards = PaymentGatewayConst::apiAuthenticateGuard();
        if($creator_table != null && $creator_id != null && $creator_guard != null) {
            if(!array_key_exists($creator_guard,$api_authenticated_guards)) throw new Exception(__('Request user doesn\'t save properly. Please try again'));
            $creator = DB::table($creator_table)->where("id",$creator_id)->first();
            if(!$creator) throw new Exception(__("Request user doesn\'t save properly. Please try again"));
            $api_user_login_guard = $api_authenticated_guards[$creator_guard];
            Auth::guard($api_user_login_guard)->loginUsingId($creator->id);
        }

        $escrow        = Escrow::findOrFail($tempData->data->escrow->escrow_id);
        $escrowDetails = EscrowDetails::where('escrow_id', $escrow->id)->first();
        $escrow->payment_gateway_currency_id = $tempData->data->gateway_currency->id;
        $escrow->payment_type                = EscrowConstants::GATEWAY;
        $escrow->status                      = EscrowConstants::ONGOING; 
        $escrowDetails->buyer_pay             = $tempData->data->escrow->buyer_amount;
        $escrowDetails->gateway_exchange_rate = $tempData->data->escrow->gateway_exchange_rate; 
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
        $message = ['success' => [__("Escrow Payment Successful, Please Go Back Your App")]];
        return ApiResponse::onlysuccess($message);
    }
    public function escrowPaymentApprovalSuccess(Request $request, $gateway = null, $trx = null) {
        $identifier    = $trx;
        $tempData      = TemporaryData::where("identifier",$identifier)->first();
        if(!$tempData) return ApiResponse::onlyError(['error' => [__('Transaction Failed. Record didn\'t saved properly. Please try again')]]);
        
        $creator_table = $tempData->data->creator_table ?? null;
        $creator_id = $tempData->data->creator_id ?? null;
        $creator_guard = $tempData->data->creator_guard ?? null;
        $api_authenticated_guards = PaymentGatewayConst::apiAuthenticateGuard();
        if($creator_table != null && $creator_id != null && $creator_guard != null) {
            if(!array_key_exists($creator_guard,$api_authenticated_guards)) throw new Exception(__('Request user doesn\'t save properly. Please try again'));
            $creator = DB::table($creator_table)->where("id",$creator_id)->first();
            if(!$creator) throw new Exception(__("Request user doesn\'t save properly. Please try again"));
            $api_user_login_guard = $api_authenticated_guards[$creator_guard];
            Auth::guard($api_user_login_guard)->loginUsingId($creator->id);
        }

        $escrow        = Escrow::findOrFail($tempData->data->escrow->escrow_id);
        $escrowDetails = EscrowDetails::where('escrow_id', $escrow->id)->first();
        $escrow->payment_gateway_currency_id = $tempData->data->gateway_currency->id;
        $escrow->payment_type                = EscrowConstants::GATEWAY;
        $escrow->status                      = EscrowConstants::ONGOING; 
        $escrowDetails->buyer_pay             = $tempData->data->escrow->buyer_amount;
        $escrowDetails->gateway_exchange_rate = $tempData->data->escrow->gateway_exchange_rate; 
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
        $message = ['success' => [__("Escrow Payment Successful, Please Go Back Your App")]];
        return ApiResponse::onlysuccess($message);
    }
    public function escrowPaymentApprovalSuccessPaystack(Request $request, $gateway = null, $trx = null) {
        $identifier    = $request->trx;
        $tempData      = TemporaryData::where("identifier",$identifier)->first();
        if(!$tempData) return ApiResponse::onlyError(['error' => [__('Transaction Failed. Record didn\'t saved properly. Please try again')]]);
        
        $creator_table = $tempData->data->creator_table ?? null;
        $creator_id = $tempData->data->creator_id ?? null;
        $creator_guard = $tempData->data->creator_guard ?? null;
        $api_authenticated_guards = PaymentGatewayConst::apiAuthenticateGuard();
        if($creator_table != null && $creator_id != null && $creator_guard != null) {
            if(!array_key_exists($creator_guard,$api_authenticated_guards)) throw new Exception(__('Request user doesn\'t save properly. Please try again'));
            $creator = DB::table($creator_table)->where("id",$creator_id)->first();
            if(!$creator) throw new Exception(__("Request user doesn\'t save properly. Please try again"));
            $api_user_login_guard = $api_authenticated_guards[$creator_guard];
            Auth::guard($api_user_login_guard)->loginUsingId($creator->id);
        }

        $escrow        = Escrow::findOrFail($tempData->data->escrow->escrow_id);
        $escrowDetails = EscrowDetails::where('escrow_id', $escrow->id)->first();
        $escrow->payment_gateway_currency_id = $tempData->data->gateway_currency->id;
        $escrow->payment_type                = EscrowConstants::GATEWAY;
        $escrow->status                      = EscrowConstants::ONGOING; 
        $escrowDetails->buyer_pay             = $tempData->data->escrow->buyer_amount;
        $escrowDetails->gateway_exchange_rate = $tempData->data->escrow->gateway_exchange_rate; 
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
        $message = ['success' => [__("Escrow Payment Successful, Please Go Back Your App")]];
        return ApiResponse::onlysuccess($message);
    }
    public function escrowPaymentApprovalSuccessflutterWave(Request $request, $gateway = null, $trx = null) {
        $status = request()->status;  
        //if payment is successful
        if ($status ==  'successful' || $status ==  'completed') { 
            $identifier    = $trx;
            $tempData      = TemporaryData::where("identifier",$identifier)->first();
            if(!$tempData) return ApiResponse::onlyError(['error' => [__('Transaction Failed. Record didn\'t saved properly. Please try again')]]);
            
            $creator_table = $tempData->data->creator_table ?? null;
            $creator_id = $tempData->data->creator_id ?? null;
            $creator_guard = $tempData->data->creator_guard ?? null;
            $api_authenticated_guards = PaymentGatewayConst::apiAuthenticateGuard();
            if($creator_table != null && $creator_id != null && $creator_guard != null) {
                if(!array_key_exists($creator_guard,$api_authenticated_guards)) throw new Exception(__('Request user doesn\'t save properly. Please try again'));
                $creator = DB::table($creator_table)->where("id",$creator_id)->first();
                if(!$creator) throw new Exception(__("Request user doesn\'t save properly. Please try again"));
                $api_user_login_guard = $api_authenticated_guards[$creator_guard];
                Auth::guard($api_user_login_guard)->loginUsingId($creator->id);
            }

            $escrow        = Escrow::findOrFail($tempData->data->escrow->escrow_id);
            $escrowDetails = EscrowDetails::where('escrow_id', $escrow->id)->first();
            $escrow->payment_gateway_currency_id = $tempData->data->gateway_currency->id;
            $escrow->payment_type                = EscrowConstants::GATEWAY;
            $escrow->status                      = EscrowConstants::ONGOING; 
            $escrowDetails->buyer_pay             = $tempData->data->escrow->buyer_amount;
            $escrowDetails->gateway_exchange_rate = $tempData->data->escrow->gateway_exchange_rate; 
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
            $message = ['success' => [__("Escrow Payment Successful, Please Go Back Your App")]];
            return ApiResponse::onlysuccess($message);
        }else{
            $message = ['error' => [__("Escrow Payment Canceled")]];
            return ApiResponse::onlyError($message); 
        }
    }
    public function escrowPaymentApprovalSuccessQrpay(Request $request, $gateway = null, $trx = null) { 
        $identifier    = $trx;
        $tempData      = TemporaryData::where("identifier",$identifier)->first();
        if(!$tempData) return ApiResponse::onlyError(['error' => [__('Transaction Failed. Record didn\'t saved properly. Please try again')]]);
        
        $creator_table = $tempData->data->creator_table ?? null;
        $creator_id = $tempData->data->creator_id ?? null;
        $creator_guard = $tempData->data->creator_guard ?? null;
        $api_authenticated_guards = PaymentGatewayConst::apiAuthenticateGuard();
        if($creator_table != null && $creator_id != null && $creator_guard != null) {
            if(!array_key_exists($creator_guard,$api_authenticated_guards)) throw new Exception(__('Request user doesn\'t save properly. Please try again'));
            $creator = DB::table($creator_table)->where("id",$creator_id)->first();
            if(!$creator) throw new Exception(__("Request user doesn\'t save properly. Please try again"));
            $api_user_login_guard = $api_authenticated_guards[$creator_guard];
            Auth::guard($api_user_login_guard)->loginUsingId($creator->id);
        }

        $escrow        = Escrow::findOrFail($tempData->data->escrow->escrow_id);
        $escrowDetails = EscrowDetails::where('escrow_id', $escrow->id)->first();
        $escrow->payment_gateway_currency_id = $tempData->data->gateway_currency->id;
        $escrow->payment_type                = EscrowConstants::GATEWAY;
        $escrow->status                      = EscrowConstants::ONGOING; 
        $escrowDetails->buyer_pay             = $tempData->data->escrow->buyer_amount;
        $escrowDetails->gateway_exchange_rate = $tempData->data->escrow->gateway_exchange_rate; 
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
        $message = ['success' => [__("Escrow Payment Successful, Please Go Back Your App")]];
        return ApiResponse::onlysuccess($message);
    }
    public function escrowPaymentApprovalSuccessCoingate(Request $request) {  
        $callback_data = $request->all(); 
        $callback_status = $callback_data['status'] ?? ""; 
        $tempData   = TemporaryData::where("identifier",$request->get('trx'))->first(); 
        if(!$tempData) return ApiResponse::onlyError(['error' => [__('Transaction Failed. Record didn\'t saved properly. Please try again')]]);
        $creator_table = $tempData->data->creator_table ?? null;
        $creator_id = $tempData->data->creator_id ?? null;
        $creator_guard = $tempData->data->creator_guard ?? null;
        $api_authenticated_guards = PaymentGatewayConst::apiAuthenticateGuard();
        if($creator_table != null && $creator_id != null && $creator_guard != null) {
            if(!array_key_exists($creator_guard,$api_authenticated_guards)) throw new Exception(__('Request user doesn\'t save properly. Please try again'));
            $creator = DB::table($creator_table)->where("id",$creator_id)->first();
            if(!$creator) throw new Exception(__("Request user doesn\'t save properly. Please try again"));
            $api_user_login_guard = $api_authenticated_guards[$creator_guard];
            Auth::guard($api_user_login_guard)->loginUsingId($creator->id);
        } 
        
        $escrow = Escrow::where('callback_ref',$request->get('trx'))->first();

        if($escrow->status == EscrowConstants::APPROVAL_PENDING) {
            $escrow->status = EscrowConstants::PAYMENT_PENDING;  
        } 

        $escrowDetails = EscrowDetails::where('escrow_id', $escrow->id)->first();
        $escrow->payment_gateway_currency_id = $tempData->data->gateway_currency->id;
        $escrow->payment_type                = EscrowConstants::GATEWAY;

        $escrowDetails->buyer_pay             = $tempData->data->escrow->buyer_amount;
        $escrowDetails->gateway_exchange_rate = $tempData->data->escrow->gateway_exchange_rate; 
        DB::beginTransaction();
        try{ 
            $escrow->save();
            $escrowDetails->save();
            DB::commit(); 
        }catch(Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage());
        } 
        $message = ['success' => [__("Escrow Payment Successful, Please Go Back Your App")]];
        return ApiResponse::onlysuccess($message);
    }
    public function escrowPaymentApprovalCallbackCoingate(Request $request) {
        $callback_data = $request->all(); 
        $callback_status = $callback_data['status'] ?? ""; 
        $tempData   = TemporaryData::where("identifier",$request->get('trx'))->first(); 
        $escrowData = Escrow::where('callback_ref',$request->get('trx'))->first();
        $escrowDetails = EscrowDetails::where('escrow_id', $escrowData->id)->first();
        if($escrowData != null) { // if transaction already created & status is not success
            // Just update transaction status and update user wallet if needed
            if($callback_status == "paid") {  
                $escrowData->payment_gateway_currency_id = $tempData->data->gateway_currency->id;
                $escrowData->payment_type                = EscrowConstants::GATEWAY;
                $escrowData->status                      = EscrowConstants::ONGOING; 
                $escrowDetails->buyer_pay             = $tempData->data->escrow->buyer_amount;
                $escrowDetails->gateway_exchange_rate = $tempData->data->escrow->gateway_exchange_rate; 
                DB::beginTransaction();
                try{ 
                    $escrowData->save();
                    $escrowDetails->save();
                    DB::commit();
                    $this->approvelNotificationSend($escrowData->user, $escrowData);
                }catch(Exception $e) {
                    DB::rollBack();
                    logger($e->getMessage()); 
                }
            }
        }else { // need to create transaction and update status if needed 
            $status = EscrowConstants::PAYMENT_PENDING; 
            if($callback_status == "paid") {
                $status = EscrowConstants::ONGOING;
            } 
            $escrowData->payment_gateway_currency_id = $tempData->data->gateway_currency->id;
            $escrowData->payment_type                = EscrowConstants::GATEWAY;
            $escrowData->status                      = $status; 
            $escrowDetails->buyer_pay             = $tempData->data->escrow->buyer_amount;
            $escrowDetails->gateway_exchange_rate = $tempData->data->escrow->gateway_exchange_rate; 
            DB::beginTransaction();
            try{ 
                $escrowData->save();
                $escrowDetails->save();
                DB::commit();
                $this->approvelNotificationSend($escrowData->user, $escrowData);
            }catch(Exception $e) {
                DB::rollBack();
                logger($e->getMessage()); 
            }
        } 
        logger("Escrow Created Successfully ::" . $callback_data['status']);
    } 
    // payment approval success manual
    public function manualPaymentConfirm(Request $request){  
        $oldData        = TemporaryData::where('identifier', $request->trx)->first();
        $escrow         = Escrow::findOrFail($oldData->data->escrow->escrow_id);
        $escrowDetails  = EscrowDetails::where('escrow_id',$escrow->id)->first();
        $gateway        = PaymentGateway::manual()->where('slug',PaymentGatewayConst::add_money_slug())->where('id',$oldData->data->gateway_currency->gateway->id)->first();
        $payment_fields = $gateway->input_fields ?? [];
        $validation_rules       = $this->generateValidationRules($payment_fields);
        $validator2 = Validator::make($request->all(), $validation_rules);
        if ($validator2->fails()) {
            $message =  ['error' => $validator2->errors()->all()];
            return ApiResponse::onlyError($message); 
        }
        $payment_field_validate = $validator2->validate();
        $get_values             = $this->placeValueWithFields($payment_fields,$payment_field_validate);
        $escrow->payment_gateway_currency_id = $oldData->data->gateway_currency->id;
        $escrow->payment_type                = EscrowConstants::GATEWAY;
        $escrow->details                     = json_encode($get_values);
        $escrow->status                      = EscrowConstants::PAYMENT_PENDING;
        $escrowDetails->buyer_pay             = $oldData->data->escrow->buyer_amount;
        $escrowDetails->gateway_exchange_rate = $oldData->data->escrow->gateway_exchange_rate;
        DB::beginTransaction();
        try{ 
            $escrow->save();
            $escrowDetails->save();
            DB::commit();
        }catch(Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage());
        }
        $message = ['success' => [__("Escrow Payment Successful, Please Go Back Your App")]];
        return ApiResponse::onlysuccess($message); 
    }
    //payment approval success 
    public function escrowPaymentApprovalSuccessSslcommerz(Request $request) { 
        $tempData      = TemporaryData::where("identifier",$request->tran_id)->first();
        if(!$tempData) return ApiResponse::onlyError(['error' => [__('Transaction Failed. Record didn\'t saved properly. Please try again.')]]);

        $creator_table = $tempData->data->creator_table ?? null;
        $creator_id = $tempData->data->creator_id ?? null;
        $creator_guard = $tempData->data->creator_guard ?? null;
        $api_authenticated_guards = PaymentGatewayConst::apiAuthenticateGuard();
        if($creator_table != null && $creator_id != null && $creator_guard != null) {
            if(!array_key_exists($creator_guard,$api_authenticated_guards)) throw new Exception(__('Request user doesn\'t save properly. Please try again'));
            $creator = DB::table($creator_table)->where("id",$creator_id)->first();
            if(!$creator) throw new Exception(__("Request user doesn\'t save properly. Please try again"));
            $api_user_login_guard = $api_authenticated_guards[$creator_guard];
            Auth::guard($api_user_login_guard)->loginUsingId($creator->id);
        }

        $escrow        = Escrow::findOrFail($tempData->data->escrow->escrow_id);
        $escrowDetails = EscrowDetails::where('escrow_id', $escrow->id)->first(); 
        $escrow->payment_gateway_currency_id = $tempData->data->gateway_currency->id;
        $escrow->payment_type                = EscrowConstants::GATEWAY;
        $escrow->status                      = EscrowConstants::ONGOING; 
        $escrowDetails->buyer_pay             = $tempData->data->escrow->buyer_amount;
        $escrowDetails->gateway_exchange_rate = $tempData->data->escrow->gateway_exchange_rate; 
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
        $message = ['success' => [__("Escrow Payment Successful, Please Go Back Your App")]];
        return ApiResponse::onlysuccess($message);
    }
    //escrow approvel payment mail send 
    public function approvelNotificationSend($user, $escrow){
        try{
            $user->notify(new EscrowApprovel($user,$escrow));
        }catch(Exception $e){
          
        }
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
    } 
    //escrow conversation 
    public function escrowConversation($escrow_id){ 
        $escrowData = Escrow::with('conversations','escrowCategory','escrowDetails')->findOrFail($escrow_id);
        $escrowChats = $escrowData->conversations->map(function($data){
            return[
                'sender'         => $data->sender,
                'message_sender' => $data->sender == auth()->user()->id ? "own" : "opposite",
                'message'        => $data->message,
                'attachments'    => $data->conversationsAttachments->map(function($data){
                        return[
                            'file_name' => $data->attachment,
                            'file_type' => $data->attachment_info->type,
                            'file_path' => files_asset_path('escrow-conversation'),
                        
                        ];
                    }),
                'profile_img' => $data->senderImage,
            ];
        }); 
        $data =[
            'escrow_id'            => $escrowData->id,
            'status'               => $escrowData->status,
            'escrow_conversations' => $escrowChats,
            'message_send'         => route('api.v1.user.api-escrow-action.message.send'),
            'method'               => "post",
        ];
        $message = ['success'=>[__('Escrow Conversation Information')]];
        return ApiResponse::success($message, $data);
    }
    //escrow conversation message send
    public function messageSend(Request $request) {
        $validator = Validator::make($request->all(),[
            'escrow_id' => 'required|numeric',
            'message'   => 'nullable|string|max:2000',
            'files.*'         => 'nullable|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);
        if($validator->fails()) {
            $error = ['error' => $validator->errors()];
            return ApiResponse::onlyError($error);
        }
        $validated = $validator->validate();
        $escrow = Escrow::findOrFail($validated['escrow_id']);
        if(!$escrow) return ApiResponse::onlyError(['error' => ['This escrow is closed.']]);
        $data = [
            'escrow_id'     => $escrow->id,
            'sender'        => auth()->user()->id,
            'sender_type'   => "USER",
            'message'       => $validated['message'],
            'receiver_type' => "USER",
        ];
        try{
            $chat_data = EscrowChat::create($data);
            if(isset($validated['files']) && !empty($validated['files'])) { 
                $chat_attachments = []; 
                foreach($request->file("files") as $item) {
                    $upload_file = upload_file($item,'escrow-conversation');
                    $upload_image = upload_files_from_path_dynamic([$upload_file['dev_path']],'escrow-conversation');
                    chmod($upload_file['dev_path'], 0644);
                    $file_type = explode("/", $upload_file['type'])[0];
                    if ($file_type == "image") {
                        delete_file($upload_file['dev_path']);
                    }
                    $chat_attachments[] = [
                        'escrow_chat_id'  => $chat_data->id,
                        'attachment'      =>  $upload_image,
                        'attachment_info' => json_encode($upload_file),
                        'created_at'      => now(),
                    ];
                }
                EscrowConversationAttachment::insert($chat_attachments);
            } 
        }catch(Exception $e) {
            $error = ['error' => [__('Message Sending faild! Please try again')]];
            return ApiResponse::onlyError($error);
        }
        event(new EscrowConversationEvent($escrow,$chat_data));
        $message = ['success'=>[__('Escrow Message Send Successfully')]];
        return ApiResponse::onlysuccess($message);
    }
    //dispute payment 
    public function disputePayment(Request $request){
        $validator = Validator::make($request->all(),[
            'target' => 'required',
        ]);
        $validated = $validator->validate();
        $escrow    = Escrow::findOrFail($validated['target']);
        $user      = User::findOrFail($escrow->user_id == auth()->user()->id ? $escrow->buyer_or_seller_id : $escrow->user_id);
        //status check 
        if($escrow->status != EscrowConstants::ONGOING) { 
            return ApiResponse::onlyError(['error' => [__('Something went wrong')]]);
        }
        $escrow->status = EscrowConstants::ACTIVE_DISPUTE;
        try{ 
            $escrow->save();
            DB::commit();
            //send user notification
            $notification_content = [
                'title'   => "Payment Disputed",
                'message' => "An user dispute the payment",
                'time'    => Carbon::now()->diffForHumans(),
                'image'   => files_asset_path('profile-default'),
            ];
            UserNotification::create([
                'type'    => NotificationConst::ESCROW_CREATE,
                'user_id' => $escrow->user_id == auth()->user()->id ? $escrow->buyer_or_seller_id : $escrow->user_id,
                'message' => $notification_content,
            ]);
            try { 
                $user->notify(new EscrowDisputePayment($user,$escrow));
            } catch (\Throwable $th) {
                //throw $th;
            }
        }catch(Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage());
        } 
        $message = ['success'=>[__('You have request to dispute this escrow successfully')]];
        return ApiResponse::onlysuccess($message);
    }
    //release payment to seller
    public function releasePayment(Request $request){
        $validator = Validator::make($request->all(),[
            'target' => 'required',
        ]);
        $validated = $validator->validate();
        $escrow    = Escrow::findOrFail($validated['target']);
        $user      = User::findOrFail($escrow->user_id == auth()->user()->id ? $escrow->buyer_or_seller_id : $escrow->user_id);
        //status check 
        if($escrow->status != EscrowConstants::ONGOING) {
            return ApiResponse::onlyError(['error' => [__('Something went wrong')]]);
        }
        $user_wallet           = UserWallet::where('user_id',$escrow->buyer_or_seller_id)->where('currency_id',$escrow->escrowCurrency->id)->first();
        if(empty($user_wallet))  return ApiResponse::onlyError(['error' => ['Seller Wallet not found.']]); 
        $user_wallet->balance += $escrow->escrowDetails->seller_get;
        $escrow->status = EscrowConstants::RELEASED;
        DB::beginTransaction();
        try{ 
            $user_wallet->save();
            $escrow->save();
            DB::commit();
            //send user notification
            $notification_content = [
                'title'   => "Payment Released",
                'message' => "A buyer released your payment",
                'time'    => Carbon::now()->diffForHumans(),
                'image'   => files_asset_path('profile-default'),
            ];
            UserNotification::create([
                'type'    => NotificationConst::ESCROW_CREATE,
                'user_id' => $escrow->user_id == auth()->user()->id ? $escrow->buyer_or_seller_id : $escrow->user_id,
                'message' => $notification_content,
            ]);
            try{ 
                $user->notify(new EscrowReleased($user,$escrow));
            }catch(Exception $e){

            }
        }catch(Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage());
        }  
        $message = ['success'=>[__('You have successfully released the payment')]];
        return ApiResponse::onlysuccess($message);
    }
    //send payment release request to buyer
    public function releaseRequest(Request $request){
        $validator = Validator::make($request->all(),[
            'target' => 'required',
        ]);
        $validated = $validator->validate();
        $escrow    = Escrow::findOrFail($validated['target']);
        $user      = User::findOrFail($escrow->user_id == auth()->user()->id ? $escrow->buyer_or_seller_id : $escrow->user_id);
        try{
            //send user notification 
            $notification_content = [
                'title'   => "Release Request",
                'message' => "A seller is requested to release there payment",
                'time'    => Carbon::now()->diffForHumans(),
                'image'   => files_asset_path('profile-default'),
            ];
            UserNotification::create([
                'type'    => NotificationConst::ESCROW_CREATE,
                'user_id' => $user->id,
                'message' => $notification_content,
            ]);  
            try{ 
                $user->notify(new EscrowReleasedRequest($user,$escrow));
            }catch(Exception $e) { 
                
            }
        }catch(Exception $e) { 
            return ApiResponse::onlyError(['error' => [__('Something went wrong! Please try again')]]);
        } 
        $message = ['success'=>[__('Email send successfully!')]];
        return ApiResponse::onlysuccess($message);
    }



    //escrow sslcommerz fail
    public function escrowSllCommerzFails(Request $request){ 
        $tempData = TemporaryData::where("identifier",$request->tran_id)->first();
        if(!$tempData) return ApiResponse::onlyError(['error' => [__('Transaction Failed. Record didn\'t saved properly. Please try again')]]);
        $creator_id    = $tempData->data->creator_id ?? null;
        $creator_guard = $tempData->data->creator_guard ?? null;
        $user          = Auth::guard($creator_guard)->loginUsingId($creator_id);
        if($request->status == "FAILED"){
            TemporaryData::destroy($tempData->id); 
            return ApiResponse::onlyError(['error' => [__('Escrow Create Failed')]]);
        }
    } 
    //escrow sslcommerz cancel
    public function escrowSllCommerzCancel(Request $request){ 
        $tempData = TemporaryData::where("identifier",$request->tran_id)->first();
        if(!$tempData) return ApiResponse::onlyError(['error' => [__('Transaction Failed. Record didn\'t saved properly. Please try again')]]);
        $creator_id    = $tempData->data->creator_id ?? null;
        $creator_guard = $tempData->data->creator_guard ?? null;
        $user          = Auth::guard($creator_guard)->loginUsingId($creator_id);
        if($request->status == "FAILED"){
            TemporaryData::destroy($tempData->id); 
            return ApiResponse::onlyError(['error' => [__('Escrow Create Cancel')]]);
        }
    } 
    public function tatumUserTransactionRequirements($trx_type = null) {
        $requirements = [
            PaymentGatewayConst::TYPEADDMONEY => [
                [
                    'type'          => 'text',
                    'label'         =>  "Txn Hash",
                    'placeholder'   => "Enter Txn Hash",
                    'name'          => "txn_hash",
                    'required'      => true,
                    'validation'    => [
                        'min'           => "0",
                        'max'           => "250",
                        'required'      => true,
                    ]
                ]
            ],
        ];

        if($trx_type) {
            if(!array_key_exists($trx_type, $requirements)) throw new Exception("User Transaction Requirements Not Found!");
            return $requirements[$trx_type];
        }

        return $requirements;
    }
    public function cryptoPaymentAddress(Request $request, $escrow_id) {  
        $escrowData = Escrow::where('escrow_id',$escrow_id)->first();
        if($escrowData->paymentGatewayCurrency->gateway->isCrypto() && $escrowData->details?->payment_info?->receiver_address ?? false) {
            $data =[
                'escrow_data'         => $escrowData,
                'address_info'      => [
                    'coin'          => $escrowData->details?->payment_info?->currency ?? "",
                    'address'       => $escrowData->details?->payment_info?->receiver_address ?? "",
                    'input_fields'  => $this->tatumUserTransactionRequirements(PaymentGatewayConst::TYPEADDMONEY),
                    'submit_url'    => route('api.v1.api-escrow-action.payment.crypto.confirm',$escrow_id),
                    'method'        => "post",
                ],
                'base_url'          => url('/'),
            ];
            $message = ['success'=>[__('Crypto Information')]];
            return ApiResponse::success($message, $data);
        }

        return ApiResponse::error(['error' => ['Something went wrong! Please try again']]);
    }
    public function cryptoPaymentConfirm(Request $request, $escrow_id)
    {  
        $escrowData = Escrow::where('escrow_id',$escrow_id)->first();
        
        $dy_input_fields = $escrowData->details->payment_info->requirements ?? [];
        $validation_rules = $this->generateValidationRules($dy_input_fields);

        $validated = [];
        if(count($validation_rules) > 0) {
            $validated = Validator::make($request->all(), $validation_rules)->validate();
        }
        
        if(!isset($validated['txn_hash'])) return ApiResponse::error(['error' => ['Transaction hash is required for verify']]);

        $receiver_address = $escrowData->details->payment_info->receiver_address ?? "";

        
        // check hash is valid or not
        $crypto_transaction = CryptoTransaction::where('txn_hash', $validated['txn_hash'])
                                                ->where('receiver_address', $receiver_address)
                                                ->where('asset',$escrowData->paymentGatewayCurrency->currency_code)
                                                ->where(function($query) {
                                                    return $query->where('transaction_type',"Native")
                                                                ->orWhere('transaction_type', "native");
                                                })
                                                ->where('status',PaymentGatewayConst::NOT_USED)
                                                ->first();
       
        if(!$crypto_transaction) return ApiResponse::error(['error' => ['Transaction hash is not valid! Please input a valid hash']]);

        if($crypto_transaction->amount >= $escrowData->escrowDetails->buyer_pay == false) {
            if(!$crypto_transaction) return ApiResponse::error(['error' => ['Insufficient amount added. Please contact with system administrator']]);
        }

        DB::beginTransaction();
        try{

            // update crypto transaction as used
            DB::table($crypto_transaction->getTable())->where('id', $crypto_transaction->id)->update([
                'status'        => PaymentGatewayConst::USED,
            ]);

            // update transaction status
            $transaction_details = json_decode(json_encode($escrowData->details), true);
            $transaction_details['payment_info']['txn_hash'] = $validated['txn_hash'];

            DB::table($escrowData->getTable())->where('id', $escrowData->id)->update([
                'details'       => $transaction_details,
                'status'        => EscrowConstants::ONGOING,
                'payment_type'        => EscrowConstants::GATEWAY,
            ]);

            DB::commit();
        }catch(Exception $e) { 
            DB::rollback();
            return ApiResponse::error(['error' => ['Something went wrong! Please try again']]);
        }

        return ApiResponse::onlySuccess(['error' => ['Payment Confirmation Success!']]);
    }
    /**
     * Redirect Users for collecting payment via Button Pay (JS Checkout)
     */
    public function redirectBtnPay(Request $request, $gateway)
    { 
        // dd($request->all());
        try{ 
            return EscrowPaymentGateway::init([])->handleBtnPay($gateway, $request->all());
        }catch(Exception $e) { 
            return redirect()->route('user.my-escrow.index')->with(['error' => [$e->getMessage()]]);
        }
    }
    public function escrowPaymentSuccessRazorpayPost(Request $request, $gateway) { 
        $tempData      = TemporaryData::where("identifier",$request->token)->first();
        if(!$tempData) return ApiResponse::onlyError(['error' => [__('Transaction Failed. Record didn\'t saved properly. Please try again')]]);
   
        $escrow        = Escrow::findOrFail($tempData->data->escrow->escrow_id);
        $escrowDetails = EscrowDetails::where('escrow_id', $escrow->id)->first();
        $escrow->payment_gateway_currency_id = $tempData->data->gateway_currency->id;
        $escrow->payment_type                = EscrowConstants::GATEWAY;
        $escrow->status                      = EscrowConstants::ONGOING; 
        $escrowDetails->buyer_pay             = $tempData->data->escrow->buyer_amount;
        $escrowDetails->gateway_exchange_rate = $tempData->data->escrow->gateway_exchange_rate; 
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
        $message = ['success' => [__("Escrow Payment Successful, Please Go Back Your App")]];
        return ApiResponse::onlysuccess($message);
    }
}
