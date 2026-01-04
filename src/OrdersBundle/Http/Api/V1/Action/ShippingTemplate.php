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

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use OrdersBundle\Services\ShippingTemplatesService;
use CompanysBundle\Ego\CompanysActivationEgo;

class ShippingTemplate extends Controller
{
    /**
     * @SWG\Get(
     *     path="/shipping/templates/list",
     *     summary="获取运费模板列表",
     *     tags={"订单"},
     *     description="获取运费模板列表",
     *     operationId="getShippingTemplatesList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="当前页数", type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页数量", type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="total_count", type="integer", example="2", description=""),
     *               @SWG\Property(property="list", type="array", description="",
     *                 @SWG\Items(
     *                           @SWG\Property(property="template_id", type="string", example="53", description="运费模板id"),
     *                           @SWG\Property(property="company_id", type="string", example="1", description="商家id"),
     *                           @SWG\Property(property="distributor_id", type="string", example="0", description="分销商id"),
     *                           @SWG\Property(property="name", type="string", example="偏远地区不发货", description="运费模板名称"),
     *                           @SWG\Property(property="is_free", type="string", example="1", description="是否包邮"),
     *                           @SWG\Property(property="valuation", type="string", example="1", description="运费计算参数来源"),
     *                           @SWG\Property(property="protect", type="string", example="", description="物流保价"),
     *                           @SWG\Property(property="protect_rate", type="string", example="", description="保价费率"),
     *                           @SWG\Property(property="minprice", type="string", example="", description="保价费最低值"),
     *                           @SWG\Property(property="status", type="string", example="1", description="是否开启"),
     *                           @SWG\Property(property="fee_conf", type="string", example="", description="运费模板中运费信息对象，包含默认运费和指定地区运费"),
     *                           @SWG\Property(property="nopost_conf", type="string", example="", description="不包邮地区"),
     *                           @SWG\Property(property="free_conf", type="string", example="", description="指定包邮的条件"),
     *                           @SWG\Property(property="create_time", type="integer", example="1572593089", description="创建时间"),
     *                           @SWG\Property(property="update_time", type="integer", example="1593512354", description="最后修改时间"),
     *                 ),
     *               ),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function getShippingTemplatesList(Request $request)
    {
        $shippingTemplatesServices = new ShippingTemplatesService();

        $status = $request->input('status', 0);
        if ($status) {
            $filter['status'] = 1;
        }

        $filter['company_id'] = app('auth')->user()->get('company_id');
        if ($request->input('is_free', -1) != -1) {
            $filter['is_free'] = $request->input('is_free');
        }
        if ($request->input('valuation', 0)) {
            $filter['valuation'] = $request->input('valuation');
        }

        $operatorType = app('auth')->user()->get('operator_type');
        if ($operatorType == 'supplier') {
            $filter['supplier_id'] = app('auth')->user()->get('operator_id');
        }elseif($operatorType == 'distributor') {
            $filter['distributor_id'] = app('auth')->user()->get('distributor_id');
        } else {
            $filter['supplier_id'] = 0;
        }

        $company = (new CompanysActivationEgo())->check($filter['company_id']);
        if ($company['product_model'] == 'platform') {
            $filter['distributor_id'] = app('auth')->user()->get('distributor_id');
        }

        //云店--运费模板在总部
        if ($company['product_model'] == 'standard') {
            $filter['distributor_id'] = 0;
        }
        
        $orderBy = ['create_time' => 'DESC'];

        $pageSize = $request->input('pageSize', 50);
        $page = $request->input('page', 1);
        $data = $shippingTemplatesServices->getList($filter, $orderBy, $page, $pageSize);
        return $this->response->array($data);
    }

    /**
     * @SWG\Get(
     *     path="/shipping/templates/info/{id}",
     *     summary="获取运费模板列表",
     *     tags={"订单"},
     *     description="获取运费模板列表",
     *     operationId="getShippingTemplatesInfo",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="id", in="path", description="运费模板id", type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="template_id", type="string", example="64", description="运费模板id"),
     *               @SWG\Property(property="company_id", type="string", example="1", description="商家id"),
     *               @SWG\Property(property="distributor_id", type="string", example="0", description="分销商id"),
     *               @SWG\Property(property="name", type="string", example="绍兴", description="运费模板名称"),
     *               @SWG\Property(property="is_free", type="string", example="0", description="是否包邮"),
     *               @SWG\Property(property="valuation", type="string", example="1", description="运费计算参数来源"),
     *               @SWG\Property(property="protect", type="string", example="", description="物流保价"),
     *               @SWG\Property(property="protect_rate", type="string", example="", description="保价费率"),
     *               @SWG\Property(property="minprice", type="string", example="", description="保价费最低值"),
     *               @SWG\Property(property="status", type="string", example="1", description="是否开启"),
     *               @SWG\Property(property="fee_conf", type="string", example="", description="运费模板中运费信息对象，包含默认运费和指定地区运费"),
     *               @SWG\Property(property="nopost_conf", type="string", example="[]", description="不包邮地区"),
     *               @SWG\Property(property="free_conf", type="string", example="", description="指定包邮的条件"),
     *               @SWG\Property(property="create_time", type="integer", example="1581926101", description="创建时间"),
     *               @SWG\Property(property="update_time", type="integer", example="1583132964", description="最后修改时间"),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function getShippingTemplatesInfo($id)
    {
        $shippingTemplatesServices = new ShippingTemplatesService();
        $companyId = app('auth')->user()->get('company_id');
        $data = $shippingTemplatesServices->getInfo($id, $companyId);
        return $this->response->array($data);
    }

    /**
     * @SWG\Post(
     *     path="/shipping/templates/create",
     *     summary="添加运费模板",
     *     tags={"订单"},
     *     description="添加运费模板",
     *     operationId="createShippingTemplates",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="name", in="query", description="运费模板名称", type="string"),
     *     @SWG\Parameter( name="is_free", in="query", description="是否包邮", type="string"),
     *     @SWG\Parameter( name="valuation", in="query", description="计价方式", type="string"),
     *     @SWG\Parameter( name="status", in="query", description="是否启用", type="string"),
     *     @SWG\Parameter( name="fee_conf", in="query", description="运费计算", type="string"),
     *     @SWG\Parameter( name="free_conf", in="query", description="指定条件包邮", type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="template_id", type="string", example="110", description="运费模板id"),
     *               @SWG\Property(property="company_id", type="string", example="1", description="商家id"),
     *               @SWG\Property(property="distributor_id", type="integer", example="0", description="分销商id"),
     *               @SWG\Property(property="name", type="string", example="测试运费模版", description="运费模板名称"),
     *               @SWG\Property(property="is_free", type="string", example="0", description="是否包邮"),
     *               @SWG\Property(property="valuation", type="string", example="1", description="运费计算参数来源"),
     *               @SWG\Property(property="protect", type="string", example="", description="物流保价"),
     *               @SWG\Property(property="protect_rate", type="string", example="", description="保价费率"),
     *               @SWG\Property(property="minprice", type="string", example="", description="保价费最低值"),
     *               @SWG\Property(property="status", type="string", example="1", description="是否开启"),
     *               @SWG\Property(property="fee_conf", type="string", example="", description="运费模板中运费信息对象，包含默认运费和指定地区运费"),
     *               @SWG\Property(property="nopost_conf", type="string", example="[]", description="不包邮地区"),
     *               @SWG\Property(property="free_conf", type="string", example="", description="指定包邮的条件"),
     *               @SWG\Property(property="create_time", type="integer", example="1612507040", description="创建时间"),
     *               @SWG\Property(property="update_time", type="integer", example="1612507040", description="最后修改时间"),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function createShippingTemplates(Request $request)
    {
        $shippingTemplatesServices = new ShippingTemplatesService();

        $data['company_id'] = app('auth')->user()->get('company_id');
        $data['distributor_id'] = app('auth')->user()->get('distributor_id');
        $data['name'] = $request->input('name');
        $data['is_free'] = $request->input('is_free');
        $data['valuation'] = $request->input('valuation');
        $data['status'] = $request->input('status');
        $data['fee_conf'] = $request->input('fee_conf');
        $data['free_conf'] = $request->input('free_conf');
        $data['nopost_conf'] = $request->input('nopost_conf');
        $operatorType = app('auth')->user()->get('operator_type');
        if ($operatorType == 'supplier') {
            $data['supplier_id'] = app('auth')->user()->get('operator_id');
        } else {
            $data['supplier_id'] = 0;
        }
        $data = $shippingTemplatesServices->createShippingTemplates($data);
        return $this->response->array($data);
    }

    /**
     * @SWG\Put(
     *     path="/shipping/templates/update/{id}",
     *     summary="修改运费模板",
     *     tags={"订单"},
     *     description="修改运费模板",
     *     operationId="updateTemplates",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="id", in="path", description="更新id", type="integer"),
     *     @SWG\Parameter( name="name", in="query", description="运费模板名称", type="string"),
     *     @SWG\Parameter( name="is_free", in="query", description="是否包邮", type="string"),
     *     @SWG\Parameter( name="valuation", in="query", description="计价方式", type="string"),
     *     @SWG\Parameter( name="status", in="query", description="是否启用", type="string"),
     *     @SWG\Parameter( name="fee_conf", in="query", description="运费计算", type="string"),
     *     @SWG\Parameter( name="free_conf", in="query", description="指定条件包邮", type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="template_id", type="string", example="1", description="运费模板id"),
     *               @SWG\Property(property="company_id", type="string", example="1", description="商家id"),
     *               @SWG\Property(property="distributor_id", type="string", example="0", description="分销商id"),
     *               @SWG\Property(property="name", type="string", example="包邮", description="运费模板名称"),
     *               @SWG\Property(property="is_free", type="string", example="1", description="是否包邮"),
     *               @SWG\Property(property="valuation", type="string", example="1", description="运费计算参数来源"),
     *               @SWG\Property(property="protect", type="string", example="", description="物流保价"),
     *               @SWG\Property(property="protect_rate", type="string", example="", description="保价费率"),
     *               @SWG\Property(property="minprice", type="string", example="", description="保价费最低值"),
     *               @SWG\Property(property="status", type="string", example="1", description="是否开启"),
     *               @SWG\Property(property="fee_conf", type="string", example="", description="运费模板中运费信息对象，包含默认运费和指定地区运费"),
     *               @SWG\Property(property="nopost_conf", type="string", example="[]", description="不包邮地区"),
     *               @SWG\Property(property="free_conf", type="string", example="", description="指定包邮的条件"),
     *               @SWG\Property(property="create_time", type="integer", example="1560928188", description="创建时间"),
     *               @SWG\Property(property="update_time", type="integer", example="1612513545", description="最后修改时间"),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function updateShippingTemplates($id, Request $request)
    {
        $shippingTemplatesServices = new ShippingTemplatesService();
        $templateId = $id;
        $companyId = app('auth')->user()->get('company_id');
        $data['distributor_id'] = app('auth')->user()->get('distributor_id');
        $data['name'] = $request->input('name');
        $data['is_free'] = $request->input('is_free');
        $data['valuation'] = $request->input('valuation');
        $data['status'] = $request->input('status');
        $data['fee_conf'] = $request->input('fee_conf');
        $data['free_conf'] = $request->input('free_conf');
        $data['nopost_conf'] = $request->input('nopost_conf');
        $operatorType = app('auth')->user()->get('operator_type');
        if ($operatorType == 'supplier') {
            $data['supplier_id'] = app('auth')->user()->get('operator_id');
        } else {
            $data['supplier_id'] = 0;
        }
        $data = $shippingTemplatesServices->updateShippingTemplates($templateId, $companyId, $data);
        return $this->response->array($data);
    }

    /**
     * @SWG\Delete(
     *     path="/shipping/templates/delete/{id}",
     *     summary="删除运费模板列表",
     *     tags={"订单"},
     *     description="删除运费模板列表",
     *     operationId="deleteShippingTemplates",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="id", in="path", description="运费模板id", type="string"),
     *     @SWG\Response(
     *         response=204,
     *         description="成功返回结构",
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function deleteShippingTemplates($id)
    {
        $shippingTemplatesServices = new ShippingTemplatesService();

        $companyId = app('auth')->user()->get('company_id');
        $distributorId = app('auth')->user()->get('distributor_id');
        $operatorType = app('auth')->user()->get('operator_type');
        if ($operatorType == 'supplier') {
            $supplierId = app('auth')->user()->get('operator_id');
        } else {
            $supplierId = 0;
        }

        $data = $shippingTemplatesServices->deleteShippingTemplates($id, $companyId, $distributorId, $supplierId);
        return $this->response->noContent($data);
    }
}
