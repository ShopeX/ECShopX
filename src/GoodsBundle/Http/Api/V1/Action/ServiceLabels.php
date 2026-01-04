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

namespace GoodsBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Response;
use GoodsBundle\Services\ServiceLabelsService;

class ServiceLabels extends BaseController
{
    /**
     * @SWG\Post(
     *     path="/goods/servicelabels",
     *     summary="添加会员数值属性",
     *     tags={"商品"},
     *     description="添加会员数值属性",
     *     operationId="createServiceLabels",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="label_name",
     *         in="query",
     *         description="会员数值属性名称",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="label_price",
     *         in="query",
     *         description="价格",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="label_desc",
     *         in="query",
     *         description="会员数值属性描述",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="label_id", type="integer"),
     *                     @SWG\Property(property="label_name", type="string"),
     *                     @SWG\Property(property="label_price", type="string"),
     *                     @SWG\Property(property="label_desc", type="string"),
     *                     @SWG\Property(property="company_id", type="integer")
     *                 )
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function createServiceLabels(Request $request)
    {
        // ShopEx EcShopX Core Module
        $params = $request->input();
        $validator = app('validator')->make($params, [
            'label_name' => 'required',
            'label_price' => 'required|numeric|min:0',
            'label_desc' => '',
            'service_type' => 'required|in:point,deposit,timescard',
        ]);
        if ($validator->fails()) {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.add_member_value_attr_error'), $validator->errors());
        }

        $serviceLabelsService = new ServiceLabelsService();
        $company_id = app('auth')->user()->get('company_id');
        $data = [
            'company_id' => $company_id,
            'label_name' => $params['label_name'],
            'label_price' => bcmul($params['label_price'], 100),
            'label_desc' => $params['label_desc'],
            'service_type' => $params['service_type'],
        ];
        $result = $serviceLabelsService->createServiceLabels($data);

        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/goods/servicelabels/{label_id}",
     *     summary="更新会员数值属性",
     *     tags={"商品"},
     *     description="更新会员数值属性",
     *     operationId="updateServiceLabels",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="label_id",
     *         in="path",
     *         description="会员数值属性id",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="label_name",
     *         in="query",
     *         description="会员数值属性名称",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="label_price",
     *         in="query",
     *         description="价格",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="label_desc",
     *         in="query",
     *         description="会员数值属性描述",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="label_id", type="integer"),
     *                     @SWG\Property(property="label_name", type="string"),
     *                     @SWG\Property(property="label_price", type="string"),
     *                     @SWG\Property(property="label_desc", type="string"),
     *                     @SWG\Property(property="company_id", type="integer")
     *                 )
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function updateServiceLabels($label_id, Request $request)
    {
        $params = $request->input();
        $params['label_id'] = $label_id;
        $validator = app('validator')->make($params, [
            'label_name' => 'required',
            'label_price' => 'required',
            'label_desc' => '',
            'label_id' => 'required|integer|min:1',
            'service_type' => 'required|in:point,deposit,timescard',
        ]);
        if ($validator->fails()) {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.update_member_value_attr_error'), $validator->errors());
        }

        $serviceLabelsService = new ServiceLabelsService();
        $company_id = app('auth')->user()->get('company_id');
        $data = [
            'company_id' => $company_id,
            'label_id' => $label_id,
            'label_name' => $params['label_name'],
            'label_price' => bcmul($params['label_price'], 100),
            'label_desc' => $params['label_desc'],
            'service_type' => $params['service_type'],
        ];
        $result = $serviceLabelsService->updateServiceLabels($data);

        return $this->response->array($result);
    }

    /**
     * @SWG\Delete(
     *     path="/goods/servicelabels/{label_id}",
     *     summary="删除会员数值属性",
     *     tags={"商品"},
     *     description="删除会员数值属性",
     *     operationId="deleteServiceLabels",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="label_id",
     *         in="path",
     *         description="会员数值属性id",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="label_id", type="string")
     *                 )
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function deleteServiceLabels($label_id)
    {
        $params['label_id'] = $label_id;
        $validator = app('validator')->make($params, [
            'label_id' => 'required|integer|min:1',
        ]);
        if ($validator->fails()) {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.delete_member_value_attr_error'), $validator->errors());
        }

        $serviceLabelsService = new ServiceLabelsService();
        $company_id = app('auth')->user()->get('company_id');
        $params = [
            'label_id' => $label_id,
            'company_id' => $company_id,
        ];
        $result = $serviceLabelsService->deleteServiceLabels($params);

        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Get(
     *     path="/goods/servicelabels/{label_id}",
     *     summary="获取会员数值属性详情",
     *     tags={"商品"},
     *     description="获取会员数值属性详情",
     *     operationId="getServiceLabelsDetail",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="label_id",
     *         in="path",
     *         description="会员数值属性id",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="label_id", type="string"),
     *                     @SWG\Property(property="label_name", type="string"),
     *                     @SWG\Property(property="label_price", type="string"),
       *                   @SWG\Property(property="company_id", type="string")
     *                 )
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function getServiceLabelsDetail($label_id)
    {
        $validator = app('validator')->make(['label_id' => $label_id], [
            'label_id' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.get_member_value_attr_error'), $validator->errors());
        }
        $serviceLabelsService = new ServiceLabelsService();
        $result = $serviceLabelsService->getServiceLabelsDetail($label_id);
        $company_id = app('auth')->user()->get('company_id');
        if ($company_id != $result['company_id']) {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.get_member_value_attr_info_error'));
        }

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/goods/servicelabels",
     *     summary="获取会员数值属性列表",
     *     tags={"商品"},
     *     description="获取会员数值属性列表",
     *     operationId="getServiceLabelsList",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="page",
     *         in="query",
     *         description="当前页面,获取会员数值属性列表的初始偏移位置，从1开始计数",
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="pageSize",
     *         in="query",
     *         description="每页数量,最大不能超过50，并且如果传入的limit参数是0，那么按默认值20处理",
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="keywords",
     *         in="query",
     *         description="物料名称",
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="label_id", type="string"),
     *                     @SWG\Property(property="label_name", type="string"),
     *                     @SWG\Property(property="label_price", type="string"),
     *                     @SWG\Property(property="company_id", type="string")
     *                 )
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function getServiceLabelsList(request $request)
    {
        $inputData = $request->input();
        $validator = app('validator')->make($inputData, [
            'page' => 'required|integer|min:1',
            'pageSize' => 'required|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.get_member_value_attr_list_error'), $validator->errors());
        }

        $params['company_id'] = app('auth')->user()->get('company_id');
        if ($request->input('label_name')) {
            $params['label_name'] = $request->input('label_name');
        }

        if ($request->input('keywords')) {
            unset($params['label_name']);
            $params['label_name|contains'] = $request->input('keywords');
        }

        $params['service_type'] = $inputData['service_type'];
        $page = $inputData['page'];
        $pageSize = $inputData['pageSize'];
        $serviceLabelsService = new ServiceLabelsService();
        $result = $serviceLabelsService->getServiceLabelsList($params, $page, $pageSize);

        return $this->response->array($result);
    }
}
