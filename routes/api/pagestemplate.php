<?php
$api->version('v1', function ($api) {
    $api->group(['namespace' => 'ThemeBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function ($api) {
        $api->post('/pagestemplate/set', ['name' => '模板展示设置', 'as' => 'pagestemplateset.set', 'uses' => 'PagesTemplateSet@set']);
        $api->get('/pagestemplate/setInfo', ['name' => '模板展示设置信息', 'as' => 'pagestemplateset.getInfo', 'uses' => 'PagesTemplateSet@getInfo']);
        $api->get('/pagestemplate/lists', ['name' => '模板列表', 'as' => 'pagestemplate.lists', 'uses' => 'PagesTemplate@lists']);
        $api->post('/pagestemplate/add', ['name' => '新增模板', 'as' => 'pagestemplate.add', 'uses' => 'PagesTemplate@add']);
        $api->put('/pagestemplate/edit', ['name' => '编辑模板', 'as' => 'pagestemplate.edit', 'uses' => 'PagesTemplate@edit']);
        $api->get('/pagestemplate/detail', ['name' => '模板详情', 'as' => 'pagestemplate.detail', 'uses' => 'PagesTemplate@detail']);
        $api->get('/pagestemplate/widget/items', ['name' => '获取模板组件商品', 'as' => 'pagestemplate.widget.items', 'uses' => 'PagesTemplate@getWidgetItems']);
        $api->post('/pagestemplate/copy', ['name' => '复制模板', 'as' => 'pagestemplate.copy', 'uses' => 'PagesTemplate@copy']);
        $api->delete('/pagestemplate/del/{pages_template_id}', ['name' => '废弃模板', 'as' => 'pagestemplate.delete', 'uses' => 'PagesTemplate@delete']);
        $api->put('/pagestemplate/modifyStatus', ['name' => '模板状态变更', 'as' => 'pagestemplate.modifyStatus', 'uses' => 'PagesTemplate@modifyStatus']);
        $api->put('/pagestemplate/sync', ['name' => '模板同步', 'as' => 'pagestemplate.sync', 'uses' => 'PagesTemplate@sync']);
    });

    // 开屏广告设置
    $api->group(['namespace' => 'ThemeBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function ($api) {
        $api->get('/openscreenad/set', ['name' => '获取设置信息', 'as' => 'openscreenad.set.info', 'uses' => 'OpenScreenAd@getInfo']);
        $api->post('/openscreenad/set', ['name' => '保存设置信息', 'as' => 'openscreenad.set.save', 'uses' => 'OpenScreenAd@Save']);
    });
    
    //pc模板
    $api->group(['namespace' => 'ThemeBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function ($api) {
        $api->get('/pctemplate/lists', ['name' => 'pc模板列表', 'as' => 'pctemplate.lists', 'uses' => 'PcTemplate@lists']);
        $api->post('/pctemplate/add', ['name' => '新增pc模板', 'as' => 'pctemplate.add', 'uses' => 'PcTemplate@add']);
        $api->put('/pctemplate/edit', ['name' => '编辑pc模板', 'as' => 'pctemplate.edit', 'uses' => 'PcTemplate@edit']);
        $api->delete('/pctemplate/delete/{theme_pc_template_id}', ['name' => '删除pc模板', 'as' => 'pctemplate.delete', 'uses' => 'PcTemplate@delete']);
        
        $api->get('/pctemplate/getHeaderOrFooter', ['name' => '获取头部尾部', 'as' => 'pctemplate.getHeaderOrFooter', 'uses' => 'PcTemplate@getHeaderOrFooter']);
        $api->post('/pctemplate/saveHeaderOrFooter', ['name' => '头尾部保存', 'as' => 'pctemplate.saveHeaderOrFooter', 'uses' => 'PcTemplate@saveHeaderOrFooter']);
        $api->get('/pctemplate/getTemplateContent', ['name' => '获取pc模板内容', 'as' => 'pctemplate.getTemplateContent', 'uses' => 'PcTemplate@getTemplateContent']);
        $api->post('/pctemplate/saveTemplateContent', ['name' => '保存pc模板内容', 'as' => 'pctemplate.saveTemplateContent', 'uses' => 'PcTemplate@saveTemplateContent']);

        $api->get('/pctemplate/loginPage/setting', ['name' => '获取pc登录页设置', 'as' => 'pctemplate.getLoginPageSetting', 'uses' => 'PcTemplate@getLoginPageSetting']);
        $api->post('/pctemplate/loginPage/setting', ['name' => '保存pc登录页设置', 'as' => 'pctemplate.saveLoginPageSetting', 'uses' => 'PcTemplate@saveLoginPageSetting']);
    });
    
    //会员中心分享信息设置
    $api->group(['namespace' => 'ThemeBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function ($api) {
        $api->post('/memberCenterShare/set', ['name' => '设置会员中心分享信息', 'as' => 'memberCenterShare.set', 'uses' => 'MemberCenterShare@set']);
        $api->get('/memberCenterShare/getInfo', ['name' => '获取会员中心分享信息', 'as' => 'memberCenterShare.getInfo', 'uses' => 'MemberCenterShare@getInfo']);
    });

    // 侧边栏设置
    $api->group(['namespace' => 'ThemeBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function ($api) {
        $api->get('/sidebar/list', ['name' => '获取侧边栏列表', 'as' => 'pages.sidebar.list', 'uses' => 'PagesSideBar@getList']);
        $api->get('/sidebar/{id}', ['name' => '获取侧边栏详情', 'as' => 'pages.sidebar.getInfo', 'uses' => 'PagesSideBar@getInfo']);
        $api->post('/sidebar', ['name' => '设置侧边栏设置', 'as' => 'pages.sidebar.create', 'uses' => 'PagesSideBar@create']);
        $api->put('/sidebar/{id}', ['name' => '更新侧边栏设置', 'as' => 'pages.sidebar.update', 'uses' => 'PagesSideBar@update']);
        $api->delete('/sidebar/{id}', ['name' => '删除侧边栏设置', 'as' => 'pages.sidebar.delete', 'uses' => 'PagesSideBar@delete']);
    });

    // 广告位设置
    $api->group(['namespace' => 'ThemeBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function ($api) {
        $api->get('/adplace/popup/list', ['name' => '获取广告位列表', 'as' => 'pages.adplace.popup.list', 'uses' => 'PagesAdPlace@getList']);
        $api->get('/adplace/popup/{id}', ['name' => '获取广告位详情', 'as' => 'pages.adplace.popup.getInfo', 'uses' => 'PagesAdPlace@getInfo']);
        $api->post('/adplace/popup', ['name' => '设置广告位', 'as' => 'pages.adplace.popup.create', 'uses' => 'PagesAdPlace@create']);
        $api->put('/adplace/popup/{id}', ['name' => '更新广告位', 'as' => 'pages.adplace.popup.update', 'uses' => 'PagesAdPlace@update']);
        $api->delete('/adplace/popup/{id}', ['name' => '删除广告位', 'as' => 'pages.adplace.popup.delete', 'uses' => 'PagesAdPlace@delete']);
        $api->post('/adplace/popup/submit/{id}', ['name' => '提交审核广告位', 'as' => 'pages.adplace.popup.submit', 'uses' => 'PagesAdPlace@submit']);
        $api->post('/adplace/popup/audit/{id}', ['name' => '审核广告位', 'as' => 'pages.adplace.popup.audit', 'uses' => 'PagesAdPlace@audit']);
        $api->post('/adplace/popup/withdraw/{id}', ['name' => '撤回广告位审核申请', 'as' => 'pages.adplace.popup.withdraw', 'uses' => 'PagesAdPlace@withdraw']);

        $api->get('/adplace/carousel/list', ['name' => '获取广告位列表', 'as' => 'pages.adplace.carousel.list', 'uses' => 'PagesAdPlace@getList']);
        $api->get('/adplace/carousel/{id}', ['name' => '获取广告位详情', 'as' => 'pages.adplace.carousel.getInfo', 'uses' => 'PagesAdPlace@getInfo']);
        $api->post('/adplace/carousel', ['name' => '设置广告位', 'as' => 'pages.adplace.carousel.create', 'uses' => 'PagesAdPlace@create']);
        $api->put('/adplace/carousel/{id}', ['name' => '更新广告位', 'as' => 'pages.adplace.carousel.update', 'uses' => 'PagesAdPlace@update']);
        $api->delete('/adplace/carousel/{id}', ['name' => '删除广告位', 'as' => 'pages.adplace.carousel.delete', 'uses' => 'PagesAdPlace@delete']);
        $api->post('/adplace/carousel/submit/{id}', ['name' => '提交审核广告位', 'as' => 'pages.adplace.carousel.submit', 'uses' => 'PagesAdPlace@submit']);
        $api->post('/adplace/carousel/audit/{id}', ['name' => '审核广告位', 'as' => 'pages.adplace.carousel.audit', 'uses' => 'PagesAdPlace@audit']);
        $api->post('/adplace/carousel/withdraw/{id}', ['name' => '撤回广告位审核申请', 'as' => 'pages.adplace.carousel.withdraw', 'uses' => 'PagesAdPlace@withdraw']);
    });
});
