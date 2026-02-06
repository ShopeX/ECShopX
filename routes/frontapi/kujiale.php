<?php
$api->version('v1', function($api) {
    $api->group(['namespace' => 'KujialeBundle\Http\FrontApi\V1\Action', 'middleware' => 'frontnoauth:h5app', 'prefix' => 'h5app', 'providers' => 'jwt'], function($api) {
        $api->post('/wxapp/kujiale/desginList', ['name' => '设计师方案列表', 'as' => 'kujiale.desgin.list', 'uses' => 'KuController@desginWorkList']);
        $api->get('/wxapp/kujiale/desginTagsList', ['name' => '方案标签列表', 'as' => 'kujiale.tags.list', 'uses' => 'KuController@getDesginTagsList']);
        $api->post('/wxapp/kujiale/desginDetail', ['name' => '设计师方案详情', 'as' => 'kujiale.desgin.detail', 'uses' => 'KuController@desginWorkDetail']);
        $api->post('/wxapp/kujiale/viewcount', ['name' => '设计师方案详情', 'as' => 'kujiale.desgin.detail', 'uses' => 'KuController@updateDesignViewCount']);

        $api->get('/wxapp/kujiale/getProductList', ['name' => '获取渲染图商品列表', 'as' => 'kujiale.desgin.goods.list', 'uses' => 'KuController@getProductListByPicId']);
        $api->get('/wxapp/kujiale/getDesignerDetail', ['name' => '获取渲染图详情', 'as' => 'kujiale.desgin.pic.detail', 'uses' => 'KuController@getDesignerPicById']);
        $api->get('/wxapp/kujiale/getLocationList', ['name' => '获取城市列表', 'as' => 'kujiale.location.list', 'uses' => 'KuController@getLocationList']);
    });

    $api->group(['namespace' => 'KujialeBundle\Http\FrontApi\V1\Action', 'middleware' => ['dingoguard:h5app', 'api.auth'], 'prefix' => 'h5app', 'providers' => 'jwt'], function($api) {
        $api->post('/wxapp/kujiale/like', ['name' => '设计师方案详情', 'as' => 'kujiale.desgin.detail', 'uses' => 'KuController@updateDesignLikeCount']);
    });
});