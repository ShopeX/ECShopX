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

use WsugcBundle\Services\CommentService;
use WsugcBundle\Services\PostService;
use WsugcBundle\Services\ContentCheckService;

use Dingo\Api\Exception\UpdateResourceFailedException;
use Dingo\Api\Exception\DeleteResourceFailedException;
use Dingo\Api\Exception\StoreResourceFailedException;

class CommentController extends Controller {

    public $service;
    public $limit;

    public function __construct()
    {
        $this->service = new CommentService();
        $this->limit = 20;
    }

    /**
     * @SWG\Post(
     *     path="/h5app/wxapp/ugc/comment/create",
     *     summary="发表评论",
     *     tags={"笔记评论"},
     *     description="发表评论",
     *     operationId="createComment",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="user_id", in="formData",description="会员id", required=true, type="integer"),
     *     @SWG\Parameter( name="post_id", in="formData",description="笔记id", required=true, type="integer"),
     *     @SWG\Parameter( name="parent_comment_id", in="formData",description="父级评论id，对笔记评论无需传这个参数", type="integer"),
     *     @SWG\Parameter( name="reply_comment_id", in="formData",description="回复评论id，对笔记评论无需传这个参数", type="integer"),
     *     @SWG\Parameter( name="content", in="formData",description="评论内容", required=true, type="string"),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespones") ) )
     * )
     */
    public function createComment(Request $request)
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

        if (isset($user_id) && $user_id>0) {

        }else{
            throw new StoreResourceFailedException('会员id不能为空！');
        }
        if (isset($params['post_id']) && $params['post_id']>0) {

        }else{
            throw new StoreResourceFailedException('笔记id不能为空！');
        }

        if (isset($params['parent_comment_id']) && $params['parent_comment_id']>0) {

        }else{
            $params['parent_comment_id']=0;
        }
        if (isset($params['reply_comment_id']) && $params['reply_comment_id']>0) {

        }else{
            $params['reply_comment_id']=0;
        }

        if (isset($params['content']) && strlen($params['content']) < 1) {
            throw new StoreResourceFailedException('评论内容不能为空！');
        } elseif (isset($params['content']) && strlen($params['content']) > 15000) {
            throw new StoreResourceFailedException('评论内容不超过500个汉字！');
        }

        // ip
        $realIp = explode(',', $request->server('HTTP_X_FORWARDED_FOR'))[0];
        $ip = $realIp ? : $request->getClientIp();

        $data=[
            'user_id'=>$user_id,
            'post_id'=>$params['post_id'],
            'parent_comment_id'=>$params['parent_comment_id'],
            'reply_comment_id'=>$params['reply_comment_id'],
            'content'=>$params['content'],
            'company_id'=>$company_id,
            'p_order'=>0,
            'ip'=>$ip,
        ];
        //内容
        $postService = new PostService();
        $content_status=0;
        $open_id=$postService->getOpenId($user_id,$company_id);
        $contentCheckService=new ContentCheckService($company_id);
        if($params['content']){
            if($msgCheckResult=$contentCheckService->msgCheck($params['content'],$open_id)){
                $content_status=$msgCheckResult;
            }
            else{
                //机器审核不上的话，还是 待审核
                $content_status=0;
            }
        }
        //标题和内容都过审，才pass
        $data['status']=0;
        $msg='';
        if($content_status==1){
            $data['status']=1;
            $msg='发表评论成功';
        }
        elseif($content_status==4){
            $data['status']=4;
            $msg='评论违规';

        }
        else{
            $data['status']=0;
            $msg='评论等待管理员审核';

        }
        $result = $this->service->createComment($data);
        $result['message'] = $msg;

