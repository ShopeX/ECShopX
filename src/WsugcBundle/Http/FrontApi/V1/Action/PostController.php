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
use Dingo\Api\Exception\ResourceException;
use Dingo\Api\Exception\StoreResourceFailedException;
use WsugcBundle\Services\ContentCheckService;
use WsugcBundle\Services\PostService;
use WsugcBundle\Services\SettingService;
use WsugcBundle\Services\PostTopicService;
use WsugcBundle\Services\PostLikeService;
use WsugcBundle\Services\PostFavoriteService;
use WsugcBundle\Services\TopicService;

class PostController extends Controller
{
     /**
     * @SWG\Post(
     *     path="/h5app/wxapp/ugc/post/create",
     *     summary="发布笔记",
     *     tags={"笔记"},
     *     description="发布笔记",
     *     operationId="createPost",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=false, type="string"),
     *     @SWG\Parameter( name="user_id", in="formData", description="用户id", required=false, type="integer"),
     *     @SWG\Parameter( name="post_id", in="formData", description="笔记id。修改笔记时必传。", required=false, type="integer"),
     *     @SWG\Parameter( name="is_draft", in="formData", description="是否草稿箱。 1草稿,0非草稿", required=false, type="integer"),
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
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespones") ) )
     * )
     */
    public function createPost(Request $request)
    {
        $postService = new PostService();
        $allParams = $request->all('post_id','topic_id','title','content','cover','images','image_tag','image_path', 'topics','video','video_ratio','video_place','video_thumb', 'is_draft','is_top','p_order','badges','user_id','goods');
        $authInfo = $request->get('auth');
        $user_id=0;
        if($authInfo && $authInfo['user_id']){
            $user_id=$authInfo['user_id'];
        }
    /*     else if(env('APP_ENV')=='local'){
            $user_id=$allParams['user_id']??0;
        } */
        // if($allParams['user_id']??0){
        //     if( $user_id!=)
        // }
        if (!($user_id ?? 0)) {
            throw new ResourceException('只有会员才可以发布笔记');
        }
      
        $params=$allParams;
        if($params['is_draft']!=1){
            if(!$params['cover']){
                throw new ResourceException('封面图不能为空');
            }
        }
        if(!($params['images']??null)){
            if($params['is_draft']!=1){
                throw new ResourceException('images参数不能为空');
            }
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
        //图片相对路径
        $images=[];
        if($params['image_path']??null){
            $images=$params['image_path'];
            $params['image_path']=implode(',',$params['image_path']);
        }
        else{
            if($params['is_draft']!=1){
                throw new ResourceException('image_path参数不能为空');
            }
        }
        //图片tag
        if($params['image_tag']??null){
            $params['image_tag']=json_encode($params['image_tag']);
        }
        $params['user_id'] =  $user_id;


        //创建或更新判断是否越权操作
        if($params['post_id']??null){
            $resultExist = $postService->getInfo(['post_id'=>$params['post_id']]);
            if(!$resultExist){
                throw new ResourceException('笔记不存在');
            }
            else{
                if($resultExist['user_id']!=$params['user_id']){
                    throw new ResourceException('非法操作：不能编辑别人的笔记');
                }
            }
        }

        $params['mobile'] = $authInfo['mobile']??'0';
        $params['company_id'] = $authInfo['company_id']??1;
        $params['enabled'] =  1;
        $params['status'] =  0;
        $params['ip']=$_SERVER['REMOTE_ADDR'];
        $params['p_order'] = $params['p_order']??0;
        $params['is_draft'] = $params['is_draft']??0;//是否草稿
        $params['operator_id'] = 0;
        $params['is_top']=0;
        $params['source']=1;
        //先上机器审核
        //标题
        $title_status=0;
        $content_status=0;
        $image_status=0;
        $open_id=$postService->getOpenId($user_id,$params['company_id']);
        $contentCheckService=new ContentCheckService($params['company_id']);
        if($params['title']){
            if($msgCheckResult=$contentCheckService->msgCheck($params['title'],$open_id)){
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
        //内容
        if($params['content']){
            if($msgCheckResult=$contentCheckService->msgCheck($params['content'],$open_id)){
                $content_status=$msgCheckResult;
            }
            else{
                //机器审核不上的话，还是 待审核
                $content_status=0;
            }
        }
        else{
                $content_status=1;
        }

        //图片的机器审核
        $mediaCheckTraceId=[];//追踪id
        $traceIds=[];//追踪id 拼接

        if($params['image_path']){
            $tmp_images=[];
            $image_status=0;
            $filesystem = app('filesystem')->disk('import-image');
            foreach($images as $ki=>$vi){
                //保持一致
                $image_full_url=$filesystem->url($vi);
                if($mediaCheckResult=$contentCheckService->mediaCheck($image_full_url,$open_id)){
                    //print_r($mediaCheckResult);exit;
                   // $image_status=$msgCheckResult;
                   if($mediaCheckResult){
                        $mediaCheckTraceId[]=['trace_id'=>$mediaCheckResult,'image_full_url'=>$image_full_url,'image_index'=>$ki];

                        $traceIds[]=','.$mediaCheckResult.':false';
                   }
                }
                else{
                    //机器审核不上的话，还是 待审核
                    
                }
            }
        }
        else{
            $image_status=1;
        }



        //标题和内容都过审，才pass
        if($title_status==1 && $content_status==1 && $image_status==1){
            $params['status']=1;
        }
        else if($title_status==4 || $content_status==4 || $image_status==4){
            $params['status']=4;
        }
        else{
            $params['status']=0;
        }
        if(($params['is_draft'] ?? 0)==1){
            //草稿的话，始终是待审核状态。
            $params['status'] =  0;
        }
      
        //多加几个字段2022-07-22 11:33:27，否则图片异步审核通过后，得判断其他2个是否是已审核通过了，都审核通过，笔记才能审核通过，有一个不审核通过，其他的都不能审核通过。
        $params['title_status']=$title_status;
        $params['content_status']=$content_status;
        $params['image_status']=$image_status;


        //创建或更新
        if($params['post_id']??null){
            $result = $postService->saveData($params,['post_id'=>$params['post_id']]);
            $result['post_id']=$params['post_id'];
        }
        else{
            $result = $postService->saveData($params);
        }
        if($result['post_id']??null){

            //更新mediaCheckTraceId和post_id的关联关系2022-07-22 11:31:11
            if($mediaCheckTraceId){
                $result = $postService->saveData(['mediacheck_traceid'=>json_encode($mediaCheckTraceId),'trace_ids'=>implode('|',$traceIds)],['post_id'=>$result['post_id']]);
            }
           


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
        }
        ksort($result);
        if(($params['is_draft']==0)){
            if($params['status']==1){
                $result['message'] = '发布成功';
            }
            else if($params['status']==4){
                $result['message'] = '笔记内容违规，审核失败，请修改后重新提交';
            }
            else if($params['status']==0){
                $result['message'] = '笔记已提交，人工审核中';
            }
            else{
                $result['message'] = '笔记已提交，人工审核中';
            }
        }
        else{
            $result['message'] = '草稿已保存';
        }
        if($params['status']==1 && !($params['post_id']??null) && $params['is_draft']==0){
            //送积分-新发帖才送
            //20送积分给发布笔记
            try{
                 $postService->addUgcPoint($result['post_id'],$params['user_id'], $params['company_id'],20);
            }
            catch(\Exception $e){
                app('log')->debug('addUgcPoint 发布笔记 送积分失败:post_id:'.$result['post_id'].'|params'.var_export($params,true)."|失败原因:".$e->getMessage());
            }
        }
     
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/h5app/wxapp/ugc/post/share",
     *     summary="分享笔记",
     *     tags={"笔记"},
     *     description="分享笔记",
     *     operationId="sharePost",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=false, type="string"),
     *     @SWG\Parameter( name="post_id", in="formData", description="笔记id", required=false, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="post_id", type="string", example="49", description="记录id"),
     *                  @SWG\Property( property="reason", type="string", example="null", description="审核不通过原因"),
     *                  @SWG\Property( property="created", type="string", example="1612441632", description="创建时间"),
     *                  @SWG\Property( property="updated", type="string", example="1612441632", description=" 修改时间"),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespones") ) )
     * )
     */
    public function sharePost(Request $request)
    {
        $postService = new PostService();
        $allParams = $request->all('post_id');
        $authInfo = $request->get('auth');
        $user_id=0;
        if($authInfo && $authInfo['user_id']){
            $user_id=$authInfo['user_id'];
        }
        else if(env('APP_ENV')=='local'){
            $user_id=$allParams['user_id']??0;
        }
        if (!($user_id ?? 0)) {
            throw new ResourceException('只有会员才可以分享笔记');
        }
        $params=$allParams;
        $params['user_id']=$user_id;
        $params['company_id']=$authInfo['company_id']??1;
        $share_nums=0;
        if($params['post_id']??null){
            $postInfo=$postService->entityRepository->lists(['post_id'=>$params['post_id']],'share_nums');
            if($postInfo['list']??null){
                $share_nums=$postInfo['list'][0]['share_nums']??0;
            }
            else{

            }
        }
        else{
            throw new ResourceException('post_id参数不能为空');
        }
        $share_nums++;//加1
        //创建或更新
        if($params['post_id']??null){
            $result = $postService->saveData(['share_nums'=> $share_nums],['post_id'=>$params['post_id']]);

            //是否已分享记录一下到redis
            app('redis')->hset('ugc_share', $params['post_id'].'::'.$user_id, 1);


            try{
                //24 分享笔记送积分给分享
                $postService->addUgcPoint($params['post_id'],$params['user_id'], $params['company_id'],24);
            }
            catch(\Exception $e){
                app('log')->debug('addUgcPoint 分享笔记 送积分失败:'.var_export($params,true)."|失败原因:".$e->getMessage());
            }
        }
        else{
           // $result = $postService->saveData($params);
        }
        $params['share_nums']=$share_nums;
        $params['message'] = '分享成功';
        return $this->response->array($params);
    }


    /**
     * @SWG\Post(
     *     path="/h5app/wxapp/ugc/post/delete",
     *     summary="删除笔记",
     *     tags={"笔记"},
     *     description="删除笔记",
     *     operationId="deletePost",
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
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespones") ) )
     * )
     */
    public function deletePost(Request $request)
    {
        $allParams = $request->all('post_id');
        $authInfo = $request->get('auth');
        $user_id=0;
        if($authInfo && $authInfo['user_id']){
            $user_id=$authInfo['user_id'];
        }
        else if(env('APP_ENV')=='local'){
            $user_id=$allParams['user_id']??0;
        }
        if (!($user_id ?? 0)) {
            throw new ResourceException('未登录不可以删除笔记');
        }
        $params=$allParams;
        if($params['post_id']??null){
            //$params['topics']=implode(',',$params['topics']);
        }
        else{
            throw new ResourceException('post_id参数不能为空');
        }
        $postService = new PostService();
        //判断当前post_id是否都是当前会员的
        $list=$postService->entityRepository->lists(['post_id'=>$params['post_id']],'user_id,post_id',0,-1);
        if($list['list']??null){
            //print_r($list['list']);exit;
            foreach($list['list'] as $k=>$v){
                if($v['user_id']!=$user_id){
                    throw new ResourceException('此笔记'.$v['post_id'].'不属于您，不能删除');
                }
            }
        }
        $result = $postService->deletePost(['post_id'=>$params['post_id']]);
        if($result['post_id']??null){
           
        }
        $params['message'] = '删除成功';
        return $this->response->array($params);
    }
    /**
     * @SWG\Get(
     *     path="/h5app/wxapp/ugc/post/detail",
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
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespones") ) )
     * )
     */
    public function getPostDetail(Request $request)
    {
        $authInfo = $request->get('auth');

        $result['post_info'] = null;

        $filter = [
            'company_id' => $authInfo['company_id'],
            'post_id' => $request->get('post_id'),
        ];
        $postService = new PostService();
        $postInfo = $postService->getPostDetail($filter, $authInfo['user_id']);
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
     *     path="/h5app/wxapp/ugc/post/list",
     *     summary="获取笔记列表（包括首页,本人,他人）",
     *     tags={"笔记"},
     *     description="获取笔记列表",
     *     operationId="getPostList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=false, type="string"),
     *     @SWG\Parameter( name="content", in="query", description="搜索关键词", type="string"),
     *     @SWG\Parameter( name="searchType", in="query", description="搜索条件：like:点赞过的笔记，favorite收藏的笔记", type="string"),
     *     @SWG\Parameter( name="page", in="query", description="页码", type="integer"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页条数", type="integer"),
     *     @SWG\Parameter( name="sort", in="query", description="排序", type="string",description="时间排序：created desc 热度:likes desc"),
     *      @SWG\Parameter( name="user_id", in="query", description="用户id（我的，他人笔记列表 必传）", required=false, type="integer"),
     *    @SWG\Parameter( name="is_draft", in="query", description="是否草稿箱 1草稿,0非草稿", required=false, type="integer"),
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
     *                  @SWG\Property( property="activity_id", type="string", example="36", description="活动ID"),
     *                  @SWG\Property( property="user_id", type="string", example="20337", description="用户id "),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespones") ) )
     * )
     */
    public function getPostList(Request $request)
    {
        // IDX: 2367340174
        $authInfo = $request->get('auth');
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 30);
        $authInfo = $request->get('auth');
        $filter['company_id'] = $authInfo['company_id'];
        $authInfo_user_id=$authInfo['user_id']??0;

        //官方、用户 笔记
        if ($request->get('source') != '') {
            $filter['source'] = $request->get('source');
        }
        //关键字搜索。匹配标题，内容，话题名称
        if ($request->get('content') != '') {
            //匹配话题名称开始
            $topicService = new TopicService();
            $relatedTopicList = $topicService->entityRepository->lists(['topic_name'=>trim($request->get('content'))],'topic_id',0,-1);
            $keyword_topics_post_id=[-1];
            if($relatedTopicList['list']??null){
                $related_topic_id=array_column($relatedTopicList['list'],'topic_id');
                $postTopicService = new PostTopicService();
                $postTopicList=$postTopicService->getPostTopicList(['topic_id'=>$related_topic_id],'post_id',0,-1);
                if($postTopicList['list']??null){
                    $keyword_topics_post_id=array_map(function($item){
                        return  $item['post_id'];
                    },$postTopicList['list']);
                }
                else{
                    $keyword_topics_post_id=[-1];
                }
            }
            //匹配话题名称end

            $filter['content|contains'] = ['content'=>$request->get('content'),'keyword_topics_post_id'=>$keyword_topics_post_id];
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
        $postService = new PostService();
        if ($request->get('user_id') != '') {
           //$filter['enabled'] = 1;
           $filter['user_id']=$request->get('user_id');
           if($authInfo_user_id==$request->get('user_id')){
               //本人
               if($request->get('is_draft')!=''){
                    $filter['is_draft'] = $request->get('is_draft');
               }
           }
           else{
               //非本人
               $filter['status'] = 1;
               $filter['enabled'] = 1;
               $filter['is_draft'] = 0;//非草稿箱的才显示
           }
        }
        else{
            $filter['enabled'] = 1;
            //话题条件筛选
            $filter['is_draft'] = 0;//非草稿箱的才显示
            $filter['status'] = 1;
        }
        $filter['disabled'] = 0;
        //searchType 搜索类型
        if(($request->get('searchType') ?? '') && ($filter['user_id']??null)){
            $searchType=$request->get('searchType');
            if($searchType=='like'){
                //点赞过的
                $postLikeService=new PostLikeService();
                $likePostLists=$postLikeService->entityRepository->lists(['disabled'=>0,'user_id'=>$filter['user_id']],'post_id',1,-1);
                $filter['post_id']=array_column($likePostLists,'post_id');
              
                if($likePostLists['list']??null){
                    $filter['post_id']=array_column($likePostLists['list'],'post_id');
                }
                else{
                    $filter['post_id']=[-1];
                }
                // print_r($filter);
                // exit;
                unset($filter['user_id']);//一定要干掉这个条件

            }
            else if($searchType=='favorite'){
                //收藏过的
                $postFavoriteService=new PostFavoriteService();
                $favoritePostLists=$postFavoriteService->entityRepository->lists(['disabled'=>0,'user_id'=>$filter['user_id']],'post_id',1,-1);

                // print_r(['user_id'=>$filter['user_id']]);
                // print_r($favoritePostLists);
                // exit;

                //
                //app('log')->debug('favoritePostLists:'.var_export($favoritePostLists,true));
                if($favoritePostLists['list']??null){
                    $filter['post_id']=array_column($favoritePostLists['list'],'post_id');
                }
                else{
                    $filter['post_id']=[-1];
                }
                unset($filter['user_id']);//一定要干掉这个条件

            }
        }
        $sort = $request->get('sort') ?? '';
        $allSort=[
            'likes desc',
            'created desc'
        ];
        if($sort && !in_array($sort,$allSort)){
            throw new StoreResourceFailedException('排序不合法');
        }
        $orderBy = [];
        if ($sort && trim($sort)) {
            $orderByRs = explode(' ', $sort);
            $orderBy[$orderByRs[0]] = $orderByRs[1];
            //$orderBy['p_order'] = 'asc';
            //$filter['start_time|gte']=time();//开始时间大于当前时间
        }
        //最小原则
        $cols=['post_id','user_id','company_id','title','cover','status','created','badges','topics','p_order','likes','video','source'];
        $fromAdmin=false;
        $result = $postService->getPostList($filter, $cols, $page, $pageSize, $orderBy,$fromAdmin,$authInfo_user_id);

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
     *     path="/h5app/wxapp/ugc/post/like",
     *     summary="笔记点赞/取消点赞",
     *     tags={"笔记"},
     *     description="笔记点赞/取消点赞",
     *     operationId="likePost",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="user_id", in="formData",description="会员id", required=true, type="integer"),
     *     @SWG\Parameter( name="post_id", in="formData",description="笔记id", required=true, type="integer"),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespones") ) )
     * )
     */
    public function likePost(Request $request)
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
       
        $data=[
            'user_id'=>$user_id,
            'post_id'=>$params['post_id'],
        ];
        $postService = new PostService();
        $result = $postService->likePost($data);

        return $this->response->array($result);
    }

     /**
     * @SWG\Post(
     *     path="/h5app/wxapp/ugc/post/favorite",
     *     summary="笔记收藏/取消收藏",
     *     tags={"笔记"},
     *     description="笔记收藏/取消收藏",
     *     operationId="favoritePost",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="post_id", in="formData",description="笔记id", required=true, type="integer"),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespones") ) )
     * )
     */
    public function favoritePost(Request $request)
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
       
        $data=[
            'user_id'=>$user_id,
            'post_id'=>$params['post_id'],
        ];
        $postService = new PostService();
        $result = $postService->favoritePost($data);

        return $this->response->array($result);
    }
}
