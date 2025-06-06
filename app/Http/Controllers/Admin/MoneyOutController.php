<?php

namespace App\Http\Controllers\Admin;

use Exception;
use App\Models\UserWallet;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Http\Helpers\Response;
use App\Models\Admin\Currency;
use App\Models\UserNotification;
use App\Constants\NotificationConst;
use App\Http\Controllers\Controller;
use App\Constants\PaymentGatewayConst;
use Illuminate\Support\Facades\Validator;
use App\Models\Admin\PaymentGatewayCurrency;
use App\Notifications\User\Withdraw\ApprovedByAdminMail;
use App\Notifications\User\Withdraw\RejectedByAdminMail;
use App\Events\User\NotificationEvent as UserNotificationEvent;

class MoneyOutController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $page_title = "All Logs";
        $transactions = Transaction::with(
            'user:id,firstname,email,username,mobile',
            'payment_gateway:id,name',
        )->where('type', 'money-out')->latest()->paginate(20);
        return view('admin.sections.money-out.index',compact(
            'page_title',
            'transactions'
        ));
    } 
    /**
     * Display All Pending Logs
     * @return view 
     */
    public function pending() {
        $page_title = "Pending Logs";
        $transactions = Transaction::with(
            'user:id,firstname,email,username,mobile',
            'payment_gateway:id,name',
        )->where('type', 'money-out')->where('status', 2)->latest()->paginate(20);
        return view('admin.sections.money-out.index',compact(
            'page_title',
            'transactions'
        ));
    } 
    /**
     * Display All Complete Logs
     * @return view
     */
    public function complete() {
        $page_title = "Completed Logs";
        $transactions = Transaction::with(
            'user:id,firstname,email,username,mobile',
            'payment_gateway:id,name',
        )->where('type', 'money-out')->where('status', 1)->latest()->paginate(20);
        return view('admin.sections.money-out.index',compact(
            'page_title',
            'transactions'
        ));
    } 
    /**
     * Display All Canceled Logs
     * @return view
     */
    public function canceled() {
        $page_title = "Canceled Logs";
        $transactions = Transaction::with(
            'user:id,firstname,email,username,mobile',
            'payment_gateway:id,name',
        )->where('type', 'money-out')->where('status', 4)->latest()->paginate(20);
        return view('admin.sections.money-out.index',compact(
            'page_title',
            'transactions'
        ));
    } 
    public function moneyOutDetails($id){

        $data = Transaction::where('id',$id)->with(
          'user:id,firstname,lastname,email,username,full_mobile',
            'gateway_currency:id,name,alias,payment_gateway_id,currency_code,rate',
        )->where('type', 'money-out')->first();
        $page_title = "Money Out details for".'  '.$data->trx_id;
        return view('admin.sections.money-out.details', compact(
            'page_title',
            'data'
        ));
    }
    public function approved(Request $request){
        $validator = Validator::make($request->all(),[
            'id' => 'required|integer',
        ]);
        if($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        $data = Transaction::where('id',$request->id)->where('status',2)->where('type', PaymentGatewayConst::TYPEMONEYOUT)->first();
        $data->status = 1;
        try{
           $approved = $data->save();
           if( $approved){
            $notification_content = [
                'title'         => "Money Out",
                'message'       => "Your Money Out request approved by admin " .get_amount(@$data->total_payable,@$data->gateway_currency->currency_code)." successful.",
                'image'         => files_asset_path('profile-default'),
            ];
            $user =$data->user;
            $user->notify(new ApprovedByAdminMail($user,$data));
            UserNotification::create([
                'type'      => NotificationConst::MONEY_OUT,
                'user_id'  =>  $data->user_id,
                'message'   => $notification_content,
            ]);
            //Push Notifications
            event(new UserNotificationEvent($notification_content,$user));
            send_push_notification(["user-".$user->id],[
                'title'     => $notification_content['title'],
                'body'      => $notification_content['message'],
                'icon'      => $notification_content['image'],
            ]);
           }
            return redirect()->back()->with(['success' => ['Money Out request approved successfully']]);
        }catch(Exception $e){
            return back()->with(['error' => [$e->getMessage()]]);
        }
    }
    public function rejected(Request $request){

        $validator = Validator::make($request->all(),[
            'id' => 'required|integer',
            'reject_reason' => 'required|string|max:200',
        ]);
        if($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        $data = Transaction::where('id',$request->id)->where('status',2)->where('type', PaymentGatewayConst::TYPEMONEYOUT)->first();
        $data->status = 4;
        $data->reject_reason = $request->reject_reason;
        try{
            $rejected =  $data->save();
            if( $rejected){
                //base_cur_charge
                if($data->user_id != null) {
                    $userWallet = UserWallet::find($data->user_wallet_id);
                    $userWallet->balance +=  $data->sender_request_amount;
                    $userWallet->save();
                }

            // notification
            $notification_content = [
                'title'         => "Money Out",
                'message'       => "Your Money Out request rejected by admin " .get_amount(@$data->total_payable,@$data->gateway_currency->currency_code),
                'image'         => files_asset_path('profile-default'),
            ];
            $user =$data->user;
            $user->notify(new RejectedByAdminMail($user,$data));
            UserNotification::create([
                'type'      => NotificationConst::MONEY_OUT,
                'user_id'  =>  $data->user_id,
                'message'   => $notification_content,
            ]);
            //Push Notifications
            event(new UserNotificationEvent($notification_content,$user));
            send_push_notification(["user-".$user->id],[
                'title'     => $notification_content['title'],
                'body'      => $notification_content['message'],
                'icon'      => $notification_content['image'],
            ]);
            }
            return redirect()->back()->with(['success' => ['Money Out request rejected successfully']]);
        }catch(Exception $e){
            return back()->with(['error' => [$e->getMessage()]]);
        }
    }
    public function search(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'text'  => 'required|string',
        ]);

        if($validator->fails()) {
            $error = ['error' => $validator->errors()];
            return Response::error($error,null,400);
        }
        $validated = $validator->validate();

        $transactions = Transaction::with(
            'user:id,firstname,email,username,mobile',
            'payment_gateway:id,name',
        )->where('type', 'money-out')->where("trx_id","like","%".$validated['text']."%")->latest()->paginate(20);
        return view('admin.components.data-table.money-out-transaction-log', compact( 
            'transactions'
        ));
    }
}
