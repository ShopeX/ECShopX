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

use OrdersBundle\Interfaces\OrderInterface;
use OrdersBundle\Entities\BargainOrders;
use OrdersBundle\Entities\OrderAssociations;
use OrdersBundle\Entities\Trade;
use OrdersBundle\Traits\GetOrderIdTrait;
use OrdersBundle\Traits\GetUserIdByMobileTrait;

use PromotionsBundle\Services\BargainPromotionsService;
use PromotionsBundle\Services\UserBargainService;

use Exception;
use Dingo\Api\Exception\ResourceException;

class BargainOrderService implements OrderInterface
{
    use GetOrderIdTrait;
    use GetUserIdByMobileTrait;

    public $orderClass = 'bargain';

    public $orderType = 'bargain';

    // 订单是否支持优惠券优惠
    public $isSupportCouponDiscount = false;

    // 订单是否需要进行门店验证
    public $isCheckShopValid = false;

    // 订单是否需要进行店铺验证
    public $isCheckDistributorValid = false;

    // 订单是否需要验证白名单
    public $isCheckWhitelistValid = true;

    public $bargainOrderRepository;

    public $orderAssociationsRepository;
    public $userBargain;

    public function __construct()
    {
        $this->bargainOrderRepository = app('registry')->getManager('default')->getRepository(BargainOrders::class);
        $this->orderAssociationsRepository = app('registry')->getManager('default')->getRepository(OrderAssociations::class);
    }

    public function checkCreateOrderNeedParams(&$params)
    {
        if (!isset($params['receiver_zip'])) {
            $params['receiver_zip'] = '000000';
        }

        $rules = [
            'item_num' => ['required|integer|min:1', '商品数量必填,商品数量必须为整数,商品数量最少为1'],
            'company_id' => ['required', '企业id必填'],
            'user_id' => ['required', '用户id必填'],
            'mobile' => ['required|mobile', '未授权手机号，请授权'],
            'bargain_id' => ['required', '助力活动id必填'],
            'receiver_name' => ['required_with:bargain_id|zhstring', '请填写正确的收货人姓名'],
            'receiver_mobile' => ['required_with:bargain_id', '请填写联系方式'],
            'receiver_zip' => ['required_with:bargain_id|postcode', '请填写正确的邮编'],
            'receiver_state' => ['required_with:bargain_id|zhstring', '请填写正确的省份'],
            'receiver_city' => ['required_with:bargain_id|zhstring', '请填写正确的城市'],
            'receiver_district' => ['required_with:bargain_id|zhstring', '请填写正确的地区'],
            'receiver_address' => ['required_with:bargain_id', '请填写正确的详细地址'],
        ];

        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        return true;
    }

    public function check($params)
    {
        $orderFilter = ['user_id' => $params['user_id'], 'bargain_id' => $params['bargain_id']];
        $order = $this->bargainOrderRepository->get($orderFilter);

        //获取用户砍价详情
        $userBargainService = new UserBargainService();
        $userBargain = $userBargainService->getBargainInfo($params['company_id'], $params['bargain_id'], $params['user_id']);

        if ($order) {
            if ($order['order_status'] == 'DONE') {
                throw new ResourceException("您已经参与过此活动， 不能重复参加！");
            }
            //更新订单备注
            $orderResult = $this->bargainOrderRepository->update($orderFilter, ['remark' => $params['remark']]);
        } else {
            if (!$userBargain['user_bargain_info']) {
                throw new ResourceException("您未参加此活动");
            }
            //获取商品详情
            $bargainPromotion = $userBargain['bargain_info'];
            if (!$bargainPromotion) {
                throw new ResourceException("此次活动不存在！");
            }
            if ($bargainPromotion['end_time'] < time()) {
                throw new ResourceException("助力活动已过期，期待您的下次参与");
            }
            if ($bargainPromotion['order_num'] >= $bargainPromotion['limit_num']) {
                throw new ResourceException("商品已售完，期待您的下次参与");
            }
        }
        $this->userBargain = $userBargain;

        return true;
    }

    // 砍价订单为单独商品
    public function getOrderItemIds($params)
    {
        return null;
    }

    public function minusItemStore()
    {
        return true;
    }

