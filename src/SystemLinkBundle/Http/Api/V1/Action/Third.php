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

namespace SystemLinkBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use SystemLinkBundle\Services\ThirdSettingService;

use OrdersBundle\Entities\NormalOrdersItems;

class Third extends Controller
{
    /**
     * @SWG\Post(
     *     path="/third/shopexerp/setting",
     *     summary="保存shopexerp配置信息",
     *     tags={"oms"},
     *     description="保存shopexerp配置信息",
     *     operationId="setShopexErpSetting",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="node_id", in="query", description="erp节点", required=true, type="string"),
     *     @SWG\Parameter( name="is_open", in="query", description="是否开启(true,false)", default="true", required=true, type="string"),
     *     @SWG\Parameter( name="openapi_flag", in="query", description="访问来源标识", required=true, type="string"),
     *     @SWG\Parameter( name="openapi_token", in="query", description="接口调用私钥", required=true, type="string"),
     *     @SWG\Parameter( name="is_openapi_open", in="query", description="是否启用开放数据接口(true,false)", default="true", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="操作结果"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SystemLinkErrorResponse") ) )
     * )
     */
    public function setShopexErpSetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');

        $service = new ThirdSettingService();
        $postdata = $request->input();
        $data = [
            'node_id' => trim($postdata['node_id']),
            'is_open' => (isset($postdata['is_open']) && $postdata['is_open'] == 'true') ? true : false,
            'openapi_flag' => trim($postdata['openapi_flag']),
            'openapi_token' => trim($postdata['openapi_token']),
            'is_openapi_open' => (isset($postdata['is_openapi_open']) && $postdata['is_openapi_open'] == 'true') ? true : false,
        ];
        $service->setShopexErpSetting($companyId, $data);

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/third/shopexerp/setting",
     *     summary="获取shopexerp配置信息",
     *     tags={"oms"},
     *     description="获取shopexerp配置信息",
     *     operationId="getShopexErpSetting",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="node_id", type="string", example="o2022596090", description="erp节点"),
     *                  @SWG\Property( property="is_open", type="string", example="false", description="是否开启"),
     *                  @SWG\Property( property="openapi_flag", type="string", example="shop", description="访问来源标识"),
     *                  @SWG\Property( property="openapi_token", type="string", example="IsVaOpYKUKDuKJsvWcgauuyQWIGMaHPT", description="接口调用私钥"),
     *                  @SWG\Property( property="is_openapi_open", type="string", example="false", description="是否启用开放数据接口"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SystemLinkErrorResponse") ) )
     * )
     */
    public function getShopexErpSetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');

        $service = new ThirdSettingService();
        $data = $service->getShopexErpSetting($companyId);

        return $this->response->array($data);
    }

    /**
     * 发送订单到oms
     */
    public function sendOrderToOms_test(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        // 判断是否开启OME
        $orderId = $request->input('order_id');

        // 判断是否开启OME
        $service = new \SystemLinkBundle\Services\ThirdSettingService();
        $data = $service->getShopexErpSetting($companyId);
        $orderService = new \SystemLinkBundle\Services\ShopexErp\OrderService();

        $orderStruct = $orderService->getOrderStruct($companyId, $orderId, 'normal');
        $orderStruct['custom_mark'] = '异常订单，手动操作更新';
        $orderStruct['lastmodify'] = date('Y-m-d H:i:s');
        if ($orderStruct) {
            $omeRequest = new \SystemLinkBundle\Services\ShopexErp\Request($companyId);
            $method = 'ome.order.add';
            $result = $omeRequest->call($method, $orderStruct);
        }
        return $this->response->array($result);
    }

    public function sendOrderToOms(Request $request)
    {
        $normalOrderService = new \OrdersBundle\Services\Orders\NormalOrderService();
        $normalOrdersItemsRepository = app('registry')->getManager('default')->getRepository(NormalOrdersItems::class);
        $filter = [
            'discount_fee|gt' => 0,
            'order_status|notin' => ['CANCEL', 'NOTPAY'],
        ];
        $count = $normalOrderService->count($filter);
        $pageSize = 20;
        $totalPage = ceil($count / $pageSize);

        $result = [];
        $result1 = [];
        for ($i = 0; $i < $totalPage; $i++) {
            $orderlist = $normalOrderService->getList($filter, $i, $pageSize);
            foreach ($orderlist as $order) {
                $totalDiscountFee = $order['discount_fee'];
                $ifilter = [
                    'discount_fee|gt' => 0,
                    'order_item_type' => 'normal',
                    'order_id' => $order['order_id'],
                ];
                $orderBy = ['item_fee' => 'asc', 'item_id' => 'asc'];
                $orderitemlist = $normalOrdersItemsRepository->getList($ifilter, 0, -1, $orderBy);
                $discountfeeArr = array_column($orderitemlist['list'], 'discount_fee');
                if (count($orderitemlist['list']) > 1 && intval($totalDiscountFee) != intval(array_sum($discountfeeArr))) {
                    $lastdata = [];
                    $discountFee = [];
                    foreach ($orderitemlist['list'] as $k => $orderitem) {
                        if ($discountFee && $k == count($orderitemlist['list']) - 1) {
                            $discountFee[$k] = bcsub($totalDiscountFee, array_sum($discountFee));
                        } else {
                            $discountFee[$k] = $orderitem['discount_fee'];
                        }
                        $lastdata['total_fee'] = intval(bcsub($orderitem['item_fee'], $discountFee[$k]));
                        $lastdata['discount_fee'] = intval($discountFee[$k]);
                        $ufilter = [
                            'order_id' => $orderitem['order_id'],
                            'id' => $orderitem['id'],
                        ];
                        $normalOrdersItemsRepository->update($ufilter, $lastdata);
                        $result[$order['order_id']][] = [
                            'update' => $lastdata,
                            'filter' => $ufilter,
                        ];
                    }
                }
                $orderService = new \SystemLinkBundle\Services\ShopexErp\OrderService();
                $orderStruct = $orderService->getOrderStruct($order['company_id'], $order['order_id'], 'normal');
                $orderStruct['custom_mark'] = '异常订单，手动操作更新';
                $orderStruct['lastmodify'] = date('Y-m-d H:i:s');
                if ($orderStruct) {
                    $omeRequest = new \SystemLinkBundle\Services\ShopexErp\Request($order['company_id']);
                    $method = 'ome.order.add';
                    $result1[] = $omeRequest->call($method, $orderStruct);
                }
            }
        }
        return $this->response->array([$result1, $result]);
    }
}
