<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace SupplierBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller;
use Dingo\Api\Exception\ResourceException;
use DistributionBundle\Services\DistributorService;
use Illuminate\Http\Request;
use OrdersBundle\Services\Orders\NormalOrderService;
use SupplierBundle\Services\SupplierOrderService;
use SupplierBundle\Services\SupplierService;

class SupplierOrder extends Controller
{
    /**
     * @SWG\Get(
     *     path="/supplier/get_order_list",
     *     summary="供应商查询订单列表",
     * )
     */
    public function getOrderList(Request $request)
    {
        $params = $request->all();
        $auth = app('auth')->user()->get();
        $params['company_id'] = $auth['company_id'];
        $params['supplier_id'] = $auth['operator_id'];
        // $params['supplier_id'] = $auth['operator_id'];
        $params['is_check'] = 0;
        $page = intval($params['page'] ?? 1);
        $pageSize = intval($params['pageSize'] ?? 10);

        $supplierService = new SupplierOrderService();
        $filter = $supplierService->getOrderFilter($params);

        $orderBy = ['id' => 'DESC'];
        
        $result = $supplierService->repository->lists($filter, '*', $page, $pageSize, $orderBy);
        if ($result['list']) {   
            
            //获取店铺信息
            $distributorService = new DistributorService();
            $storeIds = array_filter(array_unique(array_column($result['list'], 'distributor_id')), function ($distributorId) {
                return is_numeric($distributorId) && $distributorId >= 0;
            });
            $storeData = [];
            if ($storeIds) {
                $storeList = $distributorService->getDistributorOriginalList([
                    'company_id' => $params['company_id'],
                    'distributor_id' => $storeIds,
                ], 1, $pageSize);
                $storeData = array_column($storeList['list'], null, 'distributor_id');
                // 附加总店信息
                $storeData[0] = $distributorService->getDistributorSelfSimpleInfo($params['company_id']);
            }
            
            $orderIds = array_column($result['list'], 'order_id');
            $normalOrderService = new NormalOrderService();
            $rs = $normalOrderService->normalOrdersItemsRepository->getList([
                'company_id' => $params['company_id'],
                'order_id' => $orderIds,
                'supplier_id' => $params['supplier_id'],
            ]);
            foreach ($rs['list'] as $v) {
                $orderItemData[$v['order_id']][] = $v;
            }

            foreach ($result['list'] as $k => $v) {
                $result['list'][$k]['order_status_msg'] = $supplierService->getOrderStatusMsg($v, [], 'supplier');
                $result['list'][$k]['items'] = $orderItemData[$v['order_id']] ?? [];
                $result['list'][$k]['distributor_name'] = isset($v['distributor_id']) ? ($storeData[$v['distributor_id']]['name'] ?? '') : '';
            }
        }

        // 是否有权限查看加密数据
        $datapassBlock = $request->get('x-datapass-block', 0);
        $result['filter'] = $filter;
        $result['datapass_block'] = $datapassBlock;

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/supplier/order_paid_confirm",
     *     summary="供应商确认线下支付状态",
     * )
     */
    public function orderPaidConfirm(Request $request)
    {
        $params = $request->all();
        $auth = app('auth')->user()->get();
        // $params['company_id'] = $auth['company_id'];
        // $params['operator_id'] = $auth['operator_id'];
        $orderId = intval($params['order_id']);
        $companyId = $auth['company_id'];
        $supplierId = $auth['operator_id'];

        $filter = [
            'company_id' => $companyId,
            'supplier_id' => $supplierId,
            'order_id' => $orderId,
        ];

        $result = [];
        $supplierOrderService = new SupplierOrderService();
        $orderInfo = $supplierOrderService->repository->getInfo($filter);
        if (!$orderInfo) {
            throw new ResourceException(trans('SupplierBundle.order_not_exist'));
        }
        if ($orderInfo['order_status'] != 'WAIT_PAID_CONFIRM') {
            throw new ResourceException(trans('SupplierBundle.order_not_pending_confirm'));
        }

        $supplierOrderService->orderPaidConfirm($companyId, $supplierId, $orderId);

        return $this->response->array($result);
    }

}
