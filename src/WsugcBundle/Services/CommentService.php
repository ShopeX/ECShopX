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

namespace WsugcBundle\Services;

use Exception;
use WsugcBundle\Entities\Comment;
use MembersBundle\Services\MemberService;
use WsugcBundle\Services\CommentLikeService;
use MembersBundle\Services\WechatUserService;

class CommentService
{
    public function __construct()
    {
        // $this->entityRepository = app('registry')->getManager('default')->getRepository(Comment::class);
        $this->entityRepository = getRepositoryLangue(Comment::class);
    }

    public function createComment($params)
    {   
        // 获取回复评论的发布评论人
        // 笔记信息
        $messageService=new MessageService();
        $postService=new PostService();
        $postInfo=$postService->entityRepository->getInfoById($params['post_id']);
        $reply_user_id=0;
        $reply_comment=true;
        if ($params['reply_comment_id']>0) {
            //回复二级评论
            $commentInfo=$this->entityRepository->getInfoById($params['reply_comment_id']);
            $reply_user_id=$commentInfo['user_id'];
        }
        else{
            //笔记的评论吗？那就是回复笔记的作者,$reply_user_id要对齐。怎么能留0 by sk
            if($params['parent_comment_id']){
                //回复1级评论
                $commentInfo=$this->entityRepository->getInfoById($params['parent_comment_id']);
                //1级评论作者
                $reply_user_id= $commentInfo['user_id'];
            }
            else{
                //回复笔记本身
                //$commentInfo=$this->entityRepository->getInfoById($params['reply_comment_id']);
                $reply_user_id= $postInfo['user_id'];
                $reply_comment =false;
            }
        }
       // print_r($commentInfo);exit;
        $commentData=[
            'user_id'=>$params['user_id'],
            'post_id'=>$params['post_id'],
            'parent_comment_id'=>$params['parent_comment_id'],
            'reply_comment_id'=>$params['reply_comment_id'],
            'reply_user_id'=>$reply_user_id,
            'content'=>$params['content'],
            'company_id'=>$params['company_id'],
            'ip'=>$params['ip'],
            'disabled'=>0,
            'status'=>$params['status']
        ];
        $result = $this->entityRepository->create($commentData);
        if($result){
            try{
                //22送积分给评论者 ,且是已审核的状态才给积分
                if(!$reply_comment && $params['status']==1){
                    $postService->addUgcPoint($params['post_id'],$params['user_id'], $params['company_id'],22);
                }
                else{
                    app('log')->debug('addUgcPoint 送积分失败:'.var_export($params,true)."|失败原因:回复评论而不是笔记不给积分");

                }
               
            }
            catch(\Exception $e){
                app('log')->debug('addUgcPoint 送积分失败:'.var_export($params,true)."|失败原因:".$e->getMessage());
            }
            if($params['status']==1){
                try{
                    //发送 回复评论/评论笔记。
                    //基本信息
                    $messageData['type']='reply';
                    $messageData['sub_type']=($reply_comment?'replyComment':'replyPost');
                    $messageData['source']=1;
                    $messageData['post_id']=$params['post_id'];
                    $messageData['comment_id']=$result['comment_id'];
                    $messageData['company_id']=$params['company_id'];

                    //发
                    $messageData['from_user_id']=$params['user_id'];
                    $messageData['from_nickname']=$postService->getNickName($messageData['from_user_id'],$params['company_id']);
                    
                    //收, 评论笔记就是给笔记的作者；回复某个评论，就是给这个评论的发布者
                    $messageData['to_user_id']=$reply_user_id;//$postInfo['user_id'];
                    $messageData['to_nickname']=$postService->getNickName($messageData['to_user_id'],$postInfo['company_id']);
                    $messageData['title']=($reply_comment?"回复了您":"评论了您的笔记");

                    //回复评论，就是评论内容；回复笔记，就是笔记标题
                    $messageData['content']=$params['content'];
                    $messageService->sendMessage($messageData);
                }
                catch(\Exception $e){
                    app('log')->debug('发送评论消息 失败: messageData:'.var_export($messageData,true)."|失败原因:".$e->getMessage());
                }
            }
            else{
                app('log')->debug('发送评论消息失败: messageData:'.var_export($params,true)."|失败原因:没有审核通过");
            }
        }
        $this->addCommentsToRedis($result['comment_id'],$result['post_id'],$result['user_id']);

        return $result;
    }

