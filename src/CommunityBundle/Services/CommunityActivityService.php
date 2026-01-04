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

use CommunityBundle\Entities\CommunityActivity;
use CommunityBundle\Entities\CommunityActivityItem;
use CommunityBundle\Entities\CommunityActivityZiti;
use CommunityBundle\Entities\CommunityChiefDistributor;
use CommunityBundle\Entities\CommunityOrderRelActivity;
use CommunityBundle\Jobs\CancelActivityOrdersJob;
use CommunityBundle\Repositories\CommunityActivityItemRepository;
use CommunityBundle\Repositories\CommunityActivityRepository;
use CommunityBundle\Repositories\CommunityActivityZitiRepository;
use CommunityBundle\Repositories\CommunityChiefDistributorRepository;
use CommunityBundle\Repositories\CommunityOrderRelActivityRepository;
use Dingo\Api\Exception\ResourceException;
use GoodsBundle\Services\ItemsService;
use MembersBundle\Entities\MembersInfo;
use OrdersBundle\Entities\NormalOrdersItems;
use OrdersBundle\Services\Orders\CommunityNormalOrderService;
use CommunityBundle\Services\CommunitySettingService;

class CommunityActivityService
{
    /**
     * @var CommunityActivityRepository
     */
    private $entityRepository;
    /**
     * @var CommunityActivityItemRepository
     */
    private $entityItemRepository;
    /**
     * @var CommunityActivityZitiRepository
     */
    private $entityZitiRepository;

    /**
     * @var CommunityChiefDistributorRepository
     */
    private $communityChiefDistributorRepository;

