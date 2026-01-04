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
use App\Http\Controllers\Controller as Controller;
use AdaPayBundle\Services\AdapayWithdrawSetService;
use Dingo\Api\Exception\ResourceException;
use DistributionBundle\Services\DistributorService;
use Illuminate\Http\Request;

class AdapayWithdrawSet extends Controller
{
    /**
     * @SWG\Get(
     *     path="/adapay/withdrawset",
     *     summary="获取提现设置",
     *     tags={"Adapay"},
     *     description="获取提现设置",
     *     operationId="index",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                     @SWG\Property(property="id", type="integer", description="汇付提现配置表id"),
     *                     @SWG\Property(property="rule", type="string", description="提现规则"),
     *                     @SWG\Property(property="isAuto", type="boolean", description="是否开启自动提现"),
     *                     @SWG\Property(property="cash_type", type="string", description="提现类型"),
     *                     @SWG\Property(property="cash_amt", type="string", description="提现金额"),
     *                     @SWG\Property(property="cash_types", type="string", description="提现类型列表"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AdaPayErrorResponse") ) )
     * )
     */
    public function index()
    {
        $cash_types = ["T0", "T1", "D1"]; //提现类型后面再定义
        $auth = app('auth')->user()->get();
        $distributor_id = $auth['distributor_id'];
        $company_id = $auth['company_id'];
        if (empty($distributor_id)) {
            throw new ResourceException('请选择店铺');
        }
        $filter['company_id'] = $company_id;
        $filter['distributor_id'] = $distributor_id;
        $withdrawService = new AdapayWithdrawSetService();
        $result = $withdrawService->getWithdrawSet($filter);
        $result['cash_types'] = $cash_types;
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/adapay/withdrawset",
     *     summary="保存提现设置",
     *     tags={"Adapay"},
     *     description="保存提现设置",
     *     operationId="save",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter(name="isAuto", in="query", type="boolean", description="是否支持自动提现", required=true),
     *     @SWG\Parameter(name="rule[type]", in="query", type="string", description="规则类型: month;day/limit_amount", required=true),
     *     @SWG\Parameter(name="rule[filter][month]", in="query", type="string", description="每月提取时间", required=false),
     *     @SWG\Parameter(name="rule[filter][day]", in="query", type="string", description="每日提取时间", required=false),
     *     @SWG\Parameter(name="rule[filter][amount]", in="query", type="integer", description="余额限制", required=false),
     *     @SWG\Parameter(name="cash_amt", in="query", type="string", description="提现金额"),
     *     @SWG\Parameter(name="cash_type", in="query", type="string", description="提现类型"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="status", type="boolean")
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AdaPayErrorResponse") ) )
     * )
     */
    public function save(Request $request)
    {
        $auth = app('auth')->user()->get();
        $distributor_id = $auth['distributor_id'];
        if (empty($distributor_id)) {
            throw new ResourceException('请选择店铺');
        }
        $params = $request->input();
        $companyId = $auth['company_id'];
        $params['company_id'] = $companyId;
        $distributorId = $params['distributor_id'];
        $rules = [
            'isAuto' => ['required|in:true,false',"是否开启自动提现"],
            'rule.type' => ["required_if:isAuto,true", '规则类型必填'],
            'cash_amt' => ["required_if:isAuto,true | numeric | min:0", '店铺账号提现金额'],
            'cash_type' => ["required_if:isAuto,true", '提现类型'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $withdrawService = new AdapayWithdrawSetService();
        $filter['company_id'] = $companyId;
        $filter['distributor_id'] = $distributorId;
        $data = $withdrawService->getWithdrawSet($filter);
        if ($data) {
            $params['id'] = $data['id'];
        }
        $result = $withdrawService->saveWithdrawSet($params);

        $distributorService = new DistributorService();
        $distributorInfo = $distributorService->getInfo(['company_id' => $companyId, 'distributor_id' => $distributorId]);
        $logParams = [
            'company_id' => $companyId,
            'name' => $distributorInfo['name']
        ];
        $relId = $auth['operator_id'];
        (new AdapayLogService())->logRecord($logParams, $relId, 'withdrawset', 'merchant');

        return $this->response->array(['status' => true]);
    }
}
