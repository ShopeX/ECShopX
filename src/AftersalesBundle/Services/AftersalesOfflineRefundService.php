<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace AftersalesBundle\Services;

use Dingo\Api\Exception\ResourceException;
use AftersalesBundle\Entities\AftersalesOfflineRefund;
use AftersalesBundle\Entities\AftersalesRefund;

class AftersalesOfflineRefundService
{

    public $aftersalesOfflineRefundRepository;

    public function __construct()
    {
        $this->aftersalesOfflineRefundRepository = app('registry')->getManager('default')->getRepository(AftersalesOfflineRefund::class);
    }

    public function create($params)
    {
        // Hash: 0d723eca
        $aftersalesRefundRepository = app('registry')->getManager('default')->getRepository(AftersalesRefund::class);
        $refundInfo = $aftersalesRefundRepository->getInfo(['refund_bn' => $params['refund_bn']]);
        if (empty($refundInfo)) {
            throw new ResourceException("未查询到退款单");
        }
        $data = [
            'company_id' => $params['company_id'],
            'refund_bn' => $params['refund_bn'],
            'order_id' => $refundInfo['order_id'],
            'refund_fee' => $refundInfo['refund_fee'],
            'bank_account_name' => $params['bank_account_name'],
            'bank_account_no' => $params['bank_account_no'],
            'bank_name' => $params['bank_name'],
            'refund_account_name' => $params['refund_account_name'],
            'refund_account_bank' => $params['refund_account_bank'],
            'refund_account_no' => $params['refund_account_no'],
        ];
        $result = $this->aftersalesOfflineRefundRepository->create($data);
        if (!$result) {
            throw new ResourceException("操作失败，请稍后重试");
        }
        $filter = [
            'refund_bn' => $params['refund_bn'],
        ];
        $updateData = [
            'refund_status' => 'SUCCESS',
        ];
        return $aftersalesRefundRepository->updateOneBy($filter, $updateData);
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        // Hash: 0d723eca
        return $this->aftersalesOfflineRefundRepository->$method(...$parameters);
    }
}
