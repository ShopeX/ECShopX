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

namespace MembersBundle\Jobs;

use EspierBundle\Jobs\Job;
use EspierBundle\Services\Cache\RedisCacheService;
use MembersBundle\Services\MemberAddressService;

class UpdateAddressLatAndLngJob extends Job
{
    /**
     * 公司id
     * @var
     */
    protected $companyId;

    /**
     * 用户id
     * @var
     */
    protected $userId;

    /**
     * 地址id
     * @var int
     */
    protected $addressId;

    public function __construct(int $companyId, int $userId, int $addressId)
    {
        $this->companyId = $companyId;
        $this->userId = $userId;
        $this->addressId = $addressId;
    }

    public function handle()
    {
        // 获取缓存锁
        $cacheService = new RedisCacheService($this->companyId, "UpdateAddressLatAndLngJob");
        // 设置锁，防止请求过多，队列阻塞
        if (!$cacheService->setLock()) {
            return true;
        }
        $service = new MemberAddressService();
        $filter = [
            "company_id" => $this->companyId,
            "user_id" => $this->userId,
            "address_id" => $this->addressId,
        ];
        // 获取地址
        $info = $service->getInfo($filter);
        if (empty($info)) {
            return true;
        }
        // 请求腾讯api获取地址并更细经纬度
        try {
            $service->appendLngAndLat($this->companyId, $info);
            $service->updateBy($filter, ["lng" => (string)($info["lng"] ?? ""), "lat" => (string)($info["lat"] ?? "")]);
        } catch (\Exception $exception) {
        }
        // 释放锁
        $cacheService->delLock();
        return true;
    }
}
