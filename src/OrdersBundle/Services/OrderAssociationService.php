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

use MembersBundle\Entities\MembersDeleteRecord;
use OrdersBundle\Entities\OrderAssociations;
use DataCubeBundle\Services\SourcesService;
use OrdersBundle\Traits\GetUserIdByMobileTrait;
use OrdersBundle\Traits\GetOrderIdTrait;

// 订单关联表相关
class OrderAssociationService
{
    use GetUserIdByMobileTrait;
    use GetOrderIdTrait;

    /** @var \OrdersBundle\Repositories\OrderAssociationsRepository */
    public $orderAssociationsRepository;
    public $membersDeleteRecordRepository;

    public function __construct()
    {
        $this->orderAssociationsRepository = app('registry')->getManager('default')->getRepository(OrderAssociations::class);
        $this->membersDeleteRecordRepository = app('registry')->getManager('default')->getRepository(MembersDeleteRecord::class);
    }

    public function getOrder($companyId, $orderId)
    {
        $filter = [
            'company_id' => $companyId,
            'order_id' => $orderId,
        ];
        $order = $this->orderAssociationsRepository->get($filter);

        return $order;
    }

    public function getOrderList($cols = '*', $filter, $page, $limit)
    {
        $filter = $this->checkMobile($filter);
        // 根据达达的订单状态进行查询
        $filter = $this->getOrderIdByDadaStatus($filter);

        $offset = ($page - 1) * $limit;
        $result['list'] = [];
        if (!isset($filter['order_id']) || !empty($filter['order_id'])) {
            $result['list'] = $this->orderAssociationsRepository->getList($cols, $filter, $offset, $limit);
        }
        $membersDelete = $this->membersDeleteRecordRepository->getLists(['company_id' => $filter['company_id']], 'user_id');
        if (!empty($membersDelete)) {
            $deleteUsers = array_column($membersDelete, 'user_id');
        }
        if ($result['list']) {
            $sourceIds = array_column($result['list'], 'source_id');
            $objSource = new SourcesService();
            $sourceInfo = $objSource->getSourcesList(['company_id' => $filter['company_id'], 'source_id' => $sourceIds], 1, 100);
            $sourceList = [];
            if ($sourceInfo['list']) {
                $sourceList = array_bind_key($sourceInfo['list'], 'sourceId');
            }
            foreach ($result['list'] as $k => $v) {
                $result['list'][$k]['source_name'] = '-';
                if ($sourceList && $v['source_id'] > 0) {
                    $result['list'][$k]['source_name'] = $sourceList[$v['source_id']]['sourceName'];
                }
                $result['list'][$k]['create_date'] = date('Y-m-d H:i:s', $v['create_time']);
                $result['list'][$k]['user_delete'] = false;
                if (!empty($deleteUsers)) {
                    if (in_array($v['user_id'], $deleteUsers)) {
                        $result['list'][$k]['user_delete'] = true;
                    }
                }
            }
        }
        $result['pager']['count'] = 0;
        if (!isset($filter['order_id']) || !empty($filter['order_id'])) {
            $result['pager']['count'] = $this->orderAssociationsRepository->count($filter);
        }
        $result['pager']['page_no'] = $page;
        $result['pager']['page_size'] = $limit;

        return $result;
    }

    public function countOrderNum($filter)
    {
        return $this->orderAssociationsRepository->count($filter);
    }

    /**
     * Dynamically call the KaquanService instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->orderAssociationsRepository->$method(...$parameters);
    }
}
