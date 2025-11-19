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
    $api->group(['prefix' => 'h5app', 'namespace' => 'PromotionsBundle\Http\FrontApi\V1\Action', 'middleware' => ['dingoguard:h5app', 'api.auth'], 'providers' => 'jwt'], function ($api) {
        //收录小程序formid---
        $api->post('/wxapp/promotion/formid', ['name' => '收录小程序formid', 'as' => 'front.wxapp.formid', 'uses'=>'WxaTemplateMsg@setFormId']);
        //砍价-----
        $api->get('/wxapp/promotion/bargains', ['name' => '获取砍价活动列表', 'as' => 'front.wxapp.bargains.list', 'uses'=>'BargainPromotions@getBargainList']);
        //参与砍价------
        $api->post('/wxapp/promotion/userbargain', ['name' => '参与砍价活动', 'as' => 'front.wxapp.user.bargain.join', 'uses'=>'UserBargains@createUserBargain']);
        //获取砍价分享code？？？？？
        $api->get('/wxapp/promotion/bargainfriendwxappcode', ['name' => '获取砍价分享小程序码', 'as' => 'front.wxapp.user.bargain.friendwxappcode', 'uses'=>'UserBargains@getBargainFriendWxaCode']);
        //免费领取付费会员卡---
        $api->get('/wxapp/promotion/getMemberCard', ['name' => '会员领取付费会员卡', 'as' => 'front.wxapp.promotion.register.getMemberCard', 'uses'=>'RegisterPromotions@getMembercardPromotions']);
        //用户获取秒杀资格----
        $api->get('/wxapp/promotion/seckillactivity/geticket',['name' => '用户获取秒杀资格', 'as' => 'front.promotion.seckill.geticket','uses' => 'SeckillActivity@getSeckillItemTicket']);
        $api->delete('/wxapp/promotion/seckillactivity/cancelTicket',['name' => '取消会员秒杀资格', 'as' => 'front.promotion.seckill.cancelticket','uses' => 'SeckillActivity@cancelSeckillTicket']);
        $api->post('/wxapp/promotion/checkin/create',['as' => 'front.promotion.checkin.create', 'uses' => 'CheckInController@createCheckIn']);
        $api->get('/wxapp/promotion/checkin/getlist',['as' => 'front.promotion.checkin.getlist','uses' => 'CheckInController@getCheckInList']);
        //用户获取大转盘配置
        $api->get('/wxapp/promotion/turntableconfig', ['as' => 'front.promotion.turntable.config.get', 'uses' => 'Turntable@getTurntableConfig']);

        //for david
        //抽奖
        $api->get('/wxapp/promotion/getLuckyDrawData', ['as' => 'front.promotion.turntable.data.get', 'uses' => 'Turntable@getLuckyDrawActInfo']);

        //抽奖记录
        $api->get('/wxapp/promotion/getLuckyDrawLog', ['as' => 'front.promotion.turntable.log.get', 'uses' => 'Turntable@getTurnLog']);


        //用户参与大转盘
        $api->get('/wxapp/promotion/turntable', ['as' => 'front.promotion.turntable', 'uses' => 'Turntable@joinTurntable']);
        //用户登陆赠送抽奖次数
        $api->get('/wxapp/promotion/loginaddtimes', ['name' => '用户登陆赠送抽奖次数', 'as' => 'front.promotion.turntable.loginaddtimes', 'uses' => 'Turntable@loginAddSurplusTimes']);
        // 获取加价购活动商品列表
        $api->get('/wxapp/promotion/pluspricebuy/getItemList', ['name' => '获取加价购活动商品列表', 'as' => 'front.promotion.marketing.itemlist', 'uses' => 'MarketingActivity@getPlusPriceBuyItem']);

    });

    $api->group(['prefix' => 'h5app', 'namespace' => 'PromotionsBundle\Http\FrontApi\V1\Action', 'middleware' => ['frontnoauth:h5app'], 'providers' => 'jwt'], function ($api) {
        //用户参与砍价详情-----
        $api->get('/wxapp/promotion/userbargain',  ['name' => '获取用户参加砍价活动详情', 'as' => 'front.wxapp.user.bargain.info', 'uses'=>'UserBargains@getUserBargain']);
        //创建砍价参与日志------
        $api->post('/wxapp/promotion/bargainlog',  ['as' => 'front.wxapp.user.bargain.help', 'uses'=>'UserBargains@createBargainLog']);
    });

    //暂时没有接口注释
    //以下接口必须授权登录
    $api->group(['prefix' => 'h5app', 'namespace' => 'PopularizeBundle\Http\FrontApi\V1\Action', 'middleware' => ['dingoguard:h5app', 'api.auth'], 'providers' => 'jwt'], function ($api) {
        $api->post('/wxapp/promoter', ['name' => '会员成为推广员', 'as' => 'front.promotions.promoter.add', 'uses' => 'PromoterController@changePromoter']);
        $api->put('/wxapp/promoter', ['as' => 'front.promotions.promoter.update', 'uses' => 'PromoterController@updatePromoterInfo']);
        $api->get('/wxapp/promoter/index',  ['name' => '推广员首页数据', 'as' => 'front.promotions.promoter.index', 'uses' => 'PromoterController@indexCount']);
        $api->get('/wxapp/promoter/children', ['as' => 'front.promotions.promoter.children.get', 'uses' => 'PromoterController@getPromoterchildrenList']);
        $api->get('/wxapp/childrenpromoter/list', ['name' => '获取推广员的下级推广员','as' => 'front.promotions.promoter.childrenpromoter.list', 'uses' => 'PromoterController@getChildrenpromoterList']);
        $api->get('/wxapp/promoter/qrcode',  ['name' => '获取推广员小程序码', 'as' => 'front.promotions.promoter.qrcode', 'uses' => 'PromoterController@getPromoterQrcode']);
        $api->get('/wxapp/promoter/brokerages',  ['as' => 'front.promotions.promoter.brokerage.list', 'uses' => 'BrokerageController@getBrokerageList']);
        $api->get('/wxapp/promoter/second/brokerages',  ['name' => '获取B级推广员业绩列表', 'as' => 'front.promotions.promoter.second.brokerage.list', 'uses' => 'BrokerageController@getSecondBrokerageList']);
        $api->get('/wxapp/promoter/brokerage/count',  ['as' => 'front.promotions.promoter.brokerage.count', 'uses' => 'BrokerageController@brokerageCount']);
        $api->get('/wxapp/promoter/brokerage/point_count',  ['as' => 'front.promotions.promoter.brokerage.point_count', 'uses' => 'BrokerageController@brokeragePointCount']);
        $api->get('/wxapp/promoter/taskBrokerage/logs',  ['as' => 'front.promotions.promoter.taskbrokerage.list', 'uses' => 'BrokerageController@getTaskBrokerageList']);
        $api->get('/wxapp/promoter/taskBrokerage/count',  ['as' => 'front.promotions.promoter.taskbrokerage.count', 'uses' => 'BrokerageController@getTaskBrokerageCountList']);
        $api->post('/wxapp/promoter/cash_withdrawal',  ['name' => '推广员佣金提现申请', 'as' => 'front.promotions.promoter.cash_withdrawal.apply', 'uses' => 'BrokerageController@applyCashWithdrawal']);
        $api->get('/wxapp/promoter/cash_withdrawal',  ['name' => '推广员佣金提现申请列表', 'as' => 'front.promotions.promoter.cash_withdrawal.get', 'uses' => 'BrokerageController@getCashWithdrawalList']);
        $api->get('/wxapp/brokerage/qrcode', ['name' => '获取推广二维码', 'as' => 'front.brokerage.qrcode', 'uses' => 'BrokerageController@getBrokerageQrcode']);
        $api->post('/wxapp/promoter/relgoods',  ['name' => '关联推广员关联的商品', 'as' => 'front.promotions.promoter.relgoods.post', 'uses' => 'PromoterController@relPromoterGoods']);
        $api->delete('/wxapp/promoter/relgoods',  ['name' => '删除推广员关联的商品', 'as' => 'front.promotions.promoter.relgoods.delete', 'uses' => 'PromoterController@deleteRelPromoterGoods']);

        $api->get('/wxapp/promoter/getSalesmanCount',  ['name' => '业务员统计', 'as' => 'front.promotions.promoter.getSalesmanCount', 'uses' => 'PromoterController@getSalesmanCount']);
        $api->get('/wxapp/promoter/getSalesmanStatic',  ['name' => '业务员业绩', 'as' => 'front.promotions.promoter.getSalesmanStatic', 'uses' => 'PromoterController@getSalesmanStatic']);
        $api->get('/wxapp/promoter/getSalesmanStoreitems',  ['name' => '业务员推广店铺商品', 'as' => 'front.promotions.promoter.getSalesmanStoreitems', 'uses' => 'PromoterController@getSalesmanStoreitems']);


    });

    $api->group(['prefix' => 'h5app', 'namespace' => 'PopularizeBundle\Http\FrontApi\V1\Action', 'middleware' => 'frontnoauth:h5app'], function ($api) {
        $api->get('/wxapp/promoter/info',  ['name' => '获取推广员基本信息', 'as' => 'front.promotions.promoter.get', 'uses' => 'PromoterController@getPromoterInfo']);
        $api->get('/wxapp/promoter/relgoods',  ['name' => '获取推广员关联的商品', 'as' => 'front.promotions.promoter.relgoods.get', 'uses' => 'PromoterController@getPromoterGoods']);
        $api->get('/wxapp/promoter/banner',  ['name' => '获取店招默认封面图', 'as' => 'front.promotions.promoter.banner', 'uses' => 'PromoterController@getPromoterBanner']);
        $api->get('/wxapp/promoter/custompage',  ['name' => '获取设置虚拟店首页模板', 'as' => 'front.promotions.promoter.custompage', 'uses' => 'PromoterController@getPromoterCustompage']);
        //临时接口
        $api->post('/wxapp/promoter/qrcode/log',  ['name' => '记录推广员小程序码参数', 'as' => 'front.promotions.promoter.qrcode.log', 'uses' => 'PromoterController@logPromoterQrcode']);
        $api->get('/wxapp/promoter/qrcode.png',  ['name' => '获取推广员小程序码', 'as' => 'front.promotions.promoter.qrcode.png', 'uses' => 'PromoterController@getPromoterQrcodePng']);
        $api->get('/wxapp/promoter/new_qrcode.png',  ['name' => '获取推广邀请码', 'as' => 'front.promotions.promoter.newqrcode.png', 'uses' => 'PromoterController@getPromoterNewQrcodePng']);
    });

    //以下为最新无需授权的接口
    $api->group(['prefix' => 'h5app', 'namespace' => 'PromotionsBundle\Http\FrontApi\V1\Action', 'middleware' => 'frontnoauth:h5app'], function ($api) {
        //获取注册引导营销配置-----
        $api->get('/wxapp/promotion/register', ['name' => '获取注册引导营销配置', 'as' => 'front.wxapp.promotion.register', 'uses'=>'RegisterPromotions@getRegisterPromotionsConfig']);
        //获取拼团列表----
        $api->get('/wxapp/promotions/groups', ['name' => '获取拼团列表', 'as' => 'front.promotions.groups.list', 'uses' => 'PromotionGroupsActivity@getPromotionGroupsActivityList']);
        //获取秒杀列表------
        $api->get('/wxapp/promotion/seckillactivity/getlist', ['name' => '获取秒杀列表', 'as' => 'front.promotion.seckill.getlist', 'uses' => 'SeckillActivity@getSeckillList']);
        //获取秒杀详情及秒杀商品列表------
        $api->get('/wxapp/promotion/seckillactivity/getinfo', ['name' => '获取秒杀详情及秒杀商品列表', 'as' => 'front.promotion.seckill.getinfo', 'uses' => 'SeckillActivity@getSeckillInfo']);
        $api->get('/wxapp/promotions/recommendlike', ['name' => '获取猜你喜欢列表', 'as' => 'front.promotion.recommendlike.show', 'uses' => 'RecommendLikeController@getRecommendLikeLists']);
        //获取指定商品的促销活动
        $api->get('/wxapp/promotion/getskumarketing', ['name' => '获取指定商品的活动信息', 'as' => 'front.promotion.sku.marketing', 'uses' => 'MarketingActivity@getValidMarketingActivityByItemId']);
        // 组合商品相关接口
        $api->get('/wxapp/promotions/package', ['name' => '组合商品列表', 'as' => 'front.promotion.package.list.get', 'uses' => 'PackagePromotions@lists']);
        $api->get('/wxapp/promotions/package/{packageId}', ['name' => '组合商品详情', 'as' => 'front.promotion.package.info.get', 'uses' => 'PackagePromotions@info']);
        //获取促销活动商品列表
        $api->get('/wxapp/promotion/fullpromotion/getitemlist', ['as' => 'front.promotion.fullpromotion.items.list', 'uses' => 'MarketingActivity@getMarketingActivityItemsList']);

        // 获取直播列表信息
        $api->get('/wxapp/promotion/live/list', ['name'=>'获取直播视频列表', 'as' => 'front.promotion.live.list',  'uses'=>'LiveBroadcast@getLiveList']);
        // 获取录播列表信息
        $api->get('/wxapp/promotion/replay/list', ['name'=>'获取回放视频列表', 'as' => 'front.promotion.replay.list',  'uses'=>'LiveBroadcast@getReplayList']);
    });

    $api->group(['prefix' => 'h5app', 'namespace' => 'AliBundle\Http\FrontApi\V1\Action', 'middleware' => 'frontnoauth:h5app'], function ($api) {
        //获取注册引导营销配置-----
        $api->get('/wxapp/alitemplatemessage', ['name' => '获取小程序订阅消息模板列表', 'as' => 'front.aliapp.templatemessage', 'uses'=>'AliMiniApp@getTemplateMessage']);

    });

    $api->group(['prefix' => 'h5app', 'namespace' => 'PromotionsBundle\Http\FrontApi\V1\Action', 'middleware' => 'frontnoauth:h5app'], function ($api) {

        //todo 需要删除
        $api->get('/wxapp/promotiontest/turntableconfig', ['as' => 'front.promotion.turntable.config.get.text', 'uses' => 'Turntable@getLuckyDrawActInfo']);

//        $api->get('/wxapp/promotion/turntable', ['as' => 'front.promotion.turntable', 'uses' => 'Turntable@joinTurntable']);
    });

    $api->group(['prefix' => 'h5app', 'namespace' => 'PromotionsBundle\Http\FrontApi\V1\Action', 'middleware' => ['dingoguard:h5app', 'api.auth'], 'providers' => 'jwt'], function ($api) {
        //收录小程序formid---
        $api->post('/wxapp/sign', ['name' => '用户签到', 'as' => 'front.wxapp.sign.do', 'uses'=>'UserSign@signIn']);

        $api->get('/wxapp/sign/weekly/list', ['name' => '用户签到列表', 'as' => 'front.wxapp.sign.list', 'uses'=>'UserSign@signIn']);
    });

});
/* ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ taro小程序、h5、app端、pc端 ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ */
