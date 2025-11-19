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
    $api->group(['prefix' => '/admin/wxapp', 'namespace' => 'PromotionsBundle\Http\AdminApi\V1\Action', 'middleware' => ['api.auth', 'distributorlog'], 'providers' => 'adminwxapp'], function($api) {
        $api->get('/promotions/activearticlelist', ['name' => '获取活动文章列表', 'as' => 'admin.wxapp.activearticle.list',  'uses'=>'ActiveArticle@getActiveArticleList']);
        $api->get('/promotions/activearticle/{id}', ['name' => '获取活动文章详情', 'as' => 'admin.wxapp.activearticle.list',  'uses'=>'ActiveArticle@getActiveArticleDetail']);
        $api->get('/promotions/activearticleforward', ['name' => '转发活动', 'as' => 'admin.wxapp.activearticle.forward',  'uses'=>'ActiveArticle@forwardActiveArticle']);
    });
});
