<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace AftersalesBundle\Jobs;

use EspierBundle\Jobs\Job;

use OrdersBundle\Entities\NormalOrders;
use OrdersBundle\Entities\NormalOrdersItems;
use KaquanBundle\Services\UserDiscountService;
use AftersalesBundle\Services\AftersalesService;
use OrdersBundle\Services\Orders\NormalOrderService;

// 检查订单所有售后都完成job
class OrderRefundCompleteJob extends Job
{
    public $companyId = '';
    public $orderId = '';
    /**
     * 创建一个新的任务实例。
     *
     * @return void
     */
    public function __construct($companyId, $orderId)
    {
        $this->companyId = $companyId;
        $this->orderId = $orderId;
    }

    /**
     * 运行任务。
     *
     * @param  Mailer  $mailer
     * @return void
     */
    public function handle()
    {
        // ShopEx EcShopX Core Module
        $normalOrdersRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
        $normalOrdersItemsRepository = app('registry')->getManager('default')->getRepository(NormalOrdersItems::class);
        $orderId = $this->orderId;
        $companyId = $this->companyId;
        app('log')->debug('OrderRefundCompleteJob=>'.$orderId. '判断全部售后完成事件开始');
        $orderInfo = $normalOrdersRepository->getInfo(['order_id' => $orderId, 'company_id' => $companyId]);
        if (!$orderInfo) {
            return false;
        }
        $orderItems = $normalOrdersItemsRepository->get($companyId, $orderId);
        $can_apply_aftersales = 0;
        $total_num = 0;
        $delivery_num = 0;
        if (($orderInfo['order_status'] == 'WAIT_BUYER_CONFIRM') || $orderInfo['order_status'] == 'DONE') {
            $aftersalesService = new AftersalesService();
            foreach ($orderItems as &$v) {
                if ($v['order_item_type'] == 'gift') {
                    continue;
                }
                $applied_num = $aftersalesService->getAppliedNum($v['company_id'], $v['order_id'], $v['id']); // 已申请数量
                $v['left_aftersales_num'] = $v['delivery_item_num'] - $applied_num; // 剩余申请数量
                $can_apply_aftersales += $v['left_aftersales_num'];
                $total_num += $v['num'];
                $delivery_num += $v['delivery_item_num'];
            }
            if ($can_apply_aftersales != 0 || $delivery_num != $total_num) {
                return true;
            }

            // @todo 临时增加全部售后完自动确认收货
            if ($orderInfo['order_status'] != 'DONE') {
                $filter = [
                    'company_id' => $orderInfo['company_id'],
                    'order_id' => $orderInfo['order_id'],
                    'user_id' => $orderInfo['user_id'],
                ];
                $normalOrderService = new NormalOrderService();
                $normalOrderService->confirmReceipt($filter);
            }
            if ($orderInfo['discount_info']) {
                $discountInfo = $orderInfo['discount_info'];
                if (!is_array($orderInfo['discount_info'])) {
                    $discountInfo = json_decode($orderInfo['discount_info'], true);
                }
                $userDiscountService = new UserDiscountService();
                foreach ($discountInfo as $value) {
                    // if ($value && isset($value['type']) && $value['type'] == 'gift_discount') {
                    //     continue;
                    // }
                    if ($value && isset($value['coupon_code'])) {
                        $userDiscountService->callbackUserCard($orderInfo['company_id'], $value['coupon_code'], $orderInfo['user_id']);
                    }
                }
            }
        }
        return true;
    }
}
