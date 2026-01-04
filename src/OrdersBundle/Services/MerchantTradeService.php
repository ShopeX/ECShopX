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

namespace OrdersBundle\Services;

use OrdersBundle\Entities\MerchantPaymentTrade;
use OrdersBundle\Events\MerchantTradeFinishEvent;
use OrdersBundle\Traits\GetOrderIdTrait;

class MerchantTradeService
{
    use GetOrderIdTrait;

    public $entityRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(MerchantPaymentTrade::class);
    }

    /**
     * 创建交易单
     */
    public function create(array $data)
    {
        $data['merchant_trade_id'] = $this->genId($data['user_id']);
        $data['status'] = 'NOT_PAY';
        $data = $this->entityRepository->create($data);
        return $data;
    }

    /**
     * 更新交易单状态
     */
    public function updateStatus($filter, $result = array())
    {
        $data = $this->entityRepository->updateOneBy($filter, $result);
        if ($result['status'] == 'SUCCESS') {
            // 支付成功，触发事件
            $this->finishEvents($data);
        }

        return $data;
    }

    /**
     * 交易完成处理事件
     */
    public function finishEvents($eventsParams)
    {
        event(new MerchantTradeFinishEvent($eventsParams));
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }
}
