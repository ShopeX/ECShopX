<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
