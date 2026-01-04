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
use HfPayBundle\Services\HfpayLedgerConfigService;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;

class HfpayLedgerConfig extends Controller
{
    /**
     * @SWG\Get(
     *     path="/hfpay/ledgerconfig/index",
     *     summary="获取分账配置",
     *     tags={"汇付天下"},
     *     description="获取分账配置",
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
     *                     @SWG\Property(property="hfpay_ledger_config_id", type="integer", description="配置ID"),
     *                     @SWG\Property(property="company_id", type="integer", description="公司id"),
     *                     @SWG\Property(property="is_open", type="string", description="是否开启"),
     *                     @SWG\Property(property="business_type", type="string", description="业务模式"),
     *                     @SWG\Property(property="agent_number", type="string", description="代理商户号"),
     *                     @SWG\Property(property="provider_number", type="string", description="服务商渠道号"),
     *                     @SWG\Property(property="app_id", type="string", description="appid"),
     *                     @SWG\Property(property="rate", type="string", description="费率"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/HfPayErrorRespones") ) )
     * )
     */
    public function index()
    {
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $hfpayLedgerConfigService = new HfpayLedgerConfigService();
        $result = $hfpayLedgerConfigService->getLedgerConfig($filter);

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/hfpay/ledgerconfig/save",
     *     summary="保存分账配置",
     *     tags={"汇付天下"},
     *     description="保存分账配置",
     *     operationId="save",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="is_open", in="query", description="是否开启分账", required=true, type="string"),
     *     @SWG\Parameter( name="business_type", in="query", description="业务模式", required=true, type="string"),
     *     @SWG\Parameter( name="agent_number", in="query", description="代理商户号", type="string"),
     *     @SWG\Parameter( name="provider_number", in="query", description="服务商渠道号", type="string"),
     *     @SWG\Parameter( name="app_id", in="query", description="appid", type="string"),
     *     @SWG\Parameter( name="rate", in="query", description="费率", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                   @SWG\Property(property="hfpay_ledger_config_id", type="integer", description="配置ID"),
     *                   @SWG\Property(property="company_id", type="integer", description="公司id"),
     *                   @SWG\Property(property="is_open", type="string", description="是否开启"),
     *                   @SWG\Property(property="business_type", type="string", description="业务模式"),
     *                   @SWG\Property(property="agent_number", type="string", description="代理商户号"),
     *                   @SWG\Property(property="provider_number", type="string", description="服务商渠道号"),
     *                   @SWG\Property(property="app_id", type="string", description="appid"),
     *                   @SWG\Property(property="rate", type="string", description="费率"),
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
            'rate' => ['required', '费率必填'],
            'agent_number' => ['required_if:business_type,2', '代理商商户号必填'],
            'provider_number' => ['required_if:business_type,2', '服务商渠道号必填'],
            'app_id' => ['required_if:business_type,2', '小程序appID必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $hfpayLedgerConfigService = new HfpayLedgerConfigService();
        $filter['company_id'] = $companyId;
        $data = $hfpayLedgerConfigService->getLedgerConfig($filter);
        if ($data) {
            $params['hfpay_ledger_config_id'] = $data['hfpay_ledger_config_id'];
        }
        $result = $hfpayLedgerConfigService->saveConfig($params);

        return $this->response->array($result);
    }
}
