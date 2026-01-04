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

namespace WsugcBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use Dingo\Api\Exception\ResourceException;
use Dingo\Api\Exception\StoreResourceFailedException;
use WsugcBundle\Services\SettingService;
use WsugcBundle\Services\CommentService;
use WsugcBundle\Services\PostService;
use WsugcBundle\Services\MessageService;

class CommentController extends Controller
{
    /**
     * @SWG\Post(
     *     path="/ugc/comment/verify",
     *     summary="审核评论",
     *     tags={"评论"},
     *     description="审核评论",
     *     operationId="createComment",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=false, type="string"),
     *     @SWG\Parameter( name="status", in="formData", description="审核状态 0待审核,1审核通过,2机器拒绝,3待人工审核,4人工拒绝", required=true, type="integer"),
     *     @SWG\Parameter( name="refuse_reason", in="formData", description="拒绝原因", required=false, type="string"),
     *     @SWG\Parameter( name="comment_id[]", in="formData", description="评论comment_id,数组,提交参数的格式为form表单类型,比如 comment_id[]:2 comment_id[]:3", required=true,collectionFormat="multi",type="array",
     *    @SWG\Items(
     *             type="integer",
     *             format="int32"
     *   ),uniqueItems=true),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="comment_id", type="string", example="49", description="记录id"),
     *                  @SWG\Property( property="activity_id", type="string", example="36", description="活动ID"),
     *                  @SWG\Property( property="user_id", type="string", example="20337", description="用户id "),
     *                  @SWG\Property( property="mobile", type="string", example="17521302310", description="手机号"),
     *                  @SWG\Property( property="wxapp_appid", type="string", example="", description="会员小程序appid"),
     * ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespone") ) )
     * )
     */
    public function verifyComment(Request $request)
    {
        $allParams = $request->all('comment_id','status','refuse_reason');
        $authInfo = app('auth')->user()->get();
        $authInfo =  app('auth')->user();
        $admin['operator_id']=$authInfo->get('operator_id');
        $admin['username']=$authInfo->get('username');
        $admin['company_id']=$authInfo->get('company_id');

        $user_id=0;
        if($authInfo && $admin['operator_id']){
            $user_id=$admin['operator_id'];
        }
        else if(env('APP_ENV')=='local'){
            $user_id=$allParams['user_id']??0;
        }
        if (!($user_id ?? 0)) {
           // throw new ResourceException('未登录不可以审核评论');
        }
        $params=$allParams;
        if($params['status']??null){
            
        }
        else{
            throw new ResourceException('status参数不能为空');
        }
        if($params['comment_id']??null){
            //$params['topics']=implode(',',$params['topics']);
        }
        else{
            throw new ResourceException('comment_id参数不能为空');
        }
    /*     if($params['status']==4 && (($params['refuse_reason']??null)=='')){
            //
            throw new ResourceException('人工拒绝原因不能为空');
        } */
        // $params['comment_id'] =  1;
        // $params['status'] =  1;
        // 查询活动信息 
        $postService = new PostService();
        $commentService=new CommentService();
        $params['manual_refuse_reason']=$params['refuse_reason']??'';
        $data=$params;
        //if(isset($data['refuse_reason'])) {
            unset($data['refuse_reason']);
        //}
        unset($data['comment_id']);
        $result = $commentService->entityRepository->updateBy(['comment_id'=>$params['comment_id']],$data);

        if($params['comment_id']??null){
           
            if($params['comment_id']??null){
                if($data['status']==4){
                    $messageService=new MessageService();

                 foreach($params['comment_id'] as $k=>$paramsOne){
                             
                     $messageData=[];
     
                     try{
                         
                         $commentInfo=$commentService->entityRepository->getInfoById($paramsOne);
                         //发送 回复评论/评论笔记。
                         //基本信息
                         $messageData['type']='system';
                         $messageData['sub_type']='refuseComment';//评论被拒绝
                         $messageData['source']=2;
                         $messageData['post_id']=$commentInfo['post_id'];
                         $messageData['comment_id']=$paramsOne;
                         $messageData['company_id']=$commentInfo['company_id'];
                         //发
                         $messageData['from_user_id']=$user_id;//管理员id
                         $messageData['from_nickname']=($admin['username']??'系统管理员');//$postService->getNickName($messageData['from_user_id'],$params['company_id']);
                         
                         $messageData['to_user_id']=$commentInfo['user_id'];
                         $messageData['to_nickname']=$postService->getNickName($messageData['to_user_id'],$commentInfo['company_id']);
                         $messageData['title']='您的评论包含违规内容,他人将不可见';
     
                         //拒绝评论
                         $messageData['content']=($data['manual_refuse_reason']??'');
                         $messageService->sendMessage($messageData);
                     }
                     catch(\Exception $e){
                         app('log')->debug('发送评论消息 失败: messageData:'.var_export($messageData,true)."|失败原因:".$e->getMessage());
                     }
                 }
                 }
     
             }

        }
        //ksort($result);
        /*发送消息-免费的发，收费的付款后发*/
        /*
        if (!$activityinfo['need_fee']) {
            //收费的不发
            $messageService->sendMassage($filter['company_id'], $result['comment_id'], 'yuyueAdd');
        } 
        */
        /**/
        $params['message'] = '审核成功';
        return $this->response->array($params);
    }
    /**
     * @SWG\Post(
     *     path="/ugc/comment/delete",
     *     summary="删除评论",
     *     tags={"评论"},
     *     description="删除评论",
     *     operationId="createComment",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=false, type="string"),
     *     @SWG\Parameter( name="comment_id[]", in="formData", description="评论comment_id,数组,提交参数的格式为form表单类型,比如 comment_id[]:2 comment_id[]:3", required=true,collectionFormat="multi",type="array",
     *    @SWG\Items(
     *             type="integer",
     *             format="int32"
     *   ),uniqueItems=true),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="comment_id", type="string", example="49", description="记录id"),
     *                  @SWG\Property( property="activity_id", type="string", example="36", description="活动ID"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespone") ) )
     * )
     */
    public function deleteComment(Request $request)
    {
        // 1e236443e5a30b09910e0d48c994b8e6 core
        $allParams = $request->all('comment_id');
        $authInfo = app('auth')->user()->get();
        $user_id=0;
        if($authInfo && $authInfo['operator_id']){
            $user_id=$authInfo['operator_id'];
        }
        else if(env('APP_ENV')=='local'){
            $user_id=$allParams['user_id']??0;
        }
        if (!($user_id ?? 0)) {
           // throw new ResourceException('未登录不可以审核评论');
        }
      
        $params=$allParams;
      
       
        if($params['comment_id']??null){
            //$params['topics']=implode(',',$params['topics']);
        }
        else{
            throw new ResourceException('comment_id参数不能为空');
        }
        //$params['comment_id'] =  1;
        //$params['status'] =  1;
        //查询活动信息
        $postService = new CommentService();
        $result = $postService->deleteBy(['comment_id'=>$params['comment_id']]);
        if($result['comment_id']??null){
           
        }
        //ksort($result);
        /*发送消息-免费的发，收费的付款后发*/
        /*
        if (!$activityinfo['need_fee']) {
            //收费的不发
            $messageService->sendMassage($filter['company_id'], $result['comment_id'], 'yuyueAdd');
        } 
        */
        /**/
        $params['message'] = '删除成功';
        return $this->response->array($params);
    }
    /**
     * @SWG\Get(
     *     path="/ugc/comment/detail",
     *     summary="获取评论详情",
     *     tags={"评论"},
     *     description="获取评论详情",
     *     operationId="getCommentDetail",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=false, type="string"),
     *     @SWG\Parameter( name="comment_id", in="query", description="评论id", required=true, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="comment_id", type="string", example="49", description="记录id"),
     *                  @SWG\Property( property="activity_id", type="string", example="36", description="活动ID"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespone") ) )
     * )
     */
    public function getCommentDetail(Request $request)
    {
        // 1e236443e5a30b09910e0d48c994b8e6 core
        $authInfo = app('auth')->user()->get();

        $result['comment_info'] = null;

        $filter = [
            'company_id' => $authInfo['company_id']??1,
            'comment_id' => $request->get('comment_id'),
        ];
        $fromAdmin=true;
        $postService = new CommentService();
        $postInfo = $postService->getCommentDetail($filter, '',$fromAdmin);
        if ($postInfo) {

            $result['comment_info'] = $postInfo;
        }
        ksort($result);
        return $this->response->array($result);
    }
    /**
     * @SWG\Get(
     *     path="/ugc/comment/list",
     *     summary="获取评论列表",
     *     tags={"评论"},
     *     description="获取评论列表",
     *     operationId="getCommentList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=false, type="string"),
     *     @SWG\Parameter( name="content", in="query", description="标题关键字", type="integer"),
     *     @SWG\Parameter( name="status", in="query", description="状态", type="string"),
     *     @SWG\Parameter( name="mobile", in="query", description="手机号", type="string"),
     *     @SWG\Parameter( name="nickname", in="query", description="昵称", type="string"),
    *     @SWG\Parameter( name="content", in="query", description="评论内容", type="string"),
     *     @SWG\Parameter( name="page", in="query", description="页码", type="integer"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页条数", type="integer"),
     *     @SWG\Parameter( name="sort", in="query", description="排序", type="string",description="时间排序：created desc"),
     *     @SWG\Parameter( name="post_id[]", in="query", description="关联笔记post_id,数组,提交参数的格式为form表单类型，比如 post_id[]:2 post_id[]:3", required=false,collectionFormat="multi",type="array",
     *    @SWG\Items(
     *             type="integer",
     *             format="int32"
     *   ),uniqueItems=true),
     *     @SWG\Parameter( name="badges[]", in="query", description="关联角标badge_id,数组,提交参数的格式为form表单类型，比如 badges[]:2 badges[]:3", required=false,collectionFormat="multi",type="array",
     *    @SWG\Items(
     *             type="integer",
     *             format="int32"
     *   ),uniqueItems=true),
          *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="comment_id", type="string", example="49", description="记录id"),
     *                  @SWG\Property( property="user_id", type="string", example="20337", description="用户id "),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespone") ) )
     * )
     */
    public function getCommentList(Request $request)
    {
        $postService = new PostService();

        $authInfo = app('auth')->user()->get();
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 30);
        $authInfo = app('auth')->user()->get();
        $filter['company_id'] = $authInfo['company_id']??1;
        if ($request->get('status') != '') {
            $filter['status'] = $request->get('status');
        }
        //帖子条件筛选
        if ($request->get('post_id') != '') {
            $post_id = $request->get('post_id');//数组
            $filter['post_id']=$post_id;
        }
         //昵称
         if ($request->get('nickname') != '') {
            $filter['user_id']=$postService->getUserIdByNickName($request->get('nickname') );
        }
        //手机号
        if ($request->get('mobile') != '') {
            $filter['user_id']=$postService->getUserIdByMobile($request->get('mobile'));
        }
        //评论内容，模糊
        if ($request->get('content') != '') {
            $filter['content|contains']=$request->get('content');
        }

