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
    // 微信相关信息
    $api->group(['namespace' => 'WechatBundle\Http\Api\V1\Action', 'middleware' => ['api.auth','shoplog'], 'providers' => 'jwt'], function($api) {
        $api->get('/wechat/pre_auth_url', ['name'=>'获取微信公众号预授权URL地址','middleware'=>'activated',  'as' => 'wechat.pre_auth_url',  'uses'=>'Authorized@getPreAuthUrl']);
        $api->post('/wechat/bind', [ 'name'=>'回调绑定当前授权信息', 'middleware'=>'activated', 'as' => 'wechat.bind', 'uses'=>'Authorized@authorizedBind']);
        $api->post('/wechat/directbind', [ 'name'=>'添加直连小程序', 'middleware'=>'activated', 'as' => 'wechat.bind.direct', 'uses'=>'Authorized@directBind']);

        $api->get('/wechat/authorizerinfo', [ 'name'=>'获取公众帐号基础信息','middleware'=>'activated', 'as' => 'wechat.authorizerInfo', 'uses'=>'Authorized@getAuthorizerInfo']);
        //同步微信端菜单到开发端
        //$api->get('/wechat/menu', [ 'middleware'=>'activated', 'as' => 'wechat.menu', 'uses'=>'Menu@getAll']);

        $api->post('/wechat/menu', [ 'name'=>'添加菜单', 'middleware' => 'activated',  'as' => 'wechat.add_menu', 'uses'=>'Menu@addMenu']);
        $api->delete('/wechat/menu', [ 'name'=>'删除菜单', 'middleware' => 'activated',  'as' => 'wechat.remove_menu', 'uses'=>'Menu@removeMenu']);
        $api->get('/wechat/menutree', ['name'=>'获取菜单树形列表','middleware' => 'activated',  'as' => 'wechat.get_menu', 'uses' => 'Menu@getMenuTree']);

        $api->post('/wechat/kfs', [ 'name'=>'添加微信客服', 'middleware'=>'activated', 'as' => 'wechat.create.kfs', 'uses'=>'Kf@createWechatKf']);
        $api->get('/wechat/kfs', [ 'name'=>'获取所有客服账号列表','middleware'=>'activated', 'as' => 'wechat.lists.kfs', 'uses'=>'Kf@lists']);
        $api->delete('/wechat/kfs', ['name'=>'删除指定微信客服', 'middleware'=>'activated', 'as' => 'wechat.delete.kfs', 'uses'=>'Kf@deleteWechatKf']);
        $api->post('/wechat/update/kfs', [ 'name'=>'修改指定微信客服','middleware'=>'activated', 'as' => 'wechat.update.kfs', 'uses'=>'Kf@updateWechatKf']);

        $api->post('/wechat/keyword/reply', ['name'=>'新增关键字消息回复',  'middleware'=>'activated', 'as' => 'wechat.keyword.messagereply.add', 'uses'=>'MessageReply@addKeywordReply']);
        $api->get('/wechat/keyword/reply', ['name'=>'获取关键字回复列表', 'middleware'=>'activated', 'as' => 'wechat.keyword.messagereply.get', 'uses'=>'MessageReply@getKeywordReplyList']);
        $api->put('/wechat/keyword/reply', [ 'name'=>'更新关键字消息回复','middleware'=>'activated', 'as' => 'wechat.keyword.messagereply.put', 'uses'=>'MessageReply@updateKeywordReply']);
        $api->delete('/wechat/keyword/reply', [ 'name'=>'删除关键字回复规则','middleware'=>'activated', 'as' => 'wechat.keyword.messagereply.delete', 'uses'=>'MessageReply@deleteKeywordReply']);

        $api->post('/wechat/default/reply', [ 'name'=>'设置默认回复', 'middleware'=>'activated', 'as' => 'wechat.default.messagereply.add', 'uses'=>'MessageReply@setDefaultReply']);
        $api->get('/wechat/default/reply', ['name'=>'获取默认消息回复', 'middleware'=>'activated', 'as' => 'wechat.default.messagereply.get', 'uses'=>'MessageReply@getDefaultReply']);
        $api->get('/wechat/openkf/reply', ['name'=>'获取多客服回复配置', 'middleware'=>'activated', 'as' => 'wechat.openkf.messagereply.get', 'uses'=>'MessageReply@getOpenKfReply']);
        $api->post('/wechat/openkf/reply', [ 'name'=>'设置多客服回复配置','middleware'=>'activated', 'as' => 'wechat.openkf.messagereply.set', 'uses'=>'MessageReply@setOpenKfReply']);
        $api->get('/wechat/subscribe/reply', [ 'name'=>'获取被关注自动回复消息配置','middleware'=>'activated', 'as' => 'wechat.subscribe.messagereply.get', 'uses'=>'MessageReply@getSubscribeReply']);
        $api->post('/wechat/subscribe/reply', [ 'name'=>'设置被关注自动回复消息', 'middleware'=>'activated', 'as' => 'wechat.subscribe.messagereply.set', 'uses'=>'MessageReply@setSubscribeReply']);

        $api->post('/wechat/material', ['name'=>'上传素材', 'middleware'=>'activated', 'as' => 'wechat.material.upload', 'uses'=>'Material@uploadMaterial']);
        $api->post('/wechat/news/image', ['name'=>'上传图文内的图片', 'middleware'=>'activated', 'as' => 'wechat.news.image.upload', 'uses'=>'Material@uploadArticleImage']);
        $api->delete('/wechat/material', [ 'name'=>'删除素材','middleware'=>'activated', 'as' => 'wechat.material.delete', 'uses'=>'Material@deleteMaterial']);
        $api->get('/wechat/material', [ 'name'=>'获取永久素材列表','middleware'=>'activated', 'as' => 'wechat.material.list', 'uses'=>'Material@getMaterialLists']);
        $api->get('/wechat/material/stats', ['name'=>'获取素材状态', 'middleware'=>'activated', 'as' => 'wechat.material.stats', 'uses'=>'Material@getMaterialStats']);

        $api->post('/wechat/news', [ 'name'=>'创建图文素材', 'middleware'=>'activated', 'as' => 'wechat.material.news.add', 'uses'=>'Material@createNews']);
        $api->get('/wechat/news/{materialId}', ['name'=>'获取图文素材详情', 'middleware'=>'activated', 'as' => 'wechat.material.news.get', 'uses'=>'Material@getNewsMaterial']);
        $api->put('/wechat/news/', [ 'name'=>'修改图文素材','middleware'=>'activated', 'as' => 'wechat.material.news.update', 'uses'=>'Material@updateArticle']);

        $api->get('/wechat/stats/userweeksummary', ['name'=>'最近七天用户数据统', 'middleware'=>'activated', 'as' => 'wechat.stats.userWeekSummary', 'uses'=>'Stats@userWeekSummary']);

        $api->post('/wechat/open', [ 'name'=>'开通开放平台账号并且绑定小程序','middleware'=>'activated', 'as' => 'wechat.user.open', 'uses'=>'Open@openCreate']);
        $api->get('/wechat/offiaccountcodeforever', [ 'name'=>'获取公众号永久二维码','middleware'=>'activated',  'as' => 'wechat.wxa.codeunlimit',  'uses'=>'Wxa@getOffiaccountCodeForever']);
    });

    $api->group(['namespace' => 'WorkWechatBundle\Http\Api\V1\Action', 'middleware' => ['api.auth','shoplog'], 'providers' => 'jwt'], function($api) {
        $api->get('/workwechat/config', ['name'=>'获取企业微信配置','middleware'=>'activated',  'as' => 'workwechat.config.info',  'uses'=>'WorkWechat@getConfig']);
        $api->get('/workwechat/report', ['name'=>'获取企业微信通讯录','middleware'=>'activated',  'as' => 'workwechat.report.info',  'uses'=>'WorkWechat@getReport']);
        $api->get('/workwechat/report/{department_id}', ['name'=>'获取企业微信部门成员列表','middleware'=>'activated',  'as' => 'workwechat.report.userlists',  'uses'=>'WorkWechat@getReportUserLists']);
        $api->post('/workwechat/config', [ 'name'=>'保存企业微信配置', 'middleware'=>'activated', 'as' => 'workwechat.config.save', 'uses'=>'WorkWechat@setConfig']);
        $api->post('/workwechat/report/syncDistributor', [ 'name'=>'同步企微部门信息到店铺', 'middleware'=>'activated', 'as' => 'workwechat.report.distributor.sync', 'uses'=>'WorkWechat@syncDistributor']);
        $api->post('/workwechat/report/syncSalesperson', [ 'name'=>'同步企微部门成员到导购员', 'middleware'=>'activated', 'as' => 'workwechat.report.salesperson.sync', 'uses'=>'WorkWechat@syncSalesperson']);
        $api->get('/workwechat/rellist/{salespersonId}', [ 'name'=>'导购员企业微信关联信息', 'middleware'=>'activated', 'as' => 'workwechat.rel.list', 'uses'=>'WorkWechat@getWorkWechatList']);
        $api->get('/workwechat/rellogs/{userId}', [ 'name'=>'导购员企业微信关联信息日志', 'middleware'=>'activated', 'as' => 'workwechat.rel.log', 'uses'=>'WorkWechat@getWorkWechatLogsList']);
        $api->get('/workwechat/messagetemplate', ['name'=>'企业微信通知模板列表获取','middleware'=>'activated',  'as' => 'workwechat.message.template.list',  'uses'=>'WorkWechatMessageTemplate@getTemplateList']);
        $api->get('/workwechat/messagetemplate/{templateId}', ['name'=>'企业微信通知模板获取','middleware'=>'activated',  'as' => 'workwechat.message.template.get',  'uses'=>'WorkWechatMessageTemplate@getTemplate']);
        $api->put('/workwechat/messagetemplate/{templateId}', ['name'=>'企业微信通知模板保存','middleware'=>'activated',  'as' => 'workwechat.message.template.save',  'uses'=>'WorkWechatMessageTemplate@saveTemplate']);
        $api->put('/workwechat/messagetemplate/open/{templateId}', ['name'=>'企业微信通知模板保存','middleware'=>'activated',  'as' => 'workwechat.message.template.open',  'uses'=>'WorkWechatMessageTemplate@openTemplate']);
        $api->put('/workwechat/messagetemplate/close/{templateId}', ['name'=>'企业微信通知模板保存','middleware'=>'activated',  'as' => 'workwechat.message.template.close',  'uses'=>'WorkWechatMessageTemplate@closeTemplate']);

        $api->post('/workwechat/distributor/js/config', [ 'name'=>'前台获取JsSDK', 'middleware'=>'activated', 'as' => 'workwechat.distributor.js.config', 'uses'=>'WorkWechat@getDistributorJsConfig']);
        $api->post('/workwechat/domain/verify', [ 'name'=>'上传企业微信校验域名文件', 'middleware'=>'activated', 'as' => 'workwechat.domain.verify', 'uses'=>'WorkWechat@verifyDomain']);
    });

    $api->group(['namespace' => 'WorkWechatBundle\Http\Controllers'], function($api) {
        $api->any('/workwechat/notify/{corpid}', ['name'=>'企业微信回调','middleware'=>'activated',  'as' => 'workwechat.notify',  'uses'=>'WorkWechatCallback@notify']);
        $api->any('/workwechat/customer/notify/{corpid}', ['name'=>'企业微信客户联系回调','middleware'=>'activated',  'as' => 'workwechat.customer.notify',  'uses'=>'WorkWechatCallback@customerNotify']);
        $api->any('/workwechat/report/notify/{corpid}', ['name'=>'企业微信通讯录回调','middleware'=>'activated',  'as' => 'workwechat.report.notify',  'uses'=>'WorkWechatCallback@reportNotify']);
    });
});

