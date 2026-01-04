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

namespace SalespersonBundle\Services;

use SalespersonBundle\Entities\Leaderboard;
use SalespersonBundle\Entities\LeaderboardDistributor;
use DistributionBundle\Services\DistributorService;

/**
 * 导购排名 class
 */
class LeaderboardService
{
    public $default_date = '202000';

    public $salespersonLeaderboardRepository;
    public $salespersonLeaderboardDistributorRepository;

    public function __construct()
    {
        $this->salespersonLeaderboardRepository = app('registry')->getManager('default')->getRepository(Leaderboard::class);
        $this->salespersonLeaderboardDistributorRepository = app('registry')->getManager('default')->getRepository(LeaderboardDistributor::class);
    }

    /**
     * 获取当前的排行榜的key
     *
     * @param int $companyId 商家id
     * @param int $distributorId 门店id
     * @param int $month 排行日期
     * @return void
     */
    public function getSalespersonLeaderboardKey($companyId, $distributorId, $month = null)
    {
        $month = $month ?: date('Ym');
        $key = 'leaderboard:s:' . $companyId . ':' . $month . ':' . $distributorId . ':rank';
        return $key;
    }

    /**
     * 获取当前的排行榜的key
     *
     * @param int $companyId 商家id
     * @param int $distributorId 门店id
     * @param int $month 排行日期
     * @return string
     */
    public function getSalespersonLeaderboardInfoKey($companyId, $distributorId, $salespersonId, $month = null)
    {
        $month = $month ?: date('Ym');
        $key = 'leaderboard:s:' . $companyId . ':' . $month . ':' . $distributorId . ':info:' . $salespersonId;
        return $key;
    }

    /**
     * 增加对应销售额
     *
     * @param int $companyId 商家id
     * @param int $salespersonId 导购id
     * @param int $sales 销售额
     * @return boolean
     */
    public function addSalespersonLeaderboard($companyId, $distributorId, $salespersonId, $sales, $isIncrement = true, $number = 1)
    {
        // $key = $this->getSalespersonLeaderboardKey($companyId, $distributorId, $month);
        // $infoKey = $this->getSalespersonLeaderboardInfoKey($companyId, $distributorId, $salespersonId, $month);
        // if ($isIncrement) {
        //     app('redis')->connection('default')->zincrby($key, $sales, $salespersonId);
        //     app('redis')->connection('default')->hincrby($infoKey, 'sales', $sales);
        //     app('redis')->connection('default')->hincrby($infoKey, 'number', $number);
        // } else {
        //     $result = app('redis')->connection('default')->zadd($key, $sales, $salespersonId);
        //     app('redis')->connection('default')->hset($infoKey, 'sales', $sales);
        //     app('redis')->connection('default')->hset($infoKey, 'number', $number);
        // }

        $filter = [
            'company_id' => $companyId,
            'distributor_id' => $distributorId,
            'salesperson_id' => $salespersonId,
            'date' => $this->default_date,
        ];
        $fieldNumber = [
            'sales' => $sales,
            'number' => $number,
        ];
        $this->salespersonLeaderboardRepository->add($fieldNumber, $filter);

        $filter = [
            'company_id' => $companyId,
            'distributor_id' => $distributorId,
            'salesperson_id' => $salespersonId,
            'date' => date('Ym'),
        ];
        $fieldNumber = [
            'sales' => $sales,
            'number' => $number,
        ];
        $this->salespersonLeaderboardRepository->add($fieldNumber, $filter);

        return true;
    }

    /**
     * 获取当前导购数据
     *
     * @param int $companyId
     * @param int $distributorId
     * @param int $salespersonId
     * @param int $month
     * @return string
     */
    public function getSalespersonLeaderboardInfo($companyId, $distributorId, $salespersonId, $month = null)
    {
        $infoKey = $this->getSalespersonLeaderboardInfoKey($companyId, $distributorId, $salespersonId, $month);
        $result = app('redis')->connection('default')->hgetall($infoKey);
        return $result;
    }

