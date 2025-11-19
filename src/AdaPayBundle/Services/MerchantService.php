<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace AdaPayBundle\Services;

use AdaPayBundle\Entities\AdapayMerchantEntry;

class MerchantService
{
    public $adaPayMerchantEntryRepository;

    public function __construct()
    {
        $this->adaPayMerchantEntryRepository = app('registry')->getManager('default')->getRepository(AdapayMerchantEntry::class);
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->adaPayMerchantEntryRepository->$method(...$parameters);
    }
}
