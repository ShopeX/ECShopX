<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ThirdPartyBundle\Services;

use ThirdPartyBundle\Entities\SaasErpLog;

class SaasErpLogService
{
    public $entityRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(SaasErpLog::class);
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }

    public function saveLog($companyId, $method, $inputData = [], $id = 0)
    {
        $logParams = [
            'company_id' => $companyId,
            'api_type' => 'request',
            'worker' => $method,
            'status' => 'start',
        ];
        if ($inputData['result'] ?? null) {
            $logParams['result'] = $inputData['result'];
        }
        if ($inputData['params'] ?? null) {
            $logParams['params'] = $inputData['params'];
        }
        if ($inputData['status'] ?? null) {
            $logParams['status'] = $inputData['status'];
        }
        if ($inputData['runtime'] ?? null) {
            $logParams['runtime'] = $inputData['runtime'];
        }
        if ($id) {
            $filter['id'] = $id;
            $result = $this->entityRepository->updateOneBy($filter, $logParams);
        } else {
            $result = $this->entityRepository->create($logParams);
        }
        return $result;
    }
}
