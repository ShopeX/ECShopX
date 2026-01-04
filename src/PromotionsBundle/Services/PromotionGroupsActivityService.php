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

use GoodsBundle\Services\ItemsService;
use PromotionsBundle\Entities\PromotionGroupsActivity;
use Dingo\Api\Exception\ResourceException;
use PromotionsBundle\Entities\PromotionGroupsTeam;
use PromotionsBundle\Traits\CheckPromotionsValid;
use PromotionsBundle\Traits\CheckPromotionsRules;
use OrdersBundle\Services\OrderAssociationService;
use PromotionsBundle\Jobs\SavePromotionItemTag;
use SalespersonBundle\Jobs\SalespersonItemsShelvesJob;
use DistributionBundle\Services\DistributorService;
use DistributionBundle\Services\DistributorItemsService;

class PromotionGroupsActivityService
{
    use CheckPromotionsValid;
    use CheckPromotionsRules;
    public const STATUS_DISABLED_TRUE = true;
    public const STATUS_DISABLED_FALSE = false;
    /**
     * PromotionGroupsActivity Repository类
     */
    public $promotionGroupsActivityRepository = null;

    public function __construct()
    {
        $this->promotionGroupsActivityRepository = app('registry')->getManager('default')->getRepository(PromotionGroupsActivity::class);
    }

    /**
     * 获取拼团活动列表
     * @param array $filter 条件
     * @param int $page 页码
     * @param int $pageSize 数据条数
     * @param array $orderBy 排序方式
     * @return mixed
     */
    public function getList(array $filter, $page = 1, $pageSize = 100, $orderBy = ['created' => 'DESC'])
    {
        // Powered by ShopEx EcShopX
        $filter['disabled'] = self::STATUS_DISABLED_FALSE;
        $result = $this->promotionGroupsActivityRepository->lists($filter, $orderBy, $pageSize, $page);
        if (empty($result['list'])) {
            return $result;
        }
        $itemId = [];
        foreach ($result['list'] as $v) {
            $itemId[] = $v['goods_id'];
        }
        $itemsService = new ItemsService();
        $itemList = $itemsService->getItemsList(['item_id' => $itemId], 1, $pageSize);
        $itemNewList = [];
        if ($itemList['list']) {
            $itemNewList = array_bind_key($itemList['list'], 'itemId');
        }
        foreach ($result['list'] as &$v) {
            $v['goods_name'] = isset($itemNewList[$v['goods_id']]['itemName']) ? $itemNewList[$v['goods_id']]['itemName'] : trans('PromotionsBundle.invalid_product');
            if ($v['begin_time'] > time()) {
                $v['activity_status'] = 1;
            } elseif ($v['end_time'] < time()) {
                $v['activity_status'] = 3;
            } else {
                $v['activity_status'] = 2;
            }
        }
        return $result;
    }

    /**
     * 获取单个拼团活动信息
     * @param array $filter 条件
     * @return mixed
     */
    public function getInfo(array $filter)
    {
        $filter['disabled'] = self::STATUS_DISABLED_FALSE;
        return $this->promotionGroupsActivityRepository->getInfo($filter);
    }

    /**
     * 获取单个拼团活动信息
     * @param $itemsId
     * @param $begin_time
     * @param $end_time
     * @return mixed
     */
    public function getGroupBeginInfoByItemsId($itemsId, $begin_time, $end_time)
    {
        // ID: 53686f704578
        $lists = $this->promotionGroupsActivityRepository->getIsHave($itemsId, $begin_time, $end_time);
        return isset($lists[0]) ? $lists[0] : [];
    }

