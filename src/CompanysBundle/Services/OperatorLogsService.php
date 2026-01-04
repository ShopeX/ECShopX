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

namespace CompanysBundle\Services;

use CompanysBundle\Interfaces\OperatorLogsInterface;

class OperatorLogsService implements OperatorLogsInterface
{
    /** @var operatorLogsInterface */
    public $operatorLogsInterface;

    /**
     * ShopsService 构造函数.
     */
    public function __construct(OperatorLogsInterface $operatorLogsInterface)
    {
        $this->operatorLogsInterface = $operatorLogsInterface;
    }

    public function addLogs($params)
    {
        return $this->operatorLogsInterface->addLogs($params);
    }

    public function getLogsList($filter, $page, $pageSize, $orderBy)
    {
        return $this->operatorLogsInterface->getLogsList($filter, $page, $pageSize, $orderBy);
    }

    public function deleteLogs($filter)
    {
        return $this->operatorLogsInterface->deleteLogs($filter);
    }

    public function scheduleDelOperatorLogs()
    {
        $delDate = config('common.del_operator_logs_date');

        if (!$delDate) {
            $date = strtotime("-3 month"); // 没有设置开始时间默认获取三个月之前的时间戳
        } else {
            $date = strtotime("-$delDate day");
        }

        app('log')->info('开始执行删除操作日志脚本，清理'.date('Y-m-d', $date).'之前的记录');
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder()->delete('companys_operator_logs');

        $criteria->where($criteria->expr()->lte('created', $date));
        $res = $criteria->execute();

        app('log')->info('本次执行共删除'.$res.'条记录');
    }
}
