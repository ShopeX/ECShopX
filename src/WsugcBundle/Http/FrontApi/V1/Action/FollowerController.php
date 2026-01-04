<?php
/**
 * Copyright 2019-2026 ShopeX
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace WsugcBundle\Http\FrontApi\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;

use WsugcBundle\Services\FollowerService;
use WsugcBundle\Services\PostLikeService;

use Dingo\Api\Exception\UpdateResourceFailedException;
use Dingo\Api\Exception\DeleteResourceFailedException;
use Dingo\Api\Exception\StoreResourceFailedException;
use WsugcBundle\Services\PostService;
use MembersBundle\Services\WechatUserService;
use WsugcBundle\Services\MessageService;


class FollowerController extends Controller {

    public $service;
    public $limit;

    public function __construct()
    {
        $this->service = new FollowerService();
        $this->limit = 20;
    }

    /**
     * @SWG\Post(
     *     path="/h5app/wxapp/ugc/follower/create",
     *     summary="关注/取消关注",
     *     tags={"粉丝"},
     *     description="关注/取消关注",
     *     operationId="createFollow",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token（粉丝id）", required=true, type="string"),
     *     @SWG\Parameter( name="user_id", in="formData",description="被关注会员id", required=true, type="integer"),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespones") ) )
     * )
     */
    public function createFollow(Request $request)
    {   
        $params = $request->all();

        $authInfo = $request->get('auth');
        $user_id=0;
        $company_id=0;
        if($authInfo && $authInfo['user_id']){
            $user_id=$authInfo['user_id'];
            $company_id=$authInfo['company_id'];
        }
        else{
            if(env('APP_ENV')=='local' && isset($params['follower_user_id'])){
                $user_id=$params['follower_user_id'];
                $company_id=1;
            }
        }

        if (isset($user_id) && $user_id>0) {

        }else{
            throw new StoreResourceFailedException('粉丝id不能为空！');
        }
        if ($params['user_id']==$user_id) {
            throw new StoreResourceFailedException('不能关注自己！');
        }
        $data=[
            'user_id'=>$params['user_id'],
            'follower_user_id'=>$user_id,
            'company_id'=>$company_id,
        ];
        $result = $this->service->createFollow($data);
        return $this->response->array($result);
    }


    /**
     * @SWG\Get(
     *     path="/h5app/wxapp/ugc/follower/list",
     *     summary="获取粉丝列表",
     *     tags={"粉丝"},
     *     description="获取粉丝列表",
     *     operationId="getFollowerList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="user_id", in="query",description="会员id（他人的）", required=true, type="integer"),
     *     @SWG\Parameter( name="page_no", in="query",description="当前页面,获取粉丝列表的初始偏移位置，从1开始计数",  type="integer"),
     *     @SWG\Parameter( name="page_size", in="query",description="每页数量,最大不能超过50，并且如果传入的limit参数是0，那么按默认值20处理",  type="integer"),
     *     @SWG\Parameter( name="user_type", in="query",description="类型，默认follower。follower:关注列表，user:粉丝列表",  type="string", items={"user","follower"}),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespones") ) )
     * )
     */
    public function getFollowerList(Request $request)
    {   
        $params = $request->all();

        $authInfo = $request->get('auth');
        $user_id=0;
        $company_id=0;
        if($authInfo && $authInfo['user_id']){
            $user_id=$authInfo['user_id'];
            $company_id=$authInfo['company_id'];
        }
        else{
            if(env('APP_ENV')=='local' && isset($params['user_id'])){
                $user_id=$params['user_id'];
                $company_id=1;
            }
        }

        $pageNo=1;
        $pageSize=20;

        if (isset($params['page_no']) && $params['page_no']>0) {
            $pageNo=$params['page_no'];
        }
        if (isset($params['page_size']) && $params['page_size']>0 && $params['page_size']<=50) {
            $pageSize=$params['page_size'];
        }
  
        if (isset($user_id) && $user_id>0) {

        }else{
            throw new StoreResourceFailedException('会员id不能为空！');
        }
        //他人的user_id
        if ($request->get('user_id') != '') {
            //$filter['enabled'] = 1;
            $user_id=$request->get('user_id');
        }
        $user_type='follower';
        if (isset($params['user_type']) && $params['user_type']=='user') {
            $user_type='user';
        }

        $orderBy = ['created' => 'DESC'];

        $data=[
            'disabled'=>0,
            'company_id'=>isset($params['company_id'])?$params['company_id']:1,
        ];
        if ($user_type=='follower') {
            $data['follower_user_id']=$user_id;
        }else{
            $data['user_id']=$user_id;
        }
        $result = $this->service->getFollowerList($user_type,$data,$pageNo, $pageSize, $orderBy);

        return $this->response->array($result);
    }


    /**
     * @SWG\Get(
     *     path="/h5app/wxapp/ugc/follower/stat",
     *     summary="获取统计数据",
     *     tags={"粉丝"},
     *     description="获取统计数据",
     *     operationId="getFollowerStat",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="user_id", in="query",description="会员id。（对方的user_id，非当前登录用户。比如查看其他人笔记中心，传此笔记中心的用户id）", required=true, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
    *                  @SWG\Property( property="mutal_follow", type="integer", example="0", description="是否互相关注。1是，0否"),
    *                  @SWG\Property( property="follow_status", type="integer", example="1", description="我是否关注了博主。1是，0否"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespones") ) )
     * )
     */
    public function getFollowerStat(Request $request)
    {
        $params = $request->all();

        $authInfo = $request->get('auth');
        $user_id=0;
        $company_id=0;
        $user_id_auth=0;
        if($authInfo && $authInfo['user_id']){
            $user_id=$authInfo['user_id'];
            $user_id_auth=$user_id;
            $company_id=$authInfo['company_id'];
        }
        else{
            if(env('APP_ENV')=='local' && isset($params['user_id'])){
                $user_id=$params['user_id'];
                $company_id=1;
            }
        }
        if($request->get('user_id')??null){
            $user_id=$request->get('user_id');//其他人的统计呢，如果有user_id传过来，赋值是其他人。因为可以看其他人。
        }
        if (isset($user_id) && $user_id>0) {

        }else{
            throw new StoreResourceFailedException('会员id不能为空！');
        }

        // 获取粉丝数
        $params=[
            'user_id'=>$user_id,
            'disabled'=>0
        ];
        $followers = $this->service->getFollowers($params);
        // 获取关注数
        $params=[
            'follower_user_id'=>$user_id,
            'disabled'=>0
        ];
        $idols = $this->service->getIdols($params);
        // 获取获赞量
        $params=[
            'user_id'=>$user_id,
            'disabled'=>0
        ];
        $postLikeService=new PostLikeService();
        $likes = $postLikeService->getUserPostLikes($params);
        //获取头像昵称，笔记数量，未读消息总数
        $filter = ['user_id' => $user_id, 'company_id' => $company_id];
        $wechatUserService = new WechatUserService();
        $postService = new PostService();
        $messageService = new MessageService();

        $userInfo = $wechatUserService->getUserInfo($filter);
        //所有帖子总数
        $post_all_nums=$postService->entityRepository->count(array_merge($filter,['disabled'=>0,'is_draft'=>0]));

        //未读消息总数
        $unread_nums=$messageService->getUnreadnumsTotal($user_id);

        //是否已互相关注
        $mutal_follow=$this->service->getMutalFollow($user_id_auth,$user_id);

        //是否已关注
        $follow_status=$this->service->getFollowStatus($user_id_auth,$user_id);

        //草稿箱
        $draft_post=[];
        if($request->get('user_id')!=''){
            //本人
            if($authInfo['user_id']==$request->get('user_id')){
                //查询草稿箱id
                $draft_post=$postService->entityRepository->lists(['user_id'=>$authInfo['user_id'],'is_draft'=>1,'disabled'=>0],'post_id,title,cover',1,1,['post_id'=>'desc']);
                if($draft_post['list']??null){
                    $draft_post= $draft_post['list'][0];
                }
            }
        }
        //去掉这几个2022-10-09 16:17:30
        $userInfoKeys=['nickname','user_id','headimgurl','unionid'];
        foreach($userInfo as $k=>$v){
            if(!in_array($k,$userInfoKeys)){
                unset($userInfo[$k]);
            }
        }
        $result=[
            'followers'=>$followers,
            'idols'=>$idols,
            'likes'=>$likes,
            'userInfo'=>$userInfo,
            'post_all_nums'=>$post_all_nums,
            'unread_nums'=>$unread_nums,
            'mutal_follow'=>$mutal_follow,
            'follow_status'=>$follow_status,
            'draft_post'=>$draft_post//草稿箱
        ];
        return $this->response->array($result);
    }
    
}
