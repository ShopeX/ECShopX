<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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