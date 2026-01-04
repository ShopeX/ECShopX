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

namespace ThirdPartyBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use ThirdPartyBundle\Services\SaasErpLogService;

class SaasErp extends Controller
{
    /**
     * @SWG\Get(
     *     path="/saaserp/log/list",
     *     summary="获取saasErp通信日志列表",
     *     tags={"ShopexErp"},
     *     description="获取saasErp通信日志列表",
     *     operationId="getLogList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="页码", required=true, type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页数量", required=true, type="string"),
     *     @SWG\Parameter( name="status", in="query", description="状态", required=true, type="string"),
     *     @SWG\Parameter( name="api_type", in="query", description="类型", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="status", type="stirng"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
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

        $saasErpLogService = new SaasErpLogService();
        $result = $saasErpLogService->lists($filter, $page, $pageSize, $orderBy);
        return $this->response->array($result);
    }
}
