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

namespace OrdersBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use OrdersBundle\Services\Orders\NormalOrderService;
use OrdersBundle\Services\StatementsService;
use OrdersBundle\Services\StatementDetailsService;
use Dingo\Api\Exception\ResourceException;
use DistributionBundle\Services\DistributorService;
use MerchantBundle\Services\MerchantService;
use EspierBundle\Jobs\ExportFileJob;
use SupplierBundle\Services\SupplierService;

class Statements extends Controller
{
    /**
     * @SWG\Get(
     *     path="/statement/summarized",
     *     summary="获取结算汇总数据",
     *     tags={"订单"},
     *     description="获取结算汇总数据",
     *     operationId="getSummarized",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="当前页面，从1开始计数", type="integer"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页数量", type="integer"),
     *     @SWG\Parameter( name="distributor_id", in="query", description="店铺ID", type="integer"),
     *     @SWG\Parameter( name="merchant_id", in="query", description="商家ID", type="integer"),
     *     @SWG\Parameter( name="statement_status", in="query", description="结算状态 ready:待结算 done:已结算", type="string"),
     *     @SWG\Parameter( name="start_time", in="query", description="结算周期开始时间", type="integer"),
     *     @SWG\Parameter( name="end_time", in="query", description="结算周期结束时间", type="integer"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                     @SWG\Property(property="total_count", type="integer", description="总数"),
     *                     @SWG\Property(property="list", type="array", @SWG\Items(
     *                         @SWG\Property(property="id", type="string", description="ID"),
     *                         @SWG\Property(property="company_id", type="string", description="平台商户id"),
     *                         @SWG\Property(property="merchant_id", type="string", description="商户id"),
     *                         @SWG\Property(property="distributor_id", type="string", description="店铺id"),
     *                         @SWG\Property(property="statement_no", type="string", description="结算单号"),
     *                         @SWG\Property(property="order_num", type="string", description="订单数量"),
     *                         @SWG\Property(property="total_fee", type="string", description="实付金额，以分为单位"),
     *                         @SWG\Property(property="freight_fee", type="string", description="运费金额，以分为单位"),
     *                         @SWG\Property(property="intra_city_freight_fee", type="string", description="同城配金额，以分为单位"),
     *                         @SWG\Property(property="rebate_fee", type="string", description="分销佣金，以分为单位"),
     *                         @SWG\Property(property="refund_fee", type="string", description="退款金额，以分为单位"),
     *                         @SWG\Property(property="statement_fee", type="string", description="结算金额，以分为单位"),
     *                         @SWG\Property(property="start_time", type="string", description="结算周期开始时间"),
     *                         @SWG\Property(property="end_time", type="string", description="结算周期结束时间"),
     *                         @SWG\Property(property="statement_time", type="string", description="结算时间"),
     *                         @SWG\Property(property="statement_status", type="string", description="结算状态 ready:待结算 done:已结算"),
     *                     ),
     *                 ),
     *             ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function getSummarized(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');

        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 20);

        $params = $request->all('distributor_id', 'merchant_id', 'supplier_id', 'merchant_type', 'statement_status', 'start_time', 'end_time');

        $filter['company_id'] = $companyId;
        $filter['merchant_type'] = $params['merchant_type'] ?? 'distributor';

        if (isset($params['supplier_id']) && $params['supplier_id']) {
            $filter['supplier_id'] = floatval($params['supplier_id']);
        }

        if (isset($params['distributor_id']) && $params['distributor_id']) {
            $filter['distributor_id'] = $params['distributor_id'];
        }

        if (isset($params['merchant_id']) && $params['merchant_id']) {
            $filter['merchant_id'] = $params['merchant_id'];
        }

        if (isset($params['statement_status']) && $params['statement_status']) {
            if ($params['statement_status'] == 'ready') {
                $filter['statement_status'] = ['ready', 'confirmed'];
            } else {
                $filter['statement_status'] = $params['statement_status'];
            }
        }

        if (isset($params['start_time']) && $params['start_time']) {
            $filter['start_time|gt'] = $params['start_time'];
        }

        if (isset($params['end_time']) && $params['end_time']) {
            $filter['end_time|lt'] = $params['end_time'];
        }

        $operatorType = app('auth')->user()->get('operator_type');
        if ($operatorType == 'distributor') { //店铺端
            $filter['distributor_id'] = app('auth')->user()->get('distributor_id');
        }
        if ($operatorType == 'merchant') { //商家端
            $filter['merchant_id'] = app('auth')->user()->get('merchant_id');
        }
        if ($operatorType == 'supplier') { //供应商端
            $supplierService = new SupplierService();
            $filter['supplier_id'] = $supplierService->getIdByOperatorId(app('auth')->user()->get('operator_id'));
        }

        $service = new StatementsService();
        $result = $service->lists($filter, '*', $page, $pageSize, ['created' => 'DESC']);

        $sumFiler = [
            'company_id' => $companyId,
            'merchant_type' => $filter['merchant_type'],
        ];
        if ($operatorType == 'distributor') { //店铺端
            $sumFiler['distributor_id'] = app('auth')->user()->get('distributor_id');
        }

        if ($operatorType == 'merchant') { //商家端
            $sumFiler['merchant_id'] = app('auth')->user()->get('merchant_id');
        }
        if ($operatorType == 'supplier') { //供应商端
            $sumFiler['merchant_id'] = $filter['supplier_id'] ?? 0;
        }

        $sumFiler['statement_status'] = ['ready', 'confirmed'];
        $result['total_statement_fee_ready'] = $service->getSum('statement_fee', $sumFiler); //待结算总额

        $sumFiler['statement_status'] = 'done';
        $result['total_statement_fee_done'] = $service->getSum('statement_fee', $sumFiler);  //已结算总额

        if (!empty($result['list'])) {
            $distributorService = new DistributorService();
            $distributorList = $distributorService->getLists(['distributor_id' => array_column($result['list'], 'distributor_id')], 'distributor_id,name');
            $distributorName = array_column($distributorList, 'name', 'distributor_id');

            $merchantService = new MerchantService();
            $merchantList = $merchantService->getLists(['id' => array_column($result['list'], 'merchant_id')], 'id,merchant_name');
            $merchantName = array_column($merchantList, 'merchant_name', 'id');

            $supplierService = new SupplierService();
            $rs = $supplierService->repository->getLists(['id' => array_column($result['list'], 'supplier_id')], 'id,supplier_name');
            $supplier_names = array_column($rs, 'supplier_name', 'id');

            foreach ($result['list'] as $key => $value) {
                $result['list'][$key]['distributor_name'] = $distributorName[$value['distributor_id']] ?? '';
                $result['list'][$key]['merchant_name'] = $merchantName[$value['merchant_id']] ?? '';
                $result['list'][$key]['supplier_name'] = $supplier_names[$value['supplier_id']] ?? '';
            }
        }

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/statement/summarized/export",
     *     summary="导出结算汇总数据",
     *     tags={"订单"},
     *     description="导出结算汇总数据",
     *     operationId="exportSummarized",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="distributor_id", in="query", description="店铺ID", type="integer"),
     *     @SWG\Parameter( name="merchant_id", in="query", description="商家ID", type="integer"),
     *     @SWG\Parameter( name="statement_status", in="query", description="结算状态 ready:待结算 done:已结算", type="string"),
     *     @SWG\Parameter( name="start_time", in="query", description="结算周期开始时间", type="integer"),
     *     @SWG\Parameter( name="end_time", in="query", description="结算周期结束时间", type="integer"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="status", type="boolean", description="导出状态"),
     *             ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function exportSummarized(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $operatorId = app('auth')->user()->get('operator_id');

        $params = $request->all('distributor_id', 'merchant_id', 'statement_status', 'start_time', 'end_time','merchant_type', 'supplier_id');

        $filter['company_id'] = $companyId;

        if (isset($params['distributor_id']) && $params['distributor_id']) {
            $filter['distributor_id'] = $params['distributor_id'];
        }

        if (isset($params['merchant_id']) && $params['merchant_id']) {
            $filter['merchant_id'] = $params['merchant_id'];
        }

        if (isset($params['supplier_id']) && $params['supplier_id']) {
            $filter['supplier_id'] = $params['supplier_id'];
        }

        if (isset($params['statement_status']) && $params['statement_status']) {
            if ($params['statement_status'] == 'ready') {
                $filter['statement_status'] = ['ready', 'confirmed'];
            } else {
                $filter['statement_status'] = $params['statement_status'];
            }
        }

        $filter['merchant_type'] = 'distributor'; //默认是店铺端
        if (isset($params['merchant_type']) && $params['merchant_type']) {
            $filter['merchant_type'] = $params['merchant_type'];
        } 

        if (isset($params['start_time']) && $params['start_time']) {
            $filter['start_time|gt'] = $params['start_time'];
        }

        if (isset($params['end_time']) && $params['end_time']) {
            $filter['end_time|lt'] = $params['end_time'];
        }

        $operatorType = app('auth')->user()->get('operator_type');
        if ($operatorType == 'distributor') { //店铺端
            $filter['distributor_id'] = app('auth')->user()->get('distributor_id');
        }

        if ($operatorType == 'merchant') { //商家端
            $filter['merchant_id'] = app('auth')->user()->get('merchant_id');
        }

        if ($operatorType == 'supplier') { //供应商端
            $supplierService = new SupplierService();
            $filter['supplier_id'] = $supplierService->getIdByOperatorId(app('auth')->user()->get('operator_id'));
        }

        $gotoJob = (new ExportFileJob('statements', $companyId, $filter, $operatorId))->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/statement/confirm/{statement_id}",
     *     summary="确认结算",
     *     tags={"订单"},
     *     description="确认结算",
     *     operationId="comfirmStatement",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="statement_id", in="path", description="结算单ID", type="integer"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="id", type="string", description="ID"),
     *                 @SWG\Property(property="company_id", type="string", description="平台商户id"),
     *                 @SWG\Property(property="merchant_id", type="string", description="商户id"),
     *                 @SWG\Property(property="distributor_id", type="string", description="店铺id"),
     *                 @SWG\Property(property="statement_no", type="string", description="结算单号"),
     *                 @SWG\Property(property="order_num", type="string", description="订单数量"),
     *                 @SWG\Property(property="total_fee", type="string", description="实付金额，以分为单位"),
     *                 @SWG\Property(property="freight_fee", type="string", description="运费金额，以分为单位"),
     *                 @SWG\Property(property="intra_city_freight_fee", type="string", description="运费金额，以分为单位"),
     *                 @SWG\Property(property="rebate_fee", type="string", description="分销佣金，以分为单位"),
     *                 @SWG\Property(property="refund_fee", type="string", description="退款金额，以分为单位"),
     *                 @SWG\Property(property="statement_fee", type="string", description="结算金额，以分为单位"),
     *                 @SWG\Property(property="start_time", type="string", description="结算周期开始时间"),
     *                 @SWG\Property(property="end_time", type="string", description="结算周期结束时间"),
     *                 @SWG\Property(property="statement_time", type="string", description="结算时间"),
     *                 @SWG\Property(property="statement_status", type="string", description="结算状态 ready:待结算 done:已结算"),
     *             ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function comfirmStatement($statement_id, Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');

        $filter['id'] = $statement_id;
        $filter['company_id'] = $companyId;

        $operatorType = app('auth')->user()->get('operator_type');
        if ($operatorType == 'distributor') { //店铺端
            $filter['distributor_id'] = app('auth')->user()->get('distributor_id');
        }
        if ($operatorType == 'merchant') { //商家端
            $filter['merchant_id'] = app('auth')->user()->get('merchant_id');
        }
        if ($operatorType == 'supplier') { //供应商端
            $supplierService = new SupplierService();
            $filter['supplier_id'] = $supplierService->getIdByOperatorId(app('auth')->user()->get('operator_id'));
        }

        $service = new StatementsService();
        $data = $service->getInfo($filter);
        if (!$data) {
            throw new ResourceException(trans('OrdersBundle/Order.statement_not_exist'));
        }
        if ($data['statement_status'] == 'done') {
            throw new ResourceException(trans('OrdersBundle/Order.already_settled'));
        }

        if ($operatorType == 'admin' || $operatorType == 'staff') {
            if ($data['statement_status'] != 'confirmed') {
                throw new ResourceException(trans('OrdersBundle/Order.merchant_not_confirmed'));
            }

            $updateData['statement_status'] = 'done';
            $updateData['statement_time'] = time();
        } elseif ($operatorType == 'distributor' || $operatorType == 'merchant' || $operatorType == 'supplier') {
            if ($data['statement_status'] != 'ready') {
                throw new ResourceException(trans('OrdersBundle/Order.already_confirmed'));
            }

            $updateData['statement_status'] = 'confirmed';
            $updateData['confirm_time'] = time();
        } else {
            throw new ResourceException(trans('OrdersBundle/Order.no_permission_to_operate'));
        }

        $result = $service->updateOneBy($filter, $updateData);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/statement/detail/{statement_id}",
     *     summary="获取结算明细数据",
     *     tags={"订单"},
     *     description="获取结算明细数据",
     *     operationId="getDetail",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="statement_id", in="path", description="结算单ID", type="integer"),
     *     @SWG\Parameter( name="page", in="query", description="当前页面，从1开始计数", type="integer"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页数量", type="integer"),
     *     @SWG\Parameter( name="start_time", in="query", description="结算周期开始时间", type="integer"),
     *     @SWG\Parameter( name="end_time", in="query", description="结算周期结束时间", type="integer"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                     @SWG\Property(property="total_count", type="integer", description="总数"),
     *                     @SWG\Property(property="list", type="array", @SWG\Items(
     *                         @SWG\Property(property="id", type="string", description="ID"),
     *                         @SWG\Property(property="company_id", type="string", description="平台商户id"),
     *                         @SWG\Property(property="merchant_id", type="string", description="商户id"),
     *                         @SWG\Property(property="distributor_id", type="string", description="店铺id"),
     *                         @SWG\Property(property="statement_id", type="string", description="结算单ID"),
     *                         @SWG\Property(property="statement_no", type="string", description="结算单号"),
     *                         @SWG\Property(property="order_id", type="string", description="订单号"),
     *                         @SWG\Property(property="total_fee", type="string", description="实付金额，以分为单位"),
     *                         @SWG\Property(property="freight_fee", type="string", description="运费金额，以分为单位"),
     *                         @SWG\Property(property="intra_city_freight_fee", type="string", description="运费金额，以分为单位"),
     *                         @SWG\Property(property="rebate_fee", type="string", description="分销佣金，以分为单位"),
     *                         @SWG\Property(property="refund_fee", type="string", description="退款金额，以分为单位"),
     *                         @SWG\Property(property="statement_fee", type="string", description="结算金额，以分为单位"),
     *                         @SWG\Property(property="pay_type", type="string", description="支付方式"),
     *                     ),
     *                 ),
     *             ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function getDetail($statement_id, Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');

        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 20);

        $params = $request->all('start_time', 'end_time', 'order_id');

        $filter['statement_id'] = $statement_id;
        $filter['company_id'] = $companyId;

        if (isset($params['start_time']) && $params['start_time']) {
            $filter['created|gt'] = $params['start_time'];
        }

        if (isset($params['end_time']) && $params['end_time']) {
            $filter['created|lt'] = $params['end_time'];
        }

        if (isset($params['order_id']) && $params['order_id']) {
             $filter['order_id|contains'] = $params['order_id'];
        }

        $operatorType = app('auth')->user()->get('operator_type');
        if ($operatorType == 'distributor') { //店铺端
            $filter['distributor_id'] = app('auth')->user()->get('distributor_id');
        }

        if ($operatorType == 'merchant') { //商家端
            $filter['merchant_id'] = app('auth')->user()->get('merchant_id');
        }

        if ($operatorType == 'supplier') { //供应商端
            $supplierService = new SupplierService();
            $filter['supplier_id'] = $supplierService->getIdByOperatorId(app('auth')->user()->get('operator_id'));
        }

        $service = new StatementDetailsService();
        $result = $service->lists($filter, '*', $page, $pageSize);

        if ($result['total_count'] > 0) {
            $distributorService = new DistributorService();
            $distributorList = $distributorService->getLists(['distributor_id' => array_column($result['list'], 'distributor_id')], 'distributor_id,name');
            $distributorName = array_column($distributorList, 'name', 'distributor_id');

            $merchantService = new MerchantService();
            $merchantList = $merchantService->getLists(['id' => array_column($result['list'], 'merchant_id')], 'id,merchant_name');
            $merchantName = array_column($merchantList, 'merchant_name', 'id');

            $supplierService = new SupplierService();
            $rs = $supplierService->repository->getLists(['id' => array_column($result['list'], 'supplier_id')], 'id,supplier_name');
            $supplier_names = array_column($rs, 'supplier_name', 'id');

            $orderService = new NormalOrderService();
            $orderList = $orderService->getList(['order_id' => array_column($result['list'], 'order_id')]);
            $orderMap = array_column( $orderList, null, 'order_id');

            $distributor_ids = array_column($orderList, 'distributor_id');
            // 供应商结算，不存店铺id,所有从订单表理获取店铺id
            $distributorList = $distributorService->getLists(['distributor_id' => $distributor_ids], 'distributor_id,name');
            $distributorOrderName = array_column($distributorList, 'name', 'distributor_id');
            if (empty($distributorName)) {
                $distributorName = $distributorOrderName;
            }
            foreach ($result['list'] as $key => $value) {
                $distributor_id = $orderMap[$value['order_id']]['distributor_id'] ?? 0;
                $result['list'][$key]['distributor_name'] = $value['distributor_id'] > 0 ? $distributorName[$value['distributor_id']] : $distributorName[$distributor_id] ?? '';
                $result['list'][$key]['merchant_name'] = $merchantName[$value['merchant_id']] ?? '';
                $result['list'][$key]['supplier_name'] = $supplier_names[$value['supplier_id']] ?? '';
                $result['list'][$key]['end_time'] = isset($orderMap[$value['order_id']]['end_time']) ? date('Y-m-d H:i:s', $orderMap[$value['order_id']]['end_time']) : 0;
            }
        }

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/statement/detail/exoprt",
     *     summary="导出结算明细数据",
     *     tags={"订单"},
     *     description="导出结算明细数据",
     *     operationId="exportDetail",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="statement_id", in="query", description="结算单ID", type="integer"),
     *     @SWG\Parameter( name="start_time", in="query", description="结算周期开始时间", type="integer"),
     *     @SWG\Parameter( name="end_time", in="query", description="结算周期结束时间", type="integer"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="status", type="boolean", description="导出状态"),
     *             ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function exportDetail(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $operatorId = app('auth')->user()->get('operator_id');

        $params = $request->all('statement_id', 'start_time', 'end_time');

        $filter['company_id'] = $companyId;

        if (isset($params['statement_id']) && $params['statement_id']) {
            $filter['statement_id'] = $params['statement_id'];
        }

        if (isset($params['start_time']) && $params['start_time']) {
            $filter['created|gt'] = $params['start_time'];
        }

        if (isset($params['end_time']) && $params['end_time']) {
            $filter['created|lt'] = $params['end_time'];
        }

        $operatorType = app('auth')->user()->get('operator_type');
        if ($operatorType == 'distributor') { //店铺端
            $filter['distributor_id'] = app('auth')->user()->get('distributor_id');
        }

        if ($operatorType == 'merchant') { //商家端
            $filter['merchant_id'] = app('auth')->user()->get('merchant_id');
        }

        if ($operatorType == 'supplier') { //供应商端
            $supplierService = new SupplierService();
            $filter['supplier_id'] = $supplierService->getIdByOperatorId(app('auth')->user()->get('operator_id'));
        }
        $statementsInfo = (new StatementsService())->getInfo(['company_id' => $companyId, 'id' => $filter['statement_id']]);
        $filter['merchant_type'] = $statementsInfo['merchant_type'] ?? 'distributor'; //默认是店铺端

        $gotoJob = (new ExportFileJob('statement_details', $companyId, $filter, $operatorId))->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);

        return $this->response->array(['status' => true]);
    }
}
