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


//Route::domain('admin.eleb.com')->group(function (){
//    Route::namespace('Admin')->goup(function (){
//        Route::resource('shop_categories','ShopcategorieController');
//        Route::post('/upload','ShopCategoryController@upload')->name('upload');
//    });
//});

//接口
//商家列表
Route::get('/api/businesslist','Api\ShopController@busniessList');
//获取指定商家
Route::get('/api/business','Api\ShopController@business');








////注册接口
//Route::post('/api/register',function (){
//    return 'sadfs';
//});


//发送短信接口
//Route::get('/api/sms','Api\ApiController@sms');
Route::get('/api/sms','Api\ApiController@sms');

//注册接口
Route::post('/api/regist','Api\ApiController@register');
//登陆
Route::post('/api/loginCheck','Api\ApiController@loginCheck');
//地址列表接口
Route::get('/api/addressList','Api\ApiController@addressList');
//新增地址
Route::post('/api/addAddress','Api\ApiCOntroller@addAddress');
//修改地址
Route::get('/api/address','Api\ApiCOntroller@address');
//保存修改地址
Route::post('/api/editAddress','Api\ApiCOntroller@editAddress');
//保存购物车
Route::post('/api/addCart','Api\ApiCOntroller@addCart');
//获取购物车数据接口
Route::get('/api/cart','Api\ApiCOntroller@cart');
//添加订单
Route::post('/api/addorder','Api\ApiController@addorder');
//获得指定订单接口
Route::get('/api/order','Api\ApiController@order');
//
Route::get('/api/orderList','Api\ApiController@orderList');


//修改密码
Route::post('/api/changePassword','Api\ApiController@changePassword');
//忘记密码
Route::post('/api/forgetPassword','Api\ApiController@forgetPassword');

Route::get('/aaa',function (){
    \Illuminate\Support\Facades\Redis::set('name','qwer');
    return 'adsf';
});






