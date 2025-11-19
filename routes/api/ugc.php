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

$api->version('v1', function ($api) {
    $api->group(['namespace' => 'WsugcBundle\Http\Api\V1\Action', 'middleware' => [/* 'api.auth', 'shoplog' */], 'providers' => 'jwt'], function ($api) {
        //发表
        $api->post('/ugc/post/create', [ 'name'=>'发布笔记', 'as' => 'ugc.post.create', 'uses'=>'PostController@createPost']);
        //笔记批量打角标
        $api->post('/ugc/post/edit', [ 'name'=>'笔记批量打角标', 'as' => 'ugc.post.edit', 'uses'=>'PostController@editPost']);
        //笔记批量打角标
        $api->post('/ugc/post/setBadges', [ 'name'=>'笔记批量打角标', 'as' => 'ugc.post.setBadges', 'uses'=>'PostController@setBadges']);
        //审核
        $api->post('/ugc/post/verify', [ 'name'=>'审核', 'as' => 'ugc.post.verify', 'uses'=>'PostController@verifyPost']);
        //发布与下架
        $api->post('/ugc/post/enable', [ 'name'=>'发布/上架', 'as' => 'ugc.post.enable', 'uses'=>'PostController@enablePost']);

        //笔记列表
        $api->get('/ugc/post/list', ['name' => '获取笔记列表', 'middleware' => 'activated', 'as' => 'ugc.post.list', 'uses' => 'PostController@getPostList']);

        //详情
        $api->get('/ugc/post/detail', ['name' => '笔记详情', 'middleware' => ['activated'], 'as' => 'ugc.post.detail', 'uses' => 'PostController@getPostDetail']);

        //删除
        $api->post('/ugc/post/delete',  ['as' => 'ugc.post.delete',  'uses' => 'PostController@deletePost']);

        //话题置顶
        $api->post('/ugc/post/settop',  ['as' => 'ugc.topic.settop',  'uses' => 'PostController@setTopTopic']);

    });
    // 话题
    $api->group(['namespace' => 'WsugcBundle\Http\Api\V1\Action',  'middleware' => ['api.auth', 'shoplog'], 'providers' => 'jwt'], function ($api) {
        //创建话题
         $api->post('/ugc/topic/create', [ 'name'=>'新建话题', 'as' => 'ugc.topic.create', 'uses'=>'TopicController@createTopic']);
        //审核话题
        $api->post('/ugc/topic/verify', [ 'name'=>'审核话题', 'as' => 'ugc.topic.verify', 'uses'=>'TopicController@verifyTopic']);
     
        //置顶话题
        $api->post('/ugc/topic/top', [ 'name'=>'置顶话题', 'as' => 'ugc.topic.top', 'uses'=>'TopicController@topTopic']);
        //发布与下架话题
        $api->post('/ugc/topic/enable', [ 'name'=>'发布/上架话题', 'as' => 'ugc.topic.enable', 'uses'=>'TopicController@enableTopic']);
        //话题列表
        $api->get('/ugc/topic/list', ['name' => '获取话题列表', 'middleware' => 'activated', 'as' => 'ugc.topic.list', 'uses' => 'TopicController@getTopicList']);
        //话题详情
         $api->get('/ugc/topic/detail', ['name' => '话题详情', 'middleware' => ['activated'], 'as' => 'ugc.topic.detail', 'uses' => 'TopicController@getTopicDetail']);
        //删除话题
        $api->post('/ugc/topic/delete',  ['as' => 'ugc.topic.delete',  'uses' => 'TopicController@deleteTopic']);
        //话题置顶
        $api->post('/ugc/topic/settop',  ['as' => 'ugc.topic.settop',  'uses' => 'TopicController@setTopTopic']);
    });

    //tag
    $api->group(['namespace' => 'WsugcBundle\Http\Api\V1\Action',  'middleware' => ['api.auth', 'shoplog'], 'providers' => 'jwt'], function ($api) {
         //创建图片标签
         $api->post('/ugc/tag/create', [ 'name'=>'新建图片标签', 'as' => 'ugc.tag.create', 'uses'=>'TagController@createTag']);

        //审核图片标签
        $api->post('/ugc/tag/verify', [ 'name'=>'审核图片标签', 'as' => 'ugc.tag.verify', 'uses'=>'TagController@verifyTag']);    

        //发布与下架图片标签
        $api->post('/ugc/tag/enable', [ 'name'=>'发布/上架图片标签', 'as' => 'ugc.tag.enable', 'uses'=>'TagController@enableTag']);


        //图片标签列表
        $api->get('/ugc/tag/list', ['name' => '获取图片标签列表', 'middleware' => 'activated', 'as' => 'ugc.tag.list', 'uses' => 'TagController@getTagList']);
 
        //图片标签详情
         $api->get('/ugc/tag/detail', ['name' => '图片标签详情', 'middleware' => ['activated'], 'as' => 'ugc.tag.detail', 'uses' => 'TagController@getTagDetail']);
 
        //删除图片标签
        $api->delete('/ugc/tag/delete',  ['as' => 'ugc.tag.delete',  'uses' => 'TagController@deleteTag']);
    });

    //角标
    $api->group(['namespace' => 'WsugcBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'shoplog'], 'providers' => 'jwt'], function ($api) {
          //创建角标
          $api->post('/ugc/badge/create', [ 'name'=>'新建角标', 'as' => 'ugc.badge.create', 'uses'=>'BadgeController@createBadge']);
          //角标列表
          $api->get('/ugc/badge/list', ['name' => '获取角标列表', 'middleware' => 'activated', 'as' => 'ugc.badge.list', 'uses' => 'BadgeController@getBadgeList']);
          //角标详情
          $api->get('/ugc/badge/detail', ['name' => '角标详情', 'middleware' => ['activated'], 'as' => 'ugc.badge.detail', 'uses' => 'BadgeController@getBadgeDetail']);
          //删除角标
          $api->post('/ugc/badge/delete',  ['name' => '删除角标','as' => 'ugc.badge.delete',  'uses' => 'BadgeController@deleteBadge']);
          //角标置顶
        //   $api->post('/ugc/badge/settop',  ['as' => 'ugc.badge.settop',  'uses' => 'BadgeController@setTopBadge']);

    });
    //评论
    $api->group(['namespace' => 'WsugcBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'shoplog'], 'providers' => 'jwt'], function ($api) {
        //审核
        $api->post('/ugc/comment/verify', [ 'name'=>'审核评论', 'as' => 'ugc.comment.verify', 'uses'=>'CommentController@verifyComment']);

        // //发布与下架
        // $api->post('/ugc/comment/enable', [ 'name'=>'发布/上架', 'as' => 'ugc.comment.enable', 'uses'=>'CommentController@enableComment']);

        //评论列表
        $api->get('/ugc/comment/list', ['name' => '获取评论列表', 'middleware' => 'activated', 'as' => 'ugc.comment.list', 'uses' => 'CommentController@getCommentList']);

        //详情
        $api->get('/ugc/comment/detail', ['name' => '笔记详情', 'middleware' => ['activated'], 'as' => 'ugc.comment.detail', 'uses' => 'CommentController@getCommentDetail']);

        //删除
        $api->post('/ugc/comment/delete',  ['as' => 'ugc.comment.delete',  'uses' => 'CommentController@deleteComment']);

        // //评论置顶
        // $api->post('/ugc/comment/settop',  ['as' => 'ugc.comment.settop',  'uses' => 'CommentController@setTopTopic']);
    });
      //ugc设置
      $api->group(['namespace' => 'WsugcBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'shoplog'], 'providers' => 'jwt'], function ($api) {
        //保存积分设置
        $api->post('/ugc/setting/point/saveSetting', [ 'name'=>'保存积分设置', 'as' => 'ugc.setting.point.saveSetting', 'uses'=>'SettingController@savePointSetting']);


        //获取积分设置
        $api->get('/ugc/setting/point/getSetting', ['name' => '获取积分设置', 'middleware' => ['activated'], 'as' => 'ugc.setting.point.getSetting', 'uses' => 'SettingController@getPointSetting']);



    });
});
