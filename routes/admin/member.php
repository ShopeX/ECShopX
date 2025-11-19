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
	//获取用户基础信息
    $api->group(['prefix' => '/admin/wxapp', 'namespace' => 'MembersBundle\Http\AdminApi\V1\Action', 'middleware' => ['api.auth', 'distributorlog'], 'providers' => 'adminwxapp'], function($api) {
        // 导购获取顾客相关接口
        $api->get('/getUserData', ['name' => '根据手机号获取用户信息', 'as' => 'admin.wxapp.user.get',  'uses'=>'UserData@getUserData']);
        $api->get('/asyncGetUserData', ['name' => '根据手机号获取用户信息(异步)', 'as' => 'admin.wxapp.user.async',  'uses'=>'UserData@asyncGetUserData']);
        $api->get('/getUserList', ['name' => '获取指定导购下关联的会员列表', 'as' => 'admin.wxapp.user.list',  'uses'=>'UserData@getUserList']);
        $api->get('/distributors/getUserList', ['name' => '获取指定店铺下关联的会员列表', 'as' => 'admin.wxapp.distributors.user.list',  'uses'=>'UserData@getDistributorUserList']);
        // 导购获取顾客标签相关接口
        $api->get('/member/taglist', ['name' => '获取会员标签列表', 'as' => 'admin.wxapp.user.taglist',  'uses'=>'UserData@getTagsList']);
        $api->post('/member/reltag', ['name' => '关联会员标签', 'as' => 'admin.wxapp.user.reltag',  'uses'=>'UserData@userRelTag']);
        $api->delete('/member/delreltag', ['name' => '删除会员标签', 'as' => 'admin.wxapp.user.delreltag',  'uses'=>'UserData@delRelUserTag']);
        // 导购获取顾客标签相关接口
        $api->get('/member/onlinetag', ['name' => '获取会员标签列表', 'as' => 'admin.wxapp.user.onlinetag',  'uses'=>'UserTagController@getOnlineUserTags']);
        $api->post('/member/tagadd',    ['name' => '会员标签添加', 'as' => 'admin.wxapp.user.tagadd',     'uses'=>'UserTagController@addUserTag']);
        $api->post('/member/tagupdate', ['name' => '会员标签编辑', 'as' => 'admin.wxapp.user.tagupdate',  'uses'=>'UserTagController@updateUserTag']);
        $api->get('/member/selftag',   ['name' => '获取管理员自有会员标签', 'as' => 'admin.wxapp.user.selftag',    'uses'=>'UserTagController@getSelfUserTags']);
        $api->delete('/member/delselftag',   ['name' => '删除管理员自有会员标签', 'as' => 'admin.wxapp.user.delselftag',    'uses'=>'UserTagController@delSelfUserTags']);
        // 导购获取顾客分组相关接口
        $api->post('/member/grouplist', ['name' => '导购员创建会员分组', 'as' => 'admin.wxapp.user.grouplist.create',  'uses'=>'UserGroupController@createGroup']);
        $api->get('/member/grouplist', ['name' => '导购员获取会员分组列表', 'as' => 'admin.wxapp.user.grouplist.list',  'uses'=>'UserGroupController@getGroupList']);
        $api->get('/member/userlistbygroup', ['name' => '导购员根据分组id获取会员列表', 'as' => 'admin.wxapp.user.group.users',  'uses'=>'UserGroupController@getUsersByGroup']);
        $api->put('/member/grouplist', ['name' => '导购员修改会员分组列表', 'as' => 'admin.wxapp.user.grouplist.update',  'uses'=>'UserGroupController@updateGroup']);
        $api->post('/member/moveusertogroup', ['name' => '导购员移动会员到分组', 'as' => 'admin.wxapp.user.usergroup.create',  'uses'=>'UserGroupController@moveUserToGroup']);
        $api->delete('/member/grouplist', ['name' => '导购员删除分组', 'as' => 'admin.wxapp.user.usergroup.delete',  'uses'=>'UserGroupController@deleteGroup']);

        $api->post('/member/remarks', ['name' => '导购员备注会员', 'as' => 'admin.wxapp.user.remarks.create',  'uses'=>'UserRemarksController@addRemarks']);
        $api->get('/member/remarks', ['name' => '获取导购员备注会员', 'as' => 'admin.wxapp.user.remarks.get',  'uses'=>'UserRemarksController@getRemarks']);
        
        $api->get('/member/browse/history/{userId}', ['name' => '获取历史浏览记录', 'as' => 'admin.wxapp.user.history.list',  'uses'=>'UserData@getBrowseHistory']);
    });
});
