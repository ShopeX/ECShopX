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

namespace AdaPayBundle\Tests\Services;

use AdaPayBundle\Services\AdapayLogService;

class OperationLogTest extends \EspierBundle\Services\TestBaseService
{
    private $companyId = 43;
    private $operatorId = 1;

    /**
     * 测试记录日志
     *
     * @return mixed
     */
    public function testLogRecord()
    {
        $params = [
            'company_id' => $this->companyId,
            'operator_id' => $this->operatorId
        ];
        $action = 'merchant_entry/create';
        $sourceType = 'merchant';
        $result = (new AdapayLogService())->logRecord($params, 12, $action, $sourceType);
        $this->assertTrue(is_array($result));
        return $result;
    }

    /**
     * 测试日志列表
     *
     * @return array
     */
    public function testLogList()
    {
        $params = [
            'page' => 1,
            'page_size' => 10,
            'company_id' => $this->companyId,
            'log_type' => 'merchant',
            'operator_id' => $this->operatorId
        ];

        $list = (new AdapayLogService())->logList($params);
        $this->assertArrayHasKey('total_count', $list);
        return $list;
    }
}
