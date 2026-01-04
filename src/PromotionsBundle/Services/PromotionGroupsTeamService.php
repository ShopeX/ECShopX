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

use Dingo\Api\Exception\ResourceException;
use GoodsBundle\Services\ItemsService;
use MembersBundle\Entities\Members;
use MembersBundle\Entities\MembersInfo;
use OrdersBundle\Entities\NormalOrdersItems;
use OrdersBundle\Services\Orders\GroupsNormalOrderService;
use OrdersBundle\Services\Orders\GroupsServiceOrderService;
use OrdersBundle\Services\TradeService;
use OrdersBundle\Traits\GetOrderServiceTrait;
use OrdersBundle\Traits\GetTeamIdTrait;
use PromotionsBundle\Entities\PromotionGroupsTeam;
use PointBundle\Services\PointMemberService;
use SystemLinkBundle\Events\TradeUpdateEvent as OmsTradeUpdateEvent;

class PromotionGroupsTeamService
{
    use GetTeamIdTrait;
    use GetOrderServiceTrait;

    public const STATUS_DISABLED_TRUE = true;

    public const STATUS_DISABLED_FALSE = false;
    // 拼团进行中
    public const STATUS_TEAM_BEGINNING = 1;
    // 拼团成功
    public const STATUS_TEAM_SUCCESS = 2;
    // 拼团失败
    public const STATUS_TEAM_ERROR = 3;
    /**
     * PromotionGroupsActivity Repository类
     */
    public $promotionGroupsTeamRepository = null;

    public function __construct()
    {
        $this->promotionGroupsTeamRepository = app('registry')->getManager('default')->getRepository(PromotionGroupsTeam::class);
    }

    /**
     * 查询商品开团列表
     * @param $groupsId
     * @param $companyId
     * @param int $limit
     * @return mixed
     */
    public function getGroupsTeamByItems($filter, $page, $pageSize, $orderBy = ['p.created' => 'ASC'], $header = true)
    {
        $result = $this->promotionGroupsTeamRepository->getOrderList($filter, $orderBy, $pageSize, $page, $header);
        foreach ($result['list'] as &$v) {
            $v['member_info'] = json_decode($v['member_info']);
            $v['over_time'] = $v['end_time'] > time() ? $v['end_time'] - time() : 0;
        }
        return $result['list'];
    }

    /**
     * 获取单个拼团活动信息
     * @param array $filter 条件
     * @return mixed
     */
    public function getInfo(array $filter)
    {
        return $this->promotionGroupsTeamRepository->getInfo($filter);
    }

    /**
     * 获取拼团列表
     * @param $filter
     * @param $page
     * @param $pageSize
     * @param array $orderBy
     * @return mixed
     */
    public function getList($filter, $page, $pageSize, $orderBy = ['created' => 'DESC'])
    {
        $result = $this->promotionGroupsTeamRepository->lists($filter, $orderBy, $pageSize, $page);
        if (empty($result['list'])) {
            return $result;
        }
        $uid = [];
        foreach ($result['list'] as $v) {
            $uid[] = $v['head_mid'];
        }
        $memberNewList = [];
        if ($uid) {
            $membersRepository = app('registry')->getManager('default')->getRepository(Members::class);
            $membersInfoRepository = app('registry')->getManager('default')->getRepository(MembersInfo::class);

            $companyId = $filter['company_id'];
            $indexMobile = $membersRepository->getMobileByUserIds($companyId, $uid);
            $memberList = $membersInfoRepository->getListByUserIds($companyId, $uid);

            if (!empty($memberList)) {
                $memberNewList = array_bind_key($memberList, 'user_id');
            }
        }


        foreach ($result['list'] as &$v) {
            $v['username'] = isset($memberNewList[$v['head_mid']]) ? $memberNewList[$v['head_mid']]['username'] : '';

            if ($v['username'] === '') {
                $v['username'] = $indexMobile[$v['head_mid']] ?? '';
            }
        }
        return $result;
    }

