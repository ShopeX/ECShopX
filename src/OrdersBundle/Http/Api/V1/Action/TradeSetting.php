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

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use OrdersBundle\Services\TradeSetting\BasicService;
use OrdersBundle\Services\TradeSetting\CancelService;
use OrdersBundle\Services\TradeSettingService;

class TradeSetting extends Controller
{
    /**
     * @SWG\Post(
     *     path="/trade/setting",
     *     summary="交易配置信息保存",
     *     tags={"订单"},
     *     description="交易配置信息保存",
     *     operationId="setSetting",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="pay_type", in="query", description="快递类型", required=true, type="string"),
     *     @SWG\Parameter( name="config", in="query", description="配置信息json数据", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                     @SWG\Property(property="status", type="stirng"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function setSetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $config = $request->get('config');
        $service = new TradeSettingService(new BasicService());
        $service->setSetting($companyId, $config);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/trade/setting",
     *     summary="获取配置信息保存",
     *     tags={"订单"},
     *     description="获取配置信息保存",
     *     operationId="getSetting",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="pay_type", in="query", description="支付类型", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                     @SWG\Property(property="merchant_id", type="stirng", description="商户ID"),
     *                     @SWG\Property(property="key", type="stirng", description="密钥"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function getSetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $service = new TradeSettingService(new BasicService());

        $data = $service->getSetting($companyId);

        return $this->response->array($data);
    }

    /**
     * @SWG\Post(
     *     path="/trade/cancel/setting",
     *     summary="取消订单配置信息保存",
     *     tags={"订单"},
     *     description="取消订单配置信息保存",
     *     operationId="setCancelSetting",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                     @SWG\Property(property="status", type="stirng", description="状态 true"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function setCancelSetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $params = $request->all('repeat_cancel');
        $params['repeat_cancel'] = $params['repeat_cancel'] == 'true' ? true : false;
        $service = new TradeSettingService(new CancelService());
        $service->setSetting($companyId, $params);

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/trade/cancel/setting",
     *     summary="获取取消订单配置信息",
     *     tags={"订单"},
     *     description="获取取消订单配置信息",
     *     operationId="getCancelSetting",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                     @SWG\Property(property="repeat_cancel", type="stirng", description="状态 true"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function getCancelSetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $service = new TradeSettingService(new CancelService());
        $result = $service->getSetting($companyId);
        $result['repeat_cancel'] = $result['repeat_cancel'] ?? false;

        return $this->response->array($result);
    }
}
