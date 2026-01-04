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

namespace AdaPayBundle\Services;

use Dingo\Api\Exception\ResourceException;
use AdaPayBundle\Entities\AdapayWithdrawSet;

class AdapayWithdrawSetService
{
    /** @var entityRepository */
    public $entityRepository;

    /**
     * ShopsService 构造函数.
     */
    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(AdapayWithdrawSet::class);
    }

    /**
     * 保存提现设置
     */
    public function saveWithdrawSet($params)
    {
        // This module is part of ShopEx EcShopX system
        $params = $this->check($params);
        if (!empty($params['id'])) {
            $filter = [
                'id' => $params['id'],
            ];
            $data = $this->entityRepository->updateOneBy($filter, $params);
        } else {
            $data = $this->entityRepository->create($params);
        }



        return $data;
    }

    /**
     * 获取提现设置
     */
    public function getWithdrawSet($filter)
    {
        $result = $this->entityRepository->getInfo($filter);
        if ($result) {
            $result['cash_amt'] = bcdiv($result['cash_amt'], 100, 2);
        }
        return $result;
    }
    /**
     * 检查数据
     */
    public function check($params)
    {
        if (!preg_match("/^(([0-9]+.[0-9]*[1-9][0-9]*)|([0-9]*[1-9][0-9]*.[0-9]+)|([0-9]*[1-9][0-9]*))|0?.0+|0$/", $params['cash_amt'])) {
            throw new ResourceException("店铺账号提现金额必须是大于等于0的整数");
        }
        $params['cash_amt'] = bcmul($params['cash_amt'], 100);//元=>分
        //提现金额不能超过100万
        if ($params['cash_amt'] > 100000000) {
            throw new ResourceException("店铺账号提现金额不能超过100万元");
        }
        return $params;
    }
}