    /**
     * 查询用户拼团列表
     * @param $filter
     * @param $page
     * @param $pageSize
     * @param array $orderBy
     * @return mixed
     */
    public function getGroupsTeamListByUser($filter, $page, $pageSize, $orderBy = ['created' => 'DESC'])
    {
        $page = ($page < 1) ? 1 : $page;
        $pageSize = ($pageSize > 100) ? 100 : $pageSize;
        $pageSize = ($pageSize <= 0) ? 10 : $pageSize;
        $promotionGroupsTeamList = $this->promotionGroupsTeamRepository->getOrderList($filter, ['p.created' => 'DESC'], $pageSize, $page);
        if (empty($promotionGroupsTeamList['list'])) {
            return $promotionGroupsTeamList;
        }
        $activityId = [];
        $teamId = [];
        foreach ($promotionGroupsTeamList['list'] as $v) {
            $activityId[] = $v['act_id'];
            $teamId[] = $v['team_id'];
        }
        $promotionGroupsTeamMemberService = new PromotionGroupsTeamMemberService();
        $promotionGroupsTeamMemberList = $promotionGroupsTeamMemberService->promotionGroupsTeamMemberRepository->lists(['team_id' => $teamId, 'disabled' => false], ['join_time' => 'DESC'], $pageSize * 10, 1);
        $promotionGroupsTeamMemberNewList = [];
        foreach ($promotionGroupsTeamMemberList['list'] as $v) {
            $promotionGroupsTeamMemberNewList[$v['team_id']][] = $v;
        }
        $promotionGroupsActivityService = new PromotionGroupsActivityService();
        $promotionGroupsActivityList = $promotionGroupsActivityService->promotionGroupsActivityRepository->lists(['groups_activity_id' => $activityId], ['created' => 'DESC'], $pageSize, 1);
        $promotionGroupsActivityNewList = [];
        $itemsId = [];
        foreach ($promotionGroupsActivityList['list'] as $v) {
            $itemsId[] = $v['goods_id'];
            $promotionGroupsActivityNewList[$v['groups_activity_id']] = $v;
        }
        $itemsService = new ItemsService();
        $itemsList = $itemsService->getItemsList(['item_id' => $itemsId], 1, $pageSize);
        $itemsNewList = [];
        if ($itemsList['list']) {
            $itemsNewList = array_bind_key($itemsList['list'], 'itemId');
        }
        foreach ($promotionGroupsTeamList['list'] as &$v) {
            $itemsInfo = $itemsNewList[$promotionGroupsActivityNewList[$v['act_id']]['goods_id']] ?? [];

            $v['member_list'] = isset($promotionGroupsTeamMemberNewList[$v['team_id']]) ? $promotionGroupsTeamMemberNewList[$v['team_id']] : null;
            $v['member_info'] = json_decode($v['member_info']);
            $v['person_num'] = $promotionGroupsActivityNewList[$v['act_id']]['person_num'];
            $v['itemId'] = $promotionGroupsActivityNewList[$v['act_id']]['goods_id'];
            $v['itemName'] = $itemsInfo['itemName'] ?? $promotionGroupsActivityNewList[$v['act_id']]['act_name'];
            $v['price'] = $promotionGroupsActivityNewList[$v['act_id']]['act_price'];
            $v['pics'] = $itemsInfo['pics'] ?? $promotionGroupsActivityNewList[$v['act_id']]['pics'];
        }
        return $promotionGroupsTeamList;
    }

