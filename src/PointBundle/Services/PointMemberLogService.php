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
