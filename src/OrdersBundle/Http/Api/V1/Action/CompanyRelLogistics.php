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
use OrdersBundle\Services\CompanyRelLogisticsServices;
use Dingo\Api\Exception\ResourceException;
use OrdersBundle\Services\Kuaidi\KdniaoService;

class CompanyRelLogistics extends Controller
{
    /**
     * @SWG\Get(
     *     path="/company/logistics/list",
     *     summary="获取物流公司列表",
     *     tags={"订单"},
     *     description="获取物流公司列表",
     *     operationId="getCompanyLogisticsList",
     *     @SWG\Parameter( name="corp_name", in="query", description="物流名称", type="string"),
     *     @SWG\Parameter( name="status", in="query", description="物流状态:0,全部;1,开启;2,关闭", type="integer"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="total_count", type="integer", example="30", description="总记录条数"),
     *               @SWG\Property(property="list", type="array", description="",
     *                 @SWG\Items(
     *                           @SWG\Property(property="company_id", type="string", example="1", description="公司ID"),
     *                           @SWG\Property(property="corp_code", type="string", example="OTHER", description="物流公司代码"),
     *                           @SWG\Property(property="corp_id", type="string", example="0", description="物流公司ID"),
     *                           @SWG\Property(property="corp_name", type="string", example="其他", description="物流公司简称"),
     *                           @SWG\Property(property="id", type="string", example="0", description=""),
     *                           @SWG\Property(property="is_enable", type="string", example="1", description=""),
     *                           @SWG\Property(property="kuaidi_code", type="string", example="", description="快递100代码"),
     *                           @SWG\Property(property="phone", type="string", example="123", description="电话"),
     *                           @SWG\Property(property="logo", type="number", example="", description="logo"),
     *                 ),
     *               ),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones")))
     * )
     */
    public function getCompanyLogisticsList(Request $request)
    {
        // FIXME: check performance
        $inputData = $request->input();

        $filter = [];
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $filter['distributor_id'] = $request->get('distributor_id', 0);
        $operatorType = app('auth')->user()->get('operator_type');
        if ($operatorType == 'supplier') {
            $filter['supplier_id'] = app('auth')->user()->get('operator_id');
        } else {
            $filter['supplier_id'] = 0;
        }
        if (!empty($inputData['corp_name'])) {
            $filter['corp_name'] = $inputData['corp_name'];
        }
        if (!empty($inputData['status'])) {
            $filter['status'] = $inputData['status'];
        }
        $companyRelLogisticsServices = new CompanyRelLogisticsServices();
        $companyRelLogisticsList = $companyRelLogisticsServices->getCompanyRelLogisticsList($filter);
        $logisticsEnable = [];
        $logisticsUnEnable = [];
        foreach ($companyRelLogisticsList['list'] as &$value) {
            if ($value['company_id'] == $filter['company_id']) {
                $value['is_enable'] = true;
                $logisticsEnable[] = $value;
            } else {
                $logisticsUnEnable[] = $value;
            }
        }
        if (!empty($logisticsEnable)) {
            foreach ($logisticsEnable as $key => $item) {
                $enable_sort[] = getFirstCharter($item['corp_name']);
            }
            array_multisort($enable_sort, SORT_STRING, $logisticsEnable);
        }
        if (!empty($logisticsUnEnable)) {
            foreach ($logisticsUnEnable as $key => $item) {
                $unEnable_sort[] = getFirstCharter($item['corp_name']);
            }
            array_multisort($unEnable_sort, SORT_STRING, $logisticsUnEnable);
        }
        $companyRelLogisticsList['list'] = array_merge($logisticsEnable, $logisticsUnEnable);
        $other = [[
            "company_id" => $filter['company_id'],
            "corp_code" => "OTHER",
            "corp_id" => "0",
            "corp_name" => "其他",
            "id" => "0",
            "is_enable" => true,
            "kuaidi_code" => "",
            'phone' => '',
            'logo' => 'https://b-img-cdn.yuanyuanke.cn/image/21/2021/05/21/f425f5ae2e6032eb6fded1015fc979e4FBZzXLlyYgHllKWlMHg0NyKqVBZDNkvM',
        ]];
        if (!isset($filter['corp_name']) && !isset($filter['status'])) {
            $companyRelLogisticsList['list'] = array_merge($other, $companyRelLogisticsList['list']);
            $companyRelLogisticsList['total_count']++;
        }
        if (!empty($filter['status']) && $filter['status'] == 1) {
            $companyRelLogisticsList['list'] = array_merge($other, $companyRelLogisticsList['list']);
            $companyRelLogisticsList['total_count']++;
        }
        return $this->response->array($companyRelLogisticsList);
    }

    /**
     * @SWG\Get(
     *     path="/trade/logistics/list",
     *     summary="发货显示物流列表",
     *     tags={"订单"},
     *     description="发货显示物流列表",
     *     operationId="getLogisticsList",
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                     @SWG\Property(property="value", type="string", example="长宁店"),
     *                     @SWG\Property(property="name", type="string", example="上海徐汇田林路"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones")))
     * )
     */
    public function getLogisticsList(Request $request)
    {
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $filter['distributor_id'] = $request->get('distributor_id', 0);
        $operatorType = app('auth')->user()->get('operator_type');
        if ($operatorType == 'supplier') {
            $filter['supplier_id'] = app('auth')->user()->get('operator_id');
        } else {
            $filter['supplier_id'] = 0;
        }

        $kuaidiType = app('redis')->get('kuaidiTypeOpenConfig:' . sha1($filter['company_id']));

        if ($kuaidiType == 'kuaidi100') {
            $cols = 'kuaidi_code as value,corp_name as name';
        } else {
            $cols = 'corp_code as value,corp_name as name';
        }

        $companyRelLogisticsServices = new CompanyRelLogisticsServices();
        $companyRelLogisticsList = $companyRelLogisticsServices->getCompanyRelLogistics($filter, 1, 100, ['id' => 'DESC'], $cols);
        $other = [[
            "value" => 'OTHER',
            "name" => "其他",
        ]];
        $companyRelLogisticsList['list'] = array_merge($other, $companyRelLogisticsList['list']);

        return $this->response->array($companyRelLogisticsList);
    }

