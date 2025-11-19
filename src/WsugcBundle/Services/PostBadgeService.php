<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace WsugcBundle\Services;
use WsugcBundle\Entities\PostBadge;
use MembersBundle\Services\MemberService;
use PromotionsBundle\Services\SmsService;
use PromotionsBundle\Services\SmsDriver\ShopexSmsClient;
use CompanysBundle\Services\CompanysService;
class PostBadgeService
{
    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(PostBadge::class);
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

    public function getPostBadgeList($filter,$cols='*', $page = 1, $pageSize = -1, $orderBy=[])
    {
        if(!$orderBy){
            //按排序，小的在前。
            $orderBy=['post_badge_id'=>'asc'];
        }
        $lists = $this->entityRepository->lists($filter,$cols, $page, $pageSize, $orderBy);
        if (!($lists['list'] ?? [])) {
            return [];
        }
        return $lists;
    }
    /**
     * [getPostBadge 分类详情]
     * @Author   sksk
     * @DateTime 2021-07-09T14:09:22+0800
     * @param    [type]                   $filter [description]
     * @return   [type]                           [description]
     */
    public function getPostBadgeDetail($filter,$user_id=""){
        $activityinfo=$this->getInfo($filter);
        ksort($activityinfo);
        return $activityinfo;
    }
}
