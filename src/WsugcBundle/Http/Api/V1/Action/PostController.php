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

use WsugcBundle\Services\PostService;
use WsugcBundle\Services\SettingService;
use WsugcBundle\Services\PostTopicService;
use WsugcBundle\Services\PostBadgeService;
use WsugcBundle\Services\MessageService;

class PostController extends Controller
{
     /**
     * @SWG\Post(
     *     path="/ugc/post/create",
     *     summary="创建笔记",
     *     tags={"笔记"},
     *     description="创建笔记",
     *     operationId="createPost",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=false, type="string"),
     *     @SWG\Parameter( name="post_id", in="formData", description="帖子id", required=false, type="integer"),
     *     @SWG\Parameter( name="is_draft", in="formData", description="是否草稿箱 1草稿,0非草稿", required=false, type="integer"),
     *     @SWG\Parameter( name="is_top", in="formData", description="是否置顶。1置顶,0非置顶", required=false, type="integer"),
     *     @SWG\Parameter( name="p_order", in="formData", description="排序", required=false, type="integer"),
     *     @SWG\Parameter( name="title", in="formData", description="笔记标题", required=false, type="string"),
     *     @SWG\Parameter( name="content", in="formData", description="笔记内容", required=false, type="string"),
     *     @SWG\Parameter( name="cover", in="formData", description="封面图(相对路径; 图片笔记,传图片第一张图,视频笔记，传视频的第一帧图，图片和视频混传的，cover以视频图优先; 比如 images/1/2022/05/18/1dfb7b330d7f9d3651b52965ecb3cc02VbeRaeRgKI25GfVN4o4PqDf6sjJeQyht)", required=true, type="string"),
     *     @SWG\Parameter( name="video", in="formData", description="视频(相对路径，比如 videos/1/2022/05/18/1dfb7b330d7f9d3651b52965ecb3cc02VbeRaeRgKI25GfVN4o4PqDf6sjJeQyht)", required=false, type="string"),
     *     @SWG\Parameter( name="video_ratio", in="formData", description="视频比例", required=false, type="string"),
     *    @SWG\Parameter( name="video_place", in="formData", description="视频位置", required=false, type="string"), 
     *     @SWG\Parameter( name="images[]", in="formData", description="组图image_id,数组,提交参数的格式为form表单类型,比如 images[]:2 images[]:3", required=true,collectionFormat="multi",type="array",
     *    @SWG\Items(
     *             type="integer",
     *             format="int32"
     *   ),uniqueItems=true),
     *     @SWG\Parameter( name="image_path[]", in="formData", description="组图的图片相对地址,数组,提交参数的格式为form表单类型，比如 image_path[]:image/1/2022/05/18/1dfb7b330d7f9d3651b52965ecb3cc02VbeRaeRgKI25GfVN4o4PqDf6sjJeQyht", required=true,collectionFormat="multi",type="array",
     *    @SWG\Items(
     *             type="string",
     *             format="varchar"
     *   ),uniqueItems=true),
     *     @SWG\Parameter( name="image_tag[]", in="formData", description="图片tag信息,数组,提交参数的格式为form表单类型，比如 image_tag[]:{”image_id”:1,”proportion”:”,”ugcwidth”:”,”tags”:[{”tag_id”:1,”tag_name”:”lv”,”movearray”:{”than_x”:”1”,”than_y”:”2”,”x”:”3”,”y”:”4”}},{”tag_id”:2,”tag_name”:”gucci”,”movearray”:{”than_x”:”4”,”than_y”:”5”,”x”:”6”,”y”:”7”}}]}", required=false,collectionFormat="multi",type="array",
     *    @SWG\Items(
     *             type="string",
     *             format="varchar"
     *   ),uniqueItems=true), 
     *     @SWG\Parameter( name="topics[]", in="formData", description="关联话题topic_id,数组,提交参数的格式为form表单类型，比如 topics[]:2 topics[]:3", required=false,collectionFormat="multi",type="array",
     *    @SWG\Items(
     *             type="integer",
     *             format="int32"
     *   ),uniqueItems=true),
     *     @SWG\Parameter( name="badges[]", in="formData", description="关联话题badge_id,数组,提交参数的格式为form表单类型，比如 badges[]:2 badges[]:3", required=false,collectionFormat="multi",type="array",
     *    @SWG\Items(
     *             type="integer",
     *             format="int32"
     *   ),uniqueItems=true),
     *     @SWG\Parameter( name="goods[]", in="formData", description="推荐商品goods_id，数组", required=false,collectionFormat="multi",type="array",     
     *    @SWG\Items(
     *             type="integer",
     *             format="int32"
     *   ),uniqueItems=true),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="post_id", type="string", example="49", description="记录id"),
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
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespone") ) )
     * )
     */
    public function createPost(Request $request)
    {
        // ShopEx EcShopX Core Module
        $allParams = $request->all('post_id','user_id','title','content','cover','images','image_tag','image_path', 'topics','video','video_ratio','video_place','video_thumb','is_draft','badges','goods','is_top','p_order');
        $authInfo = app('auth')->user()->get();
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
        if(!$params['cover']){
            throw new ResourceException('封面图不能为空');
        }
        if(isset($params['images']) && empty($params['images'])){
            throw new ResourceException('images参数不能为空');
        }
        if(isset($params['topics'])){
            if (empty($params['topics'])) {
                $params['topics'] = null;
            }else {
                $params['topics'] = implode(',',$params['topics']);
            }
        }
        if(isset($params['badges'])){
            if (empty($params['badges'])) {
                $params['badges'] = null;
            }else {
                $params['badges'] = implode(',',$params['badges']);
            }
        }
        if(isset($params['goods'])){
            if (empty($params['goods'])) {
                $params['goods'] = null;
            }else {
                $params['goods'] = implode(',',$params['goods']);
            }
        }
        //图片相对路径
        if(isset($params['image_path']) && !empty($params['image_path'])){
            $params['image_path'] = implode(',',$params['image_path']);
        }
        else{
            throw new ResourceException('image_path参数不能为空');
        }
        //图片tag
        if(isset($params['image_tag'])){
            if (empty($params['image_tag'])) {
                $params['image_tag'] = null;
            }else {
                $params['image_tag'] = json_encode($params['image_tag']);
            }
        }
        $params['source'] =  '2';//官方
        //$params['user_id'] =  $user_id;
        $params['operator_id'] =  $user_id;
        $params['user_id'] =  0;//官方账号user_id=0
        $params['mobile'] = $authInfo['mobile']??'0';
        $params['company_id'] = $authInfo['company_id']??1;
        $params['enabled'] =  1;
        $params['status'] =  1;
        $params['ip']=$_SERVER['REMOTE_ADDR'];
        $params['p_order']=$params['p_order']??0;
        $tmpParamsTop=0;
        if($params['is_top']??null){
            if($params['is_top']==1){
                //置顶,p_order为-1
                $tmpParamsTop=1;
                $params['is_top']=0;//还是先等于0，后面会重置 -1;
                $params['p_order']=0;//还是先等于0，后面会重置 -1;
            }
            else{
                $params['is_top']=0;
            }
        }
        else{
            $params['is_top']=0;//不置顶的话排序是-1
          
        }
        if($params['is_top']==0){
            //不置顶的话，如果没有传p_order或p_order小于0，强制设置为0
            if(!isset($params['p_order']) || (isset($params['p_order']) && $params['p_order']<=0)){
                //没传p_oprder
                $params['p_order']=0;
            }
        }
        else{
            //置顶的话。
            //
        }
        //print_r($params['is_top']);exit;
        //查询活动信息
        $postService = new PostService();
        //创建或更新
        $action='add';
        if($params['post_id']??null){
            $result = $postService->saveData($params,['post_id'=>$params['post_id']]);
            $result['post_id']=$params['post_id'];
            $action='edit';
        }
        else{
            $result = $postService->saveData($params);
        }
        if($result['post_id']??null){
            //如果有topics,插入PostTopics关联表
            $post_id=$result['post_id'];
            if($params['topics']){
                $params['topics']=explode(',',$params['topics']);
                //已经是数组了
                $postTopicService = new PostTopicService();
                //删除
                $postTopicService->deleteBy(['post_id'=>$post_id]);
                //再插入
                foreach($params['topics'] as $k=>$v){
                    if($v){
                        //print_r($postTopicService);exit;
                        $postTopicService->create(['post_id'=>$post_id,'topic_id'=>$v,'company_id'=>($authInfo['company_id']??1)]);
                    }
               
                }
            }
            else{
                    //没有传话题数据
                  //删除
                  $postTopicService = new PostTopicService();

                  $postTopicService->deleteBy(['post_id'=>$post_id]);
                }
            //角标
            if($params['badges']??null){
                $params['badges']=explode(',',$params['badges']);
                //已经是数组了
                $postBadgeService = new PostBadgeService();
                //删除
                $postBadgeService->deleteBy(['post_id'=>$post_id]);
                //再插入
                foreach($params['badges'] as $k=>$v){
                    if($v){
                        //print_r($postBadgeService);exit;
                        $postBadgeService->create(['post_id'=>$post_id,'badge_id'=>$v,'company_id'=>($authInfo['company_id']??1)]);
                    }
               
                }
            }
            else{
                //没有传角标数据
                  //删除
                  $postBadgeService = new PostBadgeService();

                  $postBadgeService->deleteBy(['post_id'=>$post_id]);
            }
            //如果是有置顶的，话，执行置顶重新排序。最多2个笔记置顶，后面的定义前面的
            if($tmpParamsTop==1){
                $postService->updateIsTopPost($result['post_id']);
            }
        }
        ksort($result);
        /*发送消息-免费的发，收费的付款后发*/
        /*
        if (!$activityinfo['need_fee']) {
            //收费的不发
            $messageService->sendMassage($filter['company_id'], $result['post_id'], 'yuyueAdd');
        } 
        */
        /**/
        $result['message'] = (($action=='edit'?'更新':'创建').'笔记成功');
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/ugc/post/edit",
     *     summary="编辑笔记（角标，排序，置顶）",
     *     tags={"笔记"},
     *     description="编辑笔记（角标，排序，置顶）",
     *     operationId="editPost",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=false, type="string"),
     *     @SWG\Parameter( name="post_id", in="formData", description="帖子id", required=false, type="integer"),
     *     @SWG\Parameter( name="is_top", in="formData", description="是否置顶。1置顶,0非置顶", required=false, type="integer"),
     *     @SWG\Parameter( name="p_order", in="formData", description="排序", required=false, type="integer"),
     *     @SWG\Parameter( name="badges[]", in="formData", description="关联话题badge_id,数组,提交参数的格式为form表单类型，比如 badges[]:2 badges[]:3", required=false,collectionFormat="multi",type="array",
     *    @SWG\Items(
     *             type="integer",
     *             format="int32"
     *   ),uniqueItems=true),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="post_id", type="string", example="49", description="记录id"),
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
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespone") ) )
     * )
     */
    public function editPost(Request $request)
    {
        $allParams = $request->all('post_id','badges','topics','is_top','p_order');
        $authInfo = app('auth')->user()->get();
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
        if(!$params['post_id']){
            throw new ResourceException('post_id不能为空');
        }
        if($params['topics']??null){
            $params['topics']=implode(',',$params['topics']);
        }
        if($params['badges']??null){
            $params['badges']=implode(',',$params['badges']);
        }
        if($params['goods']??null){
            $params['goods']=implode(',',$params['goods']);
        }
        $params['operator_id'] =  $user_id;
        $params['p_order']=$params['p_order']??0;
        $tmpParamsTop=0;
        if($params['is_top']??null){

            if($params['is_top']==1){
                //置顶,p_order为-1
                $tmpParamsTop=1;
                $params['p_order']=0;
                $params['is_top']=0;

                //$params['p_order']=-1;
            }
            else{
                $params['is_top']=0;
            }
        }
        else{
            $params['is_top']=0;
        }
        $postService = new PostService();
        //创建或更新
        $action='add';
        if($params['post_id']??null){
            $result = $postService->saveData($params,['post_id'=>$params['post_id']]);
            $result['post_id']=$params['post_id'];
            $action='edit';
        }
        else{
            $result = $postService->saveData($params);
        }
        if($result['post_id']??null){
            //如果有topics,插入PostTopics关联表
            $post_id=$result['post_id'];
            if($params['topics']){
                $params['topics']=explode(',',$params['topics']);
                //已经是数组了
                $postTopicService = new PostTopicService();
                //删除
                $postTopicService->deleteBy(['post_id'=>$post_id]);
                //再插入
                foreach($params['topics'] as $k=>$v){
                    if($v){
                        //print_r($postTopicService);exit;
                        $postTopicService->create(['post_id'=>$post_id,'topic_id'=>$v,'company_id'=>($authInfo['company_id']??1)]);
                    }
               
                }
            }
            //角标
            if($params['badges']??null){
                $params['badges']=explode(',',$params['badges']);
                //已经是数组了
                $postBadgeService = new PostBadgeService();
                //删除
                $postBadgeService->deleteBy(['post_id'=>$post_id]);
                //再插入
                foreach($params['badges'] as $k=>$v){
                    if($v){
                        //print_r($postBadgeService);exit;
                        $postBadgeService->create(['post_id'=>$post_id,'badge_id'=>$v,'company_id'=>($authInfo['company_id']??1)]);
                    }
               
                }
            }
             //如果是有置顶的，话，执行置顶重新排序。最多2个笔记置顶，后面的定义前面的
             if($tmpParamsTop==1){
                $postService->updateIsTopPost($result['post_id']);
            }
        }
        ksort($result);
        $result['message'] ='更新笔记成功';
        return $this->response->array($result);
    }
    
    /**
     * @SWG\Post(
     *     path="/ugc/post/verify",
     *     summary="审核笔记",
     *     tags={"笔记"},
     *     description="审核笔记",
     *     operationId="createPost",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=false, type="string"),
     *     @SWG\Parameter( name="status", in="formData", description="审核状态 0待审核,1审核通过,2机器拒绝,3待人工审核,4人工拒绝", required=true, type="integer"),
     *     @SWG\Parameter( name="refuse_reason", in="formData", description="拒绝原因", required=false, type="string"),
     *     @SWG\Parameter( name="post_id[]", in="formData", description="笔记post_id,数组,提交参数的格式为form表单类型,比如 post_id[]:2 post_id[]:3", required=true,collectionFormat="multi",type="array",
     *    @SWG\Items(
     *             type="integer",
     *             format="int32"
     *   ),uniqueItems=true),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="post_id", type="string", example="49", description="记录id"),
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
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespone") ) )
     * )
     */
    public function verifyPost(Request $request)
    {
        $allParams = $request->all('post_id','status','refuse_reason');
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
           // throw new ResourceException('未登录不可以审核笔记');
        }
        $params=$allParams;
        if($params['status']??null){
            
        }
        else{
            throw new ResourceException('status参数不能为空');
        }
        if($params['post_id']??null){
            //$params['topics']=implode(',',$params['topics']);
        }
        else{
            throw new ResourceException('post_id参数不能为空');
        }
 /*        if($params['status']==4 && (($params['refuse_reason']??null)=='')){
            //
            throw new ResourceException('人工拒绝原因不能为空');
        } */
        // $params['post_id'] =  1;
        // $params['status'] =  1;
        // 查询活动信息
        $postService = new PostService();
        $params['manual_refuse_reason']=$params['refuse_reason']??'';
        $data=$params;
        unset($data['refuse_reason']);

        unset($data['post_id']);

        app('log')->debug('verifyPost:'.var_export($data,true));

        $result = $postService->entityRepository->updateBy(['post_id'=>$params['post_id']],$data);
        if($params['post_id']??null){
           if($data['status']==4){
            $messageService=new MessageService();

            foreach($params['post_id'] as $k=>$paramsOne){
                        
                $messageData=[];
                $postInfo=$postService->entityRepository->getInfoById($paramsOne);
                    //20送积分扣除笔记
                    try{
                         app('log')->debug('addUgcPoint 拒绝笔记 扣取积分开始:post_id:'.$postInfo['post_id'].'|params'.var_export($postInfo,true));
                         //
                         $postService->addUgcPoint($postInfo['post_id'],$postInfo['user_id'], $postInfo['company_id'],9920,'reduce');
                    }
                    catch(\Exception $e){
                        app('log')->debug('addUgcPoint 拒绝笔记 扣取积分失败:post_id:'.$postInfo['post_id'].'|params'.var_export($postInfo,true)."|失败原因:".$e->getMessage());
                    } 

                try{
                    
                
                    //发送 回复评论/评论笔记。
                    //基本信息
                    $messageData['type']='system';
                    $messageData['sub_type']='refusePost';//评论被拒绝
                    $messageData['source']=2;
                    $messageData['post_id']=$paramsOne;
                    $messageData['comment_id']=0;
                    $messageData['company_id']=$postInfo['company_id'];

                    //发
                    $messageData['from_user_id']=$user_id;//管理员id
                    $messageData['from_nickname']=($admin['username']??'系统管理员');//$postService->getNickName($messageData['from_user_id'],$params['company_id']);
                    
                    $messageData['to_user_id']=$postInfo['user_id'];
                    $messageData['to_nickname']=$postService->getNickName($messageData['to_user_id'],$postInfo['company_id']);
                    $messageData['title']='您的笔记包含违规内容,他人将不可见';

                    //拒绝笔记
                    $messageData['content']=($data['manual_refuse_reason']??'');
                    $messageService->sendMessage($messageData);
                }
                catch(\Exception $e){
                    app('log')->debug('发送评论消息 失败: messageData:'.var_export($messageData,true)."|失败原因:".$e->getMessage());
                }
            }
            }
            elseif($data['status']==1){
                foreach($params['post_id'] as $k=>$paramsOne){
                        
                    $messageData=[];
                    $postInfo=$postService->entityRepository->getInfoById($paramsOne);
                        //20送积分给发布笔记
                        try{
                             app('log')->debug('addUgcPoint 通过笔记 发送积分开始:post_id:'.$postInfo['post_id'].'|params'.var_export($postInfo,true));
                             //
                             $postService->addUgcPoint($postInfo['post_id'],$postInfo['user_id'], $postInfo['company_id'],20);
                        }
                        catch(\Exception $e){
                            app('log')->debug('addUgcPoint 通过笔记 发送积分失败:post_id:'.$postInfo['post_id'].'|params'.var_export($postInfo,true)."|失败原因:".$e->getMessage());
                        }
                }
            }

        }
        /**/
        $params['message'] = '审核成功';
        return $this->response->array($params);
    }

    /**
     * @SWG\Post(
     *     path="/ugc/post/setBadges",
     *     summary="笔记批量打角标",
     *     tags={"笔记"},
     *     description="审核笔记",
     *     operationId="setBadges",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=false, type="string"),
     *     @SWG\Parameter( name="post_id[]", in="query", description="笔记post_id,数组,提交参数的格式为form表单类型，比如 post_id[]:2 post_id[]:3", required=false,collectionFormat="multi",type="array",
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
    public function setBadges(Request $request)
    {
        $allParams = $request->all('post_id','badges');
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
           // throw new ResourceException('未登录不可以审核笔记');
        }
        $params=$allParams;
        $badges_str="";
        if($params['badges']??null){
            $badges_str=implode(',',$params['badges']);
        }
        else{
            throw new ResourceException('badges参数不能为空');
        }
        if($params['post_id']??null){
            //$params['topics']=implode(',',$params['topics']);
        }
        else{
            throw new ResourceException('post_id参数不能为空');
        }
        $postService = new PostService();
        $data=$params;
        $postBadgeService = new PostBadgeService();
        foreach($params['post_id'] as $key=>$post_id){
             //角标
          if($params['badges']??null){
                //$params['badges']=explode(',',$params['badges']);
                //已经是数组了
                //删除
                $postBadgeService->deleteBy(['post_id'=>$post_id]);
                //再插入
                foreach($params['badges'] as $kb=>$vb){
                    if($vb){
                        //print_r($postBadgeService);exit;
                        $postBadgeService->create(['post_id'=>$post_id,'badge_id'=>$vb,'company_id'=>($admin['company_id']??1)]);
                    }
            
                }
                //再保存post表
                $result = $postService->saveData(['badges'=>$badges_str],['post_id'=>$post_id]);

            }
        }
        $params['message'] = '批量设置角标成功';
        return $this->response->array($params);
    }

     /**
     * @SWG\Post(
     *     path="/ugc/post/delete",
     *     summary="删除笔记",
     *     tags={"笔记"},
     *     description="删除笔记",
     *     operationId="createPost",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=false, type="string"),
     *     @SWG\Parameter( name="post_id[]", in="formData", description="笔记post_id,数组,提交参数的格式为form表单类型,比如 post_id[]:2 post_id[]:3", required=true,collectionFormat="multi",type="array",
     *    @SWG\Items(
     *             type="integer",
     *             format="int32"
     *   ),uniqueItems=true),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="post_id", type="string", example="49", description="记录id"),
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
     *                                         @SWG\Property( property="company_id", type="string", example="1", description="公司id "),
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
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespone") ) )
     * )
     */
    public function deletePost(Request $request)
    {
        $allParams = $request->all('post_id');
        $authInfo = app('auth')->user()->get();
        $user_id=0;
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
        if($params['post_id']??null){
            //$params['topics']=implode(',',$params['topics']);
        }
        else{
            throw new ResourceException('post_id参数不能为空');
        }
        //$params['post_id'] =  1;
        //$params['status'] =  1;
        //查询活动信息
        $postService = new PostService();
        $result = $postService->deletePost(['post_id'=>$params['post_id']]);
        if($result['post_id']??null){
           
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
     * @SWG\Get(
     *     path="/ugc/post/detail",
     *     summary="获取笔记详情",
     *     tags={"笔记"},
     *     description="获取笔记详情",
     *     operationId="getPostDetail",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=false, type="string"),
     *     @SWG\Parameter( name="post_id", in="query", description="笔记id", required=true, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="comment_id", type="string", example="49", description="记录id"),
     *                  @SWG\Property( property="activity_id", type="string", example="36", description="活动ID"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespone") ) )
     * )
     */
    public function getPostDetail(Request $request)
    {
        $authInfo = app('auth')->user()->get();

        $result['post_info'] = null;

        $filter = [
            'company_id' => $authInfo['company_id'],
            'post_id' => $request->get('post_id'),
        ];
        $postService = new PostService();
        $fromAdmin=true;
        $postInfo = $postService->getPostDetail($filter, '',$fromAdmin);
        if ($postInfo) {

            $result['post_info'] = $postInfo;
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
     *     path="/ugc/post/list",
     *     summary="获取笔记列表",
     *     tags={"笔记"},
     *     description="获取笔记列表",
     *     operationId="getPostList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=false, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="页码", type="integer"),
     *     @SWG\Parameter( name="status", in="query", description="状态 0待审核 1已审核 4已拒绝", type="string"),
     *     @SWG\Parameter( name="source", in="query", description="来源 1用户，2官方", type="string"),
     *     @SWG\Parameter( name="content", in="query", description="帖子标题/内容", type="string"),
     *     @SWG\Parameter( name="nickname", in="query", description="昵称", type="string"),
     *     @SWG\Parameter( name="mobile", in="query", description="手机号", type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页条数", type="integer"),
     *     @SWG\Parameter( name="sort", in="query", description="排序", type="string",description="时间排序：created desc 热度:likes desc"),
     *     @SWG\Parameter( name="topics[]", in="query", description="关联话题topic_id,数组,提交参数的格式为form表单类型，比如 topics[]:2 topics[]:3", required=false,collectionFormat="multi",type="array",
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
     *                  @SWG\Property( property="post_id", type="string", example="49", description="记录id"),
     *                  @SWG\Property( property="user_id", type="string", example="20337", description="用户id "),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespone") ) )
     * )
     */
    public function getPostList(Request $request)
    {
        $authInfo = app('auth')->user()->get();
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 30);
        $authInfo = app('auth')->user()->get();
        $filter['company_id'] = $authInfo['company_id']??1;
        $postService = new PostService();
        //官方、用户
        if ($request->get('source') != '') {
            $filter['source'] = $request->get('source');
        }
        //状态
        if ($request->get('status') != '') {
            $filter['status'] = $request->get('status');
        }
        //话题条件筛选
        if ($request->get('topics') != '') {
            $topics = $request->get('topics');//数组
            $postTopicService = new PostTopicService();
            $postTopicList=$postTopicService->getPostTopicList(['topic_id'=>$topics],'post_id',0,-1);
            if($postTopicList['list']??null){
                $filter['post_id']=array_map(function($item){
                    return  $item['post_id'];
                },$postTopicList['list']);
            }
            else{
                $filter['post_id']=[-1];
            }
        }
        //角标筛选
        if ($request->get('badges') != '') {
            $badges = $request->get('badges');//数组
            $postBadgeService = new postBadgeService();
            $postBadgeList=$postBadgeService->getPostBadgeList(['badge_id'=>$badges],'post_id',0,-1);
            if($postBadgeList['list']??null){
                $filter['post_id']=array_map(function($item){
                    return  $item['post_id'];
                },$postBadgeList['list']);
            }
            else{
                $filter['post_id']=[-1];
            }
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
        $postService = new PostService();
       // $filter['enabled'] = 1;
        $filter['is_draft'] = 0;//去掉草稿箱
       // $filter['status'] = 1;
        $filter['disabled'] = 0;//去掉已删除

        $sort = $request->get('sort') ?? '';
        $orderBy = [];
        if ($sort && trim($sort)) {
            $orderByRs = explode(' ', $sort);
            $orderBy[$orderByRs[0]] = $orderByRs[1];
            //$orderBy['p_order'] = 'asc';
            //$filter['start_time|gte']=time();//开始时间大于当前时间
        }
        //最小原则
        $cols=['post_id','user_id','company_id','title','cover','status','created','badges','topics','p_order','images','image_path','likes','source','is_top'];
        $fromAdmin=true;
        $result = $postService->getPostList($filter, $cols, $page, $pageSize, $orderBy,$fromAdmin);

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
     *     definition="PostInfo",
     *     description="笔记信息",
     *     type="object",
     *     @SWG\Property( property="post_id", type="string", example="48", description="笔记id"),
    *                          @SWG\Property( property="user_id", type="string", example="36", description="用户ID"),
    *                          @SWG\Property( property="mobile", type="string", example="18612345678", description="用户手机号"),
    *                          @SWG\Property( property="title", type="string", example="我的第一个笔记", description="标题"),
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



    /**
     * @SWG\Post(
     *     path="/ugc/post/like",
     *     summary="笔记点赞/取消点赞",
     *     tags={"笔记"},
     *     description="笔记点赞/取消点赞",
     *     operationId="likePost",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="user_id", in="formData",description="会员id", required=true, type="integer"),
     *     @SWG\Parameter( name="post_id", in="formData",description="笔记id", required=true, type="integer"),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespone") ) )
     * )
     */
    public function likePost(Request $request)
    {
        $params = $request->all();

        $authInfo = app('auth')->user()->get();
        $user_id=0;
        $company_id=0;
        if($authInfo && $authInfo['operator_id']){
            $user_id=$authInfo['operator_id'];
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
       
        $data=[
            'user_id'=>$user_id,
            'post_id'=>$params['post_id'],
        ];
        $postService = new PostService();
        $result = $postService->likePost($data);

        return $this->response->array($result);
    }
}
