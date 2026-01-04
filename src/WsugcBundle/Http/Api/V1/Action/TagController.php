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

//图片标签
namespace WsugcBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use Dingo\Api\Exception\ResourceException;
use Dingo\Api\Exception\StoreResourceFailedException;

use WsugcBundle\Services\TagService;
use WsugcBundle\Services\PostService;

class TagController extends Controller
{
     /**
     * @SWG\Post(
     *     path="/ugc/tag/create",
     *     summary="创建图片标签",
     *     tags={"图片标签"},
     *     description="发布图片标签",
     *     operationId="createTag",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=false, type="string"),
     *     @SWG\Parameter( name="tag_name", in="formData", description="图片标签名称", required=false, type="string"),
    *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="tag_id", type="string", example="49", description="记录id"),
     *                  @SWG\Property( property="user_id", type="string", example="20337", description="用户id "),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespone") ) )
     * )
     */
    public function createTag(Request $request)
    {
        // ShopEx EcShopX Business Logic Layer
        $allParams = $request->all('tag_id','tag_name','user_id');
        $authInfo = app('auth')->user()->get();
        $user_id=0;
        $user_id=0;
        if($authInfo && $authInfo['operator_id']){
            $user_id=$authInfo['operator_id'];
        }
        else if(env('APP_ENV')=='local'){
            $user_id=$allParams['user_id']??0;
        }
        if (!($user_id ?? 0)) {
            //throw new ResourceException('未登录不可以发布笔记');
        }
        $params=$allParams;
        if(!$params['tag_name']){
            throw new ResourceException('tag名称不能为空');
        }
        $tagService = new TagService();
        if($tagService->entityRepository->count(['tag_name'=>$params['tag_name']])>0){
            throw new ResourceException('同名标签已存在');
        }
        $params['user_id'] =  0;
        $params['operator_id'] =  $user_id;
        $params['mobile'] = $authInfo['mobile']??'0';
        $params['company_id'] = $authInfo['company_id']??1;
        $params['p_order']=0;
        $params['source']=2;//管理员发的
        //查询活动信息
        $tagService = new TagService();
        $result = $tagService->create($params);
        ksort($result);
        /*发送消息-免费的发，收费的付款后发*/
        /*
        if (!$activityinfo['need_fee']) {
            //收费的不发
            $messageService->sendMassage($filter['company_id'], $result['tag_id'], 'yuyueAdd');
        } 
        */
        /**/
        $result['message'] = '创建成功';
        return $this->response->array($result);
    }
        /**
     * @SWG\Post(
     *     path="/ugc/tag/verify",
     *     summary="审核图片标签",
     *     tags={"图片标签"},
     *     description="审核图片标签",
     *     operationId="createTag",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=false, type="string"),
     *     @SWG\Parameter( name="status", in="formData", description="审核状态 0待审核,1审核通过,2机器拒绝,3待人工审核,4人工拒绝", required=true, type="integer"),
     *     @SWG\Parameter( name="refuse_reason", in="formData", description="拒绝原因", required=false, type="string"),
     *     @SWG\Parameter( name="tag_id[]", in="formData", description="笔记tag_id,数组,提交参数的格式为form表单类型,比如 tag_id[]:2 tag_id[]:3", required=true,collectionFormat="multi",type="array",
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
    public function verifyTag(Request $request)
    {
        $allParams = $request->all('tag_id','status','refuse_reason');
        $authInfo = app('auth')->user()->get();
        $user_id=0;
        if($authInfo && $authInfo['operator_id']){
            $user_id=$authInfo['operator_id'];
        }
        else if(env('APP_ENV')=='local'){
            $user_id=$allParams['user_id']??0;
        }
        if (!($user_id ?? 0)) {
        }
        $params=$allParams;
        if($params['status']??null){
            
        }
        else{
            throw new ResourceException('status参数不能为空');
        }
        if($params['tag_id']??null){
            //$params['topics']=implode(',',$params['topics']);
        }
        else{
            throw new ResourceException('tag_id参数不能为空');
        }
/*         if($params['status']==4 && (($params['refuse_reason']??null)=='')){
            //
            throw new ResourceException('人工拒绝原因不能为空');
        } */
        // $params['post_id'] =  1;
        // $params['status'] =  1;
        // 查询活动信息
        $postService = new TagService();
        $params['manual_refuse_reason']=$params['refuse_reason']??'';
        $data=$params;
        $data['manual_verify_time']=time();
        unset($data['refuse_reason']);

