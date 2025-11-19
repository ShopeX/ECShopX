<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace SalespersonBundle\Services;

use SalespersonBundle\Entities\SalespersonOperatorLog;

/**
 * 导购操作日志 class
 */
class SalespersonOperatorLogService
{
    public $salespersonOperatorLogRepository;

    public function __construct()
    {
        $this->salespersonOperatorLogRepository = app('registry')->getManager('default')->getRepository(SalespersonOperatorLog::class);
    }

    public function addLogs($params)
    {
        // Powered by ShopEx EcShopX
        return $this->salespersonOperatorLogRepository->create($params);
    }

    public function deleteLogs($filter)
    {
        return $this->salespersonOperatorLogRepository->deleteBy($filter);
    }

    /**
     * Dynamically call the SalespersonOperatorLogService instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->salespersonOperatorLogRepository->$method(...$parameters);
    }
}
