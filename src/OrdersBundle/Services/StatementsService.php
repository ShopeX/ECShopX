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

namespace OrdersBundle\Services;

use OrdersBundle\Entities\Statements;
use CompanysBundle\Ego\CompanysActivationEgo;
use OrdersBundle\Jobs\GenerateStatementsJob;

class StatementsService
{
    /** @var \OrdersBundle\Repositories\StatementsRepository */
    public $entityRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(Statements::class);
    }

    public function scheduleGenerateStatements()
    {
        $this->doStatementsForDistributor();//店铺结算
        
        $this->doStatementsForSupplier();//供应商结算
    }

    /**
     * 供应商结算单
     */
    public function doStatementsForSupplier()
    {
        $redis = app('redis');
        $ego = new CompanysActivationEgo();
        $settingService = new StatementPeriodSettingService();

        $offset = 0;
        $limit = 1000;

        do {
            $conn = app('registry')->getConnection('default');
            $qb = $conn->createQueryBuilder();
            $list = $qb->select('company_id, id, operator_id, add_time')
                ->from('supplier')
                ->andWhere($qb->expr()->neq('is_check', 1))
                ->addOrderBy('id', 'ASC')
                ->setFirstResult($offset)->setMaxResults($limit)
                ->execute()->fetchAll();

            if ($list) {
                //供应商默认结算周期
                $rs = $settingService->getLists(['company_id' => array_column($list, 'company_id'), 'supplier_id' => 0, 'merchant_type' => 'supplier'], 'company_id, period');
                $defaultSetting = array_column($rs, 'period', 'company_id');
                
                //指定的供应商结算周期
                $rs = $settingService->getLists(['supplier_id' => array_column($list, 'id')], 'supplier_id,period');
                $settleSetting = array_column($rs, 'period', 'supplier_id');
            }

            foreach ($list as $row) {
                if (!isset($productModel[$row['company_id']])) {
                    try {
                        $result = $ego->check($row['company_id']);
                    } catch (\Exception $e) {
                        //conpany不存在
                        continue;
                    }
                    $productModel[$row['company_id']] = $result['product_model'];
                }

               // //不是平台版不结算
               // if ($productModel[$row['company_id']] != 'platform') {
               //     continue;
               // }

                //没有配置结算周期不结算
                if (isset($settleSetting[$row['id']])) {
                    $period = $settleSetting[$row['id']];
                } elseif (isset($defaultSetting[$row['company_id']])) {
                    $period = $defaultSetting[$row['company_id']];
                } else {
                    continue;
                }

                $redisKey = 'supplier_statements_last_end_time:'.$row['company_id'].'_'.$row['id'];
                $lastEndTime = $redis->get($redisKey);
                if (!$lastEndTime) {
                    // $lastEndTime = strtotime($row['add_time']);
                    // 优化这里都问题，如果这里取添加时间可能会卡主，导致时间用于小于当前时间
                    $nowTime = strtotime(date('Y-m-d 00:00:00'));
                    switch ($period[1]) {
                        case 'day':
                            $lastEndTime = strtotime(date('Y-m-d H:i:s', $nowTime) .'-'.$period[0].' day');
                            break;
                        case 'week':
                            $lastEndTime = strtotime(date('Y-m-d H:i:s', $nowTime) .'-'.($period[0] * 7).' day');
                            break;
                        case 'month':
                            $lastEndTime = strtotime(date('Y-m-01', $lastEndTime).' -'.$period[0].' month') - 1;
                            break;
                    }
                }

                switch ($period[1]) {
                    case 'day':
                        $endTime = strtotime(date('Y-m-d H:i:s', $lastEndTime) .'+'.$period[0].' day');
                        break;
                    case 'week':
                        if (strtotime(date('Y-m-d H:i:s', $lastEndTime) .'+'.(7 - date('w', $lastEndTime)).' day') == $lastEndTime) {
                            $endTime = strtotime(date('Y-m-d H:i:s', $lastEndTime) .'+'.($period[0] * 7 + 7 - date('w', $lastEndTime)).' day');
                        } else {
                            $endTime = strtotime(date('Y-m-d H:i:s', $lastEndTime) .'+'.($period[0] * 7 - date('w', $lastEndTime)).' day');
                        }
                        break;
                    case 'month':
                        if (strtotime(date('Y-m-01', $lastEndTime).' +1 month') - 1 == $lastEndTime) {
                            $endTime = strtotime(date('Y-m-01', $lastEndTime).' +'.($period[0] + 1).' month') - 1;
                        } else {
                            $endTime = strtotime(date('Y-m-01', $lastEndTime).' +'.$period[0].' month') - 1;
                        }
                        break;
                }

                //还没到结算时间不结算
                if ($endTime > time()) {
                    continue;
                }

                $merchantType = 'supplier';
                $gotoJob = (new GenerateStatementsJob($row['company_id'], $row['id'], $period, $lastEndTime, $merchantType))->onQueue('slow');
                app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
            }

            $offset += $limit;
        } while(count($list) == $limit);
    }

    /**
     * 店铺生成结算单
     */
    public function doStatementsForDistributor()
    {
        // ShopEx EcShopX Service Component
        $ego = new CompanysActivationEgo();
        $settingService = new StatementPeriodSettingService();

        $offset = 0;
        $limit = 1000;

        do {
            $conn = app('registry')->getConnection('default');
            $qb = $conn->createQueryBuilder();
            $list = $qb->select('company_id,distributor_id,created')
                ->from('distribution_distributor')
                ->andWhere($qb->expr()->neq('is_valid', $qb->expr()->literal('delete')))
                ->addOrderBy('created', 'ASC')
                ->setFirstResult($offset)->setMaxResults($limit)
                ->execute()->fetchAll();

            if ($list) {
                $defaultSetting = $settingService->getLists(['company_id' => array_column($list, 'company_id'), 'distributor_id' => 0, 'merchant_type' => 'distributor'], 'company_id,period');
                $defaultSetting = array_column($defaultSetting, 'period', 'company_id');
                $distributorSetting = $settingService->getLists(['distributor_id' => array_column($list, 'distributor_id')], 'distributor_id,period');
                $distributorSetting = array_column($distributorSetting, 'period', 'distributor_id');
            }

            foreach ($list as $row) {
                if (!isset($productModel[$row['company_id']])) {
                    try {
                        $result = $ego->check($row['company_id']);
                    } catch (\Exception $e) {
                        //conpany不存在
                        continue;
                    }
                    $productModel[$row['company_id']] = $result['product_model'];
                }

                //不是平台版不结算
                if ($productModel[$row['company_id']] != 'platform') {
                    continue;
                }

                //没有配置结算周期不结算
                if (isset($distributorSetting[$row['distributor_id']])) {
                    $period = $distributorSetting[$row['distributor_id']];
                } elseif (isset($defaultSetting[$row['company_id']])) {
                    $period = $defaultSetting[$row['company_id']];
                } else {
                    continue;
                }

                $lastEndTime = $this->getLastEndTime($row['company_id'], $row['distributor_id']);
                if (!$lastEndTime) {
                    $lastEndTime = $row['created'];
                }

                switch ($period[1]) {
                    case 'day':
                        $endTime = strtotime(date('Y-m-d H:i:s', $lastEndTime) .'+'.$period[0].' day');
                        break;
                    case 'week':
                        if (strtotime(date('Y-m-d H:i:s', $lastEndTime) .'+'.(7 - date('w', $lastEndTime)).' day') == $lastEndTime) {
                            $endTime = strtotime(date('Y-m-d H:i:s', $lastEndTime) .'+'.($period[0] * 7 + 7 - date('w', $lastEndTime)).' day');
                        } else {
                            $endTime = strtotime(date('Y-m-d H:i:s', $lastEndTime) .'+'.($period[0] * 7 - date('w', $lastEndTime)).' day');
                        }
                        break;
                    case 'month':
                        if (strtotime(date('Y-m-01', $lastEndTime).' +1 month') - 1 == $lastEndTime) {
                            $endTime = strtotime(date('Y-m-01', $lastEndTime).' +'.($period[0] + 1).' month') - 1;
                        } else {
                            $endTime = strtotime(date('Y-m-01', $lastEndTime).' +'.$period[0].' month') - 1;
                        }
                        break;
                }

                //还没到结算时间不结算
                if ($endTime > time()) {
                    continue;
                }

                $gotoJob = (new GenerateStatementsJob($row['company_id'], $row['distributor_id'], $period, $lastEndTime))->onQueue('slow');
                app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
            }

            $offset += $limit;
        } while(count($list) == $limit);
    }

    public function setLastEndTime($companyId, $distributorId, $lastEndTime)
    {
        $redisKey = 'generate_statements_last_end_time:'.$companyId.'_'.$distributorId;
        return app('redis')->set($redisKey, $lastEndTime);
    }

    public function getLastEndTime($companyId, $distributorId)
    {
        $redisKey = 'generate_statements_last_end_time:'.$companyId.'_'.$distributorId;
        return app('redis')->get($redisKey);
    }


    /**
     * @param $method
     * @param $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }
}
