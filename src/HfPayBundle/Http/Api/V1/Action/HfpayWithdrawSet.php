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

namespace HfPayBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as Controller;
use HfPayBundle\Services\HfpayWithdrawSetService;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;

class HfpayWithdrawSet extends Controller
{
    /**
     * @SWG\Post(
     *     path="/hfpay/getwithdrawset",
     *     summary="获取提现设置",
     *     tags={"汇付天下"},
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
     *                     @SWG\Property(property="hfpay_withdraw_set_id", type="int", description="汇付提现配置表id"),
     *                     @SWG\Property(property="withdraw_method", type="integer", description="提现方式 1自动提现 2手动提现"),
     *                     @SWG\Property(property="distributor_money", type="string", description="店铺账号提现金额"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/HfPayErrorRespones") ) )
     * )
     */
    public function index()
    {
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $hfpayService = new HfpayWithdrawSetService();
        $result = $hfpayService->getWithdrawSet($filter);

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/hfpay/savewithdrawset",
     *     summary="保存提现设置",
     *     tags={"汇付天下"},
     *     description="保存提现设置",
     *     operationId="save",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter(name="withdraw_method", in="query", type="integer", description="提现方式 1自动提现 2手动提现", required=true),
     *     @SWG\Parameter(name="distributor_money", in="query", type="string", description="店铺账号提现金额"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                    @SWG\Property(property="hfpay_withdraw_set_id", type="int", description="汇付提现配置表id"),
     *                    @SWG\Property(property="withdraw_method", type="integer", description="提现方式 1自动提现 2手动提现"),
     *                    @SWG\Property(property="distributor_money", type="string", description="店铺账号提现金额"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/HfPayErrorRespones") ) )
     * )
     */
    public function save(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $params = $request->all();
        $params['company_id'] = $companyId;

        $rules = [
            'company_id' => ['required', '企业id必填'],
            'withdraw_method' => ['required', '请选择提现方式'],
            'distributor_money' => ['required', '店铺账号提现金额必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $hfpayService = new HfpayWithdrawSetService();
        $filter['company_id'] = $companyId;
        $data = $hfpayService->getWithdrawSet($filter);
        if ($data) {
            $params['hfpay_withdraw_set_id'] = $data['hfpay_withdraw_set_id'];
        }
        $result = $hfpayService->saveWithdrawSet($params);

        return $this->response->array($result);
    }
}
