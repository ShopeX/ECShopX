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
use OrdersBundle\Services\StatementPeriodSettingService;
use DistributionBundle\Services\DistributorService;
use MerchantBundle\Services\MerchantService;
use Dingo\Api\Exception\ResourceException;
use SupplierBundle\Services\SupplierService;

class StatementPeriodSetting extends Controller
{
    /**
     * @SWG\Post(
     *     path="/statement/period/setting",
     *     summary="保存结算周期设置",
     *     tags={"订单"},
     *     description="保存结算周期设置",
     *     operationId="saveSetting",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="id", in="query", description="ID", required=false, type="integer"),
     *     @SWG\Parameter( name="distributor_id", in="query", description="店铺ID", required=false, type="integer"),
     *     @SWG\Parameter( name="period[0]", in="query", description="周期", required=true, type="integer"),
     *     @SWG\Parameter( name="period[1]", in="query", description="周期：day天 week周 month月", required=true, type="string"),
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
     *                 @SWG\Property(property="period", type="string", description="提现周期"),
     *             ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function saveSetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        
        $params = $request->all('id', 'distributor_id', 'period', 'supplier_id', 'merchant_type');
        if (
            !isset($params['period']) ||
            !is_array($params['period']) || count($params['period']) != 2 ||
            intval($params['period'][0]) <= 0 ||
            !in_array($params['period'][1], ['day', 'week', 'month'])
        ) {
            throw new ResourceException('结算周期设置错误');
        }

        $params['company_id'] = $companyId;
        $params['merchant_type'] = $params['merchant_type'] ?? 'distributor';
        $params['distributor_id'] = $params['distributor_id'] ?? 0;
        $params['supplier_id'] = $params['supplier_id'] ?? 0;
        $params['merchant_id'] = 0;
        $params['period'][0] = intval($params['period'][0]);

        if ($params['distributor_id'] > 0) {
            $distributorService = new DistributorService();
            $distributor = $distributorService->getInfoSimple(['company_id' => $companyId, 'distributor_id' => $params['distributor_id']]);
            if (!$distributor) {
                throw new ResourceException('店铺不存在');
            }
            $params['merchant_id'] = $distributor['merchant_id'] ?? 0;
        }

        $service = new StatementPeriodSettingService();
        if ($params['merchant_type'] == 'distributor') {
            $_filter = ['company_id' => $companyId, 'distributor_id' => $params['distributor_id'], 'merchant_type' => 'distributor'];
        } else {
            $_filter = ['company_id' => $companyId, 'supplier_id' => $params['supplier_id'], 'merchant_type' => 'supplier'];
        }
        $setting = $service->getInfo($_filter);
        if (isset($params['id']) && $params['id'] > 0) {
            if (!empty($setting) && $setting['id'] != $params['id']) {
                $merchant_type_name = ($params['merchant_type'] == 'distributor') ? '店铺' : '供应商';
                throw new ResourceException("每个{$merchant_type_name}只能配置一个结算周期");
            }
            $result = $service->updateOneBy(['id' => $params['id']], $params);
        } else {
            if (!empty($setting)) {
                $result = $service->updateOneBy(['id' => $setting['id']], $params);
            } else {
                $result = $service->create($params);
            }
        }

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/statement/period/distributor/setting",
     *     summary="获取店铺结算周期配置",
     *     tags={"订单"},
     *     description="获取店铺结算周期配置",
     *     operationId="getDistributorSetting",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="当前页面，从1开始计数", type="integer"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页数量", type="integer"),
     *     @SWG\Parameter( name="distributor_id", in="query", description="店铺ID", type="integer"),
     *     @SWG\Parameter( name="merchant_id", in="query", description="商家ID", type="integer"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="total_count", type="integer", description="总数"),
     *                 @SWG\Property(property="list", type="array",
     *                     @SWG\Items(
     *                         @SWG\Property(property="id", type="string", description="ID"),
     *                         @SWG\Property(property="company_id", type="string", description="平台商户id"),
     *                         @SWG\Property(property="merchant_id", type="string", description="商户id"),
     *                         @SWG\Property(property="distributor_id", type="string", description="店铺id"),
     *                         @SWG\Property(property="period", type="string", description="提现周期"),
     *                     ),
     *                 ),
     *             ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function getDistributorSetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $service = new StatementPeriodSettingService();

        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 20);

        $filter['company_id'] = $companyId;
        $filter['merchant_type'] = 'distributor';
        $filter['distributor_id|gt'] = 0;

        if ($request->get('distributor_id', 0)) {
            $filter['distributor_id'] = $request->get('distributor_id');
        }

        if ($request->get('merchant_id', 0)) {
            $filter['merchant_id'] = $request->get('merchant_id');
        }

        $result = $service->lists($filter, '*', $page, $pageSize);

        if ($result['total_count'] > 0) {
            $distributorService = new DistributorService();
            $distributorList = $distributorService->getLists(['distributor_id' => array_column($result['list'], 'distributor_id')], 'distributor_id,name');
            $distributorName = array_column($distributorList, 'name', 'distributor_id');

            $merchantService = new MerchantService();
            $merchantList = $merchantService->getLists(['id' => array_column($result['list'], 'merchant_id')], 'id,merchant_name');
            $merchantName = array_column($merchantList, 'merchant_name', 'id');

            foreach ($result['list'] as $key => $value) {
                $result['list'][$key]['distributor_name'] = $distributorName[$value['distributor_id']] ?? '';
                $result['list'][$key]['merchant_name'] = $merchantName[$value['merchant_id']] ?? '';
            }
        }

        return $this->response->array($result);
    }

    /**
     * 获取供应商结算周期配置
     * path = "statement/period/supplier/setting"
     */
    public function getSupplierSetting(Request $request)
    {
        $result = [
            'total_count' => 0,
            'list' => []
        ];
        $companyId = app('auth')->user()->get('company_id');
        $service = new StatementPeriodSettingService();

        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 20);

        $filter['company_id'] = $companyId;
        $filter['merchant_type'] = 'supplier';
        $filter['supplier_id|gt'] = 0;

        $supplier_name = trim($request->get('supplier_name', ''));
        if ($supplier_name) {
            $supplierService = new SupplierService();
            $rs = $supplierService->repository->getLists(['supplier_name|contains' => $supplier_name], 'id');
            if (!$rs) {
                return $this->response->array($result);
            }
            $filter['supplier_id'] = array_column($rs, 'id');
        }
        $supplier_id = trim($request->get('supplier_id', ''));
        if (isset($filter['supplier_id']) && !empty($filter['supplier_id'])) {
            $filter['supplier_id'] = array_intersect([$supplier_id], $filter['supplier_id']);
        }else {
            $filter['supplier_id'] = $supplier_id;
        }

        $result = $service->lists($filter, '*', $page, $pageSize);
        if ($result['total_count'] > 0) {
            $supplierService = new SupplierService();
            $rs = $supplierService->repository->getLists(['id' => array_column($result['list'], 'supplier_id')], 'id, supplier_name');
            $supplier_names = array_column($rs, 'supplier_name', 'id');

            foreach ($result['list'] as &$v) {
                $v['supplier_name'] = $supplier_names[$v['supplier_id']] ?? '';
            }
        }

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/statement/period/default/setting",
     *     summary="获取默认结算周期配置",
     *     tags={"订单"},
     *     description="获取默认结算周期配置",
     *     operationId="getDefaultSetting",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
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
     *                 @SWG\Property(property="period", type="string", description="提现周期"),
     *             ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function getDefaultSetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $service = new StatementPeriodSettingService();

        $filter['company_id'] = $companyId;
        $filter['distributor_id'] = 0;
        $filter['merchant_type'] = $request->get('merchant_type', 'distributor');

        $result = $service->getInfo($filter);

        return $this->response->array($result);
    }
}
