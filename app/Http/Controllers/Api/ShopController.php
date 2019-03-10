<?php

namespace App\Http\Controllers\Api;

use App\Models\Menu;
use App\Models\Menucategorie;
use App\Models\Shop;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ShopController extends Controller
{
    //获得商家列表
    public function busniessList(Request $request)
    {
        $keyword = $request->keyword;
        if ($keyword) {
            $shops = Shop::where('shop_name', 'like', "%$keyword%")->get();
        } else {
        $shops = Shop::all();
        }
        return $shops;
    }
    //获取指定商家
    public function business(Request $request)
    {

        $id = $request->id;
        $shops = Shop::where('id', '=', "$id")->get();
//        $shop = Shop::find($id);
        $wheres[] = ['shop_id','=',$id];
        $menucategories = Menucategorie::where($wheres)->get();

        foreach ($menucategories as $menucategorie):
            $menucategorie['goods_list']=Menu::where('category_id','=',$menucategorie->id)->get();
            foreach ($menucategorie['goods_list'] as $good):
                $good['goods_id'] = $good->id;
            endforeach;
        endforeach;

        $shops['evaluate'] = [[
                "user_id"=> 12344,
                "username"=> "w******k",
                "user_img"=> "/images/slider-pic4.jpeg",
                "time"=> "2017-2-22",
                "evaluate_code"=> 1,
                "send_time"=> 30,
                "evaluate_details"=> "不怎么好吃",
            ],
            [
                "user_id"=> 12344,
                "username"=> "w******k",
                "user_img"=> "/images/slider-pic4.jpeg",
                "time"=> "2017-2-22",
                "evaluate_code"=> 4.5,
                "send_time"=> 30,
                "evaluate_details"=> "很好吃",
            ],
            [
                "user_id"=> 12344,
                "username"=> "w******k",
                "user_img"=> "/images/slider-pic4.jpeg",
                "time"=> "2017-2-22",
                "evaluate_code"=> 5,
                "send_time"=> 30,
                "evaluate_details"=> "很好吃"
            ]
        ];




        $shops['commodity'] = $menucategories;


//        $menu = Menu::where('shop_id','=',"$shop[0]")->get();
//        $shop[] = [$menu];
        return $shops;
    }
}
