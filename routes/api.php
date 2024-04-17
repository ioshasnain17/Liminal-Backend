<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\PayPalController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/
Route::group(['prefix' => 'auth'], function () {
    Route::post('login', [AuthController::class,'login'])->name("login");
    Route::post('logout', [AuthController::class,'logout']);

    Route::post('forgot-password', [UserController::class,'sendResetCode']);
    Route::post('reset-password', [UserController::class,'updatePassword'])->name('password.reset');
 });

/////////////User Data /////////////////////////////
Route::middleware(['auth:api'])->group(function () {
    Route::post('change-password', 'Api\AuthController@change_password');


    Route::post('verify-email',[UserController::class,'verifyCode']);


    Route::get('getUserByID/{id}',[UserController::class,'getLoginUserDataByID'])->name('getUserById')->middleware(['auth:api']);
    Route::get('login-user', [UserController::class,'userProfile'])->name('get.user');
    Route::get('mail', [UserController::class,'sendWelcomeEmail']);

////////////////////////Search route start ///////////////////

    Route::post('history/save',[SearchController::class,'saveSearch']);
    Route::get('get-history/{days}',[SearchController::class,'getHistory']);
    Route::get('delete-history/{id}',[SearchController::class,'delete']);
    Route::post('search',[SearchController::class,'search']);
    Route::post('test',[SearchController::class,'makeApiPostRequest']);
////////////////////////////End Search Route
///


Route::post('/paypal/payment', [PayPalController::class, 'createPayment']);
Route::post('/paypal/execute', [PayPalController::class, 'executePayment']);


    Route::post('paypal',[PayPalController::class,'createPayment']);
    Route::get('execute',[PayPalController::class,'executePayment'])->name("paypal.execute");
    Route::post('subscription/save',[SubscriptionController::class,'paySubscription']);
    Route::get('/paypal/cancel', [PayPalController::class,'cancelPayment'])->name('paypal.cancel');

});


/////////////register route///////////

Route::post('register',[UserController::class,'register'])->name('register');
////////////////////////////

