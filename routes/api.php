<?php

use App\Http\Controllers\MpesaPaymentController;
use App\Http\Controllers\MUserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::get('/getusers', [MUserController::class ,'get_all_users']);
Route::get('/getuser/{id}', [MUserController::class ,'getuser']);
Route::post('/createusers', [MUserController::class ,'create_user']);
Route::delete('/deleteusers/{id}', [MUserController::class ,'delete_user']);
Route::put('/updateusers/{id}', [MUserController::class ,'update_user']);


// mpesa
Route::post('/token', [MpesaPaymentController::class, 'generateAccessToken']);
Route::post('/stk-push', [MpesaPaymentController::class, 'STKPush']);
Route::post('/callback', [MpesaPaymentController::class, 'mpesaConfirmation']);
Route::post('/validation', [MpesaPaymentController::class, 'mpesaValidation']);
Route::post('/confirmation', [MpesaPaymentController::class, 'mpesaConfirmation']);
Route::post('/register/url', [MpesaPaymentController::class, 'mpesaRegisterUrls']);



Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
