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

//消息
namespace WsugcBundle\Http\FrontApi\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use Dingo\Api\Exception\ResourceException;
use Dingo\Api\Exception\StoreResourceFailedException;

use WsugcBundle\Services\MessageService;
use WsugcBundle\Services\SettingService;

class MessageController extends Controller
{
     /**
     * @SWG\Post(
     *     path="/h5app/wxapp/ugc/message/setTohasRead",
     *     summary="设置为已读(按类型，从哪个类型点进去就发起请求)",
     *     tags={"消息"},
     *     description="设置为已读",
     *     operationId="setTohasRead",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="type", in="formData", description="消息类型.system 系统消息;reply 评论笔记/回复评论;like 笔记点赞/评论点赞 favoritePost 笔记收藏;followerUser 关注 ", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="post_id", type="string", example="49", description="记录id"),
     *                  @SWG\Property( property="activity_id", type="string", example="36", description="活动ID"),
     *                  @SWG\Property( property="user_id", type="string", example="20337", description="用户id "),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespones") ) )
     * )
     */
    public function setTohasRead(Request $request)
    {
        $allParams = $request->all('type');
        $authInfo = $request->get('auth');
        $user_id=0;
        if($authInfo && $authInfo['user_id']){
            $user_id=$authInfo['user_id'];
        }
        else if(env('APP_ENV')=='local'){
            $user_id=$allParams['user_id']??0;
        }
        if (!($user_id ?? 0)) {
            throw new ResourceException('只有会员才可以设置已读');
        }
      
        $params=$allParams;
        if(!$params['type']){
            throw new ResourceException('消息类型type不能为空');
        }
        $messageService = new MessageService();
        $data['hasread']=1;//用户
        $data['to_user_id']=$user_id;//用户
        $data['type']=$params['type'];//用户
        $result = $messageService->entityRepository->updateBy(['type'=>$params['type'],'to_user_id'=>$user_id],$data);
        ksort($data);
        $data['message'] = '设置已读成功';
        return $this->response->array($data);
    }
      /**
     * @SWG\Get(
     *     path="/h5app/wxapp/ugc/message/dashboard",
     *     summary="消息桌面",
     *     tags={"消息"},
     *     description="消息桌面",
     *     operationId="getMessageDashboard",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespones") ) )
     * )
     */
    public function getMessageDashboard(Request $request)
    {
        $authInfo = $request->get('auth');
        $result['dashboard_info'] = null;
        $filter = [
            'company_id' => $authInfo['company_id'],
            'user_id' => $authInfo['user_id'],
        ];
        $messageService = new MessageService();
        $messageInfo = $messageService->getDashBoard($filter);
        if ($messageInfo) {
            $result['message_info'] = $messageInfo;
        }
        ksort($result);
        return $this->response->array($result);
    }
    /**
     * @SWG\Get(
     *     path="/h5app/wxapp/ugc/message/detail",
     *     summary="获取消息详情",
     *     tags={"消息"},
     *     description="获取消息详情",
     *     operationId="getMessageDetail",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=false, type="string"),
     *     @SWG\Parameter( name="message_id", in="query", description="消息id", required=true, type="integer"),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespones") ) )
     * )
     */
    public function getMessageDetail(Request $request)
    {
        $authInfo = $request->get('auth');

        $result['message_info'] = null;

        $filter = [
            'company_id' => $authInfo['company_id'],
            'message_id' => $request->get('message_id'),
        ];
        $messageService = new MessageService();
        $messageInfo = $messageService->getMessageDetail($filter, $authInfo['user_id']);
        if ($messageInfo) {
            $result['message_info'] = $messageInfo;
        }
        ksort($result);
        return $this->response->array($result);
    }
    /**
     * @SWG\Get(
     *     path="/h5app/wxapp/ugc/message/list",
     *     summary="获取消息列表",
     *     tags={"消息"},
     *     description="获取消息列表",
     *     operationId="getTopicList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="type", in="query", description="消息类型.system 系统消息;reply 评论笔记/回复评论;like 笔记点赞/评论点赞 favoritePost 笔记收藏;followerUser 关注 ", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="post_id", type="string", example="49", description="记录id"),
     *                  @SWG\Property( property="activity_id", type="string", example="36", description="活动ID"),
     *                  @SWG\Property( property="user_id", type="string", example="20337", description="用户id "),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespones") ) )
     * )
     */
    public function getMessageList(Request $request)
    {
        // IDX: 2367340174
        $authInfo = $request->get('auth');
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 30);
        $authInfo = $request->get('auth');
        $filter['company_id'] = $authInfo['company_id']??1;
        if($request->get('type')??null){
            $filter['type']=$request->get('type');
        }
        $filter['to_user_id']= $authInfo ['user_id']??0;
        $messageService = new MessageService();
        // $filter['enabled'] = 1;
        $sort = $request->get('sort') ?? '';
        $orderBy = [];
        if ($sort && trim($sort)) {
            $orderByRs = explode(' ', $sort);
            $orderBy[$orderByRs[0]] = $orderByRs[1];
            $orderBy['created'] = 'desc';
            //$filter['start_time|gte']=time();//开始时间大于当前时间
        }
        $cols='*';
        //print_r($filter);exit;
        $result = $messageService->getMessageList($filter, $cols, $page, $pageSize, $orderBy);
        ksort($result);
        return $this->response->array($result);
    }
    /**
     * @SWG\Definition(
     *     definition="MessageInfo",
     *     description="消息",
     *     type="object",
     *     @SWG\Property( property="message_id", type="string", example="48", description="消息id"),
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
