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

namespace AdaPayBundle\Http\Api\V1\Action;

use AdaPayBundle\Services\AdapayLogService;
use App\Http\Controllers\Controller;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;

class AdapayLog extends Controller
{
    /**
     * @SWG\Get(
     *     path="/adapay/log/list",
     *     summary="操作日志列表",
     *     tags={"Adapay"},
     *     description="操作日志列表",
     *     operationId="getList",
     *     @SWG\Parameter(name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter(name="log_type", in="query", type="string", description="日志类型：merchant-主商户;distributor-店铺;dealer-经销", required=true),
     *     @SWG\Parameter(name="page", in="query", type="string", description="页码", required=true),
     *     @SWG\Parameter(name="page_size", in="query", type="string", description="一页数据数", required=true),
     *     @SWG\Parameter(name="operator_id", in="query", type="string", description="操作者ID,经销商日志传", required=false),
     *     @SWG\Parameter(name="distributor_id", in="query", type="string", description="分店ID,店铺日志传", required=false),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             required={"data"},
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                    required={"total_count","list"},
     *                    @SWG\Property(property="total_count", type="integer", description="总记录条数"),
     *                    @SWG\Property(property="list", type="array", description="明细数据",
     *                           @SWG\Items(
     *                              @SWG\Property(property="content", type="string", description="内容"),
     *                              @SWG\Property(property="create_time", type="integer", description="日志创建时间戳"),
     *                              @SWG\Property(property="create_date", type="string", description="日志创建时间"),
     *                           ),
     *                    ),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AdaPayErrorResponse") ) )
     * )
     */
    public function getList(Request $request)
    {
        $inputData = $request->all();

        $auth = app('auth')->user()->get();
        $inputData['company_id'] = $auth['company_id'];
        $rules = [
            'log_type' => ['required|in:merchant,distributor,dealer', '日志类型必填'],
            'page' => ['required|integer|min:1', '页码必填'],
            'page_size' => ['required|integer|min:1', '页条数必填'],
        ];
        $error = validator_params($inputData, $rules);
        if ($error) {
            throw new ResourceException($error);
        }
        $result = (new AdapayLogService())->logList($inputData);

        return $this->response->array($result);
    }
}
