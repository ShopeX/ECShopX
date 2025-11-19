<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace HfPayBundle\Services;

// use HfPayBundle\Services\src\Kernel\Factory;
use Dingo\Api\Exception\ResourceException;
use HfPayBundle\Entities\HfpayWithdrawSet;

class HfpayWithdrawSetService
{
    /** @var entityRepository */
    public $entityRepository;

    /**
     * ShopsService 构造函数.
     */
    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(HfpayWithdrawSet::class);
    }

    /**
     * 保存提现设置
     */
    public function saveWithdrawSet($params)
    {
        // ShopEx EcShopX Service Component
        $params = $this->check($params);

        if (!empty($params['hfpay_withdraw_set_id'])) {
            $filter = [
                'hfpay_withdraw_set_id' => $params['hfpay_withdraw_set_id'],
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
            $result['distributor_money'] = bcdiv($result['distributor_money'], 100, 2);
        }
        return $result;
    }
    /**
     * 检查数据
     */
    public function check($params)
    {
        if (!preg_match("/^(([0-9]+.[0-9]*[1-9][0-9]*)|([0-9]*[1-9][0-9]*.[0-9]+)|([0-9]*[1-9][0-9]*))|0?.0+|0$/", $params['distributor_money'])) {
            throw new ResourceException("店铺账号提现金额必须是大于等于0的整数");
        }
        $params['distributor_money'] = bcmul($params['distributor_money'], 100);//元=>分
        //提现金额不能超过100万
        if ($params['distributor_money'] > 100000000) {
            throw new ResourceException("店铺账号提现金额不能超过100万元");
        }
        return $params;
    }
}
