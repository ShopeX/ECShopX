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

namespace HfPayBundle\Tests\Services;

use EspierBundle\Services\TestBaseService;
use HfPayBundle\Services\HfpayCashRecordService;

class HfpayCashRecordServiceTest extends TestBaseService
{
    /**
     * @var HfpayCashRecordService
     */
    protected $service;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->service = new HfpayCashRecordService();
    }

    public function testTotal()
    {
        $result = $this->service->total([]);

        $this->assertArrayHasKey('count', $result);
    }

    public function testLists()
    {
        $filter = [
            'company_id' => $this->getCompanyId(),
            'distributor_id' => 2,
        ];
        $this->service->lists($filter);
    }

    public function testWithdraw()
    {
        $filter = [
            'company_id' => $this->getCompanyId(),
            'distributor_id' => 4,
            'withdrawal_amount' => 100,
        ];

        // $result = $this->service->withdraw($filter);
    }
}
