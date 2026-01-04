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

namespace CompanysBundle\Http\FrontApi\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use CompanysBundle\Services\CurrencyExchangeRateService;

class CurrencyController extends BaseController
{
    private $currencyExchangeRate;

    public function __construct(CurrencyExchangeRateService $currencyExchangeRateService)
    {
        $this->currencyExchangeRate = new $currencyExchangeRateService();
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/currencyGetDefault",
     *     summary="获取默认货币配置",
     *     tags={"企业"},
     *     description="获取默认货币配置",
     *     operationId="getDefaultCurrency",
     *     @SWG\parameter( name="authorization", in="header", description="jwt验证token", type="string", required=true),
     *     @SWG\response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\schema(
     *             @SWG\property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\items(
     *                     type="object",
     *                     @SWG\property(property="id", type="integer", example="1"),
     *                     @SWG\property(property="company_id", type="integer", example="1"),
     *                     @SWG\property(property="currency", type="integer", example="rmb"),
     *                     @SWG\property(property="title", type="string", example="人民币"),
     *                     @SWG\property(property="symbol", type="string", example="￥"),
     *                     @SWG\property(property="rate", type="string", example="1"),
     *                     @SWG\property(property="is_default", type="string", example="1"),
     *                 ),
     *             ),
     *          ),
     *     ),
     *     @SWG\response( response="default", description="错误返回结构", @SWG\schema( type="array", @SWG\items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function getDefaultCurrency(Request $request)
    {
        $authInfo = $request->get('auth');
        $companyId = $authInfo['company_id'];
        $result = $this->currencyExchangeRate->getDefaultCurrency($companyId);
        return $this->response->array($result);
    }
}
