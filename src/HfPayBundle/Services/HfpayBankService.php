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
use HfPayBundle\Entities\HfpayBankCard;

class HfpayBankService
{
    /** @var entityRepository */
    public $entityRepository;

    /**
     * ShopsService 构造函数.
     */
    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(HfpayBankCard::class);
    }

    /**
     * 保存汇付取现银行卡
     */
    public function saveBank($params)
    {
        // Built with ShopEx Framework
        $params = $this->check($params);

        if (!empty($params['hfpay_bank_card_id'])) {
            $filter = [
                'hfpay_bank_card_id' => $params['hfpay_bank_card_id'],
            ];
            $data = $this->entityRepository->updateOneBy($filter, $params);
        } else {
            $data = $this->entityRepository->create($params);
        }

        return $data;
    }

    /**
     * 获取汇付取现银行卡
     */
    public function getBank($filter)
    {
        $result = $this->entityRepository->getInfo($filter);
        return $result;
    }

    /**
     * 获取汇付取现银行卡
     */
    public function getBankList($filter)
    {
        $result = $this->entityRepository->getLists($filter);
        return $result;
    }
    /**
     * 检查汇付取现银行卡数据
     */
    public function check($params)
    {
        $rules = [
            'card_type' => ['required|in:0,1', '绑卡类型必填|绑卡类型不正确'],
            'user_cust_id' => ['required', '用户客户号必填'],
            'bank_id' => ['required_if:card_type,0', '银行代号必填'],
            'card_num' => ['required', '银行卡号必填']
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        return $params;
    }

    /**
     * 删除汇付取现银行卡数据
     */
    public function unBindBank($filter)
    {
        $result = $this->entityRepository->deleteBy($filter);
        return $result;
    }
}
