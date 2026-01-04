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

namespace OrdersBundle\Services\Orders;

use CommunityBundle\Services\CommunityActivityService;
use CommunityBundle\Services\CommunityChiefService;
use CommunityBundle\Services\CommunityChiefZitiService;
use CommunityBundle\Services\CommunityOrderRelActivityService;
use Dingo\Api\Exception\ResourceException;
use MembersBundle\Services\MemberService;
use OrdersBundle\Entities\NormalOrders;
use OrdersBundle\Repositories\NormalOrdersRepository;
use AftersalesBundle\Services\AftersalesRefundService;
use CommunityBundle\Services\CommunitySettingService;

class CommunityNormalOrderService extends AbstractNormalOrder
{
    public $orderClass = 'community';

    public $orderType = 'normal';

    // 订单是否支持优惠券优惠
    public $isSupportCouponDiscount = false;

    // 订单是否需要进行门店验证
    public $isCheckShopValid = false;

    // 订单是否需要进行店铺验证
    public $isCheckDistributorValid = true;

    // todo 需要支持购物车
    public $isSupportCart = false;

    //订单是否支持积分抵扣
    public $isSupportPointDiscount = false;

    // 订单是否支持获取积分
    public $isSupportGetPoint = false;

    private $activityInfo;
    private $chiefInfo;
    private $zitiInfo;

    public function __construct()
    {
        parent::__construct();
    }

    public function checkCreateOrderNeedParams($params, $isCreate)
    {
        $rules = [
            'company_id'            => ['required', '企业id必填'],
            'distributor_id'        => ['required', '所属店铺ID必填'],
            'community_activity_id' => ['required', '社区活动ID必填'],
            // 'community_ziti_id'     => ['required', '社区自提ID必填'],
            'user_id'               => ['required', '用户id必填'],
        ];
        if ($isCreate) {
            $rules['community_ziti_id'] = ['required', '社区自提ID必填'];
        }

        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
    }

    public function checkoutCartItems($params)
    {
        if (isset($params['items']) && $params['items']) {
            $params['items'] = array_filter($params['items'], function ($item) {
                return $item['num'] > 0;
            });

            if (empty($params['items'])) {
                throw new ResourceException(trans('OrdersBundle/Order.select_group_purchase_items'));
            }

            return $params;
        }

        throw new ResourceException(trans('OrdersBundle/Order.select_group_purchase_items'));
        //todo 团购购物车
    }

    /**
     * 检查参数的合法性
     */
    public function check($params)
    {
        $activityService = new CommunityActivityService();
        $activity        = $activityService->getActivity(['activity_id' => $params['community_activity_id']]);

        if ($activity['activity_status'] != 'public') {
            throw new ResourceException('无效的团购');
        }

        if ($activity['start_time'] > time()) {
            throw new ResourceException('团购未开始');
        }

        if ($activity['end_time'] < time()) {
            throw new ResourceException('团购已结束');
        }

        $chiefService = new CommunityChiefService();
        $chief        = $chiefService->getInfoById($activity['chief_id']);
        if (!$chief) {
            throw new ResourceException('团长信息获取失败');
        }

        $activityItemIds = [];
        array_walk($activity['items'], function ($item) use (&$activityItemIds) {
            if (isset($item['spec_items'])) {
                array_walk($item['spec_items'], function ($item) use (&$activityItemIds) {
                    $activityItemIds[] = $item['item_id'];
                });
            } else {
                $activityItemIds[] = $item['item_id'];
            }
        });
        foreach ($params['items'] as $item) {
            if (!in_array($item['item_id'], $activityItemIds)) {
                throw new ResourceException('无效的团购商品');
            }
        }

        $this->activityInfo = $activity;
        $this->chiefInfo    = $chief;

        if ($params['community_ziti_id'] ?? 0) {
            $activityZitiIds = array_column($activity['ziti'], 'ziti_id');
            if (!in_array($params['community_ziti_id'], $activityZitiIds)) {
                throw new ResourceException('无效的自提点');
            }

            $zitiService = new CommunityChiefZitiService();
            $ziti        = $zitiService->getInfoById($params['community_ziti_id']);
            if (!$ziti) {
                throw new ResourceException('无效的自提点');
            }

            $this->zitiInfo = $ziti;
        }

        return true;
    }

    // todo 如果团购可以改价的话
    // public function getOrderItemPrice($itemId)
    // {

    // }

    // todo 团购购物车
    // public function emptyCart($params) {

    // }

