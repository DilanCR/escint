<?php

namespace App\Http\Controllers\Admin;

use Exception;
use App\Models\User;
use App\Models\Escrow;
use App\Models\EscrowChat;
use App\Models\UserWallet;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\UserNotification;
use App\Constants\EscrowConstants;
use Illuminate\Support\Facades\DB;
use App\Constants\NotificationConst;
use App\Http\Controllers\Controller;
use App\Events\EscrowConversationEvent;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use App\Notifications\Admin\EscrowReleased;
use App\Notifications\Admin\EscrowPaymentApprovel;
use App\Events\User\NotificationEvent as UserNotificationEvent;

class EscrowController extends Controller
{
    public function index()
    {
        $page_title = "All Escrow Logs";
        $escrows = Escrow::with('escrowDetails')->latest()->paginate(20);
        return view('admin.sections.escrow.index', compact(
            'page_title',
            'escrows'
        ));
    }
    public function ongoing()
    {
        $page_title = "Ongoing Escrow Logs";
        $escrows = Escrow::with('escrowDetails')->where('status', EscrowConstants::ONGOING)->latest()->paginate(20);
        return view('admin.sections.escrow.index', compact(
            'page_title',
            'escrows'
        ));
    }
    public function paymentPending()
    {
        $page_title = "Payment Pending Escrow Logs";
        $escrows = Escrow::with('escrowDetails')->where('status', EscrowConstants::PAYMENT_PENDING)->latest()->paginate(20);
        return view('admin.sections.escrow.index', compact(
            'page_title',
            'escrows'
        ));
    }
    public function approvalPending()
    {
        $page_title = "Approval Pending Escrow Logs";
        $escrows = Escrow::with('escrowDetails')->where('status', EscrowConstants::APPROVAL_PENDING)->latest()->paginate(20);
        return view('admin.sections.escrow.index', compact(
            'page_title',
            'escrows'
        ));
    }
    public function released()
    {
        $page_title = "Released Escrow Logs";
        $escrows = Escrow::with('escrowDetails')->where('status', EscrowConstants::RELEASED)->latest()->paginate(20);
        return view('admin.sections.escrow.index', compact(
            'page_title',
            'escrows'
        ));
    }
    public function activeDispute()
    {
        $page_title = "Active Dispute Escrow Logs";
        $escrows = Escrow::with('escrowDetails')->where('status', EscrowConstants::ACTIVE_DISPUTE)->latest()->paginate(20);
        return view('admin.sections.escrow.index', compact(
            'page_title',
            'escrows'
        ));
    }
    public function disputed()
    {
        $page_title = "Disputed Escrow Logs";
        $escrows = Escrow::with('escrowDetails')->where('status', EscrowConstants::DISPUTED)->latest()->paginate(20);
        return view('admin.sections.escrow.index', compact(
            'page_title',
            'escrows'
        ));
    }
    public function canceled()
    {
        $page_title = "Canceled Escrow Logs";
        $escrows = Escrow::with('escrowDetails')->where('status', EscrowConstants::CANCELED)->latest()->paginate(20);
        return view('admin.sections.escrow.index', compact(
            'page_title',
            'escrows'
        ));
    }
    public function refunded()
    {
        $page_title = "Refunded Escrow Logs";
        $escrows = Escrow::with('escrowDetails')->where('status', EscrowConstants::REFUNDED)->latest()->paginate(20);
        return view('admin.sections.escrow.index', compact(
            'page_title',
            'escrows'
        ));
    }
    public function escrowDetails($id)
    {
        $page_title = "Escrow Details";
        $escrows = Escrow::with('escrowDetails')->findOrFail($id);
        return view('admin.sections.escrow.details', compact(
            'page_title',
            'escrows'
        ));
    }
    public function escrowChat($id)
    {
        $page_title = "Escrow Conversation";
        $escrows = Escrow::with('escrowDetails','conversations')->findOrFail($id);
        return view('admin.sections.escrow.conversation', compact(
            'page_title',
            'escrows'
        ));
    }
    //escrow conversation message send
    public function messageSend(Request $request) {
        $validator = Validator::make($request->all(),[
            'message'       => 'required|string|max:200',
            'escrow_id' => 'required|string',
        ]);
        if($validator->fails()) {
            $error = ['error' => $validator->errors()];
            return Response::error($error,null,400);
        }
        $validated = $validator->validate();
        
        $escrow = Escrow::findOrFail($validated['escrow_id']);
        
        if(!$escrow) return Response::error(['error' => ['This escrow is closed.']]);

        $data = [
            'escrow_id'    => $escrow->id,
            'sender'                    => auth()->user()->id,
            'sender_type'               => "ADMIN",
            'message'                   => $validated['message'],
            'receiver_type'             => "USER",
        ];

        try{
            $chat_data = EscrowChat::create($data); 
        }catch(Exception $e) {
            return $e;
            $error = ['error' => ['Message Sending faild! Please try again.']];
            return Response::error($error,null,500);
        }

        try{
            event(new EscrowConversationEvent($escrow,$chat_data));
        }catch(Exception $e) {
            return $e;
            $error = ['error' => ['SMS Sending faild! Please try again.']];
            return Response::error($error,null,500);
        }
    }
    //payment release 
    public function releasePayment(Request $request, $type){
        $validator = Validator::make($request->all(),[
            'target'       => 'required', 
        ]);
        $validated = $validator->validate();
        $escrow = Escrow::findOrFail($validated['target']);
         //status check 
        if($escrow->status != EscrowConstants::ACTIVE_DISPUTE) {
            return redirect()->back()->with(['error' => ['Something went wrong']]);
        }
        if ($escrow->role == $type) {
            $targetUserId = $escrow->user_id;
        }else{
            $targetUserId = $escrow->buyer_or_seller_id;
        }
        
        if ($type == "seller") {
            $currencyId = $escrow->escrowCurrency->id;
        }else if($type == "buyer"){
            if ($escrow->payment_type == EscrowConstants::MY_WALLET) {
                $currencyId = $escrow->escrowCurrency->id;
            }else if($escrow->payment_type == EscrowConstants::GATEWAY){
                $currencyId = $escrow->paymentGatewayCurrency->currency->id;
            }
            
        }
        $userWallet = UserWallet::where('user_id',$targetUserId)->where('currency_id',$currencyId)->first(); 
        //check release amount 
        if ($type == "seller") {
            $releaseAmount = $escrow->escrowDetails->seller_get;
        }else {
            $releaseAmount = $escrow->escrowDetails->buyer_pay;
        }
        $userWallet->balance += $releaseAmount;  
        $escrow->status = EscrowConstants::RELEASED;
        DB::beginTransaction();
        try{ 
            $userWallet->save();
            $escrow->save();
            DB::commit();
            try {
                //send user notification
                $targetUser = User::findOrFail($targetUserId);
                $notification_content = [
                    'title'         => "Payment released",
                    'message'       => "Admin released your payment",
                    'time'          => Carbon::now()->diffForHumans(),
                    'image'         => files_asset_path('profile-default'),
                ];

                UserNotification::create([
                    'type'      => NotificationConst::ESCROW_CREATE,
                    'user_id'  =>  $targetUserId,
                    'message'   => $notification_content,
                ]);
                $targetUser->notify(new EscrowReleased($targetUser,$escrow));
                //Push Notifications
                event(new UserNotificationEvent($notification_content,$targetUser));
                send_push_notification(["user-".$targetUser->id],[
                    'title'     => $notification_content['title'],
                    'body'      => $notification_content['message'],
                    'icon'      => $notification_content['image'],
                ]);
            } catch (\Throwable $th) {
                //throw $th;
            }
      
        }catch(Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage());
        } 

