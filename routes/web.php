<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\User\EscrowController;
use App\Http\Controllers\User\AddMoneyController;
use App\Http\Controllers\PagaditoCallbackController;
use App\Http\Controllers\User\EscrowActionsController;
use App\Http\Controllers\Api\V1\EscrowController as ApiEscrowController;
use App\Http\Controllers\Api\V1\EscrowActionController as ApiEscrowActionsController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/ 
Route::controller(PagaditoCallbackController::class)->prefix("payment")->group(function(){ 
    Route::get('pagadito/success','pagaditoSuccess')->name('success'); 
});
Route::controller(SiteController::class)->group(function(){
    Route::get('/','home')->name('index');
    Route::get('about-us','aboutUs')->name('aboutUs'); 
    Route::get('services','services')->name('services'); 
    Route::get('features','features')->name('features'); 
    Route::get('blogs','blog')->name('blog'); 
    Route::get('blog/details/{id}/{slug}','blogDetails')->name('blog.details');
    Route::get('blog/category/{id}/{slug}','blogByCategory')->name('blog.by.category');
    Route::get('contact-us','contactUs')->name('contactUs');
    Route::post('contact/store','contactStore')->name('contact.store');
    Route::get('page/{slug}','pageView')->name('page.view');
    Route::get('faq','faq')->name('faq');
    Route::post('languages/switch','languageSwitch')->name('languages.switch');
});
//add money sslcommerz callback urls(web)
Route::controller(AddMoneyController::class)->prefix("add-money")->name("add.money.")->group(function(){ 
    Route::post('sslcommerz/success','sllCommerzSuccess')->name('ssl.success');
    Route::post('sslcommerz/fail','sllCommerzFails')->name('ssl.fail');
    Route::post('sslcommerz/cancel','sllCommerzCancel')->name('ssl.cancel');
    Route::post("/callback/response/{gateway}",'callback')->name('payment.callback')->withoutMiddleware('web');
    
});
//my escrow sslcommerz callback urls(web)
Route::controller(EscrowController::class)->prefix("my-escrow")->name("my-escrow.")->group(function(){ 
    Route::post('sslcommerz/success','successEscrowSslcommerz')->name('ssl.success');
    Route::post('sslcommerz/fail','escrowSllCommerzFails')->name('ssl.fail');
    Route::post('sslcommerz/cancel','escrowSllCommerzCancel')->name('ssl.cancel'); 
});
//my escrow sslcommerz callback urls(api)
Route::controller(ApiEscrowController::class)->prefix("api-my-escrow")->name("api.my-escrow.")->group(function(){ 
    Route::post('sslcommerz/success','successEscrowSslcommerz')->name('ssl.success');
    Route::post('sslcommerz/fail','escrowSllCommerzFails')->name('ssl.fail');
    Route::post('sslcommerz/cancel','escrowSllCommerzCancel')->name('ssl.cancel');
});
//escrow approvel pending sslcommerz callback urls(web)
Route::controller(EscrowActionsController::class)->prefix("escrow-action")->name("escrow-action.")->group(function(){ 
    Route::post('sslcommerz/success','escrowPaymentApprovalSuccessSslcommerz')->name('ssl.success');
    Route::post('sslcommerz/fail','escrowSllCommerzFails')->name('ssl.fail');
    Route::post('sslcommerz/cancel','escrowSllCommerzCancel')->name('ssl.cancel');
});
//escrow approvel pending sslcommerz callback urls(web)
Route::controller(ApiEscrowActionsController::class)->prefix("api-escrow-action")->name("api-escrow-action.")->group(function(){ 
    Route::post('sslcommerz/success','escrowPaymentApprovalSuccessSslcommerz')->name('ssl.success');
    Route::post('sslcommerz/fail','escrowSllCommerzFails')->name('ssl.fail');
    Route::post('sslcommerz/cancel','escrowSllCommerzCancel')->name('ssl.cancel');
}); 