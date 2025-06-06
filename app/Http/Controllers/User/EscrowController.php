<?php

namespace App\Http\Controllers\User;

use Exception;
use App\Models\User;
use App\Models\Escrow;
use App\Models\UserWallet;
use Illuminate\Http\Request;
use App\Models\EscrowDetails;
use App\Models\TemporaryData;
use App\Models\Admin\Currency;
use App\Models\EscrowCategory;
use Illuminate\Support\Carbon;
use App\Models\UserNotification;
use App\Constants\EscrowConstants;
use Illuminate\Support\Facades\DB;
use App\Models\Admin\BasicSettings;
use App\Constants\NotificationConst;
use App\Http\Controllers\Controller;
use App\Models\Admin\PaymentGateway;
use Illuminate\Support\Facades\Auth; 
use App\Constants\PaymentGatewayConst;
use App\Models\Admin\CryptoTransaction;
use Illuminate\Support\Facades\Session;
use App\Models\Admin\TransactionSetting;
use App\Traits\ControlDynamicInputFields;
use Illuminate\Support\Facades\Validator;
use App\Http\Helpers\EscrowPaymentGateway;
use App\Notifications\Escrow\EscrowRequest;
use App\Models\Admin\PaymentGatewayCurrency;
use App\Events\User\NotificationEvent as UserNotificationEvent;

