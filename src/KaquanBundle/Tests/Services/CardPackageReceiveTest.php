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
use KaquanBundle\Entities\VipGrade;
use KaquanBundle\Entities\VipGradeRelUser;
use KaquanBundle\Services\PackageQueryService;
use KaquanBundle\Services\PackageReceivesService;
use KaquanBundle\Services\PackageSetService;
use KaquanBundle\Services\UserDiscountService;

/**
 * 测试卡券包 对应触发点设置 & 发送卡券 测试
 *
 *
 */
class CardPackageReceiveTest extends TestBaseService
{
    private $companyId = 1;
    private $userId = 20696;
    private $packageBaseTest;

    public function __construct()
    {
        $this->packageBaseTest = new CardPackageBaseTest();
        parent::__construct();
    }

    /**
     * 设置触发点 测试
     */
    public function testTriggerSet()
    {
        // Ref: 1996368445
        $cardPackageList = $this->packageBaseTest->testCardPackageList();
        if (empty($cardPackageList['list'])) {
            $this->assertEmpty($cardPackageList['list']);
            return true;
        }

        $totalCount = $cardPackageList['total_count'];

        $randNum = mt_rand(1, $totalCount);
        $randKey = array_rand($cardPackageList['list'], $randNum);

        $packageIdSet = [];
        if (is_array($randKey)) {
            foreach ($randKey as $item) {
                $packageIdSet[] = $cardPackageList['list'][$item]['package_id'];
            }
        } else {
            $packageIdSet[] = $cardPackageList['list'][$randKey]['package_id'];
        }

        $userSet = $this->getVipGrade();
        $firstUserSet = current($userSet);

        $result = (new PackageSetService())->setTriggerByPackageIdSet($this->companyId, $packageIdSet, $firstUserSet['vip_grade_id'], 'vip_grade');

        $this->assertTrue($result);

        return true;
    }

    /**
     * 测试得到触发点卡券信息
     */
    public function testGetTriggerCardInfo()
    {
        $userSet = $this->getVipGrade();
        $firstUserSet = current($userSet);

        $list = (new PackageQueryService())->getCardListByBindType($this->companyId, $firstUserSet['vip_grade_id'], 'vip_grade');

        $this->assertTrue(is_array($list));
    }

    /**
     * 测试触发发券
     */
    public function testTrigger()
    {
        $userSet = $this->getVipGrade();
        $firstUserSet = current($userSet);

        return (new PackageSetService())->triggerPackage($this->companyId, $this->userId, $firstUserSet['vip_grade_id'], 'vip_grade', true);
    }

    /**
     * 测试展示接收的卡券包
     */
    public function testShowReceiveCardPackage()
    {
        $receiveList = (new PackageReceivesService())->showCardPackage($this->companyId, $this->userId, 'vip_grade');
        echo '未读的卡券包信息' . PHP_EOL;
        print_r($receiveList);
        $this->assertTrue(is_array($receiveList));
    }

    public function testCardPackageQuantity()
    {
        // 库存测试
        $cardList = [
            [
                'card_id' => 856,
                'give_num' => 100,
                'end_date' => 9999999999,
                'quantity' => 21,
                'get_limit' => 1,
            ]
        ];

        (new UserDiscountService())->checkCardList(1, 123, $cardList, '');
    }

    /**
     * 得到用户付费会员列表
     *
     * @return mixed
     */
    private function getMemberList()
    {
        $entityRepository = app('registry')->getManager('default')->getRepository(VipGradeRelUser::class);
        $memberFilter = [
            'user_id' => $this->userId,
            'company_id' => $this->companyId,
            'end_date|gt' => time(),
        ];
        return $entityRepository->lists($memberFilter);
    }

    /**
     * 得到用户设置的会员列表
     *
     * @return mixed
     */
    private function getVipGrade()
    {
        // 设置的vip 等级
        $vipGradeRepository = app('registry')->getManager('default')->getRepository(VipGrade::class);
        return $vipGradeRepository->lists(['company_id' => $this->companyId]);
    }
}
