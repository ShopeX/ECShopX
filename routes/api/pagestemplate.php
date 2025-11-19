<?php
$api->version('v1', function ($api) {
    $api->group(['namespace' => 'ThemeBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function ($api) {
        $api->post('/pagestemplate/set', ['name' => '模板展示设置', 'as' => 'pagestemplateset.set', 'uses' => 'PagesTemplateSet@set']);
        $api->get('/pagestemplate/setInfo', ['name' => '模板展示设置信息', 'as' => 'pagestemplateset.getInfo', 'uses' => 'PagesTemplateSet@getInfo']);
        $api->get('/pagestemplate/lists', ['name' => '模板列表', 'as' => 'pagestemplate.lists', 'uses' => 'PagesTemplate@lists']);
        $api->post('/pagestemplate/add', ['name' => '新增模板', 'as' => 'pagestemplate.add', 'uses' => 'PagesTemplate@add']);
        $api->put('/pagestemplate/edit', ['name' => '编辑模板', 'as' => 'pagestemplate.edit', 'uses' => 'PagesTemplate@edit']);
        $api->get('/pagestemplate/detail', ['name' => '模板详情', 'as' => 'pagestemplate.detail', 'uses' => 'PagesTemplate@detail']);
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
});
