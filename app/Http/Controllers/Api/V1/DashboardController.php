<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Escrow; 
use App\Models\UserWallet;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\UserNotification;
use App\Constants\EscrowConstants;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth; 
use App\Http\Helpers\Api\Helpers as ApiResponse;

class DashboardController extends Controller
{
    /**
     * Dashboard Data Fetch
     *
     * @method GET
     * @return \Illuminate\Http\Response
    */

    public function dashboard(Request $request){
        $escrows = Escrow::where('user_id', auth()->user()->id)->orWhere('buyer_or_seller_id',auth()->user()->id)->get();
        $total_escrow = $escrows->count();
        $compledted_escrow = $escrows->where('status', EscrowConstants::RELEASED)->count();
        $pending_escrow = $escrows->where('status', EscrowConstants::ONGOING)->count();
        $dispute_escrow = $escrows->where('status', EscrowConstants::ACTIVE_DISPUTE)->count();
        // User Data
        $user = Auth::guard('api')->user();
        $user_data =[
            'default_image' => "public/backend/images/default/profile-default.webp",
            "image_path"    => "public/frontend/user",
            'user'          => $user,
        ];
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
        //transactions log
        $transactions = Transaction::where('user_id',auth()->user()->id)->latest()->take(5)->get()->map(function($item){
            return[
                'id'                    => $item->id,
                'trx_id'                => $item->trx_id,
                'gateway_currency'      => $item->gateway_currency->name ?? null,
                'transaction_type'      => $item->type,
                'sender_request_amount' => $item->sender_request_amount,
                'sender_currency_code'  => $item->sender_currency_code,
                'total_payable'         => $item->total_payable,
                'gateway_currency_code' => $item->gateway_currency->currency_code ?? null,
                'exchange_rate'         => $item->exchange_rate,
                'fee'                   => $item->transaction_details->total_charge,
                'rejection_reason'      => $item->reject_reason ?? null,
                'exchange_currency'     => $item->details->charges->exchange_currency ?? null,
                'status'                => $item->status,
                'string_status'         => $item->stringStatus->value,
                'created_at'            => $item->created_at,
            ];
        });

        $data = [
            'total_escrow'      => $total_escrow,
            'compledted_escrow' => $compledted_escrow,
            'pending_escrow'    => $pending_escrow,
            'dispute_escrow'    => $dispute_escrow,
            'user_id'    => auth()->user()->id,
            'user'              => $user_data,
            'userWallet'        => $userWallet,
            'transactions'      => $transactions,
        ];

        $message =  ['success'=>[__('Dashboard data successfully fetch!')]];
        return ApiResponse::success($message, $data);
    }
    public function userNotification() {
        $notifications = UserNotification::where('user_id', auth()->user()->id)->latest()->take(5)->get()->map(function($item){
            return[ 
                'id'      => $item->id,
                'user_id' => $item->user_id,
                'type'    => $item->type,
                'message' => [ 
                    'title'   => $item->message->title,
                    'message' => $item->message->message,
                    'time'    => $item->created_at->diffForHumans(),
                ],
                'seen'       => $item->seen,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
            ];
        });
        $data = [
            'notifications'      => $notifications, 
        ];
        $message =  ['success'=>[__('Notifications data successfully fetch!')]];
        return ApiResponse::success($message, $data);
    }
    public function allTransactions() {
        //transactions log
        $transactions = Transaction::where('user_id',auth()->user()->id)->latest()->paginate(15);
        $transactions->getCollection()->transform(function ($item) {
            return [
                'id'                    => $item->id,
                'trx_id'                => $item->trx_id,
                'gateway_currency'      => $item->gateway_currency->name ?? null,
                'transaction_type'      => $item->type,
                'sender_request_amount' => $item->sender_request_amount,
                'sender_currency_code'  => $item->sender_currency_code,
                'total_payable'         => $item->total_payable,
                'gateway_currency_code' => $item->gateway_currency->currency_code ?? null,
                'exchange_rate'         => $item->exchange_rate,
                'fee'                   => $item->transaction_details->total_charge,
                'rejection_reason'      => $item->reject_reason ?? null,
                'exchange_currency'     => $item->details->charges->exchange_currency ?? null,
                'status'                => $item->status,
                'string_status'         => $item->stringStatus->value,
                'created_at'            => $item->created_at,
            ];
        });
        $data = [
            'transactions'      => $transactions,
        ];

        $message =  ['success'=>[__('Transactions data successfully fetch!')]];
        return ApiResponse::success($message, $data);
    }


}
