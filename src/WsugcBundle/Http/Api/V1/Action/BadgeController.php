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

//角标
namespace WsugcBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use Dingo\Api\Exception\ResourceException;
use Dingo\Api\Exception\StoreResourceFailedException;

use WsugcBundle\Services\BadgeService;
use WsugcBundle\Services\SettingService;
use WsugcBundle\Services\PostService;

class BadgeController extends Controller
{
     /**
     * @SWG\Post(
     *     path="/ugc/badge/create",
     *     summary="创建角标",
     *     tags={"角标"},
     *     description="创建/更新角标",
     *     operationId="createBadge",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=false, type="string"),
     *     @SWG\Parameter( name="badge_id", in="formData", description="角标id。更新角标时必传", required=false, type="string"),
     *     @SWG\Parameter( name="badge_name", in="formData", description="角标名称", required=false, type="string"),
     *     @SWG\Parameter( name="badge_memo", in="formData", description="角标备注", required=false, type="string"),
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
    public function createBadge(Request $request)
    {
        // 456353686f7058
        $allParams = $request->all('badge_id','badge_name','badge_memo','user_id','company_id');
        $authInfo = app('auth')->user()->get();
        $user_id=0;
        if($authInfo && $authInfo['operator_id']){
            $user_id=$authInfo['operator_id'];
            $company_id=$authInfo['company_id'];
        }
        else if(env('APP_ENV')=='local'){
            $user_id=$allParams['user_id']??0;
            $company_id=1;
        }
        if (!($user_id ?? 0)) {
        }
      
        $params=$allParams;
        if(!$params['badge_name']){
            throw new ResourceException('角标名称不能为空');
        }
        $badgeService = new BadgeService();
        if($existBadgeList=$badgeService->entityRepository->lists(['badge_name'=>$params['badge_name']])){
            if($existBadgeList['list']??null){
                if($existBadgeList['list'][0]['badge_id']!=($params['badge_id']??null)){
                    throw new ResourceException('同名角标已存在');
                }
            }
            else{
                //
            }
        }
        $params['operator_id'] =  $user_id;
        $params['user_id'] =  0;
        $params['mobile'] = $authInfo['mobile']??'';
        $params['company_id'] = ($authInfo['company_id']??1);
        $params['p_order']=0;
        $params['source']=2;//管理员添加的
        $params['is_top']=0;//置顶
        $params['status']=1;//无需审核

        $action='add';
        if($params['badge_id']??null){
            $result = $badgeService->saveData($params,['badge_id'=>$params['badge_id']]);
            $result['badge_id']=$params['badge_id'];
            $action='edit';
        }
        else{
            $result = $badgeService->saveData($params);
        }

        // $result = $badgeService->create($params);
        ksort($result);
        /*发送消息-免费的发，收费的付款后发*/
        /*
        if (!$activityinfo['need_fee']) {
            //收费的不发
            $messageService->sendMassage($filter['company_id'], $result['badge_id'], 'yuyueAdd');
        } 
        */
        /**/
        $result['message'] = (($action=='edit'?'更新':'创建').'角标成功');
        return $this->response->array($result);
    }
    /**
     * @SWG\Post(
     *     path="/ugc/badge/verify",
     *     summary="审核角标",
     *     tags={"角标"},
     *     description="审核角标",
     *     operationId="createTag",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=false, type="string"),
     *     @SWG\Parameter( name="status", in="formData", description="审核状态 0待审核,1审核通过,2机器拒绝,3待人工审核,4人工拒绝", required=true, type="integer"),
     *     @SWG\Parameter( name="refuse_reason", in="formData", description="拒绝原因", required=false, type="string"),
     *     @SWG\Parameter( name="badge_id[]", in="formData", description="角标id,数组,提交参数的格式为form表单类型,比如 badge_id[]:2 badge_id[]:3", required=true,collectionFormat="multi",type="array",
     *    @SWG\Items(
     *             type="integer",
     *             format="int32"
     *   ),uniqueItems=true),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="badge_id", type="string", example="49", description="记录id"),
     *                  @SWG\Property( property="user_id", type="string", example="20337", description="用户id "),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespone") ) )
     * )
     */
    public function verifyBadge(Request $request)
    {
        $allParams =  $request->all('badge_id','status','refuse_reason');
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
        if($params['badge_id']??null){
            //$params['badges']=implode(',',$params['badges']);
        }
        else{
            throw new ResourceException('badge_id参数不能为空');
        }
      /*   if($params['status']==4 && (($params['refuse_reason']??null)=='')){
            throw new ResourceException('人工拒绝原因不能为空');
        } */
        $badgeService = new BadgeService();
        $params['manual_refuse_reason']=$params['refuse_reason']??'';
        $params['manual_verify_time']=time();
        $data=$params;
        unset($data['refuse_reason']);

        unset($data['badge_id']);
        $result = $badgeService->entityRepository->updateBy(['badge_id'=>$params['badge_id']],$data);
        if($result['badge_id']??null){
           
        }
        $params['message'] = '审核成功';
        return $this->response->array($params);
    }
    /**
     * @SWG\Post(
     *     path="/ugc/badge/settop",
     *     summary="角标置顶",
     *     tags={"角标"},
     *     description="角标置顶",
     *     operationId="createTag",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=false, type="string"),
     *     @SWG\Parameter( name="badge_id[]", in="formData", description="角标id,数组,提交参数的格式为form表单类型,比如 badge_id[]:2 badge_id[]:3", required=true,collectionFormat="multi",type="array",
     *    @SWG\Items(
     *             type="integer",
     *             format="int32"
     *   ),uniqueItems=true),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="badge_id", type="string", example="49", description="记录id"),
     *                  @SWG\Property( property="user_id", type="string", example="20337", description="用户id "),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespone") ) )
     * )
     */
    public function setTopBadge(Request $request)
    {
        $allParams =  $request->all('badge_id');
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
        if($params['badge_id']??null){
            //$params['badges']=implode(',',$params['badges']);
        }
        else{
            throw new ResourceException('badge_id参数不能为空');
        }
        $badgeService = new BadgeService();
        $data=$params;
        //置顶角标的p_order排序，最上面的在最上面
        $allUpdate=[];
        foreach($data['badge_id'] as $k=>$v){
            $updateData['is_top']=1;
            $updateData['p_order']=(count($data['badge_id'])-$k)*-1;//负数
            $result = $badgeService->entityRepository->updateOneBy(['badge_id'=>$v],$updateData);
            $allUpdate[]=$updateData;
        }
        if($result['badge_id']??null){
           
        }
        $lastData['allUpdate']=$allUpdate;
        $lastData['message'] = '置顶成功';
        return $this->response->array($lastData);
    }
    /**
     * @SWG\Get(
     *     path="/ugc/badge/detail",
     *     summary="获取角标详情",
     *     tags={"角标"},
     *     description="获取角标详情",
     *     operationId="getBadgeDetail",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=false, type="string"),
     *     @SWG\Parameter( name="badge_id", in="query", description="角标id", required=true, type="integer"),
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
    public function getBadgeDetail(Request $request)
    {
        $authInfo = app('auth')->user()->get();

        $result['badge_info'] = null;

        $filter = [
            'company_id' => $authInfo['company_id']??1,
            'badge_id' => $request->get('badge_id'),
        ];
        $badgeService = new BadgeService();
        $badgeInfo = $badgeService->getBadgeDetail($filter, $authInfo['user_id']??0);
        if ($badgeInfo) {
            $result['badge_info'] = $badgeInfo;
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
     *     path="/ugc/badge/list",
     *     summary="获取角标列表",
     *     tags={"角标"},
     *     description="获取角标列表",
     *     operationId="getBadgeList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=false, type="string"),
     *     @SWG\Parameter( name="badge_name", in="query", description="角标名称", required=false, type="string"),
     *     @SWG\Parameter( name="nickname", in="query", description="昵称", type="string"),
     *     @SWG\Parameter( name="mobile", in="query", description="手机号", type="string"),
     *     @SWG\Parameter( name="badge_memo", in="query", description="角标备注", required=false, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="页码", type="integer"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页条数", type="integer"),
     *     @SWG\Parameter( name="is_top", in="query", description="置顶角标", type="integer",description="是否指定角标 0:不是,1:置顶"),
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
    public function getBadgeList(Request $request)
    {
        // 456353686f7058
        $postService = new PostService();

        $authInfo = app('auth')->user()->get();
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 30);
        $authInfo = app('auth')->user()->get();
        $filter['company_id'] = $authInfo['company_id']??1;
        if ($request->get('badge_name') != '') {
            $filter['badge_name|contains'] = $request->get('badge_name');
        }
        if ($request->get('badge_memo') != '') {
            $filter['badge_memo|contains'] = $request->get('badge_memo');
        }
        if ($request->get('is_top') != '') {
            $filter['is_top'] = $request->get('is_top');
        }
        //昵称
        if ($request->get('nickname') != '') {
            $filter['user_id']=$postService->getUserIdByNickName($request->get('nickname') );
        }
        //手机号
        if ($request->get('mobile') != '') {
            $filter['user_id']=$postService->getUserIdByMobile($request->get('mobile'));
        }
        $badgeService = new BadgeService();
        //$filter['enabled'] = 1;
        $sort = $request->get('sort') ?? '';
        $orderBy = [];
        if ($sort && trim($sort)) {
            $orderByRs = explode(' ', $sort);
            $orderBy[$orderByRs[0]] = $orderByRs[1];
            $orderBy['p_order'] = 'asc';
            //$filter['start_time|gte']=time();//开始时间大于当前时间
        }
        $cols='badge_id,badge_name,badge_memo,user_id,p_order,created,updated,source,is_top,status,company_id';
        $result = $badgeService->getBadgeList($filter, $cols, $page, $pageSize, $orderBy);

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
     *     path="/ugc/badge/delete",
     *     summary="删除角标",
     *     tags={"角标"},
     *     description="删除角标",
     *     operationId="createPost",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=false, type="string"),
     *     @SWG\Parameter( name="badge_id[]", in="formData", description="角标badge_id,数组,提交参数的格式为form表单类型,比如 badge_id[]:2 badge_id[]:3", required=true,collectionFormat="multi",type="array",
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
    public function deleteBadge(Request $request)
    {
        $allParams = $request->all('badge_id');
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
        if($params['badge_id']??null){
            //$params['badges']=implode(',',$params['badges']);
        }
        else{
            throw new ResourceException('badge_id参数不能为空');
        }
        //$params['post_id'] =  1;
        //$params['status'] =  1;
        //查询活动信息
        $badgeService = new BadgeService();
        $result = $badgeService->deleteBy(['badge_id'=>$params['badge_id']]);
        if($result['badge_id']??null){
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
     *     definition="BadgeInfo",
     *     description="笔记信息",
     *     type="object",
     *     @SWG\Property( property="badge_id", type="string", example="48", description="角标id"),
    *                          @SWG\Property( property="user_id", type="string", example="36", description="用户ID"),
    *                          @SWG\Property( property="mobile", type="string", example="18612345678", description="用户手机号"),
    *                          @SWG\Property( property="badge_name", type="string", example="我的第一个超话", description="角标名称"),
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