        unset($data['tag_id']);
        $result = $postService->entityRepository->updateBy(['tag_id'=>$params['tag_id']],$data);
        if($result['tag_id']??null){
           
        }
        $params['message'] = '审核成功';
        return $this->response->array($params);
    }
    /**
     * @SWG\Get(
     *     path="/ugc/tag/detail",
     *     summary="获取图片标签详情",
     *     tags={"图片标签"},
     *     description="获取图片标签详情",
     *     operationId="getTagDetail",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=false, type="string"),
     *     @SWG\Parameter( name="tag_id", in="query", description="图片标签id", required=true, type="integer"),
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
    public function getTagDetail(Request $request)
    {
        $authInfo = app('auth')->user()->get();
        $user_id=0;
        $company_id = 0;
        if($authInfo && $authInfo['operator_id']){
            $user_id = $authInfo['operator_id'];
            $company_id = $authInfo['company_id'];
        }
        // else if(env('APP_ENV')=='local'){
        //     $user_id=$allParams['user_id']??0;
        //     $company_id=1;
        // }
        // if (!($user_id ?? 0)) {
        // }
        $result['tag_info'] = null;
        $filter = [
            'company_id' => $company_id,
            'tag_id' => $request->get('tag_id'),
        ];
        $tagService = new TagService();
        $fromAdmin=true;
        $postInfo = $tagService->getTagDetail($filter, $user_id,$fromAdmin);
        if ($postInfo) {

            $result['tag_info'] = $postInfo;
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
     *     path="/ugc/tag/list",
     *     summary="获取图片标签列表",
     *     tags={"图片标签"},
     *     description="获取图片标签列表",
     *     operationId="getTagList",
     *     @SWG\Parameter( name="tag_name", in="query", description="标签名称", required=false, type="string"),
     *     @SWG\Parameter( name="nickname", in="query", description="昵称", type="string"),
     *     @SWG\Parameter( name="mobile", in="query", description="手机号", type="string"),
     *     @SWG\Parameter( name="status", in="query", description="状态 0待审核 1已审核 4已拒绝", type="string"),
     *     @SWG\Parameter( name="source", in="query", description="来源 1用户，2官方", type="string"),
     *     @SWG\Parameter( name="page", in="query", description="页码", type="integer"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页条数", type="integer"),
     *     @SWG\Parameter( name="sort", in="query", description="排序", type="string",description="时间(最新的在前)：created desc"),
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
    public function getTagList(Request $request)
    {
        $postService = new PostService();

        $authInfo = app('auth')->user()->get();
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 30);
        // $user_id=0;
        // if($authInfo && $authInfo['operator_id']){
        //     $user_id=$authInfo['operator_id'];
        // }
        // else if(env('APP_ENV')=='local'){
        //     $user_id=$allParams['user_id']??0;
        // }
        // if (!($user_id ?? 0)) {
            //throw new ResourceException('未登录不可以发布笔记');
        // }
        $filter['company_id'] = $authInfo['company_id']??1;
        
        //tag名称
        if ($request->get('tag_name') != '') {
            $filter['tag_name|contains'] = $request->get('tag_name');
        }
        //官方、用户
        if ($request->get('source') != '') {
            $filter['source'] = $request->get('source');
        }
        //状态
        if ($request->get('status') != '') {
            $filter['status'] = $request->get('status');
        }
        //昵称
        if ($request->get('nickname') != '') {
            $filter['user_id']=$postService->getUserIdByNickName($request->get('nickname') );
        }
        //手机号
        if ($request->get('mobile') != '') {
            $filter['user_id']=$postService->getUserIdByMobile($request->get('mobile'));
        }
        $tagService = new TagService();
        $filter['enabled'] = 1;
        $sort = $request->get('sort') ?? '';
        $orderBy = [];
        if ($sort && trim($sort)) {
            $orderByRs = explode(' ', $sort);
            $orderBy[$orderByRs[0]] = $orderByRs[1];
            $orderBy['p_order'] = 'asc';
            //$filter['start_time|gte']=time();//开始时间大于当前时间
        }
        $fromAdmin=true;
        $result = $tagService->getTagList($filter, '*', $page, $pageSize, $orderBy,$fromAdmin);
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
     *     definition="TagInfo",
     *     description="图片标签信息",
     *     type="object",
     *     @SWG\Property( property="tag_id", type="string", example="48", description="图片标签id"),
    *                          @SWG\Property( property="user_id", type="string", example="36", description="用户ID"),
    *                          @SWG\Property( property="mobile", type="string", example="18612345678", description="用户手机号"),
    *                          @SWG\Property( property="tag_name", type="string", example="我的第一个图片标签", description="图片标签名称"),
    *                          @SWG\Property( property="p_order", type="integer", example="1608272078", description="排序"),
    *                          @SWG\Property( property="created", type="integer", example="1608272078", description="创建时间"),
    *                          @SWG\Property( property="updated", type="integer", example="1608272078", description="修改时间"),
    *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
    *                          @SWG\Property( property="created_text", type="string", example="2020-12-18 14:14:38", description="创建时间"),
    *                          @SWG\Property( property="updated_text", type="string", example="2020-12-18 14:14:38", description="更新时间")
     * )
     */
}
