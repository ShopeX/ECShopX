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

namespace OpenapiBundle\Services\Order;

use MembersBundle\Entities\MembersInfo;
use MembersBundle\Entities\Members;
use MembersBundle\Entities\MembersAssociations;
use MembersBundle\Entities\WechatUsers;
use OrdersBundle\Entities\NormalOrders;
use OpenapiBundle\Services\BaseService;
use OrdersBundle\Services\TradeService;
use GoodsBundle\Services\ItemsService;

class OrderService extends BaseService
{
    public function getEntityClass(): string
    {
        return NormalOrders::class;
    }

    /**
     * 格式化订单列表
     *
     * @param array $dataList 订单列表数据
     */
    public function formateOrderListStruct($companyId, $dataList): array
    {
        $dataList['total_count'] = $dataList['pager']['count'];
        $page = $dataList['pager']['page_no'];
        $pageSize = $dataList['pager']['page_size'];
        unset($dataList['pager']);
        $result = $this->handlerListReturnFormat($dataList, (int)$page, (int)$pageSize);
        if (empty($dataList['list'])) {
            return $result;
        }

        $tradeService = new TradeService();
        $orderIdList = array_column($dataList['list'], 'order_id');
        $tradeIndex = $tradeService->getTradeIndexByOrderIdList($companyId, $orderIdList);

        $result['list'] = [];
        foreach ($dataList['list'] as $key => $list) {
            $_list = [
                'order_id' => $list['order_id'],
                'trade_no' => $tradeIndex[$list['order_id']] ?? '-',
                'title' => $list['title'],
                'total_fee' => $list['total_fee'],
                'shop_code' => $list['distributor_info']['shop_code'] ?? '',
                'shop_name' => $list['distributor_info']['name'] ?? '',
                'mobile' => $list['mobile'],
                'order_status' => $list['order_status'],
                'pay_status' => $list['pay_status'],
                'create_time' => date('Y-m-d H:i:s', $list['create_time']),
                'update_time' => date('Y-m-d H:i:s', $list['update_time']),
                'delivery_corp' => $list['delivery_corp'],
                'delivery_code' => $list['delivery_code'],
                'delivery_time' => $list['delivery_time'] ? date('Y-m-d H:i:s', $list['delivery_time']) : '',
                'delivery_status' => $list['delivery_status'],
                'member_discount' => $list['member_discount'],
                'coupon_discount' => $list['coupon_discount'],
                'discount_fee' => $list['discount_fee'],
                'discount_info' => $this->formateDiscountInfo(json_decode($list['discount_info'], 1)),
                'cancel_status' => $list['cancel_status'],
                'end_time' => $list['end_time'] ? date('Y-m-d H:i:s', $list['end_time']) : '',
                'is_self' => $list['distributor_id'] == 0,

            ];
            $result['list'][] = $_list;
        }
        return $result;
    }

    /**
     * 格式化优惠详情
     * @param  array $discountInfo 优惠详情列表
     * @return array               处理后的优惠详情列表
     */
    private function formateDiscountInfo($discountInfo)
    {
        if (empty($discountInfo)) {
            return [];
        }
        $_discountInfo = [];
        foreach ($discountInfo as $info) {
            $_discountInfo[] = [
                'id' => $info['id'] ?? "",
                'type' => $info['type'] ?? "",
                'info' => $info['info'] ?? "",
                'rule' => $info['rule'] ?? "",
                'discount_fee' => $info['discount_fee'] ?? 0,
            ];
        }
        return $_discountInfo;
    }

