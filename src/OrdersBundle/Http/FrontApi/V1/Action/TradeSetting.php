<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OrdersBundle\Http\FrontApi\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use OrdersBundle\Services\TradeSetting\BasicService;
use OrdersBundle\Services\TradeSettingService;
use CompanysBundle\Services\SettingService;

class TradeSetting extends Controller
{
    /**
     * @SWG\Get(
     *     path="/wxapp/trade/setting",
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
     *                    @SWG\Property(property="merchant_id", type="string", description="商户ID"),
     *                    @SWG\Property(property="key", type="string", description="密钥"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function getSetting(Request $request)
    {
        $authInfo = $request->get('auth');
        $companyId = $authInfo['company_id'];
        $service = new TradeSettingService(new BasicService());

        $data = $service->getSetting($companyId);

        //获取储值配置
        $settingService = new SettingService();
        $rechargeOpen = $settingService->getRechargeSetting($companyId);
        $data['is_recharge_status'] = $rechargeOpen['recharge_status'];

        return $this->response->array($data);
    }
}
