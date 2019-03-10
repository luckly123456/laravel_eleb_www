<?php

namespace App\Http\Controllers\Api;

use App\Models\Address;
use App\Models\Cart;
use App\Models\Member;
use App\Models\Menu;
use App\Models\Order;
use App\Models\Orderdetail;
use App\Models\Shop;
//use Dotenv\Validator;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Qcloud\Sms\SmsSingleSender;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ApiController extends Controller
{
    //获得商家列表
    public function busniessList()
    {
        $shops = Shop::all();
        return $shops;
    }

    public function sms(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tel' => 'required|numeric|digits_between:11,11',
        ]);

        //
        if ($validator->fails()) {
            return [
                "status"=> "false",
                "message"=> implode(' ',$validator->errors()->all()),//$validator->errors()->first('tel')
            ];
        }

        $tel = $request->tel;
        $num = mt_rand(1000,9999);
        $tokennum = $num;

//        return $num;

        // 短信应用SDK AppID
        $appid =1400187986; // 1400开头

        // 短信应用SDK AppKey
        $appkey = "34ead0295efa5d84ecfdbc7c11e7c865";

        // 需要发送短信的手机号码
        $phoneNumbers = $tel;
        //templateId7839对应的内容是"您的验证码是: {1}"
        // 短信模板ID，需要在短信应用中申请
        $templateId = 285046;  // NOTE: 这里的模板ID`7839`只是一个示例，真实的模板ID需要在短信控制台中申请

        $smsSign = "安小康的快乐成长空间"; // NOTE: 这里的签名只是示例，请使用真实的已申请的签名，签名参数使用的是`签名内容`，而不是`签名ID`
        try {
            $ssender = new SmsSingleSender($appid, $appkey);
            $params = [$num,5];//数组具体的元素个数和模板中变量个数必须一致，例如事例中 templateId:5678对应一个变量，参数数组中元素个数也必须是一个
            $result = $ssender->sendWithParam("86", $phoneNumbers, $templateId,
                $params, $smsSign, "", "");  // 签名参数未提供或者为空时，会使用默认签名发送短信
            $rsp = json_decode($result);
            var_dump($result);
            if ($result){
                \Illuminate\Support\Facades\Redis::set($tel,$tokennum);
                return ["status"=> "true",
                        "message"=> "获取短信验证码成功"];
            }else{
                return ["status"=> "flase",
                    "message"=> "请输入号码"];
            }
        } catch(\Exception $e) {
            echo var_dump($e);
            return ["status"=> "flase",
                "message"=> "验证错误"];
        }
    }


    public function register(Request $request)
    {
        $tel = $request->tel;
        $num = \Illuminate\Support\Facades\Redis::get($tel);
        if($request->sms != $num){
            return ["status"=> "false","message"=> "注册失败"];
        }
        Member::create([
            'username'=>$request->username,
            'password'=>Hash::make($request->password),
            'tel'=>$request->tel,
        ]);
        return ["status"=> "true","message"=> "注册成功"];
    }

    public function loginCheck(Request $request)
    {
        $user_id = Member::where('username','=',$request->name)->get();
        if(Auth::attempt([
            'username'=>$request->name,
            'password'=>$request->password
        ])){
//            return redirect()->intended(route('admins.index'))->with('success','登录成功');

            return [ "status"=>"true","message"=>"登录成功","user_id"=>$user_id,"username"=>$request->name];
        }
        return [ "status"=>"false","message"=>"登录失败","user_id"=>$user_id,"username"=>$request->name];
    }

    //地址列表接口
    public function addressList(Request $request)
    {
        $id = Auth::user()->id;
        $addresses = Address::where('user_id','=',$id)->get();
        return $addresses;
//        $addresses = [
//        [
//            "id"=> "1",
//      "provence"=> "四川省",
//      "city"=> "成都市",
//      "area"=> "武侯区",
//      "detail_address"=> "四川省成都市武侯区天府大道56号",
//      "name"=> "张三",
//      "tel"=> "18584675789"]
//
//        ];
        return $addresses;
    }

    //保存新增地址
    public function addAddress(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'tel' => 'required',
            'provence' => 'required',
            'city' => 'required',
            'area' => 'required',
            'detail_address' => 'required',
        ]);

        Address::create([
            'user_id'=>Auth::user()->id,
            'name'=>$request->name,//收货人
            'tel'=>$request->tel,//电话
            'province'=>$request->provence,//省
            'address'=>$request->detail_address,//详细地址
            'city'=>$request->city,//市
            'county'=>$request->area,//区县
            'is_default'=>0,
        ]);
        return ["status"=> "true","message"=> "添加成功"];


    }

    //修改地址
    public function address(Request $request,Address $address)
    {
        $address = Address::where('id','=',$request->id)->first();
        $address['provence'] = $address->province;
        $address['area'] = $address->county;
        $address['detail_address'] = $address->address;
        return $address;
    }

    public function editAddress(Request $request,Address $address)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'tel' => 'required',
            'provence' => 'required',
            'city' => 'required',
            'area' => 'required',
            'detail_address' => 'required',
        ]);
        Address::where('id','=',Auth::user()->id)->update([
            'name'=>$request->name,//收货人
            'tel'=>$request->tel,//电话
            'province'=>$request->provence,//省
            'address'=>$request->detail_address,//详细地址
            'city'=>$request->city,//市
            'county'=>$request->area,//区县
        ]);

         return ["status"=> "true","message"=> "修改成功"];

    }

    public function addCart(Request $request){
        $validator = Validator::make($request->all(), [
            'goodsList' => 'required',
            'goodsCount' => 'required',
        ]);
        $num = 0;
        foreach ($request['goodsList'] as $goods_id):
            Cart::create([
            'user_id'=>Auth::user()->id,
            'goods_id'=>$goods_id,
                'amount'=>$request->goodsCount[$num],

        ]);
            $num +=1;
            endforeach;
        return ["status"=> "true","message"=> "添加成功"];
    }

    public function cart()
    {
        $carts = Cart::where('user_id',Auth::user()->id)->get();
        $prices = 0;
        $shops = [];
        $num = 0;
        foreach ($carts as $cart):
            $cart->goods_id;
                $goods = Menu::where('id',$cart->goods_id)->get();
            $price = $goods[0]->goods_price;
            $prices = $prices + $price;
            $shops['goods_list'] = $goods;
            $shops['totalCost'] = $prices;
        endforeach;
//        DB::table('carts')->where('user_id',Auth::user()->id)->delete();
        return $shops;
    }

    public function addorder(Request $request)
    {
        $adress = Address::where('id',$request->address_id)->get();
         $carts = Cart::where('user_id',Auth::user()->id)->get();
        $prices = 0;
        $shop_id = 0;
         foreach ($carts as $cart):
             $goods = Menu::where('id',$cart->goods_id)->get();
            $prices = $goods[0]->goods_price * $goods[0]->amount;
             $shop_id = $goods[0]['shop_id'];
             endforeach;

        Order::create([
            'user_id'=>Auth::user()->id,
            'shop_id'=>$shop_id,
            'sn'=>time().mt_rand(1000,9999),
            'province'=>$adress[0]->province,//省
//            'city'=>$request[0]->city,//市
//            'county'=>$request[0]->area,//区县
//            'address'=>$request[0]->detail_address,//详细地址
//            'tel'=>$request[0]->tel,//电话
//            'name'=>$request[0]->name,//收货人
            'city'=>'asd',//市
            'county'=>'asd',//区县
            'address'=>'asd',//详细地址
            'tel'=>'asd',//电话
            'name'=>'sad',//收货人
            'total'=>$prices,
            'status'=>0,
            'out_trade_no'=>mt_rand(100000,999999),
        ]);

        $id = Order::where('user_id',Auth::user()->id)->get()[0]->id;
        foreach ($carts as $cart):
            $goods = Menu::where('id',$cart->goods_id)->get();
            Orderdetail::create([
                'order_id'=>$id,
                'goods_id'=>$goods[0]->id,
                'amount'=>$cart->amount,
                'goods_name'=>$goods[0]->goods_name,
                'goods_img'=>$goods[0]->goods_img,
                'goods_price'=>$goods[0]->goods_price,
            ]);
//            return $goods[0]->goods_img;
        endforeach;
//        DB::table('carts')->where('user_id',Auth::user()->id)->delete();


        $title = '全新体验，手机也能玩转网易邮箱2.0';
        $content = '<p>	重要的邮件如何才能让对方立刻查看随身邮，可以让您享受随时短信提醒和发送邮件可以短信通知收件人的服务，重要的邮件一个都不能少！</p>';
        try{
            \Illuminate\Support\Facades\Mail::send('email.default',compact('title','content'),
                function($message){
                    $to = '18728407510@163.com';
                    $message->from(env('MAIL_USERNAME'))->to($to)->subject('有新用户下单啦!!');
                });
        }catch (Exception $e){
            return '邮件发送失败';
        }


        return ["status"=> "true","message"=> "添加成功","order_id"=>1];
//        return $id;

    }


