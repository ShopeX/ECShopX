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

namespace OpenapiBundle\Http\ThirdApi\V2\Action\Orders;

use Dingo\Api\Http\Response;
use DistributionBundle\Services\DistributorService;
use Illuminate\Http\Request;
use OpenapiBundle\Http\Controllers\Controller as Controller;
use OpenapiBundle\Exceptions\ErrorException;
use OpenapiBundle\Constants\ErrorCode;

use MembersBundle\Services\MemberService;
use MembersBundle\Traits\GetCodeTrait;
use MembersBundle\Entities\MembersAssociations;
use OpenapiBundle\Services\Order\OrderService as OpenapiOrderService;
use OrdersBundle\Traits\GetOrderServiceTrait;
use OrdersBundle\Services\OrderAssociationService;
use OrdersBundle\Services\CompanyRelLogisticsServices;
use OrdersBundle\Services\ShippingTemplatesService;
use CompanysBundle\Services\SettingService;
use OrdersBundle\Services\TradeService;
use phpDocumentor\Reflection\DocBlock\Tags\Method;

class Order extends Controller
{
    use GetCodeTrait;
    use GetOrderServiceTrait;
    /**
     * @SWG\Get(
     *     path="/ecx.order.list",
     *     summary="订单列表",
     *     tags={"订单"},
     *     description="订单列表",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.order.list" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="unionid", description="unionid" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="mobile", description="手机号" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="success", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="修改成功", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="状态"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function list(Request $request)
    {
        $companyId = $request->get('auth')['company_id'];
        $params = $request->all();
        $rules = [
            'mobile' => ['sometimes|regex:/^1[345789][0-9]{9}$/', '请填写正确的手机号'],
            'unionid' => ['sometimes|string', '请填写unionid'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            $this->api_response('fail', $error, null, 'E0001');
        }
        if ((!isset($params['unionid']) || empty($params['unionid'])) && (!isset($params['mobile']) || empty($params['mobile']))) {
            $this->api_response('fail', 'unionid或者手机号必填', null, 'E0001');
        }
        $memberService = new MemberService();
        if (isset($params['mobile']) && $params['mobile']) {
            $memberInfo = $memberService->getMemberInfo(['company_id' => $companyId, 'mobile' => $params['mobile']]);
        } else {
            $membersAssociationsRepository = app('registry')->getManager('default')->getRepository(MembersAssociations::class);
            $memberInfo = $membersAssociationsRepository->get(['unionid' => $params['unionid'], 'company_id' => $companyId, 'user_type' => 'wechat']);
        }
        if (!$memberInfo) {
            $this->api_response('fail', '会员信息获取失败', null, 'E0001');
        }

        $filter['user_id'] = $memberInfo['user_id'];
        $filter['company_id'] = $companyId;
        $orderService = $this->getOrderService('normal');

        $page = 1;
        $pageSize = 10;


        $count = $orderService->count($filter);
        $total_fee = $orderService->sum($filter, 'total_fee');
        if ($count <= 0) {
            $return['count'] = 0;
            $return['total_fee'] = 0;
            $return['order_avg'] = 0;
            $return['list'] = [];
            return $return;
        }
        $return['count'] = $count;
        $return['total_fee'] = bcdiv($total_fee, 100, 2);
        $return['order_avg'] = bcdiv($return['total_fee'], $return['count'], 2);
        if ($params['page'] ?? 0) {
            $page = $params['page'];
            $pageSize = $params['page_size'];
        }
        $orderList = $orderService->getOrderList($filter, $page, $pageSize);

        $tradeService = new TradeService();
        $orderIdList = array_column($orderList['list'], 'order_id');
        $tradeIndex = $tradeService->getTradeIndexByOrderIdList($filter['company_id'], $orderIdList);

        foreach ($orderList['list'] as $key => $value) {
            $order = [
                'order_id' => $value['order_id'],
                'trade_no' => $tradeIndex[$value['order_id']] ?? '-',
                'order_status' => $value['order_status'],
                'total_fee' => bcdiv($value['total_fee'], 100, 2),
                'create_time' => $value['create_time'],
            ];
            foreach ($value['items'] as $item) {
                $item = [
                    'item_name' => $item['item_name'],
                    'item_id' => $item['item_id'],
                    'item_bn' => $item['item_bn'],
                    'price' => bcdiv($item['price'], 100, 2),
                    'total_fee' => bcdiv($item['total_fee'], 100, 2),
                    'num' => $item['num'],
                    'item_spec_desc' => $item['item_spec_desc'],
                    'pic' => $item['pic']
                ];
                $return['list'][] = array_merge($order, $item);
            }
        }
        $this->api_response('true', '操作成功', $return, 'E0000');
    }

    /**
     * @SWG\Get(
     *     path="/ecx.order.get",
     *     summary="订单详情",
     *     tags={"订单"},
     *     description="查询单条订单的详情数据",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.order.get" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="order_id", description="订单编号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="ziti_code", description="自提码" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="success", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="操作成功", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="order_id", type="string", example="3438626000140032", description="订单编号"),
     *                  @SWG\Property( property="trade_no", type="string", example="1", description="订单序号"),
     *                  @SWG\Property( property="title", type="string", example="神户牛排套餐A", description="订单标题"),
     *                  @SWG\Property( property="shop_code", type="string", example="11047059", description="店铺号"),
     *                  @SWG\Property( property="total_fee", type="string", example="1201", description="订单金额，以分为单位"),
     *                  @SWG\Property( property="mobile", type="string", example="15901872216", description="会员用户手机号"),
     *                  @SWG\Property( property="freight_fee", type="string", example="1200", description="运费价格，以分为单位"),
     *                  @SWG\Property( property="item_fee", type="string", example="1", description="商品总金额"),
     *                  @SWG\Property( property="receipt_type", type="string", example="dada", description="收货方式。可选值有 logistics:物流;ziti:店铺自提;dada:达达同城配送"),
     *                  @SWG\Property( property="order_status", type="string", example="PAYED", description="订单状态。可选值有 DONE—订单完成;NOTPAY—未支付;PART_PAYMENT-部分付款;WAIT_GROUPS_SUCCESS-等待拼团成功;PAYED-已支付;CANCEL—已取消;WAIT_BUYER_CONFIRM-待用户收货"),
     *                  @SWG\Property( property="pay_status", type="string", example="PAYED", description="支付状态。可选值有 NOTPAY—未支付;PAYED-已支付;ADVANCE_PAY-预付款完成;TAIL_PAY-支付尾款中"),
     *                  @SWG\Property( property="pay_time", type="string", example="2021-01-28 14:29:29", description="支付成功时间"),
     *                  @SWG\Property( property="delivery_corp", type="string", example="", description="快递公司"),
     *                  @SWG\Property( property="delivery_code", type="string", example="", description="快递单号"),
     *                  @SWG\Property( property="delivery_time", type="string", example="", description="发货时间"),
     *                  @SWG\Property( property="end_time", type="string", example="", description="订单完成时间"),
     *                  @SWG\Property( property="delivery_status", type="string", example="PENDING", description="发货状态。可选值有 DONE—已发货;PENDING—待发货;PARTAIL-部分发货"),
     *                  @SWG\Property( property="cancel_status", type="string", example="NO_APPLY_CANCEL", description="取消订单状态。可选值有 NO_APPLY_CANCEL 未申请;WAIT_PROCESS 等待审核;REFUND_PROCESS 退款处理;SUCCESS 取消成功;FAILS 取消失败"),
     *                  @SWG\Property( property="user_id", type="string", example="1", description="会员ID"),
     *                  @SWG\Property( property="unionid", type="string", example="ofQlA00F7pKjyTx7bJmph0", description="unionid"),
     *                  @SWG\Property( property="openid", type="string", example="oHxgH0X", description="openid"),
     *                  @SWG\Property( property="username", type="string", example="小不点", description="用户昵称"),
     *                  @SWG\Property( property="receiver_name", type="string", example="小不点", description="收货人姓名"),
     *                  @SWG\Property( property="receiver_mobile", type="string", example="15901872211", description="收货人手机号"),
     *                  @SWG\Property( property="receiver_zip", type="string", example="200030", description="收货人邮编"),
     *                  @SWG\Property( property="receiver_state", type="string", example="上海市", description="收货人所在省份"),
     *                  @SWG\Property( property="receiver_city", type="string", example="上海市", description="收货人所在城市"),
     *                  @SWG\Property( property="receiver_district", type="string", example="徐汇", description="收货人所在地区"),
     *                  @SWG\Property( property="receiver_address", type="string", example="宜山路", description="收货人详细地址"),
     *                  @SWG\Property( property="point_use", type="string", example="", description="积分抵扣使用的积分数"),
     *                  @SWG\Property( property="point_fee", type="string", example="", description="积分抵扣金额，以分为单位"),
     *                  @SWG\Property( property="member_discount", type="string", example="0", description="会员折扣金额，以分为单位"),
     *                  @SWG\Property( property="coupon_discount", type="string", example="0", description="优惠券抵扣金额，以分为单位"),
     *                  @SWG\Property( property="discount_fee", type="string", example="0", description="订单优惠金额，以分为单位"),
     *                  @SWG\Property( property="discount_info", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="type", type="string", example="member_price", description="优惠类型"),
     *                          @SWG\Property( property="info", type="string", example="会员价", description="优惠详情"),
     *                          @SWG\Property( property="rule", type="string", example="会员折扣优惠7", description="优惠规格描述"),
     *                          @SWG\Property( property="discount_fee", type="string", example="0", description="优惠金额"),
     *                       ),
     *                  ),
     *                  @SWG\Property( property="create_time", type="string", example="2021-05-31 15:39:32", description="订单创建时间"),
     *                  @SWG\Property( property="update_time", type="string", example="2021-05-31 15:39:32", description="订单更新时间"),
     *                  @SWG\Property( property="order_class", type="string", example="normal", description="订单种类。可选值有 normal:普通订单;groups:拼团订单;community 社区活动订单;bargain:助力订单;seckill:秒杀订单;shopguide:导购订单"),
     *                  @SWG\Property( property="pay_type", type="string", example="deposit", description="支付方式。wxpay-微信支付;deposit-预存款支付;pos-刷卡;point-积分"),
     *                  @SWG\Property( property="remark", type="string", example="", description="订单备注"),
     *                  @SWG\Property( property="distributor_remark", type="string", example="", description="商家备注"),
     *                  @SWG\Property( property="items", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="item_bn", type="string", example="S6086639610D4B", description="商品编码"),
     *                          @SWG\Property( property="item_name", type="string", example="神户牛排套餐A", description="商品名称"),
     *                          @SWG\Property( property="num", type="string", example="1", description="购买商品数量"),
     *                          @SWG\Property( property="price", type="string", example="1", description="价格,单位为‘分’"),
     *                          @SWG\Property( property="total_fee", type="string", example="1", description="订单金额，以分为单位"),
     *                          @SWG\Property( property="item_fee", type="string", example="1", description="商品总金额，以分为单位"),
     *                          @SWG\Property( property="point_use", type="string", example="1", description="商品积分抵扣使用的积分数"),
     *                          @SWG\Property( property="point_fee", type="string", example="1", description="商品积分抵扣金额，以分为单位"),
     *                          @SWG\Property( property="discount_info", type="array",
     *                              @SWG\Items( type="object",
     *                                  @SWG\Property( property="id", type="string", example="1", description="ID"),
     *                                  @SWG\Property( property="type", type="string", example="member_price", description="优惠类型"),
     *                                  @SWG\Property( property="info", type="string", example="会员价", description="优惠详情"),
     *                                  @SWG\Property( property="rule", type="string", example="会员折扣优惠7", description="优惠规则描述"),
     *                                  @SWG\Property( property="discount_fee", type="string", example="0", description="优惠金额，以分为单位"),
     *                               ),
     *                          ),
     *                          @SWG\Property( property="item_spec_desc", type="string", example="null", description="商品规格描述"),
     *                          @SWG\Property( property="volume", type="string", example="0", description="商品体积"),
     *                          @SWG\Property( property="weight", type="string", example="2", description="商品重量"),
     *                          @SWG\Property( property="item_type", type="string", example="normal", description="订单商品类型,normal:正常商品，gift: 赠品"),
     *                          @SWG\Property( property="goods_id", type="string", example="1", description="产品ID"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function getDetail(Request $request)
    {
        $companyId = $request->get('auth')['company_id'];
        $params = $request->all('order_id', 'ziti_code');
        $rules = [
            'order_id' => ['required_without:ziti_code', '请填写订单编号'],
            'ziti_code' => ['required_without:order_id', '请填写自提码']
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $error);
        }

        $orderService = $this->getOrderService('normal');
        if (!isset($params['order_id']) || !$params['order_id']) {
            if (strpos($params['ziti_code'], 'ZT_')) {
                $params['ziti_code'] = substr($params['ziti_code'], 3);
            }

            $params['order_id'] = $orderService->getOrderIdByCode($params['ziti_code']);
            if (!$params['order_id']) {
                throw new ErrorException(ErrorCode::ORDER_NOT_FOUND, $error);
            }
        }

        $orderDetail = $orderService->getOrderInfo($companyId, $params['order_id']);

        $openapiOrderService = new OpenapiOrderService();
        $return = $openapiOrderService->formateOrderInfoStruct($companyId, $orderDetail);
        return $this->response->array($return);
    }

    /**
     * @SWG\Get(
     *     path="/ecx.order.cancel.reasons.get",
     *     summary="订单取消原因",
     *     tags={"订单"},
     *     description="获取订单取消原因列表",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.order.cancel.reasons.get" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="success", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="操作成功", description="提示信息"),
     *          @SWG\Property( property="data", type="array",
     *              @SWG\Items( type="object",
     *                  @SWG\Property( property="id", type="string", example="1", description="理由编号"),
     *                  @SWG\Property( property="reason", type="string", example="多买/错", description="取消原因"),
     *               ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function getCancelReasons(Request $request)
    {
        // FIXME: check performance
        $openapiOrderService = new OpenapiOrderService();
        $return = $openapiOrderService->cancelReasons();
        return $this->response->array($return);
    }

    /**
     * @SWG\Post(
     *     path="/ecx.order.cancel",
     *     summary="取消订单",
     *     tags={"订单"},
     *     description="对未发货的订单，进行取消操作。未发货的订单包含：未支付、已支付未发货。取消原因ID通过ecx.order.cancel.reasons.get接口获取。",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.order.cancel" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="order_id", description="订单编号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="cancel_reason_id", description="取消原因ID" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="cancel_reason", description="取消原因描述。cancel_reason_id=12时必填。" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="success", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="操作成功", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="boolean", example="true", description="状态"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function orderCancel(Request $request)
    {
        $companyId = $request->get('auth')['company_id'];
        $params = $request->all('order_id', 'cancel_reason_id', 'cancel_reason');
        $rules = [
            'order_id' => ['required', '请填写订单编号'],
            'cancel_reason_id' => ['required', '请填写取消原因ID'],
            'cancel_reason' => ['required_if:cancel_reason_id,12|max:255', '请填写取消原因描述'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $error);
        }

        $orderAssociationService = new OrderAssociationService();
        $order = $orderAssociationService->getOrder($companyId, $params['order_id']);
        if (!$order) {
            throw new ErrorException(ErrorCode::ORDER_NOT_FOUND);
        }
        if ($order['order_type'] != 'normal') {
            throw new ErrorException(ErrorCode::ORDER_HANDLE_ERROR, '实体类订单才能取消订单');
        }
        if (!isset(config('order.cancelOrderReason')[$params['cancel_reason_id']])) {
            throw new ErrorException(ErrorCode::ORDER_HANDLE_ERROR, '请填写正确的取消原因ID');
        }

        $_params = [
            'company_id' => $companyId,
            'cancel_from' => 'shop',
            'order_id' => $params['order_id'],
            'cancel_reason' => $params['cancel_reason_id'] != 12 ? config('order.cancelOrderReason')[$params['cancel_reason_id']] : '',
            'other_reason' => $params['cancel_reason'],
            'user_id' => $order['user_id'],
            'mobile' => $order['mobile'],
            'operator_type' => 'openapi',
            'operator_id' => 0,
        ];

        $orderService = $this->getOrderServiceByOrderInfo($order);
        try {
            $return = $orderService->cancelOrder($_params);
        } catch (\Exception $e) {
            throw new ErrorException(ErrorCode::ORDER_HANDLE_ERROR, $e->getMessage());
        }
        return $this->response()->array(['status' => true]);
    }

    /**
     * @SWG\Post(
     *     path="/ecx.order.writeoff",
     *     summary="自提订单核销",
     *     tags={"订单"},
     *     description="对配送方式为自提的订单，进行核销操作。",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.order.writeoff" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="order_id", description="订单编号" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="pickupcode", description="提货码。当管理后台开启了提货码时，必填" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="success", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="操作成功", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="boolean", example="true", description="状态"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function orderWriteoff(Request $request)
    {
        $companyId = $request->get('auth')['company_id'];
        $params = $request->all('order_id', 'pickupcode');
        $rules = [
            'order_id' => ['required', '请填写订单编号'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $error);
        }

        $orderAssociationService = new OrderAssociationService();
        $order = $orderAssociationService->getOrder($companyId, $params['order_id']);
        if (!$order) {
            throw new ErrorException(ErrorCode::ORDER_NOT_FOUND);
        }

        $orderService = $this->getOrderServiceByOrderInfo($order);
        $orderDetail = $orderService->getOrderInfo($companyId, $params['order_id']);
        $orderInfo = $orderDetail['orderInfo'];
        unset($orderDetail);
        if ($orderInfo['receipt_type'] != 'ziti') {
            throw new ErrorException(ErrorCode::ORDER_NOT_FOUND, '自提订单相应的明细不存在');
        }
        if ($orderInfo['order_status'] != 'PAYED' || $orderInfo['ziti_status'] != 'PENDING') {
            throw new ErrorException(ErrorCode::ORDER_HANDLE_ERROR, '自提订单状态不正确，不能进行操作');
        }
        $settingService = new SettingService();
        $pickupCodeSetting = $settingService->presalePickupcodeGet($companyId);
        $pickupcode_status = $pickupCodeSetting['pickupcode_status'];
        // 如果开启了 提货码 提货码必填
        if ($pickupcode_status && !$params['pickupcode']) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, '请填写提货码');
        }

        try {
            $orderService->orderZitiWriteoff($companyId, $params['order_id'], $pickupcode_status, $params['pickupcode'], "openapi", 0);
        } catch (\Exception $e) {
            throw new ErrorException(ErrorCode::ORDER_HANDLE_ERROR, $e->getMessage());
        }
        return $this->response()->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/ecx.logistics.enabled.get",
     *     summary="获取开启物流公司",
     *     tags={"订单"},
     *     description="获取开启中的全部物流公司列表",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.logistics.enabled.get" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="integer", required=false, name="distributor_id", description="店铺ID" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="success", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="操作成功", description="提示信息"),
     *          @SWG\Property( property="data", type="array",
     *              @SWG\Items( type="object",
     *                  @SWG\Property( property="corp_code", type="string", example="OTHER", description="物流公司代码"),
     *                  @SWG\Property( property="corp_name", type="string", example="其他", description="物流公司简称"),
     *               ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function getEnabledLogisticsList(Request $request)
    {
        $companyId = $request->get('auth')['company_id'];
        $filter = [
            'company_id' => $companyId,
            'status' => 1,
            'distributor_id' => $request->get('distributor_id', 0),
            'supplier_id' => $request->get('supplier_id', 0),
        ];

        $companyRelLogisticsServices = new CompanyRelLogisticsServices();
        $companyRelLogisticsList = $companyRelLogisticsServices->getCompanyRelLogisticsList($filter);
        $return = [[
            'corp_code' => 'OTHER',
            'corp_name' => "其他",
        ]];
        if (!$companyRelLogisticsList['list']) {
            return $this->response()->array($return);
        }
        foreach ($companyRelLogisticsList['list'] as $logistics) {
            $return[] = [
                'corp_code' => $logistics['corp_code'],
                'corp_name' => $logistics['corp_name'],
            ];
        }

        return $this->response()->array($return);
    }

    /**
     * @SWG\Post(
     *     path="/ecx.order.cancel.confirm",
     *     summary="确认订单取消审核",
     *     tags={"订单"},
     *     description="已支付未发货的订单取消后，对订单进行退款审核的操作。",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.order.cancel.confirm" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="order_id", description="订单编号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="cancel_handle", description="取消操作 0:拒绝;1:同意;" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="reject_reason", description="拒绝理由。cancel_handle=0时必填。" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="success", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="操作成功", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="状态"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function confirmCancel(Request $request)
    {
        $companyId = $request->get('auth')['company_id'];
        $params = $request->all('order_id', 'cancel_handle', 'reject_reason');
        $rules = [
            'order_id' => ['required', '订单编号必填'],
            'cancel_handle' => ['required|in:0,1', '是否同意必填'],
            'reject_reason' => ['required_if:cancel_handle,0', '拒绝退款时原因必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $error);
        }
        try {
            $orderService = $this->getOrderService('normal');
            $_params = [
                'company_id' => $companyId,
                'order_id' => $params['order_id'],
                'check_cancel' => $params['cancel_handle'],
                'shop_reject_reason' => $params['reject_reason'],
                'operator_type' => 'openapi',
                'operator_id' => 0,
            ];
            $orderService->confirmCancelOrder($_params);
            return $this->response()->array(['status' => true]);
        } catch (\Exception $e) {
            throw new ErrorException(ErrorCode::ORDER_HANDLE_ERROR, $e->getMessage());
        }
    }

    /**
     * @SWG\Get(
     *     path="/ecx.shipping.templates.get",
     *     summary="运费模板查询",
     *     tags={"订单"},
     *     description="获取运费模板列表",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.shipping.templates.get" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="page", description="当前页面，从1开始计数" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="page_size", description="每页数量,默认：20,最大值为500" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="success", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="操作成功", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *              @SWG\Property(property="total_count", type="integer", default="8", description="列表数据总数量"),
     *              @SWG\Property(property="is_last_page", type="integer", default="1", description="是否最后一页【0 不是最后一页】【1 是最后一页】"),
     *              @SWG\Property( property="pager", type="object",
     *                  ref="#definitions/Pager",
     *              ),
     *              @SWG\Property( property="list", type="array",
     *                  @SWG\Items( type="object",
     *                      @SWG\Property( property="template_id", type="string", example="117", description="运费模板id"),
     *                          @SWG\Property( property="name", type="string", example="晨光文具-按件数计算运费", description="运费模板名称"),
     *                          @SWG\Property( property="is_free", type="string", example="0", description="是否包邮"),
     *                          @SWG\Property( property="valuation", type="string", example="2", description="运费计算参数来源 1:按重量;2:按件;3:按金额;4:按体积;"),
     *                          @SWG\Property( property="protect", type="string", example="null", description="物流保价"),
     *                          @SWG\Property( property="protect_rate", type="string", example="null", description="保价费率"),
     *                          @SWG\Property( property="minprice", type="string", example="null", description="保价费最低值"),
     *                          @SWG\Property( property="status", type="boolean", example="true", description="是否开启"),
     *                          @SWG\Property( property="fee_conf", type="string", example="[{add_fee:续费(元),add_standard:续重(kg),start_fee:首费(元),start_standard:首重(kg)}]", description="运费模板中运费信息对象，包含默认运费和指定地区运费"),
     *                          @SWG\Property( property="nopost_conf", type="string", example="[]", description="不包邮地区"),
     *                          @SWG\Property( property="free_conf", type="string", example="", description="指定包邮的条件"),
     *                          @SWG\Property( property="update_time", type="string", example="2021-03-16 10:15:13", description="最后修改时间"),
     *                   ),
     *              ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function getShippingtemplates(Request $request)
    {
        $companyId = $request->get('auth')['company_id'];
        $params = $request->all('page', 'page_size');
        $params['page'] = $this->getPage();
        $params['page_size'] = $this->getPageSize();
        $rules = [
            'page' => ['integer|min:1', '当前页面最小值为1'],
            'page_size' => ['integer|min:1|max:500', '每页显示数量1-500'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $error);
        }
        $filter = [
            'company_id' => $companyId,
            'distributor_id' => 0,
        ];
        $params['page_size'] = $this->getPageSize();
        $shippingTemplatesService = new ShippingTemplatesService();
        $orderBy = ['create_time' => 'DESC'];
        $list = $shippingTemplatesService->getList($filter, $orderBy, $params['page'], $params['page_size']);
        $openapiOrderService = new OpenapiOrderService();
        $return = $openapiOrderService->formateShippingTemplatesList($list, (int)$params['page'], (int)$params['page_size']);
        return $this->response()->array($return);
    }


    /**
     * @SWG\Get(
     *     path="/ecx.trades.get",
     *     summary="获取交易单列表",
     *     tags={"订单"},
     *     description="获取交易单列表",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.trades.get" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="page", description="当前页面，从1开始计数（不填默认1）" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="page_size", description="每页显示数量（不填默认20条）,最大为500" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="success", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="操作成功", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property(property="total_count", type="integer", default="8", description="列表数据总数量"),
     *                  @SWG\Property(property="is_last_page", type="integer", default="1", description="是否最后一页【0 不是最后一页】【1 是最后一页】"),
     *                  @SWG\Property( property="pager", type="object",
     *                      ref="#definitions/Pager",
     *                  ),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="trade_id", type="string", example="110470593456678000090032", description="交易单号"),
     *                          @SWG\Property( property="order_id", type="string", example="3456678000060032", description="订单编号"),
     *                          @SWG\Property( property="mch_id", type="string", example="null", description="商户号，微信支付"),
     *                          @SWG\Property( property="total_fee", type="string", example="1", description="应付总金额,以分为单位"),
     *                          @SWG\Property( property="discount_fee", type="string", example="0", description="订单优惠金额，以分为单位"),
     *                          @SWG\Property( property="fee_type", type="string", example="CNY", description="货币类型"),
     *                          @SWG\Property( property="pay_fee", type="string", example="1", description="支付订单金额，以分为单位"),
     *                          @SWG\Property( property="trade_state", type="string", example="SUCCESS", description="交易状态。可选值有 SUCCESS—支付成功;REFUND—转入退款;NOTPAY—未支付;CLOSED—已关闭;REVOKED—已撤销;PAYERROR--支付失败(其他原因，如银行返回失败)"),
     *                          @SWG\Property( property="pay_type", type="string", example="deposit", description="支付方式。wxpay-微信支付;wxpayh5-微信H5;wxpayjs-微信JS;wxpayapp-微信APP;wxpaypos-微信POS;wxpaypc-微信PC支付;alipay-支付宝;alipayh5-支付宝H5;alipayapp-支付宝APP;alipaypos-支付宝POS;deposit-预存款支付;pos-刷卡;point-积分;hfpay-汇付支付;"),
     *                          @SWG\Property( property="time_start", type="string", example="2021-06-18 16:57:47", description="交易起始时间"),
     *                          @SWG\Property( property="time_expire", type="string", example="2021-06-18 16:57:47", description="交易结束时间"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function getTradeList(Request $request)
    {
        $companyId = $request->get('auth')['company_id'];
        $params = $request->all('page', 'page_size');
        $params['page'] = $this->getPage();
        $params['page_size'] = $this->getPageSize();
        $rules = [
            'page' => ['integer|min:1', '当前页面最小值为1'],
            'page_size' => ['integer|min:1|max:500', '每页显示数量1-500'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $error);
        }
        $params['page_size'] = $this->getPageSize();
        if ($params['page_size'] > 1000) {
            throw new ErrorException(ErrorCode::ORDER_HANDLE_ERROR, '每页显示数量不能超过1000');
        }
        $filter = [
            'company_id' => $companyId,
        ];

        $tradeService = new TradeService();
        $orderBy = ['time_start' => 'DESC'];
        $tradeList = $tradeService->getTradeList($filter, $orderBy, $params['page_size'], $params['page']);
        $openapiOrderService = new OpenapiOrderService();
        $return = $openapiOrderService->formateTradeList($tradeList, (int)$params['page'], (int)$params['page_size']);
        return $this->response()->array($return);
    }

    /**
     * @SWG\Get(
     *     path="/ecx.orders.sold.get",
     *     summary="订单搜索",
     *     tags={"订单"},
     *     description="根据条件，搜索订单（实物订单）",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.orders.sold.get" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="page", description="当前页面，从1开始计数（不填默认1）" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="page_size", description="每页显示数量（不填默认20条）,最大为500" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="mobile", description="会员手机号" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="time_begin", description="查询创建订单开始时间 2019-09-01 00:00:00" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="time_end", description="查询创建订单结束时间 2019-09-01 00:00:00" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="shop_code", description="店铺号" ),
     *     @SWG\Parameter( in="query", type="boolean", required=false, name="is_self", description="是否自营" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="order_status", description="订单状态,可选值有 DONE—订单完成;NOTPAY—未支付;PART_PAYMENT-部分付款;WAIT_GROUPS_SUCCESS-等待拼团成功;PAYED-已支付;CANCEL—已取消;WAIT_BUYER_CONFIRM-待用户收货" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="pay_status", description="支付状态,可选值有 NOTPAY—未支付;PAYED-已支付;ADVANCE_PAY-预付款完成;TAIL_PAY-支付尾款中" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="success", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="操作成功", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property(property="total_count", type="integer", default="8", description="列表数据总数量"),
     *                  @SWG\Property(property="is_last_page", type="integer", default="1", description="是否最后一页【0 不是最后一页】【1 是最后一页】"),
     *                  @SWG\Property( property="pager", type="object",
     *                      ref="#definitions/Pager",
     *                  ),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="order_id", type="string", example="3438626000140032", description="订单编号"),
     *                          @SWG\Property( property="trade_no", type="string", example="3438626000140032", description="订单序号"),
     *                          @SWG\Property( property="title", type="string", example="神户牛排套餐A", description="订单标题"),
     *                          @SWG\Property( property="total_fee", type="string", example="1201", description="订单金额，以分为单位"),
     *                          @SWG\Property( property="shop_code", type="string", example="11047059", description="店铺号"),
     *                          @SWG\Property( property="shop_name", type="string", example="店铺名称", description="店铺名称"),
     *                          @SWG\Property( property="mobile", type="string", example="15901872216", description="会员手机号"),
     *                          @SWG\Property( property="order_status", type="string", example="PAYED", description="订单状态。可选值有 DONE—订单完成;NOTPAY—未支付;PART_PAYMENT-部分付款;WAIT_GROUPS_SUCCESS-等待拼团成功;PAYED-已支付;CANCEL—已取消;WAIT_BUYER_CONFIRM-待用户收货"),
     *                          @SWG\Property( property="create_time", type="string", example="2021-05-31 15:39:32", description="订单创建时间"),
     *                          @SWG\Property( property="update_time", type="string", example="2021-05-31 15:39:32", description="订单更新时间"),
     *                          @SWG\Property( property="delivery_corp", type="string", example="", description="快递公司"),
     *                          @SWG\Property( property="delivery_code", type="string", example="", description="快递单号"),
     *                          @SWG\Property( property="delivery_time", type="string", example="", description="发货时间"),
     *                          @SWG\Property( property="delivery_status", type="string", example="PENDING", description="发货状态。可选值有 DONE—已发货;PENDING—待发货;PARTAIL-部分发货"),
     *                          @SWG\Property( property="member_discount", type="string", example="0", description="会员折扣金额，以分为单位"),
     *                          @SWG\Property( property="coupon_discount", type="string", example="0", description="优惠券抵扣金额，以分为单位"),
     *                          @SWG\Property( property="discount_fee", type="string", example="0", description="订单优惠金额，以分为单位"),
     *                          @SWG\Property( property="discount_info", type="array",
     *                              @SWG\Items( type="object",
     *                                  @SWG\Property( property="type", type="string", example="member_price", description="优惠类型"),
     *                                  @SWG\Property( property="info", type="string", example="会员价", description="优惠详情"),
     *                                  @SWG\Property( property="rule", type="string", example="会员折扣优惠7", description="优惠规则描述"),
     *                                  @SWG\Property( property="discount_fee", type="string", example="0", description="优惠金额，以分为单位"),
     *                               ),
     *                          ),
     *                          @SWG\Property( property="cancel_status", type="string", example="NO_APPLY_CANCEL", description="取消订单状态。可选值有 NO_APPLY_CANCEL 未申请;WAIT_PROCESS 等待审核;REFUND_PROCESS 退款处理;SUCCESS 取消成功;FAILS 取消失败"),
     *                          @SWG\Property( property="end_time", type="string", example="", description="订单完成时间"),
     *                          @SWG\Property( property="is_self", type="boolean", example="true", description="是否自营"),
     *                          @SWG\Property( property="pay_status", type="string", example="NOTPAY", description="支付状态"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function getList(Request $request)
    {
        $companyId = $request->get('auth')['company_id'];
        $params = $request->all('page', 'page_size', 'mobile', 'time_begin', 'time_end', 'shop_code', 'is_self', 'order_status', 'pay_status');
        $params['page'] = $this->getPage();
        $params['page_size'] = $this->getPageSize();
        $rules = [
            'page' => ['integer|min:1', '当前页面最小值为1'],
            'page_size' => ['integer|min:1|max:500', '每页显示数量1-500'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $error);
        }

        $params['page'] = $params['page'] ?? 1;
        $params['page_size'] = $params['page_size'] ?? 500;

        $filter = [
            'company_id' => $companyId,
        ];
        if ($params['mobile']) {
            $memberService = new MemberService();
            $memberInfo = $memberService->getMemberInfo(['company_id' => $companyId, 'mobile' => $params['mobile']]);
            if (!$memberInfo) {
                return $this->response->array([]);
            } else {
                $filter['user_id'] = $memberInfo['user_id'];
            }
        }

        if ($params['time_begin']) {
            $filter['create_time|gte'] = strtotime($params['time_begin']);
        }
        if ($params['time_end']) {
            $filter['create_time|lte'] = strtotime($params['time_end']);
        }

        if ($params['shop_code']) {
            $shopId = (new DistributorService())->getShopIdByShopCode($params['shop_code']);
            $filter['distributor_id'] = $shopId ?: -1;
        }

        if (isset($params['is_self'])) {
            if ($params['is_self'] == 'true') {
                $filter['distributor_id'] = 0;
            } else {
                $filter['distributor_id|gt'] = 0;
            }
        }

        if (isset($params['order_status'])) {
            $filter['order_status'] = $params['order_status'];
        }
        if (isset($params['pay_status'])) {
            $filter['pay_status'] = $params['pay_status'];
        }

        $orderService = $this->getOrderService('normal');
        $orderList = $orderService->getOrderList($filter, (int)$params['page'], (int)$params['page_size']);
        $openapiOrderService = new OpenapiOrderService();
        $return = $openapiOrderService->formateOrderListStruct($companyId, $orderList);

        return $this->response->array($return);
    }

    /**
     * @SWG\Get(
     *     path="/ecx.orders.incr.get",
     *     summary="增量订单搜索",
     *     tags={"订单"},
     *     description="根据条件，搜索订单（实物订单-修改时间倒序）",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.orders.incr.get" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="page", description="当前页面，从1开始计数（不填默认1）" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="page_size", description="每页显示数量（不填默认20条）,最大为500" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="mobile", description="会员手机号" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="start_modified", description="查询更新订单开始时间 2019-09-01 00:00:00" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="end_modified", description="查询更新订单结束时间 2019-09-01 00:00:00" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="shop_code", description="店铺号" ),
     *     @SWG\Parameter( in="query", type="boolean", required=false, name="is_self", description="是否自营" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="order_status", description="订单状态,可选值有 DONE—订单完成;NOTPAY—未支付;PART_PAYMENT-部分付款;WAIT_GROUPS_SUCCESS-等待拼团成功;PAYED-已支付;CANCEL—已取消;WAIT_BUYER_CONFIRM-待用户收货" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="pay_status", description="支付状态,可选值有 NOTPAY—未支付;PAYED-已支付;ADVANCE_PAY-预付款完成;TAIL_PAY-支付尾款中" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="success", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="操作成功", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property(property="total_count", type="integer", default="8", description="列表数据总数量"),
     *                  @SWG\Property(property="is_last_page", type="integer", default="1", description="是否最后一页【0 不是最后一页】【1 是最后一页】"),
     *                  @SWG\Property( property="pager", type="object",
     *                      ref="#definitions/Pager",
     *                  ),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="order_id", type="string", example="3438626000140032", description="订单编号"),
     *                          @SWG\Property( property="trade_no", type="string", example="1", description="订单序号"),
     *                          @SWG\Property( property="title", type="string", example="神户牛排套餐A", description="订单标题"),
     *                          @SWG\Property( property="total_fee", type="string", example="1201", description="订单金额，以分为单位"),
     *                          @SWG\Property( property="shop_code", type="string", example="11047059", description="店铺号"),
     *                          @SWG\Property( property="shop_name", type="string", example="店铺名称", description="店铺名称"),
     *                          @SWG\Property( property="mobile", type="string", example="15901872216", description="会员手机号"),
     *                          @SWG\Property( property="order_status", type="string", example="PAYED", description="订单状态。可选值有 DONE—订单完成;NOTPAY—未支付;PART_PAYMENT-部分付款;WAIT_GROUPS_SUCCESS-等待拼团成功;PAYED-已支付;CANCEL—已取消;WAIT_BUYER_CONFIRM-待用户收货"),
     *                          @SWG\Property( property="create_time", type="string", example="2021-05-31 15:39:32", description="订单创建时间"),
     *                          @SWG\Property( property="update_time", type="string", example="2021-05-31 15:39:32", description="订单更新时间"),
     *                          @SWG\Property( property="delivery_corp", type="string", example="", description="快递公司"),
     *                          @SWG\Property( property="delivery_code", type="string", example="", description="快递单号"),
     *                          @SWG\Property( property="delivery_time", type="string", example="", description="发货时间"),
     *                          @SWG\Property( property="delivery_status", type="string", example="PENDING", description="发货状态。可选值有 DONE—已发货;PENDING—待发货;PARTAIL-部分发货"),
     *                          @SWG\Property( property="member_discount", type="string", example="0", description="会员折扣金额，以分为单位"),
     *                          @SWG\Property( property="coupon_discount", type="string", example="0", description="优惠券抵扣金额，以分为单位"),
     *                          @SWG\Property( property="discount_fee", type="string", example="0", description="订单优惠金额，以分为单位"),
     *                          @SWG\Property( property="discount_info", type="array",
     *                              @SWG\Items( type="object",
     *                                  @SWG\Property( property="type", type="string", example="member_price", description="优惠类型"),
     *                                  @SWG\Property( property="info", type="string", example="会员价", description="优惠详情"),
     *                                  @SWG\Property( property="rule", type="string", example="会员折扣优惠7", description="优惠规则描述"),
     *                                  @SWG\Property( property="discount_fee", type="string", example="0", description="优惠金额，以分为单位"),
     *                               ),
     *                          ),
     *                          @SWG\Property( property="cancel_status", type="string", example="NO_APPLY_CANCEL", description="取消订单状态。可选值有 NO_APPLY_CANCEL 未申请;WAIT_PROCESS 等待审核;REFUND_PROCESS 退款处理;SUCCESS 取消成功;FAILS 取消失败"),
     *                          @SWG\Property( property="end_time", type="string", example="", description="订单完成时间"),
     *                          @SWG\Property( property="is_self", type="boolean", example="true", description="是否自营"),
     *                          @SWG\Property( property="pay_status", type="string", example="NOTPAY", description="支付状态"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function getIncrOrderList(Request $request): Response
    {
        $companyId = $request->get('auth')['company_id'];
        $params = $request->all('page', 'page_size', 'mobile', 'start_modified', 'end_modified', 'shop_code', 'is_self', 'order_status', 'pay_status');

        $params['page'] = $this->getPage();
        $params['page_size'] = $this->getPageSize();
        $rules = [
            'page' => ['required|integer|min:1', '当前页面最小值为1'],
            'page_size' => ['required|integer|min:1|max:500', '每页显示数量1-500'],
            // 'order_status' => ['sometimes|in:NOTPAY,PAYED,PART_PAYMENT,WAIT_GROUPS_SUCCESS,WAIT_BUYER_CONFIRM,DONE,CANCEL'],
            // 'pay_status' => ['sometimes|in:NOTPAY,PAYED,ADVANCE_PAY,TAIL_PAY'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $error);
        }

        $params['page'] = $params['page'] ?? 1;
        $params['page_size'] = $params['page_size'] ?? 500;

        $filter = [
            'company_id' => $companyId,
        ];
        if ($params['mobile']) {
            $userId = $this->_getUserIdByMobile((string)$params['mobile']);
            if (!$userId) {
                return $this->response->array([]);
            }
            $filter['user_id'] = $userId;
        }

        if ($params['shop_code']) {
            $shopId = (new DistributorService())->getShopIdByShopCode($params['shop_code']);
            $filter['distributor_id'] = $shopId ?: -1;
        }

        if ($params['start_modified']) {
            $filter['update_time|gte'] = strtotime($params['start_modified']);
        }
        if ($params['end_modified']) {
            $filter['update_time|lte'] = strtotime($params['end_modified']);
        }

        if (isset($params['is_self'])) {
            if ($params['is_self'] == 'true') {
                $filter['distributor_id'] = 0;
            } else {
                $filter['distributor_id|gt'] = 0;
            }
        }

        if (isset($params['order_status'])) {
            $filter['order_status'] = $params['order_status'];
        }
        if (isset($params['pay_status'])) {
            $filter['pay_status'] = $params['pay_status'];
        }

        $orderService = $this->getOrderService('normal');
        $orderList = $orderService->getOrderList($filter, $params['page'], $params['page_size'], ['update_time' => 'DESC']);
        $openapiOrderService = new OpenapiOrderService();
        $return = $openapiOrderService->formateOrderListStruct($companyId, $orderList);

        return $this->response->array($return);
    }

    /**
     *
     * @param string $mobile
     * @return false|mixed
     */
    private function _getUserIdByMobile(int $companyId, string $mobile)
    {
        if (!$mobile) {
            return false;
        }
        $memberInfo = (new MemberService())->getMemberInfo(['company_id' => $companyId, 'mobile' => $mobile]);
        if (!$memberInfo) {
            return false;
        } else {
            return $memberInfo['user_id'];
        }
    }
}
