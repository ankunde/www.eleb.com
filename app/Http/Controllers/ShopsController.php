<?php

namespace App\Http\Controllers;

use App\Model\MenuCategories;
use App\Model\Menus;
use App\Model\Shops;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class ShopsController extends Controller
{
    /**
     * 商家列表接口
     * @return string
     */
    public function index(Request $request)
    {
        /**
         * 如果进行了搜索
         */
        if($request->keyword){
            $res = $this->search($request->keyword);
            $shop = Shops::whereIn('id',$res['matches'])->get();
                foreach ($shop as &$value){
                    $value['zhun']=1;
                    $value['distance']=637;
                    $value['estimate_time']=30;
                    unset($shop['shop_category_id']);
                }
                return json_encode($shop);

        }
        /**
         * 如果没有进行了搜索
         */
        else{
            //>>判断是否存在Redis
            if (Redis::get('shoplist')) {
                //>>2.判断是否走了Redis
                $shop = Redis::get('shoplist');
                return $shop;
            }
            //>>没有Redis,需要去数据库查数据
            else{
                //>>1.商铺信息
                $shop = Shops::select('id', 'shop_name', 'shop_img', 'shop_rating', 'brand', 'on_time', 'fengniao', 'bao', 'piao', 'start_send', 'send_cost', 'notice', 'discount')->get();
                foreach ($shop as &$value) {
                    $value['zhun'] = 1;
                    $value['distance'] = 637;
                    $value['estimate_time'] = 30;
                    unset($value['shop_category_id']);
                }
                Redis::set('shoplist', json_encode($shop));
                return json_encode($shop);
            }
        }
    }
    /**
     * 商家详情
     * @param Request $request
     * @return string
     */
    public function show(Request $request){
        $id = $request->id;
        if(!Redis::get('shop_show'.$id)){
            //>>1.查询单个商户信息
            $shop = Shops::where('id',$id)->select('id','shop_name','shop_img','shop_rating','brand','on_time','fengniao','bao','piao','start_send','send_cost','notice','discount')->first();//可以用makehidden
            $shop['zhun']=1;
            $shop['distance']=637;
            $shop['estimate_time']=30;
//        unset($shop['shop_category_id']);
            //>>2.查询商品分类表
            $commodity=MenuCategories::where('shop_id',$id)->select('description','is_selected','name','type_accumulation')->get();
//        >>3.查询菜品表
            $goods_list=Menus::where('shop_id',$id)->select('id','goods_name','rating','goods_price','description','month_sales','rating_count','tips','satisfy_count','satisfy_rate','goods_img')->get();
            foreach($goods_list as &$value){
                $value['goods_id']=$value['id'];
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
            Redis::set('shop_show'.$id,json_encode($shop));
            return json_encode($shop);
        }else{
            $shop = Redis::get('shop_show'.$id);
            return $shop;
        }
    }
    /**
     * 中文分词搜索
     */
    public function search($keyword){
        $cl = new \App\SphinxClient();
        $cl->SetServer ( '127.0.0.1', 9312);
        $cl->SetConnectTimeout ( 10 );
        $cl->SetArrayResult ( true );
        $cl->SetMatchMode ( SPH_MATCH_EXTENDED2);
        $cl->SetLimits(0, 1000);
        $info = $keyword;//request()->keyword
        $res = $cl->Query($info, 'shops');
        return $res;
    }
}