        return  redirect()->back()->with(['success' => ['Payment released successfully']]);
    }
    //manual payment approval
    public function manualPaymentApproved(Request $request){
        $validator = Validator::make($request->all(),[
            'id' => 'required|integer',
        ]);
        if($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        $escrow = Escrow::findOrFail($request->id);
        $escrow->status = EscrowConstants::ONGOING;
        try{ 
            $escrow->save();
            if ($escrow->role == "buyer") {
                $targetUserId = $escrow->user_id;
            }else{
                $targetUserId = $escrow->buyer_or_seller_id;
            }
            $targetUser = User::findOrFail($targetUserId);
            $targetUser->notify(new EscrowPaymentApprovel($targetUser,$escrow));
            $notification_content = [
                'title'         => "Payment approved",
                'message'       => "Admin approved your payment",
                'time'          => Carbon::now()->diffForHumans(),
                'image'         => files_asset_path('profile-default'),
            ];

            UserNotification::create([
                'type'      => NotificationConst::ESCROW_CREATE,
                'user_id'  =>  $targetUserId,
                'message'   => $notification_content,
            ]);
            //Push Notifications
            event(new UserNotificationEvent($notification_content,$targetUser));
            send_push_notification(["user-".$targetUser->id],[
                'title'     => $notification_content['title'],
                'body'      => $notification_content['message'],
                'icon'      => $notification_content['image'],
            ]);
            return redirect()->back()->with(['success' => ['Escrow request approved successfully']]);
        }catch(Exception $e){
            return back()->with(['error' => [$e->getMessage()]]);
        }
    }
    //manual payment rejection 
    public function manualPaymentRejected(Request $request){
        $validator = Validator::make($request->all(),[
            'id' => 'required|integer',
            'reject_reason' => 'required|string|max:200',
        ]);
        if($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        } 
        $escrow = Escrow::findOrFail($request->id);
        $escrow->reject_reason = $request->reject_reason;
        $escrow->status = EscrowConstants::CANCELED;
        try{ 
            $escrow->save();
            return redirect()->back()->with(['success' => ['Escrow request rejected successfully']]);
        }catch(Exception $e){
            return back()->with(['error' => [$e->getMessage()]]);
        }
    }
}
