<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Tests\Services\Cache;

use EspierBundle\Services\Cache\RedisCacheService;

class RedisCacheTest extends \EspierBundle\Services\TestBaseService
{
    /**
     * 测试获取缓存的方法
     */
    public function testGet()
    {
        $redis = (new RedisCacheService($this->getCompanyId(), "test"));
        $value = $redis->get(function () {
            return mt_rand(0, 99999);
        });
        $this->assertTrue(is_numeric($value));
    }
}
