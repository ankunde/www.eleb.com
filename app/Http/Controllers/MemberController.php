<?php

namespace App\Http\Controllers;

use App\Model\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class MemberController extends Controller
{
    //发送短信验证码
    public function sms()
    {
        $tel = request()->tel;
        $params = [];

        // *** 需用户填写部分 ***

        // fixme 必填: 请参阅 https://ak-console.aliyun.com/ 取得您的AK信息
        $accessKeyId = "LTAImjBFWmSegGAH";
        $accessKeySecret = "cXNj0wekciFyEfUPM5u84zsciOJonE";

        // fixme 必填: 短信接收号码
        $params["PhoneNumbers"] = $tel;//request()->input('tel', 17778372324);

        // fixme 必填: 短信签名，应严格按"签名名称"填写，请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/sign
        $params["SignName"] = "安坤";

        // fixme 必填: 短信模板Code，应严格按"模板CODE"填写, 请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/template
        $params["TemplateCode"] = "SMS_140530021";

        // fixme 可选: 设置模板参数, 假如模板中存在变量需要替换则为必填项
        $params['TemplateParam'] = Array(
            "code" => random_int(000000, 999999),
//            "product" => "阿里通信"
        );



        // *** 需用户填写部分结束, 以下代码若无必要无需更改 ***
        if (!empty($params["TemplateParam"]) && is_array($params["TemplateParam"])) {
            $params["TemplateParam"] = json_encode($params["TemplateParam"], JSON_UNESCAPED_UNICODE);
        }
// 初始化SignatureHelper实例用于设置参数，签名以及发送请求
        $helper = new \App\SignatureHelper();

        // 此处可能会抛出异常，注意catch
        $content = $helper->request(
            $accessKeyId,
            $accessKeySecret,
            "dysmsapi.aliyuncs.com",
            array_merge($params, array(
                "RegionId" => "cn-hangzhou",
                "Action" => "SendSms",
                "Version" => "2017-05-25",
            ))
        // fixme 选填: 启用https
        // ,true
        );
        dd($content);
    }
    //注册
    public function register(Request $request)
    {


        //>>1.保存注册信息
        Member::create([
            'username' => $request->username,
            'password' => bcrypt($request->password),
            'tel' => $request->tel
        ]);
        //>>2.跳转登录页面
        return redirect()->route('')->with('success', '注册成功,请登录');
    }
    //验证登录
    public function store(Request $request)
    {
        //>>1.填写验证规则
        $this::validate($request,[
            'username'=>'required',
            'password'=>'required'
        ],[
            'username.required'=>'用户名必须填写',
            'password.required'=>'密码必须填写'
        ]);
        //>>2.验证登录数据
        if (Auth::attempt(['username' => $request->username, 'password' => $request->password])) {
            //登录成功跳转页面
        }else{
            return redirect()->back()->with('danger','账户或密码错误');
        }
    }
}
