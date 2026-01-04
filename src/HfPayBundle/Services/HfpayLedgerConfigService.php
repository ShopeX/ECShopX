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

namespace HfPayBundle\Services;

// use HfPayBundle\Services\src\Kernel\Factory;
use Dingo\Api\Exception\ResourceException;
use HfPayBundle\Entities\HfpayLedgerConfig;

class HfpayLedgerConfigService
{
    /** @var entityRepository */
    public $entityRepository;

    /**
     * ShopsService 构造函数.
     */
    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(HfpayLedgerConfig::class);
    }

    /**
     * 保存分账配置
     */
    public function saveConfig($params)
    {
        $params = $this->check($params);

        if (!empty($params['hfpay_ledger_config_id'])) {
            $filter = [
                'hfpay_ledger_config_id' => $params['hfpay_ledger_config_id'],
            ];
            $data = $this->entityRepository->updateOneBy($filter, $params);
        } else {
            $data = $this->entityRepository->create($params);
        }

        return $data;
    }

    /**
     * 获取分账配置
     */
    public function getLedgerConfig($filter)
    {
        $result = $this->entityRepository->getInfo($filter);
        if ($result) {
            $result['rate'] = bcdiv($result['rate'], 100, 2);
        }
        return $result;
    }
    /**
     * 检查配置数据
     */
    public function check($params)
    {
        if (!in_array($params['business_type'], ['1','2'])) {
            throw new ResourceException("不支持的业务模式");
        }
        $params['rate'] = bcmul($params['rate'], 100);
        if ($params['rate'] > 3000) {
            throw new ResourceException("服务费率不得超过30");
        }
        if ($params['business_type'] == '2') {
            if (empty($params['agent_number'])) {
                throw new ResourceException("代理商商户号不能为空");
            }
            if (empty($params['provider_number'])) {
                throw new ResourceException("服务商渠道号不能为空");
            }
            if (empty($params['app_id'])) {
                throw new ResourceException("小程序appid不能为空");
            }
        }

        return $params;
    }
}