    /**
     * 格式化订单详情数据
     * @param  array $orderDetail 订单详情数据
     * @return array              处理后的订单详情数据
     */
    public function formateOrderInfoStruct($companyId, $orderDetail): array
    {
        $orderInfo = $orderDetail['orderInfo'];
        $distributor = $orderDetail['distributor'];
        $membersInfoRepository = app('registry')->getManager('default')->getRepository(MembersInfo::class);
        $userInfo = $membersInfoRepository->getInfo(['user_id' => $orderInfo['user_id'],'company_id' => $companyId]);
        if (!empty($userInfo)) {
            $membersRepository = app('registry')->getManager('default')->getRepository(Members::class);
            $member = $membersRepository->get(['company_id' => $companyId, 'user_id' => $userInfo['user_id']]);
            $membersAssociationsRepository = app('registry')->getManager('default')->getRepository(MembersAssociations::class);
            $memberWechatRepository = app('registry')->getManager('default')->getRepository(WechatUsers::class);
            $memberAsso = $membersAssociationsRepository->get(['company_id' => $companyId, 'user_id' => $userInfo['user_id']]);
            if (!empty($memberAsso['unionid'])) {
                $memberWechatInfo = $memberWechatRepository->getUserInfo(['company_id' => $companyId, 'unionid' => $memberAsso['unionid'], 'authorizer_appid' => $member['wxa_appid']]);
            }
        }
        $tradeService = new TradeService();
        $tradeIndex = $tradeService->getTradeIndexByOrderIdList($companyId, $orderInfo['order_id']);
        $succTrade = $tradeService->getSuccTradeByOrder($companyId, $orderInfo['order_id']);
        unset($orderDetail);
        $result = [
            'order_id' => $orderInfo['order_id'],
            'trade_no'=> $tradeIndex[$orderInfo['order_id']] ?? '-',
            'title' => $orderInfo['title'],
            'shop_code' => $distributor['shop_code'],
            'total_fee' => $orderInfo['total_fee'],
            'mobile' => $orderInfo['mobile'],
            'freight_fee' => $orderInfo['freight_fee'],
            'item_fee' => $orderInfo['item_fee'],
            'receipt_type' => $orderInfo['receipt_type'],
            'order_status' => $orderInfo['order_status'],
            'pay_status' => $orderInfo['pay_status'],
            'pay_time' => $succTrade['pay_time'] ?? '',
            'delivery_corp' => $orderInfo['delivery_corp'],
            'delivery_code' => $orderInfo['delivery_code'],
            'delivery_time' => $orderInfo['delivery_time'] ? date('Y-m-d H:i:s', $orderInfo['delivery_time']) : '',
            'end_time' => $orderInfo['end_time'] ? date('Y-m-d H:i:s', $orderInfo['end_time']) : '',
            'delivery_status' => $orderInfo['delivery_status'],
            'cancel_status' => $orderInfo['cancel_status'],
            'receiver_name' => $orderInfo['receiver_name'],
            'receiver_mobile' => $orderInfo['receiver_mobile'],
            'receiver_zip' => $orderInfo['receiver_zip'],
            'receiver_state' => $orderInfo['receiver_state'],
            'receiver_city' => $orderInfo['receiver_city'],
            'receiver_district' => $orderInfo['receiver_district'],
            'receiver_address' => $orderInfo['receiver_address'],
            'member_discount' => $orderInfo['member_discount'],
            'coupon_discount' => $orderInfo['coupon_discount'],
            'discount_fee' => $orderInfo['discount_fee'],
            'discount_info' => $this->formateDiscountInfo($orderInfo['discount_info']),
            'create_time' => date('Y-m-d H:i:s', $orderInfo['create_time']),
            'update_time' => date('Y-m-d H:i:s', $orderInfo['update_time']),
            'pay_type' => $orderInfo['pay_type'],
            'remark' => $orderInfo['remark'],
            'distributor_remark' => $orderInfo['distributor_remark'] ?? '',
            'point_use' => $orderInfo['point_use'],
            'point_fee' => $orderInfo['point_fee'],
            'items' => $this->formateItems($orderInfo['items'], $companyId),
            'user_id' => $orderInfo['user_id'],
            'username' => $userInfo['username'] ?? '',
            'unionid' => $memberWechatInfo['unionid'] ?? '',
            'openid' => $memberWechatInfo['open_id'] ?? '',
            'order_class' => $orderInfo['order_class'],
        ];
        return $result;
    }

    /**
     * 格式化订单商品数据
     * @param  array $orderItems 订单商品数据列表
     * @return array             处理后的订单商品数据
     */
    private function formateItems($orderItems, $companyId): array
    {
        $itemsService = new ItemsService();
        $itemIds = array_column($orderItems, 'item_id');
        $itemLists = $itemsService->getItems($itemIds, $companyId, 'item_id,goods_id');
        $itemLists = array_column($itemLists, null, 'item_id');
        $item = [];
        foreach ($orderItems as $items) {
            $item[] = [
                'goods_id' => $itemLists[$items['item_id']]['goods_id'] ?? 0,
                'item_bn' => $items['item_bn'],
                'item_name' => $items['item_name'],
                'num' => $items['num'],
                'price' => $items['price'],
                'total_fee' => $items['total_fee'],
                'item_fee' => $items['item_fee'],
                'discount_info' => $this->formateDiscountInfo($items['discount_info']),
                'point_use' => $items['share_points'],// 积分抵扣时分摊的积分值
                'point_fee' => $items['point_fee'],// 积分抵扣时分摊的积分的金额，以分为单位
                'item_spec_desc' => $items['item_spec_desc'],
                'volume' => $items['volume'],
                'weight' => $items['weight'],
                'item_type' => $items['order_item_type'],
            ];
        }
        return $item;
    }

    /**
     * 获取取消原因列表
     * @return array 取消原因列表
     */
    public function cancelReasons()
    {
        return config('order.cancelOrderReason');
    }

    /**
     * 格式化交易单列表
     *
     * @param array $dataList 交易单列表数据
     */
    public function formateTradeList($dataList, $page, $pageSize): array
    {
        $result = $this->handlerListReturnFormat($dataList, (int)$page, (int)$pageSize);

        if (empty($dataList['list'])) {
            return $result;
        }
        $result['list'] = [];
        foreach ($dataList['list'] as $key => $list) {
            $_list = [
                'trade_id' => $list['tradeId'],
                'order_id' => $list['orderId'],
                'mch_id' => $list['mchId'],
                'total_fee' => $list['totalFee'],
                'discount_fee' => $list['discountFee'],
                'fee_type' => $list['feeType'],
                'pay_fee' => $list['payFee'],
                'trade_state' => $list['tradeState'],
                'pay_type' => $list['payType'],
                'time_start' => date('Y-m-d H:i:s', $list['timeStart']),
                'time_expire' => date('Y-m-d H:i:s', $list['timeExpire']),
            ];
            $result['list'][] = $_list;
        }
        return $result;
    }

    /**
     * 格式化运费模板列表数据
     * @param  array $dataList 运费模板列表数据
     * @param  string $page     当前页数
     * @param  string $pageSize 每页条数
     * @return array           处理后的运费模板数据
     */
    public function formateShippingTemplatesList($dataList, $page, $pageSize)
    {
        $result = $this->handlerListReturnFormat($dataList, (int)$page, (int)$pageSize);
        if (empty($dataList['list'])) {
            return $result;
        }
        $result['list'] = [];
        foreach ($dataList['list'] as $key => $list) {
            unset($list['company_id'],$list['create_time'],$list['distributor_id']);
            $list['update_time'] = date('Y-m-d H:i:s', $list['update_time']);
            $result['list'][$key] = $list;
        }
        return $result;
    }
}
