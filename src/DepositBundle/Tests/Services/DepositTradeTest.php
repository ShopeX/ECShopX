<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace DepositBundle\Tests\Services;

use DepositBundle\Entities\UserDeposit;
use DepositBundle\Services\DepositTrade;

class DepositTradeTest extends \EspierBundle\Services\TestBaseService
{
    protected $service;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->service = new DepositTrade();
    }

    public function testUserDeposit()
    {
        $companyId = 2;
        $userId = 3;
        $this->service->addUserDepositTotal($companyId, $userId, 100);
        $money = $this->service->getUserDepositTotal($companyId, $userId);
        var_dump($money);
    }

    public function testUserDepositTrans()
    {
        $redis = app('redis')->connection('deposit');
        $keyList = $redis->keys('userDepositTotal_*');

        $addData = [];
        $companyIdList = [];

        foreach ($keyList as $key) {
            $companyId = str_replace('userDepositTotal_', '', $key);
            echo '读取数据，company_id：' . $companyId . PHP_EOL;
            $hashData = $redis->hgetall($key);

            foreach ($hashData as $userId => $datum) {
                $tempKey = $companyId . "_" . $userId;
                $addData[$tempKey] = [
                    'company_id' => $companyId,
                    'user_id' => $userId,
                    'money' => $datum,
                ];
                $companyIdList[] = $companyId;
            }
        }

        // 判断数据库是否已存在数据 [use DepositBundle\Entities\UserDeposit;]!!!
        $userDeposit = app('registry')->getManager('default')->getRepository(UserDeposit::class);
        $where = [
            'company_id' => $companyIdList
        ];
        $dbList = $userDeposit->getUserDepositListByCompanyList($where);

        foreach ($dbList as $item) {
            $existKey = $item['company_id'] . "_" . $item['user_id'];
            unset($addData[$existKey]);
        }

        $addData = array_values($addData);
        echo '待添加数据总数量' . count($addData) . PHP_EOL;

        if (!empty($addData)) {
            $chunkArr = array_chunk($addData, 500);
            foreach ($chunkArr as $value) {
                $userDeposit->insertUserDeposit($value);
                echo '已添加数据数量'. count($value) . PHP_EOL;
            }
        }
    }
}
