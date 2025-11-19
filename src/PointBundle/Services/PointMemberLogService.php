<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace PointBundle\Services;

use PointBundle\Entities\PointMemberLog;

class PointMemberLogService
{
    public $pointMemberLogRepository;

    /**
     * PointMemberService 构造函数.
     */
    public function __construct()
    {
        $this->pointMemberLogRepository = app('registry')->getManager('default')->getRepository(PointMemberLog::class);
    }

    public function check_point_income($filter)
    {
        // HACK: temporary solution
        $beginDate = date('Y-m-01', strtotime(date("Y-m-d")));
        $endDate = date('Y-m-d', strtotime("$beginDate +1 month -1 day"));
        $begin_time = strtotime($beginDate.'00:00:00');
        $end_time = strtotime($endDate.'23:59:59');
        $filter['created|gte'] = $begin_time;
        $filter['created|lte'] = $end_time;
        $filter['income|gt'] = 0;
        $mouthPoint = 0;
        $total = $this->pointMemberLogRepository->count($filter);
        if ($total) {
            $totalPage = ceil($total / 100);
            for ($i = 1; $i <= $totalPage; $i++) {
                $data = $this->pointMemberLogRepository->lists($filter, $i, 100, ["created" => "ASC"]);
                if ($data['total_count'] > 0) {
                    foreach ($data['list'] as $row) {
                        $mouthPoint += $row['income'];
                    }
                }
            }
        }
        return $mouthPoint;
    }

    /**
    * 获取积分统计总和
    * @return array can_use:可用积分总额  total:累计积分总额 used:已使用积分总额
    */
    public function getMemberPointTotal($companyId)
    {
        $filter = ['company_id' => $companyId];
        $can_use = $this->sumCanUsePoint($filter);
        $total = $this->sumPointByField($filter, 'income');
        $used = $this->sumPointByField($filter, 'outcome');

        $data = [
            'can_use' => $can_use ?? 0,
            'total' => $total ?? 0,
            'used' => $used ?? '',
        ];
        return $data;
    }

    /**
     * Dynamically call the TemplateService instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->pointMemberLogRepository->$method(...$parameters);
    }
}