    /**
     * 给出对应的排行榜
     *
     * @param int $companyId 商家id
     * @param int $month 排名日期
     * @param int $number 需要给出排行榜数目
     * @param bool $asc 排序顺序 true为按照高分为第0
     * @param bool $withscores 是否需要分数
     * @param callback $callback 用于处理排行榜的回调函数
     * @return [] 对应排行榜
     */
    public function getSalespersonLeaderboard($companyId, $distributorId, $month = null, $start = 0, $pageSize = 3, $asc = true, $withscores = true)
    {
        $start = intval($start) >= 1 ?: 1;
        // $page = ($start - 1) * $pageSize;
        // $pageSize = $start * $pageSize;

        // $key = $this->getSalespersonLeaderboardKey($companyId, $distributorId, $month);

        // if ($asc) {
        //     //按照高销售额顺序排行;
        //     $nowSalespersonLeadboard =  app('redis')->connection('default')->zrevrange($key, $page, $pageSize - 1, ['withscores' => $withscores]);
        // } else {
        //     //按照低销售额顺序排行;
        //     $nowSalespersonLeadboard =  app('redis')->connection('default')->zrange($key, $page, $pageSize - 1, ['withscores' => $withscores]);
        // }
        $month = $month ?: $this->default_date;
        $filter = [
            'company_id' => $companyId,
            // 'distributor_id' => $distributorId,
            'date' => $month,
        ];
        $orderBy = ['sales' => $asc ? 'DESC' : 'ASC'];
        $result = $this->salespersonLeaderboardRepository->lists($filter, '*', $start, $pageSize, $orderBy);
        if ($result['total_count'] > 0) {
            $salespersonIds = array_column($result['list'], 'salesperson_id');
            $salespersonService = new SalespersonService();
            $salespersonListTemp = $salespersonService->getLists(['salesperson_id' => $salespersonIds]);
            $salespersonList = array_column($salespersonListTemp, null, 'salesperson_id');

            foreach ($result['list'] as $k => &$v) {
                $v['name'] = $salespersonList[$v['salesperson_id']]['name'] ?? null;
                $v['avatar'] = $salespersonList[$v['salesperson_id']]['avatar'] ?? null;
            }
        }
        return $result;
    }

    /**
     * 获取导购排名
     *
     * @param int $companyId 公司id
     * @param int $salespersonId 导购
     * @param int $month 排名日期
     * @return int
     */
    public function getSalespersonLeaderboardzrank($companyId, $distributorId, $salespersonId, $month = null)
    {
        // $key = $this->getSalespersonLeaderboardKey($companyId, $distributorId, $month);
        // $rank = app('redis')->connection('default')->zrank($key, $salespersonId);
        // return intval($rank) + 1;
        $filter = [
            'company_id' => $companyId,
            // 'distributor_id' => $distributorId,
            'salesperson_id' => $salespersonId,
            'date' => $month ?: $this->default_date,
        ];

        $result = $this->salespersonLeaderboardRepository->getInfo($filter);
        if (!$result) {
            return 0;
        }

        $filter = [
            'company_id' => $companyId,
            // 'distributor_id' => $distributorId,
            'date' => $month ?: $this->default_date,
            'sales|gt' => $result['sales']
        ];
        $rank = $this->salespersonLeaderboardRepository->count($filter);
        return intval($rank) + 1;
    }

    /**
     * 获取当前的排行榜的key
     *
     * @param int $companyId 商家id
     * @param int $month 排行日期
     * @return string
     */
    public function getDistributorLeaderboardKey($companyId, $month = null)
    {
        $month = $month ?: date('Ym');
        $key = 'leaderboard:d:' . $companyId . ':' . $month . ':rank';
        return $key;
    }

    /**
     * 获取当前的排行榜的key
     *
     * @param int $companyId 商家id
     * @param int $month 排行日期
     * @return string
     */
    public function getDistributorLeaderboardInfoKey($companyId, $distributorId, $month = null)
    {
        $month = $month ?: date('Ym');
        $key = 'leaderboard:d:' . $companyId . ':' . $month . ':info:' . $distributorId;
        return $key;
    }

    /**
     * 增加对应销售额
     *
     * @param int $companyId 商家id
     * @param int $distributorId 导购id
     * @param int $sales 销售额
     * @return boolean
     */
    public function addDistributorLeaderboard($companyId, $distributorId, $sales, $isIncrement = true, $number = 1)
    {
        // $key = $this->getDistributorLeaderboardKey($companyId);
        // $infoKey = $this->getDistributorLeaderboardInfoKey($companyId, $distributorId);
        // if ($isIncrement) {
        //     app('redis')->connection('default')->zincrby($key, $sales, $distributorId);
        //     app('redis')->connection('default')->hincrby($infoKey, 'sales', $sales);
        //     app('redis')->connection('default')->hincrby($infoKey, 'number', $number);
        //     $this->salespersonLeaderboardRepository-add($fieldNumber, $filter);
        // } else {
        //     $result = app('redis')->connection('default')->zadd($key, $sales, $distributorId);
        //     app('redis')->connection('default')->hset($infoKey, 'sales', $sales);
        //     app('redis')->connection('default')->hset($infoKey, 'number', $number);
        // }

        $filter = [
            'company_id' => $companyId,
            'distributor_id' => $distributorId,
            'date' => $this->default_date,
        ];
        $fieldNumber = [
            'sales' => $sales,
            'number' => $number,
        ];
        $this->salespersonLeaderboardDistributorRepository->add($fieldNumber, $filter);

        $filter = [
            'company_id' => $companyId,
            'distributor_id' => $distributorId,
            'date' => date('Ym'),
        ];
        $fieldNumber = [
            'sales' => $sales,
            'number' => $number,
        ];
        $this->salespersonLeaderboardDistributorRepository->add($fieldNumber, $filter);

        return true;
    }

