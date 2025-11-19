<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
