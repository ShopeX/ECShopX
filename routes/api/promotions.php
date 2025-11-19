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

$api->version('v1', function ($api) {
    // 微信相关信息
    $api->group(['namespace' => 'PromotionsBundle\Http\Api\V1\Action', 'middleware' => ['api.auth','shoplog'], 'providers' => 'jwt'], function ($api) {
        $api->get('/promotions/register', ['name'=>'获取注册引导营销配置','middleware' => 'activated', 'as' => 'promotions.register.get', 'uses' => 'RegisterPromotions@getRegisterPromotionsConfig']);
        $api->post('/promotions/register', ['name'=>'注册引导营销配置设置','middleware' => 'activated', 'as' => 'Promotions.register.add', 'uses' => 'RegisterPromotions@saveRegisterPromotionsConfig']);

        $api->get('/promotions/point', ['name'=>'获取注册积分配置','middleware' => 'activated', 'as' => 'promotions.register.point.get', 'uses' => 'RegisterPromotions@getRegisterPointConfig']);
        $api->post('/promotions/point', ['name'=>'注册积分配置','middleware' => 'activated', 'as' => 'Promotions.register.point.add', 'uses' => 'RegisterPromotions@saveRegisterPointConfig']);

        $api->get('/sms/basic', ['name'=>'短信账户基本信息','middleware' => 'activated', 'as' => 'Promotions.sms.basic', 'uses' => 'Sms@getSmsBasic']);
        $api->get('/sms/templates', ['name'=>'获取短信模版列表','middleware' => 'activated', 'as' => 'Promotions.sms.templates.list', 'uses' => 'Sms@getSmsTemplateList']);
        $api->get('/sms/template/detail', ['name'=>'获取短信模版详情','middleware' => 'activated', 'as' => 'Promotions.sms.template.detail', 'uses' => 'Sms@getSmsTemplateDetail']);
        $api->patch('/sms/template', ['name'=>'更新短信模版配置','middleware' => 'activated', 'as' => 'Promotions.sms.template.up', 'uses' => 'Sms@updateSmsTemplate']);
        $api->get('/sms/sign', ['name'=>'获取短信签名','middleware' => 'activated', 'as' => 'Promotions.sms.sign.get', 'uses' => 'Sms@getSmsSign']);
        $api->post('/sms/sign', ['name'=>'设置短信签名','middleware' => 'activated', 'as' => 'Promotions.sms.sign.save', 'uses' => 'Sms@saveSmsSign']);
        $api->post('/sms/send/test', ['name'=>'发送测试短信','middleware' => 'activated', 'as' => 'Promotions.sms.send.test', 'uses' => 'Sms@sendSmsTest']);
        $api->get('/wxa/notice/templates', [ 'name'=>'小程序通知消息模版','middleware'=>'activated',  'as' => 'wechat.wxa.notice.templates',  'uses'=>'WxaTemplate@getWxaTemplateList']);
        $api->put('/wxa/notice/templates', [ 'name'=>'开通小程序通知消息模版','middleware'=>'activated',  'as' => 'wechat.wxa.notice.templates.open',  'uses'=>'WxaTemplate@openWxaTemplate']);
        $api->get('/ali/notice/templates', [ 'name'=>'小程序通知消息模版','middleware'=>'activated',  'as' => 'wechat.wxa.notice.templates',  'uses'=>'AliTemplate@getAliTemplateList']);
        $api->put('/ali/notice/templates', [ 'name'=>'开通小程序通知消息模版','middleware'=>'activated',  'as' => 'wechat.wxa.notice.templates.open',  'uses'=>'AliTemplate@openAliTemplate']);

        // 微信助力活动
        $api->post('/promotions/bargains', ['name'=>'创建助力活动','middleware' => 'activated', 'as' => 'Promotions.bargains.add', 'uses' => 'BargainPromotions@createBargain']);
        $api->get('/promotions/bargains', ['name'=>'获取助力活动列表','middleware' => 'activated', 'as' => 'Promotions.bargains.list', 'uses' => 'BargainPromotions@getBargainList']);
        $api->get('/promotions/bargains/{bargain_id}', ['name'=>'获取助力活动详情','middleware' => 'activated', 'as' => 'Promotions.bargains.detail', 'uses' => 'BargainPromotions@getBargainDetail']);
        $api->put('/promotions/bargains/{bargain_id}', ['name'=>'更新助力活动','middleware' => 'activated', 'as' => 'Promotions.bargains.update', 'uses' => 'BargainPromotions@updateBargain']);
        $api->put('/promotions/bargains/termination/{bargain_id}', ['name'=>'终止助力活动','middleware' => 'activated', 'as' => 'Promotions.bargains.terminate', 'uses' => 'BargainPromotions@terminateBargain']);
        $api->delete('/promotions/bargains/{bargain_id}', ['name'=>'删除助力活动','middleware' => 'activated', 'as' => 'Promotions.bargains.update', 'uses' => 'BargainPromotions@deleteBargain']);

        // 自动化营销活动
        $api->post('/promotions/activity/validNum', ['name'=>'检查当前营销活动的有效数量','middleware' => 'activated', 'as' => 'promotions.activity.validNum', 'uses' => 'ActivityPromotions@checkActiveValidNum']);
        $api->put('/promotions/activity/invalid', ['name'=>'将当前自动化营销活动失效','middleware' => 'activated', 'as' => 'promotions.activity.invalid', 'uses' => 'ActivityPromotions@updateStatusInvalid']);
        $api->post('/promotions/activity/create', ['name'=>'创建自动化营销活动','middleware' => 'activated', 'as' => 'promotions.activity.create', 'uses' => 'ActivityPromotions@createActivity']);
        $api->get('/promotions/activity/lists', ['name'=>'获取自动化营销活动列表','middleware' => 'activated', 'as' => 'promotions.activity.lists', 'uses' => 'ActivityPromotions@getActivityList']);
        $api->post('/promotions/activity/give', ['name'=>'后台发放优惠券','middleware' => 'activated', 'as' => 'promotions.give.create', 'uses' => 'GivePromotions@give']);
        $api->get('/promotions/activity/give', ['name'=>'优惠券发放日志','middleware' => 'activated', 'as' => 'promotions.give.list', 'uses' => 'GivePromotions@getGiveLog']);
        $api->get('/promotions/activity/give/{id}', ['name'=>'优惠券赠送失败记录','middleware' => ['activated', 'datapass'], 'as' => 'promotions.give.info', 'uses' => 'GivePromotions@getGiveErrorLog']);

        // 拼团活动
        $api->get('/promotions/groups', ['name'=>'获取拼团活动列表','middleware' => 'activated', 'as' => 'promotions.groups.list', 'uses' => 'PromotionGroupsActivity@getPromotionGroupsActivityList']);
        $api->get('/promotions/groups/{groupId}', ['name'=>'获取拼团活动详情','middleware' => 'activated', 'as' => 'promotions.groups.detail', 'uses' => 'PromotionGroupsActivity@getPromotionGroupsActivityDetail']);
        $api->get('/promotions/groups/{groupId}/team/', ['name'=>'获取拼团数据详情','middleware' => 'activated', 'as' => 'promotions.groups.teamlist', 'uses' => 'PromotionGroupsActivity@getPromotionGroupsTeamList']);
        $api->get('/promotions/groups/team/{teamId}', ['name'=>'获取拼团数据成员详情','middleware' => 'activated', 'as' => 'promotions.groups.teaminfo', 'uses' => 'PromotionGroupsActivity@getPromotionGroupsTeamInfo']);
        $api->post('/promotions/groups', ['name'=>'创建拼团活动','middleware' => 'activated', 'as' => 'promotions.groups.create', 'uses' => 'PromotionGroupsActivity@createPromotionGroupsActivity']);
        $api->put('/promotions/groups/{groupId}', ['name'=>'更新拼团活动','middleware' => 'activated', 'as' => 'promotions.groups.update', 'uses' => 'PromotionGroupsActivity@updatePromotionGroupsActivity']);
        $api->put('/promotions/groups/finish/{groupId}', ['name'=>'结束拼团活动','middleware' => 'activated', 'as' => 'promotions.groups.finish', 'uses' => 'PromotionGroupsActivity@finishPromotionGroupsActivity']);
        $api->delete('/promotions/groups/{groupId}', ['name'=>'删除拼团活动','middleware' => 'activated', 'as' => 'promotions.groups.delete', 'uses' => 'PromotionGroupsActivity@deletePromotionGroupsActivity']);

        //大转盘
        $api->post('/promotions/turntableconfig', ['name'=>'修改大转盘配置','middleware' => 'activated', 'as' => 'promotions.turntable.config.set', 'uses' => 'Turntable@setTurntableConfig']);
        $api->get('/promotions/turntableconfig', ['name'=>'获取大转盘配置','middleware' => 'activated', 'as' => 'promotions.turntable.config.get', 'uses' => 'Turntable@getTurntableConfig']);
        $api->get('/promotions/getturntableList', ['name'=>'获取大转盘列表','middleware' => 'activated', 'as' => 'promotions.turntable.list.get', 'uses' => 'Turntable@getDrawActivityList']);
        $api->get('/promotions/getturntable', ['name'=>'获取大转盘详情','middleware' => 'activated', 'as' => 'promotions.turntable.detail.get', 'uses' => 'Turntable@getLuckyDrawDetail']);

        $api->get('/promotions/getturntable_log/byid', ['name'=>'获取大转盘日志','middleware' => 'activated', 'as' => 'promotions.turntable.log.list.get', 'uses' => 'Turntable@getLogStatistics']);
        $api->get('/promotions/getturntable_count/byid', ['name'=>'获取大转盘日志统计','middleware' => 'activated', 'as' => 'promotions.turntable.log.count.get', 'uses' => 'Turntable@getLogCount']);
        $api->get('/promotions/down_lucky_draw/byid', ['name'=>'活动下架','middleware' => 'activated', 'as' => 'promotions.turntable.down', 'uses' => 'Turntable@downLuckyDrawActivity']);
        $api->get('/promotions/down_lucky_draw/export', ['name'=>'活动日志导出','middleware' => 'activated', 'as' => 'promotions.turntable.log.export', 'uses' => 'Turntable@exportLog']);

        //活动文章（营销活动内容管理）
        $api->post('/promotions/activearticle', ['name'=>'添加活动文章','middleware' => 'activated', 'as' => 'promotions.article.save', 'uses' => 'ActivityPromotions@saveActiveArticle']);
        $api->get('/promotions/activearticle/list', ['name'=>'获取活动文章列表','middleware' => 'activated', 'as' => 'promotions.article.list', 'uses' => 'ActivityPromotions@getActiveArticleList']);
        $api->get('/promotions/activearticle/{id}', ['name'=>'获取活动文章详情','middleware' => 'activated', 'as' => 'promotions.article.detail', 'uses' => 'ActivityPromotions@getActiveArticleDetail']);
        $api->put('/promotions/activearticle', ['name'=>'修改活动文章','middleware' => 'activated', 'as' => 'promotions.article.update', 'uses' => 'ActivityPromotions@updateActiveArticle']);
        $api->delete('/promotions/activearticle/{id}', ['name'=>'删除活动文章','middleware' => 'activated', 'as' => 'promotions.article.delete', 'uses' => 'ActivityPromotions@deleteActiveArticle']);


        //会员营销-额外积分
        $api->post('/promotions/extrapoint', ['name'=>'创建额外积分活动','middleware' => 'activated', 'as' => 'promotions.extrapoints.create', 'uses' => 'ExtraPointActivity@createActivity']);
        $api->put('/promotions/extrapoint', ['name'=>'修改额外积分活动','middleware' => 'activated', 'as' => 'promotions.extrapoints.update', 'uses' => 'ExtraPointActivity@updateActivity']);
        $api->get('/promotions/extrapoint/lists', ['name'=>'额外积分活动列表','middleware' => 'activated', 'as' => 'promotions.extrapoints.lists', 'uses' => 'ExtraPointActivity@getActivityList']);
        $api->put('/promotions/extrapoint/invalid', ['name'=>'将当前额外积分活动失效','middleware' => 'activated', 'as' => 'promotions.extrapoints.invalid', 'uses' => 'ExtraPointActivity@updateStatusInvalid']);
        $api->get('/promotions/extrapoint/{id}', ['name'=>'获取额外积分活动详情','middleware' => 'activated', 'as' => 'promotions.extrapoints.info', 'uses' => 'ExtraPointActivity@getActivityInfo']);

        // 组合商品相关接口
        $api->get('/promotions/package', ['name'=>'组合商品活动列表','middleware' => 'activated', 'as' => 'promotions.package.list.get', 'uses' => 'PackagePromotions@lists']);
        $api->get('/promotions/package/{packageId}', ['name'=>'获取组合商品活动','middleware' => 'activated', 'as' => 'promotions.package.info.get', 'uses' => 'PackagePromotions@info']);
        $api->post('/promotions/package', ['name'=>'添加组合商品活动','middleware' => 'activated', 'as' => 'promotions.package.create', 'uses' => 'PackagePromotions@create']);
        $api->put('/promotions/package/{packageId}', ['name'=>'修改组合商品活动','middleware' => 'activated', 'as' => 'promotions.package.update', 'uses' => 'PackagePromotions@update']);
        $api->delete('/promotions/package/cancel/{packageId}', ['name'=>'取消组合商品活动','middleware' => 'activated', 'as' => 'promotions.package.cancel', 'uses' => 'PackagePromotions@cancel']);

        // 限购活动相关接口
        $api->get('/promotions/limit', ['name'=>'限购活动列表','middleware' => 'activated', 'as' => 'promotions.limit.list.get', 'uses' => 'LimitPromotions@lists']);
        $api->get('/promotions/limit_error_desc', ['name'=>'下载商品限购错误信息','middleware' => 'activated', 'as' => 'promotions.limit.error_desc.get', 'uses' => 'LimitPromotions@exportErrorDesc']);
        $api->get('/promotions/limit/{limitId}', ['name'=>'获取限购活动详情','middleware' => 'activated', 'as' => 'promotions.limit.info.get', 'uses' => 'LimitPromotions@info']);
        $api->post('/promotions/limit', ['name'=>'添加限购活动','middleware' => 'activated', 'as' => 'promotions.limit.create', 'uses' => 'LimitPromotions@create']);
        $api->put('/promotions/limit/{limitId}', ['name'=>'修改限购活动','middleware' => 'activated', 'as' => 'promotions.limit.update', 'uses' => 'LimitPromotions@update']);
        $api->post('/promotions/limit_items/upload', ['name'=>'上传限购商品文件','middleware' => 'activated', 'as' => 'promotions.limit_items.upload', 'uses' => 'LimitPromotions@uploadLimitItems']);

        $api->get('/promotions/limit_items/{limitId}', ['name'=>'获取限购商品列表','middleware' => 'activated', 'as' => 'promotions.limit_items.list.get', 'uses' => 'LimitPromotions@getLimitItems']);
        $api->delete('/promotions/limit_items/{limitId}', ['name'=>'删除限购商品','middleware' => 'activated', 'as' => 'promotions.limit_item.delete', 'uses' => 'LimitPromotions@deleteLimitItem']);
        $api->put('/promotions/limit_items/{limitId}', ['name'=>'更新限购商品数量','middleware' => 'activated', 'as' => 'promotions.limit_item.update', 'uses' => 'LimitPromotions@updateLimitItem']);
        $api->post('/promotions/limit_items_save', ['name'=>'保存限购商品','middleware' => 'activated', 'as' => 'promotions.limit_items.save', 'uses' => 'LimitPromotions@saveLimitItems']);

        $api->delete('/promotions/limit/cancel/{limitId}', ['name'=>'取消限购活动','middleware' => 'activated', 'as' => 'promotions.limit.cancel', 'uses' => 'LimitPromotions@cancel']);
        // 直播相关接口
        $api->get('/promotions/liverooms', ['name'=>'获取直播、回放视频列表', 'middleware'=>'activated',  'as' => 'promotions.liverooms',  'uses'=>'ActivityPromotions@getLiveRooms']);
    });
});

