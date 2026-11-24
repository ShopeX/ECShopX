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

namespace AftersalesBundle\Services;

use Dingo\Api\Exception\ResourceException;
use AftersalesBundle\Entities\AftersalesOfflineRefund;
use AftersalesBundle\Entities\AftersalesRefund;
use AftersalesBundle\Entities\Aftersales;
use AftersalesBundle\Entities\AftersalesDetail;
use PointBundle\Services\PointMemberService;
use ThirdPartyBundle\Events\TradeRefundFinishEvent;

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
        $refundInfo = $aftersalesRefundRepository->getInfo([
            'company_id' => $params['company_id'],
            'refund_bn' => $params['refund_bn'],
        ]);
        if (empty($refundInfo)) {
            throw new ResourceException("未查询到退款单");
        }
        if ((string) ($refundInfo['refund_status'] ?? '') === 'SUCCESS') {
            throw new ResourceException("退款单已处理成功，请勿重复操作");
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

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        $latestRefund = null;
        try {
            // 条件更新作幂等守卫：仅非 SUCCESS → SUCCESS
            if ($aftersalesRefundRepository->claimSuccess($params['company_id'], $params['refund_bn']) < 1) {
                throw new ResourceException("退款单已处理成功，请勿重复操作");
            }

            $result = $this->aftersalesOfflineRefundRepository->create($data);
            if (!$result) {
                throw new ResourceException("操作失败，请稍后重试");
            }

            $latestRefund = $aftersalesRefundRepository->getInfo([
                'company_id' => $params['company_id'],
                'refund_bn' => $params['refund_bn'],
            ]);
            if (empty($latestRefund)) {
                throw new ResourceException("未查询到退款单");
            }

            // 更新售后单状态为已完成
            if (!empty($refundInfo['aftersales_bn'])) {
                $aftersalesRepository = app('registry')->getManager('default')->getRepository(Aftersales::class);
                $aftersalesDetailRepository = app('registry')->getManager('default')->getRepository(AftersalesDetail::class);

                $aftersales_filter = [
                    'aftersales_bn' => $refundInfo['aftersales_bn'],
                    'company_id' => $params['company_id'],
                ];
                $aftersales_update = [
                    'aftersales_status' => 2, // 已处理。已完成
                    'progress' => 4, // 已处理
                ];

                $aftersalesRepository->update($aftersales_filter, $aftersales_update);
                $aftersalesDetailRepository->updateBy($aftersales_filter, $aftersales_update);
            }

            // 积分返还与状态抢占同事务：失败则回滚，可重试且不会永久少退
            if ((string) ($latestRefund['pay_type'] ?? '') != 'point' && (int) ($latestRefund['refund_point'] ?? 0) > 0) {
                $pointMemberService = new PointMemberService();
                $otherParams = [
                    'point_type' => 'points_refund',
                    'refund_bn' => (string) ($latestRefund['refund_bn'] ?? ''),
                    'aftersales_bn' => (string) ($latestRefund['aftersales_bn'] ?? ''),
                ];
                $pointMemberService->addPoint(
                    (int) ($latestRefund['user_id'] ?? 0),
                    (int) ($latestRefund['company_id'] ?? 0),
                    (int) ($latestRefund['refund_point'] ?? 0),
                    10,
                    true,
                    '退款单号:'.(string) ($latestRefund['refund_bn'] ?? ''),
                    (string) ($latestRefund['order_id'] ?? ''),
                    $otherParams
                );
            }

            $conn->commit();
        } catch (\Throwable $e) {
            $conn->rollback();
            if ($e instanceof ResourceException) {
                throw $e;
            }
            throw new ResourceException($e->getMessage());
        }

        event(new TradeRefundFinishEvent($latestRefund));

        return true;
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        // Hash: 0d723eca
        return $this->aftersalesOfflineRefundRepository->$method(...$parameters);
    }
}