//    public function order(Request $request)
//    {
//        $id = $request->id;
//        $or = Order::where('id',$id)->get()[0];
//        $shop = Shop::where('id',$or->shop_id)->get()[0];
//        $order_status = '待支付';
//        if($or->status==-1){
//            $order_status = '已取消';
//        }elseif ($or->status==0){
//            $order_status = '待支付';
//        }elseif ($or->status==1){
//            $order_status = '待发货';
//        }elseif ($or->status==2){
//            $order_status = '待确认';
//        }elseif ($or->status==3){
//            $order_status = '完成';
//        }
//
//
//        $shop['order_code'] = $or['sn'];
//        $shop['order_birth_time'] = $or['created_at'];
//        $shop['order_status'] = $order_status;
//
//
//        $goods_list = Orderdetail::where('order_id',$or->id)->get();
////
//        $shop['goods_list'] = $goods_list;
//
//        $order = [
//            "id"=> $or->id,
//        "order_code"=> $or->sn,
//        "order_birth_time"=> $or->created_at,
//        "order_status"=> $order_status,
//        "shop_id"=> $or->shop_id,
//        "shop_name"=> "上沙麦当劳",
//        "shop_img"=> "/images/shop-logo.png",
//        "goods_list"=> [[
//        "goods_id"=> "1",
//            "goods_name"=> "汉堡",
//            "goods_img"=> "/images/slider-pic2.jpeg",
//            "amount"=> 6,
//            "goods_price"=> 10
//        ]],
//        "order_price"=> 120,
//        "order_address"=> "北京市朝阳区霄云路50号 距离市中心约7378米北京市朝阳区霄云路50号 距离市中心约7378米"
//        ];
//
//        return $shop;
//    }


