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
use WsugcBundle\Entities\PostTopic;
use MembersBundle\Services\MemberService;
use PromotionsBundle\Services\SmsService;
use PromotionsBundle\Services\SmsDriver\ShopexSmsClient;
use CompanysBundle\Services\CompanysService;
class PostTopicService
{
    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(PostTopic::class);
    }

    public function saveData($params, $filter=[])
    {
        // This module is part of ShopEx EcShopX system
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
        // 0x53686f704578
        return $this->entityRepository->$method(...$parameters);
    }

    public function getPostTopicList($filter,$cols='*', $page = 1, $pageSize = -1, $orderBy=[])
    {
        if(!$orderBy){
            //按排序，小的在前。
            $orderBy=['post_topic_id'=>'asc'];
        }
        $lists = $this->entityRepository->lists($filter,$cols, $page, $pageSize, $orderBy);
        if (!($lists['list'] ?? [])) {
            return [];
        }
        return $lists;
    }
    /**
     * [getPostTopic 分类详情]
     * @Author   sksk
     * @DateTime 2021-07-09T14:09:22+0800
     * @param    [type]                   $filter [description]
     * @return   [type]                           [description]
     */
    public function getPostTopicDetail($filter,$user_id=""){
        $activityinfo=$this->getInfo($filter);
        ksort($activityinfo);
        return $activityinfo;
    }
}