    // todo 创建团购关联数据
    public function createExtend($orderData, $params)
    {
        $settingService = new CommunitySettingService($orderData['company_id'], $orderData['distributor_id']);
        $setting = $settingService->getSetting();

        $relData = [
            'order_id'            => $orderData['order_id'],
            'company_id'          => $orderData['company_id'],
            'chief_id'            => $this->chiefInfo['chief_id'],
            'chief_name'          => $this->chiefInfo['chief_name'],
            'chief_avatar'        => $this->chiefInfo['chief_avatar'],
            'activity_id'         => $this->activityInfo['activity_id'],
            'activity_name'       => $this->activityInfo['activity_name'],
            'ziti_name'           => $this->zitiInfo['ziti_name'],
            'ziti_address'        => $this->zitiInfo['province'] . $this->zitiInfo['city'] . $this->zitiInfo['area'] . $this->zitiInfo['address'],
            'ziti_lng'            => $this->zitiInfo['lng'],
            'ziti_lat'            => $this->zitiInfo['lat'],
            'ziti_contact_user'   => $this->zitiInfo['ziti_contact_user'],
            'ziti_contact_mobile' => $this->zitiInfo['ziti_contact_mobile'],
            'extra_data'          => $params['community_extra_data'] ?? '',
            'rebate_ratio'        => $setting['rebate_ratio'],
        ];
        $relService = new CommunityOrderRelActivityService();
        $relService->create($relData);
    }

    public function formatOrderData($orderData, $params, $isCheck)
    {
        // 如果活动时间结束，则自动取消订单
        $orderData['auto_cancel_time']  = ($this->activityInfo['end_time'] > $orderData['auto_cancel_time']) ? $orderData['auto_cancel_time'] : $this->activityInfo['end_time'];
        $orderData['act_id']            = $params['community_activity_id'];
        $orderData['receiver_name']     = $params['receiver_name'] ?? '';
        $orderData['receiver_mobile']   = $params['receiver_mobile'] ?? '';
        $orderData['receiver_state']    = $params['receiver_state'] ?? '';
        $orderData['receiver_city']     = $params['receiver_city'] ?? '';
        $orderData['receiver_district'] = $params['receiver_district'] ?? '';
        $orderData['receiver_address']  = $params['receiver_address'] ?? '';
        $orderData['receiver_zip']      = $params['receiver_zip'] ?? '';

        foreach ($orderData['items'] as $k => $row) {
            $orderData['items'][$k]['act_id'] = $params['community_activity_id'];
        }

        return $orderData;
    }

    public function orderStatusUpdate($filter, $orderStatus, $payType = '')
    {
        // 支付更新跟团号
        if ($orderStatus == 'PAYED') {
            $orderData                 = $this->getOrderInfo($filter['company_id'], $filter['order_id']);
            $orderInfo                 = $orderData['orderInfo'];
            $data['activity_trade_no'] = $this->getActivityTradeNo($orderInfo['company_id'], $orderInfo['act_id'], $orderInfo['order_id']);
            $relService                = new CommunityOrderRelActivityService();
            $relService->updateOneBy(['order_id' => $orderInfo['order_id']], $data);
        }

        return parent::orderStatusUpdate($filter, $orderStatus, $payType);
    }

    // todo 增加销量
    // public function incrSales($orderId, $companyId)
    // {
    //     return true;
    // }

    // todo 团购订单自提
    // public function finishOrderZiti($companyId, $orderId, $communityId, $orderInfo)
    // {
    // }

    // todo 定时取消订单
    // public function scheduleCancelOrders()
    // {
    // }

    // todo 取消订单
    // public function cancelOrder($data)
    // {
    // }

    // todo 已支付订单取消订单
    // public function cancelPayedOrder($orderInfo, $params)
    // {
    // }

    // todo 获取团购活动信息
    public function getActivityInfo($companyId, $activityId)
    {
    }

