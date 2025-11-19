<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
