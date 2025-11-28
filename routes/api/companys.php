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
    $api->group(['namespace' => 'CompanysBundle\Http\Api\V1\Action', 'middleware' => 'token'], function($api) {
        $api->get('/operator/credential', ['name' => '登陆获取证书','as' => 'operator.credentials', 'uses' => 'Operators@getCredentials']);
        $api->get('/operator/basic', ['name'=>'获取账号基本信息','as' => 'operator.basic', 'uses' => 'Operators@getBasicUserById']);
    });

    $api->group(['namespace' => 'CompanysBundle\Http\Api\V1\Action'], function($api) {
        $api->get('/operator/images/code', ['name' =>'获取图片验证码','as' => 'operator.images.code', 'uses' => 'Operators@getImageVcode']);
        $api->post('/operator/sms/code', ['name' => '获取手机短信验证码','as' => 'operator.images.code', 'uses' => 'Operators@getSmsCode']);
        $api->post('/operator/resetpassword', ['name' => '重置密码','as' => 'operator.reset.password', 'uses' => 'Operators@resetPassword']);
    });

    $api->group(['namespace' => 'CompanysBundle\Http\Api\V1\Action'], function($api) {
        // app相关接口
        $api->get('/operator/app/image/code', ['name' => '获取图片验证码','as' => 'operator.app.image.code', 'uses' => 'Operators@getAppImageVcode']);
        $api->post('/operator/app/sms/code', ['name' => '发送手机短信验证码','as' => 'operator.app.sms.code', 'uses' => 'Operators@getAppSmsCode']);
    });

    // 企业相关信息
    $api->group(['namespace' => 'CompanysBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'shoplog'], 'providers' => 'jwt'], function($api) {
        $api->post('/company/activate', ['name' => '系统激活', 'as' => 'company.active', 'uses'=>'Companys@active']);
        $api->get('/company/activate', ['name' => '获取激活信息', 'as' => 'company.activate.info', 'uses'=>'Companys@getActivateInfo']);
        $api->get('/company/applications', ['name' => '获取授权应用', 'as' => 'company.application.list', 'uses'=>'Companys@getApplications']);
    });

    $api->group(['namespace' => 'CompanysBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function($api) {
        $api->get('/company/resources', ['name' => '获取当前可用资源包列表', 'as' => 'company.resources', 'uses'=>'Companys@getResourceList']);
        $api->get('/companys/setting', ['name' => '获取商品配置信息', 'as' => 'companys.setting', 'uses'=>'Companys@getCompanySetting']);
        
        $api->post('/company/privacy_setting', ['name' => '隐私更新', 'as' => 'companys.setting.setp', 'uses'=>'Companys@setPrivacySetting']);
        $api->get('/company/privacy_setting', ['name' => '隐私获取', 'as' => 'companys.setting.getp', 'uses'=>'Companys@getPrivacySetting']);

        $api->get('/company/domain_setting', ['name' => '获取域名配置信息', 'as' => 'companys.domain_setting.get', 'uses'=>'Companys@getDomainSetting']);
        $api->post('/company/domain_setting', ['name' => '保存域名配置信息', 'as' => 'companys.domain_setting.set', 'uses'=>'Companys@setDomainSetting']);

        $api->post('/company/setting', ['name' => '设置当前企业的基础设置', 'as' => 'company.setting.set', 'uses'=>'Setting@setSetting']);
        $api->get('/company/setting', ['name' => '获取当前企业的基础设置', 'as' => 'company.setting.get', 'uses'=>'Setting@getSetting']);
        $api->post('/share/setting', ['name' => '设置分享设置', 'as' => 'share.setting.set', 'uses'=>'Setting@setShareSetting']);
        $api->get('/share/setting', ['name' => '获取分享设置', 'as' => 'share.setting.get', 'uses'=>'Setting@getShareSetting']);
        $api->post('/setting/selfdelivery', ['name' => '配置固定的自提地址', 'as' => 'company.selfdelivery.set.address', 'uses'=>'Setting@setSelfdeliveryAddress']);
        $api->get('/setting/selfdelivery', ['name' => '配置固定的自提地址', 'as' => 'company.selfdelivery.get.address', 'uses'=>'Setting@getSelfdeliveryAddress']);

        $api->get('/company/operatorlogs', ['name' => '操作日志', 'as' => 'company.get.operatorlogs', 'uses'=>'Operators@getCompanysLogs']);

        //修改当前登录账号的用户名和头像
        $api->put('/operator/updatedata', ["name" => '更改用户名和头像','as' => 'operator.update.data', 'uses' => 'Operators@updateUserData']);
        $api->post('/operator/select/distributor', ['name'=>'店铺端选择店铺', 'as' => 'operator.select.distributor', 'uses' => 'Operators@shopLoginSelectShopId']);
        $api->put('/operator/changestatus', ['name'=>'修改账号状态', 'as' => 'operator.status.change', 'uses' => 'Operators@changeOperatorStatus']);

        //配置外部链接
        $api->post('/setting/weburl', ['name' => '配置外部链接', 'as' => 'company.setting.weburl.set', 'uses'=>'Setting@saveWebUrlSetting']);
        $api->get('/setting/weburl', ['name' => '获取配置外部链接', 'as' => 'company.setting.weburl.get', 'uses'=>'Setting@getWebUrlSetting']);

        $api->get('/traderate/setting', [ 'name'=>'设置评价状态','middleware'=>'activated', 'as' => 'trade.rate.setting.get', 'uses'=>'Setting@rateSetting']);
        $api->post('/traderate/setting', [ 'name'=>'设置评价状态','middleware'=>'activated', 'as' => 'trade.rate.setting.set', 'uses'=>'Setting@rateSetting']);

        $api->get('/member/whitelist/setting', [ 'name'=>'获取白名单设置状态','middleware'=>'activated', 'as' => 'member.whitelist.setting.get', 'uses'=>'Setting@whitelistSetting']);
        $api->post('/member/whitelist/setting', [ 'name'=>'设置白名单状态','middleware'=>'activated', 'as' => 'member.whitelist.setting.set', 'uses'=>'Setting@whitelistSetting']);

        // 预售提货码 开启
        $api->get('/pickupcode/setting', [ 'name'=>'设置预售提货码状态','middleware'=>'activated', 'as' => 'presale.pickupcode.setting.get', 'uses'=>'Setting@pickupcodeSetting']);
        $api->post('/pickupcode/setting', [ 'name'=>'设置预售提货码状态','middleware'=>'activated', 'as' => 'presale.pickupcode.setting.set', 'uses'=>'Setting@pickupcodeSetting']);

        $api->post('/ydleads/create', [ 'name'=>'云店留资创建','middleware'=>'activated', 'as' => 'companys.ydleads.create', 'uses'=>'Operators@createYdleads']);

        $api->get('/gift/setting', [ 'name'=>'赠品相关设置','middleware'=>'activated', 'as' => 'trade.gift.setting.get', 'uses'=>'Setting@getGiftSetting']);
        $api->post('/gift/setting', [ 'name'=>'赠品相关设置','middleware'=>'activated', 'as' => 'trade.gift.setting.set', 'uses'=>'Setting@setGiftSetting']);
        $api->get('/sendoms/setting', [ 'name'=>'推oms相关设置','middleware'=>'activated', 'as' => 'trade.sendoms.setting.get', 'uses'=>'Setting@getSendOmsSetting']);
        $api->post('/sendoms/setting', [ 'name'=>'推oms相关设置','middleware'=>'activated', 'as' => 'trade.sendoms.setting.set', 'uses'=>'Setting@setSendOmsSetting']);

        // 用于关闭前端店铺切换功能
        $api->get('/nostores/setting', [ 'name'=>'获取前端店铺展示开关','middleware'=>'activated', 'as' => 'nostores.setting.get', 'uses'=>'Setting@getNostoresSetting']);
        $api->post('/nostores/setting', [ 'name'=>'设置前端店铺展示开关','middleware'=>'activated', 'as' => 'nostores.setting.set', 'uses'=>'Setting@setNostoresSetting']);

        // 储值功能 开关
        $api->get('/recharge/setting', [ 'name'=>'设置储值功能状态','middleware'=>'activated', 'as' => 'presale.recharge.setting.get', 'uses'=>'Setting@rechargeSetting']);
        $api->post('/recharge/setting', [ 'name'=>'设置储值功能状态','middleware'=>'activated', 'as' => 'presale.recharge.setting.set', 'uses'=>'Setting@rechargeSetting']);

        // 商品详情库存显示 开关
        $api->get('/itemStore/setting', [ 'name'=>'获取商品库存显示状态','middleware'=>'activated', 'as' => 'item.store.setting.get', 'uses'=>'Setting@itemStoreSetting']);
        $api->post('/itemStore/setting', [ 'name'=>'设置商品库存显示状态','middleware'=>'activated', 'as' => 'item.store.setting.set', 'uses'=>'Setting@itemStoreSetting']);

        // 商品销量显示 开关
        $api->get('/itemSales/setting', [ 'name'=>'获取商品销量显示状态','middleware'=>'activated', 'as' => 'item.store.setting.get', 'uses'=>'Setting@itemSalesSetting']);
        $api->post('/itemSales/setting', [ 'name'=>'设置商品销量显示状态','middleware'=>'activated', 'as' => 'item.store.setting.set', 'uses'=>'Setting@itemSalesSetting']);

        // 结算页发票选项 开关
        $api->get('/invoice/setting', [ 'name'=>'获取发票选项显示状态','middleware'=>'activated', 'as' => 'item.store.setting.get', 'uses'=>'Setting@invoiceSetting']);
        $api->post('/invoice/setting', [ 'name'=>'设置发票选项显示状态','middleware'=>'activated', 'as' => 'item.store.setting.set', 'uses'=>'Setting@invoiceSetting']);

        // 商品分享设置
        $api->get('/itemshare/setting', [ 'name'=>'获取商品分享设置','middleware'=>'activated', 'as' => 'item.share.setting.get', 'uses'=>'Setting@getItemShareSetting']);
        $api->post('/itemshare/setting', [ 'name'=>'保存商品分享设置','middleware'=>'activated', 'as' => 'item.share.setting.save', 'uses'=>'Setting@saveItemShareSetting']);

        // 小程序分享参数设置
        $api->get('/shareParameters/setting', [ 'name'=>'获取小程序分享参数设置','middleware'=>'activated', 'as' => 'share.parameters.setting.get', 'uses'=>'Setting@getShareParametersSetting']);
        $api->post('/shareParameters/setting', [ 'name'=>'保存小程序分享参数设置','middleware'=>'activated', 'as' => 'share.parameters.setting.save', 'uses'=>'Setting@saveShareParametersSetting']);

        // 店务端设置
        $api->get('/dianwu/setting', [ 'name'=>'获取店务端设置','middleware'=>'activated', 'as' => 'item.dianwu.setting.get', 'uses'=>'Setting@getDianwuSetting']);
        $api->post('/dianwu/setting', [ 'name'=>'保存店务端设置','middleware'=>'activated', 'as' => 'item.dianwu.setting.save', 'uses'=>'Setting@saveDianwuSetting']);

        // 商品价格显示 开关
        $api->get('/itemPrice/setting', [ 'name'=>'获取商品价格显示配置','middleware'=>'activated', 'as' => 'item.price.setting.get', 'uses'=>'Setting@getItemPriceSetting']);
        $api->post('/itemPrice/setting', [ 'name'=>'保存商品价格显示配置','middleware'=>'activated', 'as' => 'item.price.setting.set', 'uses'=>'Setting@saveItemPriceSetting']);
        $api->get('/categoryPage/setting', [ 'name'=>'获取分类页页面风格设置','middleware'=>'activated', 'as' => 'category.page.setting.get', 'uses'=>'Setting@getCategoryPageSetting']);
        $api->post('/categoryPage/setting', [ 'name'=>'保存分类页页面风格设置','middleware'=>'activated', 'as' => 'category.page.setting.set', 'uses'=>'Setting@saveCategoryPageSetting']);

        $api->post('/pharmaIndustry/setting', [ 'name'=>'保存医药行业设置','middleware'=>'activated', 'as' => 'pharma.industry.setting.set', 'uses'=>'Setting@saveMedicineSetting']);
        //全部设置
        $api->get('/settings', [ 'name'=>'获取全部配置配置','middleware'=>'activated', 'as' => 'all.setting.get', 'uses'=>'Setting@getAllSetting']);
        //保存全局白名单
        $api->post('/openDivided/setting', [ 'name'=>'保存店铺隔离白名单','middleware'=>'activated', 'as' => 'distributor.white.set', 'uses'=>'Setting@openDistributorDividedSetting']);

        // 自营商品起订量设置
        $api->get('/items/startNum/setting', [ 'name'=>'设置商品起订量','middleware'=>'activated', 'as' => 'item.startnum.setting.get', 'uses'=>'Setting@itemStartNumSetting']);
        $api->post('/items/startNum/setting', [ 'name'=>'设置商品起订量','middleware'=>'activated', 'as' => 'item.startnum.setting.set', 'uses'=>'Setting@itemStartNumSetting']);
        
        // 供应商商品起订量设置
        $api->get('/supplierItems/startNum/setting', [ 'name'=>'设置供应商商品起订量','middleware'=>'activated', 'as' => 'supplieritem.startnum.setting.get', 'uses'=>'Setting@supplirItemStartNumSetting']);
        $api->post('/supplierItems/startNum/setting', [ 'name'=>'设置供应商商品起订量','middleware'=>'activated', 'as' => 'supplieritem.startnum.setting.set', 'uses'=>'Setting@supplirItemStartNumSetting']);

        // 邮件配置设置
        $api->get('/mail/setting', [ 'name'=>'获取邮件配置','middleware'=>'activated', 'as' => 'mail.setting.get', 'uses'=>'Setting@getMailSetting']);
        $api->post('/mail/setting', [ 'name'=>'保存邮件配置','middleware'=>'activated', 'as' => 'mail.setting.set', 'uses'=>'Setting@saveMailSetting']);

    });

    $api->group(['namespace' => 'CompanysBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function($api) {
        $api->post('/shops/wxshops', ['name' => '添加微信门店', 'as' => 'shops.create', 'uses'=>'Shops@createWxShops']);

        $api->get('/shops/wxshops/sync', ['name' => '同步微信门店到本地', 'as' => 'shops.sync', 'uses'=>'Shops@syncWxShops']);
        $api->get('/shops/wxshops/setting', ['name' => '配置门店通用配置信息', 'as' => 'shops.setting.get', 'uses'=>'Shops@getWxShopsSetting']);
        $api->put('/shops/wxshops/setting', ['name' => '获取门店通用配置信息', 'as' => 'shops.setting.set', 'uses'=>'Shops@setWxShopsSetting']);

        $api->get('/shops/wxshops', ['name' => '获取微信门店列表', 'as' => 'shops.lists', 'uses'=>'Shops@getWxShopsList']);
        $api->post('/shops/wxshops/setDefaultShop', ['name' => '设置默认门店', 'as' => 'shops.defaultshop.set', 'uses'=>'Shops@setDefaultShop']);
        $api->post('/shops/wxshops/setShopResource', ['name' => '激活门店', 'as' => 'shops.shopresource.set', 'uses'=>'Shops@setResource']);
        $api->get('/shops/wxshops/{wx_shop_id}', ['name' => '获取单个微信门店详情', 'as' => 'shops.detail', 'uses'=>'Shops@getWxShopsDetail']);
        $api->delete('/shops/wxshops/{wx_shop_id}', ['name' => '删除微信门店', 'as' => 'shops.delete', 'uses'=>'Shops@deleteWxShops']);
        $api->put('/shops/wxshops/{wx_shop_id}', ['name' => '更新微信门店', 'as' => 'shops.update', 'uses'=>'Shops@updateWxShops']);

        $api->post('/shops/wxshops/setShopStatus', ['name' => '设置门店状态', 'as' => 'shops.status.set', 'uses'=>'Shops@setShopStatus']);

        $api->patch('/company', ['name' => '更新企业信息', 'as' => 'company.update', 'uses'=>'Companys@updateCompanyInfo']);
        $api->put('/shops/protocol', ['name' => '更新协议信息', 'as' => 'shops.protocol.put', 'uses'=>'ProtocolController@set']);
        $api->get('/shops/protocol', ['name' => '获取协议信息', 'as' => 'shops.protocol.get', 'uses'=>'ProtocolController@get']);
    });

    //文章相关接口
    $api->group(['namespace' => 'CompanysBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function($api) {
        $api->post('/article/management', ['name' => '创建文章', 'as' => 'article.create', 'uses'=>'ArticleController@createDataArticle']);
        $api->put('/article/management/{article_id}', ['name' => '更新文章', 'as' => 'article.update', 'uses'=>'ArticleController@updateDataArticle']);
        $api->delete('/article/management/{article_id}', ['name' => '删除文章', 'as' => 'article.delete', 'uses'=>'ArticleController@deleteDataArticle']);
        $api->get('/article/management', ['name' => '获取文章列表', 'as' => 'article.list', 'uses'=>'ArticleController@listDataArticle']);
        $api->get('/article/management/{article_id}', ['name' => '获取文章详情', 'as' => 'article.info', 'uses'=>'ArticleController@infoDataArticle']);
        $api->put('/article/updatestatusorsort', ['name' => '获取文章详情', 'as' => 'article.update.sortstatus', 'uses'=>'ArticleController@updateArticleStatusOrSort']);

        $api->post('/article/category', ['name' => '创建文章栏目', 'as' => 'article.create.category', 'uses'=>'ArticleCategory@createData']);
        $api->get('/article/category', ['name' => '获取文章栏目列表', 'as' => 'article.list.category', 'uses'=>'ArticleCategory@getCategory']);
        $api->get('/article/category/{category_id}', ['name' => '获取单条文章栏目', 'as' => 'article.info.category', 'uses'=>'ArticleCategory@getCategory']);
        $api->put('/article/category/{category_id}', ['name' => '更新单条文章栏目', 'as' => 'article.update.category', 'uses'=>'ArticleCategory@updateCategory']);
        $api->delete('/article/category/{category_id}', ['name' => '删除文章栏目', 'as' => 'article.delete.category', 'uses'=>'ArticleCategory@deleteCategory']);
    });

    //货币相关接口
    $api->group(['namespace' => 'CompanysBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function($api) {
        $api->post('/currency', ['name' => '货币信息新增', 'as' => 'currency.create', 'uses'=>'CurrencyController@createData']);
        $api->delete('/currency/{id}', ['name' => '删除企业员工', 'as' => 'currency.delete', 'uses'=>'CurrencyController@deleteData']);
        $api->put('/currency/{id}', ['name' => '更新企业员工', 'as' => 'currency.update', 'uses'=>'CurrencyController@updateData']);
        $api->get('/currency/{id}', ['name' => '获取货币详情', 'as' => 'currency.info', 'uses'=>'CurrencyController@getDataInfo']);
        $api->get('/currency', ['name' => '获取货币列表信息', 'as' => 'currency.list', 'uses'=>'CurrencyController@getDataList']);
        $api->put('/currencySetDefault/{id}', ['name' => '设置默认货币', 'as' => 'currency.set.default', 'uses'=>'CurrencyController@setDefaultCurrency']);
        $api->get('/currencyGetDefault', ['name' => '获取默认货币配置', 'as' => 'currency.get.default', 'uses'=>'CurrencyController@getDefaultCurrency']);
    });

    $api->group(['namespace' => 'CompanysBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function($api) {
        $api->post('/account/management', ['name' => '创建企业员工', 'as' => 'account.create', 'uses'=>'EmployeeController@createData']);
        $api->get('/account/management', ['name' => '获取企业员工信息列表', 'middleware' => ['datapass'], 'as' => 'account.list', 'uses'=>'EmployeeController@getListData']);
        $api->get('/account/management/{operator_id}', ['name' => '获取企业员工信息', 'as' => 'account.info', 'uses'=>'EmployeeController@getInfoData']);
        $api->patch('/account/management/{operator_id}', ['name' => '更改企业员工信息', 'as' => 'account.update', 'uses'=>'EmployeeController@updateData']);
        $api->delete('/account/management/{operator_id}', ['name' => '删除企业员工信息', 'as' => 'account.delete', 'uses'=>'EmployeeController@deleteData']);

        $api->post('/roles/management', ['name' => '创建企业员工角色', 'as' => 'roles.create', 'uses'=>'RolesController@createDataRole']);
        $api->get('/roles/management', ['name' => '获取角色列表', 'as' => 'roles.list', 'uses'=>'RolesController@getDataList']);
        $api->get('/roles/management/{role_id}', ['name' => '获取角色详情', 'as' => 'roles.info', 'uses'=>'RolesController@getDataInfo']);
        $api->patch('/roles/management/{role_id}', ['name' => '更新企业员工角色', 'as' => 'roles.update', 'uses'=>'RolesController@updateDataRole']);
        $api->delete('/roles/management/{role_id}', ['name' => '删除角色', 'as' => 'roles.delete', 'uses'=>'RolesController@deleteDataRole']);
    });

    $api->group(['namespace' => 'CompanysBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'shoplog'], 'providers' => 'jwt'], function($api) {
        $api->get('/permission', ['name' => '获取权限详情', 'as' => 'account.roles.permission', 'uses'=>'RolesController@getPermission']);
        $api->get('/operator/getinfo', ['as' => 'operator.get.data', 'uses' => 'Operators@getUserData']);
    });


    $api->group(['namespace' => 'CompanysBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function($api) {
        $api->get('/getStatistics', ['name' => '获取商城订单统计信息', 'as' => 'company.real.statistics', 'uses'=>'StatisticsController@getDataList']);
        $api->get('/getNoticeStatistics', ['name' => '获取商城总量统计(待处理订单数，待处理商品数，进行中的营销活动数)', 'as' => 'company.notice.statistics', 'uses'=>'StatisticsController@getOrderStatusCount']);
    });

    // 地区管理
    $api->group(['namespace' => 'CompanysBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function($api) {
        $api->get('/regionauth', ['name' => '地区权限列表', 'as' => 'company.regionauth.list', 'uses'=>'Regionauth@getlist']);
        $api->get('/regionauth/{id}', ['name' => '地区权限详情', 'as' => 'company.regionauth.info', 'uses'=>'Regionauth@getinfo']);
        $api->post('/regionauth', ['name' => '地区权限添加', 'as' => 'company.regionauth.add', 'uses'=>'Regionauth@add']);
        $api->put('/regionauth/{id}', ['name' => '地区权限修改', 'as' => 'company.regionauth.update', 'uses'=>'Regionauth@update']);
        $api->delete('/regionauth/{id}', ['name' => '地区权限删除', 'as' => 'company.regionauth.dell', 'uses'=>'Regionauth@del']);
        $api->put('/regionauth/enable/{id}', ['name' => '状态操作', 'as' => 'company.regionauth.enable', 'uses'=>'Regionauth@enable']);
    });

    //外部小程序配置相关接口
    $api->group(['namespace' => 'CompanysBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function($api) {
        $api->get('/wxexternalconfig/list', ['name' => '获取外部小程序配置列表', 'as' => 'wxexternalconfig.list', 'uses'=>'WxExternalConfigController@getWxExternalConfigList']);
        $api->post('/wxexternalconfig/create', ['name' => '创建外部小程序配置', 'as' => 'wxexternalconfig.create', 'uses'=>'WxExternalConfigController@createWxExternalConfig']);
        $api->put('/wxexternalconfig/update/{wx_external_config_id}', ['name' => '更新外部小程序配置', 'as' => 'wxexternalconfig.update', 'uses'=>'WxExternalConfigController@updateWxExternalConfig']);

        $api->delete('/wxexternalconfig/{wx_external_config_id}', ['name' => '删除外部小程序配置', 'as' => 'wxexternalconfig.delete', 'uses'=>'WxExternalConfigController@deleteWxExternalConfig']);

        $api->get('/wxexternalroutes/list', ['name' => '获取外部小程序路径列表', 'as' => 'wxexternalroutes.list', 'uses'=>'WxExternalRoutesController@getwxexternalroutesList']);
        $api->post('/wxexternalroutes/create', ['name' => '创建外部小程序路径', 'as' => 'wxexternalroutes.create', 'uses'=>'WxExternalRoutesController@createwxexternalroutes']);
        $api->put('/wxexternalroutes/update/{wx_external_config_id}', ['name' => '更新外部小程序路径', 'as' => 'wxexternalroutes.update', 'uses'=>'WxExternalRoutesController@updatewxexternalroutes']);
        $api->delete('/wxexternalroutes/{wx_external_config_id}', ['name' => '删除外部小程序路径', 'as' => 'wxexternalroutes.delete', 'uses'=>'WxExternalRoutesController@deleteWxExternalRoutes']);
        $api->get('/wxexternalconfigroutes/list', ['name' => '获取外部小程序配置路径列表', 'as' => 'wxexternalconfigroutes.list', 'uses'=>'WxExternalConfigController@getConfigRoutesList']);
    });

    // 数据敏感权限相关接口
    $api->group(['namespace' => 'CompanysBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function($api) {
        $api->post('/datapass', ['name' => '申请查看敏感数据', 'as' => 'companys.datapass.apply', 'uses' => 'Operators@applyDataPass']);
        $api->put('/datapass/apply/{id}', ['name' => '敏感数据申请审核', 'as' => 'companys.datapass.approve', 'uses' => 'Operators@approveDataPass']);
        $api->put('/datapass/open/{id}', ['name' => '敏感数据申请开启', 'as' => 'companys.datapass.approve', 'uses' => 'Operators@approveDataPass']);
        $api->put('/datapass/close/{id}', ['name' => '敏感数据申请关闭', 'as' => 'companys.datapass.approve', 'uses' => 'Operators@approveDataPass']);

        $api->get('/datapass', ['name' => '查看敏感数据申请列表', 'as' => 'companys.datapass.list', 'uses' => 'Operators@listDataPass']);
        $api->get('/datapass/{id}', ['name' => '查看敏感数据申请详情', 'as' => 'companys.datapass.detail', 'uses' => 'Operators@fetchDataPassDetail']);
        $api->get('/datapasslog', ['name' => '查看敏感数据日志', 'as' => 'companys.datapass.log.list', 'uses' => 'Operators@listDataPassLog']);
    });

    // 移动收银相关
    $api->group(['namespace' => 'CompanysBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated'], 'providers' => 'jwt'], function($api) {
        $api->post('/operator/scancodeAddcart', ['name' => '扫条形码加入购物车', 'as' => 'companys.operator.cartdata.scanadd', 'uses' => 'OperatorCartController@scanCodeSales']);
        $api->post('/operator/cartdataadd', ['name' => '管理员购物车新增', 'as' => 'companys.operator.cartdata.add', 'uses' => 'OperatorCartController@cartDataAdd']);
        $api->post('/operator/cartdataupdate', ['name' => '管理员购物车更新', 'as' => 'companys.operator.cartdata.update', 'uses' => 'OperatorCartController@updateCartData']);
        $api->get('/operator/cartdatalist', ['name' => '获取管理员购物车', 'as' => 'companys.operator.cartdata.list', 'uses' => 'OperatorCartController@getCartDataList']);
        $api->delete('/operator/cartdatadel', ['name' => '管理员购物车删除', 'as' => 'companys.operator.cartdata.del', 'uses' => 'OperatorCartController@delCartData']);
        $api->get('/operator/pending/list', ['name' => '管理员挂单列表', 'as' => 'companys.operator.pending.list', 'uses' => 'OperatorPendingOrderController@listPendingData']);
        $api->post('/operator/cartdata/pending', ['name' => '管理员购物车挂单', 'as' => 'companys.operator.cartdata.pending', 'uses' => 'OperatorPendingOrderController@pendingCartData']);
        $api->post('/operator/order/pending', ['name' => '管理员待支付订单挂单', 'as' => 'companys.operator.order.pending', 'uses' => 'OperatorPendingOrderController@pendingOrderData']);
        $api->post('/operator/pending/fetch', ['name' => '管理员取单', 'as' => 'companys.operator.pending.fetch', 'uses' => 'OperatorPendingOrderController@fetchPendingData']);
        $api->delete('/operator/pending/delete', ['name' => '管理员挂单数据删除', 'as' => 'companys.operator.pending.delete', 'uses' => 'OperatorPendingOrderController@delPendingData']);
    });
});
