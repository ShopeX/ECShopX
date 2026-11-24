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

namespace WechatBundle\Services;

/**
 * 历史空壳：微信卡券事件处理未实现。
 * 保留类名避免自动加载断裂，调用即失败，防止误接静默失效。
 * 业务卡券请使用 KaquanBundle\Services\WechatCardService / UserDiscountService。
 */
class WechatCardService
{
    public function __construct()
    {
        throw new \LogicException('WechatBundle\\Services\\WechatCardService 已废弃且无实现，请使用 KaquanBundle 卡券服务');
    }

    public function userGetCard($eventData)
    {
        throw new \LogicException('WechatBundle\\Services\\WechatCardService::userGetCard 未实现');
    }

    public function userGiftingCard($eventData)
    {
        throw new \LogicException('WechatBundle\\Services\\WechatCardService::userGiftingCard 未实现');
    }

    public function userDelCard($eventData)
    {
        throw new \LogicException('WechatBundle\\Services\\WechatCardService::userDelCard 未实现');
    }

    public function userConsumeCard($eventData)
    {
        throw new \LogicException('WechatBundle\\Services\\WechatCardService::userConsumeCard 未实现');
    }

    public function userPayFromPayCell($eventData)
    {
        throw new \LogicException('WechatBundle\\Services\\WechatCardService::userPayFromPayCell 未实现');
    }
}
