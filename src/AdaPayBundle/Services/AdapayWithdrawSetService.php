<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
