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

namespace PopularizeBundle\Services;

use AftersalesBundle\Entities\Aftersales;
use AftersalesBundle\Entities\AftersalesDetail;
use CompanysBundle\Traits\GetDefaultCur;
use OrdersBundle\Entities\NormalOrders;
use OrdersBundle\Traits\GetOrderServiceTrait;
use MembersBundle\Services\MemberService;
use OrdersBundle\Services\OrderAssociationService;

use PopularizeBundle\Entities\TaskBrokerage;
use PopularizeBundle\Entities\TaskBrokerageCount;
use GoodsBundle\Services\ItemsService;

class TaskBrokerageService
{
    use GetOrderServiceTrait;
    use GetDefaultCur;

    public $taskBrokerageRepository;
    public $taskBrokerageCountRepository;
    public function __construct()
    {
        $this->taskBrokerageRepository = app('registry')->getManager('default')->getRepository(TaskBrokerage::class);
        $this->taskBrokerageCountRepository = app('registry')->getManager('default')->getRepository(TaskBrokerageCount::class);
    }

    /**
     * 推广员任务制返佣记录保存
     */
    public function promoterTaskBrokerage($order)
    {
        $settingService = new SettingService();
        $config = $settingService->getConfig($order['company_id']);
        $rebateGoodsMode = $config['goods'];

        if (!isset($config['isOpenShop']) || $config['isOpenShop'] != 'true') {
            // 未开启虚拟店铺
            return true;
        }

        $orderService = $this->getOrderServiceByOrderInfo($order);
        $orderInfo = $orderService->getOrderInfo($order['company_id'], $order['order_id']);
        $orderInfo = $orderInfo['orderInfo'];

        // 如果购买商品不需要进行统计
        if (!isset($orderInfo['items'])) {
            return true;
        }

        // 如果不是在分销店铺中购买
        if (!$order['promoter_shop_id']) {
            return true;
        }

        $itemIds = array_column($orderInfo['items'], 'item_id');
        $itemsService = new ItemsService();
        $itemList = $itemsService->getItemsLists(['item_id' => $itemIds], 'item_id,rebate,rebate_type,item_name, rebate_conf,item_bn,distributor_id');
        if (!$itemList) {
            return [];
        }

        foreach ($itemList as $row) {
            //如果商品不是任务制
            if (!$row['rebate_type'] || $row['rebate_type'] == 'default') {
                continue;
            }

            if (($rebateGoodsMode == 'all' && !$row['distributor_id']) || $row['rebate']) {
                $this->__saveTaskBrokerage($orderInfo, $row, $order['promoter_shop_id']);
            }
        }
        return true;
    }

    private function __saveTaskBrokerage($orderInfo, $row, $promoterShopId = null)
    {
        $orderId = $orderInfo['order_id'];
        $buyuserId = $orderInfo['user_id'];
        $companyId = $orderInfo['company_id'];

        $filter = [
            'plan_date' => date('Y-m-t'),
            'order_id' => $orderId,
            'item_id' => $row['item_id'],
            'company_id' => $companyId,
        ];
        $info = $this->taskBrokerageRepository->getInfo($filter);

        $orderInfoItems = array_column($orderInfo['items'], null, 'item_id');
        $subOrder = $orderInfoItems[$row['item_id']];

        if ($orderInfo['order_status'] == 'DONE') {
            $status = 'finish';
        } elseif ($orderInfo['order_status'] == 'REFUND_SUCCESS' || $orderInfo['order_status'] == 'CANCEL') {
            $status = 'close';
        } elseif (in_array($subOrder['aftersales_status'], ['SELLER_SEND_GOODS', 'REFUND_SUCCESS', 'CLOSED'])) {
            $status = 'close';
        } else {
            $status = 'wait';
        }

        // 如果是关闭账期
        if ($status == 'close') {
            $finishFilter = [
                'order_id' => $orderId,
                'item_id' => $row['item_id'],
                'company_id' => $companyId,
                'status' => 'finish',
            ];
            $tempInfo = $this->taskBrokerageRepository->getInfo($finishFilter);
            if ($tempInfo) {
                // 有已经完成的账期，那么则不需要再关闭了
                return true;
            }
        }

        if ($info) {
            if ($status != $info['status']) {
                $updateData['status'] = $status;
                $data = $this->taskBrokerageRepository->updateBy($filter, $updateData);
                $info['status'] = $status;
                $this->TaskBrokerageCountRow($row, $subOrder, $info, false);
            }
        } else {
            $data = [
                'order_id' => $orderId,
                'item_id' => $row['item_id'],
                'user_id' => $promoterShopId, // 推广员开启店铺id
                'item_name' => $subOrder['item_name'],
                'item_spec_desc' => $subOrder['item_spec_desc'],
                'buy_user_id' => $buyuserId,
                'company_id' => $companyId,
                'price' => round(round(floatval($subOrder['fee_rate']), 4) * $subOrder['total_fee']),
                'status' => $status,
                'plan_date' => date('Y-m-t'),
                'num' => $subOrder['num'],
                'created' => time(),
                'updated' => time(),
            ];
            $this->taskBrokerageRepository->create($data);
            $this->TaskBrokerageCountRow($row, $subOrder, $data, true);
        }
        return true;
    }

