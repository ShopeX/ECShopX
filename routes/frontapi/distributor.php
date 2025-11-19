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

/* ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ taro小程序、h5、app端、pc端 ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ */
$api->version('v1', function ($api) {
    // 企业相关信息
    $api->group(['prefix' => 'h5app', 'namespace' => 'DistributionBundle\Http\FrontApi\V1\Action', 'middleware' => ['dingoguard:h5app', 'api.auth'], 'providers' => 'jwt'], function ($api) {
        // 佣金提现申请-已支持h5
        $api->post('/wxapp/cash_withdrawal', ['as' => 'front.wxapp.cash_withdrawal.apply',  'uses'=>'CashWithdrawal@applyCashWithdrawal']);
        // 获取提现申请列表-已支持h5
        $api->get('/wxapp/cash_withdrawals', ['as' => 'front.wxapp.cash_withdrawal.list',  'uses'=>'CashWithdrawal@getCashWithdrawalList']);
        // 获取分销商详情-已支持h5
        $api->get('/wxapp/distributor', ['as' => 'front.wxapp.distributor.get',  'uses'=>'Distributor@getDistributor']);
        // 获取分销商统计-已支持h5
        $api->get('/wxapp/distributor/count', ['as' => 'front.wxapp.distributor.count',  'uses'=>'Distributor@getDistributorCount']);
        //根据店铺id获取店铺售后地址
        $api->get('/wxapp/distributor/aftersaleaddress', ['as' => 'front.wxapp.distributor.aftersaleaddress',  'uses'=>'Distributor@getAftersaleAddressByDistributor']);

        // 业务员佣金提现申请-已支持h5
        $api->post('/wxapp/salesman/applyCashWithdrawal', ['as' => 'front.wxapp.salesman.cash_withdrawal.apply',  'uses'=>'CashWithdrawal@salesmanApplyCashWithdrawal']);
        // 业务员提现申请列表-已支持h5
        $api->get('/wxapp/salesman/getCashWithdrawalList', ['as' => 'front.wxapp.salesman.cash_withdrawal.list',  'uses'=>'CashWithdrawal@salesmanGetCashWithdrawalList']);

        //自配送员店铺列表
        $api->get('/wxapp/selfdelivery/getDistributorList', ['as' => 'front.wxapp.selfdelivery.distributor.list',  'uses'=>'SelfDeliveryStaff@getSelfDeliveryStaffDistributor']);
        $api->get('/wxapp/selfdelivery/list', ['as' => 'front.wxapp.selfdelivery.list',  'uses'=>'SelfDeliveryStaff@getSelfDeliveryList']);

        // 获取用户白名单店铺列表
        $api->get('/wxapp/distributor/whitelistByMember', ['as' => 'front.wxapp.distributor.member.whitelist',  'uses'=>'Distributor@getDistributorListByWhiteMember']);
        
    });

    //不需要小程序授权登录
    $api->group(['prefix' => 'h5app', 'namespace' => 'DistributionBundle\Http\FrontApi\V1\Action', 'middleware' => 'frontnoauth:h5app'], function ($api) {
        // 验证分销商id是否有效-已支持h5
        $api->get('/wxapp/distributor/is_valid', ['as' => 'front.wxapp.distributor.is_valid',  'uses'=>'Distributor@getDistributorIsValid']);
        // 获取店铺列表-已支持h5
        $api->get('/wxapp/distributor/list', ['as' => 'front.wxapp.distributor.list',  'uses'=>'Distributor@getDistributorList']);
        $api->get('/wxapp/distributor/alllist', ['as' => 'front.wxapp.distributor.alllist',  'uses'=>'Distributor@getAllDistributorList']);
        // 获取总部自提点店铺的详细信息
        $api->get('/wxapp/distributor/self', ['as' => 'front.wxapp.distributor.self',  'uses'=>'Distributor@getDistributionSelfDetail']);
        // 获取默认的店铺详细信息
        $api->get('/wxapp/distributor/default', ['as' => 'front.wxapp.distributor.default',  'uses'=>'Distributor@getDistributionDefaultDetail']);
        //获取店铺配送方式
        $api->get('/wxapp/distributor/deliverytype', ['as' => 'front.wxapp.distributor.deliverytype',  'uses'=>'Distributor@getDeliveryType']);
        // 逆地址解析
        $api->get('/wxapp/distributor/areainfo', ['as' => 'front.wxapp.distributor.list',  'uses'=>'Distributor@getAreaInfo']);

        //大屏首屏广告
        $api->get('/wxapp/distributor/advertisements', ['as' => 'front.wxapp.distributor.getAdvertisements', 'uses' => 'Distributor@getAdvertisements']);

        //大屏首页轮播
        $api->get('/wxapp/distributor/slider', ['as' => 'front.wxapp.distributor.getSlider', 'uses' => 'Distributor@getSlider']);

        //获取图片验证码
        $api->get('/wxapp/distributor/image/code', ['as' => 'front.wxapp.distributor.getImageVcode', 'uses' => 'Distributor@getImageVcode']);
        //获取短信验证码
        $api->get('/wxapp/distributor/sms/code', ['as' => 'front.wxapp.distributor.getSmsCode', 'uses' => 'Distributor@getSmsCode']);
        //验证短信验证码
        $api->post('/wxapp/distributor/sms/code', ['as' => 'front.wxapp.distributor.checkSmsVcode', 'uses' => 'Distributor@checkSmsVcode']);
        //查找指定门店信息，未指定返回默认门店
        $api->get('/wxapp/distributor/getDistributorInfo', ['name' => '查找指定门店信息，未指定返回默认门店', 'as' => 'front.wxapp.distributor.getDistributorInfo', 'uses' => 'Distributor@getDistributorInfo']);
        // 根据店铺id，查询商家是否可用
        $api->get('/wxapp/distributor/merchant/isvaild', ['name' => '查询店铺关联商家是否可用', 'as' => 'front.wxapp.distributor.merchant.isvaild', 'uses' => 'Distributor@getDistributorMerchantIsvaild']);

        // 获取附近的门店自提点列表
        $api->get('/wxapp/distributor/pickuplocation', ['name' => '查询附近的门店自提点列表', 'as' => 'front.wxapp.distributor.pickuplocation.list', 'uses' => 'Distributor@getNearPickupLocation']);
        // 查询附近的门店退货点列表
        $api->get('/wxapp/distributor/aftersaleslocation', ['name' => '查询附近的门店自提点列表', 'as' => 'front.wxapp.distributor.aftersaleslocation.list', 'uses' => 'Distributor@getNearAftersalesLocation']);

        $api->get('/wxapp/distributor/checkUserInWhite', ['name' => '检查该用户是否在当前白名单内', 'as' => 'front.wxapp.distributor.checkwhite.distributor', 'uses' => 'Distributor@checkUserValidInDistributor']);
        
        // 获取店铺进店规则
        $api->get('/wxapp/distributor/config/inRule', ['name' => '获取店铺进店规则', 'as' => 'front.wxapp.distributor.config.inRule', 'uses' => 'Distributor@getInRule']);
          // 店铺进店规则验证
        $api->post('/wxapp/distributor/config/inRule/check', ['name' => '验证店铺进店规则', 'as' => 'front.wxapp.distributor.config.inRule.check', 'uses' => 'Distributor@checkInRule']);
        
    });

    $api->group(['prefix' => 'h5app', 'namespace' => 'SalespersonBundle\Http\FrontApi\V1\Action', 'middleware' => ['dingoguard:h5app', 'api.auth'], 'providers' => 'jwt'], function ($api) {
        //获取导购员信息
        $api->get('/wxapp/salesperson', [ 'as' => 'salesperson.info', 'uses'=>'SalespersonController@getSalespersonInfo']);
        //提交投诉导购员信息
        $api->post('/wxapp/salesperson/complaints', [ 'as' => 'salesperson.complaints', 'uses'=>'SalespersonController@sendSalespersonComplaints']);
        //获取已投诉导购员列表
        $api->get('/wxapp/salesperson/complaintsList', [ 'as' => 'salesperson.complaints.list', 'uses'=>'SalespersonController@getSalespersonComplaintsList']);
        //查看已投诉导购员详情
        $api->get('/wxapp/salesperson/complaintsDetail/{id}', [ 'as' => 'salesperson.complaints.detail', 'uses'=>'SalespersonController@getSalespersonComplaintsDetail']);
        //用户查看与导购员的关系
        $api->get('/wxapp/usersalespersonrel', [ 'as' => 'salesperson.user.rel', 'uses'=>'SalespersonController@userSalespersonRelationship']);
        //用户查看与导购员的关系
        $api->post('/wxapp/usersalespersonrel', [ 'as' => 'salesperson.user.update', 'uses'=>'SalespersonController@bindUserSalespersonRelationship']);
        // 完成导购分享任务
        $api->post('/wxapp/salesperson/task/share', [ 'as' => 'salesperson.task.share', 'uses'=>'SalespersonTaskController@share']);
    });

    $api->group(['prefix' => 'h5app', 'namespace' => 'SalespersonBundle\Http\FrontApi\V1\Action', 'middleware' => 'frontnoauth:h5app'], function ($api) {
        //获取导购员信息
        $api->get('/wxapp/salesperson/nologin', [ 'as' => 'salesperson.info.nologin', 'uses'=>'SalespersonController@getSalespersonInfoNologin']);
        //导购员门店签到/签退
        $api->get('/wxapp/salesperson/signinQrcode', ['as' => 'salesperson.shop.getSigninQrcode', 'uses' => 'SalespersonController@getSigninQrcode']);
        $api->post('/wxapp/salesperson/signinValid', ['as' => 'salesperson.shop.signinvalid', 'uses' => 'SalespersonController@validSignin']);

        $api->post('/wxapp/salesperson/subtask/post', ['name' => '提交子任务参数', 'as' => 'h5app.wxapp.salesperson.subtask.post',  'uses'=>'SalespersonTaskController@postSubtask']);
        $api->post('/wxapp/salesperson/relationshipcontinuity', ['name' => '关系延续埋点', 'as' => 'h5app.wxapp.salesperson.relationshipcontinuity',  'uses'=>'SalespersonTaskController@relationshipContinuity']);
    });

});
/* ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ taro小程序、h5、app端、pc端 ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ */
