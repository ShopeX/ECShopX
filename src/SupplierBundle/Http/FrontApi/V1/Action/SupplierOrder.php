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

namespace SupplierBundle\Http\FrontApi\V1\Action;

use App\Http\Controllers\Controller;
use Dingo\Api\Exception\ResourceException;
use Dingo\Api\Exception\StoreResourceFailedException;
use Illuminate\Http\Request;

use AdaPayBundle\Services\BankCodeService;
use MembersBundle\Services\MemberService;
use OpenapiBundle\Services\Order\OrdersNormalOrdersService;
use OrdersBundle\Services\OrderItemsService;
use OrdersBundle\Services\Orders\NormalOrderService;
use SupplierBundle\Services\SupplierOrderService;
use SupplierBundle\Services\SupplierService;

class SupplierOrder extends Controller
{
    /**
     * @SWG\Get(
     *     path="/wxapp/order/get_offline_pay_info",
     *     summary="获取订单线下支付信息",
     * )
     */
    public function getOfflinePayInfo(Request $request)
    {
        $params = $request->all('order_id');
        $rules = [
            'order_id' => ['required', trans('SupplierBundle.order_id_required')],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new StoreResourceFailedException($error);
        }

        $authInfo = app('auth')->user()->get();
        $userId = $authInfo['user_id'];
        $companyId = $authInfo['company_id'];
        $orderId = $params['order_id'];

        $filter = [
            'company_id' => $companyId,
            'order_id' => $orderId,
            'user_id' => $userId,
        ];
        $orderItemsService = new OrderItemsService();
        $orderItemData = $orderItemsService->repository->getList($filter);
        if (!$orderItemData['total_count']) {
            throw new ResourceException(trans('SupplierBundle.order_not_exist_or_no_permission'));
        }

        $supplierId = array_column($orderItemData['list'], 'supplier_id');
        $supplierId = array_unique($supplierId);
        if ($supplierId) {
            $supplierService = new SupplierService();
            $supplierData = $supplierService->repository->getLists(['operator_id' => $supplierId]);
            $supplierData = array_column($supplierData, null, 'operator_id');
        }

        $offlinePayInfo = [];
        $normalOrderService = new NormalOrderService();
        foreach ($orderItemData['list'] as $v) {
            $v['total_fee'] = bcdiv($v['total_fee'], '100', 2);
            if (isset($offlinePayInfo[$v['supplier_id']])) {                
                $offlinePayInfo[$v['supplier_id']]['total_fee'] += $v['total_fee'];
            } else {
                $relData = $normalOrderService->normalOrdersRelSupplierRepository->getInfo(['order_id' => $v['order_id'], 'supplier_id' => $v['supplier_id']]);
                if ($relData && $relData['freight_fee']) {
                    $relData['freight_fee'] = bcdiv($relData['freight_fee'], '100', 2);
                    $v['total_fee'] += $relData['freight_fee'] ?? 0;
                }                
                $offlinePayInfo[$v['supplier_id']] = [
                    'total_fee' => $v['total_fee'],
                    'supplier_id' => $v['supplier_id'],
                                    'bank_name' => $supplierData[$v['supplier_id']]['bank_name'] ?? trans('SupplierBundle.unknown_bank'),
                'bank_account' => $supplierData[$v['supplier_id']]['bank_account'] ?? trans('SupplierBundle.unknown_account'),
                ];
            }
        }
        $offlinePayInfo = array_values($offlinePayInfo);

        return $this->response->array($offlinePayInfo);
    }

    /**
     * @SWG\Post(
     *     path="/wxapp/supplier/set_order_pay_status",
     *     summary="用户设置订单到已转账状态",
     * )
     */
    public function setOrderPayStatus(Request $request)
    {
        $params = $request->all('order_id');
        $rules = [
            'order_id' => ['required', trans('SupplierBundle.order_id_required')],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new StoreResourceFailedException($error);
        }

        $authInfo = app('auth')->user()->get();
        $userId = $authInfo['user_id'];
        $companyId = $authInfo['company_id'];
        $orderId = $params['order_id'];

        $redis = app('redis');
        $redisKey = "supplier:setOrderPayStatus:" . $orderId;
        if ($redis->get($redisKey)) {
            throw new ResourceException(trans('SupplierBundle.please_wait_3_seconds'));
        }
        $redis->set($redisKey, 1, 'EX', 3);

        $supplierOrderService = new SupplierOrderService();
        $orderInfo = $supplierOrderService->buyerUpdatePayStatus($companyId, $orderId, $userId);

        return $this->response->array($orderInfo);
    }

}
