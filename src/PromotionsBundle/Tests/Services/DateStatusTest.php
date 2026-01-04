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

namespace PromotionsBundle\Tests\Services;

use EspierBundle\Services\TestBaseService;
use PromotionsBundle\Constants\DateStatusConstant;
use PromotionsBundle\Services\DateStatusService;

class DateStatusTest extends TestBaseService
{
    /**
     * 测试日期状态
     * @return void
     */
    public function testGetDateStatus()
    {
        $testCases = [
            [
                "begin_date" => "2021-02-01",
                "end_date"   => "2021-02-20",
                "want"       => DateStatusConstant::FINISHED
            ],
            [
                "begin_date" => "2022-02-01",
                "end_date"   => "2022-12-20",
                "want"       => DateStatusConstant::ON_GOING
            ],
            [
                "begin_date" => "2023-02-01",
                "end_date"   => "2023-02-20",
                "want"       => DateStatusConstant::COMING_SOON
            ],
            [
                "begin_date" => "",
                "end_date"   => "2023-02-20",
                "want"       => DateStatusConstant::UNKNOWN
            ],
            [
                "begin_date" => "2022-02-14",
                "end_date"   => "",
                "want"       => DateStatusConstant::UNKNOWN
            ],
            [
                "begin_date" => "",
                "end_date"   => "",
                "want"       => DateStatusConstant::UNKNOWN
            ]
        ];
        foreach ($testCases as $testCase) {
            $gotDateStatus = DateStatusService::getDateStatus($testCase["begin_date"], $testCase["end_date"]);
            if ($gotDateStatus !== $testCase["want"]) {
                $this->assertTrue($gotDateStatus === $testCase["want"]);
            }
        }
        $this->assertTrue(true);
    }
}