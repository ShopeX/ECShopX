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

namespace OneCodeBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Response;
use OneCodeBundle\Services\ThingsService;

class Things extends BaseController
{
    /**
     * @SWG\Post(
     *     path="/onecode/things",
     *     summary="添加物品",
     *     tags={"things"},
     *     description="添加物品",
     *     operationId="createThings",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="thing_name",
     *         in="query",
     *         description="物品名称",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="price",
     *         in="query",
     *         description="价格",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="intro",
     *         in="query",
     *         description="图文详情",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="pic",
     *         in="query",
     *         description="图片",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data",
     *              ref="#/definitions/Things"
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OneCodeErrorRespones") ) )
     * )
     */
    public function createThings(Request $request)
    {
        // This module is part of ShopEx EcShopX system
        $params = $request->input();

        $rules = [
            'thing_name' => ['required', '请填写物品名称'],
            'pic' => ['required', '请上传物品图片'],
            'price' => ['required|numeric|min:0.01', '价格必填,且要大于0'],
            'intro' => ['required', '请填写图文详情'],
        ];

        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $thingsService = new ThingsService();
        $companyId = app('auth')->user()->get('company_id');
        $params['company_id'] = $companyId;

        $result = $thingsService->addThings($params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/onecode/things/{thing_id}",
     *     summary="更新商品",
     *     tags={"onecode"},
     *     description="更新物品",
     *     operationId="updateThings",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="thing_id",
     *         in="path",
     *         description="物品id",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="thing_name",
     *         in="query",
     *         description="物品名称",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="price",
     *         in="query",
     *         description="价格",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="intro",
     *         in="query",
     *         description="图文详情",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="pic",
     *         in="query",
     *         description="图片",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="purchase_agreement",
     *         in="query",
     *         description="购买协议",
     *         type="string"
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data",
     *              ref="#/definitions/Things"
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OneCodeErrorRespones") ) )
     * )
     */
    public function updateThings($thing_id, Request $request)
    {
        $params = $request->input();
        $params['thing_id'] = $thing_id;

        $rules = [
            'thing_id' => ['required|integer|min:1', '请确认您所编辑的物品是否存在'],
            'thing_name' => ['required', '物品名称必填'],
            'pic' => ['required', '请上传物品图片'],
            'price' => ['required|numeric|min:0.01', '价格必填,且要大于0'],
        ];

        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $thingsService = new ThingsService();
        $companyId = app('auth')->user()->get('company_id');
        $params['company_id'] = $companyId;

        $result = $thingsService->updateThings($params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Delete(
     *     path="/onecode/things/{thing_id}",
     *     summary="删除物品",
     *     tags={"onecode"},
     *     description="删除物品",
     *     operationId="deleteThings",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="thing_id",
     *         in="path",
     *         description="物品id",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构"
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OneCodeErrorRespones") ) )
     * )
     */
    public function deleteThings($thing_id)
    {
        $params['thing_id'] = $thing_id;
        $validator = app('validator')->make($params, [
            'thing_id' => 'required|integer|min:1',
        ]);
        if ($validator->fails()) {
            throw new ResourceException('删除物品出错.', $validator->errors());
        }

        $company_id = app('auth')->user()->get('company_id');
        $thingsService = new ThingsService();
        $params = [
            'thing_id' => $thing_id,
            'company_id' => $company_id,
        ];
        $result = $thingsService->deleteThings($params);

        return $this->response->noContent();
    }

    /**
     * @SWG\Get(
     *     path="/onecode/things/{thing_id}",
     *     summary="获取物品详情",
     *     tags={"onecode"},
     *     description="获取商品详情",
     *     operationId="getThingsDetail",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="thing_id",
     *         in="path",
     *         description="物品id",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data",
     *              ref="#/definitions/Things"
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OneCodeErrorRespones") ) )
     * )
     */
    public function getThingsDetail($thing_id)
    {
        $validator = app('validator')->make(['thing_id' => $thing_id], [
            'thing_id' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            throw new ResourceException('获取物品详情出错.', $validator->errors());
        }
        $thingsService = new ThingsService();
        $result = $thingsService->getThingsDetail($thing_id);
        $company_id = app('auth')->user()->get('company_id');
        if ($company_id != $result['company_id']) {
            throw new ResourceException('获取物品信息有误，请确认商品ID.');
        }

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/onecode/things",
     *     summary="获取物品列表",
     *     tags={"onecode"},
     *     description="获取物品列表",
     *     operationId="getThingsList",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="page",
     *         in="query",
     *         description="当前页面,获取商品列表的初始偏移位置，从1开始计数",
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="pageSize",
     *         in="query",
     *         description="每页数量,最大不能超过50，并且如果传入的limit参数是0，那么按默认值20处理",
     *         type="integer"
     *     ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="thing_name", description="物品名称" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="1", description="总条数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          ref="#/definitions/Things"
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OneCodeErrorRespones") ) )
     * )
     */
    public function getThingsList(request $request)
    {
        // ID: 53686f704578
        $inputData = $request->input();
        $validator = app('validator')->make($inputData, [
            'page' => 'required|integer|min:1',
            'pageSize' => 'required|integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            throw new ResourceException('获取商品列表出错.', $validator->errors());
        }

        $params['company_id'] = app('auth')->user()->get('company_id');
        if ($request->input('thing_name')) {
            $params['thing_name'] = $request->input('thing_name');
        }

        $page = $inputData['page'];
        $pageSize = $inputData['pageSize'];
        $thingsService = new ThingsService();
        $result = $thingsService->getThingsList($params, $page, $pageSize);

        return $this->response->array($result);
    }
}