    /**
     * 获取当前导购数据
     *
     * @param int $companyId
     * @param int $distributorId
     * @param int $salespersonId
     * @param int $month
     * @return string
     */
    public function getDistributorLeaderboardInfo($companyId, $distributorId, $month = null)
    {
        $infoKey = $this->getDistributorLeaderboardInfoKey($companyId, $distributorId, $month);
        $result = app('redis')->connection('default')->hgetall($infoKey);
        return $result;
    }

    /**
     * 给出对应的排行榜
     *
     * @param int $companyId 商家id
     * @param int $month 排名日期
     * @param int $number 需要给出排行榜数目
     * @param bool $asc 排序顺序 true为按照高分为第0
     * @param callback $callback 用于处理排行榜的回调函数
     * @return [] 对应排行榜
     */
    public function getDistributorLeaderboard($companyId, $month = null, $start = 0, $pageSize = 3, $asc = true, $withscores = true)
    {
        $start = intval($start) >= 1 ? intval($start) : 1;
        // $page = ($start - 1) * $pageSize;
        // $pageSize = $start * $pageSize;

        // $key = $this->getDistributorLeaderboardKey($companyId, $month);
        // if ($asc) {
        //     //按照高销售额顺序排行;
        //     $nowLeadboard =  app('redis')->connection('default')->zrevrange($key, $page, $pageSize - 1, ['withscores' => $withscores]);
        // } else {
        //     //按照低销售额顺序排行;
        //     $nowLeadboard =  app('redis')->connection('default')->zrange($key, $page, $pageSize - 1, ['withscores' => $withscores]);
        // }

        // $result = [];
        $month = $month ?: $this->default_date;
        $filter = [
            'company_id' => $companyId,
            'date' => $month,
        ];
        $orderBy = ['sales' => $asc ? 'DESC' : 'ASC'];
        $result = $this->salespersonLeaderboardDistributorRepository->lists($filter, '*', $start, $pageSize, $orderBy);
        if ($result['total_count'] > 0) {
            $distributorIds = array_column($result['list'], 'distributor_id');
            $distributorService = new DistributorService();
            $distributorListTemp = $distributorService->getLists(['distributor_id' => $distributorIds]);
            $distributorList = array_column($distributorListTemp, null, 'distributor_id');
            foreach ($result['list'] as $k => &$v) {
                $v['name'] = $distributorList[$v['distributor_id']]['name'] ?? null;
            }
        }
        return $result;
    }

    /**
     * 获取门店排名
     *
     * @param int $companyId 公司id
     * @param int $salespersonId 导购
     * @param int $month 排名日期
     * @return int
     */
    public function getDistributorLeaderboardzrank($companyId, $distributorId, $month = null)
    {
        // $key = $this->getDistributorLeaderboardKey($companyId, $month);
        // $rank = app('redis')->connection('default')->zrank($key, $distributorId);
        // if (null === $rank) {
        //     return 0;
        // }
        // return intval($rank) + 1;
        $filter = [
            'company_id' => $companyId,
            'distributor_id' => $distributorId,
            'date' => $month ?: $this->default_date,
        ];
        $result = $this->salespersonLeaderboardDistributorRepository->getInfo($filter);
        if (!$result) {
            return 0;
        }

        $filter = [
            'company_id' => $companyId,
            'date' => $month ?: $this->default_date,
            'sales|gt' => $result['sales']
        ];
        $rank = $this->salespersonLeaderboardDistributorRepository->count($filter);
        return intval($rank) + 1;
    }

    /**
     * 获取用户销售排序
     *
     * @param int $companyId
     * @param int $distributorId
     * @param int $salespersonId
     * @param integer $start
     * @param integer $pageSize
     * @return array
     */
    public function getLeaderboardInfo($companyId, $distributorId, $salespersonId, $start = 1, $pageSize = 3)
    {
        $month = date('Ym');
        $result['salesperson_rank_top'] = $this->getSalespersonLeaderboard($companyId, $distributorId, $month, $start, $pageSize);
        $result['salesperson_rank'] = $this->getSalespersonLeaderboardZrank($companyId, $distributorId, $salespersonId, $month);
        $result['distributor_rank_top'] = $this->getDistributorLeaderboard($companyId, $month, $start, $pageSize);
        $result['distributor_rank'] = $this->getDistributorLeaderboardZrank($companyId, $distributorId, $month);
        return $result;
    }

    /**
     * Dynamically call the LeaderboardService instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->salespersonLeaderboardRepository->$method(...$parameters);
    }
}