//以下为新的注册营销（分销商）
$api->version('v1', function($api) {
    // 微信相关信息
    $api->group(['namespace' => 'PromotionsBundle\Http\Api\V1\Action', 'middleware' => ['api.auth','shoplog'], 'providers' => 'jwt'], function($api) {
        $api->post('/promotions/register/distributor', ['name'=>'创建/修改 注册促销(分销商)', 'middleware'=>'activated',  'as' => 'Promotions.register.add',  'uses'=>'RegisterController@createRegister']);
        $api->get('/promotions/register/distributor', [ 'name'=>'获取注册促销列表(分销商)','middleware'=>'activated',  'as' => 'promotions.register.get',  'uses'=>'RegisterController@getRegisterList']);
        $api->get('/promotions/register/distributor/{id}', ['name'=>'获取注册促销详情(分销商)', 'middleware'=>'activated',  'as' => 'promotions.register.get',  'uses'=>'RegisterController@getRegisterInfo']);
        $api->delete('/promotions/register/distributor/{id}', ['name'=>'删除注册促销(分销商)', 'middleware'=>'activated',  'as' => 'promotions.register.get',  'uses'=>'RegisterController@deleteRegister']);
        $api->get('/promotions/distributor', [ 'name'=>'获取分销商列表','middleware'=>'activated',  'as' => 'promotions.register.get',  'uses'=>'RegisterController@getDistributorList']);
    });
});

