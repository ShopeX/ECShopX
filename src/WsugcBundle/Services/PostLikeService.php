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

use WsugcBundle\Entities\PostLike;
use WsugcBundle\Entities\Post;
use WsugcBundle\Services\PostService;
use WsugcBundle\Services\MessageService;


class PostLikeService
{
    public function __construct()
    {
        // 0x53686f704578
        $this->entityRepository = app('registry')->getManager('default')->getRepository(PostLike::class);
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


    public function likePost($params)
    {
        $filter=[
            'user_id'=>$params['user_id'],
            'post_id'=>$params['post_id'],
        ];
        $postLikeInfo=$this->entityRepository->getInfo($filter);
        //笔记信息
        $postService=new PostService();
        $postInfo=$postService->entityRepository->getInfoById($params['post_id']);
        $messageService=new MessageService();

        $action='like';
        // 3种情况 1.空=>点赞  2.有 disabled0=>取消点赞 3.有 disabled1=>重新点赞
        if (empty($postLikeInfo)) {
            $postLikeData=[
                'user_id'=>$params['user_id'],
                'post_id'=>$params['post_id'],
                'disabled'=>0,
            ];
            $result = $this->entityRepository->create($postLikeData);
            if($result){
                    //21送积分给点赞者
                try{
                    $postService->addUgcPoint($params['post_id'],$params['user_id'], $postInfo['company_id'],21);
                }
                catch(\Exception $e){
                    app('log')->debug('addUgcPoint 点赞笔记 送积分失败:'.var_export($params,true)."|失败原因:".$e->getMessage());
                }
                try{
                    //发送笔记点赞消息。只第一次发送点赞时发送消息，取消后再点赞这种，不发送消息
                    //基本信息
                    $messageData['type']='like';
                    $messageData['sub_type']='likePost';
                    $messageData['source']=1;
                    $messageData['post_id']=$params['post_id'];
                    $messageData['comment_id']=0;
                    $messageData['company_id']=$postInfo['company_id'];

                    //发
                    $messageData['from_user_id']=$params['user_id'];
                    $messageData['from_nickname']=$postService->getNickName($messageData['from_user_id'],$postInfo['company_id']);
                    //收
                    $messageData['to_user_id']=$postInfo['user_id'];
                    $messageData['to_nickname']=$postService->getNickName($messageData['to_user_id'],$postInfo['company_id']);
                    $messageData['title']="赞了您的笔记";
                    $messageData['content']=$postInfo['title'];
                    $messageService->sendMessage($messageData);
                }
                catch(\Exception $e){
                    app('log')->debug('发送笔记点赞消息失败: messageData:'.var_export($messageData,true)."|失败原因:".$e->getMessage());
                }
             
            }
        }elseif ($postLikeInfo['disabled']==0) {
            $action='unlike';
            $result = $this->entityRepository->updateOneBy($filter, ['disabled'=>1]);
        }elseif ($postLikeInfo['disabled']==1) {
            $result = $this->entityRepository->updateOneBy($filter, ['disabled'=>0]);
        }



        /*写入redis*/
        if ($action=='like') {
            $this->addPostLikesToRedis($result['user_id'],$result['post_id'],$postInfo['user_id']);
        }else{
            $this->reducePostLikesToRedis($result['user_id'],$result['post_id'],$postInfo['user_id']);
        }
        /*写入redis end*/

        $filter=[
            'post_id'=>$result['post_id'],
            'disabled'=>0,
        ];

        $postLikes=$this->getPostLikes($filter);

        $result=[
            'action'=>$action,
            'likes'=>$postLikes,
        ];
        return $result;
    }

    public function getPostLikes($params)
    {   
        // 0x53686f704578
        if (1==1) {
            $result=$this->entityRepository->count($params);
        }else{
            $result=$this->getPostLikesFromRedis($params['post_id']);
        }
        
        return $result;
    }


    public function getUserPostLikes($params)
    {
        if (1==1) {
            $result = app('registry')->getManager('default')->getRepository(Post::class)->getLists(['user_id'=>$params['user_id']],'post_id,user_id');
            $post_ids=array_column($result, 'post_id');
            if (!empty($post_ids)) {
                $result=$this->entityRepository->count(['post_id'=>$post_ids,'disabled'=>0]);
            }else{
                $result=0;
            }
        }else{
            $result=$this->getUserPostLikesFromRedis($params['user_id']);
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
    /**
     * 会员是否已点赞此笔记 function
     *
     * @param [type] $post_id
     * @param [type] $user_id
     * @return void
     */
    function getPostUserLikeStatus($post_id,$user_id){

        app('log')->debug('getPostUserLikeStatus-笔记点赞状态: post_id:'.var_export($post_id,true)."|user_id:".$user_id);

        $result = app('redis')->hget('ugc_post_like', $this->getField([$user_id,$post_id]));
        return intval($result);
    }
    /*redis 保存点赞  点赞数+1

    user_id 点赞会员
    post_id 笔记
    post_user_id 发布笔记的会员
    */
    public function addPostLikesToRedis($user_id,$post_id,$post_user_id)
    {
        $result=app('redis')->hset('ugc_post_like', $this->getField([$user_id,$post_id]), 1);
        $result=app('redis')->hset('ugc_post_like_count', $post_id, $this->checkAndCountfromRedis($this->getPostLikesFromRedis($post_id),1));
        $result=app('redis')->hset('ugc_user_post_like_count', $post_user_id, $this->checkAndCountfromRedis($this->getUserPostLikesFromRedis($post_user_id),1));
        return true;
    }

    // redis 取消点赞 点赞数-1
    public function reducePostLikesToRedis($user_id,$post_id,$post_user_id)
    {
        $result=app('redis')->hset('ugc_post_like', $this->getField([$user_id,$post_id]), 0);
        $result=app('redis')->hset('ugc_post_like_count', $post_id, $this->checkAndCountfromRedis($this->getPostLikesFromRedis($post_id),-1));
        $result=app('redis')->hset('ugc_user_post_like_count', $post_user_id, $this->checkAndCountfromRedis($this->getUserPostLikesFromRedis($post_user_id),-1));
        return true;
    }

    // 获取笔记点赞量
    public function getPostLikesFromRedis($post_id)
    {
        $result=app('redis')->hget('ugc_post_like_count', $post_id);
        return $result;
    }

    // 获取会员发布的笔记点赞量
    public function getUserPostLikesFromRedis($user_id)
    {
        $result=app('redis')->hget('ugc_user_post_like_count', $user_id);
        return $result;
    }
    
}