    /**
     * @SWG\Post(path="/company/logistics/create",
     *   tags={"订单"},
     *   summary="启用物流公司",
     *   description="启用物流公司",
     *   operationId="createCompanyLogistics",
     *   produces={"application/json"},
     *   @SWG\Parameter(
     *     in="query",
     *     name="corp_id",
     *     description="物流ID",
     *     required=true,
     *     type="string"
     *   ),
     *     @SWG\Parameter(
     *     in="query",
     *     name="corp_name",
     *     description="公司简称",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     in="query",
     *     name="corp_code",
     *     description="物流代码",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="id", type="integer", example="2696", description=""),
     *               @SWG\Property(property="corp_id", type="string", example="571", description="物流公司ID"),
     *               @SWG\Property(property="company_id", type="string", example="1", description="公司ID"),
     *               @SWG\Property(property="corp_code", type="string", example="ZYWL", description="物流公司代码"),
     *               @SWG\Property(property="kuaidi_code", type="string", example="zhongyouwuliu", description="快递100代码"),
     *               @SWG\Property(property="corp_name", type="string", example="中邮物流", description="物流公司简称"),
     *            ),
     *         ),
     *    ),
     *    @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function createCompanyLogistics(Request $request)
    {
        $params = $request->all('corp_id', 'corp_name', 'corp_code', 'kuaidi_code');
        $rules = [
            'corp_id' => ['required', '物流公司id必填'],
            'corp_name' => ['required', '物流公司简称必填'],
            'corp_code' => ['required', '快递鸟代码必填'],
            'kuaidi_code' => ['required', '快递100代码必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $params['company_id'] = app('auth')->user()->get('company_id');
        $params['distributor_id'] = $request->get('distributor_id', 0);
        $operatorType = app('auth')->user()->get('operator_type');
        if ($operatorType == 'supplier') {
            $params['supplier_id'] = app('auth')->user()->get('operator_id');
        } else {
            $params['supplier_id'] = 0;
        }

        $companyRelLogisticsServices = new CompanyRelLogisticsServices();
        $result = $companyRelLogisticsServices->createCompanyRelLogistics($params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Delete(
     *     path="/company/logistics/{id}",
     *     summary="关闭物流公司",
     *     tags={"订单"},
     *     description="关闭物流公司",
     *     operationId="deleteCompanyLogistics",
     *     @SWG\Parameter( name="corp_id", in="query", description="物流公司id", required=true, type="integer"),
     *     @SWG\Response(
     *       response="200",
     *       description="删除成功",
     *       @SWG\Schema()
     *   ),
     *     ),
     * )
     */
    public function deleteCompanyLogistics($id, Request $request)
    {
        // FIXME: check performance
        $params['corp_id'] = $id;
        $rules = [
            'corp_id' => ['required', '物流公司id必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $params['company_id'] = app('auth')->user()->get('company_id');
        $params['distributor_id'] = $request->get('distributor_id', 0);
        $operatorType = app('auth')->user()->get('operator_type');
        if ($operatorType == 'supplier') {
            $params['supplier_id'] = app('auth')->user()->get('operator_id');
        } else {
            $params['supplier_id'] = 0;
        }

        $companyRelLogisticsServices = new CompanyRelLogisticsServices();
        $companyRelLogisticsServices->deleteCompanyRelLogistics($params);

        return $this->response->noContent();
    }

    /**
     * @SWG\Get(path="/company/logistics/qinglongcode",
     *   tags={"订单"},
     *   summary="青龙物流编码信息",
     *   description="青龙物流编码信息",
     *   operationId="getQinglongcode",
     *   produces={"application/json"},
     *   @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="qinglong_code", type="string", example="1231", description=""),
     *            ),
     *         ),
     *    ),
     * )
     */
    public function getQinglongcode(Request $request)
    {
        $company_id = app('auth')->user()->get('company_id');

        $kdniaoService = new KdniaoService();
        $qinglong_code = $kdniaoService->getQingLongCode($company_id);
        return $this->response->array(['qinglong_code' => $qinglong_code]);
    }

    /**
     * @SWG\Post(path="/company/logistics/qinglongcode",
     *   tags={"订单"},
     *   summary="设置青龙物流编码",
     *   description="设置青龙物流编码",
     *   operationId="setQinglongcode",
     *   produces={"application/json"},
     *   @SWG\Parameter(
     *     in="query",
     *     name="qinglong_code",
     *     description="青龙物流编码",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="status", type="string", example="true", description=""),
     *            ),
     *         ),
     *    ),
     *    @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function setQinglongcode(Request $request)
    {
        $params = $request->all('qinglong_code');
        $rules = [
            'qinglong_code' => ['required', '青龙物流编码必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $params['company_id'] = app('auth')->user()->get('company_id');

        $kdniaoService = new KdniaoService();
        $kdniaoService->setQingLongCode($params['company_id'], $params['qinglong_code']);
        return $this->response->array(['status' => true]);
    }
}
