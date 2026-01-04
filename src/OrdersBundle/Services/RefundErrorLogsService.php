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
