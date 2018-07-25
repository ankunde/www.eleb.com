<?php

namespace App\Http\Controllers;

use App\Model\MenuCategories;
use App\Model\Menus;
use App\Model\Shops;
use Illuminate\Http\Request;

class ShopsController extends Controller
{
    public function index()
    {
        $shop = Shops::select('id','shop_name','shop_img','shop_rating','brand','on_time','fengniao','bao','piao','start_send','send_cost','notice','discount')->get();

        foreach ($shop as &$value){
            $value['zhun']=1;
            $value['distance']=637;
            $value['estimate_time']=30;
            unset($value['shop_category_id']);
        }
        return json_encode($shop);
    }
    public function show(Request $request){
        $id = $request->id;
        //>>1.查询单个商户信息
        $shop = Shops::where('id',$id)->select('id','shop_name','shop_img','shop_rating','brand','on_time','fengniao','bao','piao','start_send','send_cost','notice','discount')->first();
        $shop['zhun']=1;
        $shop['distance']=637;
        $shop['estimate_time']=30;
//        unset($shop['shop_category_id']);
        //>>2.查询商品分类表
        $commodity=MenuCategories::where('shop_id',$id)->select('description','is_selected','name','type_accumulation')->get();
//        >>3.查询菜品表
        $goods_list=Menus::where('shop_id',$id)->select('goods_name','rating','goods_price','description','month_sales','rating_count','tips','satisfy_count','satisfy_rate','goods_img')->get();
        foreach($goods_list as &$value){
            $value['goods_id']=$id;
        }
        //先把菜品压进分类
        foreach ($commodity as &$value){
            $value['goods_list']=$goods_list;
        }
        //再把分类压进商家
        $shop['commodity']=$commodity;

//        >>4.添加假数据
        $evaluate= [["user_id"=>12344,
            "username"=>"w******k",
            "user_img"=> "http://www.homework.com/images/slider-pic4.jpeg",
            "time"=>"2017-2-22",
            "evaluate_code"=>1,
            "send_time"=> 30,
            "evaluate_details"=> "不怎么好吃"]
            ,
            ["user_id"=> 12344,
                "username"=> "w******k",
                "user_img"=>"http://www.homework.com/images/slider-pic4.jpeg",
                "time"=> "2017-2-22",
                "evaluate_code"=>5,
                "send_time"=> 30,
                "evaluate_details"=> "很好吃"]];
        //把假数据压进去
        $shop['evaluate']=$evaluate;
        return json_encode($shop);
    }
}
