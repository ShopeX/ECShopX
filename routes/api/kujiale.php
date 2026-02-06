<?php

/*
|--------------------------------------------------------------------------
| Kujiale API Routes
|--------------------------------------------------------------------------
|
| 酷家乐相关后台管理接口
|
*/

$api->version('v1', function($api) {
    $api->group([
        'namespace' => 'KujialeBundle\Http\Api\V1\Action', 
        'middleware' => ['api.auth', 'activated', 'shoplog'], 
        'providers' => 'jwt'
    ], function($api) {
        // 设计师作品与商品绑定相关接口
        $api->post('/kujiale/designer-works/bind-item', [
            'name' => '绑定设计师作品与商品', 
            'as' => 'kujiale.designer.works.bind.item', 
            'uses' => 'KuController@bindDesignerWorksItem'
        ]);
        
        $api->delete('/kujiale/designer-works/unbind-item', [
            'name' => '解绑设计师作品与商品', 
            'as' => 'kujiale.designer.works.unbind.item', 
            'uses' => 'KuController@unbindDesignerWorksItem'
        ]);
        
        $api->get('/kujiale/designer-works/items', [
            'name' => '查询关联了design的商品列表', 
            'as' => 'kujiale.designer.works.items', 
            'uses' => 'KuController@getDesignerWorksItems'
        ]);
        
        $api->get('/kujiale/designer-works/list', [
            'name' => '获取设计师作品列表', 
            'as' => 'kujiale.designer.works.list', 
            'uses' => 'KuController@getDesignerWorksList'
        ]);
    });
});
