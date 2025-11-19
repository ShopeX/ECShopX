<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
