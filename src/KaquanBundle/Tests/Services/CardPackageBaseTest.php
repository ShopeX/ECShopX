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

namespace KaquanBundle\Tests\Services;

use EspierBundle\Services\TestBaseService;
use KaquanBundle\Services\DiscountCardService;
use KaquanBundle\Services\KaquanService;
use KaquanBundle\Services\PackageEditService;
use KaquanBundle\Services\PackageQueryService;

/**
 * 测试卡券包基础的增删改查
 * 主要测试卡券包增删改查能力
 *
 */
class CardPackageBaseTest extends TestBaseService
{
    public const DEFAULT_PAGE_COUNT = 10;
    public const DEFAULT_MAX_LIMIT_COUNT = 10;


    private $companyId = 1;

    /**
     * 测试卡券包列表
     *
     * @return array
     */
    public function testCardPackageList()
    {
        $list = (new PackageQueryService())->getList($this->companyId, 1, self::DEFAULT_PAGE_COUNT);
        $this->assertTrue(is_array($list));

        echo '卡券包列表' . PHP_EOL;
        print_r($list);
        return $list;
    }

    /**
     * 测试卡券包详情
     *
     * @depends testCardPackageList
     */
    public function testDetails($cardPackageList)
    {
        $randItemKey = array_rand($cardPackageList['list']);
        $randItem = $cardPackageList['list'][$randItemKey];

        $details = (new PackageQueryService())->getDetails($this->companyId, $randItem['package_id']);
        $this->assertTrue(!empty($details));

        echo '卡券包详情' . PHP_EOL;
        print_r($details);
        return $details;
    }

    /**
     * 测试卡券包创建
     *
     * @throws \Exception
     */
    public function testCardPackageCreate()
    {
        $packageContentNum = rand(0, 20);
        $cards = $this->getRandCard($packageContentNum);

        $packageContent = [];
        foreach ($cards as $card) {
            $packageContent[] = [
                'card_id' => $card['card_id'],
                'give_num' => rand(1, $card['quantity'])
            ];
        }

        $mockInputData = [
            'title' => date('H:i:s'),
            'limit_count' => mt_rand(1, self::DEFAULT_MAX_LIMIT_COUNT),
            'package_describe' => '',
            'package_content' => $packageContent
        ];
        $result = (new PackageEditService())->createPackage($this->companyId, $mockInputData);

        $this->assertTrue($result);
    }

    /**
     * 测试卡券包删除
     *
     * @depends testCardPackageList
     */
    public function testCardPackageDelete($cardPackageList)
    {
        if (!$cardPackageList['total_count']) {
            $this->assertEmpty($cardPackageList['list']);
            return true;
        }

        $packageKey = array_rand($cardPackageList['list']);
        $currentTotal = $cardPackageList['total_count'];

        $packageId = $cardPackageList['list'][$packageKey]['package_id'];

        $result = (new PackageEditService())->deletePackage($this->companyId, $packageId);

        $this->assertTrue($result);

        $list = $this->testCardPackageList();
        echo '删除后数量变化：' . PHP_EOL;
        echo '删除前卡券包列表数：' . $currentTotal . ' 删除后卡券包列表数：' . $list['total_count'] . PHP_EOL;
        $this->assertTrue($list['total_count'] < $currentTotal);
    }

    /**
     * 测试卡券包编辑
     *
     * @depends testCardPackageList
     */
    public function testCardPackageEdit($cardPackageList)
    {
        if (empty($cardPackageList['list'])) {
            $this->assertEmpty($cardPackageList['list']);
        }

        $key = array_rand($cardPackageList['list']);

        $inputData = [
            'title' => 'e' . date('H:i:s'),
            'package_id' => $cardPackageList['list'][$key]['package_id'],
        ];
        $result = (new PackageEditService())->editPackage($this->companyId, $inputData);
        $this->assertTrue($result);
    }


    private function getCardInfo(int $page, int $count)
    {
        $offset = ($page - 1) * $count;
        $discountCardService = new KaquanService(new DiscountCardService());
        $filter['company_id'] = $this->companyId;
        return $discountCardService->getKaquanList($offset, $count, $filter);
    }

    /**
     * 得到随机卡券
     * @param int $num
     * @return mixed
     */
    private function getRandCard(int $num = 1)
    {
        $initPage = 1;
        $count = $num > self::DEFAULT_PAGE_COUNT ? $num : self::DEFAULT_PAGE_COUNT;

        $cardInfo = $this->getCardInfo($initPage, $count);
        $totalPage = ceil($cardInfo['total_count'] / $count);
        $randPage = mt_rand(1, $totalPage);
        $randList = $this->getCardInfo($randPage, $count);
        $key = array_rand($randList['list'], $num);
        if (is_numeric($key)) {
            return [$randList['list'][$key]];
        } else {
            $result = [];
            foreach ($key as $item) {
                $result[] = $randList['list'][$item];
            }
            return $result;
        }
    }
}
