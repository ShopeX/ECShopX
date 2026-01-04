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

use WsugcBundle\Entities\PostFavorite;
use WsugcBundle\Entities\Post;
use WsugcBundle\Services\PostService;


class PostFavoriteService
{
    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(PostFavorite::class);
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


    public function favoritePost($params)
    {
        $filter=[
            'user_id'=>$params['user_id'],
            'post_id'=>$params['post_id'],
        ];
        $postLikeInfo=$this->entityRepository->getInfo($filter);
        $postService=new PostService();
        $postInfo=$postService->entityRepository->getInfoById($params['post_id']);
        $messageService=new MessageService();

        $action='favorite';
        // 3种情况 1.空=>收藏  2.有 disabled0=>取消收藏 3.有 disabled1=>重新收藏
        if (empty($postLikeInfo)) {
            $postLikeData=[
                'user_id'=>$params['user_id'],
                'post_id'=>$params['post_id'],
                'disabled'=>0,
            ];
            $result = $this->entityRepository->create($postLikeData);
            if($result){
                try{
                    //23 收藏笔记送积分给收藏着
                    $postService->addUgcPoint($params['post_id'],$params['user_id'], $postInfo['company_id'],23);
                }
                catch(\Exception $e){
                    app('log')->debug('addUgcPoint 收藏笔记 送积分失败:'.var_export($params,true)."|失败原因:".$e->getMessage());
                }
                try{
                    //发送收藏笔记消息。只第一次收藏时发送消息，取消后再收藏这种，不发送消息

                    //基本信息
                    $messageData['type']='favoritePost';
                    $messageData['sub_type']='favorite';
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
                    $messageData['title']="收藏了您的笔记";
                    $messageData['content']=$postInfo['title'];
                    $messageService->sendMessage($messageData);
                }
                catch(\Exception $e){
                    app('log')->debug('发送笔记收藏消息失败: messageData:'.var_export($messageData,true)."|失败原因:".$e->getMessage());
                }
             
            }
        }elseif ($postLikeInfo['disabled']==0) {
            $action='unfavorite';
            $result = $this->entityRepository->updateOneBy($filter, ['disabled'=>1]);
        }elseif ($postLikeInfo['disabled']==1) {
            $result = $this->entityRepository->updateOneBy($filter, ['disabled'=>0]);
        }

   

        /*写入redis*/
        if ($action=='favorite') {
            $this->addPostFavoritesToRedis($result['user_id'],$result['post_id'],$postInfo['user_id']);
        }else{
            $this->reducePostFavoritesToRedis($result['user_id'],$result['post_id'],$postInfo['user_id']);
        }
        /*写入redis end*/

        $filter=[
            'post_id'=>$result['post_id'],
            'disabled'=>0,
        ];

        $postLikes=$this->getPostFavorites($filter);

        $result=[
            'action'=>$action,
            'likes'=>$postLikes,
        ];
        return $result;
    }
    /**
     * 会员是否已收藏此笔记 function
     *
     * @param [type] $post_id
     * @param [type] $user_id
     * @return void
     */
    function getPostUserFavorStatus($post_id,$user_id){
        $result = app('redis')->hget('ugc_post_favorite', $this->getField([$user_id,$post_id]));
        return intval($result);
    }

    public function getPostFavorites($params)
    {   
        if (1==2 && $result=$this->getPostFavoritesFromRedis($params['post_id'])) {
            
        }else{
            $params['disabled']=0;
            $result=$this->entityRepository->count($params);
        }
        return $result;
    }


    public function getUserPostFavorites($params)
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
            $result=$this->getUserPostFavoritesFromRedis($params['user_id']);
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

    /*redis 保存收藏  收藏数+1

    user_id 收藏会员
    post_id 笔记
    post_user_id 发布笔记的会员
    */
    public function addPostFavoritesToRedis($user_id,$post_id,$post_user_id)
    {
        //此用户此帖子的是否已收藏
        $result=app('redis')->hset('ugc_post_favorite', $this->getField([$user_id,$post_id]), 1);
        $result=app('redis')->hset('ugc_post_favorite_count', $post_id, $this->checkAndCountfromRedis($this->getPostFavoritesFromRedis($post_id),1));
        $result=app('redis')->hset('ugc_user_post_favorite_count', $post_user_id, $this->checkAndCountfromRedis($this->getUserPostFavoritesFromRedis($post_user_id),1));
        return true;
    }

    // redis 取消收藏 收藏数-1
    public function reducePostFavoritesToRedis($user_id,$post_id,$post_user_id)
    {
        $result=app('redis')->hset('ugc_post_favorite', $this->getField([$user_id,$post_id]), 0);
        $result=app('redis')->hset('ugc_post_favorite_count', $post_id, $this->checkAndCountfromRedis($this->getPostFavoritesFromRedis($post_id),-1));
        $result=app('redis')->hset('ugc_user_post_favorite_count', $post_user_id, $this->checkAndCountfromRedis($this->getUserPostFavoritesFromRedis($post_user_id),-1));
        return true;
    }

    // 获取笔记收藏量
    public function getPostFavoritesFromRedis($post_id)
    {
        $result=app('redis')->hget('ugc_post_favorite_count', $post_id);
        return $result;
    }

    // 获取会员发布的笔记收藏量
    public function getUserPostFavoritesFromRedis($user_id)
    {
        $result=app('redis')->hget('ugc_user_post_favorite_count', $user_id);
        return $result;
    }
    
}
