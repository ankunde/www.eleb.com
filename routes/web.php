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
    Route::post('loginCheck','MemberController@store');//登录
    Route::post('register','MemberController@register');//注册
    Route::get('addressList','MemberController@addressList');//地址列表接口
    Route::post('addAddress','MemberController@addAddress');//保存新增地址接口
    Route::get('address','MemberController@address');//指定地址接口
    Route::post('editAddress','MemberController@editAddress');//保存修改地址接口
    Route::post('addCart','MemberController@addCart');//保存购物车接口
    Route::get('cart','MemberController@cart');//获取购物车数据接口
    Route::post('addorder','MemberController@addorder');// 添加订单接口
    Route::get('orderList','MemberController@orderList');// 获得订单列表接口
    Route::get('order','MemberController@order');// 获得指定订单接口
    Route::post('changePassword','MemberController@changePassword');// 修改密码接口
    Route::post('forgetPassword','MemberController@forgetPassword');// 忘记密码接口
});