    public function updateTaskBrokerage($companyId, $orderId)
    {
        $orderAssociationService = new OrderAssociationService();
        $order = $orderAssociationService->getOrder($companyId, $orderId);

        $this->promoterTaskBrokerage($order);

        return true;
    }

    public function TaskBrokerageCountRow($itemInfo, $subOrder, $taskBrokerageInfo, $isCreate)
    {
        $data = $this->taskBrokerageCountRepository->getInfo([
            'company_id' => $taskBrokerageInfo['company_id'],
            'item_id' => $taskBrokerageInfo['item_id'],
            'user_id' => $taskBrokerageInfo['user_id'],
            'plan_date' => $taskBrokerageInfo['plan_date']
        ]);

        switch ($taskBrokerageInfo['status']) {
        case 'wait':
            $statusCol = 'wait_num';
            break;
        case 'close':
            $statusCol = 'close_num';
            break;
        default:
            $statusCol = 'finish_num';
            break;
        }

        if ($data) {
            $updateData[$statusCol] = $data[$statusCol];
            $updateData[$statusCol] += $taskBrokerageInfo['num'];

            if ($statusCol == 'finish_num') {
                // 新增总销售额
                $this->addTotalFee($data['company_id'], $data['user_id'], $data['total_fee']);

                $updateData['total_fee'] = $data['total_fee'];
                $updateData['total_fee'] += $taskBrokerageInfo['price'];

                $data['total_fee'] = $updateData['total_fee'];
                $data['finish_num'] = $updateData['finish_num'];

                $updateData['rebate_money'] = $this->getRebateMoney($data);
            }
            if (!$isCreate) {
                $updateData['wait_num'] = $data['wait_num'];
                $updateData['wait_num'] -= $taskBrokerageInfo['num'];
            }
            $this->taskBrokerageCountRepository->updateBy(['id' => $data['id']], $updateData);
        } else {
            $insertData = [
                'rebate_type' => $itemInfo['rebate_type'],
                'rebate_conf' => json_decode($itemInfo['rebate_conf'], true),
                'item_id' => $taskBrokerageInfo['item_id'],
                'total_fee' => ($statusCol == 'finish_num') ? $taskBrokerageInfo['price'] : 0,
                'item_name' => $itemInfo['item_name'],
                'item_bn' => $itemInfo['item_bn'],
                'item_spec_desc' => $taskBrokerageInfo['item_spec_desc'],
                'user_id' => $taskBrokerageInfo['user_id'],
                'company_id' => $taskBrokerageInfo['company_id'],
                'rebate_money' => 0,
                'finish_num' => 0,
                'wait_num' => 0,
                'close_num' => 0,
                'plan_date' => $taskBrokerageInfo['plan_date'],
                'created' => time(),
                'updated' => time(),
            ];
            $insertData[$statusCol] = $taskBrokerageInfo['num'];
            $insertData['rebate_money'] = $this->getRebateMoney($insertData);

            if ($insertData['total_fee']) {
                // 新增总销售额
                $this->addTotalFee($insertData['company_id'], $insertData['user_id'], $insertData['total_fee']);
            }
            $this->taskBrokerageCountRepository->create($insertData);
        }
    }

    /**
     * 获取小店提成积分数量
     *
     * @param int $companyId
     * @param int $userId
     * @return mixed
     */
    public function getTaskPromoterRebatePoint(int $companyId, int $userId)
    {
        $filter = [
            'user_id' => $userId,
            'company_id' => $companyId
        ];
        return $this->taskBrokerageCountRepository->sum('rebate_point', $filter);
    }