    //getOrderList 团购订单列表
    public function getOrderList($filter, $page = 0, $limit = -1, $orderBy = ['create_time' => 'DESC'], $isGetTotal = true, $from = 'api')
    {
        $data = parent::getOrderList($filter, $page, $limit, $orderBy, $isGetTotal, $from);
        if (!$data || !$data['list']) {
            return $data;
        }

        $relFilter = [
            'order_id' => array_column($data['list'], 'order_id'),
        ];
        $relService = new CommunityOrderRelActivityService();
        $orderRels  = $relService->lists($relFilter);
        $orderRelActs = array_bind_key($orderRels['list'], 'order_id');

        $activityFilter = [
            'activity_id' => array_column($data['list'], 'act_id'),
        ];
        $activityService = new CommunityActivityService();
        $activitys = $activityService->lists($activityFilter, 'activity_id,activity_status');
        $activitys = array_bind_key($activitys['list'], 'activity_id');

        $memberFilter = [
            'user_id' => array_column($data['list'], 'user_id'),
        ];
        $membersService = new MemberService();
        $members = $membersService->getMemberList($memberFilter, 1, count($data['list']));
        $members = array_bind_key($members, 'user_id');

        array_walk($data['list'], function (&$v) use ($orderRelActs, $activitys, $members,$activityService) {
            //装饰团购活动信息
            $v['community_info'] = $orderRelActs[$v['order_id']] ?? [];
            if (isset($activitys[$v['act_id']])) {
                $v['community_info']['activity_status'] = $activitys[$v['act_id']]['activity_status'];
            }
            //取活动名
            $v['community_info']['activity_name'] = $activityService->getActivityName($v['act_id']);
            if (isset($v['community_info']['extra_data']) && $v['community_info']['extra_data']) {
               $v['community_info']['extra_data'] =json_decode($v['community_info']['extra_data'],true ) ;
               $v['community_info']['extra_data'] = array_chunk($v['community_info']['extra_data'],1, true);
               $v['community_info']['extra_data_str'] = [];
               foreach ($v['community_info']['extra_data'] as $key => &$value) {
                 $v['community_info']['extra_data_str'][$key] = key($value).':'.implode($value);
               }
            }
            //装饰会员信息
            $v['total_num'] = array_sum(array_column($v['items'], 'num'));
            $v['member']    = $members[$v['user_id']] ?? [];
            $v['auto_cancel_seconds'] = $v['auto_cancel_time'] - time();
        });

        return $data;
    }

    public function getOrderInfo($companyId, $orderId, $checkaftersales = false, $from = 'api')
    {
        $data = parent::getOrderInfo($companyId, $orderId, $checkaftersales, $from);

        $relFilter = [
            'order_id' => $orderId,
        ];
        $relService                          = new CommunityOrderRelActivityService();
        $orderRel                            = $relService->getInfo($relFilter);
        $data['orderInfo']['community_info'] = $orderRel;

        return $data;
    }

    private function getActivityTradeNo($companyId, $activityId, $orderId)
    {
        $haskKey = 'community_activity_trade_no_hash:' . $companyId . '_' . $activityId;
        $incrKey = 'community_activity_trade_no_incr:' . $companyId . '_' . $activityId;
        $tradeNo = app('redis')->hget($haskKey, $orderId);
        if (!$tradeNo) {
            $tradeNo = app('redis')->incr($incrKey);
            app('redis')->hset($haskKey, $orderId, $tradeNo);
        }
        return $tradeNo;
    }

    //团长订单
    public function getChiefOrderList($filter, $page = 0, $limit = -1, $orderBy = ['create_time' => 'DESC'], $isGetTotal = true, $from = 'api')
    {
        //取团长活动订单
        $activityFilter = [
            'chief_id'   => $filter['chief_id'],
            'company_id' => $filter['company_id'],
        ];
        if ($filter['activity_id'] ?? '') {
            $activityFilter['activity_id'] = $filter['activity_id'];
        }
        $relService = new CommunityOrderRelActivityService();
        $orderLists  = $relService->lists($activityFilter);

        if (!$orderLists || !$orderLists['list']) {
            $result['list']        = [];
            $result['pager']['count'] = 0;
            return $result;
        }
        //组订单参数
        $orderIds = array_column($orderLists['list'], 'order_id');
        // $filter['order_id|in'] = implode(',', $orderIds);
        $filter['order_id'] = $orderIds;
        unset($filter['chief_id'], $filter['activity_id']);
        
        $data = $this->getOrderList($filter, $page, $limit, $orderBy , $isGetTotal , $from );

        //统计数据 [去过滤状态] 冗余
        $normalOrdersRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
        unset($filter['order_status'],$filter['delivery_status'],$filter['ziti_status'],$filter['order_status|in'],$filter['auto_cancel_time|gt']);
        $allOrder = $normalOrdersRepository->getList($filter);

        $totalFee = 0;
        $appliedTotalNum = 0;
        $appliedTotalRefundFee = 0;
        $aftersalesRefundService = new AftersalesRefundService();
        foreach ($allOrder as $order) {
            $totalFee +=$order['total_fee'];

            $appliedTotalRefundFee += $aftersalesRefundService->getTotalRefundFee($order['company_id'], $order['order_id']);

            if ($order['order_status'] == 'CANCEL') {
                $appliedTotalNum ++;
            }
        }
        $data['statistics'] = [
            'totalFee' => $totalFee,
            'appliedTotalNum' => count($allOrder) - $appliedTotalNum,
            'appliedTotalRefundFee' => $appliedTotalRefundFee,
        ];
        
        return $data;
    }
}
