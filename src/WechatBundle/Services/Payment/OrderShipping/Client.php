<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/*
 * This file is part of the overtrue/wechat.
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace WechatBundle\Services\Payment\OrderShipping;

use EasyWeChat\Kernel\BaseClient;

class Client extends BaseClient
{
    /**
     * Upload shipping info.
     *
     * @param array $params
     *
     * @return array|\EasyWeChat\Kernel\Support\Collection|object|\Psr\Http\Message\ResponseInterface|string
     *
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function uploadShippingInfo(array $params)
    {
        return $this->httpPostJson('wxa/sec/order/upload_shipping_info', $params);
    }

    /**
     * Get order.
     *
     * @param array $params
     *
     * @return array|\EasyWeChat\Kernel\Support\Collection|object|\Psr\Http\Message\ResponseInterface|string
     *
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getOrder(array $params)
    {
        return $this->httpPostJson('wxa/sec/order/get_order', $params);
    }

    /**
     * Notify confirm receive.
     *
     * @param array $params
     *
     * @return array|\EasyWeChat\Kernel\Support\Collection|object|\Psr\Http\Message\ResponseInterface|string
     *
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function notifyConfirmReceive(array $params)
    {
        return $this->httpPostJson('wxa/sec/order/notify_confirm_receive', $params);
    }

    /**
     * Get order list.
     *
     * @param array $query
     *
     * @return array|\EasyWeChat\Kernel\Support\Collection|object|\Psr\Http\Message\ResponseInterface|string
     *
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getOrderList(array $query)
    {
        return $this->httpGet('wxa/sec/order/get_order_list', $query);
    }
}
