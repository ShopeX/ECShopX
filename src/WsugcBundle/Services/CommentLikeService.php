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

use WsugcBundle\Entities\CommentLike;
use WsugcBundle\Services\CommentService;


class CommentLikeService
{
    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(CommentLike::class);
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


    public function likeComment($params)
    {
        $filter=[
            'user_id'=>$params['user_id'],
            'post_id'=>$params['post_id'],
            'comment_id'=>$params['comment_id']
        ];
        $commentLikeInfo=$this->entityRepository->getInfo($filter);
        $commentService=new CommentService();
        $commentInfo=$commentService->entityRepository->getInfoById($params['comment_id']);
        $postService=new PostService();
        $messageService=new MessageService();
        $action='like';
        // 3种情况 1.空=>点赞  2.有 disabled0=>取消点赞 3.有 disabled1=>重新点赞
        if (empty($commentLikeInfo)) {
            $commentLikeData=[
                'user_id'=>$params['user_id'],
                'post_id'=>$params['post_id'],
                'comment_id'=>$params['comment_id'],
                'disabled'=>0,
            ];
            $result = $this->entityRepository->create($commentLikeData);

            if($result){
                try{
                    //发送 评论点赞消息。只第一次发送 评论点赞时发送消息，取消后再点赞这种，不发送消息
                    //基本信息
                    $messageData['type']='like';
                    $messageData['sub_type']='likeComment';
                    $messageData['source']=1;
                    $messageData['post_id']=$params['post_id'];
                    $messageData['comment_id']=$params['comment_id'];
                    $messageData['company_id']=$commentInfo['company_id'];

                    //发
                    $messageData['from_user_id']=$params['user_id'];
                    $messageData['from_nickname']=$postService->getNickName($messageData['from_user_id'],$commentInfo['company_id']);
                    //收
                    $messageData['to_user_id']=$commentInfo['user_id'];
                    $messageData['to_nickname']=$postService->getNickName($messageData['to_user_id'],$commentInfo['company_id']);
                    $messageData['title']="赞了您的评论";
                    $messageData['content']=$commentInfo['content'];
                    $messageService->sendMessage($messageData);
                }
                catch(\Exception $e){
                    app('log')->debug('发送 评论点赞 消息失败: messageData:'.var_export($messageData,true)."|失败原因:".$e->getMessage());
                }
             
            }

        }elseif ($commentLikeInfo['disabled']==0) {
            $action='unlike';
            $result = $this->entityRepository->updateOneBy($filter, ['disabled'=>1]);
        }elseif ($commentLikeInfo['disabled']==1) {
            $result = $this->entityRepository->updateOneBy($filter, ['disabled'=>0]);
        }



        /*写入redis*/
        if ($action=='like') {
            $this->addCommentLikesToRedis($result['user_id'],$result['comment_id'],$commentInfo['user_id']);
        }else{
            $this->reduceCommentLikesToRedis($result['user_id'],$result['comment_id'],$commentInfo['user_id']);
        }
        /*写入redis end*/

        $filter=[
            'post_id'=>$result['post_id'],
            'comment_id'=>$result['comment_id'],
            'disabled'=>0,
        ];

        $commentLikes=$this->getCommentLikes($filter);

        $result=[
            'action'=>$action,
            'likes'=>$commentLikes,
        ];
        return $result;
    }

    public function getCommentLikes($params)
    {   
        if (1==1) {
            $result=$this->entityRepository->count($params);
        }else{
            $result=$this->getCommentLikesFromRedis($params['comment_id']);
        }
        
        return $result;
    }

    public function getUserCommentLikeStatus($params){
        $filter=[
            'user_id'=>$params['user_id'],
            'post_id'=>$params['post_id'],
            'comment_id'=>$params['comment_id']
        ];
        $commentLikeInfo=$this->entityRepository->getInfo($filter);

        $result=false;
        if (!empty($commentLikeInfo) && $commentLikeInfo['disabled']==0) {
            $result = true;
        }
        
        return $result;
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

    /*redis 保存点赞  点赞数+1

    user_id 点赞会员
    comment_id 评论
    comment_user_id 发布评论的会员
    */
    public function addCommentLikesToRedis($user_id,$comment_id,$comment_user_id)
    {   
        $result=app('redis')->hset('ugc_comment_like', $this->getField([$user_id,$comment_id]), 1);
        $result=app('redis')->hset('ugc_comment_like_count', $comment_id, $this->checkAndCountfromRedis($this->getCommentLikesFromRedis($comment_id),1));
        $result=app('redis')->hset('ugc_user_comment_like_count', $comment_user_id, $this->checkAndCountfromRedis($this->getUserCommentLikesFromRedis($comment_user_id),1));
        return true;
    }

    // redis 取消点赞 点赞数-1
    public function reduceCommentLikesToRedis($user_id,$comment_id,$comment_user_id)
    {
        $result=app('redis')->hset('ugc_comment_like', $this->getField([$user_id,$comment_id]), 0);
        $result=app('redis')->hset('ugc_comment_like_count', $comment_id, $this->checkAndCountfromRedis($this->getCommentLikesFromRedis($comment_id),-1));
        $result=app('redis')->hset('ugc_user_comment_like_count', $comment_user_id, $this->checkAndCountfromRedis($this->getUserCommentLikesFromRedis($comment_user_id),-1));
        return true;
    }

    // 获取评论点赞量
    public function getCommentLikesFromRedis($comment_id)
    {   
        $result=app('redis')->hget('ugc_comment_like_count', $comment_id);
        return $result;
    }

    // 获取会员发布的评论点赞量
    public function getUserCommentLikesFromRedis($user_id)
    {   
        $result=app('redis')->hget('ugc_user_comment_like_count', $user_id);
        return $result;
    }
    
}
