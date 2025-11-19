<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OrdersBundle\Services;

use OrdersBundle\Entities\RefundErrorLogs;
use OrdersBundle\Traits\GetOrderServiceTrait;
use AftersalesBundle\Services\AftersalesRefundService;

class RefundErrorLogsService
{
    use GetOrderServiceTrait;

    /** @var \OrdersBundle\Repositories\RefundErrorLogsRepository  */
    private $refundErrorLogsRepository;

    public function __construct()
    {
        $this->refundErrorLogsRepository = app('registry')->getManager('default')->getRepository(RefundErrorLogs::class);
    }

    public function getList(array $filter, $page = 1, $pageSize = 100, $orderBy = ['id' => 'DESC'])
    {
        return $this->refundErrorLogsRepository->lists($filter, $page, $pageSize, $orderBy);
    }

    public function create($data)
    {
        return $this->refundErrorLogsRepository->create($data);
    }

    public function errorLogsNum($filter)
    {
        $count = $this->refundErrorLogsRepository->count($filter);
        return intval($count);
    }

    //重新提交失败的退款
    public function resubmit($id)
    {
        $refundErrorLogs = $this->refundErrorLogsRepository->getInfoById($id);
        $data = json_decode($refundErrorLogs['data_json'], true);
        $aftersalesRefundService = new AftersalesRefundService();
        $refund_filter = [
            'refund_bn' => $data['refund_bn'],
            'company_id' => $data['company_id']
        ];
        $aftersalesRefundService->doRefund($refund_filter, true);

        $res = $this->refundErrorLogsRepository->updateOneBy(['id' => $refundErrorLogs['id']], ['is_resubmit' => true]);
        return $res;
    }
}
