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

namespace GoodsBundle\Services;

use GoodsBundle\Entities\ItemsRecommend;
use Dingo\Api\Exception\ResourceException;

class ItemsRecommendService
{
    public $max_num = 20;

    private $entityRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(ItemsRecommend::class);
    }

    /**
     * Dynamically call the shopsservice instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }

    public function checkParams($mainItemId, $data)
    {
        $_data = array_column($data, null, 'item_id');
        unset($_data[$mainItemId]);
        if (count($_data) > $this->max_num) {
            $msg = '推荐商品不能超过'.$this->max_num.'个';
            throw new ResourceException($msg);
        }
    }

    /**
    * 设置商品的关联商品信息
    * @param int $companyId:企业ID
    * @param int $main_item_id:主商品的默认item_id
    * @param array $params:推荐商品数据
    */
    public function saveItemsRecommendData($companyId, $mainItemId, $params)
    {
        // 如果没有推荐商品数据，则删除这个商品下的所有推荐商品
        if (empty($params)) {
            $recommend_items_list = $this->getLists(['main_item_id' => $mainItemId]);
            if (!$recommend_items_list) {
                return false;
            }
            foreach ($recommend_items_list as $key => $value) {
                $this->entityRepository->deleteById($value['id']);
            }
        }
        $rel_items_id = $rel_items = [];
        foreach ($params as $key => $value) {
            if ($value['item_id'] == $mainItemId) {
                continue;
            }
            $rel_items_id[] = $value['item_id'];
            $rel_items[$value['item_id']] = $value['sort'];
        }
        if (!$rel_items_id) {
            return true;
        }
        $filter = [
            'company_id' => $companyId,
            'item_id' => $rel_items_id,
        ];
        $itemsService = new ItemsService();
        $items_list = $itemsService->getItemsList($filter);
        if ($items_list['total_count'] == 0) {
            return false;
        }

        $recommend_items_list = $this->getLists(['company_id' => $companyId,'main_item_id' => $mainItemId]);
        $delete_item_id = [];
        if ($recommend_items_list) {
            $recommend_items_id = array_column($recommend_items_list, 'id', 'item_id');
            $old_item_id = array_column($recommend_items_list, 'item_id');
            $cur_item_id = array_column($items_list['list'], 'item_id');
            $delete_item_id = array_diff($old_item_id, $cur_item_id);
        }

        $result = $find_item_id = [];
        foreach ($items_list['list'] as $key => $item) {
            $insertData = [
                'main_item_id' => $mainItemId,
                'item_id' => $item['item_id'],
                'company_id' => $companyId,
                'brief' => $item['brief'],
                'item_name' => $item['item_name'],
                'pics' => $item['pics'][0] ?? '',
                'price' => $item['price'],
                'market_price' => $item['market_price'] ?? 0,
                'item_spec_desc' => '',
                'sort' => $rel_items[$item['item_id']],
            ];
            $filter['company_id'] = $companyId;
            $filter['main_item_id'] = $mainItemId;
            $filter['item_id'] = $item['item_id'];
            $result[] = $this->entityRepository->updateOneBy($filter, $insertData);
        }

        // 删除
        if ($delete_item_id) {
            foreach ($delete_item_id as $item_id) {
                $id = $recommend_items_id[$item_id] ?? 0;
                if (!$id) {
                    continue;
                }
                $this->entityRepository->deleteById($id);
            }
        }

        $this->otherItemsRecommend($companyId, $mainItemId);

        return $result;
    }

    /**
    * 处理当前主商品的，在其他推荐商品的数据
    */
    public function otherItemsRecommend($company_id, $item_id)
    {
        $itemsService = new ItemsService();
        $item_detail = $itemsService->getItemsDetail($item_id);
        $recommend_items_list = $this->getLists(['company_id' => $company_id,'item_id' => $item_id]);
        if (!$recommend_items_list) {
            return true;
        }
        $data = [
            'company_id' => $company_id,
            'brief' => $item_detail['brief'],
            'item_name' => $item_detail['item_name'],
            'pics' => $item_detail['pics'][0] ?? '',
            'price' => $item_detail['price'],
            'market_price' => $item_detail['market_price'] ?? 0,
        ];
        $filter['company_id'] = $company_id;
        $filter['item_id'] = $item_id;
        return $this->entityRepository->updateBy($filter, $data);
    }

    public function getListData($filter, $page, $pageSize, $orderBy = [])
    {
        $lists = $this->entityRepository->lists($filter, '*', $page, $pageSize, $orderBy);
        if (!$lists['list']) {
            return [];
        }

        $_list = [];
        foreach ($lists['list'] as $key => $value) {
            $value['pics'] = [$value['pics']];
            $_list[$key] = $value;
        }
        return $_list;
    }
}
