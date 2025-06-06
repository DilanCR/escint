<?php
namespace App\Http\Controllers\User;
use Exception;
use App\Models\Escrow;
use App\Models\UserWallet;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Http\Helpers\Response;
use App\Models\UserNotification;
use App\Constants\EscrowConstants;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Constants\PaymentGatewayConst;
use App\Models\User;

class DashboardController extends Controller
{
    public function index() {
        $page_title = "Dashboard";
        $escrows = Escrow::where('user_id', auth()->user()->id)->orWhere('buyer_or_seller_id',auth()->user()->id)->get();
        $total_escrow = $escrows->count();
        $compledted_escrow = $escrows->where('status', EscrowConstants::RELEASED)->count();
        $pending_escrow = $escrows->where('status', EscrowConstants::ONGOING)->count();
        $dispute_escrow = $escrows->where('status', EscrowConstants::ACTIVE_DISPUTE)->count();
        
        $userWallet = UserWallet::with('currency')->where(['user_id' => auth()->user()->id, 'status' => 1])->orderBY('balance', 'DESC')->get(); 
        $transactions = Transaction::where('user_id', auth()->user()->id)->orderByDesc("id")->take(5)->get(); 

          //chart data calculation 
          $start = strtotime(date('Y-m-01'));
          $end = strtotime(date('Y-m-31'));
          //add money data 
          $add_money_success_data  = [];
          $money_out_success_data  = [];
          $exchange_money_success_data = [];
          //escrow data 
          $escrow_release_data = []; 
          //get chart data  
          $month_day  = [];
          while ($start <= $end) {
              $start_date = date('Y-m-d', $start);
              //================ Monthley add money start======================== 
              $add_money_success = Transaction::where('user_id', auth()->user()->id)
                                          ->where('type', PaymentGatewayConst::TYPEADDMONEY)
                                          ->whereDate('created_at',$start_date)
                                          ->where('status',1)
                                          ->count();
              $money_out_success = Transaction::where('user_id', auth()->user()->id)
                                          ->where('type', PaymentGatewayConst::TYPEMONEYOUT)
                                          ->whereDate('created_at',$start_date)
                                          ->where('status',1)
                                          ->count();
              $exchange_money_success = Transaction::where('user_id', auth()->user()->id)
                                          ->where('type', PaymentGatewayConst::TYPEMONEYEXCHANGE)
                                          ->whereDate('created_at',$start_date)
                                          ->where('status',1)
                                          ->count();
              $add_money_success_data[]  = $add_money_success;
              $money_out_success_data[]  = $money_out_success;
              $exchange_money_success_data[]  = $exchange_money_success;
              //================ Monthley add money end========================
              //================ Monthley escrow start========================
              $escrow_release = Escrow::where('user_id', auth()->user()->id)
                                  ->where('status', EscrowConstants::RELEASED)
                                  ->whereDate('created_at',$start_date) 
                                  ->count();
  
              $escrow_release_data[]  = $escrow_release;
              //================ Monthley escrow end========================
  
              $month_day[] = date('Y-m-d', $start);
              $start = strtotime('+1 day',$start);
          }

        // Chart one
        $chart_one_data = [
            'released_escrow_by_month'  => $escrow_release_data, 
        ];
        // Chart two
        $chart_two_data = [
            'add_money'  =>$add_money_success_data,
            'money_out'  => $money_out_success_data,
            'exchange_money' => $exchange_money_success_data,
        ];
        $chartData =[ 
            'chart_one_data'   => $chart_one_data, 
            'chart_two_data'   => $chart_two_data, 
            'month_day'   => $month_day, 
        ];
        $state =(object)[ 
            'total_escrow'      => $total_escrow,
            'compledted_escrow' => $compledted_escrow,
            'pending_escrow'    => $pending_escrow,
            'dispute_escrow'    => $dispute_escrow,
        ];
        return view('user.dashboard',compact('page_title','transactions','userWallet','chartData','state'));
    }
    public function logout(Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('user.login');
    }
    public function deleteAccount(Request $request) {  
        $user = User::findOrFail(auth()->user()->id);
        $user->status = false;
        $user->email_verified = false;
        $user->sms_verified = false;
        $user->kyc_verified = false;
        $user->deleted_at = now();
        $user->save();
        try{
            Auth::logout();
            return redirect()->route('index')->with(['success' => [__('Your profile deleted successfully!')]]);
        }catch(Exception $e) {
            return back()->with(['error' => [__('Something went wrong! Please try again')]]);
        }
    }
    public function userNotificationUpdate() {
        $user = auth()->user(); 
        try{
            $userNotification = UserNotification::where('user_id',$user->id)->where('seen',0)->update([
                'seen'     => true,
            ]);
        }catch(Exception $e) {
            $error = ['error' => [__('Something went wrong! Please try again')]];
            return Response::error($error,null,404);
        }

        $success = ['success' => [__('Notifications seen successfully!')]];
        return Response::success($success,null,200);
    }  
}