class EscrowController extends Controller
{
    use ControlDynamicInputFields;
        /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        $page_title = "My Escrow";
        $escrowData = Escrow::with('escrowCategory','escrowDetails')->where('user_id', auth()->user()->id)->orWhere('buyer_or_seller_id',auth()->user()->id)->latest()->paginate(20);
        return view('user.my-escrow.index', compact('page_title','escrowData'));
    } 
        /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request) {
        $page_title                  = "Create New Escrow";
        $currencies                  = Currency::where('status', true)->get();
        $escrowCategories            = EscrowCategory::where('status', true)->latest()->get();
        $payment_gateways_currencies = PaymentGatewayCurrency::whereHas('gateway', function ($gateway) {
            $gateway->where('slug', PaymentGatewayConst::add_money_slug());
            $gateway->where('status', 1);
        })->get();
        $user_pass_data = $request->all();
        return view('user.my-escrow.create', compact('page_title', 'escrowCategories','currencies','payment_gateways_currencies','user_pass_data'));
    }
    //===================== escrow submission ======================================================
    // escrow submit 
    public function submit(Request $request) { 
        $basic_setting = BasicSettings::first();
        $user          = auth()->user();
        if($basic_setting->kyc_verification){
            if( $user->kyc_verified == 0){
                return redirect()->route('user.authorize.kyc')->with(['error' => [__('Please submit kyc information')]]);
            }elseif($user->kyc_verified == 2){
                return redirect()->route('user.authorize.kyc')->with(['error' => [__('Please wait before admin approved your kyc information')]]);
            }elseif($user->kyc_verified == 3){
                return redirect()->route('user.authorize.kyc')->with(['error' => [__('Admin rejected your kyc information, Please re-submit again')]]);
            }
        }
        $page_title = "Escrow Details";
        $validator  = Validator::make($request->all(),[
            'title'                 => 'required|string',
            'escrow_category'       => 'required|integer',
            'role'                  => 'required|string',
            'who_will_pay_options'  => 'required|string',
            'buyer_seller_identify' => 'required',
            'amount'                => 'required|numeric',
            'escrow_currency'       => 'required|string',
            'payment_gateway'       => 'nullable',
            'remarks'               => 'nullable|string',
            'file.*'                => "nullable|file|max:100000|mimes:jpg,jpeg,png,pdf,zip",
        ]);
        if($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        $validated            = $validator->validate();
        $escrowCategory       = EscrowCategory::find($validated['escrow_category']);
        $getEscrowChargeLimit = TransactionSetting::find(1);
        $sender_currency      = Currency::where('code', $validated['escrow_currency'])->first();
        $digitShow            = $sender_currency->type == "CRYPTO" ? 6 : 2 ;
        $opposite_user                 = User::where('username',$validated['buyer_seller_identify'])->orWhere('email',$validated['buyer_seller_identify'])->first();
        //user check 
        if(empty($opposite_user) || $opposite_user->email == auth()->user()->email) return redirect()->back()->withInput()->with(['error' => [__('User not found')]]);
        //get payment method
        $payment_type = EscrowConstants::DID_NOT_PAID;
        $payment_gateways_currencies = null;
        if ($validated['role'] == "buyer") {
            if ($validated['payment_gateway'] == "myWallet") {
                $user_wallets = UserWallet::where(['user_id' => auth()->user()->id, 'currency_id' => $sender_currency->id])->first();
                if(empty($user_wallets)) return redirect()->back()->withInput()->with(['error' => ['Wallet not found.']]); 
                if($user_wallets->balance == 0 || $user_wallets->balance < 0 || $user_wallets->balance < $validated['amount']) return redirect()->back()->withInput()->with(['error' => [__('Insuficiant Balance')]]);
                $payment_method        = "My Wallet";
                $gateway_currency      = $validated['escrow_currency'];
                $gateway_exchange_rate = 1;
                $payment_type          = EscrowConstants::MY_WALLET;
            } else{
                $payment_gateways_currencies = PaymentGatewayCurrency::with('gateway')->find($validated['payment_gateway']);
                if(!$payment_gateways_currencies || !$payment_gateways_currencies->gateway) {
                    return redirect()->back()->withInput()->with(['error' => ['Payment gateway not found.']]); 
                }
                $payment_method   = $payment_gateways_currencies->name;
                $gateway_currency = $payment_gateways_currencies->currency_code;
                                                                                                          //calculate gateway exchange rate 
                $gateway_exchange_rate =  (1/$sender_currency->rate)*$payment_gateways_currencies->rate;  //this currency is converted in payment gateway currency
                $payment_type = EscrowConstants::GATEWAY;
            }
        }
        $request_amount = $validated['amount'];
        //convert escrow currency amount into default currency
        $usd_exchange_amount = (1/$sender_currency->rate)*$request_amount;
        //charge calculate in USD currency 
        $usd_fixed_charge   = $getEscrowChargeLimit->fixed_charge;
        $usd_percent_charge = ($getEscrowChargeLimit->percent_charge/100)*$usd_exchange_amount;
        $usd_total_charge   = $usd_fixed_charge+$usd_percent_charge;
        //final charge in escrow currency
        $escrow_total_charge = $usd_total_charge*$sender_currency->rate;
        //limit check 
        if($getEscrowChargeLimit->min_limit > $usd_exchange_amount || $getEscrowChargeLimit->max_limit < $usd_exchange_amount) return redirect()->back()->withInput()->with(['error' => [__('Please follow the escrow limit')]]);
        //calculate seller amount 
        if ($validated['who_will_pay_options'] == "seller") {
            $seller_amount = $request_amount - $escrow_total_charge;
        }else if($validated['who_will_pay_options'] == "half"){
            $seller_amount = $request_amount - ($escrow_total_charge/2);
        }else if($validated['who_will_pay_options'] == "me" && $validated['role'] == "seller"){
            $seller_amount = $request_amount - $escrow_total_charge;
        }else{
            $seller_amount = $request_amount;
        } 
        //calculate buyer amount 
        if ($validated['role'] == "buyer") {
            if ($validated['who_will_pay_options'] == "buyer") {
                $buyer_amount = ($request_amount+$escrow_total_charge)*$gateway_exchange_rate;
            }else if($validated['who_will_pay_options'] == "half"){
                $buyer_amount = ($request_amount + ($escrow_total_charge/2))*$gateway_exchange_rate;
            }else if($validated['who_will_pay_options'] == "me" && $validated['role'] == "buyer"){
                $buyer_amount = ($request_amount+$escrow_total_charge)*$gateway_exchange_rate;
            }else{
                $buyer_amount = $request_amount*$gateway_exchange_rate;
            } 
            if ($validated['payment_gateway'] == "myWallet") {
                if($user_wallets->balance == 0 || $user_wallets->balance < 0 || $user_wallets->balance < $buyer_amount) return redirect()->back()->withInput()->with(['error' => ['Insuficiant Balance.Here escrow charge will be substack with your wallet. Your escrow charge is '.$escrow_total_charge.' '.$validated['escrow_currency']]]);
            }
        } 
        $oldData = (object) [ 
            'buyer_or_seller_id'          => $opposite_user->id,
            'escrow_category_id'          => $validated['escrow_category'],
            'payment_type'                => $payment_type,
            'payment_gateway_currency_id' => $payment_type == EscrowConstants::GATEWAY ? $payment_gateways_currencies->id : null,

            'user_id'         => auth()->user()->id,
            'title'           => $validated['title'],
            'role'            => $validated['role'],
            'product_type'    => $escrowCategory->name,
            'amount'          => $validated['amount'],
            'escrow_currency' => $validated['escrow_currency'],
            'charge_payer'    => $validated['who_will_pay_options'],

            'escrow_total_charge'   => $escrow_total_charge,
            'seller_amount'         => $seller_amount ?? 0,
            'gateway_currency'      => $gateway_currency ?? "null",
            'payment_method'        => $payment_method ?? "null",
            'gateway_exchange_rate' => $gateway_exchange_rate ?? 0,
            'buyer_amount'          => $buyer_amount ?? 0,

            'remarks' => $validated['remarks'] ?? null,
            'file'    => null,
        ];
        //file upload
        $attachment = [];
        if($request->hasFile('file')) {
            $validated_files = $request->file("file");
            $attachment      = [];
            $files_link      = [];
            foreach($validated_files as $item) {
                $upload_file = upload_file($item,'escrow-temp-file');
                if($upload_file != false) {
                    $attachment[] = [
                        'attachment'      => $upload_file['name'],
                        'attachment_info' => json_encode($upload_file),
                        'created_at'      => now(),
                    ];
                }

                $files_link[] = get_files_path('escrow-temp-file') . "/". $upload_file['name'];
            }

            try{
                $attachment = $attachment;
            }catch(Exception $e) {
                delete_files($files_link); 
                return back()->with(['error' => [__('Opps! Failed to upload attachment. Please try again')]]);
            }
        }
        $identifier = generate_unique_string("escrows","escrow_id",16);
        $tempData = [
            'trx'              => $identifier,
            'escrow'           => $oldData,
            'gateway_currency' => $payment_gateways_currencies ?? null,
            'attachment'       => json_encode($attachment) ?? null,
            'creator_table'         => auth()->guard(get_auth_guard())->user()->getTable(),
            'creator_id'       => auth()->guard(get_auth_guard())->user()->id,   //for sscommerz relogin after payment
            'creator_guard'    => get_auth_guard(),                              //for sscommerz relogin after payment
        ];
        $this->addEscrowTempData($identifier, $tempData);
        Session::put('identifier',$identifier);
        return view('user.my-escrow.escrow-preview', compact('page_title','oldData','digitShow','identifier'));
    }
    //escrow temp data insert
    public function addEscrowTempData($identifier,$data) {  
        return TemporaryData::create([
            'type'       => "add-escrow",
            'identifier' => $identifier,
            'data'       => $data,
        ]);
    }
    //===================== end escrow submission ==================================================
    //====================== escrow payment ========================================================
    public function successConfirm(Request $request) {
        $validator  = Validator::make($request->all(),[
            'identifier' => 'required',
        ]);
        if($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        } 
        $identifier = $request->identifier ?? session()->get('identifier');
        $tempData   = TemporaryData::where("identifier",$identifier)->first();
        if ($tempData->data->escrow->role == EscrowConstants::SELLER_TYPE) { 
            $this->createEscrow($tempData);
            return redirect()->route('user.my-escrow.index')->with(['success' => [__('Escrow created successfully')]]);
        }
        //escrow wallet payment 
        if ($tempData->data->escrow->payment_type == EscrowConstants::MY_WALLET) { 
            $this->escrowWalletPayment($tempData);
            $this->createEscrow($tempData);
            return redirect()->route('user.my-escrow.index')->with(['success' => [__('Escrow created successfully')]]); 
        }
        //escrow payment by payment gateway
        if ($tempData->data->escrow->payment_type == EscrowConstants::GATEWAY) {
            try{
                $instance = EscrowPaymentGateway::init($tempData)->gateway(); 
                return $instance;
            }catch(Exception $e) { 
                return back()->with(['error' => [$e->getMessage()]]);
            } 
        } 
        return redirect()->back()->with(['error' => __("Something went wrong")]);
    }
    public function escrowPaymentSuccess(Request $request, $gateway = null, $trx = null) { 
        try{
            $identifier = $trx ?? session()->get('identifier');
            $tempData   = TemporaryData::where("identifier",$identifier)->first();
            
            if(Escrow::where('callback_ref',$identifier)->first()!=null){
                return redirect()->route('user.my-escrow.index')->with(['success' => [__('Escrow created successfullyd')]]);
            }
            $this->createEscrow($tempData);
        }catch(Exception $e) {
            return back()->with(['error' => [$e->getMessage()]]);
        }
        return redirect()->route('user.my-escrow.index')->with(['success' => [__('Escrow created successfully')]]); 
    }
    public function cancel(Request $request, $gateway = null) {
        $token = session()->get('identifier');
        if($token){
            TemporaryData::where("identifier",$token)->delete();
        }

        return redirect()->route('user.my-escrow.index')->with(['error' => [__('You have canceled the payment')]]);
    }
    //stripe payment success 
    public function stripePaymentSuccess(Request $request, $gateway = null, $trx = null){
        try{
            $identifier = $trx ?? session()->get('identifier');
            $tempData   = TemporaryData::where("identifier",$identifier)->first();
            $this->createEscrow($tempData);
        }catch(Exception $e) {
            return back()->with(['error' => [$e->getMessage()]]);
        }
        return redirect()->route('user.my-escrow.index')->with(['success' => [__('Escrow created successfully')]]); 
    }
    //qrpay payment success 
    public function qrpayPaymentSuccess(Request $request, $gateway = null, $trx = null){
        try{
            $identifier = $trx ?? session()->get('identifier');
            $tempData   = TemporaryData::where("identifier",$identifier)->first();
            $this->createEscrow($tempData);
        }catch(Exception $e) {
            return back()->with(['error' => [$e->getMessage()]]);
        }
        return redirect()->route('user.my-escrow.index')->with(['success' => [__('Escrow created successfully')]]); 
    }
    public function qrpayCancel(Request $request, $trx = null) {
        $token = session()->get('identifier');
        if($token){
            TemporaryData::where("identifier",$token)->delete();
        }

        return redirect()->route('user.my-escrow.index')->with(['error' => [__('You have canceled the payment')]]);
    } 
    //qrpay payment success 
    public function coingateSuccess(Request $request){ 
        try{  
            $identifier = $request->get('trx');
            $escrowData = Escrow::where('callback_ref',$identifier)->first();
            if($escrowData == null) {
                $tempData   = TemporaryData::where("identifier",$identifier)->first();
                $this->createEscrow($tempData,null,EscrowConstants::PAYMENT_PENDING);
            } 
        }catch(Exception $e) {
            return back()->with(['error' => [$e->getMessage()]]);
        }
        return redirect()->route('user.my-escrow.index')->with(['success' => [__('Escrow created successfully')]]); 
    }
    public function coingateCallbackResponse(Request $request) { 
        $callback_data = $request->all(); 
        $callback_status = $callback_data['status'] ?? ""; 
        $tempData   = TemporaryData::where("identifier",$request->get('trx'))->first(); 
        $escrowData = Escrow::where('callback_ref',$request->get('trx'))->first();
        if($escrowData != null) { // if transaction already created & status is not success
            // Just update transaction status and update user wallet if needed
            if($callback_status == "paid") {  
                // update transaction status
                DB::beginTransaction(); 
                try{ 
                    DB::table('escrows')->where('id',$escrowData->id)->update([
                        'status'            => EscrowConstants::ONGOING, 
                        'callback_ref'      => $callback_data['trx'],
                    ]); 
                    DB::commit(); 
                }catch(Exception $e) {
                    DB::rollBack();
                    logger($e->getMessage());
                    throw new Exception($e);
                }
            }
        }else { // need to create transaction and update status if needed 
            $status = EscrowConstants::PAYMENT_PENDING; 
            if($callback_status == "paid") {
                $status = EscrowConstants::ONGOING;
            } 
            $this->createEscrow($tempData,null,$status);
        } 
        logger("Escrow Created Successfully ::" . $callback_data['status']);
    }
    public function coingateCancel(Request $request, $trx = null) {
        $token = session()->get('identifier');
        if($token){
            TemporaryData::where("identifier",$token)->delete();
        }

        return redirect()->route('user.my-escrow.index')->with(['error' => [__('You have canceled the payment')]]);
    } 
    //flutterwave payment success 
    public function flutterwaveCallback(Request $request, $gateway = null, $trx = null) { 
        $status = request()->status; 
        //if payment is successful
        if ($status ==  'successful' || $status ==  'completed') {  
            try{
                $identifier = $trx ?? session()->get('identifier');
                $tempData   = TemporaryData::where("identifier",$identifier)->first();
                $this->createEscrow($tempData); 
            }catch(Exception $e) {
                return back()->with(['error' => [$e->getMessage()]]);
            }
            return redirect()->route('user.my-escrow.index')->with(['success' => [__('Escrow created successfully')]]);
        }
        elseif ($status ==  'cancelled'){
            return redirect()->route('user.my-escrow.index','flutterWave')->with(['error' => [__('You have cancelled the payment')]]);
        }
        else{
            return redirect()->route('user.my-escrow.payment.success')->with(['error' => [__('Transaction failed')]]);
        }
    }
    public function razorCallback(){ 
        $request_data = request()->all(); 
        $identifier = $request_data['trx'] ?? session()->get('identifier');
        $tempData   = TemporaryData::where("identifier",$identifier)->first();
        $this->createEscrow($tempData);
        return redirect()->route('user.my-escrow.index')->with(['success' => [__('Escrow created successfully')]]); 
    }
    public function escrowPaymentSuccessperfectMoney(Request $request, $gateway = null, $trx = null) { 
        try{
            $identifier = $trx ?? session()->get('identifier');
            $tempData   = TemporaryData::where("identifier",$identifier)->first();
            $this->createEscrow($tempData);
        }catch(Exception $e) {
            return back()->with(['error' => [$e->getMessage()]]);
        }
        return redirect()->route('user.my-escrow.index')->with(['success' => [__('Escrow created successfully')]]); 
    }
    //========= escrow manual payment ===============
    public function manualPaymentPrivew(Request $request, $gateway = null, $trx = null) {
        $identifier = $trx ?? session()->get('identifier');
        $oldData   = TemporaryData::where("identifier",$identifier)->first();
        $gateway    = PaymentGateway::manual()->where('slug',PaymentGatewayConst::add_money_slug())->where('id',$oldData->data->gateway_currency->gateway->id)->first();
        $page_title = "Manual Payment".' ( '.$gateway->name.' )';
        if(!$oldData){
            return redirect()->route('user.my-escrow.index');
        }
        return view('user.my-escrow.manual-payment',compact("page_title","oldData",'gateway'));
    }
    public function manualPaymentConfirm(Request $request) { 
        $tempData       = Session::get('identifier');
        $oldData        = TemporaryData::where('identifier', $tempData)->first();
        $gateway        = PaymentGateway::manual()->where('slug',PaymentGatewayConst::add_money_slug())->where('id',$oldData->data->gateway_currency->gateway->id)->first();
        $payment_fields = $gateway->input_fields ?? [];
        $validation_rules       = $this->generateValidationRules($payment_fields);
        $payment_field_validate = Validator::make($request->all(),$validation_rules)->validate();
        $get_values             = $this->placeValueWithFields($payment_fields,$payment_field_validate);
        $this->successEscrow($get_values);
        return redirect()->route('user.my-escrow.index')->with(['success' => [__('Escrow created successfully')]]); 
    }
    //========= end escrow manual payment ===============
    //escrow wallet payment
    public function escrowWalletPayment($escrowTempData) {
        $sender_currency       = Currency::where('code', $escrowTempData->data->escrow->escrow_currency)->first();
        $user_wallet           = UserWallet::where(['user_id' => auth()->user()->id, 'currency_id' => $sender_currency->id])->first(); 
        $user_wallet->balance -= $escrowTempData->data->escrow->buyer_amount;
        $user_wallet->save();
    }
    //====================== end escrow payment ========================================================
    //======================= escrow data insertion after payment ==================================
    //escrow data insert
    public function successEscrow($additionalData = null) {
        $identifier = session()->get('identifier');
        $tempData   = TemporaryData::where("identifier",$identifier)->first();
        if(!$tempData) return redirect()->route('user.my-escrow.index')->with(['error' => [__('Transaction Failed. Record didn\'t saved properly. Please try again')]]);
        $this->createEscrow($tempData,$additionalData);
        
        return redirect()->route('user.my-escrow.index')->with(['success' => [__('Escrow created successfully')]]); 
    } 
    //escrow sslcommerz data insert
    public function successEscrowSslcommerz(Request $request) {  
        $tempData = TemporaryData::where("identifier",$request->tran_id)->first();
        if(!$tempData) return redirect()->route('user.my-escrow.index')->with(['error' => [__('Transaction Failed. Record didn\'t saved properly. Please try again')]]); 
        $creator_id    = $tempData->data->creator_id ?? null;
        $creator_guard = $tempData->data->creator_guard ?? null;
        $user          = Auth::guard($creator_guard)->loginUsingId($creator_id); 
        if( $request->status != "VALID"){
            return redirect()->route("user.my-escrow.index")->with(['error' => [__('Escrow Create Failed')]]);
        }
        $this->createEscrow($tempData); 
        return redirect()->route('user.my-escrow.index')->with(['success' => [__('Escrow created successfully')]]); 
    } 
    //insert escrow data
    public function createEscrow($tempData, $additionalData = null,$setStatus = null) {
        $escrowData = $tempData->data->escrow;
        if ($setStatus == null) {
            $status = 0;
            if ($escrowData->role == "seller") {
                $status = EscrowConstants::APPROVAL_PENDING;
            }else if($escrowData->role == "buyer" && $escrowData->payment_gateway_currency_id != null){ 
                if ($tempData->data->gateway_currency->gateway->type == PaymentGatewayConst::AUTOMATIC) {
                    $status = EscrowConstants::ONGOING;
                }else if($tempData->data->gateway_currency->gateway->type == PaymentGatewayConst::MANUAL){
                    $status         = EscrowConstants::PAYMENT_PENDING;
                    $additionalData = json_encode($additionalData);
                }
            }else if($escrowData->role == "buyer" && $escrowData->payment_type == EscrowConstants::MY_WALLET){
                $status = EscrowConstants::ONGOING;
            }
        }else{
            $status = $setStatus;
        }
     
        DB::beginTransaction();
        try{ 
           $escrowCreate = Escrow::create([
                'user_id'                     => $escrowData->user_id,
                'escrow_category_id'          => $escrowData->escrow_category_id,
                'payment_gateway_currency_id' => $escrowData->payment_gateway_currency_id ?? null,
                'escrow_id'                   => 'EC'.getTrxNum(),
                'payment_type'                => $escrowData->payment_type,
                'role'                        => $escrowData->role,
                'who_will_pay'                => $escrowData->charge_payer,
                'buyer_or_seller_id'          => $escrowData->buyer_or_seller_id,
                'amount'                      => $escrowData->amount,
                'escrow_currency'             => $escrowData->escrow_currency,
                'title'                       => $escrowData->title,
                'remark'                      => $escrowData->remarks,
                'file'                        => json_decode($tempData->data->attachment),
                'status'                      => $status,
                'details'                     => $additionalData,
                'created_at'                  => now(),
                'callback_ref'                  => $tempData->identifier,
            ]);
            EscrowDetails::create([
                'escrow_id'             => $escrowCreate->id ?? 0,
                'fee'                   => $escrowData->escrow_total_charge,
                'seller_get'            => $escrowData->seller_amount,
                'buyer_pay'             => $escrowData->buyer_amount,
                'gateway_exchange_rate' => $escrowData->gateway_exchange_rate,
                'created_at'            => now(),
            ]);
            DB::commit();
            //send user notification
            $byerOrSeller = User::findOrFail($escrowData->buyer_or_seller_id);
            $notification_content = [
                'title'   => "Escrow Request",
                'message' => "A user created an escrow with you",
                'time'    => Carbon::now()->diffForHumans(),
                'image'   => files_asset_path('profile-default'),
            ];
            UserNotification::create([
                'type'    => NotificationConst::ESCROW_CREATE,
                'user_id' => $escrowData->buyer_or_seller_id,
                'message' => $notification_content,
            ]);
            //Push Notifications
            $basic_setting = BasicSettings::first();
            try{ 
                $byerOrSeller->notify(new EscrowRequest($byerOrSeller,$escrowCreate));
                
                if($basic_setting->push_notification == true){
                    event(new UserNotificationEvent($notification_content,$byerOrSeller));
                    send_push_notification(["user-".$byerOrSeller->id],[
                        'title'     => $notification_content['title'],
                        'body'      => $notification_content['message'],
                        'icon'      => $notification_content['image'],
                    ]);
                }
            }catch(Exception $e) {

            }
          
            TemporaryData::where("identifier", $tempData->identifier)->delete();
        }catch(Exception $e) { 
            DB::rollBack();
            logger($e->getMessage());
            throw new Exception($e->getMessage());
        } 
    }
    //escrow sslcommerz fail
     public function escrowSllCommerzFails(Request $request){ 
        $tempData = TemporaryData::where("identifier",$request->tran_id)->first();
        if(!$tempData) return redirect()->route('user.my-escrow.index')->with(['error' => [__('Transaction Failed. Record didn\'t saved properly. Please try again')]]);
        $creator_id    = $tempData->data->creator_id ?? null;
        $creator_guard = $tempData->data->creator_guard ?? null;
        $user          = Auth::guard($creator_guard)->loginUsingId($creator_id);
        if($request->status == "FAILED"){
            TemporaryData::destroy($tempData->id);
            return redirect()->route("user.my-escrow.index")->with(['error' => [__('Escrow Create Failed')]]);
        }
    } 
    //escrow sslcommerz cancel
    public function escrowSllCommerzCancel(Request $request){ 
        $tempData = TemporaryData::where("identifier",$request->tran_id)->first();
        if(!$tempData) return redirect()->route('user.my-escrow.index')->with(['error' => [__('Transaction Failed. Record didn\'t saved properly. Please try again')]]);
        $creator_id    = $tempData->data->creator_id ?? null;
        $creator_guard = $tempData->data->creator_guard ?? null;
        $user          = Auth::guard($creator_guard)->loginUsingId($creator_id);
        if($request->status == "FAILED"){
            TemporaryData::destroy($tempData->id);
            return redirect()->route("user.my-escrow.index")->with(['error' => [__('Escrow Create Cancel')]]);
        }
    } 
    //======================= end escrow data insertion after payment ==================================
    //======================= additional actions ==============================================
    // ajax call for get user available balance by currency 
    public function availableBalanceByCurrency(Request $request) {
        $user_wallets = UserWallet::where(['user_id' => auth()->user()->id, 'currency_id' => $request->id])->first();
        $digitShow    = $user_wallets->currency->type == "CRYPTO" ? 6 : 2 ;
        return number_format($user_wallets->balance,$digitShow);
    } 
    public function userCheck(Request $request){ 
        $getUser = User::where('status', true)->where('username', $request->userCheck)->orWhere('email',$request->userCheck)->first();
        if($getUser != null){
            if($getUser->id == auth()->user()->id){
                return false;
            }
            return true;
        }
        return false;
    }

    public function cryptoPaymentAddress(Request $request, $escrow_id) {
        $page_title = "Crypto Payment Address";
        $escrowData = Escrow::where('escrow_id',$escrow_id)->first();
         
        return view('user.my-escrow.payment.crypto.address', compact( 
            'page_title',
            'escrowData',
        )); 
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

        if(!isset($validated['txn_hash'])) return back()->with(['error' => ['Transaction hash is required for verify']]);

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
       
        if(!$crypto_transaction) return back()->with(['error' => ['Transaction hash is not valid! Please input a valid hash']]);

        if($crypto_transaction->amount >= $escrowData->escrowDetails->buyer_pay == false) {
            if(!$crypto_transaction) return back()->with(['error' => ['Insufficient amount added. Please contact with system administrator']]);
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
            return back()->with(['error' => ['Something went wrong! Please try again']]);
        }

        return back()->with(['success' => ['Payment Confirmation Success!']]);
    }
    /**
     * Redirect Users for collecting payment via Button Pay (JS Checkout)
     */
    public function redirectBtnPay(Request $request, $gateway)
    {  
        try{ 
            return EscrowPaymentGateway::init([])->handleBtnPay($gateway, $request->all());
        }catch(Exception $e) {
            return redirect()->route('user.my-escrow.index')->with(['error' => [$e->getMessage()]]);
        }
    }
    public function escrowPaymentSuccessRazorpay(Request $request, $gateway) {
        try{
            $identifier = $request->token ;
            $tempData   = TemporaryData::where("identifier",$identifier)->first();
            $this->createEscrow($tempData);
        }catch(Exception $e) {
            return back()->with(['error' => [$e->getMessage()]]);
        }
        return redirect()->route('user.my-escrow.index')->with(['success' => [__('Escrow created successfully')]]); 
    }
    public function escrowPaymentSuccessRazorpayPost(Request $request, $gateway) {
        try{
            $identifier = $request->token ;
            $tempData   = TemporaryData::where("identifier",$identifier)->first();
            $this->createEscrow($tempData);
        }catch(Exception $e) {
            return back()->with(['error' => [$e->getMessage()]]);
        }
        return redirect()->route('user.my-escrow.index')->with(['success' => [__('Escrow created successfully')]]); 
    }
}
