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

/* ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ taro小程序、h5、app端、pc端 ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ */
$api->version('v1', function ($api) {

	$api->group(['prefix' => 'h5app', 'namespace' => 'WsugcBundle\Http\FrontApi\V1\Action', 'middleware' => ['dingoguard:h5app', 'api.auth'], 'providers' => 'jwt'], function ($api) {
		// 关注/取消关注
        $api->post('/wxapp/ugc/follower/create', ['name' => '关注/取消关注', 'as' => 'front.ugc.follower.create', 'uses' => 'FollowerController@createFollow']);

        // 关注/粉丝列表
        $api->get('/wxapp/ugc/follower/list', ['name' => '关注/粉丝列表', 'as' => 'front.ugc.follower.list', 'uses' => 'FollowerController@getFollowerList']);

        // 获取统计数据-粉丝，关注，获赞
        $api->get('/wxapp/ugc/follower/stat', ['name' => '获取统计数据', 'as' => 'front.ugc.follower.stat', 'uses' => 'FollowerController@getFollowerStat']);
        
 		// 评论
 		$api->post('/wxapp/ugc/comment/create', ['name' => '发表评论', 'as' => 'front.ugc.comment.create', 'uses' => 'CommentController@createComment']);
 	
        // 评论点赞/取消点赞
        $api->post('/wxapp/ugc/comment/like', ['name' => '评论点赞/取消点赞', 'as' => 'front.ugc.comment.like', 'uses' => 'CommentController@likeComment']);
        // 会员删除评论
        $api->post('/wxapp/ugc/comment/delete', ['name' => '删除评论', 'as' => 'front.ugc.comment.delete', 'uses' => 'CommentController@deleteComment']);
    });

    // 不需要授权
    $api->group(['prefix' => 'h5app', 'namespace' => 'WsugcBundle\Http\FrontApi\V1\Action', 'middleware' => 'frontnoauth:h5app'], function ($api) {
            // 评论列表
            $api->get('/wxapp/ugc/comment/list', ['name' => '评论列表', 'as' => 'front.ugc.comment.list', 'uses' => 'CommentController@getCommentList']);
            // 获取设置
            $api->get('/wxapp/ugc/post/setting', ['name' => '获取设置', 'as' => 'front.ugc.post.getSetting', 'uses' => 'SettingController@getSetting']);

            // 获取pullfeed
            $api->get('/wxapp/mps/pullfeed', ['name' => '拉取Feed文件', 'as' => 'front.mps.pullfeed', 'uses' => 'SettingController@pullfeed']);
    });

    //笔记
    $api->group(['prefix' => 'h5app', 'namespace' => 'WsugcBundle\Http\FrontApi\V1\Action', 'middleware' => ['dingoguard:h5app', 'api.auth'], 'providers' => 'jwt'], function($api) {
        //创建笔记
        $api->post('/wxapp/ugc/post/create', [ 'name'=>'发布笔记', 'as' => 'front.h5app.ugc.post.create', 'uses'=>'PostController@createPost']);

        //删除笔记
        $api->post('/wxapp/ugc/post/delete',  ['as' => 'front.wxapp.ugc.post.delete',  'uses' => 'PostController@deletePost']);

        // 分享笔记
        $api->post('/wxapp/ugc/post/share', ['name' => '分享笔记', 'as' => 'front.ugc.post.share', 'uses' => 'PostController@sharePost']);

        // 笔记点赞/取消点赞
        $api->post('/wxapp/ugc/post/like', ['name' => '笔记点赞/取消点赞', 'as' => 'front.ugc.post.like', 'uses' => 'PostController@likePost']);

        //话题
        //创建话题
        $api->post('/wxapp/ugc/topic/create', [ 'name'=>'创建话题', 'as' => 'front.h5app.ugc.post.create', 'uses'=>'TopicController@createTopic']);
        
        //图片标签
        $api->post('/wxapp/ugc/tag/create', [ 'name'=>'创建图片标签', 'as' => 'front.h5app.ugc.tag.create', 'uses'=>'TagController@createTag']);
        
        //消息
        
        //桌面最新数量+返回。
        $api->get('/wxapp/ugc/message/dashboard', [ 'name'=>'消息桌面', 'as' => 'front.h5app.ugc.message.dashboard', 'uses'=>'MessageController@getMessageDashboard']);

        //消息列表
        $api->get('/wxapp/ugc/message/list', [ 'name'=>'消息列表', 'as' => 'front.h5app.ugc.message.list', 'uses'=>'MessageController@getMessageList']);
        $api->post('/wxapp/ugc/message/setTohasRead', [ 'name'=>'设置已读', 'as' => 'front.h5app.ugc.message.setTohasRead', 'uses'=>'MessageController@setTohasRead']);
        $api->get('/wxapp/ugc/message/detail', [ 'name'=>'消息详情', 'as' => 'front.h5app.ugc.message.getMessageDetail', 'uses'=>'MessageController@getMessageDetail']);

    });

    // 不需要授权
    $api->group(['prefix' => 'h5app', 'namespace' => 'WsugcBundle\Http\FrontApi\V1\Action', 'middleware' => 'frontnoauth:h5app'], function ($api) {

        /*笔记*/
        //笔记详情
        $api->get('/wxapp/ugc/post/detail', [ 'name'=>'笔记详情', 'as' => 'front.h5app.ugc.post.detail', 
        'uses'=>'PostController@getPostDetail']);

        //笔记列表
        $api->get('/wxapp/ugc/post/list', [ 'name'=>'笔记列表', 'as' => 'front.h5app.ugc.post.list', 'uses'=>'PostController@getPostList']);

        //笔记收藏
        $api->post('/wxapp/ugc/post/favorite', ['name' => '笔记收藏', 'as' => 'front.ugc.post.favorite', 'uses' => 'PostController@favoritePost']);


        /*话题*/

        //话题详情
        $api->get('/wxapp/ugc/topic/detail', [ 'name'=>'话题详情', 'as' => 'front.h5app.ugc.post.detail', 'uses'=>'TopicController@getTopicDetail']);

        //话题列表
        $api->get('/wxapp/ugc/topic/list', [ 'name'=>'话题列表', 'as' => 'front.h5app.ugc.post.list', 'uses'=>'TopicController@getTopicList']);

        /*图片标签*/

        //标签详情
        $api->get('/wxapp/ugc/tag/detail', [ 'name'=>'图片标签详情', 'as' => 'front.h5app.ugc.post.detail', 'uses'=>'TagController@getTagDetail']);

        //图片列表
        $api->get('/wxapp/ugc/tag/list', [ 'name'=>'图片标签列表', 'as' => 'front.h5app.ugc.post.list', 'uses'=>'TagController@getTagList']);
    });
    
});
/* ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ taro小程序、h5、app端、pc端 ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ */
