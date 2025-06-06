<?php

namespace App\Http\Controllers\Admin;

use Exception;
use App\Models\User;
use App\Models\Escrow;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Http\Helpers\Response;
use App\Models\UserSupportTicket;
use App\Constants\EscrowConstants;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Constants\PaymentGatewayConst;
use App\Models\Admin\AdminNotification;
use App\Providers\Admin\BasicSettingsProvider;
use Pusher\PushNotifications\PushNotifications;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $page_title = "Dashboard"; 
        //escrow  calculate
        $escrow = Escrow::get();
        $total_escrow = $escrow->count();
        $ongoing_escrow = $escrow->where('status', EscrowConstants::ONGOING)->count();
        $active_dispute_escrow = $escrow->where('status', EscrowConstants::ACTIVE_DISPUTE)->count();
        $released_escrow = $escrow->where('status', EscrowConstants::RELEASED)->count();

        //transactions
        $transaction = Transaction::get();
        $latest_transactions = Transaction::with(
            'user:id,firstname,email,username,mobile',
            'payment_gateway:id,name',
        )->latest()->paginate(5);
        //calculate add money balance
        $add_money_trn_active = $transaction->where('type', PaymentGatewayConst::TYPEADDMONEY)->where('status', PaymentGatewayConst::STATUSSUCCESS);
        $add_money_trn_pending = $transaction->where('type', PaymentGatewayConst::TYPEADDMONEY)->where('status', PaymentGatewayConst::STATUSPENDING);
      
        $active_add_money = getTotalAmountOnBaseCurr($add_money_trn_active);
        $pending_add_money = getTotalAmountOnBaseCurr($add_money_trn_pending);

        //calculate money out balance
        $money_out_trn_active = $transaction->where('type', PaymentGatewayConst::TYPEMONEYOUT)->where('status', PaymentGatewayConst::STATUSSUCCESS);
        $money_out_trn_pending = $transaction->where('type', PaymentGatewayConst::TYPEMONEYOUT)->where('status', PaymentGatewayConst::STATUSPENDING);
      
        $active_money_out = getTotalAmountOnBaseCurr($money_out_trn_active);
        $pending_money_out = getTotalAmountOnBaseCurr($money_out_trn_pending);

        //profile calculation 
        $transaction_profit = $transaction->where('status', PaymentGatewayConst::STATUSSUCCESS);
        $transactions_profit_this_month = $transaction->filter(function ($transaction) {
            return $transaction->status === PaymentGatewayConst::STATUSSUCCESS
                && $transaction->created_at->isCurrentMonth();
        }); 
        $transactions_profit_last_month = $transaction->filter(function ($transaction) {
            return $transaction->status === PaymentGatewayConst::STATUSSUCCESS
                && $transaction->created_at->isLastMonth();
        });
        $transaction_profite_amount = getTotalProfitOnBaseCurr($transaction_profit);
        $transaction_profite_this_month_amount = getTotalProfitOnBaseCurr($transactions_profit_this_month);
        $transaction_profite_last_month_amount = getTotalProfitOnBaseCurr($transactions_profit_last_month);

        $total_profite_amount = $transaction_profite_amount;  
        //user count
        $user = User::get();
        $total_user = $user->count();
        $active_user = $user->where('status', true)->count();
        //support ticket count
        $support_ticket = UserSupportTicket::get();
        $total_support_ticket = $support_ticket->count();
        $pending_support_ticket = $support_ticket->where('status', 3)->count();
        $active_support_ticket = $support_ticket->where('status', 2)->count();
        $solved_support_ticket = $support_ticket->where('status', 1)->count();

        //chart data calculation 
        $start = strtotime(date('Y-m-01'));
        $end = strtotime(date('Y-m-31'));
        //add money data 
        $add_money_pending_data  = [];
        $add_money_success_data  = [];
        $add_money_canceled_data = [];
        $add_money_all_data = [];
        // Money Out
        $Money_out_pending_data  = [];
        $Money_out_success_data  = [];
        $Money_out_canceled_data = [];
        $Money_out_hold_data     = [];
        //escrow data 
        $escrow_release_data = [];
        $escrow_ongoing_data = [];
        $escrow_active_dispute_data = [];
        //get chart data  
        $month_day  = [];
        while ($start <= $end) {
            $start_date = date('Y-m-d', $start);

            //================ Monthley add money start========================
            $add_money_pending = Transaction::where('type', PaymentGatewayConst::TYPEADDMONEY)
                                        ->whereDate('created_at',$start_date)
                                        ->where('status', 2)
                                        ->count();
            $add_money_success = Transaction::where('type', PaymentGatewayConst::TYPEADDMONEY)
                                        ->whereDate('created_at',$start_date)
                                        ->where('status', 1)
                                        ->count();
            $add_money_canceled = Transaction::where('type', PaymentGatewayConst::TYPEADDMONEY)
                                        ->whereDate('created_at',$start_date)
                                        ->where('status', 4)
                                        ->count();
            $add_money_all = Transaction::where('type', PaymentGatewayConst::TYPEADDMONEY)
                                        ->whereDate('created_at',$start_date) 
                                        ->count();
            $add_money_pending_data[]  = $add_money_pending;
            $add_money_success_data[]  = $add_money_success;
            $add_money_canceled_data[] = $add_money_canceled;
            $add_money_all_data[]      = $add_money_all;
            //================ Monthley add money end========================
            //====================== Monthley money Out start==============
            $money_pending = Transaction::where('type', PaymentGatewayConst::TYPEMONEYOUT)
                            ->whereDate('created_at',$start_date)
                            ->where('status', 2)
                            ->count();
            $money_success = Transaction::where('type', PaymentGatewayConst::TYPEMONEYOUT)
                            ->whereDate('created_at',$start_date)
                            ->where('status', 1)
                            ->count();
            $money_canceled = Transaction::where('type', PaymentGatewayConst::TYPEMONEYOUT)
                            ->whereDate('created_at',$start_date)
                            ->where('status', 4)
                            ->count();
            $money_hold = Transaction::where('type', PaymentGatewayConst::TYPEMONEYOUT)
                            ->whereDate('created_at',$start_date)
                            ->where('status', 3)
                            ->count();
            $Money_out_pending_data[]  = $money_pending;
            $Money_out_success_data[]  = $money_success;
            $Money_out_canceled_data[] = $money_canceled;
            $Money_out_hold_data[]     = $money_hold;
            //====================== Monthley money Out end==============
            //================ Monthley escrow start========================
            $escrow_release = Escrow::where('status', EscrowConstants::RELEASED)
                                ->whereDate('created_at',$start_date) 
                                ->count();
            $escrow_ongoing = Escrow::where('status', EscrowConstants::ONGOING)
                                ->whereDate('created_at',$start_date) 
                                ->count();
            $escrow_active_dispute = Escrow::where('status', EscrowConstants::ACTIVE_DISPUTE)
                                ->whereDate('created_at',$start_date) 
                                ->count();

            $escrow_release_data[]  = $escrow_release;
            $escrow_ongoing_data[]  = $escrow_ongoing;
            $escrow_active_dispute_data[]  = $escrow_active_dispute;
            //================ Monthley escrow end========================


            $month_day[] = date('Y-m-d', $start);
            $start = strtotime('+1 day',$start);
        }
        // Chart one
        $chart_one_data = [
            'add_money_pending_data'  => $add_money_pending_data,
            'add_money_success_data'  => $add_money_success_data,
            'add_money_canceled_data' => $add_money_canceled_data,
            'add_money_all'           => $add_money_all_data,
        ];
        // Chart three
        $chart_two_data = [
            'pending_data'  => $Money_out_pending_data,
            'success_data'  => $Money_out_success_data,
            'canceled_data' => $Money_out_canceled_data,
            'hold_data'     => $Money_out_hold_data,
        ];
        // Chart three
        $chart_three_data = [
            'escrow_release_data'  => $escrow_release_data,
            'escrow_ongoing_data'  => $escrow_ongoing_data,
            'escrow_active_dispute_data' => $escrow_active_dispute_data, 
        ];
    
        $total_user = User::toBase()->count();
        $unverified_user = User::toBase()->where('sms_verified', 0)->count();
        $active_user = User::toBase()->where('status', 1)->count();
        $banned_user = User::toBase()->where('status', 0)->count();
        // Chart four | User analysis
        $chart_four_data = [$active_user, $banned_user,$unverified_user,$total_user];

        $escrow_profit = 0; 
        try{
            $totalAmount = 0;
            foreach ($escrow->where('status', EscrowConstants::RELEASED) as $escrow) {
                $totalCharge = $escrow->escrowDetails->fee??0;
                $walletRate = $escrow->escrowCurrency->rate;
                $result = (floatval($totalCharge) *floatval( $walletRate)) ; 
                $totalAmount += $result;
            }
            $escrow_profit = $totalAmount??0 ;
        }catch(Exception $e){

        }
        return view('admin.sections.dashboard.index',compact(
            'page_title',
            'latest_transactions',
            'total_escrow',
            'ongoing_escrow',
            'active_dispute_escrow',
            'released_escrow',

            'active_add_money',
            'pending_add_money',

            'active_money_out',
            'pending_money_out',

            'total_profite_amount', 
            'transaction_profite_this_month_amount',
            'transaction_profite_last_month_amount',

            'total_user',
            'active_user',
            'total_support_ticket',
            'pending_support_ticket',
            'active_support_ticket',
            'solved_support_ticket',
 
            'chart_one_data',
            'chart_two_data',
            'chart_three_data',
            'chart_four_data',
            'month_day',

            'escrow_profit',
        ));
    }


    /**
     * Logout Admin From Dashboard
     * @return view
     */
    public function logout(Request $request) { 
        $push_notification_setting = BasicSettingsProvider::get()->push_notification_config;
        $admin = auth()->user();
        try{
            if($push_notification_setting) {
                $method = $push_notification_setting->method ?? false;
    
                if($method == "pusher") {
                    $instant_id     = $push_notification_setting->instance_id ?? false;
                    $primary_key    = $push_notification_setting->primary_key ?? false;
    
                    if($instant_id && $primary_key) {
                        $pusher_instance = new PushNotifications([
                            "instanceId"    => $instant_id,
                            "secretKey"     => $primary_key,
                        ]);
    
                        $pusher_instance->deleteUser("".Auth::user()->id."");
                    }
                }
    
            }
            $admin->update([
                'last_logged_out'   => now(),
                'login_status'      => false,
            ]);
        }catch(Exception $e) {
            // Handle Error
        }

        Auth::guard()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
 
    /**
     * Function for clear admin notification
     */
   

    public function notificationsClear() {
        $admin = auth()->user();
        if(!$admin) {
            return false;
        }
        try{
            $notifications = AdminNotification::auth()->where('clear_at',null)->get();
            foreach( $notifications as $notify){
                $notify->clear_at = now();
                $notify->save();
            }
        }catch(Exception $e) {
            $error = ['error' => [__("Something went wrong! Please try again.")]];
            return Response::error($error,null,404);
        }
        $success = ['success' => [__("Notifications clear successfully!")]];
        return Response::success($success,null,200);
    }

    //all notification show
    public function allNotifications() {
        $page_title = "All Notifications";
        $notifications = AdminNotification::auth()->latest()->paginate(30);
        return view('admin.sections.notifications.index', compact('page_title','notifications'));
    }
}
