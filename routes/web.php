<?php
use App\Http\Controllers\CometiController;

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PayPalController;

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

Route::view('register','welcome');
Route::get('confrim-email/{id}',[UserController::class,'confirmEmail'])->name('confrim.email');
Route::get('cometi-edit/{id}',[CometiController::class,'edit'])->name('cometi.edit');
Route::view('create','cometi.create');

Route::get('/payment/success', [PayPalController::class,'paymentSuccess'])->name('papal.success');
Route::get('/payment/cancel', [PayPalController::class,'paymentCancel'])->name('papal.cancel');
