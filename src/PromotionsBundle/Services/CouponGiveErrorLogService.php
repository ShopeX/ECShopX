<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