$api->version('v1', function($api){
    $api->group(['namespace' => 'PromotionsBundle\Http\Api\V1\Action', 'middleware' => ['api.auth','shoplog'], 'providers' => 'jwt'], function($api) {
        // 秒杀活动
        $api->post('/promotions/seckillactivity/create', ['name'=>'创建秒杀活动', 'middleware'=>'activated',  'as' => 'Promotions.seckill.add',  'uses'=>'SeckillActivity@createSeckillActivity']);
        $api->put('/promotions/seckillactivity/update', [ 'name'=>'修改秒杀活动','middleware'=>'activated',  'as' => 'Promotions.seckill.update',  'uses'=>'SeckillActivity@updateSeckillActivity']);
        $api->get('/promotions/seckillactivity/getlist', ['name'=>'获取秒杀活动列表', 'middleware'=>'activated',  'as' => 'Promotions.seckill.list',  'uses'=>'SeckillActivity@getSeckillActivityList']);
        $api->get('/promotions/seckillactivity/getinfo', [ 'name'=>'创建秒杀活动','middleware'=>'activated',  'as' => 'Promotions.seckill.info',  'uses'=>'SeckillActivity@getSeckillActivityInfo']);
        $api->put('/promotions/seckillactivity/updatestatus', ['name'=>'更新秒杀活动状态', 'middleware'=>'activated',  'as' => 'Promotions.seckill.statusupdate',  'uses'=>'SeckillActivity@updateStatus']);
        $api->get('/promotions/seckillactivity/getIteminfo',['name'=>'获取秒杀活动商品列表', 'middleware'=>'activated',  'as' => 'Promotions.seckill.item.list',  'uses'=>'SeckillActivity@getSeckillItemList']);
        $api->get('/promotions/seckillactivity/wxcode',[ 'name'=>'获取秒杀活动小程序码','middleware'=>'activated',  'as' => 'Promotions.seckill.wxcode',  'uses'=>'SeckillActivity@getSeckillWxaCode']);

        //创建活动获取商品
        $api->get('/promotions/seckillactivity/search/items', ['name' => '根据条件获取商品列表', 'middleware' => 'activated', 'as' => 'Promotions.seckill.search.item.list', 'uses' => 'SeckillActivity@searchItems']);

        //以下为满折,满减，满赠营销
        $api->post('/marketing/create', ['name'=>'创建满折促销活动', 'middleware'=>'activated',  'as' => 'marketing.add',  'uses'=>'MarketingActivity@createMarketingActivity']);
        $api->delete('/marketing/delete', ['name'=>'删除满折促销活动', 'middleware'=>'activated',  'as' => 'marketing.delete',  'uses'=>'MarketingActivity@deleteMarketingActivity']);
        $api->put('/marketing/update', ['name'=>'修改满折促销活动', 'middleware'=>'activated',  'as' => 'marketing.update',  'uses'=>'MarketingActivity@updateMarketingActivity']);
        $api->get('/marketing/getlist', ['name'=>'获取满折促销活动列表', 'middleware'=>'activated',  'as' => 'marketing.list',  'uses'=>'MarketingActivity@getMarketingActivityList']);
        $api->get('/marketing/getinfo', [ 'name'=>'获取满折促销活动详情','middleware'=>'activated',  'as' => 'marketing.info',  'uses'=>'MarketingActivity@getMarketingActivityInfo']);
        $api->get('/marketing/getItemList', ['name'=>'获取满折促销活动商品列表', 'middleware'=>'activated',  'as' => 'marketing.item.list',  'uses'=>'MarketingActivity@getActivityItemList']);

        //猜你喜欢商品配置
        $api->get('/promotions/recommendlike', ['name'=>'获取猜你喜欢商品列表','middleware'=>'activated', 'as' => 'recommendlike.list', 'uses' => 'RecommendLikeController@getRecommendLikeLists']);
        $api->put('/promotions/recommendlike', ['name'=>'编辑猜你喜欢商品','middleware'=>'activated', 'as' => 'recommendlike.update', 'uses' => 'RecommendLikeController@updateRecommendLike']);
        //获取该企业的猜你喜欢的所有商品列表 或者 商品id
        $api->get('/promotions/recommendlikes', ['name'=>'获取猜你喜欢商品','middleware'=>'activated', 'as' => 'recommendlike.list', 'uses' => 'RecommendLikeController@getRecommendLikeItems']);
        $api->post('/promotions/recommendlike', ['name'=>'添加猜你喜欢商品','middleware'=>'activated', 'as' => 'recommendlike.save', 'uses' => 'RecommendLikeController@createRecommendLike']);
        $api->delete('/promotions/recommendlike/{id}', ['name'=>'删除猜你喜欢商品','middleware'=>'activated', 'as' => 'recommendlike.delete', 'uses' => 'RecommendLikeController@delRecommendLike']);

        //以下为定向促销
        $api->post('/specific/crowd/discount', ['name'=>'创建定向促销', 'middleware'=>'activated',  'as' => 'specific.crowd.discount.add',  'uses'=>'SpecificCrowdDiscount@createSpecificCrowdDiscount']);
        $api->put('/specific/crowd/discount', ['name'=>'更新定向促销', 'middleware'=>'activated',  'as' => 'specific.crowd.discount.update',  'uses'=>'SpecificCrowdDiscount@updateSpecificCrowdDiscount']);
        $api->get('/specific/crowd/discountList', ['name'=>'获取定向促销列表', 'middleware'=>'activated',  'as' => 'specific.crowd.discount.list',  'uses'=>'SpecificCrowdDiscount@getSpecificCrowdDiscountList']);
        $api->get('/specific/crowd/discountInfo', [ 'name'=>'获取定向促销详情','middleware'=>'activated',  'as' => 'specific.crowd.discount.info',  'uses'=>'SpecificCrowdDiscount@getSpecificCrowdDiscountInfo']);
        $api->get('/specific/crowd/discountLogList', [ 'name'=>'获取定向促销优惠日志','middleware'=>['activated','datapass'],  'as' => 'specific.crowd.discount.loglist',  'uses'=>'SpecificCrowdDiscount@getSpecificcrowddiscountLogList']);

        // 会员营销-积分升值
        $api->get('/promotions/pointupvaluation/lists', ['name'=>'获取积分升值活动列表', 'middleware'=>'activated',  'as' => 'promotions.pointupvaluation.list',  'uses'=>'PointupvaluationActivity@getActivityList']);
        $api->post('/promotions/pointupvaluation/create', ['name'=>'创建积分升值活动', 'middleware'=>'activated',  'as' => 'promotions.pointupvaluation.add',  'uses'=>'PointupvaluationActivity@createActivity']);
        $api->put('/promotions/pointupvaluation/update', [ 'name'=>'修改积分升值活动','middleware'=>'activated',  'as' => 'promotions.pointupvaluation.update',  'uses'=>'PointupvaluationActivity@updateActivity']);
        $api->get('/promotions/pointupvaluation/getinfo', [ 'name'=>'获取积分升值活动详情','middleware'=>'activated',  'as' => 'promotions.pointupvaluation.info',  'uses'=>'PointupvaluationActivity@getActivityInfo']);
        $api->put('/promotions/pointupvaluation/updatestatus', ['name'=>'更新积分升值活动状态', 'middleware'=>'activated',  'as' => 'promotions.pointupvaluation.statusupdate',  'uses'=>'PointupvaluationActivity@updateStatus']);
    });
});

$api->version('v1', function($api){
    $api->group(['namespace' => 'PromotionsBundle\Http\Api\V1\Action', 'middleware' => ['api.auth','shoplog'], 'providers' => 'jwt'], function($api) {
        $api->get('/promotions/checkin/getlist',['name'=>'获取会员签到记录列表', 'middleware'=>'activated',  'as' => 'Promotions.checkin.list',  'uses'=>'CheckInController@getCheckInList']);
    });
});