    public function getTaskPromoterRebate(int $companyId, int $userId)
    {
        $filter = [
            'user_id' => $userId,
            'company_id' => $companyId
        ];
        return $this->taskBrokerageCountRepository->sum('rebate_money', $filter);
    }

    /**
     * 新增总销售额
     */
    public function addTotalFee($companyId, $promoterShopId, $money)
    {
        $hashKey = floor($promoterShopId / 20);
        app('redis')->hincrby('promoterPopularizeTaskFeeTotal:'.$hashKey, $promoterShopId, $money);

        return true;
    }

    /**
     * 获取总销售额
     */
    public function getTotalFee($companyId, $promoterShopId)
    {
        $hashKey = floor($promoterShopId / 20);
        $money = app('redis')->hget('promoterPopularizeTaskFeeTotal:'.$hashKey, $promoterShopId);
        return $money ? intval($money) : 0;
    }

    /**
     *  统计分销店铺前一个月售卖商品
     */
    public function handleTaskBrokerageCount()
    {
        // 获取当月
        $filter['plan_date'] = date("Y-m-t", strtotime("-1 day"));

        // 后续优化
        $this->taskBrokerageCountRepository->deleteBy($filter);

        $lists = $this->taskBrokerageRepository->getMonthCount($filter);
        if ($lists['total_count'] < 0) {
            return true;
        }
        $itemIds = array_column($lists['list'], 'item_id');
        $itemsService = new ItemsService();
        $itemLists = $itemsService->getItemsLists(['item_id' => $itemIds], 'item_id, rebate_type, rebate_conf, item_bn');
        $itemLists = array_column($itemLists, null, 'item_id');
        foreach ($lists['list'] as $row) {
            $itemId = $row['item_id'];
            $row['rebate_conf'] = isset($itemLists[$itemId]) ? $itemLists[$itemId]['rebate_conf'] : '';
            $row['rebate_type'] = isset($itemLists[$itemId]) ? $itemLists[$itemId]['rebate_type'] : 'default';
            $row['item_bn'] = isset($itemLists[$itemId]) ? $itemLists[$itemId]['item_bn'] : '';
            $this->replaceRow($row);
        }
        return true;
    }

    private function replaceRow($value)
    {
        switch ($value['status']) {
        case 'wait':
            $statusCol = 'wait_num';
            $count = $value['_count'];
            break;
        case 'close':
            $statusCol = 'close_num';
            $count = $value['_count'];
            break;
        default:
            $statusCol = 'finish_num';
            $count = $value['_count'];
            break;
        }

        $data = $this->taskBrokerageCountRepository->getInfo(['item_id' => $value['item_id'], 'user_id' => $value['user_id'], 'plan_date' => $value['plan_date']]);
        if ($data) {
            $updateData = [
                'updated' => time(),
                'total_fee' => $value['total_fee'],
                $statusCol => $count,
            ];
            if ($statusCol == 'finish_num') {
                $data['finish_num'] = $count;
                $data['total_fee'] = $updateData['total_fee'];
            }
            $updateData['rebate_money'] = $this->getRebateMoney($data);
            $this->taskBrokerageCountRepository->updateBy(['id' => $data['id']], $updateData);
        } else {
            $insertData = [
                'rebate_type' => $value['rebate_type'],
                'rebate_conf' => $value['rebate_conf'],
                'item_id' => $value['item_id'],
                'total_fee' => $value['price'],
                'item_name' => $value['item_name'],
                'item_spec_desc' => $value['item_spec_desc'],
                'user_id' => $value['user_id'],
                'company_id' => $value['company_id'],
                'item_bn' => $value['item_bn'],
                'finish_num' => 0,
                'rebate_money' => 0,
                'wait_num' => 0,
                'close_num' => 0,
                'plan_date' => $value['plan_date'],
                'created' => time(),
                'updated' => time(),
            ];
            $insertData[$statusCol] = $count;
            $this->taskBrokerageCountRepository->create($insertData);
        }
    }