        $commentService = new CommentService();
        $sort = $request->get('sort') ?? '';
        $orderBy = [];
        if ($sort && trim($sort)) {
            $orderByRs = explode(' ', $sort);
            $orderBy[$orderByRs[0]] = $orderByRs[1];
        }
        else{
            $orderBy = ['p_order' => 'ASC','created' => 'DESC'];
        }
        //$parent_comment_id=0; 后台所有评论都要看到，不能只看没有父级Id的
        if (isset($params['parent_comment_id']) && $params['parent_comment_id']>0) {
            $parent_comment_id=$params['parent_comment_id'];
            $filter['parent_comment_id']= $parent_comment_id;
        }
        //后台最大原则
        $cols=['*'];
        $fromAdmin=true;
        $result = $commentService->getCommentList($filter,$cols, 0, $page, $pageSize, $orderBy,$cols,$fromAdmin);
        ksort($result);
        /*
            id: "",         
            imgUrl: "https://itiandi-uat-image.oss-cn-shanghai.aliyuncs.com/image/10/2021/07/03/c120baeff09fc49bbd4b036ec9b175bdUXJKEZdSvYCOjNt5Q23PAsSYAPbzsaV9"
            linkPage: "category"
            template: "one"
            title: "热销商品"
          */
       
        return $this->response->array($result);
    }
    /**
     * @SWG\Definition(
     *     definition="CommentInfo",
     *     description="评论信息",
     *     type="object",
     *     @SWG\Property( property="comment_id", type="string", example="48", description="评论id"),
    *                          @SWG\Property( property="user_id", type="string", example="36", description="用户ID"),
    *                          @SWG\Property( property="mobile", type="string", example="18612345678", description="用户手机号"),
    *                          @SWG\Property( property="title", type="string", example="我的第一个评论", description="标题"),
    *                          @SWG\Property( property="content", type="string", example="很好吃,菜品很丰富", description="内容"),
    *                          @SWG\Property( property="status", type="string", example="0", description="审核状态:0审核中,1已审核,2已拒绝"),
    *                          @SWG\Property( property="status_text", type="string", example="已拒绝", description="审核状态"),
    *                          @SWG\Property( property="created", type="string", example="1608272078", description="创建时间"),
    *                          @SWG\Property( property="updated", type="string", example="1608272078", description="修改时间"),
    *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
    *                          @SWG\Property( property="created_text", type="string", example="2020-12-18 14:14:38", description="创建时间"),
    *                          @SWG\Property( property="updated_text", type="string", example="2020-12-18 14:14:38", description="更新时间"),

     * )
     */
}
