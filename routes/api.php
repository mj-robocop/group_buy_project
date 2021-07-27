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

Route::apiResource('product', 'ProductController');
Route::apiResource('groupBuyProduct', 'GroupBuyProductController');

Route::group(['middleware' => 'auth'], function ($router) {
    Route::get('order', 'OrderController@index');
    Route::get('order/{id}', 'OrderController@show');
    Route::delete('order/{id}', 'OrderController@destroy');

    Route::get('basket', 'OrderController@getBasket');
    Route::post('basket/addToBasket', 'OrderController@addToBasket');
    Route::post('basket/editAddress', 'OrderController@editAddress');
});

Route::group(['middleware' => 'auth', 'prefix' => 'payment'], function ($router) {
    Route::get('do/{orderId}', 'PaymentController@doPayment');
    Route::any('done/{orderId}', 'PaymentController@donePayment')->name('donePayment');

    Route::post('setPayBack', 'PaymentController@setPayBack');
    Route::get('admin/getPayments', 'PaymentController@getPayments')->middleware('auth.admin');
});
