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
    // 用户相关信息
    $api->group(['namespace' => 'MembersBundle\Http\Api\V1\Action','middleware' => ['api.auth', 'shoplog'], 'providers' => 'jwt'], function($api) {
        //粉丝相关
        $api->get('/wechat/fans/list',  ['name'=>'获取微信用户列表','middleware'=>'activated', 'as' => 'wxFans.list',   'uses' =>'WechatFans@getWxFansList' ]);
        $api->get('/wechat/fans',       ['name'=>'获取微信用户基本信息','middleware'=>'activated', 'as' => 'wxFans.info',   'uses' =>'WechatFans@getWxFansInfo' ]);
        $api->put('/wechat/fans/remark',['name'=>'修改微信用户备注','middleware'=>'activated', 'as' => 'wxFans.remark', 'uses'=>'WechatFans@wxremark' ]);
        $api->get('/wechat/fans/sync',  ['name'=>'同步微信用户列表','middleware'=>'activated', 'as' => 'wxFans.sync',   'uses' =>'WechatFans@syncWechatFans' ]);
        $api->get('/wechat/fans/tags',  ['name'=>'获取指定用户的标签列表','middleware'=>'activated', 'as' => 'wxFans.tags',   'uses'=>'WechatFans@getWxTagsOfUser' ]);

        $api->post('/wechat/tag',       ['name'=>'微信标签创建','middleware'=>'activated', 'as' => 'wxTags.create', 'uses' =>'WechatFansTags@wxtagCreate']);
        $api->put('/wechat/tag',        ['name'=>'微信标签更新','middleware'=>'activated', 'as' => 'wxTags.update', 'uses' =>'WechatFansTags@wxtagUpdate']);
        $api->delete('/wechat/tag',     ['name'=>'删除微信标签','middleware'=>'activated', 'as' => 'wxTags.delete', 'uses'=>'WechatFansTags@wxtagDelete']);
        $api->get('/wechat/tags',       ['name'=>'获取微信标签列表','middleware'=>'activated', 'as' => 'wxTags.list', 'uses' =>'WechatFansTags@getWxtagList']);
        $api->get('/wechat/tag/sync',   ['name'=>'同步微信用户标签列表','middleware'=>'activated', 'as' => 'wxTags.sync',  'uses' =>'WechatFansTags@syncWechatTags']);
        $api->get('/wechat/tag/fans',   ['name'=>'获取指定标签下用户列表','middleware'=>'activated', 'as' => 'wxTags.fans', 'uses'=>'WechatFans@getWxFansOfTag']);
        $api->patch('/wechat/tag/batchSet', ['name'=>'微信用户批量打标签','middleware'=>'activated', 'as' => 'wxTags.batchSet', 'uses' =>'WechatFansTags@batchSetUserTags']);

        //会员相关
        $api->post('/members/register/setting', ['name'=>'设置会员注册项','middleware'=>'activated', 'as' => 'member.register.set', 'uses' =>'Members@setMemberRegItems']);
        $api->get('/members/register/setting',  ['name'=>'获取会员注册项','middleware'=>'activated', 'as' => 'member.register.get', 'uses' =>'Members@getMemberRegItems']);

        $api->get('/members',  ['name'=>'获取会员列表','middleware'=>['activated', 'datapass'],  'as' => 'member.list', 'uses' =>'Members@getMemberList']);
        $api->get('/member',   ['name'=>'获取会员信息','middleware'=>['activated', 'datapass'],  'as' => 'member.info', 'uses' =>'Members@getMemberInfo']);
        // 获取短信验证码-已支持h5
        $api->get('/member/sms/code', ['as' => 'member.sms.code',      'uses' => 'Members@getSmsCode']);
        // 获取图片验证码-已支持h5
        $api->get('/member/image/code', ['as' => 'member.image.code',      'uses' => 'Members@getImageVcode']);
        $api->post('/member',   ['name'=>'新增会员','middleware'=>['activated'],  'as' => 'member.create', 'uses' =>'Members@createMember']);
        $api->patch('/member', ['name'=>'更新会员信息','middleware'=>'activated',  'as' => 'member.update', 'uses' =>'Members@updateMemberInfo']);
        $api->put('/member',   ['name'=>'更新会员手机信息','middleware'=>'activated',  'as' => 'member.upate.mobile', 'uses' =>'Members@updateMobileById']);
        $api->put('/member/salesman',   ['name'=>'设置会员的导购员','middleware'=>'activated',  'as' => 'member.upate.salesman', 'uses' =>'Members@setMemberSalesman']);
        $api->put('/member/grade',   ['name'=>'更新会员等级','middleware'=>'activated',  'as' => 'member.upate.grade_id', 'uses' =>'Members@updateGradeById']);
        $api->patch('/member/grade',   ['name'=>'批量更新会员等级','middleware'=>'activated',  'as' => 'member.upate.grade_ids', 'uses' =>'Members@updateGrade']);
        $api->get('/operate/loglist',   ['name'=>'获取会员操作日志','middleware'=>'activated',  'as' => 'member.operate.logs', 'uses' =>'Members@gerMemberOperateLogList']);


        $api->post('/member/smssend',       ['name'=>'会员群发短信','middleware'=>'activated', 'as' => 'member.smssend', 'uses' =>'SmsController@smsSends']);


        $api->post('/member/tag',       ['name'=>'新增会员标签','middleware'=>'activated', 'as' => 'member.tag.add', 'uses' =>'MemberTags@createTags']);
        $api->delete('/member/tag/{tag_id}',       ['name'=>'删除会员标签','middleware'=>'activated', 'as' => 'member.tag.delete', 'uses' =>'MemberTags@deleteTag']);
        $api->put('/member/tag',       ['name'=>'更新会员标签','middleware'=>'activated', 'as' => 'member.tag.update', 'uses' =>'MemberTags@updateTags']);
        $api->get('/member/tag',       ['name'=>'获取会员标签列表','middleware'=>'activated', 'as' => 'member.tag.list', 'uses' =>'MemberTags@getTagsList']);
        $api->get('/member/tag/{tag_id}',       ['name'=>'获取会员标签详情','middleware'=>'activated', 'as' => 'member.tag.get', 'uses' =>'MemberTags@getTagsInfo']);
        $api->post('/member/reltagdel',       ['name'=>'删除会员标签','middleware'=>'activated', 'as' => 'member.tag.del', 'uses' =>'MemberTags@tagsRelUserDel']);
        $api->post('/member/reltag',       ['name'=>'关联会员标签','middleware'=>'activated', 'as' => 'member.tag.rel', 'uses' =>'MemberTags@tagsRelUser']);

        $api->get('/member/tagsearch',       ['name'=>'根据标签筛选会员','middleware'=>'activated', 'as' => 'member.tagsearch', 'uses' =>'MemberTags@getUserIdsByTagids']);

        $api->get('/member/export',       ['name'=>'导出会员信息','middleware'=>['activated', 'datapass'], 'as' => 'member.export', 'uses' =>'ExportData@exportMemberData']);
        $api->post('/member/batchOperating',       ['name'=>'批量操作会员信息', 'middleware'=>'activated', 'as' => 'member.batch.operating', 'uses' =>'ExportData@batchProcessMemberData']);
        //会员标签分类
        $api->post('/member/tagcategory',                    ['name'=>'新增会员标签分类','middleware'=>'activated', 'as' => 'member.tagcategory.add', 'uses' =>'TagsCategoryController@createTagsCategory']);
        $api->delete('/member/tagcategory/{category_id}',    ['name'=>'删除会员标签分类','middleware'=>'activated', 'as' => 'member.tagcategory.delete', 'uses' =>'TagsCategoryController@deleteTagsCategory']);
        $api->put('/member/tagcategory/{category_id}',       ['name'=>'更新会员标签分类','middleware'=>'activated', 'as' => 'member.tagcategory.update', 'uses' =>'TagsCategoryController@updateTagsCategory']);
        $api->get('/member/tagcategory',                     ['name'=>'获取会员标签分类列表','middleware'=>'activated', 'as' => 'member.tagcategory.list', 'uses' =>'TagsCategoryController@getTagsCategoryList']);
        $api->get('/member/tagcategory/{category_id}',       ['name'=>'获取会员标签分类详情','middleware'=>'activated', 'as' => 'member.tagcategory.get', 'uses' =>'TagsCategoryController@getTagsCategoryInfo']);


        $api->post('/member/bindusersalespersonrel', ['name'=>'添加或修改会员与导购员的绑定关系','middleware'=>'activated', 'as' => 'member.bindusersalesperson.update', 'uses' =>'Members@bindUserSalespersonRel']);
        //更新会员基础信息
        $api->put('/member/update',  ['name'=>'更新会员基础信息','middleware'=>'activated', 'as' => 'member.update',  'uses' => 'Members@updateMember']);

        // 白名单
        $api->get('/members/whitelist/list',['name'=>'获取会员白名单列表','middleware'=>['activated', 'datapass'], 'as' => 'member.whitelist.list', 'uses' =>'MembersWhitelist@getLists']);
        $api->get('/members/whitelist/{id}',['name'=>'获取会员白名单详情','middleware'=>'activated', 'as' => 'member.whitelist.info', 'uses' =>'MembersWhitelist@getInfo']);
        $api->post('/members/whitelist', ['name' => '创建会员白名单', 'as' => 'member.whitelist.create', 'uses'=>'MembersWhitelist@createData']);
        $api->post('/members/whitelist/{id}', ['name' => '更改会员白名单信息', 'as' => 'member.whitelist.update', 'uses'=>'MembersWhitelist@updateData']);
        $api->delete('/members/whitelist/{id}', ['name' => '删除会员白名单信息', 'as' => 'member.whitelist.delete', 'uses'=>'MembersWhitelist@deleteData']);

        //获取订阅列表
        $api->get('/members/subscribe/list',['name'=>'获取订阅列表','middleware'=>'activated', 'as' => 'member.subscribe.list', 'uses' =>'MembersSubscribe@getLists']);

        //信任登录列表
        $api->post('/members/trustlogin/list',['name'=>'获取信任登录列表','middleware'=>'activated', 'as' => 'member.trustlogin.list', 'uses' =>'TrustLogin@getTrustLoginList']);
        $api->put('/members/trustlogin/setting',['name'=>'保存信任登录状态','middleware'=>'activated', 'as' => 'member.trustlogin.setting', 'uses' =>'TrustLogin@saveStatusSetting']);

    });
});
