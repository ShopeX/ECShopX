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

use DistributionBundle\Services\selfDeliveryService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;


class selfDelivery extends Controller
{
    /**
     * @SWG\Post(
     *     path="/distributor/selfdelivery/setting",
     *     summary="商家自配送配置信息保存",
     *     tags={"订单"},
     *     description="商家自配送配置信息保存",
     *     operationId="setSelfDeliverySetting",
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
    public function setSelfDeliverySetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');

        $distributorId = app('auth')->user()->get('distributor_id');

        $config = $request->input();
        $service = new selfDeliveryService();
        $service->setSelfDeliverySetting($companyId, $distributorId, $config);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/distributor/selfdelivery/setting",
     *     summary="获取商家自配送配置信息",
     *     tags={"订单"},
     *     description="获取商家自配送配置信息",
     *     operationId="getSelfDeliverySetting",
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
    public function getSelfDeliverySetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $distributorId = app('auth')->user()->get('distributor_id');
        $distributor_id = $request->input('distributor_id','');
        if($distributor_id){
            $distributorId = $distributor_id;
        }
        $service = new selfDeliveryService();

        $data = $service->getSelfDeliverySetting($companyId,$distributorId);

        return $this->response->array($data);
    }
}
