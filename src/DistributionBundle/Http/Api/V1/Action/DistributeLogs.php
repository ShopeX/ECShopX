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

namespace DistributionBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;

use DistributionBundle\Services\DistributeLogsService;
use DistributionBundle\Services\DistributeCountService;

use Dingo\Api\Exception\ResourceException;

class DistributeLogs extends Controller
{
    /**
     * @SWG\Get(
     *     path="/distribution/logs",
     *     summary="获取佣金记录",
     *     tags={"店铺"},
     *     description="获取佣金记录",
     *     operationId="getDistributeLogs",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页的数量", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="分页页数", required=true, type="string"),
     *     @SWG\Parameter( name="distributor_id", in="query", description="分销商id，如果有值则返回指定分销商的记录", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="list", type="stirng"),
     *                     @SWG\Property(property="total_count", type="stirng"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function getDistributeLogs(Request $request)
    {
        $params = $request->all('pageSize', 'page');

        $rules = [
            'page' => ['required|integer|min:1', trans('DistributionBundle/Controllers/DistributeLogs.page_error')],
            'pageSize' => ['required|integer|min:1|max:50', trans('DistributionBundle/Controllers/DistributeLogs.pagesize_max')],
        ];

        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $companyId = app('auth')->user()->get('company_id');
        $filter['company_id'] = $companyId;

        if ($request->get('distributor_id', 0)) {
            $filter['distributor_id'] = $request->get('distributor_id');
        }

        if ($request->input('is_close', false)) {
            $filter['is_close'] = $request->input('is_close') == 'true' ? true : false;
        }

        $distributeLogsService = new DistributeLogsService();
        $data = $distributeLogsService->lists($filter, ["create_time" => "DESC"], $params['pageSize'], $params['page']);

        return $this->response->array($data);
    }

    /**
     * @SWG\Get(
     *     path="/distribution/count",
     *     summary="获取分销统计",
     *     tags={"店铺"},
     *     description="获取分销统计",
     *     operationId="getCompanyCount",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="cashWithdrawalRebate", type="stirng", example="可提现佣金 单位为分"),
     *                 @SWG\Property(property="freezeCashWithdrawalRebate", type="stirng", example="申请提现佣金，冻结提现佣金"),
     *                 @SWG\Property(property="itemTotalPrice", type="stirng", example="分销商品总金额"),
     *                 @SWG\Property(property="noCloseRebate", type="stirng", example="未结算佣金"),
     *                 @SWG\Property(property="rebateTotal", type="stirng", example="分销佣金总金额"),
     *             )
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function getCompanyCount(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $distributeCountService = new DistributeCountService();
        $data = $distributeCountService->getCount($companyId);
        return $this->response->array($data);
    }
}
