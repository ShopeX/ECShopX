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
namespace WsugcBundle\Http\FrontApi\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use Dingo\Api\Exception\ResourceException;
use Dingo\Api\Exception\StoreResourceFailedException;

use WsugcBundle\Services\TopicService;
use WsugcBundle\Services\SettingService;
use WsugcBundle\Services\ContentCheckService;
use WsugcBundle\Services\PostService;
class TopicController extends Controller
{
     /**
     * @SWG\Post(
     *     path="/h5app/wxapp/ugc/topic/create",
     *     summary="创建话题",
     *     tags={"话题"},
     *     description="创建话题",
     *     operationId="createTopic",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=false, type="string"),
     *     @SWG\Parameter( name="user_id", in="formData", description="用户id", required=false, type="integer"),
     *     @SWG\Parameter( name="topic_name", in="formData", description="话题名称", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="topic_id", type="string", example="49", description="记录id"),
     *                  @SWG\Property( property="activity_id", type="string", example="36", description="活动ID"),
     *                  @SWG\Property( property="user_id", type="string", example="20337", description="用户id "),
     *                  @SWG\Property( property="mobile", type="string", example="17521302310", description="手机号"),
     *                  @SWG\Property( property="wxapp_appid", type="string", example="", description="会员小程序appid"),
     *                  @SWG\Property( property="open_id", type="string", example="", description="用户open_id"),
     *                  @SWG\Property( property="status", type="string", example="pending", description="状态"),
     *                  @SWG\Property( property="content", type="array",
     *                              @SWG\Items( type="object",
     *                                  @SWG\Property( property="title", type="string", example="区块一标题", description="名称"),
     *                                  @SWG\Property( property="sort", type="string", example="1", description="排序，数字越大越靠前"),
     *                                  @SWG\Property( property="formdata", type="array",
     *                                      @SWG\Items( type="object",
     *                                          @SWG\Property( property="id", type="string", example="36", description="ID"),
     *                                          @SWG\Property( property="company_id", type="string", example="1", description="公司id "),
     *                                          @SWG\Property( property="field_title", type="string", example="团长姓名", description="表单项标题(中文描述)"),
     *                                          @SWG\Property( property="field_name", type="string", example="username", description="表单项英文名称(英文或拼音描述),唯一标示"),
     *                                          @SWG\Property( property="form_element", type="string", example="text", description="表单元素,text:文本,textarea:文本域,select:选择框,radio:单选,checkbox:多选框,date:日期选择,time:时间选择,area:地区地址选择, image:图片上传,number:纯数字"),
     *                                          @SWG\Property( property="status", type="string", example="1", description="状态"),
     *                                          @SWG\Property( property="sort", type="string", example="1", description="排序，数字越大越靠前"),
     *                                          @SWG\Property( property="is_required", type="string", example="false", description="是否必填"),
     *                                          @SWG\Property( property="image_url", type="string", example="", description="元素配图"),
     *                                          @SWG\Property( property="options", type="string", example="null", description="表单元素为选择类时选择项（json）当form_element in (select, radio, checkbox)时，此项必填"),
     *                                          @SWG\Property( property="answer", type="string", example="吴琼", description="回答内容"),
     *                                       ),
     *                                  ),
     *                               ),
     *                          ),
     *                  @SWG\Property( property="reason", type="string", example="null", description="审核不通过原因"),
     *                  @SWG\Property( property="created", type="string", example="1612441632", description="创建时间"),
     *                  @SWG\Property( property="updated", type="string", example="1612441632", description=" 修改时间"),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespones") ) )
     * )
     */
    public function createTopic(Request $request)
    {
        $allParams = $request->all('topic_id','topic_name','user_id','company_id');
        $authInfo = $request->get('auth');
        $user_id=0;
        if($authInfo && $authInfo['user_id']){
            $user_id=$authInfo['user_id'];
        }
        else if(env('APP_ENV')=='local'){
            $user_id=$allParams['user_id']??0;
        }
        if (!($user_id ?? 0)) {
            throw new ResourceException('只有会员才可以创建话题');
        }
      
        $params=$allParams;
        if(!$params['topic_name']){
            throw new ResourceException('话题名称不能为空');
        }
        $topicService = new TopicService();
        if($topicService->entityRepository->count(['topic_name'=>$params['topic_name'], 'company_id' => $params['company_id']])>0){
            throw new ResourceException('同名话题已存在');
        }
        $params['user_id'] =  $user_id;
        $params['mobile'] = $authInfo['mobile']??'0';
        $params['company_id'] = ($authInfo['company_id']??1);
        $params['p_order']=0;
        $params['source']=1;//用户
        $params['is_top']=0;//置顶

        //机器审核
        $title_status=0;
        $postService = new PostService();
        $open_id=$postService->getOpenId($user_id,$params['company_id']);
        $contentCheckService=new ContentCheckService($params['company_id']);
        $to_verify_content=$params['topic_name'];
        if($to_verify_content){
            if($msgCheckResult=$contentCheckService->msgCheck($to_verify_content,$open_id)){
                $title_status=$msgCheckResult;
                //$params['status']=$msgCheckResult;
            }
            else{
                //机器审核不上的话，还是 待审核
                $title_status=0;
        }
        }
        else{
            $title_status=1;
        }
        $params['status']=$title_status;//先过审核
        //查询活动信息
        
        $result = $topicService->create($params);
        ksort($result);
        /*发送消息-免费的发，收费的付款后发*/
        /*
        if (!$activityinfo['need_fee']) {
            //收费的不发
            $messageService->sendMassage($filter['company_id'], $result['topic_id'], 'yuyueAdd');
        } 
        */
        /**/
        if($params['status']==1){
            $result['message'] = '创建成功';
        }
        else if($params['status']==4){
            $result['message'] = '话题违规，审核失败，请修改后重新提交';
        }
        else if($params['status']==0){
            $result['message'] = '话题已提交，人工审核中';
        }
        else{
            $result['message'] = '话题已提交，人工审核中';
        }
        return $this->response->array($result);
    }
    /**
     * @SWG\Get(
     *     path="/h5app/wxapp/ugc/topic/detail",
     *     summary="获取话题详情",
     *     tags={"话题"},
     *     description="获取话题详情",
     *     operationId="getTopicDetail",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=false, type="string"),
     *     @SWG\Parameter( name="topic_id", in="query", description="话题id", required=true, type="integer"),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespones") ) )
     * )
     */
    public function getTopicDetail(Request $request)
    {
        $authInfo = $request->get('auth');

        $result['topic_info'] = null;

        $filter = [
            'company_id' => $authInfo['company_id'],
            'topic_id' => $request->get('topic_id'),
        ];
        $topicService = new TopicService();
        $topicInfo = $topicService->getTopicDetail($filter, $authInfo['user_id']);
        if ($topicInfo) {
            $result['topic_info'] = $topicInfo;
            if($topicInfo['status']!=1){
                $result['topic_info']=[];
                $result['message']='话题的状态不是已审核';
            }
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
     *     path="/h5app/wxapp/ugc/topic/list",
     *     summary="获取话题列表",
     *     tags={"话题"},
     *     description="获取话题列表",
     *     operationId="getTopicList",
     *     @SWG\Parameter( name="topic_name", in="query", description="话题名称", required=false, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="页码", type="integer"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页条数", type="integer"),
     *     @SWG\Parameter( name="is_top", in="query", description="置顶话题", type="integer",description="是否指定话题 0:不是,1:置顶"),
     *     @SWG\Parameter( name="sort", in="query", description="排序", type="string",description="按后台拖动设置的排序：p_order asc;创建时间（最新的在前）:created desc"),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespones") ) )
     * )
     */
    public function getTopicList(Request $request)
    {
        $authInfo = $request->get('auth');
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 30);
        $authInfo = $request->get('auth');
        $filter['company_id'] = $authInfo['company_id']??1;
        if ($request->get('topic_name') != '') {
            $filter['topic_name|contains'] = $request->get('topic_name');
        }
        $topicService = new TopicService();
        $filter['enabled'] = 1;
        $filter['status'] = 1;//审核通过的才行
        $sort = $request->get('sort') ?? '';
        $orderBy = [];
        if ($sort && trim($sort)) {
            $orderByRs = explode(' ', $sort);
            $orderBy[$orderByRs[0]] = $orderByRs[1];
            $orderBy['p_order'] = 'asc';
            //$filter['start_time|gte']=time();//开始时间大于当前时间
        }
        $cols='topic_id,topic_name,user_id,p_order,created,source,status';
        $result = $topicService->getTopicList($filter, $cols, $page, $pageSize, $orderBy);

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