        return $this->response->array($result);
    }


    /**
     * @SWG\Get(
     *     path="/h5app/wxapp/ugc/comment/list",
     *     summary="获取评论列表",
     *     tags={"笔记评论"},
     *     description="获取评论列表",
     *     operationId="getCommentList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=false, type="string"),
     *     @SWG\Parameter( name="post_id", in="query",description="笔记id", required=true, type="integer"),
     *     @SWG\Parameter( name="parent_comment_id", in="query",description="父级评论id",  type="integer"),
     *     @SWG\Parameter( name="page_no", in="query",description="当前页面,获取评论列表的初始偏移位置，从1开始计数",  type="integer"),
     *     @SWG\Parameter( name="page_size", in="query",description="每页数量,最大不能超过50，并且如果传入的limit参数是0，那么按默认值20处理",  type="integer"),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespones") ) )
     * )
     */
    public function getCommentList(Request $request)
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
            if(env('APP_ENV')=='local'){
                $company_id=1;
            }
            if(env('APP_ENV')=='local' && isset($params['user_id'])){
                $user_id=$params['user_id'];
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

        if (isset($params['post_id']) && $params['post_id']>0) {

        }else{
            throw new StoreResourceFailedException('笔记id不能为空！');
        }

        $parent_comment_id=0;
        $orderBy = ['p_order' => 'ASC','created' => 'DESC'];
        if (isset($params['parent_comment_id']) && $params['parent_comment_id']>0) {
            $parent_comment_id=$params['parent_comment_id'];
            $orderBy = ['p_order' => 'ASC','created' => 'ASC'];
        }

        $orderBy = ['p_order' => 'ASC','created' => 'DESC'];

        $data=[
            'post_id'=>$params['post_id'],
            'parent_comment_id'=>$parent_comment_id,
            'status'=>'1',
            'enable'=>'1',
            'disabled'=>0,
            'company_id'=>$company_id,
        ];
        $cols='comment_id,post_id,user_id,reply_user_id,content,likes,company_id,created,status';
        $result = $this->service->getCommentList($data,$cols,$user_id,$pageNo, $pageSize, $orderBy);

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/h5app/wxapp/ugc/comment/delete",
     *     summary="删除评论",
     *     tags={"笔记评论"},
     *     description="删除评论",
     *     operationId="deleteComment",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="user_id", in="formData",description="会员id", required=true, type="integer"),
     *     @SWG\Parameter( name="comment_id", in="formData",description="评论id", type="integer"),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespones") ) )
     * )
     */
    public function deleteComment(Request $request)
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

        if (isset($user_id) && $user_id>0) {

        }else{
            throw new StoreResourceFailedException('会员id不能为空！');
        }
        if (isset($params['comment_id']) && $params['comment_id']>0) {

        }else{
            throw new StoreResourceFailedException('评论id不能为空！');
        }

        $data=[
            'user_id'=>$user_id,
            'comment_id'=>$params['comment_id'],
            'disabled'=>0,
        ];
        $result = $this->service->deleteComment($data);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/h5app/wxapp/ugc/comment/like",
     *     summary="点赞/取消点赞",
     *     tags={"笔记评论"},
     *     description="点赞/取消点赞",
     *     operationId="likeComment",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="user_id", in="formData",description="会员id", required=true, type="integer"),
     *     @SWG\Parameter( name="post_id", in="formData",description="笔记id", required=true, type="integer"),
     *     @SWG\Parameter( name="comment_id", in="formData",description="评论id", required=true, type="integer"),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespones") ) )
     * )
     */
    public function likeComment(Request $request)
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

        if (isset($user_id) && $user_id>0) {

        }else{
            throw new StoreResourceFailedException('会员id不能为空！');
        }
        if (isset($params['post_id']) && $params['post_id']>0) {

        }else{
            throw new StoreResourceFailedException('笔记id不能为空！');
        }
        if (isset($params['comment_id']) && $params['comment_id']>0) {

        }else{
            throw new StoreResourceFailedException('评论id不能为空！');
        }
        
        $data=[
            'user_id'=>$user_id,
            'post_id'=>$params['post_id'],
            'comment_id'=>$params['comment_id'],
        ];
        $result = $this->service->likeComment($data);

        return $this->response->array($result);
    }
    
}