    /**
     * 评论列表 function
     *
     * @param [type] $filter
     * @param string $cols
     * @param integer $user_id
     * @param integer $pageNo
     * @param integer $pageSize
     * @param array $orderBy
     * @param boolean $fromAdmin 是否后台
     * @return void
     */
    public function getCommentList($filter,$cols="*",$user_id=0,$pageNo=1, $pageSize=10000, $orderBy=['p_order' => 'ASC','created' => 'DESC'],$fromAdmin=false)
    {   
        if(!$cols){
            $cols='comment_id,post_id,user_id,reply_user_id,content,likes,company_id,created,status';
        }
        $result = $this->entityRepository->lists($filter,$cols,$pageNo, $pageSize, $orderBy);
        if($result['list']) {
            $wechatUserService = new WechatUserService();
            $commentLikeService = new CommentLikeService();
            foreach ($result['list'] as $key => $value) {
                $result['list'][$key]['created']=$this->formatTime($value['created']);
                $result['list'][$key]['created_text']=date('Y-m-d H:i:s',$value['created']);
                $result['list'][$key]['status_text']=$this->getStatusText($value['status']);
                $filterUser=['user_id' => $value['user_id'], 'company_id' => $value['company_id']];
                $userInfo = $wechatUserService->getUserInfo($filterUser);
                if(!$userInfo) {
                    //throw new Exception("用户信息错误！");
                }
                $result['list'][$key]['nickname']=$userInfo['nickname']??'';
                $result['list'][$key]['headimgurl']=$userInfo['headimgurl']??'';
                $result['list'][$key]['userInfo']['nickanme']=$userInfo['nickname']??'';
                $result['list'][$key]['userInfo']['headimgurl']=$userInfo['headimgurl']??'';


                if ($value['reply_user_id']>0) {
                    $userInfo = $wechatUserService->getUserInfo(['user_id' => $value['reply_user_id'], 'company_id' => $value['company_id']]);
                    if(!$userInfo) {
                        //throw new Exception("用户信息错误！");
                    }
                    $result['list'][$key]['reply_nickname']=$userInfo['nickname']??'';
                }
                if($fromAdmin){
                    //后台要显示手机号
                    $this->memberService=new MemberService();
                    $memberInfo = $this->memberService->getMemberInfo($filterUser);
                    if($memberInfo){
                        $result['list'][$key]['userInfo']=array_merge( $memberInfo,$result['list'][$key]['userInfo']);
                    }
                    $result['list'][$key]['status_text']=$this->getStatusText($value['status']);
                }
                if(!$fromAdmin){
                    //不是来自后台
                    $like_status=0;
                    if($user_id>0 && $commentLikeService->getUserCommentLikeStatus(['user_id' => $user_id, 'post_id' => $value['post_id'],'comment_id'=>$value['comment_id']])){
                        $like_status=1;
                    }
                    $result['list'][$key]['like_status']=$like_status;

                    $childCommentList=$this->entityRepository->lists(['parent_comment_id'=>$value['comment_id'],'disabled'=>0],'comment_id,post_id,user_id,reply_user_id,content,likes,company_id,created',1,2,$orderBy);
                    if ($childCommentList['list']) {
                        foreach ($childCommentList['list'] as $k => $v) {
                            $childCommentList['list'][$k]['created']=$this->formatTime($v['created']);

                            $userInfo = $wechatUserService->getUserInfo(['user_id' => $v['user_id'], 'company_id' => $v['company_id']]);
                            if(!$userInfo) {
                            // throw new Exception("用户信息错误！");
                            }
                            $childCommentList['list'][$k]['nickname']=$userInfo['nickname']??'';
                            $childCommentList['list'][$k]['headimgurl']=$userInfo['headimgurl']??'';

                            if ($v['reply_user_id']>0) {
                                $userInfo = $wechatUserService->getUserInfo(['user_id' => $v['reply_user_id'], 'company_id' => $v['company_id']]);
                                if(!$userInfo) {
                                // throw new Exception("用户信息错误！");
                                }
                                $childCommentList['list'][$k]['reply_nickname']=$userInfo['nickname']??'';
                            }

                            $like_status=0;
                            if($user_id>0 && $commentLikeService->getUserCommentLikeStatus(['user_id' => $user_id, 'post_id' => $v['post_id'],'comment_id'=>$v['comment_id']])){
                                $like_status=1;
                            }
                            $childCommentList['list'][$k]['like_status']=$like_status;
                            ksort($childCommentList['list'][$k]);

                        }
                        $result['list'][$key]['child']=$childCommentList['list'];
                    }
                }
                ksort($result['list'][$key]);
              
            }
        }

        $result['pager']['count'] = $result['total_count'];
        $result['pager']['page_no'] = $pageNo;
        $result['pager']['page_size'] = $pageSize;

        return $result;
    }
    /**
     * [getActivityCat 分类详情]
     * @Author   sksk
     * @DateTime 2021-07-09T14:09:22+0800
     * @param    [type]                   $filter [description]
     * @return   [type]                           [description]
     */
    public function getCommentDetail($filter,$user_id="",$fromAdmin=false){
        $cols='comment_id,post_id,user_id,reply_user_id,content,likes,company_id,created,status';
        $commentInfo=$this->getCommentList($filter,$cols,0,0,-1,[],$fromAdmin);
        if($commentInfo && ($commentInfo['list']??null)){
          $data=$commentInfo['list'][0];
        }
        else{
            $data=[];
        }
        ksort($data);
        return $data;
    }
    /**
     * [formatDetail 格式化标签数据]
     * @Author   sksk
     * @DateTime 2021-07-14T10:14:36+0800
     * @param    [type]                   $v [description]
     * @return   [type]                      [description]
     */
    function formatDetail($v,$fromdetail=false,$wechatUserService=null){
        $v['created_text'] = date('Y-m-d H:i:s', $v['created']);
        $v['status']=$this->getTagStatusReal($v);//真正的status
        $v['status_text']=$this->getTagStatusText($v['status']);//真正的status
        //视频完整路径2022-06-01 10:14:58
        if( $v['user_id']??null){
            $filter = ['user_id' => $v['user_id'], 'company_id' => $v['company_id']??1];
            $v['userInfo'] = $wechatUserService->getUserInfo($filter);
            $this->memberService=new MemberService();
            $memberInfo = $this->memberService->getMemberInfo($filter);
            if($memberInfo){
                $v['userInfo']=array_merge( $memberInfo,$v['userInfo']);
                $allow_keys_user=['username','avatar','headimgurl','nickname','user_id'];
                foreach($v['userInfo'] as $km=>$vm){
                    if(!in_array($km,$allow_keys_user)){
                        unset($v['userInfo'][$km]);
                    }
                }
            }
        }
        if($fromdetail){
           
        }
        return $v;
    }     
    public function deleteComment($params)
    {
        $commentInfo=$this->entityRepository->getInfo($params);
        if (!empty($commentInfo)) {
            if ($commentInfo['user_id']==$params['user_id']) {
                $result = $this->entityRepository->updateOneBy($params, ['disabled'=>1]);
            }else{
                throw new Exception("仅能删除自己的评论！");
            }
        }else{
            throw new Exception("评论异常，删除失败！");
        }

        $this->reduceCommentsToRedis($result['comment_id'],$result['post_id'],$result['user_id']);
        
        return $result;
    }

