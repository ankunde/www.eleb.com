<?php

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

Route::get('/', function () {
    return view('welcome');
});
Route::prefix('api')->group(function () {
    Route::get('shops','ShopsController@index');//获取商家列表
    Route::get('shop', 'ShopsController@show');//获取指定商家信息
    Route::get('sms', 'MemberController@sms');//登录测试发送短信6
    Route::post('login','MemberController@store');//登录
    Route::post('register','MemberController@register');//注册
});