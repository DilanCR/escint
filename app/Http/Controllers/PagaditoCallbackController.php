<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Escrow;
use Illuminate\Http\Request;
use App\Models\EscrowDetails;
use App\Models\TemporaryData;
use Illuminate\Support\Carbon;
use App\Models\UserNotification;
use App\Constants\EscrowConstants;
use Illuminate\Support\Facades\DB;
use App\Constants\NotificationConst;
use Illuminate\Support\Facades\Auth;
use App\Constants\PaymentGatewayConst;
use App\Notifications\Escrow\EscrowApprovel;
use App\Http\Controllers\User\EscrowController;
use App\Http\Helpers\Api\Helpers as ApiResponse;
use App\Http\Helpers\PaymentGateway as PaymentGatewayHelper;
use App\Events\User\NotificationEvent as UserNotificationEvent;

class PagaditoCallbackController extends EscrowController
{ 
    public function pagaditoSuccess() {
        $request_data = request()->all();
        // dd($request_data);
        //if payment is successful
        $token = $request_data['param1'];
        $checkTempData = TemporaryData::where("type",PaymentGatewayConst::PAGADITO)->where("identifier",$token)->first();
        if($checkTempData->data->env_type == 'addmoneyweb'){
            if(!$checkTempData) return redirect()->route('user.add.money.index')->with(['error' => ['Transaction Failed. Record didn\'t saved properly. Please try again.']]);
            $checkTempData = $checkTempData->toArray();
            try{
                PaymentGatewayHelper::init($checkTempData)->type(PaymentGatewayConst::TYPEADDMONEY)->responseReceive('pagadito');
            }catch(Exception $e) {
                return back()->with(['error' => [$e->getMessage()]]);
            }
            return redirect()->route("user.add.money.index")->with(['success' => ['Successfully added money']]);
        }elseif($checkTempData->data->env_type == 'addmoneyapi'){ 
            if(!$checkTempData) {
                $message = ['error' => ['Transaction Failed. Record didn\'t saved properly. Please try again.']];
                return ApiResponse::error($message);
            }
            $checkTempData = $checkTempData->toArray();
            try{
                PaymentGatewayHelper::init($checkTempData)->type(PaymentGatewayConst::TYPEADDMONEY)->responseReceiveApi('pagadito');
            }catch(Exception $e) {
                $message = ['error' => [$e->getMessage()]];
                ApiResponse::error($message);
            }
            $message = ['success' => ["Payment Successful, Please Go Back Your App"]];
            return ApiResponse::onlySuccess($message);
        }elseif($checkTempData->data->env_type == 'escrowcreateweb'){ 
            try{
                $this->createEscrow($checkTempData);
                return redirect()->route('user.my-escrow.index')->with(['success' => [__('Escrow created successfully')]]); 
            }catch(Exception $e) {
                return back()->with(['error' => [$e->getMessage()]]);
            }

        }elseif($checkTempData->data->env_type == 'escrowcreateapi'){ 
            try{
                $creator_table = $checkTempData->data->creator_table ?? null;
                $creator_id = $checkTempData->data->creator_id ?? null;
                $creator_guard = $checkTempData->data->creator_guard ?? null;
                $api_authenticated_guards = PaymentGatewayConst::apiAuthenticateGuard();
                if($creator_table != null && $creator_id != null && $creator_guard != null) {
                    if(!array_key_exists($creator_guard,$api_authenticated_guards)) throw new Exception(__('Request user doesn\'t save properly. Please try again'));
                    $creator = DB::table($creator_table)->where("id",$creator_id)->first();
                    if(!$creator) throw new Exception(__("Request user doesn\'t save properly. Please try again"));
                    $api_user_login_guard = $api_authenticated_guards[$creator_guard];
                    Auth::guard($api_user_login_guard)->loginUsingId($creator->id);
                }
    
                $this->createEscrow($checkTempData);
                $message = ['success' => [__("Escrow created Successful, Please Go Back Your App")]];
                return ApiResponse::onlysuccess($message); 
            }catch(Exception $e) {
                $message = ['error' => [$e->getMessage()]];
                return ApiResponse::onlyError($message); 
            }

        }elseif($checkTempData->data->env_type == 'escrowApprovalPendingweb'){ 
            if(!$checkTempData) return redirect()->route('user.my-escrow.index')->with(['error' => [__('Transaction Failed. Record didn\'t saved properly. Please try again')]]);
            $escrow        = Escrow::findOrFail($checkTempData->data->escrow->escrow_id);
            $escrowDetails = EscrowDetails::where('escrow_id', $escrow->id)->first();
            $escrow->payment_gateway_currency_id = $checkTempData->data->gateway_currency->id;
            $escrow->payment_type                = EscrowConstants::GATEWAY;
            $escrow->status                      = EscrowConstants::ONGOING; 
            $escrowDetails->buyer_pay             = $checkTempData->data->escrow->buyer_amount;
            $escrowDetails->gateway_exchange_rate = $checkTempData->data->escrow->eschangeRate; 
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
            return redirect()->route('user.my-escrow.index')->with(['success' => [__('Payment successfully completed.')]]);

        }elseif($checkTempData->data->env_type == 'escrowApprovalPendingapi'){  
            if(!$checkTempData) return ApiResponse::onlyError(['error' => [__('Transaction Failed. Record didn\'t saved properly. Please try again')]]);
            
            $creator_table = $checkTempData->data->creator_table ?? null;
            $creator_id = $checkTempData->data->creator_id ?? null;
            $creator_guard = $checkTempData->data->creator_guard ?? null;
            $api_authenticated_guards = PaymentGatewayConst::apiAuthenticateGuard();
            if($creator_table != null && $creator_id != null && $creator_guard != null) {
                if(!array_key_exists($creator_guard,$api_authenticated_guards)) throw new Exception(__('Request user doesn\'t save properly. Please try again'));
                $creator = DB::table($creator_table)->where("id",$creator_id)->first();
                if(!$creator) throw new Exception(__("Request user doesn\'t save properly. Please try again"));
                $api_user_login_guard = $api_authenticated_guards[$creator_guard];
                Auth::guard($api_user_login_guard)->loginUsingId($creator->id);
            }
    
            $escrow        = Escrow::findOrFail($checkTempData->data->escrow->escrow_id);
            $escrowDetails = EscrowDetails::where('escrow_id', $escrow->id)->first();
            $escrow->payment_gateway_currency_id = $checkTempData->data->gateway_currency->id;
            $escrow->payment_type                = EscrowConstants::GATEWAY;
            $escrow->status                      = EscrowConstants::ONGOING; 
            $escrowDetails->buyer_pay             = $checkTempData->data->escrow->buyer_amount;
            $escrowDetails->gateway_exchange_rate = $checkTempData->data->escrow->gateway_exchange_rate; 
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
        else{
            $message = ['error' => ['Payment Failed,Please Contact With Owner']];
            ApiResponse::error($message);
        }
    }

    //escrow approvel payment mail send 
    public function approvelNotificationSend($user, $escrow){
        $user->notify(new EscrowApprovel($user,$escrow));
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
        event(new UserNotificationEvent($notification_content,$user));
        send_push_notification(["user-".$user->id],[
            'title'     => $notification_content['title'],
            'body'      => $notification_content['message'],
            'icon'      => $notification_content['image'],
        ]);
    } 
}