    /**
     * 获取用户的拼团详情
     * @param $filter
     * @return array
     */
    public function getGroupsTeamDetailByUser($filter)
    {
        $info['team_info'] = $this->promotionGroupsTeamRepository->getInfo($filter);
        if (!isset($info['team_info']) || !$info['team_info']) {
            return [];
        }
        $promotionGroupsTeamMemberService = new PromotionGroupsTeamMemberService();
        $promotionGroupsTeamMemberList = $promotionGroupsTeamMemberService->promotionGroupsTeamMemberRepository->lists(['team_id' => $info['team_info']['team_id'], 'disabled' => false], ['join_time' => 'DESC'], 99, 1);
        if (!isset($promotionGroupsTeamMemberList['list'])) {
            return [];
        }
        // 获取订单号
        $orderId = null;
        foreach ($promotionGroupsTeamMemberList['list'] as $v) {
            if ('' != $v['order_id']) {
                $orderId = $v['order_id'];
                break;
            }
        }
        $promotionGroupsActivityService = new PromotionGroupsActivityService();
        $promotionGroupsActivityInfo = $promotionGroupsActivityService->promotionGroupsActivityRepository->getInfo(['groups_activity_id' => $info['team_info']['act_id']]);
        $ItemsService = new ItemsService();
        $itemInfo = $ItemsService->getItemsSkuDetail($promotionGroupsActivityInfo['goods_id']);
        $orderService = new GroupsServiceOrderService();
        $orderInfo = $orderService->serviceOrderRepository->get($promotionGroupsActivityInfo['company_id'], $orderId);
        $info['activity_info']['act_price'] = $promotionGroupsActivityInfo['act_price'];
        $info['activity_info']['save_price'] = ($itemInfo['price'] - $promotionGroupsActivityInfo['act_price']) * $promotionGroupsActivityInfo['person_num'];
        $info['activity_info'] = $promotionGroupsActivityInfo;
        $info['activity_info']['over_time'] = $info['team_info']['end_time'] > time() ? $info['team_info']['end_time'] - time() : 0;
        $info['member_list'] = $promotionGroupsTeamMemberList;
        $info['activity_info']['itemId'] = $itemInfo['item_id'];
        $info['activity_info']['itemName'] = $itemInfo['item_name'];
        $info['activity_info']['price'] = $itemInfo['price'];
        $info['activity_info']['pics'] = $itemInfo['pics'];
        $info['activity_info']['shop_id'] = $orderInfo ? $orderInfo->getShopId() : null;

        // 拼团状态
        $info["team_info"]["status"] = $this->getStatus($info["team_info"]);
        // 拼团状态
        $info["team_info"]["progress"] = $this->getProgress($info["team_info"]);
        // 活动状态
        $info["activity_info"]["status"] = $promotionGroupsActivityService->getStatus($info["activity_info"]);
        return $info;
    }

