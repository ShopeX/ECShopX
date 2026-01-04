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

namespace PromotionsBundle\Services;

// 优惠券发放错误日志记录
use MembersBundle\Entities\Members;
use PromotionsBundle\Entities\CouponGiveErrorLog;

class CouponGiveErrorLogService
{
    public $pageSize = 50;

    public $couponGiveErrorLogRepository;

    public $membersRepository;

    public function __construct()
    {
        $this->couponGiveErrorLogRepository = app('registry')->getManager('default')->getRepository(CouponGiveErrorLog::class);
        $this->membersRepository = app('registry')->getManager('default')->getRepository(Members::class);
    }

    /**
     * 创建错误日志记录
     * @param $params
     */
    public function createCouponGiveErrorLog($params)
    {
        $this->couponGiveErrorLogRepository->create($params);
    }

    /**
     * 获取模版订单列表
     */
    public function getCouponGiveErrorLogList(array $filter, $page = 1, $pageSize = 100, $orderBy = ['created' => 'DESC'])
    {
        $list = $this->couponGiveErrorLogRepository->lists($filter, $orderBy, $pageSize, $page);

        $uid = [];
        foreach ($list['list'] as $v) {
            $uid[] = $v['uid'];
        }

        if ($uid) {
            $uidList = $this->membersRepository->getList(['user_id|in' => $uid], 0, $pageSize);
            $newUidList = [];
            foreach ($uidList['list'] as $v) {
                $newUidList[$v['user_id']] = $v;
            }
            foreach ($list['list'] as &$v) {
                $v['username'] = isset($newUidList[$v['uid']]) ? $newUidList[$v['uid']]['username'] : 'null';
                $v['mobile'] = isset($newUidList[$v['uid']]) ? $newUidList[$v['uid']]['mobile'] : 'null';
            }
        }
        return $list;
    }
}
