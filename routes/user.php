<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\DashboardController;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\User\EscrowController;
use App\Http\Controllers\User\EscrowActionsController;
use App\Http\Controllers\User\TransactionController;
use App\Http\Controllers\User\AddMoneyController;
use App\Http\Controllers\User\MoneyOutController;
use App\Http\Controllers\User\SupportTicketController;
use App\Http\Controllers\User\AuthorizationController;
use App\Http\Controllers\User\MoneyExchangeController;
use App\Http\Controllers\User\SecurityController;
use App\Providers\Admin\BasicSettingsProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Pusher\PushNotifications\PushNotifications;

Route::prefix("user")->name("user.")->group(function(){
    Route::controller(DashboardController::class)->group(function(){
        Route::get('dashboard','index')->name('dashboard');
        Route::post('logout','logout')->name('logout');
        Route::post('delete/account','deleteAccount')->name('delete.account')->middleware('app.mode');
        Route::post('notification/seen/update','userNotificationUpdate')->name('notifications.seen.update');
    });
    //user profile routes
    Route::controller(ProfileController::class)->prefix("profile")->middleware('app.mode')->name("profile.")->group(function(){
        Route::get('/','index')->name('index');
        Route::put('password/update','passwordUpdate')->name('password.update');
        Route::put('update','update')->name('update');
        Route::get('/user-type/update','profileTypeUpdate')->name('type.update');
    });
    // My Escrow routes
    Route::controller(EscrowController::class)->name('my-escrow.')->group(function(){
        Route::get('/my-escrow','index')->name('index'); 
        Route::get('/create-escrow','create')->name('add'); 
        Route::post('/submit-escrow','submit')->name('submit'); 
        Route::post('/confirm-escrow','successConfirm')->name('confirm'); 
        Route::get('/success-escrow','successEscrow')->name('success'); 
        Route::get('/escrow-payment/{gateway}/{trx}','escrowPaymentSuccess')->name('payment.success'); 
        Route::get("cancel/response/{gateway}",'cancel')->name('payment.cancel');

        Route::get('perfect-money/escrow-payment/{gateway}/{trx}','escrowPaymentSuccessperfectMoney')->name('perfect.money.payment.success'); 

        // Route::get('/payment-escrow/{gateway}','escrowPaymentSuccessRazorpay')->name('payment.success.razorpay');
        Route::post('/payment-escrow/{gateway}','escrowPaymentSuccessRazorpayPost')->name('payment.success.razorpay'); 
        //redirect with Btn Pay
        Route::get('redirect/btn/checkout/{gateway}', 'redirectBtnPay')->name('payment.btn.pay')->withoutMiddleware(['auth','verification.guard','user.google.two.factor']);
        //stripe payment success
        Route::get('/payment/success/{gateway}/{trx}', 'stripePaymentSuccess')->name('stripe.success');
        //qrpay payment success
        Route::get('/qrpay/success/{gateway}/{trx}', 'qrpayPaymentSuccess')->name('qrpay.success');
        Route::get("qrpay/cancel/{trx}",'qrpayCancel')->name('qrpay.cancel');
        // Qrpay gateway
        Route::match(['get','post'],'escrow-create/coingate/success/{gateway}', 'coingateSuccess')->name('coingate.success');
        Route::post('escrow-create/coingate/callback/{gateway}', 'coingateCallbackResponse')->name('coingate.callback')->withoutMiddleware(['web','auth','verification.guard','user.google.two.factor']);
        Route::match(['get','post'],'coingate/cancel/{trx_id}', 'coingateCancel')->name('coingate.cancel');
        //flutterwave callback
        Route::get('/payment/callback/{gateway}/{trx}', 'flutterwaveCallback')->name('flutterwave.callback');
        //manual payment 
        Route::get('/escrow/manual/payment', 'manualPaymentPrivew')->name('manual.payment');
        Route::post('/escrow/manual/confirm', 'manualPaymentConfirm')->name('manual.confirm');
        //razor callback
        Route::get('razor/callback', 'razorCallback')->name('razor.callback');
        // Tatum Payment Gateway
        Route::prefix('escrow/payment')->name('payment.')->group(function() {
            Route::get('crypto/address/{trx}','cryptoPaymentAddress')->name('crypto.address');
            Route::post('crypto/confirm/{trx}','cryptoPaymentConfirm')->name('crypto.confirm');
        });
        //available balance 
        Route::get('/available-balance-by-currency','availableBalanceByCurrency')->name('available.balance.byCurrency'); 
        //check user is avaibleable
        Route::get('user-check','userCheck')->name('userCheck');
    });
    // Escrow action routes
    Route::controller(EscrowActionsController::class)->name('escrow-action.')->group(function(){
        Route::get('escrow/payment/approval-pending/{id}','paymentApprovalPending')->name('paymentApprovalPending');  
        Route::post('escrow/payment/approval-submit/{id}','paymentApprovalSubmit')->name('paymentApprovalSubmit');  
        Route::get('/escrow-payment-approval/{gateway}','escrowPaymentApprovalSuccess')->name('payment.approval.success'); 
        Route::get("cancel/response",'cancel')->name('payment.cancel');
        Route::get('/flutterWave/escrow-payment-approval/{gateway}','escrowPaymentApprovalSuccessflutterWave')->name('payment.approval.success.flutterWave');
        //coingate payment success 
        Route::get('coingate/escrow-payment-approval/{gateway}','escrowPaymentApprovalSuccessCoingate')->name('coingate.payment.approval.success');  
        Route::post('coingate/callback/escrow-payment-approval/{gateway}','escrowPaymentApprovalCallbackCoingate')->name('coingate.payment.approval.callback')->withoutMiddleware(['web','auth','verification.guard','user.google.two.factor']);  
        //manual payment 
        Route::get('/approval-pending/manual/payment', 'manualPaymentPrivew')->name('manual.payment');
        Route::post('/approval-pending/manual/confirm', 'manualPaymentConfirm')->name('manual.confirm');

        // Route::get('/payment-escrow-approval/{gateway}','escrowPaymentSuccessRazorpay')->name('payment.success.razorpay');  
        Route::post('/payment-escrow-approval/{gateway}','escrowPaymentSuccessRazorpayPost')->name('payment.success.razorpay'); 
        //redirect with Btn Pay
        Route::get('redirect/btn/checkout/approval-pending/{gateway}', 'redirectBtnPay')->name('payment.btn.pay')->withoutMiddleware(['auth','verification.guard','user.google.two.factor']);

        //razor callback
        Route::get('/approval-pending/razor/callback', 'razorCallback')->name('razor.callback');
        //escrow conversations
        Route::get('escrow/conversation/{id}','escrowConversation')->name('escrowConversation');  
        Route::post('escrow/message/send','messageSend')->name('message.send');
        //escrow conversation file upload
        Route::post('escrow/conversation/file-upload','chatFileUpload')->name('conversation.file.upload');
        //dispute payment request
        Route::post('escrow/dispute-payment','disputePayment')->name('dispute.payment');
        //release payment request
        Route::post('escrow/release-payment','releasePayment')->name('release.payment');
        Route::post('escrow/release-request','releaseRequest')->name('release.request');

        Route::prefix('approval-pending/payment')->name('payment.')->group(function() {
            Route::get('crypto/address/{trx}','cryptoPaymentAddress')->name('crypto.address');
            Route::post('crypto/confirm/{trx}','cryptoPaymentConfirm')->name('crypto.confirm');
        });

    });
     // Transaction routes
     Route::controller(TransactionController::class)->prefix('transactions')->name('transactions.')->group(function(){
        Route::get('/{slug?}', 'index')->name('index');
    });
    //add money routes
    Route::controller(AddMoneyController::class)->prefix("add-money")->name("add.money.")->group(function(){
        Route::get('/','index')->name("index"); 
        Route::post('submit','submit')->name('submit'); 
        //paypay
        Route::get('success/response/{gateway}','success')->name('payment.success');
        Route::get("cancel/response/{gateway}",'cancel')->name('payment.cancel'); 
        Route::post("callback/response/{gateway}",'callback')->name('payment.callback')->withoutMiddleware(['web','auth','verification.guard','user.google.two.factor']);
        // POST Route For Unauthenticated Request
        Route::post('success/response/{gateway}', 'postSuccess')->name('payment.success')->withoutMiddleware(['auth:api']);
        Route::post('cancel/response/{gateway}', 'postCancel')->name('payment.cancel')->withoutMiddleware(['auth:api']);
        
        Route::post("perfect-money/callback/response/{gateway}",'perfectMoneyCallback')->name('perfect-money.payment.callback')->withoutMiddleware(['web','auth','verification.guard','user.google.two.factor']);
        // redirect with HTML form route
        Route::get('redirect/form/{gateway}', 'redirectUsingHTMLForm')->name('payment.redirect.form')->withoutMiddleware(['auth','verification.guard','user.google.two.factor']);

        Route::get('perfect-money/success/response/{gateway}','perfectMoneySuccess')->name('perfect-money.payment.success');
        //redirect with Btn Pay
        Route::get('redirect/btn/checkout/{gateway}', 'redirectBtnPay')->name('payment.btn.pay')->withoutMiddleware(['auth','verification.guard','user.google.two.factor']);

        Route::get('user-available-balance-by-currency','availableBalanceByCurrency')->name('available.balance.byCurrency');  
        Route::get('stripe/payment/success/{trx}','stripePaymentSuccess')->name('stripe.payment.succePss'); 
        //add money manual payment gateway routes
        Route::get('manual/payment','manualPayment')->name('manual.payment');
        Route::post('manual/payment/confirmed','manualPaymentConfirmed')->name('manual.payment.confirmed');
        Route::get('/flutterwave/callback', 'flutterwaveCallback')->name('flutterwave.callback');
        //razor callback  
        Route::get('success/response/global/{gateway}','successGlobal')->name('payment.global.success');
        Route::post('success/response/global/{gateway}', 'postSuccess')->name('payment.global.success')->withoutMiddleware(['auth','verification.guard','user.google.two.factor']);
        Route::get('razor-pay/cancel', 'razorCancel')->name('razor.cancel');
        // Qrpay gateway
        Route::get('qrpay/callback', 'qrpayCallback')->name('qrpay.callback');
        Route::get('qrpay/cancel/{trx_id}', 'qrpayCancel')->name('qrpay.cancel');
        //coingate
        Route::match(['get','post'],'coingate/success/response/{gateway}','coinGateSuccess')->name('coingate.payment.success');
        Route::match(['get','post'],"coingate/cancel/response/{gateway}",'coinGateCancel')->name('coingate.payment.cancel'); 
        //paystack
        Route::match(['get','post'],'paystack/success/response/{gateway}','paystackSuccess')->name('paystack.payment.success');
          // Tatum Payment Gateway
        Route::prefix('payment')->name('payment.')->group(function() {
            Route::get('crypto/address/{trx_id}','cryptoPaymentAddress')->name('crypto.address');
            Route::post('crypto/confirm/{trx_id}','cryptoPaymentConfirm')->name('crypto.confirm');
        });
    });
    //money out routes
    Route::controller(MoneyOutController::class)->prefix("money-out")->name("money.out.")->group(function(){
        Route::get('/','index')->name("index"); 
        Route::post('insert','paymentInsert')->name('insert');
        Route::get('preview','preview')->name('preview');
        Route::post('confirm','confirmMoneyOut')->name('confirm');

        
        //check bank validation
        Route::post('check/flutterwave/bank','checkBanks')->name('check.flutterwave.bank');
        //automatic withdraw confirmed
        Route::post('automatic/confirmed','confirmMoneyOutAutomatic')->name('confirm.automatic');
    });
     //money exchange
     Route::controller(MoneyExchangeController::class)->prefix("money-exchange")->name("money.exchange.")->group(function(){
        Route::get('/','index')->name("index"); 
        Route::post('submit','moneyExchangeSubmit')->name('submit'); 
    });
    //support tickets routes
    Route::controller(SupportTicketController::class)->prefix("support")->name("support.ticket.")->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('create', 'create')->name('create');
        Route::post('store', 'store')->name('store');
        Route::get('conversation/{encrypt_id}','conversation')->name('conversation');
        Route::post('message/send','messageSend')->name('message.send'); 
    });
    //kyc verification 
    Route::controller(AuthorizationController::class)->prefix("authorize")->name('authorize.')->group(function(){
        Route::get('kyc','showKycFrom')->name('kyc');
        Route::post('kyc/submit','kycSubmit')->name('kyc.submit');
    });
    
    //google-2fa
    Route::controller(SecurityController::class)->prefix("security")->name('security.')->group(function(){
        Route::get('google/2fa','google2FA')->name('google.2fa');
        Route::post('google/2fa/status/update','google2FAStatusUpdate')->name('google.2fa.status.update')->middleware('app.mode');
    });

});

Route::get('user/pusher/beams-auth', function (Request $request) {
    if(Auth::check() == false) {
        return response(['Inconsistent request'], 401);
    }
    $userID = Auth::user()->id;

    $basic_settings = BasicSettingsProvider::get();
    if(!$basic_settings) {
        return response('Basic setting not found!', 404);
    }

    $notification_config = $basic_settings->push_notification_config;

    if(!$notification_config) {
        return response('Notification configuration not found!', 404);
    }

    $instance_id    = $notification_config->instance_id ?? null;
    $primary_key    = $notification_config->primary_key ?? null;
    if($instance_id == null || $primary_key == null) {
        return response('Sorry! You have to configure first to send push notification.', 404);
    }
    $beamsClient = new PushNotifications(
        array(
            "instanceId" => $notification_config->instance_id,
            "secretKey" => $notification_config->primary_key,
        )
    );
    $publisherUserId = "user-".$userID;
    try{
        $beamsToken = $beamsClient->generateToken($publisherUserId);
    }catch(Exception $e) {
        return response(['Server Error. Faild to generate beams token.'], 500);
    }

    return response()->json($beamsToken);
})->name('user.pusher.beams.auth');