//    public function orderList(Request $request)
//    {
//        $order_list = Order::where('user_id',Auth::user()->id)->get();
//
//        foreach ($order_list as $order):
//            $order_status = '待支付';
//            if($order->status==-1){
//                $order_status = '已取消';
//            }elseif ($order->status==0){
//                $order_status = '待支付';
//            }elseif ($order->status==1){
//                $order_status = '待发货';
//            }elseif ($order->status==2){
//                $order_status = '待确认';
//            }elseif ($order->status==3){
//                $order_status = '完成';
//            }
//
//
//            $shop = Shop::where('id',$order->shop_id)->get();
//            $prices = 0;
//            foreach ($shop as $sh):
//                $prices =$prices+ $sh->goods_price;
//                endforeach;
//            $order['order_code'] = $request->order_code;
//            $order['order_birth_time']=$request->order_birth_time;
//            $order['order_status']=$request->order_status;
//            $order['shop_name']=$request->shop_name;
//            $order['shop_img']=$request->shop_img;
//
//            $goods_list = Orderdetail::where('order_id',$order->id)->get();
//
//            $order['goods_list']=$goods_list;
//            $order['order_price']=$prices;
//            $order['order_address']=$order->province.$order->city.$order->county.$order->address;
//
//            endforeach;
//        return $order_list;
//    }

    public function Order(Request$request){
        $orders=Order::where('id',$request->id)->first();
        //dd($orders->shop_id);
        $shop=Shop::where('id',$orders->shop_id)->first();
        $orders["shop_name"]=$shop->shop_name;
        $orders["shop_img"]=$shop->shop_img;
        $order_detail=OrderDetail::where('order_id',$orders->id)->get();
        //dd($order_detail);
        $orders["goods_list"]=$order_detail;
        $orders["order_price"]=$orders->total;
        $orders["order_address"]=$orders->address;
        $orders["order_code"]=$orders->sn;
        $orders["order_birth_time"]=substr($orders->created_at,0,16);
        if($orders->status==-1){
            $status="已取消";
        }elseif($orders->status==0){
            $status="待支付";
        }elseif($orders->status==1){
            $status = "待发货";
        }elseif($orders->status==2){
            $status = "待确认";
        }else{
            $status = "完成";
        }
        $orders["order_status"]=$status;
        return $orders;
    }



    public function OrderList(){
        $orders=Order::where('user_id',Auth::user()->id)->get();
        foreach ($orders as $order):
            $shop=Shop::where('id',$order->shop_id)->first();
            $order["shop_name"]=$shop->shop_name;
            $order["shop_img"]=$shop->shop_img;
            $order_detail=OrderDetail::where('order_id',$order->id)->get();
            $order["goods_list"]=$order_detail;
            $order["order_price"]=$order->total;
            $order["order_address"]=$order->address;
            $order["order_code"]=$order->sn;
            $order["order_birth_time"]=substr($order->created_at,0,16);
            if($order->status==-1){
                $status="已取消";
            }elseif($order->status==0){
                $status="待支付";
            }elseif($order->status==1){
                $status = "待发货";
            }elseif($order->status==2){
                $status = "待确认";
            }else{
                $status = "完成";
            }
            $order["order_status"]=$status;
        endforeach;
        return $orders;
    }



    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'oldPassword' => 'required',
            'newPassword' => 'required',
        ]);
        $re = Member::where('id','=',Auth::user()->id)->get()[0]->username;

        if(Auth::attempt([
            'username'=>$re,
            'password'=>$request->oldPassword
        ])){
            Member::where('id','=',Auth::user()->id)->update([
            'password'=>$request->newPassword,
        ]);

            return ["status"=>"true","message"=>"修改成功"];
        }
        return ["status"=>"false","message"=>"修改失败"];
    }

    public function forgetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tel' => 'required',
            'sms' => 'required',
            'password' => 'required',
        ]);

        $tel = $request->tel;
        $password = $request->password;
        $num = \Illuminate\Support\Facades\Redis::get($tel);
        if($request->sms != $num){
            return ["status"=> "false","message"=> "重置失败"];
        }
        Member::where('tel',$tel)->update([
            'password'=>Hash::make($password),
        ]);
        return ["status"=> "true","message"=> "重置成功"];
    }
}