    public function likeComment($params)
    {
        $commentLikeService = new CommentLikeService();

        $result=$commentLikeService->likeComment($params);

        if (isset($result['likes']) && $result['likes']>=0) {
            $res = $this->entityRepository->updateOneBy(['comment_id'=>$params['comment_id']], ['likes'=>$result['likes']]);
        }

        return $result;
    }

    public function formatTime($time){
        $current_time=time();
        // 秒数
        $seconds=$current_time-$time;
        // 分钟
        $minutes=floor($seconds/60);
        // 日期
        $date=strtotime(date('Y-m-d',$time));
        // 今天日期
        $dateToday=strtotime(date('Y-m-d',$current_time));
        // 天数
        $days=floor(($dateToday-$date)/86400);
        // 年份
        $year=strtotime(date('Y',$time));
        // 今天日期
        $yearToday=strtotime(date('Y',$current_time));

        // 1分钟内为刚刚,10分钟内显示 几分钟前,今天显示时间，昨天显示 昨天+时间，前天显示
        if ($minutes==0) {
            return "刚刚";
        }elseif ($minutes<=10){
            return $minutes."分钟前";
        }elseif ($date==$dateToday){
            return date('H:i',$time);
        }elseif ($days==1){
            return "昨天".date('H:i',$time);
        }elseif ($days==2){
            return "前天".date('H:i',$time);
        }elseif ($days<=10){
            return $days."天前";
        }elseif ($year==$yearToday){
            return date('m-d',$time);
        }else{
            return date('Y-m-d',$time);
        }
    }


