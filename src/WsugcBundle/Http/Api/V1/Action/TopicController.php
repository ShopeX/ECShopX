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

//话题
namespace WsugcBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use Dingo\Api\Exception\ResourceException;
use Dingo\Api\Exception\StoreResourceFailedException;
use WsugcBundle\Services\TopicService;
use WsugcBundle\Services\PostService;
use WsugcBundle\Services\SettingService;

class TopicController extends Controller
{
     /**
     * @SWG\Post(
     *     path="/ugc/topic/create",
     *     summary="创建话题",
     *     tags={"话题"},
     *     description="创建话题",
     *     operationId="createTopic",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=false, type="string"),
     *     @SWG\Parameter( name="topic_name", in="formData", description="话题名称", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="post_id", type="string", example="49", description="记录id"),
     *                  @SWG\Property( property="activity_id", type="string", example="36", description="活动ID"),
     *                  @SWG\Property( property="user_id", type="string", example="20337", description="用户id "),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespone") ) )
     * )
     */
    public function createTopic(Request $request)
    {
        $allParams = $request->all('topic_id','topic_name','user_id','company_id');
        $authInfo = app('auth')->user()->get();
        $user_id=0;
        if($authInfo && $authInfo['operator_id']){
            $user_id=$authInfo['operator_id'];
            $company_id=$authInfo['company_id'];
        }
        // else if(env('APP_ENV')=='local'){
        //     $user_id=$allParams['user_id']??0;
        //     $company_id=1;
        // }
        // if (!($user_id ?? 0)) {
        // }
      
        $params=$allParams;
        if(!$params['topic_name']){
            throw new ResourceException('话题名称不能为空');
        }
        $topicService = new TopicService();
        if($topicService->entityRepository->count(['topic_name'=>$params['topic_name'], 'company_id' => $params['company_id']])>0){
            throw new ResourceException('同名话题已存在');
        }
        $params['operator_id'] =  $user_id;
        $params['user_id'] =  0;
        $params['mobile'] = $authInfo['mobile']??'';
        $params['company_id'] = $company_id;
        $params['p_order']=0;
        $params['source']=2;//管理员添加的
        $params['is_top']=0;//置顶
        //查询活动信息
        //创建或更新
        $action='add';
        if($params['topic_id']??null){
            $result = $topicService->saveData($params,['topic_id'=>$params['topic_id']]);
            $result['topic_id']=$params['topic_id'];
            $action='edit';
        }
        else{
            $result = $topicService->saveData($params);
        }
        //$result = $topicService->create($params);
        ksort($result);
        /**/
        $result['message'] = (($action=='edit'?'更新':'创建').'话题成功');
        return $this->response->array($result);
    }
    /**
     * @SWG\Post(
     *     path="/ugc/topic/verify",
     *     summary="审核话题",
     *     tags={"话题"},
     *     description="审核话题",
     *     operationId="createTag",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=false, type="string"),
     *     @SWG\Parameter( name="status", in="formData", description="审核状态 0待审核,1审核通过,2机器拒绝,3待人工审核,4人工拒绝", required=true, type="integer"),
     *     @SWG\Parameter( name="refuse_reason", in="formData", description="拒绝原因", required=false, type="string"),
     *     @SWG\Parameter( name="topic_id[]", in="formData", description="话题id,数组,提交参数的格式为form表单类型,比如 topic_id[]:2 topic_id[]:3", required=true,collectionFormat="multi",type="array",
     *    @SWG\Items(
     *             type="integer",
     *             format="int32"
     *   ),uniqueItems=true),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="topic_id", type="string", example="49", description="记录id"),
     *                  @SWG\Property( property="user_id", type="string", example="20337", description="用户id "),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespone") ) )
     * )
     */
    public function verifyTopic(Request $request)
    {
        $allParams =  $request->all('topic_id','status','refuse_reason');
        $authInfo  =  app('auth')->user()->get();
        $user_id   =  0;
        if($authInfo && $authInfo['operator_id']){
            $user_id= $authInfo['operator_id'];
        }
        else if(env('APP_ENV')=='local'){
            $user_id=$allParams['user_id']??0;
        }
        if (!($user_id ?? 0)) {
            //
        }
        $params=$allParams;
        if($params['status']??null){
        }
        else{
            throw new ResourceException('status参数不能为空');
        }
        if($params['topic_id']??null){
            //$params['topics']=implode(',',$params['topics']);
        }
        else{
            throw new ResourceException('topic_id参数不能为空');
        }
/*         if($params['status']==4 && (($params['refuse_reason']??null)=='')){
            throw new ResourceException('人工拒绝原因不能为空');
        } */
        $topicService = new TopicService();
        $params['manual_refuse_reason']=$params['refuse_reason']??'';
        $params['manual_verify_time']=time();
        $data=$params;
        unset($data['refuse_reason']);

        unset($data['topic_id']);
        $result = $topicService->entityRepository->updateBy(['topic_id'=>$params['topic_id']],$data);
        if($result['topic_id']??null){
           
        }
        $params['message'] = '审核成功';
        return $this->response->array($params);
    }
    /**
     * @SWG\Post(
     *     path="/ugc/topic/settop",
     *     summary="话题置顶",
     *     tags={"话题"},
     *     description="话题置顶",
     *     operationId="createTag",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=false, type="string"),
     *     @SWG\Parameter( name="topic_id[]", in="formData", description="话题id,数组,提交参数的格式为form表单类型,比如 topic_id[]:2 topic_id[]:3", required=true,collectionFormat="multi",type="array",
     *    @SWG\Items(
     *             type="integer",
     *             format="int32"
     *   ),uniqueItems=true),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="topic_id", type="string", example="49", description="记录id"),
     *                  @SWG\Property( property="user_id", type="string", example="20337", description="用户id "),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespone") ) )
     * )
     */
    public function setTopTopic(Request $request)
    {
        $allParams =  $request->all('topic_id');
        $authInfo  =  app('auth')->user()->get();
        $user_id   =  0;
        if($authInfo && $authInfo['operator_id']){
            $user_id= $authInfo['operator_id'];
        }
        else if(env('APP_ENV')=='local'){
            $user_id=$allParams['user_id']??0;
        }
        if (!($user_id ?? 0)) {
            //
        }
        $params=$allParams;
        if($params['topic_id']??null){
            //$params['topics']=implode(',',$params['topics']);
        }
        else{
            throw new ResourceException('topic_id参数不能为空');
        }
        $topicService = new TopicService();
        $data=$params;
        //置顶话题的p_order排序，最上面的在最上面
        $allUpdate=[];

        //先干掉之前所有置顶的
        $filterOldTop=['is_top'=>1];
        $updateOldTop=['is_top'=>0,'p_order'=>0];
        $result = $topicService->entityRepository->updateBy($filterOldTop,$updateOldTop);

        foreach($data['topic_id'] as $k=>$v){
            $updateData['is_top']=1;
            $updateData['p_order']=(count($data['topic_id'])-$k)*-1;//负数
            $result = $topicService->entityRepository->updateOneBy(['topic_id'=>$v],$updateData);
            $allUpdate[]=$updateData;
        }
        if($result['topic_id']??null){
           
        }
        $lastData['allUpdate']=$allUpdate;
        $lastData['message'] = '置顶成功';
        return $this->response->array($lastData);
    }
    /**
     * @SWG\Get(
     *     path="/ugc/topic/detail",
     *     summary="获取话题详情",
     *     tags={"话题"},
     *     description="获取话题详情",
     *     operationId="getTopicDetail",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=false, type="string"),
     *     @SWG\Parameter( name="topic_id", in="query", description="话题id", required=true, type="integer"),
      *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="post_id", type="string", example="49", description="记录id"),
     *                  @SWG\Property( property="activity_id", type="string", example="36", description="活动ID"),
     *                  @SWG\Property( property="user_id", type="string", example="20337", description="用户id "),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespone") ) )
     * )
     */
    public function getTopicDetail(Request $request)
    {
        // Powered by ShopEx EcShopX
        $authInfo = app('auth')->user()->get();

        $result['topic_info'] = null;

        $filter = [
            'company_id' => $authInfo['company_id']??1,
            'topic_id' => $request->get('topic_id'),
        ];
        $topicService = new TopicService();
        $fromAdmin=true;
        $topicInfo = $topicService->getTopicDetail($filter, $authInfo['user_id']??0,$fromAdmin);
        if ($topicInfo) {
            $result['topic_info'] = $topicInfo;
        }
        // $postSettingService = new postSettingService();
        // $list=$postSettingService->getSettingList([],'license');
        // $license='未设置';
        // $license_enabled="";
        // if($list && $list['total_count']>0){
        //     $license=$list['list'][0]['license'];
        //     $license_enabled=$list['list'][0]['license_enabled'];
        //     //$result['activity_info']['common_setting']=$list['list'][0];

        // }
        // $result['activity_info']['license']=$license;
        // $result['activity_info']['license_enabled']=$license_enabled;
        ksort($result);
        return $this->response->array($result);
    }
    /**
     * @SWG\Get(
     *     path="/ugc/topic/list",
     *     summary="获取话题列表",
     *     tags={"话题"},
     *     description="获取话题列表",
     *     operationId="getTopicList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=false, type="string"),
     *     @SWG\Parameter( name="topic_name", in="query", description="话题名称", required=false, type="string"),
     *     @SWG\Parameter( name="status", in="query", description="状态 0待审核 1已审核 4已拒绝", type="string"),
     *     @SWG\Parameter( name="source", in="query", description="来源 1用户，2官方", type="string"),
     *     @SWG\Parameter( name="mobile", in="query", description="手机号", type="string"),
     *     @SWG\Parameter( name="nickname", in="query", description="昵称", type="string"),
     *     @SWG\Parameter( name="page", in="query", description="页码", type="integer"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页条数", type="integer"),
     *     @SWG\Parameter( name="is_top", in="query", description="置顶话题", type="integer",description="是否指定话题 0:不是,1:置顶"),
     *     @SWG\Parameter( name="sort", in="query", description="排序", type="string",description="按后台拖动设置的排序：p_order asc;创建时间（最新的在前）:created desc"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="post_id", type="string", example="49", description="记录id"),
     *                  @SWG\Property( property="activity_id", type="string", example="36", description="活动ID"),
     *                  @SWG\Property( property="user_id", type="string", example="20337", description="用户id "),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespone") ) )
     * )
     */
    public function getTopicList(Request $request)
    {
        $postService = new PostService();

        $authInfo = app('auth')->user()->get();
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 30);
        $authInfo = app('auth')->user()->get();
        $filter['company_id'] = $authInfo['company_id']??1;
        if ($request->get('topic_name') != '') {
            $filter['topic_name|contains'] = $request->get('topic_name');
        }
        //置顶
        if ($request->get('is_top') != '') {
            $filter['is_top'] = $request->get('is_top');
        }
        //状态
        if ($request->get('status') != '') {
            $filter['status'] = $request->get('status');
        }
        //来源
        if ($request->get('source') != '') {
            $filter['source'] = $request->get('source');
        }
        //昵称
        if ($request->get('nickname') != '') {
            $filter['user_id']=$postService->getUserIdByNickName($request->get('nickname') );
        }
        //手机号
        if ($request->get('mobile') != '') {
            $filter['user_id']=$postService->getUserIdByMobile($request->get('mobile'));
        }
        $topicService = new TopicService();
        //$filter['enabled'] = 1;
        $sort = $request->get('sort') ?? '';
        $orderBy = [];
        if ($sort && trim($sort)) {
            $orderByRs = explode(' ', $sort);
            $orderBy[$orderByRs[0]] = $orderByRs[1];
            $orderBy['p_order'] = 'asc';
            //$filter['start_time|gte']=time();//开始时间大于当前时间
        }
        $cols='topic_id,topic_name,user_id,p_order,created,updated,source,is_top,status,company_id';
        $fromAdmin=true;
        $result = $topicService->getTopicList($filter, $cols, $page, $pageSize, $orderBy,$fromAdmin);

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
     * @SWG\Post(
     *     path="/ugc/topic/delete",
     *     summary="删除话题",
     *     tags={"话题"},
     *     description="删除话题",
     *     operationId="createPost",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=false, type="string"),
     *     @SWG\Parameter( name="topic_id[]", in="formData", description="话题topic_id,数组,提交参数的格式为form表单类型,比如 topic_id[]:2 topic_id[]:3", required=true,collectionFormat="multi",type="array",
     *    @SWG\Items(
     *             type="integer",
     *             format="int32"
     *   ),uniqueItems=true),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="post_id", type="string", example="49", description="记录id"),
     *                  @SWG\Property( property="activity_id", type="string", example="36", description="活动ID"),
     *                  @SWG\Property( property="user_id", type="string", example="20337", description="用户id "),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespone") ) )
     * )
     */
    public function deleteTopic(Request $request)
    {
        $allParams = $request->all('topic_id');
        $authInfo  = app('auth')->user()->get();
        $user_id   = 0;
        if($authInfo && $authInfo['operator_id']){
            $user_id=$authInfo['operator_id'];
        }
        else if(env('APP_ENV')=='local'){
            $user_id=$allParams['user_id']??0;
        }
        if (!($user_id ?? 0)) {
           // throw new ResourceException('未登录不可以审核笔记');
        }
        $params=$allParams;
        if($params['topic_id']??null){
            //$params['topics']=implode(',',$params['topics']);
        }
        else{
            throw new ResourceException('topic_id参数不能为空');
        }
        //$params['post_id'] =  1;
        //$params['status'] =  1;
        //查询活动信息
        $topicService = new TopicService();
        $result = $topicService->deleteBy(['topic_id'=>$params['topic_id']]);
        if($result['topic_id']??null){
        }
        //ksort($result);
        /*发送消息-免费的发，收费的付款后发*/
        /*
        if (!$activityinfo['need_fee']) {
            //收费的不发
            $messageService->sendMassage($filter['company_id'], $result['post_id'], 'yuyueAdd');
        } 
        */
        /**/
        $params['message'] = '删除成功';
        return $this->response->array($params);
    }
    /**
     * @SWG\Definition(
     *     definition="TopicInfo",
     *     description="笔记信息",
     *     type="object",
     *     @SWG\Property( property="topic_id", type="string", example="48", description="话题id"),
    *                          @SWG\Property( property="user_id", type="string", example="36", description="用户ID"),
    *                          @SWG\Property( property="mobile", type="string", example="18612345678", description="用户手机号"),
    *                          @SWG\Property( property="topic_name", type="string", example="我的第一个超话", description="话题名称"),
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
