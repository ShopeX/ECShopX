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
    // 企业相关信息
    $api->group(['namespace' => 'KaquanBundle\Http\Api\V1\Action' ,'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function($api) {
        $api->post('/discountcard', ['name' => '添加优惠券', 'as' => 'card.create', 'uses'=>'DiscountCard@createDiscountCard']);
        $api->delete('/discountcard', ['name' => '删除卡券', 'as' => 'card.delete', 'uses'=>'DiscountCard@deleteDiscountCard']);
        $api->patch('/discountcard', ['name' => '修改卡券内容', 'as' => 'card.update', 'uses'=>'DiscountCard@updateDiscountCard']);
        $api->get('/discountcard/get', ['name' => '获取卡券明细', 'as' => 'card.get', 'uses'=>'DiscountCard@getDiscountCardDetail']);
        $api->get('/discountcard/list', ['name' => '获取卡券列表', 'as' => 'card.list', 'uses'=>'DiscountCard@getDiscountCardList']);
        $api->get('/discountcard/detail/list', ['name' => '获取卡券领取列表以及使用明细', 'middleware' => ['datapass'], 'as' => 'card.detail.list', 'uses'=>'DiscountCardDetail@getDiscountCardDetail']);
        $api->get('/effectiveDiscountcard/list', ['name' => '获取有效卡券列表', 'as' => 'effectiveCard.list', 'uses'=>'DiscountCard@getEffectiveDiscountCardList']);
        $api->post('/discountcard/updatestore', ['name' => '修改卡券库存', 'as' => 'card.store', 'uses'=>'DiscountCard@updateCardStore']);
        $api->post('/discountcard/uploadToWechat', ['name' => '卡券推送至微信', 'as' => 'card.upload', 'uses'=>'DiscountCard@uploadToWechatCard']);

        $api->get('/discountcard/listdata', ['name' => '获取优惠券列表', 'as' => 'card.easy.list', 'uses'=>'DiscountCard@getEasyDiscountList']);
        $api->get('/discountcard/couponGrantSetting', ['name' => '获取优惠券发放管理配置信息', 'as' => 'couponGrantSetting.list', 'uses' => 'DiscountCard@getCouponCardGrantSetting']);
        $api->post('/discountcard/couponGrantSetting', ['name' => '保存优惠券发放管理配置信息', 'as' => 'couponGrantSetting.set', 'uses' => 'DiscountCard@setCouponCardGrantSetting']);

        $api->post('/discountcard/consume', ['name' => '兑换券核销', 'as' => 'card.consume', 'uses'=>'DiscountCard@consumeExCard']);

    });

    //会员卡相关
    $api->group(['namespace' => 'KaquanBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function($api) {
        $api->put('/membercard', ['name' => '更新会员卡设置', 'as' => 'membercard.setting', 'uses'=>'MemberCard@setMemberCard']);
        $api->get('/membercard', ['name' => '获取会员卡信息', 'as' => 'membercard.info', 'uses'=>'MemberCard@getMemberCard']);

        $api->put('/membercard/grade', ['name' => '更新会员卡等级', 'as' => 'membercard.grade.add', 'uses'=>'MemberCard@updateMembercardGrade']);
        $api->get('/membercard/defaultGrade', ['name' => '获取会员卡默认等级', 'as' => 'membercard.default.grade', 'uses'=>'MemberCard@getDefaultGrade']);
        $api->get('/membercard/grades', ['name' => '获取会员等级列表', 'as' => 'membercard.grade.list', 'uses'=>'MemberCard@getGradeList']);
    });

    //付费会员等级相关
    $api->group(['namespace' => 'KaquanBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function($api) {
        $api->put('/membercard/vipgrade', ['name' => '保存付费会员等级卡', 'as' => 'membercard.vipgrade.add', 'uses'=>'VipGradeController@addDataVipGrade']);
        $api->get('/membercard/vipgrade', ['name' => '获取付费会员等级卡列表', 'as' => 'membercard.vipgrade.list', 'uses'=>'VipGradeController@listDataVipGrade']);

        $api->get('/vipgrade/order', ['name' => '获取会员卡购买记录', 'middleware' => 'datapass', 'as' => 'vipgrade.order.list', 'uses'=>'VipGradeController@listDataVipGradeOrder']);
        $api->get('/vipgrades/uselist', ['name' => '获取指定用户所有的付费会员等级到期时间', 'as' => 'vipgrade.use.list', 'uses'=>'VipGradeController@getAllUserVipGrade']);
        $api->put('/vipgrades/active_delay', ['name' => '主动延期付费会员', 'as' => 'vipgrade.use.active.delay', 'uses'=>'VipGradeController@receiveMemberCard']);
        $api->put('/vipgrades/batch_active_delay', ['name' => '批量主动延期付费会员', 'as' => 'vipgrade.use.batch.active.delay', 'uses'=>'VipGradeController@batchReceiveMemberCard']);
    });

    // 卡券包相关操作
    $api->group(['namespace' => 'KaquanBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function ($api) {
        $api->get('/voucher/package/list', ['name' => '获取卡券包列表', 'as' => 'voucher.package.list', 'uses' => 'Package@getList']);
        $api->get('/voucher/package/details', ['name' => '获取卡券包详情', 'as' => 'voucher.package.details', 'uses' => 'Package@getDetails']);
        $api->post('/voucher/package/check_grade_limit', ['name' => '校验卡券包等级限制', 'as' => 'voucher.package.check_grade_limit', 'uses' => 'Package@checkCardPackageGradeLimit']);
        $api->get('/voucher/package/get_receives_log', ['name' => '卡券包领取日志', 'middleware' => ['datapass'], 'as' => 'voucher.package.receives_log', 'uses' => 'Package@getPackageReceivesLog']);
        $api->post('/voucher/package', ['name' => '创建卡券包', 'as' => 'voucher.package.create', 'uses' => 'Package@createPackage']);
        $api->patch('/voucher/package', ['name' => '编辑卡券包', 'as' => 'voucher.package.edit', 'uses' => 'Package@editPackage']);
        $api->delete('/voucher/package', ['name' => '删除卡券包', 'as' => 'voucher.package.delete', 'uses' => 'Package@deletePackage']);
    });

    // 移动收银相关操作
    $api->group(['namespace' => 'KaquanBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated'], 'providers' => 'jwt'], function ($api) {
        $api->get('/getUserCardList', ['name' => '获取用户可用的优惠券', 'as' => 'user.card.list', 'uses' => 'UserDiscount@getUserCardList']);
    });
});
