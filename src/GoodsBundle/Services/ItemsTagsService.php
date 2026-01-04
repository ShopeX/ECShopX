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

use Dingo\Api\Exception\ResourceException;
use DistributionBundle\Services\DistributorService;
use GoodsBundle\Entities\ItemsTags;
use GoodsBundle\Entities\ItemsRelTags;
use GoodsBundle\Events\ItemTagEditEvent;
use PromotionsBundle\Services\LimitService;
use PromotionsBundle\Services\MarketingActivityService;

class ItemsTagsService
{
    /**
     * @var \GoodsBundle\Repositories\ItemsTagsRepository
     */
    public $entityRepository;
    /** @var \GoodsBundle\Repositories\ItemsRelTagsRepository */
    public $itemsRelTags;
    /**
     * ItemsTagsService 构造函数.
     */
    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(itemsTags::class);
        $this->itemsRelTags = app('registry')->getManager('default')->getRepository(itemsRelTags::class);
    }

    /**
     * 创建标签
     * @param $params array 修改信息
     */
    public function createTag($params) {
        $filter = [
            'company_id' => $params['company_id'],
            'tag_name' => $params['tag_name'],
            'distributor_id' => $params['distributor_id'],
        ];
        $tagInfo = $this->entityRepository->getInfo($filter);
        if ($tagInfo) {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.tag_name_duplicate'));
        }
        return $this->entityRepository->create($params);
    }

    /**
     * 修改标签
     * @param $filter array 条件
     * @param $params array 修改信息
     */
    public function updateTag($filter, $params)
    {
        $rs = $this->entityRepository->getInfoById($filter['tag_id']);
        if (!$rs) {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.tag_not_exists'));
        }
        if ($filter['distributor_id'] && $rs['distributor_id']!=$filter['distributor_id']) {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.no_permission_to_edit_tag'));
        }
        $filter['distributor_id'] = $rs['distributor_id'];

        $tagFilter = [
            'company_id' => $filter['company_id'],
            'tag_name' => $params['tag_name'],
            'distributor_id' => $filter['distributor_id'],
        ];
        $tagInfo = $this->entityRepository->getInfo($tagFilter);
        if ($tagInfo && $tagInfo['tag_id'] != $filter['tag_id']) {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.tag_name_duplicate'));
        }
        return $this->entityRepository->updateOneBy($filter, $params);
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
        $result = $this->entityRepository->lists($filter, $page, $limit, $orderBy);
        if($result['total_count'] > 0){
            $distributor_ids = array_column($result['list'], 'distributor_id');
            $distributorService = new DistributorService();
            $distributorList = $distributorService->getLists(['distributor_id' => $distributor_ids], 'distributor_id,name');
            $distributorName = array_column($distributorList, 'name', 'distributor_id');
            foreach ($result['list'] as $key=>$val){
                $result['list'][$key]['distributor_name'] = '平台';
                $result['list'][$key]['is_platform'] = true;
                if($val['distributor_id'] > 0){
                    if(isset($distributorName[$val['distributor_id']])){
                        $result['list'][$key]['distributor_name'] = $distributorName[$val['distributor_id']] ?? '';
			$result['list'][$key]['is_platform'] = false;
                    }
                }
            }
        }
        return $result;
    }

    public function getTagIdsByItem($itemIds, $page = 1, $limit = 100)
    {
        $relTags = $this->itemsRelTags->lists(['item_id' => $itemIds]);
        return array_unique(array_column($relTags['list'], 'tag_id'));
    }

    public function getFrontListTags($filter, $page = 1, $limit = 100, $orderBy = ['created' => 'DESC'])
    {
        return $this->getListTags($filter, $page, $limit, $orderBy, true);
    }

    public function getTagsInfo($tag_id)
    {
        return $this->entityRepository->getInfoById($tag_id);
    }

    public function getItemsRelTagList($filter, string $columns = "reltag.item_id,tag.*")
    {
        return $this->itemsRelTags->getListsWithItemTag($filter, $columns);
//
//        $conn = app('registry')->getConnection('default');
//        $criteria = $conn->createQueryBuilder();
//        $criteria->select('count(*)')
//        ->from('items_rel_tags', 'reltag')
//        ->leftJoin('reltag', 'items_tags', 'tag', 'reltag.tag_id = tag.tag_id');
//        if (isset($filter['company_id']) && $filter['company_id']) {
//            $criteria->andWhere($criteria->expr()->eq('tag.company_id', $criteria->expr()->literal($filter['company_id'])));
//        }
//
//        if (isset($filter['item_id']) && $filter['item_id']) {
//            $itemIds = (array)$filter['item_id'];
//            $criteria->andWhere($criteria->expr()->in('reltag.item_id', $itemIds));
//        }
//        $criteria->select('reltag.item_id,tag.*');
//        $list = $criteria->execute()->fetchAll();
//        return $list;
    }

    /**
     * @param $filter
     * @param int $page
     * @param int $pageSize 默认 -1 返回全部
     * @return array
     */
    public function getItemIdsByTagids($filter, $page = 1, $pageSize = -1)
    {
        $relTags = $this->itemsRelTags->lists($filter);
        $itemIds = array_column($relTags['list'], 'item_id');
        return $itemIds;
    }

    public function getItemsByTagidsLimit($filter, $page = 1, $pageSize = 500)
    {
        $relTags = $this->itemsRelTags->lists($filter, $page, $pageSize);
        return $relTags;
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

    public function checkActivity($itemIds, $tagIds, $companyId, &$errMsg = '')
    {
        if (!$tagIds) {
            return true;
        }

        //根据标签查询
        $filters[] = [
            'item_id' => $tagIds,
            'item_type' => 'tag',
        ];

        //商品id转换成主商品ID
        $itemsService = new ItemsService();
        $items = $itemsService->getItems($itemIds, $companyId);
        if (!$items) {
            return true;
        }

        $itemFilter['item_main_cat_id'] = array_column($items, 'item_main_cat_id');
        $itemFilter['brand_id'] = array_column($items, 'brand_id');

        //指定商品查询
        if ($itemIds) {
            $filters[] = [
                'item_id' => $itemIds,
                'item_type' => 'normal',
            ];
        }

        //根据品牌查询
        if ($itemFilter['brand_id']) {
            $filters[] = [
                'item_id' => $itemFilter['brand_id'],
                'item_type' => 'brand',
            ];
        }

        //根据主类目查询
        if ($itemFilter['item_main_cat_id']) {
            $filters[] = [
                'item_id' => $itemFilter['item_main_cat_id'],
                'item_type' => 'category',
            ];
        }

        //获取当前商品和商品标签符合的所有商品限购活动ID
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $criteria->select('limit_id,item_id,item_type,start_time,end_time')->from('promotions_limit_item');
        foreach ($filters as $filter) {
            $criteria = $criteria->orWhere(
                $criteria->expr()->andX(
                    $criteria->expr()->in('item_id', $filter['item_id']),
                    $criteria->expr()->eq('item_type', $criteria->expr()->literal($filter['item_type'])),
                    $criteria->expr()->eq('company_id', $companyId),
                    //$criteria->expr()->lte('start_time', time()),
                    $criteria->expr()->gte('end_time', time())
                )
            );
        }
        $relItemArr = $criteria->execute()->fetchAll();
        foreach ($relItemArr as $k => $v) {
            foreach ($relItemArr as $kk => $vv) {
                if ($kk <= $k) {
                    continue;
                }
                if ($vv['start_time'] < $v['end_time'] && $vv['end_time'] > $v['start_time']) {
                    //查询冲突活动的名称
                    $limit_ids = [$v['limit_id'], $vv['limit_id']];
                    if ($limit_ids) {
                        $limitService = new LimitService();
                        $limitInfo = $limitService->lists(['limit_id' => $limit_ids], 'limit_name');
                        if ($limitInfo['list']) {
                            $limitInfo = array_column($limitInfo['list'], 'limit_name');
                            $errMsg = '商品标签导致限购活动 '.implode(', ', $limitInfo).' 冲突';
                        }
                    }
                    return false;
                }
            }
        }

        //获取当前商品和商品标签符合的所有满减满折活动ID
        $criteria = $conn->createQueryBuilder();
        $criteria->select('marketing_id,item_id,item_type,start_time,end_time,marketing_type')->from('promotions_marketing_activity_items');
        foreach ($filters as $filter) {
            $criteria = $criteria->orWhere(
                $criteria->expr()->andX(
                    $criteria->expr()->in('item_id', $filter['item_id']),
                    $criteria->expr()->eq('item_type', $criteria->expr()->literal($filter['item_type'])),
                    $criteria->expr()->eq('company_id', $companyId),
                    //$criteria->expr()->lte('start_time', time()),
                    $criteria->expr()->gte('end_time', time())
                )
            );
        }
        $relActivityItems = $criteria->execute()->fetchAll();

        //满减满折互相冲突，满赠自身冲突, 加价购自身冲突
        //营销类型: full_discount:满折,full_minus:满减,full_gift:满赠,
        //营销类型: self_select:任选优惠,plus_price_buy:加价购,member_preference:会员优先购
        $relItems = [];
        foreach ($relActivityItems as $v) {
            switch ($v['marketing_type']) {
                case 'full_discount':
                case 'full_minus':
                    $relItems['满减满折'][] = $v;//满减满折互相冲突
                    break;

                default:
                    $relItems[$v['marketing_type']][] = $v;//其他活动和自身冲突
                    break;
            }
        }

        foreach ($relItems as $relItemArr) {
            foreach ($relItemArr as $k => $v) {
                foreach ($relItemArr as $kk => $vv) {
                    if ($kk <= $k) {
                        continue;
                    }
                    if ($vv['start_time'] < $v['end_time'] && $vv['end_time'] > $v['start_time']) {
                        //查询冲突活动的名称
                        $marketing_ids = [$v['marketing_id'], $vv['marketing_id']];
                        if ($marketing_ids) {
                            $limitService = new MarketingActivityService();
                            $activityInfo = $limitService->lists(['marketing_id' => $marketing_ids], 'marketing_name,promotion_tag');
                            if ($activityInfo['list']) {
                                $activityTips = [];
                                foreach ($activityInfo['list'] as $activity) {
                                    $activityTips[] = '【'.$activity['promotion_tag'].'】'.$activity['marketing_name'];
                                }
                                $errMsg = '商品标签导致活动 '.implode(', ', $activityTips).' 冲突';
                            }
                        }
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
    * 为商品批量打标签
    */
    public function createRelTags($itemIds, $tagIds, $companyId)
    {
        if (!$itemIds) {
            return true;
        }
        $savedata['company_id'] = $companyId;
        
        //获取当前商品对应的所有标签
        $itemTags = [];
        $_filter = [
            'company_id' => $companyId,
            'item_id' => $itemIds,
        ];
        $rs = $this->itemsRelTags->getLists($_filter, 'item_id, tag_id', 1, 1000);
        if ($rs) {
            foreach ($rs as $v) {
                $itemTags[$v['item_id']][$v['tag_id']] = $v['tag_id'];
            }
        }
        
        foreach ($itemIds as $itemId) {
            $savedata['item_id'] = $itemId;
            foreach ($tagIds as $tagId) {
                $savedata['tag_id'] = $tagId;
                if (isset($itemTags[$itemId][$tagId])) {
                    unset($itemTags[$itemId][$tagId]);
                } else {
                    $this->itemsRelTags->create($savedata);
                }
            }
        }
        
        //删除已经移除的标签
        if ($itemTags) {
            foreach ($itemTags as $item_id => $tag_ids) {
                if (!$tag_ids) continue;
                $this->itemsRelTags->deleteBy(['item_id' => $item_id, 'tag_id' => $tag_ids]);
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

    public function updateItemTag($result)
    {
        event(new ItemTagEditEvent($result));
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }
}
