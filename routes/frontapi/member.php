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
    $api->group(['prefix' => 'h5app', 'namespace' => 'MembersBundle\Http\FrontApi\V1\Action', 'middleware' => ['dingoguard:h5app', 'api.auth'], 'providers' => 'jwt'], function ($api) {
        // 获取会员详情-已支持h5
        $api->get('/wxapp/member',  ['as' => 'front.h5app.member.info', 'middleware' => 'datapass', 'uses' => 'Members@getMemberInfo']);
        $api->get('/wxapp/memberinfo',  ['as' => 'front.h5app.member.info',  'uses' => 'Members@getMemberEditInfo']);
        $api->put('/wxapp/member',  ['as' => 'front.h5app.member.update',  'uses' => 'Members@updateMember']);
        // 更新会员信息（不通过验证配置）
        $api->put('/wxapp/memberinfo',  ['as' => 'front.h5app.member_info.put',  'uses' => 'Members@updateMemberNotUseValidationConfig']);
        // 更新会员的手机号
        $api->put('/wxapp/member/mobile',  ['as' => 'front.h5app.member.mobile.update',  'uses' => 'Members@updateMemberMobile']);
        // 获取会员二维码-仅支持微信
        $api->get('/wxapp/barcode', ['as' => 'front.h5app.barcode',      'uses' => 'Members@getBarcode']);
        //获取会员优惠券、积分收藏统计数量
        $api->get('/wxapp/member/statistical',  ['as' => 'front.wxapp.member.statistical',  'uses' => 'Members@getMemberStatistical']);
        // 获取会员地址列表
        $api->get('/wxapp/member/addresslist',  ['as' => 'front.wxapp.member.address.list',  'uses' => 'Members@getAddressList']);
        // 添加会员地址
        $api->post('/wxapp/member/address',  ['as' => 'front.wxapp.member.address.add',  'uses' => 'Members@createAddress']);
        // 修改会员地址
        $api->put('/wxapp/member/address/{address_id}',  ['as' => 'front.wxapp.member.address.modify',  'uses' => 'Members@updateAddress']);
        // 获取会员地址详情
        $api->get('/wxapp/member/address/{address_id}',  ['as' => 'front.wxapp.member.address.detail',  'uses' => 'Members@getAddress']);
        // 删除会员地址
        $api->delete('/wxapp/member/address/{address_id}',  ['as' => 'front.wxapp.member.address.delete',  'uses' => 'Members@deleteAddress']);
        // 添加会员商品收藏
        $api->post('/wxapp/member/collect/item/{item_id}',  ['as' => 'front.wxapp.member.collect.item.add',  'uses' => 'Members@addItemsFav']);
        // 获取商品收藏列表
        $api->get('/wxapp/member/collect/item',  ['as' => 'front.wxapp.member.collect.item.list',  'uses' => 'Members@getItemsFavList']);
        // 获取商品收藏数量
        $api->get('/wxapp/member/collect/item/num',  ['as' => 'front.wxapp.member.collect.item.num',  'uses' => 'Members@getItemsFavNum']);
        // 删除收藏商品
        $api->delete('/wxapp/member/collect/item',  ['as' => 'front.wxapp.member.collect.item.delete',  'uses' => 'Members@deleteItemsFav']);
        // 获取商品浏览记录
        $api->get('/wxapp/member/browse/history/list',  ['as' => 'front.wxapp.member.browse.history.list',  'uses' => 'Members@getBrowseHistory']);
        // 保存商品浏览记录
        $api->post('/wxapp/member/browse/history/save',  ['as' => 'front.wxapp.member.browse.history.save',  'uses' => 'Members@saveBrowseHistory']);
        // 添加心愿单收藏
        $api->post('/wxapp/member/collect/article/{article_id}',  ['as' => 'front.wxapp.member.article.fav.add',  'uses' => 'Members@addArticleFav']);
        // 删除心愿单收藏
        $api->delete('/wxapp/member/collect/article',  ['as' => 'front.wxapp.member.article.fav.delete',  'uses' => 'Members@deleteArticleFav']);
        // 获取心愿单列表
        $api->get('/wxapp/member/collect/article',  ['as' => 'front.wxapp.member.article.fav.list',  'uses' => 'Members@getArticleFavList']);
        // 获取心愿单总数
        $api->get('/wxapp/member/collect/article/num',  ['as' => 'front.wxapp.member.article.fav.num',  'uses' => 'Members@getArticleFavNum']);
        // 查询心愿单
        $api->get('/wxapp/member/collect/article/info',  ['as' => 'front.wxapp.member.article.fav.info',  'uses' => 'Members@getArticleFavInfo']);
        // 添加收藏店铺
        $api->post('/wxapp/member/collect/distribution/{distributor_id}',  ['as' => 'front.wxapp.member.distribution.fav.add',  'uses' => 'Members@addDistributionFav']);
        // 添加店铺收藏
        $api->delete('/wxapp/member/collect/distribution',  ['as' => 'front.wxapp.member.distribution.fav.delete',  'uses' => 'Members@deleteDistributionFav']);
        // 获取收藏店铺列表
        $api->get('/wxapp/member/collect/distribution',  ['as' => 'front.wxapp.member.distribution.fav.list',  'uses' => 'Members@getDistributionFavList']);
        // 获取收藏店铺总数
        $api->get('/wxapp/member/collect/distribution/num',  ['as' => 'front.wxapp.member.distribution.fav.num',  'uses' => 'Members@getDistributionFavNum']);
        //是否收藏店铺
        $api->get('/wxapp/member/collect/distribution/check',  ['as' => 'front.wxapp.member.distribution.fav.num',  'uses' => 'Members@checkDistributionFav']);
        //商品缺货通知订阅
        $api->post('/wxapp/member/subscribe/item/{item_id}',  ['as' => 'front.wxapp.member.subscribe.item.add',  'uses' => 'Members@itemsSubscribe']);
        //绑定导购
        $api->post('/wxapp/member/bindSalesperson',  ['as' => 'front.h5app.member.bindSalesperson',  'uses' => 'Members@bindSalesperson']);
        // 记录导购被访问的UV
        $api->post('/wxapp/member/salesperson/uniquevisito',  ['as' => 'front.h5app.member.salesperson.uniquevisito',  'uses' => 'Members@salespersonUniqueVisito']);
        //会员注销
        $api->delete('/wxapp/member',  ['as' => 'front.h5app.member.delete',  'uses' => 'Members@deleteMember']);

        // 获取会员发票列表
        $api->get('/wxapp/member/invoicelist',  ['as' => 'front.wxapp.member.invoice.list',  'uses' => 'Members@getInvoiceList']);
        // 添加会员发票
        $api->post('/wxapp/member/invoice',  ['as' => 'front.wxapp.member.invoice.add',  'uses' => 'Members@createInvoice']);
        // 修改会员发票
        $api->put('/wxapp/member/invoice/{invoice_id}',  ['as' => 'front.wxapp.member.invoice.modify',  'uses' => 'Members@updateInvoice']);
        // 获取会员发票详情
        $api->get('/wxapp/member/invoice/{invoice_id}',  ['as' => 'front.wxapp.member.invoice.detail',  'uses' => 'Members@getInvoice']);
        // 删除会员发票
        $api->delete('/wxapp/member/invoice/{invoice_id}',  ['as' => 'front.wxapp.member.invoice.delete',  'uses' => 'Members@deleteInvoice']);
    });
    // 不需要授权
    $api->group(['prefix' => 'h5app', 'namespace' => 'MembersBundle\Http\FrontApi\V1\Action', 'middleware' => 'frontnoauth:h5app'], function ($api) {
        // 注册会员-已支持h5
        $api->post('/wxapp/member',  ['as' => 'front.h5app.member.create',  'uses' => 'Members@creatMember']);
        // 获取会员配置-已支持h5
        $api->get('/wxapp/member/setting',  ['as' => 'front.h5app.member.setting',  'uses' => 'Members@getRegSetting']);
        $api->get('/wxapp/member/agreement',  ['as' => 'front.wxapp.member.agreement',  'uses' => 'Members@getRegAgreementSetting']);
        // 获取短信验证码-已支持h5
        $api->get('/wxapp/member/sms/code', ['as' => 'front.h5app.member.sms.code',      'uses' => 'Members@getSmsCode']);
        // 获取图片验证码-已支持h5
        $api->get('/wxapp/member/image/code', ['as' => 'front.h5app.member.image.code',      'uses' => 'Members@getImageVcode']);
        // 重置用户密码
        $api->post('/wxapp/member/reset/password', ['as' => 'front.h5app.member.reset.password',      'uses' => 'Members@resetMemberPassword']);

        // 获取白名单设置
        $api->get('/wxapp/whitelist/status', ['as' => 'front.h5app.member.whitelist.status',      'uses' => 'Members@getWhitelistStatus']);

        //判断商品是否订阅
        $api->get('/wxapp/member/item/is_subscribe/{item_id}',  ['as' => 'front.h5app.member.item.subscribe',  'uses' => 'Members@IsSubscribe']);
        $api->get('/wxapp/trustlogin/params',  ['as' => 'front.h5app.trustlogin.params', 'uses' => 'TrustLogin@getTrustLoginParams', 'name' => '获取信任登录参数']);
        $api->get('/wxapp/trustlogin/list',  ['as' => 'front.h5app.trustlogin.list', 'uses' => 'TrustLogin@getTrustLoginList', 'name' => '获取信任登录参数']);
        // 判断是否是新会员
        $api->post('/wxapp/member/is_new',  ['as' => 'front.h5app.member.is_new',  'uses' => 'Members@isNewMember']);
        //绑定会员
        $api->post('/wxapp/member/bind',  ['as' => 'front.h5app.member.bindMember',  'uses' => 'Members@bindMember']);
    });
    $api->group(['prefix' => 'h5app', 'namespace' => 'MembersBundle\Http\FrontApi\V1\Action'], function ($api) {
        // 获取地区json
        $api->get('/wxapp/member/addressarea',  ['as' => 'front.h5app.member.address.area',  'uses' => 'Members@getAddressArea']);
        $api->get('/wxapp/member/decryptPhone', ['as' => 'front.wxapp.member.decryptPhone',      'uses' => 'Members@getNoAuthDecryptPhoneNumber']);
    });

    $api->group(['prefix' => 'h5app', 'namespace' => 'SelfserviceBundle\Http\FrontApi\V1\Action', 'middleware' => ['dingoguard:h5app', 'api.auth'], 'providers' => 'jwt'], function ($api) {
        $api->get('/wxapp/selfform/statisticalAnalysis', ['as' => 'front.wxapp.member.selfform.record',      'uses' => 'FormTemplateController@statisticalAnalysis']);
        $api->get('/wxapp/selfform/physical/datelist', ['as' => 'front.wxapp.member.selfform.datelist',      'uses' => 'FormTemplateController@getRecordDateList']);
        $api->get('/wxapp/registrationActivity', ['as' => 'front.wxapp.registration.activity.info',      'uses' => 'RegistrationActivityController@getRegistrationActivity']);
        $api->get('/wxapp/registrationRecordList', ['as' => 'front.wxapp.registration.record.list',      'uses' => 'RegistrationActivityController@getRegistrationRecordList']);
        $api->get('/wxapp/registrationRecordInfo', ['as' => 'front.wxapp.registration.record.info',      'uses' => 'RegistrationActivityController@getRegistrationRecordInfo']);
        $api->post('/wxapp/registrationSubmit', ['as' => 'front.wxapp.registration.activity.submit',      'uses' => 'RegistrationActivityController@registrationSubmit']);
        $api->post('/wxapp/joinActivity', ['as' => 'front.wxapp.registration.activity.join',      'uses' => 'RegistrationActivityController@joinActivity']);
        $api->post('/wxapp/cancelRecord', ['as' => 'front.wxapp.registration.activity.cancelRecord',      'uses' => 'RegistrationActivityController@cancelRecord']);
        $api->get('/wxapp/registrationActivityList', ['as' => 'front.wxapp.registration.activity.list',      'uses' => 'RegistrationActivityController@getRegistrationActivityList']);
    });

    // 用药人信息
    $api->group(['prefix' => 'h5app', 'namespace' => 'MembersBundle\Http\FrontApi\V1\Action', 'middleware' => ['dingoguard:h5app', 'api.auth'], 'providers' => 'jwt'], function ($api) {
        $api->post('/wxapp/medicationPersonnel', ['as' => 'front.wxapp.medicationPersonnel.create', 'uses' => 'MedicationPersonnel@create']);
        $api->put('/wxapp/medicationPersonnel', ['as' => 'front.wxapp.medicationPersonnel.update', 'uses' => 'MedicationPersonnel@update']);
        $api->get('/wxapp/medicationPersonnel/list', ['as' => 'front.wxapp.medicationPersonnel.update', 'uses' => 'MedicationPersonnel@getList']);
        $api->get('/wxapp/medicationPersonnel/detail', ['as' => 'front.wxapp.medicationPersonnel.detail', 'uses' => 'MedicationPersonnel@getDetail']);
        $api->delete('/wxapp/medicationPersonnel', ['as' => 'front.wxapp.medicationPersonnel.delete', 'uses' => 'MedicationPersonnel@deleteMedicationPersonnel']);

    });
});
/* ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ taro小程序、h5、app端、pc端 ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ */
