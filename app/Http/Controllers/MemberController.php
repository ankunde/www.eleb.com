<?php

namespace App\Http\Controllers;

use App\Model\Address;
use App\Model\Member;
use App\Model\Menus;
use App\Model\OrderGoods;
use App\Model\Orders;
use App\Model\Shoppings;
use App\Model\Shops;
use App\SignatureHelper;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;

class MemberController extends Controller
{
    /**
     * 修改密码接口
     */
    public function changePassword(Request $request)
    {
        //>>1.验证密码
        $validator = Validator::make($request->all(),[
            'oldPassword'=>'required',
            'newPassword'=>'required',
        ],[
            'oldPassword.required'=>'旧密码必须填写',
            'newPassword.required'=>'新密码必须填写'
        ]);
        if($validator->fails()){
            return $validator->error()->first();
        }
        //>>2.验证数据库密码
        if (!Hash::check($request->oldPassword,auth()->user()->password)) {
                return [
                    "status"=> "false",
                    "message"=> "密码错误"];
        }
        //>>3.修改密码
        Member::where('id',auth()->user()->id)
            ->update([
                'password'=>bcrypt($request->newPassword)
            ]);
        return [
            "status"=> "true",
            "message"=> "修改成功"];
    }
    /**
     * 忘记密码接口
     */
    public function forgetPassword(Request $request)
    {
        //>>验证数据
        $validator = Validator::make($request->all(),[
            'tel'=>'required',
            'sms'=>'required',
            'password'=>'required'
        ],[
            'tel.required'=>'电话必须填写',
            'sms.required'=>'验证码必须填写',
            'password.required'=>'密码必须填写'
        ]);
        if($validator->fails()){
            return $validator->error()->first();
        }
        //>>验证验证码
        $code = Redis::get('code'.$request->tel);
        if($code!=$request->sms){
            return [
                "status"=>"false",
                "message"=>"验证码错误"
            ];
        }
        //>>修改密码
        Member::where('tel',$request->tel)
            ->update([
                'password'=>bcrypt($request->password)
            ]);
        return [
            "status"=>"true",
            "message"=>"重置密码成功"
        ];
    }
    /**
     * 添加订单接口
     */
    public function addorder(Request $request)
    {
        //>>1.开启事物添加数据

        $order_id =  DB::transaction(function () use($request){
            //>>1.1传过来的地址id
            $id = $request->address_id;
            //>>1.2根据地址查询地址信息
            $address = Address::where('id',$id)->first();
            //>>1.4查询当前用户的购物车表,二维数组
            $shoppings = Shoppings::where('user_id',auth()->user()->id)->get();
            //>>查询菜品表
            $shop_id='';
            foreach($shoppings as $value){
                $menus = Menus::where('id',$value->goods_id)->first();
                $shop_id = $menus->shop_id;
            }

            //>>1.3添加订单表
            $orders = Orders::create([
                'user_id'=>$address->user_id,
                'shop_id'=>$shop_id,
                'sn'=>uniqid().mt_rand(0,9999),
                'province'=>$address->province,
                'city'=>$address->city,
                'count'=>$address->county,
                'address'=>$address->address,
                'tel'=>$address->tel,
                'name'=>$address->name,
                'total'=>mt_rand(0,5),//必须先查订单表才能算出价格,先随机给一个价格,
                'status'=>3,
                'out_trade_no'=>uniqid('weixin').mt_rand(0,999)
            ]);
            $total = '';
            foreach($shoppings as $value){//$value是每一条订单数据
                //1.5根据拿到的商品id去查询对应的商品表
                $menus = Menus::where('id',$value->goods_id)->first();
                //>>1.6添加订单商品表
                $ordergoods = OrderGoods::create([
                    'order_id'=>$orders->id,//必须要有订单表才能查出订单id
                    'goods_id'=>$menus->id,
                    'amount'=>$value->amount,
                    'goods_name'=>$menus->goods_name,
                    'goods_img'=>$menus->goods_img,
                    'goods_price'=>$menus->goods_price
                ]);
                //1.7求出商品总价
                $total +=$ordergoods['goods_price']*$ordergoods['amount'];
            }
                //1.8再去修改总价
                $orders->update(['total'=>$total]);
            $tel =Auth::user()->tel;
            $params = [];
            $shops= Shops::where('id',$shop_id)->value('shop_name');

            // *** 需用户填写部分 ***

            // fixme 必填: 请参阅 https://ak-console.aliyun.com/ 取得您的AK信息
            $accessKeyId = "LTAImjBFWmSegGAH";
            $accessKeySecret = "cXNj0wekciFyEfUPM5u84zsciOJonE";

            // fixme 必填: 短信接收号码
            $params["PhoneNumbers"] = $tel;//request()->input('tel',17683287216);//
            // fixme 必填: 短信签名，应严格按"签名名称"填写，请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/sign
            $params["SignName"] = "安坤";

            // fixme 必填: 短信模板Code，应严格按"模板CODE"填写, 请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/template
            $params["TemplateCode"] = "SMS_141350001";

            // fixme 可选: 设置模板参数, 假如模板中存在变量需要替换则为必填项
            $params['TemplateParam'] = Array(
                "name" =>$shops,
//            "product" => "阿里通信"
            );



            // *** 需用户填写部分结束, 以下代码若无必要无需更改 ***
            if (!empty($params["TemplateParam"]) && is_array($params["TemplateParam"])) {
                $params["TemplateParam"] = json_encode($params["TemplateParam"], JSON_UNESCAPED_UNICODE);
            }
// 初始化SignatureHelper实例用于设置参数，签名以及发送请求
            $helper = new SignatureHelper();
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
            Mail::raw('商户认证审核已经通过',function ($message){
                $message->subject('饿了吧外卖');
                $message->to('yukuaiguyi@163.com');
            });
            return $orders->id;
        });




        //>>2.返回需要的数据
        return [
        "status"=> "true",
        "message"=> "添加成功",
        "order_id"=>$order_id,
    ];
    }
    /**
     * 获得订单列表接口
     */
    public function orderList(){
        //>>1.获取当前用户所有订单,二维数组
        $orders = Orders::where('user_id',3)->get();//订单表
        $rows = [];
        $order_price = 0;//总价
        $goods_list=[];
        foreach ($orders as $key=>$value){
            //>>2.根据商家的id查找商家
            $shops = Shops::where('id',$value->shop_id)->first();
//            >>3.查询属于当前用户的订单
            $ordergoods = OrderGoods::where('order_id',$value->id)->get();
            $a = [];
            foreach ($ordergoods as $val){
                unset($val->id);
                unset($val->order_id);
                unset($val->created_at);
                unset($val->updated_at);
                $order_price +=$val['amount'] * $val['goods_price'];
                $a[]=$val;
            }
            //>>4.开始生成符合规定格式的数据
            $rows[] = [
                'id'=>$value['id'],
                'order_code'=>$value['sn'],
                'order_birth_time'=>(string)$value['created_at'],
                'order_status'=>$value['status'],
                'shop_id'=>$shops->id,
                'shop_name'=>$shops->shop_name,
                'shop_img'=>$shops->shop_img,
                'goods_list'=>$a,
                'order_address'=>$value['province'].$value['city'].$value['count'].$value['address']."距离市中心约".mt_rand(0,1000)."米",
                'order_price'=>$order_price,
            ];
        }


    return $rows;
    }
    /**
     * 获得指定订单接口
     */
    public function order(Request $request)
    {
        //>>1.获取提交的所有订单,二维数组维数组
        $ordergoodes = OrderGoods::where('order_id',$request->id)->get();
        $orders = Orders::where('id',$request->id)->first();
        //>>2.获取商户信息
        $rows = [];
        $rows['id']=$request->id;
        $rows['order_code']=$orders->sn;
        $rows['order_birth_time']=(string)$orders->created_at;
        $rows['order_status']=$orders->status;
        $rows['order_price']=0;
        $goods_list = [];
        foreach ($ordergoodes as $key=>$value){
            //>>3.根据商品id找商品
            $menus = Menus::where('id',$value->goods_id)->first();
            //>>4.根据商家id找商家
            $shops = Shops::where('id',$menus->shop_id)->first();
            //>>5.开始拼接数据
            $rows['shop_id']=$shops->id;
            $rows['shop_name']=$shops->shop_name;
            $rows['shop_img']=$shops->shop_img;
            $goods_list[$key]['goods_id']=$menus->id;
            $goods_list[$key]['goods_name']=$menus->goods_name;
            $goods_list[$key]['goods_img']=$menus->goods_img;
            $goods_list[$key]['amount']=$value->amount;//数量
            $goods_list[$key]['goods_price']=$menus->goods_price;//单价
            $rows['goods_list'] = $goods_list;
            $rows['order_price']+=$value->amount * $menus->goods_price;
            }
        $rows['order_address']=$orders['province'].$orders['city'].$orders['count'].$orders['address']."距离市中心约".mt_rand(0,1000)."米";
        return $rows;
    }
    /**
     * 发送短信验证码
     * @param Request $request
     * @return array
     */
    public function sms(Request $request)
    {
        $tel = request()->tel;
        $params = [];

        // *** 需用户填写部分 ***

        // fixme 必填: 请参阅 https://ak-console.aliyun.com/ 取得您的AK信息
        $accessKeyId = "LTAImjBFWmSegGAH";
        $accessKeySecret = "cXNj0wekciFyEfUPM5u84zsciOJonE";

        // fixme 必填: 短信接收号码
        $params["PhoneNumbers"] = $tel;//request()->input('tel',17683287216);//
        // fixme 必填: 短信签名，应严格按"签名名称"填写，请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/sign
        $params["SignName"] = "安坤";

        // fixme 必填: 短信模板Code，应严格按"模板CODE"填写, 请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/template
        $params["TemplateCode"] = "SMS_140530021";

        // fixme 可选: 设置模板参数, 假如模板中存在变量需要替换则为必填项
        $code = random_int(000000, 999999);
        $Redis = new Redis();
        $Redis::set('code'.$tel,$code);
        $Redis::expire('code'.$tel,300);
        $params['TemplateParam'] = Array(
            "code" =>$code,
//            "product" => "阿里通信"
        );



        // *** 需用户填写部分结束, 以下代码若无必要无需更改 ***
        if (!empty($params["TemplateParam"]) && is_array($params["TemplateParam"])) {
            $params["TemplateParam"] = json_encode($params["TemplateParam"], JSON_UNESCAPED_UNICODE);
        }
// 初始化SignatureHelper实例用于设置参数，签名以及发送请求
        $helper = new SignatureHelper();
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
        return [
            "status"=>"true",
            "message"=> "获取短信验证码成功"
        ];
    }
    /**
     * 注册
     * @param Request $request
     * @return array|string
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'unique:members|required',
            'tel'=>'unique:members|required'
        ],[
            'username.unique'=>'该用户名已经注册',
            'tel.unique'=>'该电话号码已注册',
            'username.required'=>'用户名未填写',
            'tel.required'=>'电话号码为填写'
        ]);
        if ($validator->fails()) {
            return [
                "status"=>"false",
                "message"=> $validator->errors()->first(),
            ];
        }
        $redis = new Redis();
        $code = $redis::get('code'.$request->tel);
        if ($code!=$request->sms){
            return [
                'status'=>'false',
                'message'=>'验证码错误'
            ];
        }
        //>>1.保存注册信息
        Member::create([
            'username' => $request->username,
            'password' => bcrypt($request->password),
            'tel' => $request->tel
        ]);
        //>>2.跳转登录页面
        return json_encode(["status"=>"true","message"=>"注册成功"]);
    }
    /**
     * 验证登录
     */
    public function store(Request $request)
    {
        if (Auth::attempt([
            'username' => $request->name,
            'password' => $request->password,
            ])
        )
        {
            $row = [
                "status"=>"true",
                "message"=>"登录成功",
                "user_id"=>Auth::user()->id,
                "username"=>Auth::user()->username
            ];
            //登录成功返回json数据
            return json_encode($row);
        }else{
            $row = [
                "status"=>"false",
                "message"=>"登录失败",
                "user_id"=>'',
                "username"=>''
            ];
            return json_encode($row);
        }
    }
    /**
     * 地址列表接口
     */
    public function addressList()
    {
        $address = Address::where('user_id',auth()->user()->id)->get();
        foreach ($address as &$value){
            $value["area"]=$value['county'];
            $value["detail_address"]=$value['address'];
        }
        return json_encode($address);
    }
    /**
     * 指定地址接口
     */
    public function address(Request $request)
    {
        $row = Address::where('id',$request->id)->first();
        $row['provence']=$row['province'];
        $row['area']=$row['county'];
        $row['detail_address']=$row['address'];
        return json_encode($row);
    }
    /**
     * 保存新增地址接口
     */
    public function addAddress(Request $request)
    {
        //>>1.验证数据
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:10',
            'tel' => 'required',
            'provence'=>'required',
            'city'=>'required',
            'area'=>'required',
            'detail_address'=>'required'
        ],[
            'name.required'=>'收货人不能为空',
            'name.max'=>'收货人长度不能大于10',
            'tel.required'=>'联系方式不能为空',
            'provence.required'=>'省必须填',
            'city.required'=>'市必须填',
            'area.required'=>'区必须填',
            'detail_address.required'=>'详细地址必须填写'
        ]);
        //>>2.输出一条错误信息
        if ($validator->fails()) {
            return [
               'status'=>'false',
                'message'=>$validator->errors()->first()
            ] ;
        }
        //>>3.保存添加信息
        Address::create([
            'name'=>$request->name,
            'tel'=>$request->tel,
            'province'=>$request->provence,
            'city'=>$request->city,
            'county'=>$request->area,
            'address'=>$request->detail_address,
            'user_id'=>auth()->user()->id,
            'is_default'=>1,
        ]);
        //>>4.返回指定格式
        return [
            'status'=>'true',
            'message'=>'添加成功'
        ];
    }
    /**
     * 保存修改地址接口
     */
    public function editAddress(Request $request)
    {
        //>>1.数据判断
        $value= Validator::make($request->all(),[
            'name'=>'required|max:10',
            'tel'=>'required',
            'province'=>'required',
            'city'=>'required',
            'count'=>'required',
            'address'=>'required'
        ],[
            'name.required'=>'收货人必须填写',
            'name.max'=>'收货人长度不能超过10',
            'tel.required'=>'电话号码必须填写',
            'province.required'=>'省必须填写',
            'city.required'=>'市必须填写',
            'count.required'=>'区/县必须填写',
            'address.required'=>'详细地址必须填写'
        ]);
        //>>2.修改数据
        Address::where('id',$request->id)->update([
            'name'=>$request->name,
            'tel'=>$request->tel,
            'province'=>$request->provence,
            'city'=>$request->city,
            'county'=>$request->area,
            'address'=>$request->detail_address
        ]);
        //>>3.返回指定数据
        return [
            "status"=> "true",
            "message"=> "修改成功"
        ];
    }
    /**
     * 保存购物车接口
     */
    public function addCart(Request $request)
    {
        //>>2.保存数据
        //创建一个新数组,将一个数组的值作为key,另一个数组的值作为value
        $arr = array_combine($request->goodsList,$request->goodsCount);
        Shoppings::where('user_id',Auth::user()->id)->delete();
        foreach ($arr as $key=>$value){
            Shoppings::create([
                'user_id'=>Auth::user()->id,
                'goods_id'=>$key,
                'amount'=>$value
            ]);
        }
        //>>3.返回指定格式数据
        return [
            "status"=>"true",
            "message"=>"添加成功"
        ];
    }
    /**
     * 获取购物车数据接口
     */
    public function cart(Request $request)
    {
        $id = Auth::user()->id;
        $rows = Shoppings::where('user_id',$id)->get();
//        array_column($rows,);
        $good = [];
        $totalCost='';
        foreach($rows as $value){//$value订单表的每一条数据
            $row = Menus::where('id',$value->goods_id)->first();//下单的每一条商品数据
            $good[]=[
                'goods_id'=>$value->goods_id,
                'goods_name'=>$row->goods_name,
                'goods_img'=>$row->goods_img,
                'amount'=>$value->amount,
                'goods_price'=>$row->goods_price
            ];
            $totalCost +=$value->amount * $row->goods_price;
        }
        return ['goods_list'=>$good,'totalCost'=>$totalCost];

    }
}
