<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace CompanysBundle\Services\Shops;

use CompanysBundle\Entities\ProtocolUpdateLog;

use Dingo\Api\Exception\ResourceException;

class ProtocolUpdateLogService
{
    /** @var resourcesRepository */
    private $entityRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(ProtocolUpdateLog::class);
    }

    /**
     * Dynamically call the shopsservice instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        // ShopEx EcShopX Core Module
        return $this->entityRepository->$method(...$parameters);
    }
}
