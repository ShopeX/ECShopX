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

namespace PromotionsBundle\Services;

use GoodsBundle\Services\ItemsAttributesService;
use GoodsBundle\Services\ItemsTagsService;
use PromotionsBundle\Entities\SeckillActivity;
use PromotionsBundle\Entities\SeckillRelGoods;
use GoodsBundle\Services\ItemsService;

use Dingo\Api\Exception\ResourceException;
use PromotionsBundle\Traits\CheckPromotionsValid;
use PromotionsBundle\Traits\CheckPromotionsRules;
use WechatBundle\Services\WeappService;
use WechatBundle\Services\OpenPlatform;
use PromotionsBundle\Jobs\SavePromotionItemTag;
use SalespersonBundle\Jobs\SalespersonItemsShelvesJob;

class PromotionSeckillActivityService
{
    use CheckPromotionsValid;
    use CheckPromotionsRules;

    public $entityRepository;
    public $entityRelRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(SeckillActivity::class);
        $this->entityRelRepository = app('registry')->getManager('default')->getRepository(SeckillRelGoods::class);
    }



    public function create($params)
    {
        // ShopEx EcShopX Core Module
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $this->checkActivity($params);
            if ($params['limit_total_money'] ?? 0) {
                $params['limit_total_money'] = $params['limit_total_money'] * 100;
            }
            if ($params['limit_money'] ?? 0) {
                $params['limit_money'] = $params['limit_money'] * 100;
            }

            // 仅支持指定商品
            if ($params['use_bound'] == 'goods') {
                $params['use_bound'] = 1;
            }

            foreach ($params['items'] as $key => $item) {
                $params['items'][$key]['activity_store'] = (int) $item['activity_store'];
                $params['items'][$key]['activity_price'] = (float) $item['activity_price'];
                $params['items'][$key]['sort'] = (int) $item['sort'];
            }

            $result = $this->entityRepository->create($params);
            if ($result) {
                $result['items'] = $this->createSeckillItemRel($result, $params);
            }
            $conn->commit();
            $activityType = $params['seckill_type'] == 'normal' ? 'seckill' : 'limited_time_sale';
            $job = (new SalespersonItemsShelvesJob($params['company_id'], $result['seckill_id'], $activityType));
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);
            return $result;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    public function updateActivity($filter, $params)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $this->checkActivity($params, $filter);
            if ($params['limit_total_money'] ?? 0) {
                $params['limit_total_money'] = $params['limit_total_money'] * 100;
            }
            if ($params['limit_money'] ?? 0) {
                $params['limit_money'] = $params['limit_money'] * 100;
            }
            // 仅支持指定商品
            if ($params['use_bound'] == 'goods') {
                $params['use_bound'] = 1;
            }

            $result = $this->entityRepository->updateOneBy($filter, $params);
            if ($result) {
                $result['items'] = $this->createSeckillItemRel($result, $params);
            }
            $conn->commit();
            $activityType = $params['seckill_type'] == 'normal' ? 'seckill' : 'limited_time_sale';
            $job = (new SalespersonItemsShelvesJob($params['company_id'], $result['seckill_id'], $activityType));
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);
            return $result;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
     * 保存活动关联的商品
     */
    private function createSeckillItemRel($activity, $params)
    {
        $activityItemStoreService = new SeckillActivityItemStoreService();
        $filter = ['company_id' => $activity['company_id'], 'seckill_id' => $activity['seckill_id']];
        $this->entityRelRepository->deleteBy($filter);
        if ($activity) {
            $itemService = new ItemsService();
            $itemIds = array_column($params['items'], 'item_id');
            $filter = [
                'company_id' => $params['company_id'],
                'item_id' => $itemIds,
            ];
            $items = $itemService->getSkuItemsList($filter);
            $items = array_column($items['list'], null, 'item_id');
            $result = [];
            $activityPrice = [];
            foreach ($params['items'] as $data) {
                // if (isset($items[$data['item_id']]) && (int)$items[$data['item_id']]['price'] < ((float)$data['activity_price']) * 100) {
                //     throw new ResourceException("活动价格不能大于商品销售价");
                // }
                $itemId = $data['item_id'];
                if (isset($items[$itemId])) {
                    if (isset($isShowIds[$items[$itemId]['default_item_id']])) {
                        $isShow = false;
                    } else {
                        $isShowIds[$items[$itemId]['default_item_id']] = true;
                        $isShow = true;
                    }
                } else {
                    $this->entityRelRepository->deleteBy(['seckill_id' => $activity['seckill_id'], 'company_id' => $activity['company_id']]);
                    continue;
                }
                $data['is_show'] = $isShow;
                $data['seckill_id'] = $activity['seckill_id'];
                $data['company_id'] = $activity['company_id'];
                $data['activity_release_time'] = $activity['activity_release_time'];
                $data['activity_start_time'] = $activity['activity_start_time'];
                $data['activity_end_time'] = $activity['activity_end_time'];
                $data['item_spec_desc'] = $items[$itemId]['item_spec_desc'] ?? '';
                $data['seckill_type'] = $activity['seckill_type'];
                $result[] = $this->entityRelRepository->create($data);
                // 限时优惠库存走商品本身库存
                if ($activity['seckill_type'] != 'limited_time_sale') {
                    app('log')->debug('item Data'.var_export($data, 1));
                    $activityItemStoreService->saveItemStore($activity['seckill_id'], $activity['company_id'], $data['item_id'], $data['activity_store']);
                }
                $activityPrice[$itemId] = round($data["activity_price"] * 100);
            }
            if ($activity['seckill_type'] != 'limited_time_sale') {
                $activityItemStoreService->setExpireat($activity['seckill_id'], $activity['company_id'], $activity['activity_end_time']);
            }

//            (new SavePromotionItemTag($activity['company_id'], $activity['seckill_id'], $params['seckill_type'], $activity['activity_release_time'], $activity['activity_end_time'], $itemIds, $activityPrice))->handle();
            $gotoJob = (new SavePromotionItemTag($activity['company_id'], $activity['seckill_id'], $params['seckill_type'], $activity['activity_release_time'], $activity['activity_end_time'], $itemIds, $activityPrice))->onQueue('slow');
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        }
        return $result;
    }



    private function checkActivity($params, $filter = [])
    {
        $itemsService = new ItemsService();
        $item_ids = array_column($params['items'], 'item_id');
        if ($itemsService->__checkIsGiftItem($params['company_id'], $item_ids)) {
            throw new ResourceException(trans('PromotionsBundle.gift_exists_please_check'));
        }
        // if (time() >= $params['activity_release_time']) {
        //     throw new ResourceException('活动发布时间要大于当前时间');
        // }
        if ($params['activity_release_time'] > $params['activity_start_time']) {
            throw new ResourceException(trans('PromotionsBundle.activity_start_time_cannot_greater_than_publish'));
        }

        if ($params['activity_start_time'] >= $params['activity_end_time']) {
            throw new ResourceException(trans('PromotionsBundle.activity_start_time_cannot_greater_than_end_time'));
        }

        if (!isset($params['items']) || !$params['items']) {
            throw new ResourceException(trans('PromotionsBundle.no_activity_products_please_add'));
        }

        if (!isset($params['validity_period']) || $params['validity_period'] <= 1) {
            throw new ResourceException(trans('PromotionsBundle.unpaid_order_cancel_time_required'));
        }

        $itemIds = [];
        foreach ($params['items'] as $value) {
            if ($params['seckill_type'] == 'normal' && (int)$value['activity_store'] <= 0) {
                throw new ResourceException(trans('PromotionsBundle.product_stock_required_greater_than_zero', ['item_title' => $value['item_title']]));
            }
            if ((float)$value['activity_price'] <= 0) {
                throw new ResourceException(trans('PromotionsBundle.product_price_required_greater_than_zero', ['item_title' => $value['item_title']]));
            }
            if ((int)$value['limit_num'] <= 0) {
                throw new ResourceException(trans('PromotionsBundle.product_limit_required_greater_than_zero', ['item_title' => $value['item_title']]));
            }
            $itemIds[] = $value['item_id'];
        }

        // 如果是内购，那么需要判断同一个店铺同时只能有一个内购活动
        if ($params['seckill_type'] == 'limited_time_sale') {
            $this->__checkDistributorLimitSale(($params['distributor_id'] ?? 0), $params, $filter);
            //if (isset($params['distributor_id'])) {
            //    $distr = explode(',', $params['distributor_id']);
            //    foreach ($distr as $id) {
            //        $this->__checkDistributorLimitSale($id, $params, $filter);
            //    }
            //}
        }
        if (isset($filter['seckill_id']) && $filter['seckill_id']) {
            $result = $this->entityRepository->getInfo($filter);
            if ($result['status'] != 'waiting' && $result['seckill_type'] == 'normal') {
                throw new ResourceException(trans('PromotionsBundle.current_activity_not_editable'));
            }
            //$this->checkActivityValid($params['company_id'], $itemIds, $params['activity_release_time'], $params['activity_end_time'], $filter['seckill_id']);
        } else {
            //$this->checkActivityValid($params['company_id'], $itemIds, $params['activity_release_time'], $params['activity_end_time']);
        }
        $this->checkActivityValidBySecKill($params['company_id'], $itemIds, $params['activity_start_time'], $params['activity_end_time'], ($filter['seckill_id'] ?? 0), $params);
        return true;
    }

    private function __checkDistributorLimitSale($id, $params, $filter)
    {
        $checkfilter['activity_end_time|gt'] = time();
        $checkfilter['activity_release_time|lte'] = time();
        $checkfilter['distributor_id'] = $id;
        //$checkfilter['distributor_id|contains'] = ','.$id.',';
        //$checkfilter['distributor_id'] = ','.$id.',';
        $checkfilter['seckill_type'] = 'limited_time_sale';
        $checkfilter['company_id'] = $params['company_id'];
        $checkfilter['disabled'] = 0;

        if (isset($filter['seckill_id']) && $filter['seckill_id']) {
            $checkfilter['seckill_id|neq'] = $filter['seckill_id'];
        }
        if ($this->entityRepository->lists($checkfilter)['total_count'] > 0) {
            throw new ResourceException('店铺id={'.$id.'}在同时段已存在有效活动');
        }
        return true;
    }


    public function getLists($filter, $page = 1, $pageSize = 100, $orderBy = [], $getItemList = true)
    {
        $lists = $this->entityRepository->lists($filter, $page, $pageSize, $orderBy);
        if ($lists['list'] && $getItemList) {
            $seckillIds = array_column($lists['list'], 'seckill_id');
            $relFilter = isset($filter['company_id']) ? ['seckill_id' => $seckillIds, 'company_id' => $filter['company_id']] : ['seckill_id' => $seckillIds];
            $relLists = $this->entityRelRepository->lists($relFilter);
            $relGoods = [];
            foreach ($relLists['list'] as $value) {
                $relGoods[$value['seckill_id']][] = $value;
            }
            foreach ($lists['list'] as &$list) {
                $list['items'] = isset($relGoods[$list['seckill_id']]) ? $relGoods[$list['seckill_id']] : [];
            }
        }
        return $lists;
    }

    public function getSeckillInfo($filter, $getItemList = true, $itemId = null, $page = 1, $pageSize = 10000, $orderBy = [])
    {
        if (isset($filter['is_show'])) {
            $isShow = $filter['is_show'];
            unset($filter['is_show']);
        }
        $lists = $this->entityRepository->getInfo($filter);
        if ($lists && $getItemList) {
            $relFilter = [
                'seckill_id' => $filter['seckill_id'],
                'company_id' => $filter['company_id'],
            ];
            if ($itemId) {
                $relFilter['item_id'] = $itemId;
                $relInfo = $this->entityRelRepository->getInfo($relFilter);
                return array_merge($lists, $relInfo);
            } else {
                if (isset($isShow)) {
                    $relFilter['is_show'] = $isShow;
                }
                $relLists = $this->entityRelRepository->lists($relFilter, $page, $pageSize);
                $relGoods = [];
                foreach ($relLists['list'] as $value) {
                    $relGoods[$value['seckill_id']][] = $value;
                }
                $lists['total_count'] = $relLists['total_count'];
                $lists['items'] = isset($relGoods[$lists['seckill_id']]) ? $relGoods[$lists['seckill_id']] : [];
            }
        }
        return $lists;
    }

    /**
     * 获取秒杀商品微商城小程序code
     */
    public function getSeckillWxaCode($companyId, $seckillId, $seckillType = 'normal', $distributorId = null)
    {
        $weappService = new WeappService();
        $wxaappid = $weappService->getWxappidByTemplateName($companyId);

        // 获取微商城小程序appid
        $openPlatform = new OpenPlatform();
        $app = $openPlatform->getAuthorizerApplication($wxaappid);
        $data['page'] = 'pages/index'; // 后续提供小程序端页面地址再修改

        $sceneArr['sid'] = $seckillId;
        $sceneArr['stype'] = ($seckillType == 'normal') ? '1' : '2';
        if ($distributorId) {
            $sceneArr['dtid'] = $distributorId;
        }

        $scene = http_build_query($sceneArr);
        $wxaCode = $app->app_code->getUnlimit($scene, $data);
        $base64 = 'data:image/jpg;base64,' . base64_encode($wxaCode);
        return $base64;
    }

    /**
     * 获取指定商品在指定时间段内参加的秒杀活动
     */
    public function getSeckillInfoByItemsId($companyId, $itemId, $seckillId = null, $startTime = null, $endTime = null)
    {
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $criteria->select('main.*, rel.*')
          ->from('promotions_seckill_activity', 'main')
          ->leftJoin('main', 'promotions_seckill_rel_goods', 'rel', 'main.seckill_id = rel.seckill_id');

        $criteria->andWhere($criteria->expr()->eq('main.company_id', $criteria->expr()->literal($companyId)));
        if (is_array($itemId)) {
            $criteria->andWhere($criteria->expr()->in('rel.item_id', $itemId));
        } else {
            $criteria->andWhere($criteria->expr()->eq('rel.item_id', $criteria->expr()->literal($itemId)));
        }

        if ($seckillId) {
            $criteria->andWhere($criteria->expr()->eq('main.seckill_id', $criteria->expr()->literal($seckillId)));
        } else {
            $startTime = $startTime ?: time();
            $endTime = $endTime ?: time();
            $criteria = $criteria->andWhere($criteria->expr()->orX(
                $criteria->expr()->andX(
                    $criteria->expr()->lte('main.activity_release_time', $startTime),
                    $criteria->expr()->gte('main.activity_end_time', $startTime)
                ),
                $criteria->expr()->andX(
                    $criteria->expr()->lte('main.activity_release_time', $endTime),
                    $criteria->expr()->gte('main.activity_end_time', $endTime)
                )
            ));
            $criteria = $criteria->andWhere($criteria->expr()->eq('main.disabled', 0));
        }
        $criteria->orderBy("main.created", "DESC");
        $criteria->setFirstResult(0)->setMaxResults(1);
        $list = $criteria->execute()->fetchAll();

        $activityItemStoreService = new SeckillActivityItemStoreService();

        if (isset($list[0]) && $list[0]) {
            $result = $list[0];
            $result['activity_start_date'] = date('Y-m-d H:i:s', $result['activity_start_time']);
            $result['activity_end_date'] = date('Y-m-d H:i:s', $result['activity_end_time']);
            $result['activity_release_date'] = date('Y-m-d H:i:s', $result['activity_release_time']);
            $nowTime = time();
            if ($nowTime >= $result['activity_end_time'] || $result['disabled'] == 1) {
                $result['status'] = 'it_has_ended';    //已结束
            } elseif ($nowTime >= $result['activity_start_time'] && $nowTime < $result['activity_end_time'] && $result['disabled'] == 0) {
                $result['status'] = 'in_sale';         //售卖中
                $result['last_seconds'] = ($result['activity_end_time'] - $nowTime) > 0 ? ($result['activity_end_time'] - $nowTime) : 0;
            } elseif ($nowTime >= $result['activity_release_time'] && $nowTime < $result['activity_start_time'] && $result['disabled'] == 0) {
                $result['status'] = 'in_the_notice';   //预览中
                $result['last_seconds'] = ($result['activity_start_time'] - $nowTime) > 0 ? ($result['activity_start_time'] - $nowTime) : 0;
            } elseif ($nowTime < $result['activity_release_time'] && $result['disabled'] == 0) {
                $result['status'] = 'waiting';   //等待中
            }
            $activityStore = $activityItemStoreService->getItemStore($result['seckill_id'], $result['company_id'], $result['item_id']);
            $result['activity_store'] = $activityStore > 0 ? $activityStore : 0;
            if (in_array($result['status'], ['in_the_notice', 'in_sale'])) {
                return $result;
            }
        }
        return null;
    }

    public function getSeckillItemList($filter, $page = 1, $pageSize = 100, $orderBy = [], $isSku = true)
    {
        if (!$isSku) {
            $pageSize = 2000;
        }
        $relLists = $this->entityRelRepository->lists($filter, $page, $pageSize, $orderBy);
        if ($relLists['total_count'] == 0) {
            return $relLists = [
                'list' => '',
                'activity' => ''
            ];
        }
        $itemIds = array_column($relLists['list'], 'item_id');
        $itemService = new ItemsService();
        $filter = ['company_id' => $filter['company_id'], 'item_id' => $itemIds];
        $itemsList = $itemService->getItemsList($filter);
        $itemdata = array_column($itemsList['list'], null, 'itemId');
        foreach ($relLists['list'] as &$value) {
            if (!isset($itemdata[$value['item_id']]['goods_id'])) {
                continue;
            }
            $value['item_pic'] = $itemdata[$value['item_id']]['pics'][0] ?? '';
            $value['pics'] = $itemdata[$value['item_id']]['pics'] ?? '';
            $value['price'] = $itemdata[$value['item_id']]['price'] ?? '';
            $value['market_price'] = $itemdata[$value['item_id']]['market_price'] ?? '';
            $value['item_name'] = $itemdata[$value['item_id']]['item_name'] ?? '';
            $value['nospec'] = $itemdata[$value['item_id']]['nospec'] ?? '';
            $value['brand_logo'] = $itemdata[$value['item_id']]['brand_logo'] ?? '';
            $value['special_type'] = $itemdata[$value['item_id']]['special_type'] ?? 'normal';
            $value['goods_id'] = $itemdata[$value['item_id']]['goods_id'];
            $value['distributor_id'] = $itemdata[$value['item_id']]['distributor_id'];
            $value['activity_store'] = $value['activity_store'] - $value['sales_store'];
        }
        if (!$isSku) {
            $relLists['list'] = assoc_unique($relLists['list'], 'goods_id', 'activity_price');
            $sortArr = array_column($relLists['list'], 'sort');
            array_multisort($sortArr, SORT_ASC, $relLists['list']);
            $relLists['total_count'] = count($relLists['list']);
        }
        return $relLists;
    }

    public function incrActivityItemSalesStore($activityId, $companyId, $itemId, $num)
    {
        return $this->entityRelRepository->updateSalesStore(['seckill_id' => $activityId, 'company_id' => $companyId, 'item_id' => $itemId ], $num);
    }

    //手动结束活动
    public function endActivity($companyId, $activityId)
    {
        $filter = [
            'company_id' => $companyId,
            'seckill_id' => $activityId
        ];
        $params['disabled'] = 1;

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $result = $this->entityRepository->updateOneBy($filter, $params);
            if ($result) {
                $this->entityRelRepository->updateBySimpleFilter($filter, $params);
            }
            if ($result) {
                $promotionItemTagService = new PromotionItemTagService();
                $promotionItemTagService->deleteBy(['promotion_id' => $activityId, 'company_id' => $companyId, 'tag_type' => $result['seckill_type']]);

                $conn->commit();
                $activityType = $result['seckill_type'] == 'normal' ? 'seckill' : 'limited_time_sale';
                $job = (new SalespersonItemsShelvesJob($companyId, $activityId, $activityType));
                app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);
            } else {
                $conn->rollback();
            }
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
        return $result;
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
}
