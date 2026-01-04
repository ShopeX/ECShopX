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

use WsugcBundle\Entities\Post;
use WsugcBundle\Entities\PostGoods;
use MembersBundle\Services\MemberService;
use CompanysBundle\Services\CompanysService;
use GoodsBundle\Services\ItemsService;

class PostGoodsService
{
    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(WsugcPost::class);
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
    public function getPostList($filter,$cols="*", $page = 1, $pageSize = -1, $orderBy=[])
    {
        $postService = new PostService();
        if(!$orderBy){
            //按排序，小的在前。
            //排序从小到大，置顶是-1，然后创建时间新的在前，然后手机号
            $orderBy[]=[
                'p_order' => 'asc',
                'created' => 'desc',
                'mobile'  => 'asc'
            ];
        }
        $lists = $this->entityRepository->lists($filter, $cols, $page, $pageSize, $orderBy);
        if (!($lists['list'] ?? [])) {
            return [];
        }
        foreach ($lists['list'] as &$v) {
            $v=$this->formatPost($v);
            ksort($v);
        }
        return $lists;
    }
    /**
     * [formatPost 格式化活动数据]
     * @Author   sksk
     * @DateTime 2021-07-14T10:14:36+0800
     * @param    [type]                   $v [description]
     * @return   [type]                      [description]
     */
    function formatPost($v,$fromdetail=false){
        $postService = new PostService();
        $v['created_text'] = date('Y-m-d H:i:s', $v['created']);
        $v['status']=$this->getPostStatusReal($v);//真正的status
        $v['status_text']=$this->getPostStatusText($v['status']);//真正的status

        if($fromdetail){
            //相关话题
            if($v['topic_id']){
                $v['topic_id']=explode(',',$v['topic_id']);
                $topicService=new TopicService();
                $itemList=$topicService->list(['topic_id'=>$v['topic_id']],'topic_id,topic_name');
                $v['topics']=$itemList;
            }
            //相关商品
            if($v['goods_id']){
                $v['goods_id']=explode(',',$v['goods_id']);
                $itemService = new ItemsService();
                $itemList = $itemService->list(['item_id'=>$v['goods_id']]);
                $v['goods']=$itemList;
            }
        }
        //相关角标
        if($v['badge_id']){
            $v['badge_id']=explode(',',$v['badge_id']);
            $badgeService = new BadgeService();
            $itemList = $badgeService->list(['badge_id'=>$v['badge_id']]);
            $v['badge']=$itemList;
        }
 
        return $v;
            
    }
    /**
     * [getActivityDetail description]
     * @Author   sksk
     * @DateTime 2021-07-09T14:09:22+0800
     * @param    [type]                   $filter [description]
     * @return   [type]                           [description]
     */
    public function getActivityDetail($filter,$user_id=""){
        $activityinfo=$this->getInfo($filter);
        if($activityinfo){
            $activityinfo=$this->formatActivity($activityinfo);
        }
        ksort($activityinfo);
        return $activityinfo;
    }
        /**获得活动状态
     * @param string $activity_id
     * Author:sksk
     */
    function getActivityStatusReal($activity_info=""){
        $PostService = new PostService();

        $ntime=time();
        if($activity_info['yuyue_end_time']>$ntime && $ntime>=$activity_info['yuyue_begin_time']){
            $activity_status='1';//进行中
            if($PostService->checkCountExceedSameTimePeriod($activity_info['activity_id'],'','',$activity_info['limit_number'])){
                //超过了
                $activity_status='3';//已报满
            }
        }
       else if($activity_info['yuyue_end_time']<$ntime){
           //
           $activity_status='2';//已结束
       }
       else{
           $activity_status='0';//未开始
       }
       return $activity_status;

    }
    public function getActivityStatusText($key="",$lang='zh'){
       $rs=array(
           '0'=>array('zh'=>'未开始','en'=>'Comming Soon'),
           '1'=>array('zh'=>'报名中','en'=>'In Progress'),
           '2'=>array('zh'=>'已结束','en'=>'Closed'),
           '3'=>array('zh'=>'已报满','en'=>'Fully Booked'),
       );
       if($key!=''){
           return $rs[$key][$lang];
       }
       else{
           return $rs;
       }
   }
}
