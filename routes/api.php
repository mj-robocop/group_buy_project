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

Route::group(['middleware' => 'auth', 'prefix' => 'order'], function ($router) {
    Route::get('/', 'OrderController@index');
    Route::get('order/{id}', 'OrderController@show');
    Route::delete('order/{id}', 'OrderController@destroy');

    Route::get('getBasket', 'OrderController@getBasket');
    Route::post('addToBasket', 'OrderController@addToBasket');
    Route::post('editAddress', 'OrderController@editAddress');
});
