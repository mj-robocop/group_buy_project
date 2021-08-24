<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['middleware' => 'api', 'prefix' => 'auth'], function ($router) {
    Route::post('login', 'AuthController@login');
    Route::post('logout', 'AuthController@logout');
    Route::post('refresh', 'AuthController@refresh');
    Route::post('register', 'AuthController@register');
    Route::post('currentUser', 'AuthController@currentUser');
});

Route::apiResource('user', 'UserController');
Route::apiResource('product', 'ProductController');
Route::apiResource('groupBuyProduct', 'GroupBuyProductController');

Route::post('user/name', 'UserController@updateUserName');

Route::group(['middleware' => 'auth'], function ($router) {
    Route::get('order', 'OrderController@index');
    Route::get('order/{id}', 'OrderController@show');
    Route::delete('order/item/{id}', 'OrderController@cancelOrderItem');

    Route::post('order/item/review/{id}', 'OrderController@createReview');

    Route::get('basket', 'OrderController@getBasket');
    Route::post('basket/addToBasket', 'OrderController@addToBasket');
    Route::post('basket/editAddress', 'OrderController@editAddress');
});

Route::post('admin/order/item/status/{ids}', 'OrderController@changeOrderItemsStatus')
    ->middleware('auth.admin');

Route::group(['middleware' => 'auth', 'prefix' => 'payment'], function ($router) {
    Route::get('do/{orderId}', 'PaymentController@doPayment');
    Route::any('done/{orderId}/{payId}', 'PaymentController@donePayment')->name('donePayment');
});

Route::group(['middleware' => 'auth.admin', 'prefix' => 'admin/payment'], function ($router) {
    Route::get('getPayments', 'PaymentController@getPayments');

    Route::get('getPayBacks', 'PaymentController@getPayBacks');
    Route::post('payBack/set', 'PaymentController@setPayBack');
    Route::post('payBack/status/{ids}', 'PaymentController@changePayBackStatus');
});
