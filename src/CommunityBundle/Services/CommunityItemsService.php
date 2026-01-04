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

namespace CommunityBundle\Services;

use CommunityBundle\Entities\CommunityItems;
use GoodsBundle\Services\ItemsService;
use Dingo\Api\Exception\ResourceException;

class CommunityItemsService
{
    private $entityRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(CommunityItems::class);
    }

    public function batchInsert($data) {
        $itemsService = new ItemsService();
        $filter['company_id'] = $data['company_id'];
        $filter['distributor_id'] = $data['distributor_id'];
        foreach ((array) $data['goods_id'] as $goodsId) {
            $filter['goods_id'] = $goodsId;
            $itemList = $itemsService->getItemsLists($filter, 'item_id,item_name,audit_status,approve_status');
            if (!$itemList) {
                throw new ResourceException('ID为'.$goodsId.'的商品不存在');
            }
            $onsale = false;
            foreach ($itemList as $item) {
                if ($item['audit_status'] != 'approved') {
                    throw new ResourceException('商品'.$item['item_name'].'还未审核通过');
                }

                if ($item['approve_status'] == 'onsale') {
                    $onsale = true;
                }
            }
            if (!$onsale) {
                throw new ResourceException('商品'.$item['item_name'].'还未上架');
            }
        }

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            foreach ((array) $data['goods_id'] as $goodsId) {
                $filter['goods_id'] = $goodsId;
                if (!$this->entityRepository->getInfo($filter)) {
                    $this->entityRepository->create($filter);
                }
            }
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }

        return true;
    }

    public function getItemsList($filter, $page = 1, $pageSize = 2000, $orderBy = ['item_id' => 'DESC'])
    {
        // ShopEx framework
        $page = ($page < 1) ? 1 : $page;
        $pageSize = ($pageSize > 2000) ? 2000 : $pageSize;
        $pageSize = ($pageSize <= 0) ? 10 : $pageSize;
        $itemsList = $this->entityRepository->joinItemsList($filter, $page, $pageSize, $orderBy);

        $itemsService = new ItemsService();
        foreach ($itemsList['list'] as $key => &$v) {
            $v['item_main_cat_id'] = $v['item_category'] ?? '';
            $v['item_cat_id'] = $itemsService->getCategoryByItemId($v['item_id'], $v['company_id']);
            // 规格转成bool
            $v['nospec'] = (isset($v['nospec']) && ($v['nospec'] === 'true' || $v['nospec'] === true || $v['nospec'] === 1 || $v['nospec'] === '1')) ? true : false;
            $v['pics'] = json_decode($v['pics'], true);
        }

        return $itemsList;
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