    public function getField($arr)
    {
        return implode("::", $arr);
    }

    public function checkAndCountfromRedis($count,$num)
    {
        if ($count>0) {
            return $count+$num;
        }else{
            return $num;
        }
    }

    /*redis 保存评论数  笔记评论数  会员评论数

    user_id 评论会员
    post_id 评论
    comment_id 评论
    */
    public function addCommentsToRedis($comment_id,$post_id,$user_id)
    {   
        $result=app('redis')->hset('ugc_comment', $this->getField([$comment_id,$post_id]), 1);
        $result=app('redis')->hset('ugc_comment_count', $post_id, $this->checkAndCountfromRedis($this->getCommentsFromRedis($post_id),1));
        $result=app('redis')->hset('ugc_user_comment_count', $user_id, $this->checkAndCountfromRedis($this->getUserCommentsFromRedis($user_id),1));
        return true;
    }

    // redis 删除评论 评论数-1
    public function reduceCommentsToRedis($comment_id,$post_id,$user_id)
    {
        $result=app('redis')->hset('ugc_comment', $this->getField([$comment_id,$post_id]), 0);
        $result=app('redis')->hset('ugc_comment_count', $post_id, $this->checkAndCountfromRedis($this->getCommentsFromRedis($post_id),-1));
        $result=app('redis')->hset('ugc_user_comment_count', $user_id, $this->checkAndCountfromRedis($this->getUserCommentsFromRedis($user_id),-1));
        return true;
    }

    // 获取评论量
    public function getCommentsFromRedis($post_id)
    {   
        $result=app('redis')->hget('ugc_comment_count', $post_id);
        return $result;
    }

    // 获取会员发布的评论量
    public function getUserCommentsFromRedis($user_id)
    {   
        $result=app('redis')->hget('ugc_user_comment_count', $user_id);
        return $result;
    }

   

    public function saveData($params, $filter=[])
    {
        if ($filter) {
            $result = $this->entityRepository->updateOneBy($filter, $params);
        } else {
            $result = $this->entityRepository->create($params);
        }
        return $result;
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }
    public function getStatusText($key=""){
        //(0待审核,1审核通过,2机器拒绝,3待人工审核,4人工拒绝)
       $rs = trans('WsugcBundle/langue.CommentService_getStatusText');
       if((string)$key!=''){
           return $rs[$key];
       }
       else{
           return $rs;
       }
   }
}