    public function formatOrderData($orderData, $params, $isCheck)
    {
        $bargainPromotion = $this->userBargain['bargain_info'];
        $orderData['title'] = $bargainPromotion['item_name'];
        $orderData['bargain_id'] = $params['bargain_id'];
        $orderData['item_name'] = $bargainPromotion['item_name'];
        $orderData['item_price'] = $bargainPromotion['mkt_price'];
        $orderData['item_fee'] = $bargainPromotion['mkt_price'];
        $orderData['item_pics'] = $bargainPromotion['item_pics'];
        $orderData['item_num'] = $params['item_num'] ? $params['item_num'] : 1;
        $orderData['receiver_name'] = $params['receiver_name'];
        $orderData['receiver_mobile'] = $params['receiver_mobile'];
        $orderData['receiver_zip'] = $params['receiver_zip'];
        $orderData['receiver_state'] = $params['receiver_state'];
        $orderData['receiver_city'] = $params['receiver_city'];
        $orderData['receiver_district'] = $params['receiver_district'];
        $orderData['receiver_address'] = $params['receiver_address'];

        $discount = isset($this->userBargain['user_bargain_info']['cutdown_amount']) ? intval($this->userBargain['user_bargain_info']['cutdown_amount']) : 0;
        $orderData['total_fee'] = $this->getTotalPrice($bargainPromotion['mkt_price'], $bargainPromotion['price'], $discount);
        return $orderData;
    }

    public function create($orderData, $params)
    {
        //订单主表创建订单
        $assoc = $this->orderAssociationsRepository->create($orderData);

        //参与助力列表更新
        $filter = [
            'company_id' => $params['company_id'],
            'user_id' => $params['user_id'],
            'bargain_id' => $params['bargain_id'],
        ];
        $userBargainService = new UserBargainService();
        $userBargain = $userBargainService->updateUserBargain($filter, ['is_ordered' => true]);

        //砍价订单表创建
        $orderResult = $this->bargainOrderRepository->create($orderData);
        return $orderResult;
    }

    /**
     * @params filter company_id, user_id, order_id
     *
     */
    public function update($filter, $orderStatus)
    {
        $updateInfo['order_status'] = $orderStatus;

        $conn = app('registry')->getConnection('default');
        $orderResult = $conn->transactional(function ($conn) use ($filter, $updateInfo) {
            $order = $this->bargainOrderRepository->get($filter);
            if (!$order) {
                throw new ResourceException("订单不存在！");
            }
            $orderResult = $this->bargainOrderRepository->update($filter, $updateInfo);
            $this->orderAssociationsRepository->update($filter, $updateInfo);
            if (isset($orderResult['bargain_id']) && $orderResult['bargain_id']) {
                $bargainPromotionsService = new BargainPromotionsService();
                $bargainPromotionsService->updateBargainOrderNum($orderResult['bargain_id']);
            }

            return $orderResult;
        });

        return $orderResult;
    }

    /**
     * 复写方法
     * @return bool
     */
    public function incrSales()
    {
        return true;
    }

    /**
     * @params filter company_id, user_id, order_id
     *
     */
    public function orderStatusUpdate($filter, $orderStatus)
    {
        $updateInfo['order_status'] = $orderStatus;

        $conn = app('registry')->getConnection('default');
        $orderResult = $conn->transactional(function ($conn) use ($filter, $updateInfo) {
            $order = $this->bargainOrderRepository->get($filter);
            if (!$order) {
                throw new ResourceException("订单不存在！");
            }
            $orderResult = $this->bargainOrderRepository->update($filter, $updateInfo);
            $this->orderAssociationsRepository->update($filter, $updateInfo);
            if (isset($orderResult['bargain_id']) && $orderResult['bargain_id']) {
                $bargainPromotionsService = new BargainPromotionsService();
                $bargainPromotionsService->updateBargainOrderNum($orderResult['bargain_id']);
            }

            return $orderResult;
        });

        return $orderResult;
    }

    private function getTotalPrice($mktPrice, $basePrice, $discount)
    {
        $maxDiscount = $mktPrice - $basePrice;
        if ($maxDiscount <= $discount) {
            $discount = $maxDiscount;
        }
        $price = $mktPrice - $discount;

        return $price;
    }

    public function getOrderList($filter, $page = 1, $limit = -1, $orderBy = ['create_time' => 'DESC'])
    {
        $filter = $this->checkMobile($filter);
        $offset = ($page - 1) * $limit;
        $result = $this->bargainOrderRepository->getList($filter, $offset, $limit, $orderBy);
        return $result;
    }

    /**
     * 获取订单详情
     */
    public function getOrderInfo($companyId, $orderId, $checkaftersales = false, $from = 'api')
    {
        $filter = [
            'company_id' => $companyId,
            'order_id' => $orderId
        ];
        $order = $this->bargainOrderRepository->get($filter);
        if (!$order) {
            throw new Exception("订单号为{$orderId}的订单不存在");
        }

        //获取交易单信息
        $tradeRepository = app('registry')->getManager('default')->getRepository(Trade::class);
        $trade = $tradeRepository->getTradeList($filter);
        if ($trade['list']) {
            $tradeInfo = $trade['list'][0];
        }

        $result = [
            'orderInfo' => $order,
            'tradeInfo' => isset($tradeInfo) ? $tradeInfo : [],
        ];

        return $result;
    }

    public function delivery($params)
    {
        return;
    }

    public function getOrderValidity($companyId, $orderId)
    {
        return true;
    }
}