    /**
     * 获取任务制佣金列表
     *
     * @param array $filter
     * @param string $col
     */
    public function getTaskBrokerageList($filter, $col = "*", $page, $pageSize, $orderBy)
    {
        $list = $this->taskBrokerageRepository->lists($filter, $col, $page, $pageSize, $orderBy);
        if ($list['total_count'] < 0) {
            return $list;
        }

        $userIds = array_column($list['list'], 'user_id');
        $userIds = array_merge($userIds, array_column($list['list'], 'buy_user_id'));
        $memberList = [];
        if ($userIds) {
            $memberService = new MemberService();
            $userIds = array_unique($userIds);
            $memberMobiles = $memberService->getMobileByUserIds($filter['company_id'], $userIds);
        }

        foreach ($list['list'] as &$row) {
            $row['promoter_mobile'] = $memberMobiles[$row['user_id']] ?? '';
            $row['buy_mobile'] = $memberMobiles[$row['buy_user_id']] ?? '';
        }
        return $list;
    }

    public function getTaskBrokerageCountList($filter, $col = "*", $page, $pageSize, $orderBy = array())
    {
        $list = $this->taskBrokerageCountRepository->lists($filter, $col, $page, $pageSize, $orderBy);
        if ($list['total_count'] < 0) {
            return $list;
        }

        $userIds = array_column($list['list'], 'user_id');
        if ($userIds) {
            $memberService = new MemberService();
            $userIds = array_unique($userIds);
            $memberMobiles = $memberService->getMobileByUserIds($filter['company_id'], $userIds);
        }
        $userIds = array_column($list['list'], 'user_id');
        $itemIds = array_column($list['list'], 'item_id');
        $userIds = array_unique($userIds);
        $itemIds = array_unique($itemIds);

        $params = [
            'company_id' => $filter['company_id'],
            'item_id' => $itemIds,
            'user_id' => $userIds,
        ];
        if (!$params['item_id'] || !$params['user_id']) {
            $taskBrokerageList = [
                'total_count' => 0,
                'list' => [],
            ];
        } else {
            $taskBrokerageList = $this->taskBrokerageRepository->lists($params);
        }

        if ($taskBrokerageList['list']) {
            $orderStatus = [
                'NOTPAY' => '未支付',
                'CANCEL' => '已取消',
                'CANCEL_WAIT_PROCESS' => '取消待处理',
                'DONE' => '已完成',
                'PAYED' => '已支付',
                'REFUND_SUCCESS' => '已退款',
                'WAIT_BUYER_CONFIRM' => '待收货',
                'REVIEW_PASS' => '审核通过待出库',
                'WAIT_GROUPS_SUCCESS' => '等待成团',
                'REFUND_PROCESS' => '退款处理中',
            ];
            $statusMsg = [
                'wait' => '待统计',
                'finish' => '已完成',
                'close' => '关闭',
            ];
            $orderIds = array_column($taskBrokerageList['list'], 'order_id');
            $normalOrderRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
            $orderList = $normalOrderRepository->getList(['order_id' => $orderIds]);

            $aftersalesRepository = app('registry')->getManager('default')->getRepository(Aftersales::class);
            $aftersalesList = $aftersalesRepository->getList(['order_id' => $orderIds, 'company_id' => $filter['company_id']]);
            $indexAftersales = [];
            if (isset($aftersalesList['list']) && !empty($aftersalesList['list'])) {
                $afterBnList = array_column($aftersalesList['list'], 'aftersales_bn');
                $afterBnIndex = array_column($aftersalesList['list'],'order_id','aftersales_bn');
                $aftersalesDetailRepository = app('registry')->getManager('default')->getRepository(AftersalesDetail::class);
                $aftersalesDetailList = $aftersalesDetailRepository->getList(['company_id' => $filter['company_id'], 'aftersales_bn' => $afterBnList, 'aftersales_status' => 2, 'progress' => 4]);
                foreach ($aftersalesDetailList['list'] as $item) {
                    $indexAftersales[$afterBnIndex[$item['aftersales_bn']]] = true;
                }
            }


            foreach ($taskBrokerageList['list'] as &$task) {
                foreach ($orderList as $order) {
                    if ($order['order_id'] == $task['order_id']) {
                        $task['mobile'] = $order['mobile'];
                        $task['order_status'] = $order['order_status'];
                        $task['order_status_msg'] = $orderStatus[$order['order_status']] ?? '';
                        $task['total_fee'] = bcdiv($order['total_fee'], 100, 2);
                        $task['order_auto_close_aftersales_time'] = $order['order_auto_close_aftersales_time'];
                    }
                }
            }
        }

        $nowTime = time();
        foreach($list['list'] as &$row) {
            // 之前是按订单状态来定
            $row['total_fee'] = 0;
            $row['finish_num'] = 0;
            $row['wait_num'] = 0;
            $row['close_num'] = 0;

            $row['orders'] = [];

            foreach ($taskBrokerageList['list'] as $value) {
                if ($value['company_id'] == $row['company_id'] && $value['item_id'] == $row['item_id'] && $value['user_id'] == $row['user_id']) {
                    $orderInfo['order_id'] = $value['order_id'];
                    $orderInfo['mobile'] = $value['mobile'];
                    $orderInfo['total_fee'] = $value['total_fee'];
                    $orderInfo['order_status'] = $value['order_status'];
                    $orderInfo['order_status_msg'] = $value['order_status_msg'];
                    $orderInfo['status'] = $value['status'];
                    $orderInfo['status_msg'] = $statusMsg[$value['status']] ?? '';
                    if ($value['status'] == 'wait') {
                        ++$row['wait_num'];
                    } elseif ($value['status'] == 'close') {
                        ++$row['close_num'];
                    } else {
                        if (isset($indexAftersales[$orderInfo['order_id']])) {
                            $orderInfo['status_msg'] = '已关闭';
                            $orderInfo['status'] = 'close';
                            ++$row['close_num'];
                        } else {
                            // 订单已完成
                            if ($nowTime < $value['order_auto_close_aftersales_time']) {
                                $orderInfo['status_msg'] = '待统计';
                                $orderInfo['status'] = 'wait';
                                ++$row['wait_num'];
                            } else {
                                $row['total_fee'] = bcadd(bcmul($value['total_fee'], 100), $row['total_fee']);
                                ++$row['finish_num'];
                            }
                        }
                    }

                    $row['orders'][] = $orderInfo;
                }
            }
            $row['rebate_conf'] = json_decode($row['rebate_conf'], true);
            $row['status'] = 0;
            $row['promoter_mobile'] = $memberMobiles[$row['user_id']] ?? '';
            // 满足条件
            $filter = $row['rebate_conf']['rebate_task'][0]['filter'] ?? [];
            if ($row['rebate_type'] == 'total_money') {
                if ($filter && bcdiv($row['total_fee'], 100, 2) >= $filter) {
                    $row['status'] = 1;
                } else {
                    $row['limit_desc'] = '还差 '. (intval($filter) - $row['total_fee'] / 100) . ' 元达标';
                }
            }

            if ($row['rebate_type'] == 'total_num') {
                if ($filter && $row['finish_num'] >= $filter) {
                    $row['status'] = 1;
                } else {
                    $row['limit_desc'] = '还差 '. (intval($filter) - $row['finish_num']) . ' 件达标';
                }
            }
            $row['rebate_money'] = $this->getRebateMoney($row);
        }

        return $list;
    }

