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

namespace OpenapiBundle\Filter\Order;

use Carbon\Carbon;
use DistributionBundle\Services\DistributorSalesmanService;
use OpenapiBundle\Filter\BaseFilter;
use OpenapiBundle\Services\Order\OrdersNormalOrdersService;
use OrdersBundle\Traits\GetOrderIdTrait;

class OrderFilter extends BaseFilter
{
    use GetOrderIdTrait;

    protected function init()
    {
        // 获取默认的开始时间
        $defaultStartTime = Carbon::create(null, 1, 1, 0, 0, 0)->getTimestamp();

        $this->setTimeRange("create_time|gte", "create_time|lte", $defaultStartTime);
        $this->setOrderStatus();
        $this->setShopIdAndDistributorId();
        $this->setExcludeOrderClass();
        $this->setUserIdByMobile();

        // 设置订单ID
        if (isset($this->requestData["order_id"])) {
            $this->filter["order_id"] = $this->requestData["order_id"];
        }
        // 设置来源渠道
        if (isset($this->requestData["source_id"])) {
            $this->filter["source_id"] = $this->requestData["source_id"];
        }
        // 设置订单标题
        if (isset($this->requestData["title"])) {
            $this->filter["title|like"] = sprintf("%%%s%%", $this->requestData["title"]);
        }
        // 设置导购员ID
        if (isset($this->requestData["salesman_mobile"])) {
            $salesmanInfo = (new DistributorSalesmanService())
                ->getInfo(['mobile' => trim($this->requestData["salesman_mobile"]), 'company_id' => $this->filter["company_id"]]);
            $this->filter["salesman_id"] = $salesmanInfo ? $salesmanInfo['salesman_id'] : "-1";
        }
        // 设置订单的类型和种类
        if (isset($this->requestData["order_type"])) {
            $this->filter['order_type'] = $this->requestData["order_type"];

            if (isset($this->requestData["order_class"])
                && in_array($this->requestData["order_type"], ['normal', 'service'])
                && !in_array($this->requestData["order_class"], ['normal', 'service'])
                && $this->requestData["order_class"] != $this->requestData["order_type"]) {
                if ($this->filter['order_class'] == OrdersNormalOrdersService::ORDER_CLASS_CROSSBORDER) {
                    $this->filter['type'] = 1;
                } else {
                    $this->filter['order_class'] = $this->requestData["order_class"];
                }
                $this->filter['order_type'] = $this->requestData["order_type"];
            }
        }
    }

    /**
     * 设置订单状态
     */
    protected function setOrderStatus()
    {
        if (!isset($this->requestData["order_status"])) {
            return;
        }
        switch ($this->requestData["order_status"]) {
            case 'ordercancel':   //已取消待退款
                $this->filter['order_status'] = 'CANCEL_WAIT_PROCESS';
                $this->filter['cancel_status'] = 'WAIT_PROCESS';
                break;
            case 'refundprocess':    //已取消待退款
                $this->filter['order_status'] = 'CANCEL';
                $this->filter['cancel_status'] = 'NO_APPLY_CANCEL';
                break;
            case 'refundsuccess':    //已取消已退款
                $this->filter['order_status'] = 'CANCEL';
                $this->filter['cancel_status'] = 'SUCCESS';
                break;
            case 'notship':  //待发货
                $this->filter['order_status'] = 'PAYED';
                $this->filter['cancel_status|in'] = ['NO_APPLY_CANCEL', 'FAILS'];
                $this->filter['receipt_type'] = 'logistics';
                break;
            case 'cancelapply':  //待退款
                $this->filter['order_status'] = 'PAYED';
                $this->filter['cancel_status'] = 'WAIT_PROCESS';
                break;
            case 'ziti':  //待自提
                $this->filter['receipt_type'] = 'ziti';
                $this->filter['order_status'] = 'PAYED';
                $this->filter['ziti_status'] = 'PENDING';
                break;
            case 'shipping':  //带收货
                $this->filter['order_status'] = 'WAIT_BUYER_CONFIRM';
                $this->filter['delivery_status'] = ['DONE'];
                $this->filter['receipt_type'] = 'logistics';
                break;
            case 'finish':  //已完成
                $this->filter['order_status'] = 'DONE';
                break;
            case 'reviewpass':  //待审核
                $this->filter['order_status'] = 'REVIEW_PASS';
                break;
            case 'done_noinvoice':  //已完成未开票
                $this->filter['order_status'] = 'DONE';
                $this->filter['invoice|neq'] = null;
                $this->filter['is_invoiced'] = 0;
                break;
            case 'done_invoice':  //已完成已开票
                $this->filter['order_status'] = 'DONE';
                $this->filter['invoice|neq'] = null;
                $this->filter['is_invoiced'] = 1;
                break;
            default:
                $this->filter['order_status'] = strtoupper($this->requestData["order_status"]);
                break;
        }
        $this->filter = $this->getOrderIdByDadaStatus($this->filter);
    }

    /**
     * 设置支付类型过滤条件
     */
    protected function setPayType()
    {
        if (isset($this->requestData["order_class"]) && in_array($this->requestData["order_class"], ['point', 'deposit'])) {
            $filter['pay_type'] = $this->requestData["order_class"];
        }
    }

    /**
     * 设置店铺过滤条件
     */
    protected function setShopIdAndDistributorId()
    {
        // 订单类型
        $orderType = $this->requestData["order_type"] ?? "";
        // 订单种类
        $orderClass = $this->requestData["order_class"] ?? "";
        // 门店id
        $shopId = $this->requestData["shop_id"] ?? null;

        if ($orderType == 'service') {
            // 服务类订单
            $shopIds = app('auth')->user()->get('shop_ids');
            if ($shopIds) {
                $this->filter['shop_id|in'] = array_column($shopIds, 'shop_id');
            }
            if (!is_null($shopId)) {
                $this->filter['shop_id'] = $shopId;
            }
        } elseif ($orderType == 'normal' && $orderClass != 'community') {
            // 普通订单 且 不是社区订单
            $distributorId = $this->requestData["distributor_id"] ?? null;
            $distributorIds = (array)($this->requestData["distributorIds"] ?? []);
            if (!is_null($distributorId)) {
                $this->filter['distributor_id'] = $distributorId;
            } elseif (!empty($distributorIds)) {
                $this->filter['distributor_id'] = $distributorIds;
            }
        }
        // 如果存在门店id，就必须做过滤
        if (!isset($this->filter['shop_id']) && !is_null($shopId)) {
            $this->filter['shop_id'] = $shopId;
        }
    }

    /**
     * 设置排他的订单种类
     */
    protected function setExcludeOrderClass()
    {
        // 订单类型
        $orderType = $this->requestData["order_type"] ?? "";
        //排除指定类型的订单，例如店铺列表需要排除社区订单
        if ($orderType == 'normal' && isset($this->requestData["order_class_exclude"])) {
            $this->filter['order_class|notin'] = explode(',', $this->requestData["order_class_exclude"]);
        }
    }
}