    /**
     * 创建开团
     * @param $params
     * @return mixed
     */
    public function createGroupsTeam($params)
    {
        $rules = [
            'company_id' => ['required', trans('PromotionsBundle.company_id_required')],
            'act_id' => ['required', trans('PromotionsBundle.group_activity_id_required')],
            'head_mid' => ['required', trans('PromotionsBundle.user_id_required')],
            'begin_time' => ['required', trans('PromotionsBundle.group_begin_time_required')],
            'end_time' => ['required', trans('PromotionsBundle.group_end_time_required')],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $data = [
            'team_id' => $this->genId($params['head_mid']),
            'company_id' => $params['company_id'],
            'act_id' => $params['act_id'],
            'head_mid' => $params['head_mid'],
            'begin_time' => $params['begin_time'],
            'end_time' => $params['end_time'],
            'group_goods_type' => isset($params['group_goods_type']) ? $params['group_goods_type'] : 'services',
            'join_person_num' => 0,
            'team_status' => 1,
            'disabled' => self::STATUS_DISABLED_TRUE
        ];
        $result = $this->promotionGroupsTeamRepository->create($data);
        return $result;
    }

    /**
     * 自动取消拼团并退款
     */
    public function scheduleAutoCancelGroupOrders()
    {
        $pageSize = 100;
        // 取消订单，每分钟执行一次，当前只处理一分钟内的订单
        $filter = [
            'team_status' => 1,
            'end_time|lte' => time()
        ];
        $result = $this->promotionGroupsTeamRepository->lists($filter, ["created" => "DESC"], 1, 1);
        $totalPage = 0;
        if (isset($result['total_count']) && $result['total_count']) {
            $totalPage = ceil($result['total_count'] / $pageSize);
        } else {
            return true;
        }

        for ($i = 1; $i <= $totalPage; $i++) {
            $list = $this->promotionGroupsTeamRepository->lists($filter, ["created" => "DESC"], $pageSize, 1);
            foreach ($list['list'] as $v) {
                $this->teamFail($v['team_id']);
            }
        }
        // foreach ($result as $orderData) {
        //     //退还积分
        //     (new PointMemberService())->cancelOrderReturnBackPoints($orderData);
        // }
    }

    /**
     * 自动完成拼团
     */
    public function scheduleAutoDoneGroup()
    {
        // 取消订单，每分钟执行一次，当前只处理一分钟内的订单
        $pageSize = 100;
        // 随机提前1000~1800自动成团
        $second = 600;
        $time = time();
        $filter = [
            'p.team_status' => 1,
            'p.disabled' => false,
            'a.robot' => true,
            'p.end_time|lte' => ($time + $second),
        ];
        $result = $this->promotionGroupsTeamRepository->getTeamGroupList($filter, ["p.created" => "DESC"], 0, 1);
        $totalPage = 0;
        if (isset($result['total_count']) && $result['total_count']) {
            $totalPage = ceil($result['total_count'] / $pageSize);
        } else {
            return true;
        }

        for ($i = 1; $i <= $totalPage; $i++) {
            $list = $this->promotionGroupsTeamRepository->getTeamGroupList($filter, ["created" => "DESC"], $pageSize, 1);
            $groupsServiceOrderService = new GroupsServiceOrderService();
            $groupsNormalOrderService = new GroupsNormalOrderService();
            // 判断是否存在部分在拼团结束后才支付的订单，并将这些队伍设置为拼团失败
            $teamFailArray = $this->forceTeamFailIfPaymentTimeOverEndTime((array)array_column($list['list'], "team_id"));
            foreach ($list['list'] as $v) {
                // 如果该拼团已经在上文被处理成拼团失败则不往下处理
                if (isset($teamFailArray[$v['team_id']])) {
                    continue;
                }
                if (1 == $v['team_status'] && $this->groupRobot($v)) {
                    $promotionGroupsTeamMemberService = new PromotionGroupsTeamMemberService();
                    $result = $promotionGroupsTeamMemberService->getGroupTeamSuccess($v['team_id']);
                    foreach ($result['list'] as $v1) {
                        app('log')->debug($v['group_goods_type'] . '订单' . $v1['order_id'] . '拼团成功');
                        $filterList = ['order_id' => $v1['order_id']];
                        $updateStatus['order_status'] = 'PAYED';
                        if ($v1['member_id'] > 0) {
                            if ($v['group_goods_type'] == 'services') {
                                $groupsServiceOrderService->serviceOrderRepository->update($filterList, $updateStatus);
                                $groupsServiceOrderService->orderAssociationsRepository->update($filterList, $updateStatus);
                                $groupsServiceOrderService->addNewRights($v1['company_id'], $v1['member_id'], $v1['order_id']);
                            } else {
                                $groupsNormalOrderService->normalOrdersRepository->update($filterList, $updateStatus);
                                $groupsNormalOrderService->orderAssociationsRepository->update($filterList, $updateStatus);

                                //触发订单oms更新的事件
                                $eventData = [
                                    'company_id' => $v1['company_id'],
                                    'order_id' => $v1['order_id'],
                                    'order_class' => 'normal_groups',//todo : 这里最好是取 trades 表的 trade_source_type 字段
                                    'user_id' => $v1['member_id'],
                                ];
                                event(new OmsTradeUpdateEvent($eventData));
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * 自动机器人
     * @param $info
     */
//    public function groupRobot($info)
//    {
//        app('log')->debug($info['team_id'] . '团，成团机器人开始');
//        $promotionGroupsTeamMemberService = new PromotionGroupsTeamMemberService();
//        $teamMemberData = [
//            'team_id' => $info['team_id'],
//            'company_id' => $info['company_id'],
//            'act_id' => $info['act_id'],
//            'member_id' => 0,
//            'order_id' => ''
//        ];
//
//        $groupItemStoreService = new GroupItemStoreService();
//        $flag = $groupItemStoreService->minusGroupItemStore($info['act_id'], $info['join_person_num']);
//        // 库存扣减失败
//        if (!$flag) {
//            // 拼团失败
//            $this->teamFail($info['team_id']);
//            return false;
//        }
//
//        $num = $info['person_num'] - $info['join_person_num'] + 1;
//
//        app('log')->debug($info['team_id'] . '团，成团机器人' . $num);
//        $promotionGroupsTeamMemberService->createRobotGroupsTeamMember($teamMemberData, $num);
//        $this->promotionGroupsTeamRepository->setNum(['id' => $info['id']], $info['person_num']);
//        return true;
//    }
    /**
     * 自动机器人
     * @param $info
     */
    public function groupRobot($info)
    {
        $promotionGroupsTeamMemberService = new PromotionGroupsTeamMemberService();
        $teamMemberData = [
            'team_id' => $info['team_id'],
            'company_id' => $info['company_id'],
            'act_id' => $info['act_id'],
            'member_id' => 0,
            'order_id' => ''
        ];
        $num = $info['person_num'] - $info['join_person_num'] + 1;
        app('log')->debug($info['team_id'] . '团，成团机器人' . $num);
        $promotionGroupsTeamMemberService->createRobotGroupsTeamMember($teamMemberData, $num);
        $this->promotionGroupsTeamRepository->setNum(['id' => $info['id']], $info['person_num']);
        return true;
    }

    /**
     * 拼团团失败
     */
    public function teamFail($teamId)
    {
        $this->promotionGroupsTeamRepository->updateOneBy(['team_id' => $teamId], ['team_status' => 3]);
        $promotionGroups = $this->promotionGroupsTeamRepository->getInfo(['team_id' => $teamId]);
        $tradeService = new TradeService();
        //获取
        $promotionGroupsTeamMemberService = new PromotionGroupsTeamMemberService();
        $list = $promotionGroupsTeamMemberList = $promotionGroupsTeamMemberService->promotionGroupsTeamMemberRepository->lists(['team_id' => $teamId, 'disabled' => false, 'member_id|neq' => 0]);
        if ($list['total_count'] > 0) {
            foreach ($list['list'] as $row) {
                if ($row['group_goods_type'] == 'services') {
                    $orderType = 'service_groups';
                } else {
                    $orderType = 'normal_groups';
                }
                try {
                    $tradeService->refundStatus($row['order_id'], $row['company_id'], $orderType);
                    $groupItemStoreService = new GroupItemStoreService();
                    $normalOrdersItemsRepository = app('registry')->getManager('default')->getRepository(NormalOrdersItems::class);
                    $normalOrdersItem = $normalOrdersItemsRepository->getRow(['order_id' => $row['order_id']]);
                    $groupItemStoreService->minusGroupItemStore($promotionGroups['act_id'], -$normalOrdersItem['num']);
                    $groupsNormalOrderService = new GroupsNormalOrderService();
                    $filter = ['company_id' => $row['company_id'], 'order_id' => $row['order_id']];
                    $groupsNormalOrderService->addItemStore($filter);
                } catch (\Exception $e) {
                    app('log')->debug('拼团失败，退款失败参数:' . var_export($row, true));
                    app('log')->debug('拼团失败，退款失败:' . $e->getMessage());
                    // 退款失败
                }
            }
            return true;
        }
    }

    /**
     * 库存为0时自动完成拼团
     */
    public function scheduleNoStoreAutoDoneGroup()
    {
        $pageSize = 100;
        $filter = [
            'p.team_status' => 1,
            'p.disabled' => false,
            'a.robot' => true,
            'a.store' => 0,
        ];
        $result = $this->promotionGroupsTeamRepository->getTeamGroupList($filter, ["p.created" => "DESC"], 0, 1);
        $totalPage = 0;
        if (isset($result['total_count']) && $result['total_count']) {
            $totalPage = ceil($result['total_count'] / $pageSize);
        } else {
            return true;
        }

        for ($i = 1; $i <= $totalPage; $i++) {
            $list = $this->promotionGroupsTeamRepository->getTeamGroupList($filter, ["created" => "DESC"], $pageSize, 1);
            $groupsServiceOrderService = new GroupsServiceOrderService();
            $groupsNormalOrderService = new GroupsNormalOrderService();
            foreach ($list['list'] as $v) {
                if (1 == $v['team_status'] && $this->groupRobot($v)) {
                    $promotionGroupsTeamMemberService = new PromotionGroupsTeamMemberService();
                    $result = $promotionGroupsTeamMemberService->getGroupTeamSuccess($v['team_id']);
                    foreach ($result['list'] as $v1) {
                        app('log')->debug($v['group_goods_type'] . '订单' . $v1['order_id'] . '拼团成功');
                        $filterList = ['order_id' => $v1['order_id']];
                        $updateStatus['order_status'] = 'PAYED';
                        if ($v1['member_id'] > 0) {
                            if ($v['group_goods_type'] == 'services') {
                                $groupsServiceOrderService->serviceOrderRepository->update($filterList, $updateStatus);
                                $groupsServiceOrderService->orderAssociationsRepository->update($filterList, $updateStatus);
                                $groupsServiceOrderService->addNewRights($v1['company_id'], $v1['member_id'], $v1['order_id']);
                            } else {
                                $groupsNormalOrderService->normalOrdersRepository->update($filterList, $updateStatus);
                                $groupsNormalOrderService->orderAssociationsRepository->update($filterList, $updateStatus);

                                //触发订单oms更新的事件
                                $eventData = [
                                    'company_id' => $v1['company_id'],
                                    'order_id' => $v1['order_id'],
                                    'order_class' => 'normal_groups',//todo : 这里最好是取 trades 表的 trade_source_type 字段
                                    'user_id' => $v1['member_id'],
                                ];
                                event(new OmsTradeUpdateEvent($eventData));
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * 强制拼团失败
     * 场景: 用户在拼团结束前调起支付界面，并在拼团结束后才选择支付
     * 带来的问题：此时不应该属于拼团成功，而应该是拼团失败
     */
    public function forceTeamFailIfPaymentTimeOverEndTime(array $teamIds)
    {
        // 拼团失败的team
        $teamFailArray = [];

        // 每页的数量
        $pageSize = 100;

        // 空内容直接返回
        if (empty($teamIds)) {
            return $teamFailArray;
        }
        // 去重，然后分块
        $teamIdChunk = array_chunk(array_unique($teamIds), $pageSize);

        // 分块查询
        $teamMemberService = new PromotionGroupsTeamMemberService();
        foreach ($teamIdChunk as $teamIdArray) {
            // 获取支付时间超出了拼团时间的订单
            $teamFailList = $teamMemberService->getPaymentOverEndTimeList($teamIdArray);
            // 根据拼团的队伍id来分组记录
            foreach ($teamFailList as $item) {
                $teamId = $item["team_id"] ?? "";
                $orderId = $item["order_id"] ?? "";
                $teamFailArray[$teamId][] = $orderId;
            }
        }

        // 分批 强制处理拼团失败
        $teamFailArrayChunk = array_chunk($teamFailArray, $pageSize, true);
        foreach ($teamFailArrayChunk as $teamFailArrayValue) {
            $this->teamFail(array_keys($teamFailArrayValue));
        }
        return $teamFailArray;
    }

    /**
     * 获取拼团状态
     * @param array $promotionGroupTeamInfo
     * @return int|null
     */
    public function getStatus(array $promotionGroupTeamInfo): ?int
    {
        // 不存在开始时间和结束时间的话就返回null
        if (empty($promotionGroupTeamInfo["begin_time"]) && empty($promotionGroupTeamInfo["end_time"])) {
            return null;
        }
        $now = time();
        if ($promotionGroupTeamInfo["begin_time"] > $now) {
            return self::STATUS_COMING_SOON; // 未开始
        } elseif ($promotionGroupTeamInfo["end_time"] < $now) {
            return self::STATUS_ENDED; // 已结束
        } else {
            return self::STATUS_ONGOING; // 正在进行中
        }
    }

    /**
     * 获取拼团进度
     * @param array $promotionGroupTeamInfo
     * @return int|null
     */
    public function getProgress(array $promotionGroupTeamInfo): ?int
    {
        if (!isset($promotionGroupTeamInfo["status"])) {
            $status = $this->getStatus($promotionGroupTeamInfo);
        } else {
            $status = (int)$promotionGroupTeamInfo["status"];
        }
        switch ($status) {
            case self::STATUS_COMING_SOON:
                return self::PROGRESS_COMING_SOON;
                break;
            case self::STATUS_ONGOING:
                return self::PROGRESS_ONGOING;
                break;
            case self::STATUS_ENDED:
                $teamId = $promotionGroupTeamInfo["team_id"];
                if ((new PromotionGroupsTeamMemberService())->getPaymentOverEndTimeList([$teamId])) {
                    return self::PROGRESS_FAIL;
                } else {
                    return self::PROGRESS_SUCCESS;
                }
                break;
            default:
                return null;
        }
    }

    /**
     * 拼团状态 - 未开始
     */
    public const STATUS_COMING_SOON = PromotionGroupsActivityService::STATUS_COMING_SOON;

    /**
     * 拼团状态 - 正在进行
     */
    public const STATUS_ONGOING = PromotionGroupsActivityService::STATUS_ONGOING;

    /**
     * 拼团状态 - 已结束
     */
    public const STATUS_ENDED = PromotionGroupsActivityService::STATUS_ENDED;

    /**
     * 拼团进度 - 未开始
     */
    public const PROGRESS_COMING_SOON = 1;

    /**
     * 拼团进度 - 正在拼团
     */
    public const PROGRESS_ONGOING = 2;

    /**
     * 拼团进度 - 成功
     */
    public const PROGRESS_SUCCESS = 3;

    /**
     * 拼团进度 - 失败
     */
    public const PROGRESS_FAIL = 4;
}