    /**
     * 获取奖金总额
     */
    public function getRebateMoneyTotal($filter)
    {
        return $this->taskBrokerageCountRepository->getRebateMoneyTotal($filter);
    }

    /**
     * 获取到任务奖金
     *
     * @param array $row taskBrokerageCountRow
     */
    private function getRebateMoney($row)
    {
        if (!$row['rebate_conf']) {
            return 0;
        }

        if (!is_array($row['rebate_conf'])) {
            $row['rebate_conf'] = json_decode($row['rebate_conf'], true);
        }

        // 满足条件
        $currentFilter = 0;
        if ($row['rebate_type'] == 'total_money') {
            $currentFilter = $row['total_fee'] / 100;
        }

        if ($row['rebate_type'] == 'total_num') {
            $currentFilter = $row['finish_num'];
        }

        // 任务返佣计算类型
        $rebateTaskType = $row['rebate_conf']['rebate_task_type'];
        $row['rebate_money'] = 0;
        foreach ($row['rebate_conf']['rebate_task'] as $rebateTask) {
            if ($rebateTask['filter'] && $currentFilter >= $rebateTask['filter']) {
                if ($rebateTaskType == 'money') {
                    // 满足条件返固定金额
                    $row['rebate_money'] = ($rebateTask['money'] > 0) ? $rebateTask['money'] * 100 : 0;
                } else {
                    // 满足条件根据比例返佣
                    $row['rebate_money'] = ($rebateTask['ratio'] > 0) ? ($rebateTask['total_fee'] * $rebateTask['ratio']) : 0;
                }
            }
        }
        return $row['rebate_money'];
    }

    public function __call($method, $parameters)
    {
        return $this->taskBrokerageRepository->$method(...$parameters);
    }
}
