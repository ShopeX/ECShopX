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
        $api->get('/wxa/authorizer', [ 'name'=>'获取授权小程序列表', 'middleware'=>'activated',  'as' => 'wechat.wxa.authorizer',  'uses'=>'Wxa@getWxaList']);

        $api->get('/wxa/gettemplateweapplist', [ 'name'=>'获取小程序模版列表', 'middleware'=>'activated',  'as' => 'wechat.wxa.template.list',  'uses'=>'Wxa@getTemplateWeappList']);
        $api->get('/wxa/gettemplateweappdetail', [ 'name'=>'获取小程序模版详情', 'middleware'=>'activated',  'as' => 'wechat.wxa.template.detail',  'uses'=>'Wxa@getTemplateWeappDetail']);

        $api->post('/wxa', [ 'name'=>'上架小程序审核', 'middleware'=>'activated',  'as' => 'wechat.wxa.create',  'uses'=>'Wxa@uploadWxa']);
        $api->get('/wxa/codeunlimit', [ 'name'=>'上传小程序码base64图片','middleware'=>'activated',  'as' => 'wechat.wxa.codeunlimit',  'uses'=>'Wxa@uploadWxaCodeUnlimit']);
        $api->get('/wxa/testqrcode', [ 'name'=>'小程序码体验二维码base64图片', 'middleware'=>'activated',  'as' => 'wechat.wxa.testqrcode',  'uses'=>'Wxa@getTestQrcode']);
        $api->post('/wxa/tryrelease', [ 'name'=>'根据小程序审核状态尝试发布','middleware'=>'activated',  'as' => 'wechat.wxa.tryrelease',  'uses'=>'Wxa@tryRelease']);
        $api->get('/wxa/undocodeaudit', [ 'name'=>'小程序审核撤回', 'middleware'=>'activated',  'as' => 'wechat.wxa.undocodeaudit',  'uses'=>'Wxa@undocodeaudit']);
        $api->get('/wxa/revertcoderelease', [ 'name'=>'回退版本', 'middleware'=>'activated',  'as' => 'wechat.wxa.revertcoderelease',  'uses'=>'Wxa@revertcoderelease']);
        $api->post('/wxa/pageparams/setting', [ 'name'=>'保存小程序页面单个挂件配置信息','middleware'=>'activated',  'as' => 'wechat.wxa.pageparams.setting.set',  'uses'=>'Wxa@setPageParams']);
        $api->get('/wxa/pageparams/setting', ['name'=>'获取小程序页面配置信息', 'middleware'=>'activated',  'as' => 'wechat.wxa.pageparams.setting.get',  'uses'=>'Wxa@getParamByTempName']);
        $api->put('/wxa/pageparams/setting', [ 'name'=>'更新小程序页面单个配置信息','middleware'=>'activated',  'as' => 'wechat.wxa.pageparams.setting.update',  'uses'=>'Wxa@updateParamsById']);

        $api->post('/wxa/pageparams/setting_all', ['name'=>'保存小程序页面配置信息', 'middleware'=>'activated',  'as' => 'wechat.wxa.pageparams.setting.all.set',  'uses'=>'Wxa@savePageAllParams']);

        $api->get('/wxa/{wxaAppId}', ['name'=>'获取授权小程序详情', 'middleware'=>'activated',  'as' => 'wechat.wxa.detail',  'uses'=>'Wxa@getWxaDetail']);

        $api->get('/wxa/templates/openlist', ['name'=>'获取已有模版列表', 'middleware'=>'activated',  'as' => 'wechat.wxa.opentemplate.list',  'uses'=>'Wxa@getOpenTemplateList']);
        $api->get('/wxa/templates/list', [ 'name'=>'获取模版列表','middleware'=>'activated',  'as' => 'wechat.wxa.template.list',  'uses'=>'Wxa@getTemplateList']);
        $api->post('/wxa/templates/open', [ 'name'=>'开通模版','middleware'=>'activated',  'as' => 'wechat.wxa.opentemplate',  'uses'=>'Wxa@openTemplate']);

        $api->get('/wxa/templates/weappid', ['name'=>'获取已有模版列表', 'middleware'=>'activated',  'as' => 'wechat.wxa.weappid',  'uses'=>'Wxa@getWeappId']);

        //小程序数据分析接口
        $api->post('/wxa/stats/summarybydate', ['name'=>'某天概况趋势',  'middleware'=>'activated',  'as' => 'wechat.wxa.stats.summarytrend',  'uses'=>'WxappStats@getSummaryByDate']);
        $api->post('/wxa/stats/summarytrend', ['name'=>'小程序概况趋势', 'middleware'=>'activated',  'as' => 'wechat.wxa.stats.summarytrend',  'uses'=>'WxappStats@getSummaryTrend']);
        $api->post('/wxa/stats/visitpage', [ 'name'=>'小程序访问页面','middleware'=>'activated',  'as' => 'wechat.wxa.stats.visitpage',  'uses'=>'WxappStats@getVisitPage']);
        $api->post('/wxa/stats/visittrend', ['name'=>'小程序访问趋势', 'middleware'=>'activated',  'as' => 'wechat.wxa.stats.visittrend',  'uses'=>'WxappStats@getVisitTrend']);
        $api->post('/wxa/stats/visitdistribution', [  'name'=>'小程序访问分布','middleware'=>'activated',  'as' => 'wechat.wxa.stats.visitdistribution',  'uses'=>'WxappStats@getVisitDistribution']);
        $api->post('/wxa/stats/retaininfo', [ 'name'=>'小程序访问留存','middleware'=>'activated',  'as' => 'wechat.wxa.stats.retaininfo',  'uses'=>'WxappStats@getRetaininfo']);
        $api->post('/wxa/stats/userportrait', [ 'name'=>'小程序用户画像', 'middleware'=>'activated',  'as' => 'wechat.wxa.stats.userportrait',  'uses'=>'WxappStats@getUserPortrait']);

        $api->post('/wxa/customizepage', [ 'name'=>'增加小程序自定义页面','middleware'=>'activated', 'as' => 'wechat.wxa.customizepage.add',  'uses'=>'CustomizePage@createCustomizePage']);
        $api->put('/wxa/customizepage/{id}', [ 'name'=>'更新小程序自定义页面', 'middleware'=>'activated',  'as' => 'wechat.wxa.customizepage.update',  'uses'=>'CustomizePage@updateCustomizePage']);
        $api->delete('/wxa/customizepage/{id}', [ 'name'=>'删除小程序自定义页面', 'middleware'=>'activated',  'as' => 'wechat.wxa.customizepage.delete',  'uses'=>'CustomizePage@deleteCustomizePage']);
        $api->get('/wxa/customizepage/list', ['name'=>'小程序自定义页面列表','middleware'=>'activated','as' => 'wechat.wxa.customizepage.list', 'uses'=>'CustomizePage@getCustomizepageList']);
        $api->get('/wxa/customizepage/{id}', ['name'=>'获取小程序自定义页面详情','middleware'=>'activated','as' => 'wechat.wxa.customizepage.info', 'uses'=>'CustomizePage@getCustomizePageInfo']);
        $api->get('/wxa/salesperson/customizepage', [ 'name'=>'获取导购货架首页','middleware'=>'activated', 'as' => 'wechat.wxa.salesperson.customizepage.get',  'uses'=>'CustomizePage@getSalespersonCustomizePage']);


        $api->put('/wxa/config/{wxaAppId}', ['name' => '小程序配置', 'middleware' => 'activated', 'as' => 'wechat.wxa.config', 'uses' => 'Wxa@saveConfig']);
        $api->put('/wxappTemplate/wxapp', ['name' => '微信模板编辑', 'middleware' => 'activated', 'as' => 'wechat.wxappTemplate.wxapp', 'uses' => 'wxappTemplate@updateWxappTemplate']);
        $api->put('/wxappTemplate/domain', ['name' => '设置小程序需要用到的域名(全局)', 'middleware' => 'activated', 'as' => 'wechat.wxappTemplate.domain', 'uses' => 'wxappTemplate@setDomain']);
        $api->get('/wxappTemplate/domain', ['name' => '获取小程序需要用到的域名', 'middleware' => 'activated', 'as' => 'wechat.wxappTemplate.domain', 'uses' => 'wxappTemplate@getDomain']);

        $api->post('/wxa/onlycode', [ 'name'=>'仅上传小程序代码', 'middleware'=>'activated',  'as' => 'wechat.wxa.onlycode',  'uses'=>'Wxa@commitTempCode']);
        $api->get('/wxa/config/{wxaAppId}', ['name' => '获取授权上架配置', 'middleware' => 'activated', 'as' => 'wechat.wxa.config.detail', 'uses' => 'Wxa@getConfigDetail']);
        $api->post('/wxa/submitreview', [ 'name'=>'提交小程序并审核', 'middleware'=>'activated',  'as' => 'wechat.wxa.submitreview',  'uses'=>'Wxa@submitReview']);

        $api->post('/wxa/getdomainlist', [ 'name'=>'获取小程序域名', 'middleware'=>'activated',  'as' => 'wechat.wxa.getdomainlist',  'uses'=>'Wxa@getDomainList']);
        $api->post('/wxa/savedomain', [ 'name'=>'保存小程序域名', 'middleware'=>'activated',  'as' => 'wechat.wxa.saveDomain',  'uses'=>'Wxa@saveDomain']);

        $api->post('/wxa/cartremind/setting', [ 'name'=>'保存小程序购物车提醒配置','middleware'=>'activated',  'as' => 'wechat.wxa.cartremind.setting.set',  'uses'=>'Wxa@setCartremindSetting']);
        $api->get('/wxa/cartremind/setting', ['name'=>'获取小程序购物车提醒配置', 'middleware'=>'activated',  'as' => 'wechat.wxa.cartremind.setting.get',  'uses'=>'Wxa@getCartremindSetting']);
        // 小程序用户隐私保护指引
        $api->get('/wxa/privacy/setting', ['name'=>'查询小程序用户隐私保护指引', 'middleware'=>'activated',  'as' => 'wechat.wxa.privacy.setting.get',  'uses'=>'Wxa@getPrivacySetting']);
        $api->post('/wxa/privacy/setting', ['name'=>'设置小程序用户隐私保护指引', 'middleware'=>'activated',  'as' => 'wechat.wxa.privacy.setting.set',  'uses'=>'Wxa@setPrivacySetting']);
        $api->post('/wxa/uploadprivacy/extfile', ['name'=>'上传小程序用户隐私保护指引文件', 'middleware'=>'activated',  'as' => 'wechat.wxa.privacy.extfile.upload',  'uses'=>'Wxa@uploadPrivacyExtFile']);

    });
});
