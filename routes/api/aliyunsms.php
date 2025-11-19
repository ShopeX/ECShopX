<?php
$api->version('v1', function($api) {
    $api->group(['prefix' => '/aliyunsms', 'namespace' => 'AliyunsmsBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated','shoplog'], 'providers' => 'jwt'], function($api) {
         $api->get('/config', ['name' => '基础配置', 'middleware' => 'activated', 'as' => 'aliyunsms.config.get', 'uses' => 'Setting@getConfig']);
         $api->post('/config', ['name' => '基础配置', 'middleware' => 'activated', 'as' => 'aliyunsms.config.set', 'uses' => 'Setting@setConfig']);
         $api->get('/status', ['name' => '短信启用/关闭', 'middleware' => 'activated', 'as' => 'aliyunsms.status.get', 'uses' => 'Setting@getStatus']);
         $api->post('/status', ['name' => '短信启用/关闭', 'middleware' => 'activated', 'as' => 'aliyunsms.status.set', 'uses' => 'Setting@setStatus']);

        //签名
        $api->get('/sign/list', ['name' => '签名列表', 'middleware' => 'activated', 'as' => 'aliyunsms.sign.getList', 'uses' => 'Sign@getList']);
        $api->get('/sign/info', ['name' => '签名详情', 'middleware' => 'activated', 'as' => 'aliyunsms.sign.getInfo', 'uses' => 'Sign@getInfo']);
        $api->post('/sign/add', ['name' => '新增签名', 'middleware' => 'activated', 'as' => 'aliyunsms.sign.add', 'uses' => 'Sign@addSign']);
        $api->post('/sign/modify', ['name' => '修改签名', 'middleware' => 'activated', 'as' => 'aliyunsms.sign.modify', 'uses' => 'Sign@modifySign']);
        $api->delete('/sign/delete/{id}', ['name' => '删除签名', 'middleware' => 'activated', 'as' => 'aliyunsms.sign.delete', 'uses' => 'Sign@deleteSign']);

        //模板
         $api->get('/template/list', ['name' => '模板列表', 'middleware' => 'activated', 'as' => 'aliyunsms.tmpl.getList', 'uses' => 'Template@getList']);
         $api->get('/template/info', ['name' => '模板详情', 'middleware' => 'activated', 'as' => 'aliyunsms.tmpl.getInfo', 'uses' => 'Template@getInfo']);
         $api->post('/template/add', ['name' => '新增模板', 'middleware' => 'activated', 'as' => 'aliyunsms.tmpl.add', 'uses' => 'Template@addTemplate']);
         $api->post('/template/modify', ['name' => '修改模板', 'middleware' => 'activated', 'as' => 'aliyunsms.tmpl.modify', 'uses' => 'Template@modifyTemplate']);
        $api->delete('/template/delete/{id}', ['name' => '删除模板', 'middleware' => 'activated', 'as' => 'aliyunsms.tmpl.delete', 'uses' => 'Template@deleteTemplate']);

         //短信场景
        $api->get('/scene/list', ['name' => '场景列表', 'middleware' => 'activated', 'as' => 'aliyunsms.scene.getList', 'uses' => 'Scene@getList']);
        $api->get('/scene/simpleList', ['name' => '模板页下拉场景列表', 'middleware' => 'activated', 'as' => 'aliyunsms.scene.getList', 'uses' => 'Scene@getSimpleList']);
        $api->get('/scene/detail', ['name' => '模板页场景明细', 'middleware' => 'activated', 'as' => 'aliyunsms.scene.getDetail', 'uses' => 'Scene@getDetail']);
        $api->post('/scene/addItem', ['name' => '添加场景实例', 'middleware' => 'activated', 'as' => 'aliyunsms.scene.addItem', 'uses' => 'Scene@addItem']);
        $api->get('/scene/enableItem', ['name' => '启用场景实例', 'middleware' => 'activated', 'as' => 'aliyunsms.scene.enableItem', 'uses' => 'Scene@enableItem']);
        $api->get('/scene/disableItem', ['name' => '停用场景实例', 'middleware' => 'activated', 'as' => 'aliyunsms.scene.disableItem', 'uses' => 'Scene@disableItem']);
        $api->delete('/scene/deleteItem/{id}', ['name' => '移除场景实例', 'middleware' => 'activated', 'as' => 'aliyunsms.scene.deleteItem', 'uses' => 'Scene@deleteItem']);

        //短信记录
        $api->get('/record/list', ['name' => '短信记录列表', 'middleware' => ['activated','datapass'], 'as' => 'aliyunsms.record.getList', 'uses' => 'Record@getList']);

        //群发记录
        $api->post('/task/add', ['name' => '添加群发任务', 'middleware' => 'activated', 'as' => 'aliyunsms.task.add', 'uses' => 'Task@addTask']);
        $api->post('/task/modify', ['name' => '编辑群发任务', 'middleware' => 'activated', 'as' => 'aliyunsms.task.modify', 'uses' => 'Task@modifyTask']);
        $api->get('/task/list', ['name' => '群发任务列表', 'middleware' => 'activated', 'as' => 'aliyunsms.task.list', 'uses' => 'Task@getList']);
        $api->get('/task/info', ['name' => '群发任务详情', 'middleware' => 'activated', 'as' => 'aliyunsms.task.info', 'uses' => 'Task@getInfo']);
        $api->post('/task/revoke', ['name' => '群发任务撤销', 'middleware' => 'activated', 'as' => 'aliyunsms.task.revoke', 'uses' => 'Task@revokeTask']);
    });
});
