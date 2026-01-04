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

namespace OrdersBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as Controller;

use CompanysBundle\Services\SettingService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class Fapiao extends Controller
{
    /**
     * @SWG\Get(
     *     path="/fapiao/getFapiaoset",
     *     summary="获取企业发票配置",
     *     tags={"订单"},
     *     description="获取企业发票配置",
     *     operationId="getSetting",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(property="data", type="stirng", description="发票配置信息"),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function getFapiaoset(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');

        $settingService = new SettingService();
        $setting = $settingService->getInfo(['company_id' => $companyId]);
        app('log')->debug("\n"."-".__FUNCTION__."-".__LINE__. "-=>".json_encode($setting));

        $result = array();
        $result = isset($setting['fapiao_config']) ? ($setting['fapiao_config']) : array();
        // $result['fapiao_config'] = isset($setting['fapiao_config']) ?: array();
        // $result['fapiao_switch'] = $setting['fapiao_switch'];

        app('log')->debug("\n"."-".__FUNCTION__."-".__LINE__. "-=>".json_encode($result));

        return $this->response->array($result);
    }


    /**
     * @SWG\Post(
     *     path="/fapiao/saveFapiaoset",
     *     summary="配置企业发票",
     *     tags={"订单"},
     *     description="配置企业发票",
     *     operationId="setSetting",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="fapiao_config", in="query", description="企业发票配置", required=false, type="string"),
     *     @SWG\Parameter( name="fapiao_switch", in="query", description="企业发票开关", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="objetc",
     *                     @SWG\Property(property="company_id", type="stirng", description="公司id"),
     *                     @SWG\Property(property="community_config", type="stirng", description="社区相关设置"),
     *                     @SWG\Property(property="withdraw_bank", type="stirng", description="提现支持银行类型"),
     *                     @SWG\Property(property="consumer_hotline", type="stirng", description="客服电话"),
     *                     @SWG\Property(property="created", type="stirng", description="创建时间"),
     *                     @SWG\Property(property="updated", type="stirng", description="修改时间"),
     *                     @SWG\Property(property="customer_switch", type="stirng", description="客服开关"),
     *                     @SWG\Property(property="fapiao_config", type="stirng", description="发票相关设置"),
     *                     @SWG\Property(property="fapiao_switch", type="stirng", description="发票开关"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function saveFapiaoset(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');

        $settingService = new SettingService();

        $data_in = $request->all();
        app('log')->debug("\n"."-".__FUNCTION__."-".__LINE__. "-=>".json_encode($data_in));
        // 'fapiap_config', 'fapiao_switch'

        $data = array();
        $data['fapiao_config'] = $data_in;
        $data['fapiao_switch'] = $data_in['fapiao_switch'];
        app('log')->debug("\n"."-".__FUNCTION__."-".__LINE__. "-=>".json_encode($data));

        $setting = $settingService->getInfo(['company_id' => $companyId]);
        app('log')->debug("\n"."-".__FUNCTION__."-".__LINE__. "-=>".json_encode($setting));

        if ($setting) {
            $result = $settingService->updateOneBy(['company_id' => $companyId], $data);
            app('log')->debug("\n"."-".__FUNCTION__."-".__LINE__. "-=>".json_encode($result));
        } else {
            $data['company_id'] = $companyId;
            $result = $settingService->create($data);
        }
        return $this->response->array($result);
    }
}
