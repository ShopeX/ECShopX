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

use WsugcBundle\Entities\Follower;
use MembersBundle\Services\MemberService;
use MembersBundle\Services\WechatUserService;
use Exception;
use WsugcBundle\Services\SettingService;

class FollowerService
{
    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(Follower::class);
    }

    public function createFollow($params)
    {

        $filter=[
            'user_id'=>$params['user_id'],
            'follower_user_id'=>$params['follower_user_id']
        ];
        $followerInfo=$this->entityRepository->getInfo($filter);
        $messageService=new MessageService();
        $postService=new PostService();

        $action='follow';
        // 3种情况 1.空=>关注  2.有 disabled0=>取关 3.有 disabled1=>重新关注
        if (empty($followerInfo)) {
            $followerData=[
                'user_id'=>$params['user_id'],
                'follower_user_id'=>$params['follower_user_id'],
                'company_id'=>$params['company_id'],
                'disabled'=>0,
            ];
            $result = $this->entityRepository->create($followerData);
            if($result){
                try{
                    //发送 关注粉丝，重复取关再关注 不发送消息
                    //基本信息
                    $messageData['type']='followerUser';
                    $messageData['sub_type']='follow';
                    $messageData['source']=1;
                    $messageData['post_id']=0;
                    $messageData['comment_id']=0;
                    $messageData['company_id']=$params['company_id'];

                    //发
                    $messageData['from_user_id']=$params['follower_user_id'];//粉丝id
                    $messageData['from_nickname']=$postService->getNickName($messageData['from_user_id'],$params['company_id']);
                    
                    //收
                    $messageData['to_user_id']=$params['user_id'];
                    $messageData['to_nickname']=$postService->getNickName($messageData['to_user_id'],$params['company_id']);
                    $messageData['title']="关注了您";
                    $messageData['content']='关注了您';
                    $messageService->sendMessage($messageData);
                }
                catch(\Exception $e){
                    app('log')->debug('发送 关注消息 失败: messageData:'.var_export($messageData,true)."|失败原因:".$e->getMessage());
                }
             
            }
        }elseif ($followerInfo['disabled']==0) {
            $action='unfollow';
            $result = $this->entityRepository->updateOneBy($filter, ['disabled'=>1]);
        }elseif ($followerInfo['disabled']==1) {
            $result = $this->entityRepository->updateOneBy($filter, ['disabled'=>0]);
        }

        /*写入redis*/
        if ($action=='follow') {
            $this->addFollowersToRedis($result['user_id'],$result['follower_user_id']);
        }else{
            $this->reduceFollowersToRedis($result['user_id'],$result['follower_user_id']);
        }
        /*写入redis end*/

        $filter=[
            'user_id'=>$result['user_id'],
            'disabled'=>0,
        ];

        $followers=$this->getFollowers($filter);

        $result=[
            'action'=>$action,
            'followers'=>$followers,
        ];
        return $result;

    }


    public function getFollowerList($user_type,$filter,$pageNo=1, $pageSize=10000, $orderBy=['created' => 'DESC'])
    {
        $result = $this->entityRepository->lists($filter,'*',$pageNo, $pageSize, $orderBy);
        if($result['list']) {
            $wechatUserService = new WechatUserService();
            foreach ($result['list'] as $k => $v) {
                $user_id=$v['user_id'];
                if ($user_type=='user') {
                    //粉丝列表，这个id是粉丝id.filter里的是博主id
                    //如果是follower,user_id是博主id,filter里的是粉丝id
                    $user_id=$v['follower_user_id'];
                }
                
                if ($user_id) {
                    $filter = ['user_id' => $user_id, 'company_id' => $v['company_id']];
                    $userInfo = $wechatUserService->getUserInfo($filter);
                    if(!$userInfo) {
                        unset($result['list'][$k]);
                        $result['total_count']--;
                    }

                    $result['list'][$k] =[
                        'user_id'=>$user_id,
                        'nickname'=>$userInfo['nickname'],
                        'headimgurl'=>$userInfo['headimgurl'],
                        'mutal_follow'=>$this->getMutalFollow($v['follower_user_id'],$v['user_id'])
                    ];
                } else {
                    $service = new SettingService();
                    $setting = $service->getSettingList(['company_id' => $v['company_id'], 'type' => 'official'],'*');
                    if ($setting) {
                        $setting = array_column($setting['list'], 'value', 'keyname');
                    }
                    $result['list'][$k] =[
                        'user_id'=>0,
                        'nickname'=>$setting['official.nickname'] ?? '',
                        'headimgurl'=>$setting['official.headerimgurl'] ?? '',
                        'mutal_follow'=>$this->getMutalFollow($v['follower_user_id'],$v['user_id'])
                    ];
                }
            }
        }
        $result['list'] = array_values($result['list']);
        $result['pager']['count'] = $result['total_count'];
        $result['pager']['page_no'] = $pageNo;
        $result['pager']['page_size'] = $pageSize;

        return $result;
    }

    // 获取粉丝数
    public function getFollowers($params){
        $result=$this->entityRepository->count($params);
        return $result;
    }

    // 获取关注数
    public function getIdols($params){
        $result=$this->entityRepository->count($params);
        return $result;
    }
    /**
     * 是否互相关注 function
     *
     * @param [type] $follow_user_id 粉丝
     * @param [type] user_id 博主
     * @return integer 1互关，0未互关
     */
    function getMutalFollow($follower_user_id,$user_id){
        $result1=app('redis')->hget('ugc_follower', $this->getField([$user_id,$follower_user_id]));
        $result2=app('redis')->hget('ugc_follower', $this->getField([$follower_user_id,$user_id]));

        app('log')->debug('互关getMutalFollow'.$this->getField([$user_id,$follower_user_id]).":".var_export($result1,true)."\r\n".$this->getField([$follower_user_id,$user_id]).":".var_export($result2,true));


        if($result1!=null && $result2!=null && 2==3){
            if($result1==1 &&  $result2==1){
                app('log')->debug('互关getMutalFollow-都是1'.$this->getField([$user_id,$follower_user_id]).":".var_export($result1,true)."\r\n".$this->getField([$follower_user_id,$user_id]).":".var_export($result2,true));
                return 1;
            }
        }
        else{
            //查数据库
            $result1=$this->entityRepository->count(['user_id'=>$user_id,'follower_user_id'=>$follower_user_id,'disabled'=>0]);
            $result2=$this->entityRepository->count(['user_id'=>$follower_user_id,'follower_user_id'=>$user_id,'disabled'=>0]);
            app('log')->debug('互关getMutalFollow-databbase:'.$this->getField([$user_id,$follower_user_id]).":".var_export($result1,true)."\r\n".$this->getField([$follower_user_id,$user_id]).":".var_export($result2,true));
            if($result1==1 && $result2==1){
                return 1;
            }
        }
        return 0;

    }
    /**
     * 获取当前用户是否已关注此博主，（此笔记的博主） function
     *
     * @param [type] $follow_user_id 粉丝id，当前用户id
     * @param [type] $user_id 博主
     * @return integer 0没有关注 1关注
     */
    function getFollowStatus($follower_user_id,$user_id){
        $result=app('redis')->hget('ugc_follower', $this->getField([$user_id,$follower_user_id]));
        if($result!=null){
            return intval($result);
        }
        else{
            //查数据库
            return $this->entityRepository->count(['user_id'=>$user_id,'follower_user_id'=>$follower_user_id,'disabled'=>0]);
        }
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


     /*redis 保存关注数，粉丝数
    user_id 会员
    follower_user_id 粉丝
    */
    public function addFollowersToRedis($user_id,$follower_user_id)
    {
        $result=app('redis')->hset('ugc_follower', $this->getField([$user_id,$follower_user_id]), 1);
        $result=app('redis')->hset('ugc_follower_count', $user_id, $this->checkAndCountfromRedis($this->getFollowersFromRedis($user_id),1));
        $result=app('redis')->hset('ugc_idol_count', $follower_user_id, $this->checkAndCountfromRedis($this->getUserFollowersFromRedis($follower_user_id),1));
        return true;
    }

    // redis 删除关注 关注数-1
    public function reduceFollowersToRedis($user_id,$follower_user_id)
    {
        $result=app('redis')->hset('ugc_follower', $this->getField([$user_id,$follower_user_id]), 0);
        $result=app('redis')->hset('ugc_follower_count', $user_id, $this->checkAndCountfromRedis($this->getFollowersFromRedis($user_id),-1));
        $result=app('redis')->hset('ugc_idol_count', $follower_user_id, $this->checkAndCountfromRedis($this->getUserFollowersFromRedis($follower_user_id),-1));
        return true;
    }

    // 获取关注量
    public function getFollowersFromRedis($user_id)
    {
        $result=app('redis')->hget('ugc_follower_count', $user_id);
        return $result;
    }

    // 获取会员发布的评论量
    public function getUserFollowersFromRedis($follower_user_id)
    {
        $result=app('redis')->hget('ugc_idol_count', $follower_user_id);
        return $result;
    }


    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }

}
