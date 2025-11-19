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
    // 来源相关api
    $api->group(['namespace' => 'CommentsBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function($api) {
        $api->post('/comment', ['name' => '创建评论', 'as' => 'comment.create', 'uses' => 'Comments@createComment']);
        $api->patch('/comment/{comment_id}', ['name' => '更新评论', 'as' => 'comment.update', 'uses' => 'Comments@updateComment']);
        $api->get('/comments', ['name' => '获取评论列表', 'as' => 'comments.list', 'uses' => 'Comments@getComments']);
    });
});
