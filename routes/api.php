<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\EscrowController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\AddMoneyController;
use App\Http\Controllers\Api\V1\MoneyOutController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\AppSettingsController;
use App\Http\Controllers\Api\V1\EscrowActionController;
use App\Http\Controllers\Api\V1\MoneyExchangeController;
use App\Http\Controllers\Api\V1\Auth\AuthorizationController;
use App\Http\Controllers\Api\V1\Auth\ForgotPasswordController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix("v1")->name('api.v1.')->group(function(){ 
    Route::get('basic/settings', [AppSettingsController::class, "basicSettings"]);
    Route::controller(AppSettingsController::class)->prefix("app-settings")->group(function(){
        Route::get('/','appSettings');
        Route::get('languages','languages');
    });
    Route::controller(AuthController::class)->prefix("user")->name("user.")->group(function(){
        Route::post('/login', 'login')->name('login');
        Route::post('/register', 'register')->name('register');
    }); 
    Route::group(['prefix' => 'user/forgot/password'], function () {
        Route::post('send/otp', [ForgotPasswordController::class, 'sendCode']);
        Route::post('verify',  [ForgotPasswordController::class, 'verifyCode']);
        Route::post('reset', [ForgotPasswordController::class, 'resetPassword']);
    });
    // add money routes
    Route::controller(AddMoneyController::class)->prefix('add-money')->name('add-money.')->group(function(){
        Route::get('success/response/{gateway}','apiPaymentSuccess')->name('payment.success');
        Route::get("cancel/response/{gateway}",'cancel')->name('payment.cancel');

         // POST Route For Unauthenticated Request
         Route::post('success/response/{gateway}', 'postSuccess')->name('payment.success')->withoutMiddleware(['auth:api']);
         Route::post('cancel/response/{gateway}', 'postCancel')->name('payment.cancel')->withoutMiddleware(['auth:api']);
         
        // stripe payment confirm
        Route::get('stripe/payment/confirmed/{trx}','stripePaymentSuccess')->name('paymentSuccessStripeAutomatic');
        //razor pay callback
        Route::get('razor-pay/callback', 'razorCallback')->name('razor.callback');
        // flutter wave payment confirm
        Route::get('flutter-wave/payment/confirmed','flutterWavePaymentSuccess')->name('paymentSuccessFlutterWaveAutomatic');
         //sslcommerz
        Route::post('sslcommerz/success','sllCommerzSuccess')->name('ssl.success');
        Route::post('sslcommerz/fail','sllCommerzFails')->name('ssl.fail');
        Route::post('sslcommerz/cancel','sllCommerzCancel')->name('ssl.cancel');
        //razor api create payment link 
        Route::get('razor-payment/api-link/{order_id}','razorPaymentLink')->name('razorPaymentLink');
        //razor callback 
        Route::get('api-razor/callback', 'razorCallback')->name('razor.callback');
        //qr pay callback 
        Route::get('qrpay/success/response/{gateway}','qrpayCallback')->name('qrpay.callback');
        //redirect with Btn Pay
        Route::get('redirect/btn/checkout/{gateway}', 'redirectBtnPay')->name('payment.btn.pay')->withoutMiddleware(['auth:api','auth.api','CheckStatusApiUser']);

        //coingate
        Route::match(['get','post'],'coingate/success/response/{gateway}','coinGateSuccess')->name('coingate.payment.success');
        Route::match(['get','post'],"coingate/cancel/response/{gateway}",'cancel')->name('coingate.payment.cancel');
        //paystact
        Route::match(['get','post'],'paystack/success/response/{gateway}','paystactSuccess')->name('paystack.payment.success');
        
        Route::prefix('payment')->name('payment.')->group(function() {
            Route::get('crypto/address/{trx_id}','cryptoPaymentAddress')->name('crypto.address');
            Route::post('crypto/confirm/{trx_id}','cryptoPaymentConfirm')->name('crypto.confirm');
        });
    });
    //escrow toutes
    Route::controller(EscrowController::class)->prefix('my-escrow')->name('my-escrow.')->group(function(){ 
        Route::get('success/response/{gateway}/{trx}','apiPaymentSuccess')->name('payment.success');
        //qrpay payment success
        Route::get('qrpay/success/response/{gateway}/{trx}','qrpayPaymentSuccess')->name('qrpay.payment.success');
        //coingate payment success
        Route::get('coingate/success/response/{gateway}','coingatePaymentSuccess')->name('coingate.payment.success');
        //stripe payment success
        Route::get('/payment/success/{gateway}/{trx}', 'stripePaymentSuccess')->name('stripe.payment.success');
        //flutterwave callback
        Route::get('/payment/callback/{gateway}/{trx}', 'flutterwaveCallback')->name('flutterwave.callback');

        Route::get('redirect/btn/checkout/{gateway}', 'redirectBtnPay')->name('payment.btn.pay')->withoutMiddleware(['auth:api','auth.api','CheckStatusApiUser']);
        Route::post('/payment-escrow/{gateway}','escrowPaymentSuccessRazorpayPost')->name('payment.success.razorpay'); 
        //razor pay callback
        Route::get('razor-pay/payment-link', 'razorPayLinkCreate')->name('razor-pay.linkCreate');
        Route::get('razor-pay-api/callback', 'razorCallback')->name('razor.callbackapi');

        Route::get('payment-cancel', 'paymentCancel')->name('payment.cancel');

        Route::prefix('payment')->name('payment.')->group(function() {
            Route::get('crypto/address/{trx_id}','cryptoPaymentAddress')->name('crypto.address');
            Route::post('crypto/confirm/{escrow_id}','cryptoPaymentConfirm')->name('crypto.confirm');
        });
    });
    //escrow action routes
    Route::controller(EscrowActionController::class)->prefix('api-escrow-action')->name('api-escrow-action.')->group(function(){ 
        Route::get('/escrow-payment-link','razorPayLinkCreate')->name('payment.approval.razorPayLinkCreate');  
        Route::get('/escrow-payment-api/callback','razorCallback')->name('payment.approval.razorCallback');  
        Route::get('/escrow-payment-approval','escrowPaymentApprovalSuccess')->name('payment.approval.success');  
        Route::get('/flutterWave/escrow-payment-approval','escrowPaymentApprovalSuccessflutterWave')->name('payment.approval.success.flutterWave');  
        Route::get('/paystack/escrow-payment-approval','escrowPaymentApprovalSuccessPaystack')->name('payment.approval.success.paystack');  
        Route::get('qrpay/escrow-payment-approval/{gateway}/{trx}','escrowPaymentApprovalSuccessQrpay')->name('payment.approval.success.qrpay'); 

        Route::post('/payment-escrow-approval/{gateway}','escrowPaymentSuccessRazorpayPost')->name('payment.success.razorpay'); 
        //redirect with Btn Pay
        Route::get('redirect/btn/checkout/approval-pending/{gateway}', 'redirectBtnPay')->name('payment.btn.pay')->withoutMiddleware(['auth:api','auth.api','CheckStatusApiUser']);

        Route::get('coingate/escrow-payment-approval/{gateway}','escrowPaymentApprovalSuccessCoingate')->name('payment.approval.success.coingate'); 
        Route::post('coingate/callback/escrow-payment-approval/{gateway}','escrowPaymentApprovalCallbackCoingate')->name('coingate.payment.approval.callback')->withoutMiddleware(['web','auth','verification.guard','user.google.two.factor']); 

        Route::prefix('payment')->name('payment.')->group(function() {
            Route::get('crypto/address/{trx_id}','cryptoPaymentAddress')->name('crypto.address');
            Route::post('crypto/confirm/{escrow_id}','cryptoPaymentConfirm')->name('crypto.confirm');
        });
    });
    //al user authenticate route
    Route::middleware('auth:api')->prefix("user")->name("user.")->group(function (){
        Route::get('logout', [AuthorizationController::class, 'logout']);
        Route::post('email/otp/verify', [AuthorizationController::class,'verifyEmailCode']);
        Route::post('email/resend/code', [AuthorizationController::class,'emailResendCode']);
        Route::post('verify/google-2fa', [AuthorizationController::class,'verify2FACode']);
        Route::get('dashboard', [DashboardController::class, 'dashboard']);
        Route::get('user-notification', [DashboardController::class, 'userNotification']);
        Route::get('all-transactions', [DashboardController::class, 'allTransactions']);
        // User Profile
        Route::controller(ProfileController::class)->prefix('profile')->group(function(){
            Route::get('/', 'profile');
            Route::post('update', 'profileUpdate')->middleware('app.mode.api');
            Route::post('password/update', 'passwordUpdate')->middleware('app.mode.api');
            Route::post('delete/account', 'deleteAccount')->middleware('app.mode.api');
            Route::get('/google-2fa', 'google2FA');
            Route::post('/google-2fa/status/update', 'google2FAStatusUpdate')->middleware('app.mode.api'); 
            Route::get('type/update','profileTypeUpdate')->name('type.update');
        });
        Route::controller(AuthorizationController::class)->prefix('kyc')->group(function(){
            Route::get('input-fields','getKycInputFields');
            Route::post('submit','KycSubmit');
        });
        // add money routes
        Route::controller(AddMoneyController::class)->prefix('add-money')->name('add-money.')->group(function(){
            Route::get('index','index')->name('index'); 
            Route::post('submit','submit')->name('submit');
            // Manual payment confirmed
            Route::post('manual/payment/confirmed','manualPaymentConfirmedApi')->name('manual.payment.confirmed');
        });
        //Money out routes
        Route::controller(MoneyOutController::class)->prefix('money-out')->name('money-out.')->group(function(){
            Route::get('index','index');
            Route::post('submit','submit');
            Route::post('manual/confirmed','moneyOutManualConfirmed')->name('manual.confirmed');
        });
        //escrow create routes
        Route::controller(EscrowController::class)->prefix('my-escrow')->name('my-escrow.')->group(function(){
            Route::get('index','index')->name('index'); 
            Route::get('create','create'); 
            Route::post('submit','submit'); 
            Route::post('/confirm-escrow','successConfirm')->name('confirm'); 
            // Manual payment confirmed
            Route::post('manual/payment/confirmed','manualPaymentConfirmedApi')->name('manual.payment.confirmed');
            //check user is avaibleable
            Route::get('user-check','userCheck')->name('userCheck');
        });
        //escrow action routes
        Route::controller(EscrowActionController::class)->prefix('api-escrow-action')->name('api-escrow-action.')->group(function(){
            Route::get('payment/approval-pending/{id}','paymentApprovalPending')->name('paymentApprovalPending'); 
            Route::post('escrow/payment/approval-submit/{id}','paymentApprovalSubmit')->name('paymentApprovalSubmit');  
            Route::post('/approval-pending/manual/confirm', 'manualPaymentConfirm')->name('manual.confirm');
            //escrow conversations
            Route::get('conversation/{id}','escrowConversation')->name('escrowConversation');  
            Route::post('message/send','messageSend')->name('message.send');
            //dispute payment request
            Route::post('dispute-payment','disputePayment')->name('dispute.payment');
            //release payment request
            Route::post('release-payment','releasePayment')->name('release.payment');
            Route::post('release-request','releaseRequest')->name('release.request');
        });
        //money exchange
        Route::controller(MoneyExchangeController::class)->prefix("money-exchange")->name("money.exchange.")->group(function(){
            Route::get('/','index')->name("index"); 
            Route::post('submit','moneyExchangeSubmit')->name('submit');
            Route::get('preview','preview')->name('preview');
            Route::post('confirm','confirmMoneyOut')->name('confirm');
        });
    });  
});