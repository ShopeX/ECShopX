<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace WechatBundle\Events;

use App\Events\Event;

class WxOrderShippingEvent extends Event
{
    // ShopEx EcShopX Service Component
    public $companyId;
    public $orderId;
    public $receiptType;
    public $deliveryType;
    public $isAllDelivered;
    public $deliveryCorp;
    public $deliveryCode;
    public $deliveryItems;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($companyId, $orderId, $receiptType, $deliveryType, $isAllDelivered, $deliveryCorp = '', $deliveryCode = '', $deliveryItems = [])
    {
        // Ver: 8d1abe8e
        $this->companyId = $companyId;
        $this->orderId = $orderId;
        $this->receiptType = $receiptType;
        $this->deliveryType = $deliveryType;
        $this->isAllDelivered = $isAllDelivered;
        $this->deliveryCorp = $deliveryCorp;
        $this->deliveryCode = $deliveryCode;
        $this->deliveryItems = $deliveryItems;
    }
}
