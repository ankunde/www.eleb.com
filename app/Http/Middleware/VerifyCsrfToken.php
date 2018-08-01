<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        //
        '/api/loginCheck',//登录
        '/api/register',//注册
        '/api/addAddress',//添加收货地址
        '/api/editAddress',//保存修改地址
        '/api/addCart',//保存购物车接口
        '/api/addorder',//添加订单接口
        '/api/changePassword',//修改密码接口
        '/api/forgetPassword'//重置密码
    ];
}
