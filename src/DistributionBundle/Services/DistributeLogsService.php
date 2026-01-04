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

namespace DistributionBundle\Services;

use DistributionBundle\Entities\DistributeLogs;

class DistributeLogsService
{
    private $entityRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(DistributeLogs::class);
    }

    public function create($data)
    {
        $basicConfigService = new BasicConfigService();

        $config = $basicConfigService->getInfoById($data['company_id']);
        $settleTime = $config ? $config['limit_time'] : 0;

        if ($settleTime) {
            $data['plan_close_time'] = time() + 3600 * 24 * $settleTime;
            $data['is_close'] = false;
        } else {
            $data['plan_close_time'] = time();
            $data['is_close'] = true;
        }

        $res = $this->entityRepository->create($data);

        $distributeCountService = new DistributeCountService();
        $distributeCountService->addDistribution($data['company_id'], $data['distributor_id'], $data['total_fee'], $data['total_rebate'], $data['is_close']);
        return $res;
    }

    /**
     * 定时执行结算
     */
    public function scheduleSettleRebate()
    {
        $filter = [
            'plan_close_time|lte' => time(),
            'is_close' => false
        ];
        $totalCount = $this->entityRepository->count($filter);
        if (!$totalCount) {
            return true;
        }

        $distributeCountService = new DistributeCountService();
        $totalPage = ceil($totalCount / 100);
        for ($i = 1; $i <= $totalPage; $i++) {
            $data = $this->entityRepository->lists($filter, ["create_time" => "DESC"], 100, 1);
            foreach ($data['list'] as $row) {
                $conn = app('registry')->getConnection('default');
                $conn->beginTransaction();
                try {
                    $this->entityRepository->updateOneBy(['id' => $row], ['is_close' => true]);
                    $distributeCountService->addSettleRebate($row['company_id'], $row['distributor_id'], $row['total_rebate']);
                    $conn->commit();
                } catch (\Exception $e) {
                    $conn->rollback();
                    app('log')->debug('定时执行佣金结算失败=>'.$e->getMessage());
                    app('log')->debug('定时执行佣金结算失败参数=>'. var_export($row, 1));
                }
            }
        }
        return true;
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        // Ref: 1996368445
        return $this->entityRepository->$method(...$parameters);
    }
}
