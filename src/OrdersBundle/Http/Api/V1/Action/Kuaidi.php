<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OrdersBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use OrdersBundle\Services\Kuaidi\KdniaoService;
use OrdersBundle\Services\Kuaidi\Kuaidi100Service;
use OrdersBundle\Services\KuaidiService;

class Kuaidi extends Controller
{
    /**
     * @SWG\Post(
     *     path="/trade/kuaidi/setting",
     *     summary="快递配置信息保存",
     *     tags={"订单"},
     *     description="快递配置信息保存",
     *     operationId="setKuaidiSetting",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="kuaidi_type", in="query", description="快递类型", required=true, type="string"),
     *     @SWG\Parameter( name="config", in="query", description="配置信息json数据", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                    @SWG\Property(property="status", type="stirng"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function setKuaidiSetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        if ($request->input('kuaidi_type') == 'kdniao') {
            $kuaidiService = new KdniaoService();
        } elseif ($request->input('kuaidi_type') == 'kuaidi100') {
            $kuaidiService = new Kuaidi100Service();
        }
        $config = $request->get('config');
        $service = new KuaidiService($kuaidiService);
        $service->setKuaidiSetting($companyId, $config);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/trade/kuaidi/setting",
     *     summary="获取快递配置信息",
     *     tags={"订单"},
     *     description="获取快递配置信息",
     *     operationId="setPaymentSetting",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="kuaidi_type", in="query", description="支付类型", required=true, type="string"),
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
    public function getKuaidiSetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        if ($request->input('kuaidi_type') == 'kdniao') {
            $kuaidiService = new KdniaoService();
        } elseif ($request->input('kuaidi_type') == 'kuaidi100') {
            $kuaidiService = new Kuaidi100Service();
        }
        $service = new KuaidiService($kuaidiService);

        $data = $service->getKuaidiSetting($companyId);

        return $this->response->array($data);
    }
}
