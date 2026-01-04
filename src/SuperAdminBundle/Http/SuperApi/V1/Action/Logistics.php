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

namespace SuperAdminBundle\Http\SuperApi\V1\Action;

use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;
use App\Http\Controllers\Controller as Controller;
use SuperAdminBundle\Services\LogisticsService;

class Logistics extends Controller
{
    /**
     * @SWG\Get(
     *     path="/superadmin/logistics/list",
     *     summary="获取物流公司列表",
     *     tags={"物流公司"},
     *     description="获取物流公司列表",
     *     operationId="getLogisticsList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="分页页码", required=true, type="integer"),
     *     @SWG\Parameter( name="pageSize", in="query", description="分页数量", required=true, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="30", description="自行更改字段描述"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                            ref="#/definitions/LogisticInfo"
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SuperAdminErrorResponse") ) )
     * )
     */
    public function getLogisticsList(Request $request)
    {
        $inputData = $request->input();
        $filter = [];

        $page = $inputData['page'] ?? 1;
        $pageSize = $inputData['pageSize'] ?? 20;
        $logisticsService = new LogisticsService();
        $result = $logisticsService->getLogisticsList($filter, $page, $pageSize);

        return $this->response->array($result);
    }

    /**
     * @SWG\Put(path="/superadmin/logistics",
     *   tags={"物流公司"},
     *   summary="修改物流公司信息",
     *   description="修改物流公司信息",
     *   operationId="updateLogistics",
     *   produces={"application/json"},
     *   @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *   @SWG\Parameter(
     *     in="query",
     *     name="corp_id",
     *     description="公司id",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     in="query",
     *     name="full_name",
     *     description="公司全称",
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
     *   @SWG\Parameter(
     *     in="query",
     *     name="order_sort",
     *     description="排序",
     *     type="string"
     *   ),
     *     @SWG\Parameter(
     *     in="query",
     *     name="custom",
     *     description="是否自定义",
     *     type="string"
     *   ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  ref="#/definitions/LogisticInfo"
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SuperAdminErrorResponse") ) )
     * )
     */
    public function updateLogistics(Request $request)
    {
        $params = $request->input();
        $rules = [
            'corp_id' => ['required', '物流公司id必填'],
            'full_name' => ['required', '物流公司全称必填'],
            'corp_name' => ['required', '物流公司简称必填'],
            'corp_code' => ['required', '物流公司代码必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $filter['corp_id'] = $params['corp_id'];
        unset($params['corp_id']);
        //return var_dump($params);
        $logisticsService = new LogisticsService();
        $result = $logisticsService->updateLogistics($params, $filter);

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(path="/superadmin/logistics",
     *   tags={"物流公司"},
     *   summary="创建物流公司信息",
     *   description="创建物流公司信息",
     *   operationId="createLogistics",
     *   produces={"application/json"},
     *   @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *   @SWG\Parameter(
     *     in="query",
     *     name="full_name",
     *     description="公司全称",
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
     *   @SWG\Parameter(
     *     in="query",
     *     name="order_sort",
     *     description="排序",
     *     type="string"
     *   ),
     *     @SWG\Parameter(
     *     in="query",
     *     name="custom",
     *     description="是否自定义",
     *     type="string"
     *   ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  ref="#/definitions/LogisticInfo"
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SuperAdminErrorResponse") ) )
     * )
     */
    public function createLogistics(Request $request)
    {
        $params = $request->input();
        $rules = [
            'full_name' => ['required', '物流公司全称必填'],
            'corp_name' => ['required', '物流公司简称必填'],
            'corp_code' => ['required', '物流公司代码必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $logisticsService = new LogisticsService();
        $result = $logisticsService->createLogistics($params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Delete(
     *     path="/superadmin/logistics/{id}",
     *     summary="删除物流公司信息",
     *     tags={"物流公司"},
     *     description="删除物流公司信息",
     *     operationId="deleteLogistics",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="id", in="path", description="物流公司id", required=true, type="integer"),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SuperAdminErrorResponse") ) )
     * )
     */
    public function deleteLogistics($id)
    {
        if (!$id) {
            throw new ResourceException('未选择数据');
        }

        $logisticsService = new LogisticsService();
        $logisticsService->deleteLogistics($id);

        return $this->response->noContent();
    }
    /**
     * @SWG\Put(
     *     path="/superadmin/logistics/del",
     *     summary="多选删除物流公司信息",
     *     tags={"物流公司"},
     *     description="多选删除物流公司信息",
     *     operationId="batchdeleteLogistics",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="corp_ids", in="query", description="物流公司id(json)", required=true, type="string"),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SuperAdminErrorResponse") ) )
     * )
     */
    public function batchdeleteLogistics(Request $request)
    {
        if (!$request->get('corp_ids')) {
            throw new ResourceException('未指定物流公司');
        }

        $input_data = $request->input();
        $corp_ids = $input_data['corp_ids'];

        if (!is_array($corp_ids)) {
            $corp_ids = json_decode($corp_ids, true);
        }
        $rules = [
            'corp_ids.*.corp_id' => ['required', '物流公司id必填'],
        ];
        $errorMessage = validator_params($input_data, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $logisticsService = new LogisticsService();
        $logisticsService->batchdeleteLogistics($corp_ids);
        return $this->response->noContent();
    }

    /**
     * @SWG\get(
     *     path="/superadmin/logistics/init",
     *     summary="初始化物流公司信息",
     *     tags={"物流公司"},
     *     description="初始化物流公司信息",
     *     operationId="initLogistics",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="1", description="操作成功"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SuperAdminErrorResponse") ) )
     * )
     */
    public function initLogistics()
    {
        try {
            $json_str = file_get_contents(storage_path('static/kuaidi.json'));
        } catch (\Exception $e) {
            throw new ResourceException('文件不存在！');
        }

        $corp = json_decode($json_str, true);

        if (empty($corp)) {
            throw new ResourceException('文件无数据！');
        }
        $logisticsService = new LogisticsService();

        $logisticsService->clearLogistics();

        foreach ($corp as $v) {
            if ($v['corp_code'] == '' || $v['corp_name'] == '') {
                continue;
            }
            $data = [
                'corp_code' => $v['corp_code'],
                'kuaidi_code' => $v['kuaidi_code'],
                'full_name' => $v['corp_name'],
                'corp_name' => $v['corp_name'],
                'custom' => "false"
            ];
            $logisticsService->createLogistics($data);
        }

        return $this->response->array(['status' => 1]);
    }

    /**
     * @SWG\Definition(
     *     definition="LogisticInfo",
     *     description="物流公司信息",
     *     type="object",
     *     @SWG\Property( property="corp_id", type="string", example="569", description="物流公司ID"),
     *     @SWG\Property( property="corp_code", type="string", example="ZY_FY", description="物流公司代码"),
     *     @SWG\Property( property="kuaidi_code", type="string", example="shipgce", description="快递100代码"),
     *     @SWG\Property( property="full_name", type="string", example="飞洋快递", description="物流公司全名"),
     *     @SWG\Property( property="corp_name", type="string", example="飞洋快递", description="物流公司简称"),
     *     @SWG\Property( property="order_sort", type="string", example="99", description="排序"),
     *     @SWG\Property( property="custom", type="string", example="false", description="是否自定义"),
     *     @SWG\Property( property="created", type="string", example="1602484935", description="创建时间"),
     *     @SWG\Property( property="updated", type="string", example="1602484935", description="修改时间"),
     * )
     */
}