    /**
     * 创建活动
     * @param $params 数据
     * @return mixed
     */
    public function createActivity(array $params)
    {
        $data = $this->checkGroupParam($params, '');
        $data['disabled'] = self::STATUS_DISABLED_FALSE;
        $result = $this->promotionGroupsActivityRepository->create($data);
        if ($result['groups_activity_id'] ?? 0) {
            $itemIds[] = $params['goods_id'];
            $activityPrice[$params['goods_id']] = $data['act_price'];
            $gotoJob = (new SavePromotionItemTag($result['company_id'], $result['groups_activity_id'], 'single_group', $data['begin_time'], $data['end_time'], $itemIds, $activityPrice))->onQueue('slow');
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        }
        $groupItemStoreService = new GroupItemStoreService();
        $groupItemStoreService->saveGroupItemStore($result['groups_activity_id'], $result['store']);

        $job = (new SalespersonItemsShelvesJob($result['company_id'], $result['groups_activity_id'], 'group'));
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);
        return $result;
    }

    public function checkCreateGroupOrder($params)
    {
        $groupFilter = [
            'groups_activity_id' => $params['bargain_id'],
            'company_id' => $params['company_id'],
            'disabled' => false
        ];
        $groupInfo = $this->promotionGroupsActivityRepository->getInfo($groupFilter);
        if (!$groupInfo) {
            throw new ResourceException(trans("PromotionsBundle.group_activity_not_exist"));
        }

        if ($groupInfo['store'] == 0) {
            throw new ResourceException(trans("PromotionsBundle.group_product_sold_out"));
        }

        // 拼团活动是否过期
        if ($groupInfo['end_time'] < time()) {
            throw new ResourceException(trans("PromotionsBundle.group_activity_expired"));
        }

        $promotionGroupsTeamMemberService = new PromotionGroupsTeamMemberService();
        // 拼团商品是否限制购买
        if ($groupInfo['limit_buy_num'] != 0) {
            $checkResult = $this->getGroupsActivityLimit($params['company_id'], $params['bargain_id'], $params['user_id']);
            if ($checkResult) {
                throw new ResourceException(trans("PromotionsBundle.group_limit_reached"));
            }
        }

        $groupItemStoreService = new GroupItemStoreService();
        // 商品库存判断
        if ($groupItemStoreService->getGroupItemStore($params['bargain_id']) <= 0) {
            throw new ResourceException(trans("PromotionsBundle.product_stock_insufficient"));
        }

        // 如果是参团
        if (isset($params['team_id']) && !empty($params['team_id'])) {
            $promotionGroupsTeamService = new PromotionGroupsTeamService();

            $groupsTeamFilter = [
                'team_id' => $params['team_id'],
                'company_id' => $params['company_id'],
                'disabled' => false
            ];
            $promotionGroupsTeamInfo = $promotionGroupsTeamService->promotionGroupsTeamRepository->getInfo($groupsTeamFilter);
            // 参团判断拼团活动是否存在
            if (!$promotionGroupsTeamInfo) {
                throw new ResourceException(trans("PromotionsBundle.group_activity_ended_join_early"));
            }

            if ($promotionGroupsTeamInfo['team_status'] == '2') {
                throw new ResourceException(trans("PromotionsBundle.group_already_success_cannot_join"));
            }

            if ($promotionGroupsTeamInfo['team_status'] == '3') {
                throw new ResourceException(trans("PromotionsBundle.group_activity_ended_join_early"));
            }

            $groupsTeamMemberFilter = [
                'team_id' => $params['team_id'],
                'member_id' => $params['user_id'],
                'company_id' => $params['company_id'],
                'act_id' => $params['bargain_id'],
            ];
            $promotionGroupsTeamMemberInfo = $promotionGroupsTeamMemberService->promotionGroupsTeamMemberRepository->getInfo($groupsTeamMemberFilter);
            if ($promotionGroupsTeamMemberInfo) {
                if (!$promotionGroupsTeamMemberInfo['disabled']) {
                    throw new ResourceException(trans("PromotionsBundle.already_joined_check_my_group"));
                } else {
                    $orderAssociationService = new OrderAssociationService();
                    $order = $orderAssociationService->getOrder($params['company_id'], $promotionGroupsTeamMemberInfo['order_id']);
                    if ($order['order_status'] == 'NOTPAY') {
                        throw new ResourceException(trans("PromotionsBundle.already_joined_pay_unpaid_order"));
                    }
                }
            }
        }

        //同一个用户在同一个商品下只能有一个待成团订单
        $conn = app('registry')->getConnection('default');
        $waitGroupsOrder = $conn->fetchAll("SELECT * FROM orders_normal_orders WHERE user_id = {$params['user_id']} AND order_status = 'WAIT_GROUPS_SUCCESS' AND order_class = 'groups' AND act_id = {$params['bargain_id']}");
        if (!empty($waitGroups)) {

            $orderIdList = array_column($waitGroupsOrder, 'order_id');
            $orderIdList = array_unique($orderIdList);

            if (!empty($orderIdList)) {
                $connBuilder = $conn->createQueryBuilder();
                $dbOrderIdListWhere = [];
                foreach ($orderIdList as $value) {
                    $dbOrderIdListWhere[] = $connBuilder->expr()->literal($value);
                }

                $promotionGroupsList = $connBuilder->select('pgt.team_id,pgt.act_id,pgt.join_person_num,pgtm.order_id')
                    ->from('promotion_groups_team', 'pgt')
                    ->join('pgt', 'promotion_groups_team_member', 'pgtm', 'pgt.team_id = pgtm.team_id')
                    ->andWhere($connBuilder->expr()->in('pgtm.order_id', $dbOrderIdListWhere))
                    ->andWhere($connBuilder->expr()->eq('pgt.act_id', $connBuilder->expr()->literal($params['bargain_id'])))
                    ->andWhere($connBuilder->expr()->eq('pgtm.member_id', $connBuilder->expr()->literal($params['user_id'])))
                    ->andWhere($connBuilder->expr()->eq('pgtm.company_id', $connBuilder->expr()->literal($params['company_id'])))
                    ->execute()->fetchAll();

                if (empty($promotionGroupsList)) {
                    throw new ResourceException(trans("PromotionsBundle.group_activity_has_waiting_order"));
                }

                foreach ($promotionGroupsList as $value) {
                    if ($value['join_person_num'] <= 0) {
                        throw new ResourceException(trans("PromotionsBundle.group_activity_has_waiting_order"));
                    }
                }
            }
        }

        return $groupInfo;
    }

    /**
     * 过滤拼团活动数据
     *
     * @param int $companyId
     * @param int $userId
     * @param array $orderDiscountInfo
     * @return array
     */
    public function filterGroupActivity(int $companyId,int $userId, array $orderDiscountInfo)
    {
        if (empty($orderDiscountInfo)) {
            return [];
        }

        foreach ($orderDiscountInfo as $key => $value) {
            if ($value['type'] == 'groups') {
                // 是否已达到限制
                $orderDiscountInfo[$key]['has_been_restricted'] = $this->getGroupsActivityLimit($companyId, $value['id'], $userId);
            }
        }

        return $orderDiscountInfo;
    }

    /**
     * 得到用户是否已达到拼团活动限制
     *
     * @param $companyId
     * @param $actId
     * @param $userId
     * @return bool
     */
    public function getGroupsActivityLimit($companyId, $actId, $userId): bool
    {
        $groupFilter = [
            'groups_activity_id' => $actId,
            'company_id'         => $companyId,
            'disabled'           => false
        ];
        $groupInfo = $this->promotionGroupsActivityRepository->getInfo($groupFilter);

        if (empty($groupInfo)) {
            return true;
        }

        if (0 == $groupInfo['limit_buy_num']) {
            return false;
        }

        $conn = app('registry')->getConnection('default');

        $orderStatusList = [
            'DONE',
            'WAIT_GROUPS_SUCCESS',
            'PAYED',
            'WAIT_BUYER_CONFIRM',
        ];

        $waitGroupsCount = $conn->fetchAll("SELECT count(*) as order_num FROM orders_normal_orders WHERE user_id = {$userId} AND order_class = 'groups' AND act_id = {$actId} AND order_status in ('" . join("','", $orderStatusList) . "')");
        $hasCount = current($waitGroupsCount)['order_num'] ?? 0;

        $failNum = $this->getGroupsFailNum($companyId, $actId, $userId);
        $hasCount = $hasCount - $failNum;

        if ($hasCount >= $groupInfo['limit_buy_num']) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取拼团失败订单数量
     *
     * @param $companyId
     * @param $actId
     * @param $userId
     * @return mixed
     */
    public function getGroupsFailNum($companyId, $actId, $userId)
    {
        // 获取对应活动已失败的订单号
        $conn = app('registry')->getConnection('default');
        $connBuilder = $conn->createQueryBuilder();

        $orderList = $connBuilder->select('distinct pgtm.order_id')
            ->from('promotion_groups_team', 'pgt')
            ->join('pgt', 'promotion_groups_team_member', 'pgtm', 'pgt.team_id = pgtm.team_id')
            ->andWhere($connBuilder->expr()->eq('pgt.act_id', $connBuilder->expr()->literal($actId)))
            ->andWhere($connBuilder->expr()->eq('pgt.team_status', $connBuilder->expr()->literal(PromotionGroupsTeamService::STATUS_TEAM_ERROR)))
            ->andWhere($connBuilder->expr()->eq('pgtm.member_id', $connBuilder->expr()->literal($userId)))
            ->andWhere($connBuilder->expr()->eq('pgtm.company_id', $connBuilder->expr()->literal($companyId)))
            ->execute()->fetchAll();

        $orderList = array_column($orderList, 'order_id');

        $failOrderCount = $conn->fetchColumn("SELECT count(*) as order_num FROM orders_normal_orders WHERE user_id = {$userId} AND order_class = 'groups' AND act_id = {$actId} AND order_id in ('" . join("','", $orderList) . "')");

        return max($failOrderCount, 0);
    }

    /**
     * 编辑拼团活动
     * @param $groupId 拼团活动id
     * @param $params 数据
     * @return mixed
     */
    public function updateActivity($groupId, array $params)
    {
        $groupinfo = $this->promotionGroupsActivityRepository->getInfo(['groups_activity_id' => $groupId, 'company_id' => $params['company_id']]);
        if (time() >= $groupinfo['begin_time']) {
            throw new ResourceException(trans('PromotionsBundle.activity_started_cannot_edit'));
        }
        $data = $this->checkGroupParam($params, $groupId);
        $result = $this->promotionGroupsActivityRepository->updateOneBy(['groups_activity_id' => $groupId, 'company_id' => $params['company_id']], $data);
        if ($result['groups_activity_id'] ?? 0) {
            $itemIds[] = $params['goods_id'];
            $activityPrice[$params['goods_id']] = $data['act_price'];
            $gotoJob = (new SavePromotionItemTag($result['company_id'], $result['groups_activity_id'], 'single_group', $data['begin_time'], $data['end_time'], $itemIds, $activityPrice))->onQueue('slow');
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        }
        $groupItemStoreService = new GroupItemStoreService();
        $groupItemStoreService->saveGroupItemStore($result['groups_activity_id'], $result['store']);

        $job = (new SalespersonItemsShelvesJob($result['company_id'], $result['groups_activity_id'], 'group'));
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);
        return $result;
    }

    /**
     * 终止拼团活动
     * @param $params 条件
     * @return mixed
     */
    public function finishActivity(array $filter)
    {
        $data['end_time'] = time() - 1; //前端的终止操作是根据结束时间判断的
        $result = $this->promotionGroupsActivityRepository->updateOneBy($filter, $data);
        if ($result) {
            $promotionItemTagService = new PromotionItemTagService();
            $promotionItemTagService->deleteBy(['promotion_id' => $filter['groups_activity_id'], 'company_id' => $filter['company_id'], 'tag_type' => 'single_group']);
        }

        $promotionGroupsTeamService = new PromotionGroupsTeamService();
        $teamFilter = [
            'act_id' => $filter['groups_activity_id'],
            'company_id' => $filter['company_id']
        ];
        $promotionGroupsTeamService->promotionGroupsTeamRepository->updateBySimpleFilter($teamFilter, $data);
        $promotionGroupsTeamService->scheduleAutoCancelGroupOrders();

        $job = (new SalespersonItemsShelvesJob($filter['company_id'], $result['groups_activity_id'], 'group'));
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);
        return $result;
    }

    /**
     * 删除拼团活动
     * @param array $filter
     * @return mixed
     */
    public function deleteActivity(array $filter)
    {
        $data['disabled'] = true;
        $result = $this->promotionGroupsActivityRepository->updateOneBy($filter, $data);
        if ($result) {
            $promotionItemTagService = new PromotionItemTagService();
            $promotionItemTagService->deleteBy(['promotion_id' => $filter['groups_activity_id'], 'company_id' => $filter['company_id'], 'tag_type' => 'single_group']);
        }

        $job = (new SalespersonItemsShelvesJob($filter['company_id'], $result['groups_activity_id'], 'group'));
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);
        return $result;
    }

    /**
     * 拼团活动检测
     *
     * @param array $params
     * @param int   $groupId 活动id
     * @return mixed
     */
    public function checkGroupParam(array $params, $groupId = '')
    {
        // 查询当前商品是否有拼团活动
        $data['begin_time'] = strtotime($params['date'][0]);
        $data['end_time'] = strtotime($params['date'][1]);

        if ($data['end_time'] - $data['begin_time'] < $params['limit_time'] * 3600) {
            throw new ResourceException(trans('PromotionsBundle.activity_time_longer_than_group_time'));
        }

        $this->checkActivityValidByGroup($params['company_id'], $params['goods_id'], $data['begin_time'], $data['end_time'], $groupId);
        //$this->checkActivityValid($params['company_id'], $params['goods_id'], $data['begin_time'], $data['end_time'], $groupId);
        //$this->checkMarketingActivity($params['company_id'], $params['goods_id'], $data['begin_time'], $data['end_time'], '');

        // 查询是否有相同名称的活动
        $_filter = [
            'company_id' => $params['company_id'],
            'act_name' => $params['act_name']
        ];
        if (!empty($groupId)) {
            $_filter['groups_activity_id|neq'] = $groupId;
        }
        $info = $this->getList($_filter);

        if ($info['list']) {
            throw new ResourceException(trans('PromotionsBundle.activity_name_cannot_same'));
        }
        $beginTime = ($data['begin_time'] > time()) ? $data['begin_time'] : time();
        if ($beginTime < time()) {
            throw new ResourceException(trans('PromotionsBundle.start_time_greater_than_current'));
        }

        if ($beginTime >= $data['end_time']) {
            throw new ResourceException(trans('PromotionsBundle.end_time_greater_than_start'));
        }
        $itemsService = new ItemsService();
        $itemInfo = $itemsService->getItemsSkuDetail($params['goods_id']);
        if ($itemInfo && $itemInfo['company_id'] != $params['company_id']) {
            throw new ResourceException(trans('PromotionsBundle.product_not_found'));
        }
        if ($itemInfo['is_gift'] == 1) {
            throw new ResourceException(trans('PromotionsBundle.gift_product_check_error'));
        }
        $act_price = bcmul($params['act_price'], 100);
        if ($act_price > $itemInfo['price']) {
            throw new ResourceException(trans('PromotionsBundle.group_price_cannot_greater_than_sale_price'));
        }

        $distributorService = new DistributorService();
        $distributor = $distributorService->getLists(['company_id' => $params['company_id'], 'is_valid|neq' => 'delete'], 'distributor_id');
        if ($distributor) {
            $distributorItemsService = new DistributorItemsService();
            $distributorItem = $distributorItemsService->lists(['company_id' => $params['company_id'], 'distributor_id' => array_column($distributor, 'distributor_id'), 'item_id' => $itemInfo['item_id'], 'price|lt' => $act_price, 'is_total_store' => false], ["created" => "DESC"], 1, 1);
            if ($distributorItem['total_count'] > 0) {
                // $distributor = $distributorService->getInfo(['company_id' => $params['company_id'], 'distributor_id' => $distributorItem['list'][0]['distributor_id']]);
                throw new ResourceException(trans('PromotionsBundle.group_price_cannot_greater_than_store_price', ['store_id' => $distributorItem['list'][0]['distributor_id']]));
            }
        }

        $data['act_name'] = $params['act_name'];
        $data['company_id'] = $params['company_id'];
        $data['act_price'] = $params['act_price'] * 100;
        $data['begin_time'] = strtotime($params['date'][0]);
        $data['end_time'] = strtotime($params['date'][1]);
        $data['free_post'] = $params['free_post'];
        $data['goods_id'] = $params['goods_id'];
        $data['group_goods_type'] = $itemInfo['item_type'];
        $data['limit_buy_num'] = $params['limit_buy_num'];
        $data['limit_time'] = $params['limit_time'];
        $data['person_num'] = $params['person_num'];
        $data['pics'] = $params['pics'];
        $data['rig_up'] = $params['rig_up'];
        $data['robot'] = $params['robot'];
        $data['store'] = $params['store'];
        $data['share_desc'] = $params['share_desc'];
        return $data;
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
        return $this->promotionGroupsActivityRepository->$method(...$parameters);
    }

    /**
     * 获取活动状态
     * @param array $promotionGroupTeamInfo
     * @return int|null
     */
    public function getStatus(array $promotionGroupInfo): ?int
    {
        // 不存在开始时间和结束时间的话就返回null
        if (empty($promotionGroupInfo["begin_time"]) && empty($promotionGroupInfo["end_time"])) {
            return null;
        }
        $now = time();
        if ($promotionGroupInfo["begin_time"] > $now) {
            return self::STATUS_COMING_SOON; // 未开始
        } elseif ($promotionGroupInfo["end_time"] < $now) {
            return self::STATUS_ENDED; // 已结束
        } else {
            return self::STATUS_ONGOING; // 正在进行中
        }
    }


    /**
     * 活动状态 - 未开始
     */
    public const STATUS_COMING_SOON = 1;

    /**
     * 活动状态 - 正在进行
     */
    public const STATUS_ONGOING = 2;

    /**
     * 活动状态 - 已结束
     */
    public const STATUS_ENDED = 3;
}
