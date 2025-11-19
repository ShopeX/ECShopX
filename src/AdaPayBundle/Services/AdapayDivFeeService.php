<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace AdaPayBundle\Services;

use AdaPayBundle\Entities\AdapayDivFee;

class AdapayDivFeeService
{
    private $cashRecordRepository;

    private $bankRepository;

    private $adapayEnterapplyRepository;

    public $divFeeRepository;

    public function __construct()
    {
        $this->divFeeRepository = app('registry')->getManager('default')->getRepository(AdapayDivFee::class);
    }


    /**
     * 获取提现记录
     */
    public function lists($filter, $page = 1, $pageSize = 20, $cols = '*', $orderBy = ['create_time' => 'desc'])
    {
        $lists = $this->divFeeRepository->lists($filter, $cols, $page, $pageSize, $orderBy);
        return $lists;
    }

    public function __call($name, $arguments)
    {
        return $this->divFeeRepository->$name(...$arguments);
    }
}
