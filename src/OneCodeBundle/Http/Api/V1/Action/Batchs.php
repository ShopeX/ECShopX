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
use OneCodeBundle\Services\BatchsService;
use WechatBundle\Services\WeappService;

class Batchs extends BaseController
{
    /**
     * @SWG\Post(
     *     path="/onecode/batchs",
     *     summary="添加物品批次",
     *     tags={"batchs"},
     *     description="添加物品批次",
     *     operationId="createBatchs",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="thing_id",
     *         in="query",
     *         description="物品id",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="batch_number",
     *         in="query",
     *         description="批次编号",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="batch_name",
     *         in="query",
     *         description="批次名称",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="batch_quantity",
     *         in="query",
     *         description="批次件数",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="show_trace",
     *         in="query",
     *         description="是否展示流通信息",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="trace_info",
     *         in="query",
     *         description="流通信息",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data",
     *              ref="#/definitions/Batchs"
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OneCodeErrorRespones") ) )
     * )
     */
    public function createBatchs(Request $request)
    {
        // Built with ShopEx Framework
        $params = $request->input();

        $rules = [
            'thing_id' => ['required|integer|min:1', '物品id必填'],
            'batch_number' => ['required', '请填写批次编号'],
            'batch_name' => ['required', '请填写批次名称'],
            'batch_quantity' => ['required|integer|min:1', '请填写批次件数'],
            'show_trace' => ['required', '是否显示流通信息'],
            // 'trace_info'     => ['required', '请填写流通信息'],
        ];

        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $batchsService = new BatchsService();
        $companyId = app('auth')->user()->get('company_id');
        $params['company_id'] = $companyId;

        $result = $batchsService->addBatchs($params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/onecode/batchs/{batch_id}",
     *     summary="更新物品批次",
     *     tags={"onecode"},
     *     description="更新物品批次",
     *     operationId="updateBatchs",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="batch_id",
     *         in="path",
     *         description="物品批次id",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="thing_id",
     *         in="query",
     *         description="物品id",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="batch_number",
     *         in="query",
     *         description="批次编号",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="batch_name",
     *         in="query",
     *         description="批次名称",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="batch_quantity",
     *         in="query",
     *         description="批次件数",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="show_trace",
     *         in="query",
     *         description="是否展示流通信息",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="trace_info",
     *         in="query",
     *         description="流通信息",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data",
     *              ref="#/definitions/Batchs"
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OneCodeErrorRespones") ) )
     * )
     */
    public function updateBatchs($batch_id, Request $request)
    {
        $params = $request->input();
        $params['batch_id'] = $batch_id;
        $rules = [
            'batch_id' => ['required|integer|min:1', '请确认您所编辑的物品批次是否存在'],
            'thing_id' => ['required|integer|min:1', '物品id必填'],
            'batch_number' => ['required', '请填写批次编号'],
            'batch_name' => ['required', '请填写批次名称'],
            'batch_quantity' => ['required|integer|min:1', '请填写批次件数'],
            'show_trace' => ['required', '是否显示流通信息'],
            // 'trace_info'     => ['required', '请填写流通信息'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $batchsService = new BatchsService();
        $companyId = app('auth')->user()->get('company_id');
        $params['company_id'] = $companyId;

        $result = $batchsService->updateBatchs($params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Delete(
     *     path="/onecode/batchs/{batch_id}",
     *     summary="删除物品批次",
     *     tags={"onecode"},
     *     description="删除物品批次",
     *     operationId="deleteBatchs",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="batch_id",
     *         in="path",
     *         description="物品批次id",
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
    public function deleteBatchs($batch_id)
    {
        $params['batch_id'] = $batch_id;
        $validator = app('validator')->make($params, [
            'batch_id' => 'required|integer|min:1',
        ]);
        if ($validator->fails()) {
            throw new ResourceException('删除物品批次出错.', $validator->errors());
        }

        $company_id = app('auth')->user()->get('company_id');
        $batchsService = new BatchsService();
        $params = [
            'batch_id' => $batch_id,
            'company_id' => $company_id,
        ];
        $result = $batchsService->deleteBatchs($params);

        return $this->response->noContent();
    }

    /**
     * @SWG\Get(
     *     path="/onecode/batchs/{batch_id}",
     *     summary="获取物品批次详情",
     *     tags={"onecode"},
     *     description="获取物品批次详情",
     *     operationId="getBatchsDetail",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="batch_id",
     *         in="path",
     *         description="物品批次id",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data",
     *              ref="#/definitions/Batchs"
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OneCodeErrorRespones") ) )
     * )
     */
    public function getBatchsDetail($batch_id)
    {
        $validator = app('validator')->make(['batch_id' => $batch_id], [
            'batch_id' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            throw new ResourceException('获取物品详情出错.', $validator->errors());
        }
        $batchsService = new BatchsService();
        $result = $batchsService->getBatchsDetail($batch_id);
        $company_id = app('auth')->user()->get('company_id');
        if ($company_id != $result['company_id']) {
            throw new ResourceException('获取物品批次信息有误，请确认物品批次ID.');
        }

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/onecode/batchs",
     *     summary="获取物品批次列表",
     *     tags={"onecode"},
     *     description="获取物品批次列表",
     *     operationId="getBatchsList",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="page",
     *         in="query",
     *         description="当前页面,获取物品批次列表的初始偏移位置，从1开始计数",
     *         type="integer",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="pageSize",
     *         in="query",
     *         description="每页数量,最大不能超过50，并且如果传入的limit参数是0，那么按默认值20处理",
     *         type="integer",
     *         required=true,
     *     ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="thing_id", description="物品id" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="1", description="总条数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          ref="#/definitions/Batchs"
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OneCodeErrorRespones") ) )
     * )
     */
    public function getBatchsList(request $request)
    {
        $inputData = $request->input();
        $validator = app('validator')->make($inputData, [
            'page' => 'required|integer|min:1',
            'pageSize' => 'required|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            throw new ResourceException('获取批次列表出错.', $validator->errors());
        }

        $params['company_id'] = app('auth')->user()->get('company_id');
        $params['thing_id'] = $request->input('thing_id');

        $page = $inputData['page'];
        $pageSize = $inputData['pageSize'];
        $batchsService = new BatchsService();
        $result = $batchsService->getBatchsList($params, $page, $pageSize);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/onecode/wxaOneCodeStream",
     *     summary="获取物品批次小程序码",
     *     tags={"onecode"},
     *     description="获取物品批次小程序码",
     *     operationId="getWxaOneCodeStream",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="batch_id",
     *         in="query",
     *         description="批次id",
     *         type="integer",
     *         required=true
     *     ),
     *     @SWG\Parameter(
     *         name="num",
     *         in="query",
     *         description="批次的序号",
     *         type="integer",
     *         required=true,
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object"
     *                 )
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OneCodeErrorRespones") ) )
     * )
     */
    public function getWxaOneCodeStream(request $request)
    {
        $params = $request->input();
        $validator = app('validator')->make($params, [
            'batch_id' => 'required|min:1',
            'num' => 'required|min:1',
            // 'wxaappid' => 'required|min:1',
        ]);
        if ($validator->fails()) {
            throw new ResourceException('获取小程序码参数出错，请检查.', $validator->errors());
        }
        $weappService = new WeappService();
        $companyId = app('auth')->user()->get('company_id');
        $wxaappid = $weappService->getWxappidByTemplateName($companyId);
        if (!$wxaappid) {
            throw new ResourceException('没有开通此小程序，不能下载.', $validator->errors());
        }
        $batchsService = new BatchsService();
        $result = $batchsService->getWxaOneCodeStream($wxaappid, $params['batch_id'], $params['num']);
        return response($result)->header('content-type', 'image/jpeg');
    }
}
