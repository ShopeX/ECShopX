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

namespace OrdersBundle\Traits;

use OrdersBundle\Services\Orders\ExcardNormalOrderService;
use OrdersBundle\Services\Orders\GroupsServiceOrderService;
use OrdersBundle\Services\Orders\GroupsNormalOrderService;
use OrdersBundle\Services\OrderService;
use OrdersBundle\Services\Orders\ServiceOrderService;
use OrdersBundle\Services\Orders\BargainOrderService;
use OrdersBundle\Services\Orders\NormalOrderService;
use OrdersBundle\Services\Orders\DrugNormalOrderService;
use OrdersBundle\Services\Orders\SeckillNormalOrderService;
use OrdersBundle\Services\Orders\SeckillServiceOrderService;
use OrdersBundle\Services\Orders\ShopguideNormalOrderService;
use OrdersBundle\Services\Orders\BargainNormalOrderService;
use OrdersBundle\Services\Orders\PointsmallNormalOrderService;
use KaquanBundle\Services\VipGradeOrderService;
use Dingo\Api\Exception\ResourceException;
use OrdersBundle\Services\Orders\CommunityNormalOrderService;
use OrdersBundle\Services\Orders\ShopadminNormalOrderService;
use SupplierBundle\Services\SupplierOrderService;
use EmployeePurchaseBundle\Services\NormalOrderService as EmployeePurchaseNormalOrderService;

trait GetOrderServiceTrait
{
    public function getOrderService($orderType)
    {
        $orderType = strtolower($orderType);
        switch ($orderType) {
            case 'service':
                $orderService = new OrderService(new ServiceOrderService());
                break;
            case 'bargain':
//                $orderService = new OrderService(new BargainOrderService());
                $orderService = new OrderService(new BargainNormalOrderService());
                break;
            case 'normal_bargain':
                $orderService = new OrderService(new BargainNormalOrderService());
                break;
            case 'normal':
                $orderService = new OrderService(new NormalOrderService());
                break;
            case 'supplier_order':
                $orderService = new SupplierOrderService();
                break;
            case 'service_groups':
            case 'groups':
                $orderService = new OrderService(new GroupsServiceOrderService());
                break;
            case 'normal_groups':
                $orderService = new OrderService(new GroupsNormalOrderService());
                break;
            case 'membercard':
                $orderService = new VipGradeOrderService();
                break;
            case 'normal_seckill':
                $orderService = new OrderService(new SeckillNormalOrderService());
                break;
            case 'service_seckill':
                $orderService = new OrderService(new SeckillServiceOrderService());
                break;
            case 'normal_drug':
                $orderService = new OrderService(new DrugNormalOrderService());
                break;
            case 'normal_shopguide':  //导购下单---线下订单---导购代客下单
                $orderService = new OrderService(new ShopguideNormalOrderService());
                break;
            case 'normal_pointsmall':  // 积分商城
                $orderService = new OrderService(new PointsmallNormalOrderService());
                break;
            case 'normal_excard': // 兑换订单
                $orderService = new OrderService(new ExcardNormalOrderService());
                break;
            case 'normal_community':
                $orderService = new OrderService(new CommunityNormalOrderService());
                break;
            case 'normal_shopadmin':  //线下订单
                $orderService = new OrderService(new ShopadminNormalOrderService());
                break;
            case 'normal_employee_purchase':  //内购订单
                $orderService = new OrderService(new EmployeePurchaseNormalOrderService());
                break;
            default:
                throw new ResourceException("无此类型订单！");
        }

        return $orderService;
    }

    public function getOrderServiceByOrderInfo($order)
    {
        if (in_array($order['order_type'], ['normal', 'service']) && $order['order_class'] != $order['order_type'] && !in_array($order['order_class'], ['normal', 'service'])) {
            $orderType = $order['order_type'].'_'.$order['order_class'];
        } else {
            $orderType = $order['order_type'];
        }
        $orderService = $this->getOrderService($orderType);

        return $orderService;
    }
}
