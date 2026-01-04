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

namespace OpenapiBundle\Http\ThirdApi\V1\Action;

use Illuminate\Http\Request;

use OpenapiBundle\Http\Controllers\Controller as Controller;

// use OrdersBundle\Services\OrderAssociationService;

use OrdersBundle\Services\Orders\NormalOrderService;
use OrdersBundle\Services\OrderService;

use OrdersBundle\Traits\GetOrderServiceTrait;

// use OrdersBundle\Jobs\OrderDeliverySendMsg;

class Delivery extends Controller
{
    use GetOrderServiceTrait;

    /**
     * 订单发货接口-A4
     */
    /**
     * @SWG\Post(
     *     path="/ecx.order.deliver",
     *     summary="订单发货",
     *     tags={"订单"},
     *     description="用于订单的发货，调用该接口后，如果同一订单的商品和数量都已经发货完成，订单状态变更为已发货。如果需要将一个订单拆分为多个包裹发货，可以将同一个订单分多次请求该接口，在item_info的数据中，传入部分商品数据或qty填写部分数量。即一次请求为一个包裹的商品数据。",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称:ecx.order.deliver" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="order_id", description="订单号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="item_info", description="发货商品:json_array格式 [{name名称 sku_id货号 qty数量}]" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="lc_code", description="快递公司编码" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="l_code", description="快递单编码" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="success", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="操作成功", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="order_id", type="string", example="3286648000220261", description="订单号"),
     *                  @SWG\Property( property="authorizer_appid", type="string", example="wx6b8c2837f47e8a09", description="appid"),
     *                  @SWG\Property( property="wxa_appid", type="string", example="wx912913df9fef6ddd", description="appid"),
     *                  @SWG\Property( property="title", type="string", example="指定标签商品...", description="标题"),
     *                  @SWG\Property( property="total_fee", type="string", example="1", description=""),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                  @SWG\Property( property="shop_id", type="string", example="0", description="店铺id"),
     *                  @SWG\Property( property="store_name", type="string", example="", description="店铺名称"),
     *                  @SWG\Property( property="user_id", type="string", example="20261", description="用户id"),
     *                  @SWG\Property( property="salesman_id", type="string", example="0", description="导购员id"),
     *                  @SWG\Property( property="promoter_user_id", type="string", example="null", description="推广员user_id"),
     *                  @SWG\Property( property="promoter_shop_id", type="string", example="0", description="推广员店铺id，实际为推广员的user_id"),
     *                  @SWG\Property( property="source_id", type="string", example="14", description="来源id"),
     *                  @SWG\Property( property="monitor_id", type="string", example="38", description="监控id"),
     *                  @SWG\Property( property="mobile", type="string", example="15121097923", description="手机号"),
     *                  @SWG\Property( property="order_class", type="string", example="normal", description="订单种类。可选值有 normal:普通订单;groups:拼团订单;;community 社区活动订单;bargain:助力订单;seckill:秒杀订单;shopguide:导购订单"),
     *                  @SWG\Property( property="order_type", type="string", example="normal", description="订单类型。可选值有 service 服务业订单;bargain 砍价订单;distribution 分销订单;normal 普通实体订单"),
     *                  @SWG\Property( property="order_status", type="string", example="PAYED", description="订单状态,可选值有 DONE—订单完成;NOTPAY—未支付;CANCEL—已取消"),
     *                  @SWG\Property( property="create_time", type="string", example="1609316006", description="创建时间"),
     *                  @SWG\Property( property="update_time", type="string", example="1611815631", description="最后修改时间"),
     *                  @SWG\Property( property="is_distribution", type="string", example="true", description="是否分销订单"),
     *                  @SWG\Property( property="total_rebate", type="string", example="0", description="总分销金额，以分为单位"),
     *                  @SWG\Property( property="delivery_corp", type="string", example="null", description="快递公司"),
     *                  @SWG\Property( property="delivery_code", type="string", example="null", description="快递单号"),
     *                  @SWG\Property( property="member_discount", type="string", example="0", description="会员折扣金额，以分为单位"),
     *                  @SWG\Property( property="coupon_discount", type="string", example="1", description="优惠券抵扣金额，以分为单位"),
     *                  @SWG\Property( property="coupon_discount_desc", type="array",
     *                      @SWG\Items( type="string", example="undefined", description=""),
     *                  ),
     *                  @SWG\Property( property="member_discount_desc", type="array",
     *                      @SWG\Items( type="string", example="undefined", description=""),
     *                  ),
     *                  @SWG\Property( property="delivery_status", type="string", example="PARTAIL", description="发货状态。可选值有 DONE—已发货;PENDING—待发货;PARTAIL_DELIVERY-部分发货"),
     *                  @SWG\Property( property="delivery_time", type="string", example="null", description="提货时间"),
     *                  @SWG\Property( property="cancel_status", type="string", example="NO_APPLY_CANCEL", description="取消订单状态。可选值有 NO_APPLY_CANCEL 未申请;WAIT_PROCESS 等待审核;REFUND_PROCESS 退款处理;SUCCESS 取消成功;FAILS 取消失败"),
     *                  @SWG\Property( property="end_time", type="string", example="null", description="订单完成时间"),
     *                  @SWG\Property( property="fee_type", type="string", example="CNY", description="货币类型"),
     *                  @SWG\Property( property="fee_rate", type="string", example="1", description="货币汇率"),
     *                  @SWG\Property( property="fee_symbol", type="string", example="￥", description="货币符号"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function createDelivery(Request $request)
    {
        $params = $request->all();
        $companyId = $request->get('auth')['company_id'];
        $rules = [
            'order_id' => ['required', '订单号必填'],
            'item_info' => ['required', '发货商品信息必填'],
            'lc_code' => ['required', '快递公司编码必填'],
            'l_code' => ['required', '快递单编码必填'],
            // 'l_time' => ['required', '发货时间必填'],
            // 'order_sn'   => ['required', 'oms订单号必填'],
        ];
        // app('log')->info("saaserp DeliveryAction,".__FUNCTION__.",".__LINE__."\n");
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            $this->api_response('fail', $errorMessage, null, 'E0001');
        }
        $params['item_info'] = json_decode($params['item_info'], true);

        $orderService = new OrderService(new NormalOrderService());

        $filter = ['order_id' => $params['order_id'],'company_id' => $companyId];

        $tradeInfo = $orderService->getInfo($filter);
        if (!$tradeInfo) {
            $this->api_response('fail', '此订单不存在', null, 'E0001');
        }

        if ($tradeInfo['delivery_status'] == 'DONE') {
            $this->api_response('fail', '订单已发货，请勿重复发货', null, 'E0001');
        }

        $order_list = $orderService->getOrderList(['company_id' => $tradeInfo['company_id'],'order_id' => $tradeInfo['order_id']], -1);

        app('log')->debug("openapi-createDelivery-order_list-data=>".var_export($order_list, 1));

        $order = $order_list['list'][0];
        unset($order_list);
        if (!$order) {
            $this->api_response('fail', '获取订单信息失败', null, 'E0001');
        }

        $result = $this->doOrderDelivery($order, $params);
        unset($order,$params);

        if ($result) {
            $this->api_response('true', '操作成功', $result, 'E0000');
        } else {
            $this->api_response('fail', '操作失败', null, 'E0001');
        }
    }

    public function doOrderDelivery($order, $data)
    {
        try {
            $item_info = array_column($data['item_info'], null, 'sku_id');
            $delivery_code = $data['l_code'];
            $delivery_corp = $data['lc_code'];
            unset($data);
            $sepInfo = $isDelivery = $noDelivery = $emptyDelivery = [];
            foreach ($order['items'] as $key => $items) {
                if ($items['delivery_status'] == 'PENDING') {
                    if (isset($item_info[$items['item_bn']])) {
                        $items['delivery_code'] = $delivery_code;
                        $items['delivery_corp'] = $delivery_corp;
                        $items['delivery_num'] = $item_info[$items['item_bn']]['qty'];
                        $noDelivery[] = $items;
                    } else {
                        $emptyDelivery[] = $items;
                    }
                } elseif ($items['delivery_status'] == 'DONE') {
                    $isDelivery[] = $items;
                }
            }
            if (empty($noDelivery) && !empty($emptyDelivery)) {
                app('log')->debug("openapi-" . $order['order_id'] . " 没有发货信息 " . __FUNCTION__ . __LINE__ . ",emptyDelivery=>" . json_encode($emptyDelivery));
                $this->api_response('fail', '发货商品有误', null, 'E0001');
            }

            // if(empty($isDelivery)) {
            //     $sepInfo = $noDelivery;
            // } else {
            //     $sepInfo = array_merge($noDelivery,$isDelivery);
            // }
            $sepInfo = $noDelivery;
            if (empty($sepInfo)) {
                app('log')->debug("openapi-" . $order['order_id'] . " 没有发货信息 " . __FUNCTION__ . __LINE__);
                return false;
            }
            $delivery_params = [
                'type' => 'new',
                'company_id' => $order['company_id'],
                'delivery_corp' => $delivery_corp,
                'delivery_code' => $delivery_code,
                'delivery_type' => 'sep',
                'order_id' => $order['order_id'],
                'sepInfo' => json_encode($sepInfo),
            ];
            app('log')->debug("openapi去发货 " . __FUNCTION__ . __LINE__ . " delivery_params=>" . var_export($delivery_params, 1));
            $orderService = new OrderService(new NormalOrderService());
            $result = $orderService->delivery($delivery_params);
            return $result;
        } catch (\Exception $e) {
            $msg = 'file:' . $e->getFile() . ',line:' . $e->getLine() . ",msg=>" . $e->getMessage();
            app('log')->debug("\nopenapi 发货失败 " . __FUNCTION__ . __LINE__ . " msg=>" . $msg);
            return false;
        }
    }
}
