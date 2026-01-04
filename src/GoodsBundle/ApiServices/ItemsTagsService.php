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

namespace GoodsBundle\ApiServices;

use GoodsBundle\Entities\ItemsTags;
use GoodsBundle\Entities\ItemsRelTags;

class ItemsTagsService
{
    public $itemsTags;
    public $itemsRelTags;

    private $entityRepository;
    /**
     * ItemsTagsService 构造函数.
     */
    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(itemsTags::class);
        $this->itemsRelTags = app('registry')->getManager('default')->getRepository(itemsRelTags::class);
    }

    public function getListTags($filter, $page = 1, $limit = 100, $orderBy = ['created' => 'DESC'], $is_front_show = false)
    {
        if (isset($filter['item_id']) && $filter['item_id']) {
            $relTags = $this->itemsRelTags->lists(['item_id' => $filter['item_id']]);
            unset($filter['item_id']);
            $filter['tag_id'] = array_column($relTags['list'], 'tag_id');
        }
        if ($is_front_show) {
            $filter['front_show'] = 1;
        }
        return $this->entityRepository->lists($filter, $page, $limit, $orderBy);
    }

    public function getFrontListTags($filter, $page = 1, $limit = 100, $orderBy = ['created' => 'DESC'])
    {
        return $this->getListTags($filter, $page, $limit, $orderBy, true);
    }

    public function getTagsInfo($tag_id)
    {
        return $this->entityRepository->getInfoById($tag_id);
    }

    public function getItemsRelTagList($filter)
    {
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $criteria->select('count(*)')
        ->from('items_rel_tags', 'reltag')
        ->leftJoin('reltag', 'items_tags', 'tag', 'reltag.tag_id = tag.tag_id');
        if (isset($filter['company_id']) && $filter['company_id']) {
            $criteria->andWhere($criteria->expr()->eq('tag.company_id', $criteria->expr()->literal($filter['company_id'])));
        }

        if (isset($filter['item_id']) && $filter['item_id']) {
            $itemIds = (array)$filter['item_id'];
            $criteria->andWhere($criteria->expr()->in('reltag.item_id', $itemIds));
        }
        $criteria->select('reltag.item_id,tag.*');
        $list = $criteria->execute()->fetchAll();
        return $list;
    }

    public function getItemIdsByTagids($filter)
    {
        $relTags = $this->itemsRelTags->lists($filter);
        $itemIds = array_column($relTags['list'], 'item_id');
        return $itemIds;
    }

    public function getRelCount($filter)
    {
        return $this->itemsRelTags->count($filter);
    }

    public function deleteById($filter)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $lists = $this->itemsRelTags->lists($filter);
            if (isset($lists['list']) && $lists['list']) {
                $result = $this->itemsRelTags->deleteBy($filter);
            }
            $result = $this->entityRepository->deleteBy($filter);
            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
    * 为会员批量打标签
    */
    public function createRelTags($itemIds, $tagIds, $companyId)
    {
        $savedata['company_id'] = $companyId;
        foreach ($itemIds as $itemId) {
            $savedata['item_id'] = $itemId;
            foreach ($tagIds as $tagId) {
                $savedata['tag_id'] = $tagId;
                if (!$this->itemsRelTags->getInfo($savedata)) {
                    $result = $this->itemsRelTags->create($savedata);
                }
            }
        }
        return true;
    }

    /**
    * 为指定会员打标签
    */
    public function createRelTagsByItemId($itemId, $tagIds, $companyId)
    {
        $savedata['item_id'] = $itemId;
        $savedata['company_id'] = $companyId;
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            if ($this->itemsRelTags->getInfo($savedata)) {
                $result = $this->itemsRelTags->deleteBy($savedata);
            }
            if ($tagIds) {
                foreach ($tagIds as $tagId) {
                    $savedata['tag_id'] = $tagId;
                    $this->itemsRelTags->create($savedata);
                }
            }
            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
    * 单一标签关联多会员
    */
    public function createRelTagsByTagId($itemIds, $tagId, $companyId)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $savedata['company_id'] = $companyId;
            foreach ($itemIds as $itemId) {
                $savedata['item_id'] = $itemId;
                $this->itemsRelTags->deleteBy($savedata);
                if ($tagId) {
                    $savedata['tag_id'] = $tagId;
                    $result = $this->itemsRelTags->create($savedata);
                }
            }
            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }
}