    /**
     * @var CommunityOrderRelActivityRepository
     */
    private $communityOrderRel;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(CommunityActivity::class);
        $this->entityItemRepository = app('registry')->getManager('default')->getRepository(CommunityActivityItem::class);
        $this->entityZitiRepository = app('registry')->getManager('default')->getRepository(CommunityActivityZiti::class);
        $this->communityChiefDistributorRepository = app('registry')->getManager('default')->getRepository(CommunityChiefDistributor::class);
        $this->communityOrderRel = app('registry')->getManager('default')->getRepository(CommunityOrderRelActivity::class);
    }

    /**
     * 活动状态
     */
    public const activity_status = [
        'private' => '草稿箱',
        'public' => '跟团中',
        'protected' => '暂停中',
        'success' => '已成团',
        'fail' => '成团失败',
    ];

    /**
     * 活动发货状态
     */
    public const activity_delivery_status = [
        'PENDING' => '未发货',
        'DONE' => '已发货',
        'SUCCESS' => '已收货',
    ];

    /**
     * 验证活动有效订单时订单的order_status状态
     */
    public const validActivityOrderStatus = [
        'PAYED',                // 已支付待自提
        'WAIT_BUYER_CONFIRM',   // 待收货
        'DONE',                 // 已结束
//        'CANCEL_WAIT_PROCESS',  // 取消待处理
    ];

    /**
     * 验证活动有效订单时订单的cancel_status状态
     */
    public const validActivityOrderCancelStatus = [
        'NO_APPLY_CANCEL',      // 未申请
//        'WAIT_PROCESS',         // 等待审核
        'FAILS',                // 取消失败
    ];

    /**
     * 添加活动
     * @param $params
     * @return array
     */
    public function createActivity($params)
    {
        $items = $params['items'] ?? [];
        unset($params['items']);
        $ziti = $params['ziti'] ?? [];
        unset($params['ziti']);

        $item_ids = array_values(array_unique(array_column($items, 'item_id')));
        if (empty($item_ids)) {
            throw new ResourceException('商品参数错误');
        }
        $itemService = new ItemsService();
        //多规格商品处理
        $itemsList = $itemService->getItemsList(['default_item_id' => $item_ids]);
        $itemsList = $itemService->replaceSkuSpec($itemsList);
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            if (!isset($params['activity_desc'])) {
                $params['activity_desc'] = '';
            }
            if (!isset($params['activity_pics'])) {
                $params['activity_pics'] = json_encode([]);
            }
            $activity = $this->entityRepository->create($params);
            if (!$activity) {
                throw new ResourceException('活动创建失败');
            }
            $batchInsertItems = [];
            foreach ($itemsList['list'] as $value) {
                if ($value['distributor_id'] != $activity['distributor_id']) {
                    throw new ResourceException('只能选择一个店铺的商品开团');   
                }

                $batchInsertItems[] = [
                    'activity_id' => $activity['activity_id'],
                    'goods_id' => $value['goods_id'],
                    'item_id' => $value['item_id'],
                    'item_name' => $value['item_name'],
                    'item_spec_desc' => $value['item_spec_desc'] ?? '',
                    'item_brief' => $value['brief'],
                    'item_pics' => json_encode($value['pics']),
                    'price' => $value['price'],
                    'cost_price' => $value['cost_price'],
                    'market_price' => $value['market_price'],
                    'store' => $value['store'],
                ];
            }
            if (!empty($batchInsertItems)) {
                $this->entityItemRepository->batchInsert($batchInsertItems);
            }
            $batchInsertZiti = [];
            foreach ($ziti as $value) {
                if (empty($value['ziti_id'])) {
                    throw new ResourceException('自提点参数错误');
                }
                $batchInsertZiti[] = [
                    'activity_id' => $activity['activity_id'],
                    'ziti_id' => $value['ziti_id'],
                    'condition_num' => $value['condition_num'] ?? 0,
                    'remark' => $value['remark'] ?? '',
                ];
            }
            if (!empty($batchInsertZiti)) {
                $this->entityZitiRepository->batchInsert($batchInsertZiti);
            }
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw new ResourceException($e->getMessage());
        }

        return $activity;
    }

    /**
     * 修改活动
     * @param $activity_id
     * @param $params
     * @return array
     */
    public function updateActivity($activity_id, $params)
    {
        $items = $params['items'] ?? [];
        unset($params['items']);
        $ziti = $params['ziti'] ?? [];
        unset($params['ziti']);

        $item_ids = array_values(array_unique(array_column($items, 'item_id')));
        if (empty($item_ids)) {
            throw new ResourceException('商品参数错误');
        }
        // 查询原有的活动商品列表
        $activityItemList = $this->entityItemRepository->getLists(['activity_id' => $activity_id]);
        $activityItemIds = array_column($activityItemList, 'item_id', 'id');
        // 查询原有的自提地点列表
        $activityZitiList = $this->entityZitiRepository->getLists(['activity_id' => $activity_id]);
        $activityZitiIds = array_column($activityZitiList, 'ziti_id', 'id');

        $itemService = new ItemsService();
        //多规格商品处理
        $itemsList = $itemService->getItemsList(['default_item_id' => $item_ids]);
        $itemsList = $itemService->replaceSkuSpec($itemsList);
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $activity = $this->entityRepository->getInfo(['activity_id' => $activity_id]);
            if (!$activity) {
                throw new ResourceException('无效的活动');
            }
            if (isset($params['distributor_id']) && $params['distributor_id'] != $activity['distributor_id']) {
                throw new ResourceException('不能修改活动绑定的店铺');   
            }

            if (!isset($params['activity_desc'])) {
                $params['activity_desc'] = '';
            }
            if (!isset($params['activity_pics'])) {
                $params['activity_pics'] = json_encode([]);
            }
            $activity = $this->entityRepository->updateOneBy(['activity_id' => $activity['activity_id']], $params);

            // 处理活动商品
            $batchInertItems = [];
            $updateIds = [];
            foreach ($itemsList['list'] as $value) {
                if ($value['distributor_id'] != $activity['distributor_id']) {
                    throw new ResourceException('只能选择一个店铺的商品开团');   
                }

                $data = [
                    'activity_id' => $activity['activity_id'],
                    'goods_id' => $value['goods_id'],
                    'item_id' => $value['item_id'],
                    'item_name' => $value['item_name'],
                    'item_spec_desc' => $value['item_spec_desc'] ?? '',
                    'item_brief' => $value['brief'],
                    'item_pics' => json_encode($value['pics']),
                    'price' => $value['price'],
                    'cost_price' => $value['cost_price'],
                    'market_price' => $value['market_price'],
                    'store' => $value['store'],
                ];

                if (in_array($value['item_id'], $activityItemIds)) {
                    $existId = array_flip($activityItemIds)[$value['item_id']];
                    $updateIds[] = $existId;
                    $this->entityItemRepository->updateOneBy(['id' => $existId], $data);
                    continue;
                }
                $batchInertItems[] = $data;
            }
            if (!empty($batchInertItems)) {
                $this->entityItemRepository->batchInsert($batchInertItems);
            }
            $deleteItemIds = array_diff(array_keys($activityItemIds), $updateIds);
            if (!empty($items) && !empty($deleteItemIds)) {
                $this->entityItemRepository->deleteBy([
                    'id' => $deleteItemIds,
                ]);
            }

            // 处理活动自提点
            $batchInsertZiti = [];
            $updateZitiIds = [];
            foreach ($ziti as $value) {
                if (empty($value['ziti_id'])) {
                    throw new ResourceException('自提点参数错误');
                }

                $data = [
                    'activity_id' => $activity['activity_id'],
                    'ziti_id' => $value['ziti_id'],
                    // 'condition_num' => $value['condition_num'] ?? 0,
                    // 'remark' => $value['remark'] ?? '',
                ];

                if (in_array($value['ziti_id'], $activityZitiIds)) {
                    $existZitiId = array_flip($activityZitiIds)[$value['ziti_id']];
                    $updateZitiIds[] = $existZitiId;
                    // $this->entityZitiRepository->updateOneBy(['id' => $existZitiId], $data);
                    continue;
                }
                $batchInsertZiti[] = $data;
            }
            if (!empty($batchInsertZiti)) {
                $this->entityZitiRepository->batchInsert($batchInsertZiti);
            }
            $deleteZitiIds = array_diff(array_keys($activityZitiIds), $updateZitiIds);
            if (!empty($ziti) && !empty($deleteZitiIds)) {
                $this->entityZitiRepository->deleteBy(['id' => $deleteZitiIds]);
            }

            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw new ResourceException($e->getMessage());
        }

        return $activity;
    }

    /**
     * 获取活动详情
     * @param $filter
     * @return array
     */
    public function getActivity($filter)
    {
        $activity = $this->entityRepository->getInfo($filter);
        if (empty($activity)) {
            throw new ResourceException('无效的活动');
        }
        $chiefService = new CommunityChiefService();
        $activity['chief_info'] = $chiefService->getInfo(['chief_id' => $activity['chief_id']]);

        $settingService = new CommunitySettingService($activity['company_id'], $activity['distributor_id']);
        $setting = $settingService->getSetting();
        $activity = array_merge($activity, $setting);

        $activity_item = $this->entityItemRepository->getLists(['activity_id' => $activity['activity_id']]);
        $activity_item = array_column($activity_item, null, 'item_id');
        $item_ids = array_values(array_unique(array_keys($activity_item)));
        $staticsItems = $this->staticsActivityItems($activity['activity_id']);
        if (!empty($item_ids)) {
            $itemService = new ItemsService();
            $items = $itemService->getItemsList(['item_id' => $item_ids], 1, count($item_ids));
            $items = array_column($items['list'] ?? [], null, 'item_id');
            $validItems = [];
            foreach ($items as $value) {
                if ($value['is_default']) {
                    if (!isset($validItems[$value['goods_id']])) {
                        $validItems[$value['goods_id']] = array_merge($activity_item[$value['item_id']], $value);
                    }
                } else {
                    if (isset($validItems[$value['goods_id']])) {
                        if (!isset($validItems[$value['goods_id']]['spec_items'])) {
                            $validItems[$value['goods_id']]['spec_items'][] = $validItems[$value['goods_id']];
                        }
                        $validItems[$value['goods_id']]['spec_items'][] = array_merge($activity_item[$value['item_id']], $value);
                    } else {
                        if (isset($items[$value['default_item_id']])) {
                            $validItems[$value['goods_id']] = array_merge($activity_item[$value['default_item_id']], $items[$value['default_item_id']]);
                            $validItems[$value['goods_id']]['spec_items'][] = $validItems[$value['goods_id']];
                            $validItems[$value['goods_id']]['spec_items'][] = array_merge($activity_item[$value['item_id']], $value);
                        } else {
                            $validItems[$value['goods_id']] = array_merge($activity_item[$value['item_id']], $value);
                        }
                    }
                }
            }

            if ($validItems) {
                $communityItemsService = new CommunityItemsService();
                $communityItems = $communityItemsService->lists(['goods_id' => array_values(array_unique(array_column($validItems, 'goods_id')))], 'goods_id,min_delivery_num');
                $communityItems = array_column($communityItems['list'], 'min_delivery_num', 'goods_id');
                $conn = app('registry')->getConnection('default');
                $criteria = $conn->createQueryBuilder();
                $itemBuyList = $criteria->select('i.goods_id,sum(oi.num) as buy_num,sum(oi.total_fee) as total_fee')
                ->from('orders_normal_orders_items', 'oi')
                ->leftJoin('oi', 'orders_normal_orders', 'o', 'oi.order_id = o.order_id')
                ->leftJoin('oi', 'items', 'i', 'oi.item_id = i.item_id')
                ->leftJoin('i', 'community_items', 'ci', 'i.goods_id = ci.goods_id')
                ->andWhere($criteria->expr()->eq('o.order_type', $criteria->expr()->literal('normal')))
                ->andWhere($criteria->expr()->eq('o.order_class', $criteria->expr()->literal('community')))
                ->andWhere($criteria->expr()->eq('o.act_id', $activity['activity_id']))
                ->andWhere($criteria->expr()->eq('o.order_status', $criteria->expr()->literal('PAYED')))
                ->andWhere(
                    $criteria->expr()->orX(
                        $criteria->expr()->eq('o.cancel_status', $criteria->expr()->literal('NO_APPLY_CANCEL')),
                        $criteria->expr()->eq('o.cancel_status', $criteria->expr()->literal('FAILS'))
                    )
                )->groupBy('i.goods_id')->execute()->fetchAll();

                if ($setting['condition_type'] == 'money') {
                    $totalFee = array_reduce($itemBuyList, function($totalFee, $item) {
                        $totalFee += $item['total_fee'];
                        return $totalFee;
                    });
                    $activity['total_fee'] = $totalFee;
                } else {
                    $itemBuyList = array_column($itemBuyList, 'buy_num', 'goods_id');
                    foreach ($validItems as $key => $value) {
                        $validItems[$key]['min_delivery_num'] = $communityItems[$value['goods_id']] ?? 0;
                        $validItems[$key]['buy_num'] = $itemBuyList[$value['goods_id']] ?? 0;
                    }
                }
            }

            $activity['items'] = array_values($validItems);
        }
        $activity_ziti = $this->entityZitiRepository->getLists(['activity_id' => $activity['activity_id']]);
        $ziti_ids = array_values(array_unique(array_column($activity_ziti, 'ziti_id')));
        if (!empty($ziti_ids)) {
            $chiefZitiService = new CommunityChiefZitiService();
            $zitiList = $chiefZitiService->getLists(['ziti_id' => $ziti_ids]);
            $zitiList = array_column($zitiList, null, 'ziti_id');
            foreach ($activity_ziti as $key => $value) {
                if (empty($zitiList[$value['ziti_id']])) {
                    $activity_ziti[$key] = null;
                    continue;
                }
                $activity_ziti[$key] = array_merge($value, $zitiList[$value['ziti_id']]);
            }
            $activity_ziti = array_values(array_filter($activity_ziti));
            $activity['ziti'] = $activity_ziti;
        }
        // 处理活动的订单统计
        $activityOrderStatics = $this->getActivityOrderTotalFee([$activity['activity_id']]);
        $activity['total_fee'] = $activityOrderStatics[$activity['activity_id']]['total_fee'] ?? 0;
        $activity['order_num'] = $activityOrderStatics[$activity['activity_id']]['order_num'] ?? 0;
        $activity['user_num'] = $activityOrderStatics[$activity['activity_id']]['user_num'] ?? 0;
        // 活动的支付状态订单数量
        $activityPayOrderNum = $this->getActivityCanWriteOff([$activity['activity_id']]);
        $activity['payed_order_num'] = $activityPayOrderNum[$activity['activity_id']]['order_num'] ?? 0;
        $activity['can_writeoff'] = $activityPayOrderNum[$activity['activity_id']]['order_num'] ?? 0;

        $timestamp = time();

        $activity['last_second'] = 0;
        if ($activity['activity_status'] == 'public') {
            $activity['last_second'] = $timestamp < $activity['end_time'] ? $activity['end_time'] - $timestamp : 0;
        }

        $hour = floor(($timestamp - $activity['created_at']) / 3600);
        $save_time = $hour . '小时前';
        if ($hour > 24) {
            $day = floor($hour / 24);
            $save_time = $day . '天前';
        }
        $activity['save_time'] = $save_time;

        //todo 扩展字段
        $activity['extra_data'] = [
            ['field_name' => '楼号', 'field_type' => 'text', 'is_numeric' => false, 'unit' => '楼/栋'],
            ['field_name' => '房号', 'field_type' => 'text', 'is_numeric' => false, 'unit' => '号/室'],
        ];

        // 获取活动的订单列表
        $activity['orders'] = $this->getActivityOrderList($activity['activity_id']);

        // 默认不是活动作者
        $activity['is_activity_author'] = false;
        $activity['buttons'] = [];
        if (in_array($activity['activity_status'], ['private', 'public', 'protected'])) {
            $activity['buttons'][] = 'update';
        }
        if ($activity['activity_status'] == 'public') {
            $activity['buttons'][] = 'success';
            $activity['buttons'][] = 'fail';
        }
        $activity['activity_status_msg'] = self::activity_status[$activity['activity_status']] ?? '数据错误';
        $activity['activity_delivery_status_msg'] = self::activity_delivery_status[$activity['delivery_status']] ?? '数据错误';
        $activity['show_buy'] = false;
        if ($activity['activity_status'] == 'public' && $activity['start_time'] < $timestamp && $timestamp < $activity['end_time']) {
            $activity['show_buy'] = true;
        }
        if ($activity['activity_status'] == 'public' && $timestamp < $activity['start_time']) {
            $activity['activity_status_msg'] = '未开始';
        }
        if ($activity['activity_status'] == 'public' && $activity['end_time'] < $timestamp) {
            $activity['activity_status_msg'] = '已结束';
        }
        return $activity;
    }

    /**
     * 获取活动列表
     * @param $filter
     * @param $page
     * @param $page_size
     * @return array
     */
    public function getActivityList($filter, $page, $page_size, $orderBy = [])
    {
        $lists = $this->entityRepository->lists($filter, '*', $page, $page_size, $orderBy);
        $activity_ids = array_column($lists['list'], 'activity_id');
        if (empty($activity_ids)) {
            return $lists;
        }
        $item_ids = [];
        $ziti_ids = [];
        $activityItemList = [];
        $activityZitiList = [];
        $items = [];
        $ziti = [];
        // 获取活动关联的商品ID集合和自提点ID集合
        if (!empty($activity_ids)) {
            $activityItemList = $this->entityItemRepository->getLists(['activity_id' => $activity_ids]);
            $item_ids = array_values(array_unique(array_column($activityItemList, 'item_id')));
            $activityZitiList = $this->entityZitiRepository->getLists(['activity_id' => $activity_ids]);
            $ziti_ids = array_values(array_unique(array_column($activityZitiList, 'ziti_id')));
        }
        // 获取商品列表
        if (!empty($item_ids)) {
            $itemService = new ItemsService();
            $items = $itemService->getItemsList(['item_id' => $item_ids]);
            $items = array_column($items['list'], null, 'item_id');
        }

        // 处理活动的商品数据
        foreach ($activityItemList as $key => $value) {
            unset($activityItemList[$key]);
            $key = $value['activity_id'].'_'.$value['item_id'];
            $activityItemList[$key] = $value;
        }
        $validItems = [];
        foreach ($activityItemList as $value) {
            if (isset($items[$value['item_id']])) {
                $item = $items[$value['item_id']];
                if ($item['is_default']) {
                    if (!isset($validItems[$value['activity_id'].'_'.$item['goods_id']])) {
                        $validItems[$value['activity_id'].'_'.$item['goods_id']] = array_merge($value, $item);
                    }
                } else {
                    if (isset($validItems[$value['activity_id'].'_'.$item['goods_id']])) {
                        if (!isset($validItems[$value['activity_id'].'_'.$item['goods_id']]['spec_items'])) {
                            $validItems[$value['activity_id'].'_'.$item['goods_id']]['spec_items'][] = $validItems[$value['activity_id'].'_'.$item['goods_id']];
                        }
                        $validItems[$value['activity_id'].'_'.$item['goods_id']]['spec_items'][] = array_merge($value, $item);
                    } else {
                        if (isset($items[$item['default_item_id']]) && isset($activityItemList[$value['activity_id'].'_'.$item['default_item_id']])) {
                            $validItems[$value['activity_id'].'_'.$item['goods_id']] = array_merge($activityItemList[$value['activity_id'].'_'.$item['default_item_id']], $items[$item['default_item_id']]);
                            $validItems[$value['activity_id'].'_'.$item['goods_id']]['spec_items'][] = $validItems[$value['activity_id'].'_'.$item['goods_id']];
                            $validItems[$value['activity_id'].'_'.$item['goods_id']]['spec_items'][] = array_merge($value, $item);
                        } else {
                            $validItems[$value['activity_id'].'_'.$item['goods_id']] = array_merge($value, $item);
                        }
                    }
                }
            }
        }

        // 获取自提点列表
        if (!empty($ziti_ids)) {
            $zitiService = new CommunityChiefZitiService();
            $ziti = $zitiService->getLists(['ziti_id' => $ziti_ids], '*', -1, -1);
            $ziti = array_column($ziti, null, 'ziti_id');
        }
        // 处理活动的自提点列表
        foreach ($activityZitiList as $key => $value) {
            if (empty($ziti[$value['ziti_id']])) {
                $activityZitiList[$key] = null;
                continue;
            }
            $activityZitiList[$key] = array_merge($value, $ziti[$value['ziti_id']]);
        }
        $activityZitiList = array_values(array_filter($activityZitiList));
        // 处理活动的订单统计
        $activityOrderStatics = $this->getActivityOrderTotalFee($activity_ids);
        // 活动的支付状态订单数量
        $activityPayOrderNum = $this->getActivityCanWriteOff($activity_ids);

        $timestamp = time();
        foreach ($lists['list'] as $key => $value) {
            if (!empty($value['start_time'])) {
                $lists['list'][$key]['start_time'] = date('Y-m-d H:i:s', $value['start_time']);
            }
            if (!empty($value['end_time'])) {
                $lists['list'][$key]['end_time'] = date('Y-m-d H:i:s', $value['end_time']);
            }
            $lists['list'][$key]['items'] = [];
            $lists['list'][$key]['ziti'] = [];
            $min = 0;
            $max = 0;
            foreach ($validItems as $item) {
                if ($value['activity_id'] != $item['activity_id']) {
                    continue;
                }
                $lists['list'][$key]['items'][] = $item;
                if ($min == 0) {
                    $min = $item['price'];
                } else {
                    $min = $item['price'] < $min ? $item['price'] : $min;
                }
                $max = $item['price'] > $max ? $item['price'] : $max;
            }
            $price_range = '¥ ' . ($min / 100);
            if ($max > $min) {
                $price_range .= ' - ¥ ' . ($max / 100);
            }
            $lists['list'][$key]['min_price'] = $min;
            $lists['list'][$key]['max_price'] = $max;
            $lists['list'][$key]['price_range'] = $price_range;
            foreach ($activityZitiList as $ziti_value) {
                if ($value['activity_id'] != $ziti_value['activity_id']) {
                    continue;
                }
                $lists['list'][$key]['ziti'][] = $ziti_value;
            }
            $lists['list'][$key]['activity_status_msg'] = self::activity_status[$value['activity_status']] ?? '数据错误';
            $lists['list'][$key]['activity_delivery_status_msg'] = self::activity_delivery_status[$value['delivery_status']] ?? '数据错误';
            if ($value['activity_status'] == 'public' && $timestamp < $value['start_time']) {
                $lists['list'][$key]['activity_status_msg'] = '未开始';
            }
            if ($value['activity_status'] == 'public' && $value['end_time'] < $timestamp) {
                $lists['list'][$key]['activity_status_msg'] = '已结束';
            }

            $lists['list'][$key]['total_fee'] = $activityOrderStatics[$value['activity_id']]['total_fee'] ?? 0;
            $lists['list'][$key]['order_num'] = $activityOrderStatics[$value['activity_id']]['order_num'] ?? 0;
            $lists['list'][$key]['user_num'] = $activityOrderStatics[$value['activity_id']]['user_num'] ?? 0;

            $lists['list'][$key]['last_second'] = 0;
            if ($value['activity_status'] == 'public') {
                $lists['list'][$key]['last_second'] = $timestamp < $value['end_time'] ? $value['end_time'] - $timestamp : 0;
            }
            $lists['list'][$key]['created_at_time'] = date('Y-m-d H:i:s', $value['created_at']);
            $hour = floor(($timestamp - $value['created_at']) / 3600);
            $save_time = $hour . '小时前';
            if ($hour > 24) {
                $day = floor($hour / 24);
                $save_time = $day . '天前';
            }
            $lists['list'][$key]['save_time'] = $save_time;
            $nowTime = time();
            $lists['list'][$key]['activity_process_msg'] = $lists['list'][$key]['activity_status_msg'];
            //private草稿 public已发布 protected已暂停 success确认成团 fail成团失败
            if ($nowTime >= $value['end_time'] && $value['activity_status']=='public') {
                $lists['list'][$key]['activity_process'] = 'end';    //已结束
                $lists['list'][$key]['activity_process_msg'] = '已结束';    //已结束
            } elseif ($nowTime >= $value['start_time'] && $nowTime < $value['end_time'] && $value['activity_status']=='public') {
                $lists['list'][$key]['activity_process'] = 'ongoing';         //进行中
                $lists['list'][$key]['activity_process_msg'] = '进行中';    //进行中
            } elseif ($nowTime < $value['start_time'] && $value['activity_status']=='public') {
                $lists['list'][$key]['activity_process'] = 'waiting';   //未开始
                $lists['list'][$key]['activity_process_msg'] = '未开始';    //未开始
            }
            // 是否可以核销
            $lists['list'][$key]['can_writeoff'] = false;
            $lists['list'][$key]['payed_order_num'] = $activityPayOrderNum[$value['activity_id']]['order_num'] ?? 0;
            if ($value['delivery_status'] == 'SUCCESS') {
                $lists['list'][$key]['can_writeoff'] = $lists['list'][$key]['payed_order_num'] > 0;
            }
        }
        return $lists;
    }

    /**
     * 获取活动的支付状态订单的数量
     * @param $activity_id
     * @return array
     */
    public function getActivityCanWriteOff($activity_ids)
    {
        $conn = app("registry")->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $sql = 'SELECT rel.activity_id,count(orders.order_id) as order_num FROM community_order_rel_activity as rel INNER JOIN orders_normal_orders as `orders` on rel.order_id=`orders`.order_id WHERE
        orders.order_status="PAYED" and cancel_status in ("NO_APPLY_CANCEL", "FAILS") and ';
        if (is_array($activity_ids)) {
            $sql .= ' rel.activity_id in ("' . implode('","', $activity_ids) . '") and ';
        } else {
            $sql .= ' rel.activity_id = ' . $qb->expr()->literal($activity_ids) . ' and ';
        }
        $sql = trim($sql, 'and ');
        $sql .= ' GROUP BY rel.activity_id';
        $lists = $conn->executeQuery($sql)->fetchAll();
        $lists = array_column($lists, null, 'activity_id');
        return $lists;
    }

    /**
     * 处理活动的订单统计
     * @param $activity_ids
     * @return array
     */
    public function getActivityOrderTotalFee($activity_ids)
    {
        $conn = app("registry")->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $sql = 'SELECT rel.activity_id,SUM(`orders`.total_fee) as total_fee,count(`orders`.order_id) as order_num,COUNT(DISTINCT orders.user_id) as user_num
FROM community_order_rel_activity as rel INNER JOIN orders_normal_orders as `orders` on rel.order_id=`orders`.order_id WHERE ';
        if (is_array($activity_ids)) {
            $sql .= ' rel.activity_id in ("' . implode('","', $activity_ids) . '") ';
        } else {
            $sql .= ' rel.activity_id = ' . $qb->expr()->literal($activity_ids) . ' ';
        }
        if (!empty(self::validActivityOrderStatus)) {
            $sql .= ' and orders.order_status in ("' . implode('","', self::validActivityOrderStatus) . '")';
        }
        if (!empty(self::validActivityOrderCancelStatus)) {
            $sql .= ' and orders.cancel_status in ("' . implode('","', self::validActivityOrderCancelStatus) . '")';
        }
        $sql .= ' GROUP BY rel.activity_id';
        $lists = $conn->executeQuery($sql)->fetchAll();
        $lists = array_column($lists, null, 'activity_id');
        return $lists;
    }

    /**
     * 统计活动商品的购买数量
     * @param $activity_id
     * @return array
     */
    public function staticsActivityItems($activity_id)
    {
        $conn = app("registry")->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $sql = 'SELECT items.item_id,SUM(items.num) as item_num
FROM community_order_rel_activity as rel INNER JOIN orders_normal_orders as orders ON rel.order_id=orders.order_id LEFT JOIN orders_normal_orders_items as items on rel.order_id=items.order_id
WHERE rel.activity_id='.$qb->expr()->literal($activity_id);
        if (!empty(self::validActivityOrderStatus)) {
            $sql .= ' and orders.order_status in ("' . implode('","', self::validActivityOrderStatus) . '")';
        }
        if (!empty(self::validActivityOrderCancelStatus)) {
            $sql .= ' and orders.cancel_status in ("' . implode('","', self::validActivityOrderCancelStatus) . '")';
        }
        $sql .= ' GROUP BY items.item_id';
        $lists = $conn->executeQuery($sql)->fetchAll();
        $lists = array_column($lists, null, 'item_id');
        return $lists;
    }

    /**
     * 确认发货
     * @param $activity_id
     * @return array
     */
    public function updateConfirmStatus($filter)
    {
        return $this->entityRepository->updateOneBy($filter, [
            'delivery_status' => 'DONE',
            'delivery_time' => time(),
        ]);
    }

    /**
     * 团长确认收货
     * @param $activity_id
     * @return array
     */
    public function chiefConfirmDelivery($chief_id, $activity_id)
    {
        $activity = $this->entityRepository->getInfo(['activity_id' => $activity_id]);
        if (empty($activity)) {
            throw new ResourceException('无效的活动');
        }
        if ($chief_id != $activity['chief_id']) {
            throw new ResourceException('没有当前活动的操作权限');
        }
        if ($activity['activity_status'] != 'success') {
            throw new ResourceException('未成团的活动不能确认收货');
        }
        if ($activity['delivery_status'] != 'DONE') {
            throw new ResourceException('未发货的活动不能确认收货');
        }
        return $this->entityRepository->updateOneBy(['activity_id' => $activity_id], [
            'delivery_status' => 'SUCCESS',
        ]);
    }

    //店铺操作发货
    public function deliver($params)
    {
        if (!isset($params['distributor_id']) || !$params['activity_id']) {
            throw new ResourceException('没有当前活动的操作权限');
        }

        //活动
        $activity = $this->entityRepository->getInfo(['activity_id' => $params['activity_id'] ]);
        if (empty($activity)) {
            throw new ResourceException('无效的活动');
        }

        if ($activity['distributor_id'] != $params['distributor_id']) {
            throw new ResourceException('没有当前活动的操作权限');
        }

        if ($activity['activity_status'] != 'success') {
            throw new ResourceException('未成团的活动不能发货');
        }

        return $this->entityRepository->updateOneBy(['activity_id' => $params['activity_id'] ], [
            'delivery_status' => 'DONE',
            'delivery_time' => time()
        ]);
    }

    /**
     * 获取活动跟团记录
     * @param $activity_id
     * @return array
     */
    public function getActivityOrderList($activity_id)
    {
        $filter = [
            'activity_id' => $activity_id,
            'activity_trade_no|notnull' => 0,
        ];
        $orderRel = $this->communityOrderRel->getLists($filter, '*', 1, 20, ['created' => 'desc']);
        $orderIds = array_values(array_unique(array_column($orderRel, 'order_id')));
        if (empty($orderIds)) {
            return [];
        }

        // 获取订单商品
        $orderItemRepository = app('registry')->getManager('default')->getRepository(NormalOrdersItems::class);
        $orderItems = $orderItemRepository->getList(['order_id' => $orderIds]);
        $orderItems = $orderItems['list'] ?? [];
        if (empty($orderItems)) {
            return [];
        }
        $user_ids = array_values(array_unique(array_column($orderItems, 'user_id')));
        foreach ($orderItems as $key => $value) {
            unset($orderItems[$key]);
            $orderItems[$value['order_id']][] = [
                'item_name' => $value['item_name'],
                'num' => $value['num'],
                'user_id' => $value['user_id'],
            ];
        }

        // 获取下单用户信息
        $memberInfoRepository = app('registry')->getManager('default')->getRepository(MembersInfo::class);
        $memberList = $memberInfoRepository->getListNotPagination(['user_id' => $user_ids]);
        if (empty($memberList)) {
            return [];
        }
        $memberList = array_column($memberList, null, 'user_id');

        $result = [];
        $timestamp = time();
        foreach ($orderRel as $key => $value) {
            $value_item = $orderItems[$value['order_id']] ?? [];
            if (empty($value_item)) {
                continue;
            }
            $value_user = $memberList[$value_item[0]['user_id']];
            if (empty($value_user)) {
                continue;
            }
            $hour = floor(($timestamp - $value['created']) / 3600);
            $save_time = $hour . '小时前';
            if ($hour > 24) {
                $day = floor($hour / 24);
                $save_time = $day . '天前';
            }
            $result[] = [
                'activity_trade_no' => $value['activity_trade_no'],
                'order_id' => $value['order_id'],
                'username' => $value_user['username'] ?? '',
                'avatar' => $value_user['avatar'] ?? '',
                'created' => $value['created'],
                'item_name' => $value_item[0]['item_name'].(count($value_item) > 1 ? '...' : ''),
                'num' => $value_item[0]['num'],
                'save_time' => $save_time,
            ];
        }

        return $result;
    }

    /**
     * 修嘎团状态
     * @param $activity_id
     * @param $activity_status
     * @return array
     */
    public function updateActivityStatus($activity_id, $activity_status, $chief_id = 0)
    {
        if (!in_array($activity_status, array_keys(self::activity_status))) {
            throw new ResourceException('活动状态错误');
        }

        $activity = $this->entityRepository->getInfo(['activity_id' => $activity_id]);
        if (empty($activity)) {
            throw new ResourceException('无效的活动');
        }

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            // 修改活动状态
            $result = $this->entityRepository->updateOneBy(['activity_id' => $activity_id], ['activity_status' => $activity_status]);

            if ($activity_status == 'fail') {
                // 成团失败，取消所有订单
                $this->cancelActivityOrders($activity_id, 'all', $chief_id);
            }
            if ($activity_status == 'success') {
                // 成团成功，取消未支付订单
                $this->cancelActivityOrders($activity_id, 'notpay', $chief_id);
            }
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw new ResourceException($e->getMessage());
        }

        return $result;
    }

    /**
     * 成团失败时批量取消团订单
     * @param $activity_id
     * @param $userQueue
     * @return false|void
     */
    public function cancelActivityOrders($activityId, $orderStatus = 'all', $chiefId = 0, $userQueue = true)
    {
        $filter = [
            'order_class' => 'community',
            'order_type' => 'normal',
            'act_id' => $activityId,
            'cancel_status' => ['NO_APPLY_CANCEL', 'FAILS'],
        ];
        switch ($orderStatus) {
            case 'all':
                $filter['order_status'] = ['NOTPAY', 'PAYED'];
                $reason = '成团失败，取消订单并退款';
                break;
            case 'notpay':
                $filter['order_status'] = 'NOTPAY';
                $reason = '已成团，取消未支付订单';
                break;
        }

        $orderService = new CommunityNormalOrderService();
        $count = $orderService->countOrderNum($filter);
        if (!$count) {
            return false;
        }

        $limit = 50;
        $pages = ceil($count / $limit);
        for ($offset = 0; $offset < $pages; $offset++) {
            $list = $orderService->normalOrdersRepository->getList($filter, $offset, $limit, ['create_time' => 'ASC'], 'company_id,order_id,user_id,mobile');
            $rows = [];
            foreach ($list as $row) {
                $data = [
                    'company_id' => $row['company_id'],
                    'order_id' => $row['order_id'],
                    'cancel_reason' => $reason,
                    'user_id' => $row['user_id'],
                    'mobile' => $row['mobile'],
                    'cancel_from' => 'system',
                ];
                if ($chiefId > 0) {
                    $data['cancel_from'] = 'chief'; //团长取消订单
                    $data['chief_id'] = $chiefId;
                }
                $rows[] = $data;
            }
            if (empty($rows)) {
                continue;
            }
            if ($userQueue) {
                $gotoJob = (new CancelActivityOrdersJob($rows))->onQueue('slow');
                app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
            } else {
                (new CancelActivityOrdersJob($rows))->handle();
            }
        }
    }

    /**
     * 获取活动名
     * @param  [type] $activity_id
     * @return [type]
     */
    public function getActivityName($activity_id)
    {
        if (!$activity_id) {
            return '';
        }
        $activity = $this->entityRepository->getInfo(['activity_id' => $activity_id]);
        if (!$activity) {
            return '';
        }

        return $activity['activity_name'];
    }

    public function scheduleAutoFinishActivity()
    {
        $filter = [
            'activity_status' => 'public',
            'end_time|lte' => time(),
        ];
        $lists = $this->entityRepository->lists($filter, 'company_id,distributor_id,activity_id');
        $conn = app('registry')->getConnection('default');
        foreach ($lists['list'] as $row) {
            $criteria = $conn->createQueryBuilder();
            $itemList = $criteria->select('i.goods_id,sum(oi.num) as buy_num,sum(oi.total_fee) as total_fee,min(ci.min_delivery_num) as min_delivery_num')
                ->from('orders_normal_orders_items', 'oi')
                ->leftJoin('oi', 'orders_normal_orders', 'o', 'oi.order_id = o.order_id')
                ->leftJoin('oi', 'items', 'i', 'oi.item_id = i.item_id')
                ->leftJoin('i', 'community_items', 'ci', 'i.goods_id = ci.goods_id')
                ->andWhere($criteria->expr()->eq('o.order_type', $criteria->expr()->literal('normal')))
                ->andWhere($criteria->expr()->eq('o.order_class', $criteria->expr()->literal('community')))
                ->andWhere($criteria->expr()->eq('o.act_id', $row['activity_id']))
                ->andWhere($criteria->expr()->eq('o.order_status', $criteria->expr()->literal('PAYED')))
                ->andWhere(
                    $criteria->expr()->orX(
                        $criteria->expr()->eq('o.cancel_status', $criteria->expr()->literal('NO_APPLY_CANCEL')),
                        $criteria->expr()->eq('o.cancel_status', $criteria->expr()->literal('FAILS'))
                    )
                )->groupBy('i.goods_id')->execute()->fetchAll();

            if (!$itemList) {
                $this->updateActivityStatus($row['activity_id'], 'fail');
            }

            //判断是否成团，只有已下单的商品全部满足起送量才能成团
            $fail = false;
            $settingService = new CommunitySettingService($row['company_id'], $row['distributor_id']);
            $setting = $settingService->getSetting();
            if ($setting['condition_type'] == 'money') {
                if ($setting['condition_money'] > 0) {
                    $totalFee = array_reduce($itemList, function($totalFee, $item) {
                        $totalFee += $item['total_fee'];
                        return $totalFee;
                    });
                    if ($totalFee < bcmul($setting['condition_money'], 100)) {
                        $this->updateActivityStatus($row['activity_id'], 'fail');
                        $fail = true;
                    }
                }
            } else {
                foreach ($itemList as $item) {
                    if (!$fail && $item['min_delivery_num'] > 0 && $item['buy_num'] < $item['min_delivery_num']) {
                        $this->updateActivityStatus($row['activity_id'], 'fail');
                        $fail = true;
                    }
                }
            }

            if (!$fail) {
                $this->updateActivityStatus($row['activity_id'], 'success');
            }
        }
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
