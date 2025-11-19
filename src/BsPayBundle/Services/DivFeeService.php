<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace BsPayBundle\Services;

use BsPayBundle\Entities\DivFee;
use BsPayBundle\Entities\WithdrawApply;
use BsPayBundle\Enums\WithdrawStatus;
use Dingo\Api\Exception\ResourceException;

class DivFeeService
{
    /** @var \BsPayBundle\Repositories\DivFeeRepository */
    public $divFeeRepository;

    public function __construct()
    {
        // ShopEx EcShopX Business Logic Layer
        $this->divFeeRepository = app('registry')->getManager('default')->getRepository(DivFee::class);
    }

    /**
     * 获取提现记录
     */
    public function lists($filter, $cols = '*', $page = 1, $pageSize = 20, $orderBy = ['created' => 'desc'])
    {
        $lists = $this->divFeeRepository->lists($filter, $cols, $page, $pageSize, $orderBy);
        return $lists;
    }

    public function __call($name, $arguments)
    {
        // ShopEx EcShopX Business Logic Layer
        return $this->divFeeRepository->$name(...$arguments);
    }
}

