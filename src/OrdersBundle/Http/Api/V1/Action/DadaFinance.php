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
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use OrdersBundle\Services\LocalDeliveryService;

class DadaFinance extends Controller
{
    /**
     * @SWG\Get(
     *     path="/dada/finance/info",
     *     summary="获取达达账户余额",
     *     tags={"订单"},
     *     description="获取达达账户余额",
     *     operationId="queryBalance",
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="deliverBalance", type="int", example="9973295.41", description="运费账户余额"),
     *               @SWG\Property(property="redPacketBalance", type="int", example="381", description="红包账户余额"),
     *               ),
     *            ),
     *         ),
     *     ),
     * )
     */
    public function queryBalance()
    {
        $company_id = app('auth')->user()->get('company_id');
        $localDeliveryService = new LocalDeliveryService();
        $result = $localDeliveryService->getBalanceService()->query($company_id);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/dada/finance/create",
     *     summary="获取充值链接",
     *     tags={"订单"},
     *     description="获取充值链接",
     *     operationId="recharge",
     *     @SWG\Parameter( name="amount", in="query", description="充值金额", required=true, type="number"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="link", type="string", example="https://testpdjm.jd.com/activity/pcCashierNew/index.html?payToken=wpWePawTlQxFdXVqOGZlIk7LrGsOyMw9DlcEXqBw+qUbYqWS9JGhCbsFJzJf8VQBkwDAG1SjkGpj0kFqYGRyFW+ykB2DcyXY5/QbMcMZSy5cKNO9yATVHloCwLWB/kUv4DXnM7Lc6l1grb7ABVqx2KjGEhrQkCfmqxco7Zs7KlM=", description="充值链接"),
     *               ),
     *            ),
     *         ),
     *     ),
     * )
     */
    public function recharge(Request $request)
    {
        $params = $request->all('amount');
        $url = $request->url();
        if (strstr($url, 'index.php/')) {
            $url = str_replace('index.php/', '', $url);
        }
        $urlPath = $request->path();
        $rules = [
            'amount' => 'required',
        ];
        $msg = [
            'amount.required' => '充值金额必填',
        ];
        $validator = app('validator')->make($params, $rules, $msg);
        if ($validator->fails()) {
            $errorsMsg = $validator->errors()->toArray();
            $errmsg = current($errorsMsg)[0];
            throw new ResourceException($errmsg);
        }
        $path = 'financial/distribution/dada';
        $notify_url = str_replace($urlPath, $path, $url);
        $localDeliveryService = new LocalDeliveryService();
        $data = [
            'amount' => $params['amount'],
            'category' => 'PC',
            'notify_url' => $notify_url,
        ];
        $company_id = app('auth')->user()->get('company_id');
        $result = $localDeliveryService->getRechargeService()->recharge($company_id, $data);
        $res['link'] = $result;
        return $this->response->array($res);
    }
}
