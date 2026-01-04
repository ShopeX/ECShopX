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
namespace WsugcBundle\Http\FrontApi\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use Dingo\Api\Exception\ResourceException;
use Dingo\Api\Exception\StoreResourceFailedException;

use WsugcBundle\Services\TagService;
use WsugcBundle\Services\ContentCheckService;
use WsugcBundle\Services\PostService;

class TagController extends Controller
{
     /**
     * @SWG\Post(
     *     path="/h5app/wxapp/ugc/tag/create",
     *     summary="发布图片标签",
     *     tags={"图片标签"},
     *     description="发布图片标签",
     *     operationId="createTag",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=false, type="string"),
     *     @SWG\Parameter( name="user_id", in="formData", description="用户id", required=false, type="integer"),
     *     @SWG\Parameter( name="tag_name", in="formData", description="图片标签名称", required=false, type="string"),
        
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespones") ) )
     * )
     */
    public function createTag(Request $request)
    {
        $allParams = $request->all('tag_id','tag_name','user_id');
        $authInfo = $request->get('auth');
        $user_id=0;
        if($authInfo && $authInfo['user_id']){
            $user_id=$authInfo['user_id'];
        }
        else if(env('APP_ENV')=='local'){
            $user_id=$allParams['user_id']??0;
        }
        if (!($user_id ?? 0)) {
            throw new ResourceException('只有会员才可以发布图片标签');
        }

      
        $params=$allParams;
        $tagService = new TagService();
        if(!$params['tag_name']){
            throw new ResourceException('标签名称不能为空');
        }
        if($tagService->entityRepository->count(['tag_name'=>$params['tag_name']])>0){
            throw new ResourceException('同名标签已存在');
        }
        $params['user_id'] =  $user_id;
        $params['mobile'] = $authInfo['mobile']??'0';
        $params['company_id'] = $authInfo['company_id']??1;
        $params['p_order']=0;

        //机器审核
        $title_status=0;
        $postService = new PostService();
        $open_id=$postService->getOpenId($user_id,$params['company_id']);
        $contentCheckService=new ContentCheckService($params['company_id']);
        $to_verify_content=$params['tag_name'];
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
        $which_item='标签';
        if($params['status']==1){
            $result['message'] = '创建成功';
        }
        else if($params['status']==4){
            $result['message'] = $which_item.'违规，审核失败，请修改后重新提交';
        }
        else if($params['status']==0){
            $result['message'] = $which_item.'已提交，人工审核中';
        }
        else{
            $result['message'] = $which_item.'已提交，人工审核中';
        }
        return $this->response->array($result);
    }
    /**
     * @SWG\Get(
     *     path="/h5app/wxapp/ugc/tag/detail",
     *     summary="获取图片标签详情",
     *     tags={"图片标签"},
     *     description="获取图片标签详情",
     *     operationId="getTagDetail",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=false, type="string"),
     *     @SWG\Parameter( name="tag_id", in="query", description="图片标签id", required=true, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="activeing", description="活动状态"),
     *                  @SWG\Property( property="activity_info", type="object",
     *                          @SWG\Property( property="activity_id", type="string", example="28", description="活动ID"),
     *                          @SWG\Property( property="temp_id", type="string", example="15", description="表单模板id"),
     *                          @SWG\Property( property="activity_name", type="string", example="苹果新品预售报名", description="活动名称"),
     *                          @SWG\Property( property="start_time", type="string", example="1586361600", description="活动开始时间"),
     *                          @SWG\Property( property="end_time", type="string", example="1586620799", description="活动结束时间"),
     *                          @SWG\Property( property="join_limit", type="string", example="9", description="可参与次数"),
     *                          @SWG\Property( property="is_sms_notice", type="string", example="false", description="是否短信通知"),
     *                          @SWG\Property( property="is_wxapp_notice", type="string", example="false", description="是否小程序模板通知"),
     *                          @SWG\Property( property="created", type="string", example="1586495521", description="创建时间"),
     *                          @SWG\Property( property="updated", type="string", example="1586495527", description="修改时间"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="formdata", type="object",
     *                                  @SWG\Property( property="id", type="string", example="15", description="ID"),
     *                                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                                  @SWG\Property( property="tem_name", type="string", example="超全的模板", description="表单模板名称"),
     *                                  @SWG\Property( property="status", type="string", example="1", description="状态"),
     *                                  @SWG\Property( property="form_style", type="string", example="single", description="表单关键指数, single:单页问卷, multiple:多页问卷"),
     *                                  @SWG\Property( property="header_link_title", type="string", example="XX新品预售报名", description="头部文字"),
     *                                  @SWG\Property( property="header_title", type="string", example="帮助公众号获取用户信息，进行用户管理", description="头部文字内容"),
     *                                  @SWG\Property( property="bottom_title", type="string", example="苹果", description="表单关键指数"),
     *                                  @SWG\Property( property="key_index", type="array",
     *                                      @SWG\Items( type="string", example="undefined", description="key_index"),
     *                                  ),
     *                                  @SWG\Property( property="tem_type", type="string", example="ask_answer_paper", description="表单模板类型；ask_answer_paper：问答考卷，basic_entry：基础录入"),
     *                                  @SWG\Property( property="content", type="array",
     *                                      @SWG\Items( type="object",
     *                                          @SWG\Property( property="title", type="string", example="", description="标题"),
     *                                          @SWG\Property( property="sort", type="string", example="1", description="排序，数字越大越靠前"),
     *                                          @SWG\Property( property="formdata", type="array",
     *                                              @SWG\Items( type="object",
     *                                                  @SWG\Property( property="id", type="string", example="13", description="ID"),
     *                                                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                                                  @SWG\Property( property="field_title", type="string", example="指标3", description="表单项标题(中文描述)"),
     *                                                  @SWG\Property( property="field_name", type="string", example="zhibiao3", description="表单项英文名称(英文或拼音描述),唯一标示"),
     *                                                  @SWG\Property( property="form_element", type="string", example="number", description="表单元素,text:文本,textarea:文本域,select:选择框,radio:单选,checkbox:多选框,date:日期选择,time:时间选择,area:地区地址选择, image:图片上传,number:纯数字"),
     *                                                  @SWG\Property( property="status", type="string", example="1", description="自行更改字段描述"),
     *                                                  @SWG\Property( property="sort", type="string", example="1", description="排序，数字越大越靠前"),
     *                                                  @SWG\Property( property="is_required", type="string", example="false", description="是否必填"),
     *                                                  @SWG\Property( property="image_url", type="string", example="null", description="元素配图"),
     *                                                  @SWG\Property( property="options", type="string", example="null", description="表单元素为选择类时选择项（json）当form_element in (select, radio, checkbox)时，此项必填"),
     *                                               ),
     *                                          ),
     *                                       ),
     *                                  ),
     *                          ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespones") ) )
     * )
     */
    public function getTagDetail(Request $request)
    {
        $authInfo = $request->get('auth');

        $result['tag_info'] = null;

        $filter = [
            'company_id' => $authInfo['company_id'],
            'tag_id' => $request->get('tag_id'),
        ];
        $tagService = new TagService();
        $postInfo = $tagService->getTagDetail($filter, $authInfo['user_id']);
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
     *     path="/h5app/wxapp/ugc/tag/list",
     *     summary="获取图片标签列表",
     *     tags={"图片标签"},
     *     description="获取图片标签列表",
     *     operationId="getTagList",
     *     @SWG\Parameter( name="tag_name", in="query", description="标签名称", required=false, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="页码", type="integer"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页条数", type="integer"),
     *     @SWG\Parameter( name="sort", in="query", description="排序", type="string",description="时间(最新的在前)：created desc"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="activeing", description="活动状态"),
     *                  @SWG\Property( property="activity_info", type="object",
     *                          @SWG\Property( property="activity_id", type="string", example="28", description="活动ID"),
     *                          @SWG\Property( property="temp_id", type="string", example="15", description="表单模板id"),
     *                          @SWG\Property( property="activity_name", type="string", example="苹果新品预售报名", description="活动名称"),
     *                          @SWG\Property( property="start_time", type="string", example="1586361600", description="活动开始时间"),
     *                          @SWG\Property( property="end_time", type="string", example="1586620799", description="活动结束时间"),
     *                          @SWG\Property( property="join_limit", type="string", example="9", description="可参与次数"),
     *                          @SWG\Property( property="is_sms_notice", type="string", example="false", description="是否短信通知"),
     *                          @SWG\Property( property="is_wxapp_notice", type="string", example="false", description="是否小程序模板通知"),
     *                          @SWG\Property( property="created", type="string", example="1586495521", description="创建时间"),
     *                          @SWG\Property( property="updated", type="string", example="1586495527", description="修改时间"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="formdata", type="object",
     *                                  @SWG\Property( property="id", type="string", example="15", description="ID"),
     *                                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                                  @SWG\Property( property="tem_name", type="string", example="超全的模板", description="表单模板名称"),
     *                                  @SWG\Property( property="status", type="string", example="1", description="状态"),
     *                                  @SWG\Property( property="form_style", type="string", example="single", description="表单关键指数, single:单页问卷, multiple:多页问卷"),
     *                                  @SWG\Property( property="header_link_title", type="string", example="XX新品预售报名", description="头部文字"),
     *                                  @SWG\Property( property="header_title", type="string", example="帮助公众号获取用户信息，进行用户管理", description="头部文字内容"),
     *                                  @SWG\Property( property="bottom_title", type="string", example="苹果", description="表单关键指数"),
     *                                  @SWG\Property( property="key_index", type="array",
     *                                      @SWG\Items( type="string", example="undefined", description="key_index"),
     *                                  ),
     *                                  @SWG\Property( property="tem_type", type="string", example="ask_answer_paper", description="表单模板类型；ask_answer_paper：问答考卷，basic_entry：基础录入"),
     *                                  @SWG\Property( property="content", type="array",
     *                                      @SWG\Items( type="object",
     *                                          @SWG\Property( property="title", type="string", example="", description="标题"),
     *                                          @SWG\Property( property="sort", type="string", example="1", description="排序，数字越大越靠前"),
     *                                          @SWG\Property( property="formdata", type="array",
     *                                              @SWG\Items( type="object",
     *                                                  @SWG\Property( property="id", type="string", example="13", description="ID"),
     *                                                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                                                  @SWG\Property( property="field_title", type="string", example="指标3", description="表单项标题(中文描述)"),
     *                                                  @SWG\Property( property="field_name", type="string", example="zhibiao3", description="表单项英文名称(英文或拼音描述),唯一标示"),
     *                                                  @SWG\Property( property="form_element", type="string", example="number", description="表单元素,text:文本,textarea:文本域,select:选择框,radio:单选,checkbox:多选框,date:日期选择,time:时间选择,area:地区地址选择, image:图片上传,number:纯数字"),
     *                                                  @SWG\Property( property="status", type="string", example="1", description="自行更改字段描述"),
     *                                                  @SWG\Property( property="sort", type="string", example="1", description="排序，数字越大越靠前"),
     *                                                  @SWG\Property( property="is_required", type="string", example="false", description="是否必填"),
     *                                                  @SWG\Property( property="image_url", type="string", example="null", description="元素配图"),
     *                                                  @SWG\Property( property="options", type="string", example="null", description="表单元素为选择类时选择项（json）当form_element in (select, radio, checkbox)时，此项必填"),
     *                                               ),
     *                                          ),
     *                                       ),
     *                                  ),
     *                          ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespones") ) )
     * )
     */
    public function getTagList(Request $request)
    {
        $authInfo = $request->get('auth');
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 30);
        $authInfo = $request->get('auth');
        $filter['company_id'] = $authInfo['company_id'];
        if ($request->get('tag_name') != '') {
            $filter['tag_name|contains'] = $request->get('tag_name');
        }
        $tagService = new TagService();
        $filter['enabled'] = 1;
        $filter['status'] = 1;
        $sort = $request->get('sort') ?? '';
        $orderBy = [];
        if ($sort && trim($sort)) {
            $orderByRs = explode(' ', $sort);
            $orderBy[$orderByRs[0]] = $orderByRs[1];
            $orderBy['p_order'] = 'asc';
            //$filter['start_time|gte']=time();//开始时间大于当前时间
        }
        $result = $tagService->getTagList($filter, '*', $page, $pageSize, $orderBy);
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
