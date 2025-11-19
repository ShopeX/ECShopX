<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Services;

use EspierBundle\Entities\ExportLog;

class ExportLogService
{
    /** @var entityRepository */
    public $entityRepository;

    /**
     * ShopsService 构造函数.
     */
    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(ExportLog::class);
    }

    public function scheduleDeleteHistoryFile()
    {
        $time = time() - 3600 * 3;
        $filter = [
            'finish_time|lte' => $time,
        ];
        return $this->entityRepository->deleteBy($filter);
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
        return $this->entityRepository->$method(...$parameters);
    }
}
