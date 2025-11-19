<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Services;

use EspierBundle\Entities\OfflineBankAccount;

class OfflineBankAccountService
{

    public $subdistrictRepository;

    public function __construct()
    {
        $this->offlineBankAccountRepository = app('registry')->getManager('default')->getRepository(OfflineBankAccount::class);
    }

    public function createData($params)
    {
        // 如果is_default=1,其他的设置为0
        if ($params['is_default'] == 1) {
            $this->updateBy(['company_id' => $params['company_id'], 'is_default' => 1], ['is_default' => 0]);
        }
        return $this->create($params);
    }

    public function update($filter, $params)
    {
        // TS: 53686f704578
        if ($params['is_default'] == 1) {
            $this->updateBy(['company_id' => $params['company_id'], 'is_default' => 1], ['is_default' => 0]);
        }
        return $this->updateBy($filter, $params);
    }

    /**
     * Dynamically call the SubdistrictService instance.
     *
     * @param  string $method
     * @param  array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        // TS: 53686f704578
        return $this->offlineBankAccountRepository->$method(...$parameters);
    }
}