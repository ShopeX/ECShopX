<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
 */

$api->version('v1', function($api) {
    $api->group(['prefix' => 'h5app', 'namespace' => 'EmployeePurchaseBundle\Http\FrontApi\V1\Action', 'middleware' => 'frontnoauth:h5app'], function($api) {
        // 获取企业列表
        $api->get('/wxapp/enterprises', ['name'=> '获取企业列表', 'as' => 'front.wxapp.employeepurchase.enterprise.list', 'uses' =>'Enterprise@getEnterprisesList']);
        // 获取邮箱验证码
        $api->get('/wxapp/employee/email/vcode', ['name'=> '获取邮箱验证码', 'as' => 'front.wxapp.employeepurchase.email.vcode.get', 'uses' =>'Employee@sendEmailVcode']);
        // 验证员工的白名单，返回白名单下的企业
        $api->post('/wxapp/employee/check', ['name'=> '员工验证', 'as' => 'front.wxapp.employeepurchase.employee.check', 'uses' =>'Employee@employeeCheck']);
        // 内购活动行为流水统一上报：扫码(scan) / 口令校验(passphrase_verify)；带有效 JWT 时 company_id、user_id 来自登录态
        $api->post('/wxapp/employeepurchase/activity/behavior-report', ['name'=> '内购活动行为流水上报', 'as' => 'front.wxapp.employeepurchase.activity.behavior_report', 'uses' =>'Activity@reportActivityBehavior']);
        
        
        // 获取活动详情 基础信息 无需授权
        $api->get('/wxapp/employeepurchase/activity/detail', ['name'=> '获取活动详情', 'as' => 'front.wxapp.employeepurchase.activity.detail', 'uses' =>'Activity@getActivityDetail']);
        
    });
    $api->group(['prefix' => 'h5app', 'namespace' => 'EmployeePurchaseBundle\Http\FrontApi\V1\Action', 'middleware' => ['dingoguard:h5app', 'api.auth'], 'providers' => 'jwt'], function($api) {
        // 获取用户所在企业列表
        $api->get('/wxapp/user/enterprises', ['name'=> '获取用户所在企业列表', 'as' => 'front.wxapp.employeepurchase.user.enterprise.list', 'uses' =>'Enterprise@getUserEnterprisesList']);
        // 获取店铺数据
        $api->get('/wxapp/user/enterprise/distributor', ['name'=> '获取用户所在企业的店铺数据', 'as' => 'front.wxapp.employeepurchase.user.auth.enterprise.distributor.get', 'uses' =>'Enterprise@getUserEnterpriseDistributor']);
        // 获取邮箱验证码
        // $api->get('/wxapp/employee/email/vcode', ['name'=> '获取邮箱验证码', 'as' => 'front.wxapp.employeepurchase.email.vcode.get', 'uses' =>'Employee@sendEmailVcode']);
        // 员工身份验证
        $api->post('/wxapp/employee/auth', ['name'=> '员工身份验证', 'as' => 'front.wxapp.employeepurchase.employee.auth', 'uses' =>'Employee@authentication']);
        // 获取员工活动数据
        $api->get('/wxapp/employee/activitydata', ['name'=> '获取员工活动数据', 'as' => 'front.wxapp.employeepurchase.employee.activitydata.get', 'uses' =>'Employee@getActivityData']);
        // 获取员工邀请亲友列表
        $api->get('/wxapp/employee/invitelist', ['name'=> '获取员工邀请亲友列表', 'as' => 'front.wxapp.employeepurchase.employee.invitelist.get', 'uses' =>'Employee@getInviteList']);
        // 获取员工邀请码
        $api->get('/wxapp/employee/invitecode', ['name'=> '获取员工邀请码', 'as' => 'front.wxapp.employeepurchase.employee.invitecode.get', 'uses' =>'Employee@getInviteCode']);
        // 绑定成为亲友
        $api->post('/wxapp/employee/relative/bind', ['name'=> '绑定成为亲友', 'as' => 'front.wxapp.employeepurchase.employee.relative.bind', 'uses' =>'Employee@bindRelative']);

        // 是否开启内购
        $api->get('/wxapp/employeepurchase/is_open', ['name'=> '是否开启内购', 'as' => 'front.wxapp.employeepurchase.is_open', 'uses' =>'Activity@isOpen']);
        // 获取可参与的活动列表
        $api->get('/wxapp/employeepurchase/activities', ['name'=> '获取可参与的活动列表', 'as' => 'front.wxapp.employeepurchase.activity.list', 'uses' =>'Activity@getActivityList']);
        // 当前登录用户在某活动+企业下是否具备有效内购资格（白名单/家属）；列表、详情、加购等节点按需调用
        $api->get('/wxapp/employeepurchase/internal-sale-eligibility', ['name'=> '内购资格校验', 'as' => 'front.wxapp.employeepurchase.internal_sale_eligibility', 'uses' =>'Activity@getInternalSaleEligibility']);

        // 内购模版详情（含 resolved_pages_template_id）；字面路径较长者居前
        $api->get('/wxapp/employeepurchase/store-home-page/{id}', ['name'=> '内购模版详情ToC', 'as' => 'front.wxapp.employeepurchase.store_home_page.detail', 'uses' =>'StoreHomePage@getDetail']);

        // 字面路径须在 /activity/{activity_id}（若未来新增）之前；较长路径先于较短路径更安全。
        // 获取活动商品关联的分类
        $api->get('/wxapp/employeepurchase/activity/items/category', ['name'=> '获取活动商品关联的分类', 'as' => 'front.wxapp.employeepurchase.activity.item.category', 'uses' =>'Activity@getActivityItemCategory']);
        // 获取活动商品列表
        $api->get('/wxapp/employeepurchase/activity/items', ['name'=> '获取活动商品列表', 'as' => 'front.wxapp.employeepurchase.activity.item.list', 'uses' =>'Activity@getActivityItemList']);
        // 获取活动商品详情
        $api->get('/wxapp/employeepurchase/activity/item/{item_id}', ['name'=> '获取活动商品详情', 'as' => 'front.wxapp.employeepurchase.activity.item.detail', 'uses' =>'Activity@getActivityItemDetail']);

        // 内购购物车新增
        $api->post('/wxapp/employeepurchase/cart', ['name'=> '内购购物车新增', 'as' => 'front.wxapp.employeepurchase.cart.add', 'uses' =>'Cart@cartDataAdd']);
        // 内购购物车更新
        $api->put('/wxapp/employeepurchase/cart', ['name'=> '内购购物车更新', 'as' => 'front.wxapp.employeepurchase.cart.update', 'uses' =>'Cart@updateCartData']);
        // 修改内购购物车选中状态
        $api->put('/wxapp/employeepurchase/cart/checkstatus', ['name'=> '修改内购购物车选中状态', 'as' => 'front.wxapp.employeepurchase.cart.checkstatus.update', 'uses' =>'Cart@updateCartCheckStatus']);
        // 内购购物车商品数量
        $api->get('/wxapp/employeepurchase/cartcount',  ['name'=> '内购购物车商品数量', 'as' => 'front.wxapp.employeepurchase.cart.count',  'uses'=>'Cart@getCartItemCount']);
        // 获取内购购物车
        $api->get('/wxapp/employeepurchase/cart', ['name'=> '获取内购购物车', 'as' => 'front.wxapp.employeepurchase.cart.get', 'uses' =>'Cart@getCartDataList']);
        // 内购购物车删除
        $api->delete('/wxapp/employeepurchase/cart', ['name'=> '内购购物车删除', 'as' => 'front.wxapp.employeepurchase.cart.delete', 'uses' =>'Cart@delCartData']);

        // 修改订单的收货人信息
        $api->put('/wxapp/employeepurchase/order/receiver', ['name'=> '修改订单的收货人信息', 'as' => 'front.wxapp.employeepurchase.order.receiver.update', 'uses' =>'Order@updateOrderReceiver']);
    });
});