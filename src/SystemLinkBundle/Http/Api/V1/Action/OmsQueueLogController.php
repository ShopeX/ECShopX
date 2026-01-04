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

namespace SystemLinkBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use SystemLinkBundle\Services\OmsQueueLogService;

class OmsQueueLogController extends Controller
{
    /**
     * @SWG\Get(
     *     path="/omsqueuelog",
     *     summary="获取oms通信日志列表",
     *     tags={"oms"},
     *     description="获取oms通信日志列表",
     *     operationId="getLogList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="页码", default="1", required=true, type="integer"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页数量", default="20", required=true, type="integer"),
     *     @SWG\Parameter( name="status", in="query", description="状态", required=false, type="string"),
     *     @SWG\Parameter( name="api_type", in="query", description="类型", required=false, type="string"),
     *     @SWG\Parameter( name="content", in="query", description="接口参数", required=false, type="string"),
     *     @SWG\Parameter( name="updated[]", in="query", description="接口请求时间(Y-m-d)", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="8078", description="总数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="id", type="string", example="8078", description="ID"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="api_type", type="string", example="response", description="日志同步类型, response:响应，request:请求"),
     *                          @SWG\Property( property="worker", type="string", example="store.items.quantity.list.update", description="api"),
     *                          @SWG\Property( property="params", type="string", example="{json}", description="请求参数(DC2Type:json_array)"),
     *                          @SWG\Property( property="result", type="string", example="{json}", description="返回数据(DC2Type:json_array)"),
     *                          @SWG\Property( property="status", type="string", example="success", description="状态"),
     *                          @SWG\Property( property="runtime", type="string", example="null", description="运行时间(秒)"),
     *                          @SWG\Property( property="msg_id", type="string", example="null", description="msg_id"),
     *                          @SWG\Property( property="created", type="string", example="1613643949", description="创建时间"),
     *                          @SWG\Property( property="updated", type="string", example="1613643949", description="修改时间"),
     *                          @SWG\Property( property="created_date", type="string", example="2021-02-18 21:25:49", description="创建时间"),
     *                          @SWG\Property( property="updated_date", type="string", example="2021-02-18 21:25:49", description="修改时间"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SystemLinkErrorResponse") ) )
     * )
     */
    public function getLogList(Request $request)
    {
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 20);
        $orderBy = ['created' => 'desc'];
        $filter['company_id'] = app('auth')->user()->get('company_id');
        if ($request->get('api_type')) {
            $filter['api_type'] = $request->get('api_type');
        }
        if ($request->get('status')) {
            $filter['status'] = $request->get('status');
        }

        if ($request->get('content')) {
            $filter['params|contains'] = $request->get('content');
        }

        if ($request->get('updated')) {
            list($startDate, $endDate) = $request->get('updated');
            $filter['updated|lte'] = strtotime($endDate." 23:59:59");
            $filter['updated|gte'] = strtotime($startDate." 00:00:00");
        }

        $omsQueueLogService = new OmsQueueLogService();
        $result = $omsQueueLogService->lists($filter, $page, $pageSize, $orderBy);
        return $this->response->array($result);
    }
}
