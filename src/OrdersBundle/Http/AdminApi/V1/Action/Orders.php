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

namespace OrdersBundle\Http\AdminApi\V1\Action;

use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;
use App\Http\Controllers\Controller as Controller;
use OrdersBundle\Services\OrderProfitService;
use OrdersBundle\Services\OrderService;
use OrdersBundle\Services\Orders\ServiceOrderService;
use OrdersBundle\Services\OrderAssociationService;
use OrdersBundle\Services\LogisticTracker;

use PaymentBundle\Services\PaymentsService;
use PaymentBundle\Services\Payments\PosPayService;

use OrdersBundle\Traits\GetOrderServiceTrait;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use MembersBundle\Services\MemberService;
use MembersBundle\Services\WechatUserService;
use OrdersBundle\Services\OrderProcessLogService;

class Orders extends Controller
{
    use GetOrderServiceTrait;

    /**
     * @SWG\Post(
     *     path="/admin/wxapp/order/create",
     *     summary="创建订单",
     *     tags={"订单"},
     *     description="创建订单",
     *     operationId="createOrders",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="item_id", in="formData", description="交易商品ID", required=true, type="string"),
     *     @SWG\Parameter( name="item_num", in="formData", description="交易商品数量", required=true, type="string"),
     *     @SWG\Parameter( name="user_id", in="formData", description="购买用户id", required=true, type="string"),
     *     @SWG\Parameter( name="shop_id", in="formData", description="门店id", required=true, type="string"),
     *     @SWG\Parameter( name="distributor_id", in="formData", description="店铺id", required=true, type="string"),
     *     @SWG\Parameter( name="mobile", in="formData", description="购买用户手机号", required=true, type="string"),
     *     @SWG\Parameter( name="pay_type", in="formData", description="支付类型", required=true, type="string"),
     *     @SWG\Parameter( name="pay_bank", in="formData", description="支付银行", type="string"),
     *     @SWG\Parameter( name="transaction_id", in="formData", description="银行流水单号", type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="appId", type="string", description="应用ID"),
     *                 @SWG\Property(property="timeStamp", type="string", description="时间戳"),
     *                 @SWG\Property(property="nonceStr", type="string", description="随机字符串"),
     *                 @SWG\Property(property="package", type="string", description="订单详情扩展字符串"),
     *                 @SWG\Property(property="signType", type="string", description="签名方式"),
     *                 @SWG\Property(property="paySign", type="string", description="签名"),
     *                 @SWG\Property(property="team_id", type="string", description=""),
     *                 @SWG\Property(
     *                     property="trade_info",
     *                     type="object",
     *                         @SWG\Property(property="order_id", type="string", description="订单号"),
     *                         @SWG\Property(property="trade_id", type="string", description="交易单号"),
     *                 ),
     *             )
     *         )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function createOrders(Request $request)
    {
        $authInfo = $this->auth->user();
        $params = $request->all();

        $params['order_type'] = $params['order_type'] ?? 'service';
        $payType = (isset($params['pay_type']) && $params['pay_type']) ? $params['pay_type'] : 'pos';
        $transactionId = isset($params['transaction_id']) ? $params['transaction_id'] : '';
        $payBank = (isset($params['pay_bank']) && $params['pay_bank']) ? $params['pay_bank'] : 'POS刷卡';
        unset($params['pay_type'], $params['transaction_id']);

        $params['company_id'] = $authInfo['company_id'];
        $params['shop_id'] = $request->input('shop_id', 0);
        $params['distributor_id'] = $request->input('distributor_id', 0);

        $params['order_source'] = 'shop';
        $params['operator_desc'] = $authInfo['phoneNumber']." : ".$authInfo['salesperson_name'];

        if ($authInfo['salesperson_type'] == 'shopping_guide') {
            $params['salesman_id'] = $authInfo['salesperson_id'];
        }
        $orderService = new OrderService(new ServiceOrderService());
        $result = $orderService->create($params);

        if ($payType == 'pos') {
            $paymentsService = new PosPayService();
        }
        $service = new PaymentsService($paymentsService);

        $data = [
            'company_id' => $authInfo['company_id'],
            'user_id' => $params['user_id'],
            'total_fee' => $result['total_fee'],
            'detail' => $result['title'],
            'order_id' => $result['order_id'],
            'body' => $result['title'],
            'shop_id' => $result['shop_id'],
            'distributor_id' => $result['distributor_id'] ?? 0,
            'mobile' => $params['mobile'],
            'pay_type' => $payType,
            'transaction_id' => $transactionId,
            'bank_type' => $payBank,
            'open_id' => '',
            'pay_fee' => $result['total_fee'],
            'trade_source_type' => $params['order_type'],
            'return_url' => $params['return_url'] ?? '',
        ];

        $payResult = $service->doPayment('', '', $data, false);
        return $this->response->array($payResult);
    }

    /**
     * @SWG\Get(
     *     path="/admin/wxapp/order/getlist",
     *     summary="获取用户订单列表",
     *     tags={"订单"},
     *     description="获取用户订单列表",
     *     operationId="getOrdersList",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide"),
     *     @SWG\Parameter( name="page", in="query", description="页数", required=true, type="integer", default="1"),
     *     @SWG\Parameter( name="page_size", in="query", description="每页数量", required=true, type="integer", default="20"),
     *     @SWG\Parameter( name="order_id", in="query", description="订单号", type="integer" ),
     *     @SWG\Parameter( name="mobile", in="query", description="手机号", type="integer" ),
     *     @SWG\Parameter( name="user_id", in="query", description="会员id", type="integer" ),
     *     @SWG\Parameter( name="order_type", in="query", description="订单类型", type="integer" ),
     *     @SWG\Parameter( name="status", in="query", description="订单状态", type="integer" ),
     *     @SWG\Parameter( name="order_class", in="query", description="订单活动类型{seckill,group}", type="integer" ),
     *     @SWG\Parameter( name="time_start_begin", in="query", description="查询开始时间", type="integer" ),
     *     @SWG\Parameter( name="time_start_end", in="query", description="查询结束时间", type="integer" ),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="list", type="array", description="",
     *                 @SWG\Items(
     *                           @SWG\Property(property="order_id", type="string", example="3321684000300350", description="订单号"),
     *                           @SWG\Property(property="title", type="string", example="测试多规格1...", description="订单标题"),
     *                           @SWG\Property(property="total_fee", type="string", example="16", description="订单金额，以分为单位"),
     *                           @SWG\Property(property="company_id", type="string", example="1", description="公司id"),
     *                           @SWG\Property(property="shop_id", type="string", example="0", description="门店id"),
     *                           @SWG\Property(property="store_name", type="string", example="", description=""),
     *                           @SWG\Property(property="user_id", type="string", example="20350", description="用户id"),
     *                           @SWG\Property(property="mobile", type="string", example="17638125092", description="手机号"),
     *                           @SWG\Property(property="order_type", type="string", example="normal", description="订单类型。可选值有 normal:普通实体订单"),
     *                           @SWG\Property(property="order_status", type="string", example="PAYED", description="订单状态。可选值有 DONE—订单完成;NOTPAY—未支付;PART_PAYMENT-部分付款;WAIT_GROUPS_SUCCESS-等待拼团成功;PAYED-已支付;CANCEL—已取消;WAIT_BUYER_CONFIRM-待用户收货"),
     *                           @SWG\Property(property="create_time", type="string", example="1612343172", description="订单创建时间"),
     *                           @SWG\Property(property="update_time", type="string", example="1612343255", description="订单更新时间"),
     *                           @SWG\Property(property="source_id", type="string", example="0", description="订单来源id"),
     *                           @SWG\Property(property="monitor_id", type="string", example="0", description="订单监控页面id"),
     *                           @SWG\Property(property="authorizer_appid", type="string", example="", description=""),
     *                           @SWG\Property(property="wxa_appid", type="string", example="", description=""),
     *                           @SWG\Property(property="is_distribution", type="string", example="1", description="是否分销订单"),
     *                           @SWG\Property(property="total_rebate", type="string", example="0", description="订单总分销金额，以分为单位"),
     *                           @SWG\Property(property="delivery_corp", type="string", example="", description="快递公司"),
     *                           @SWG\Property(property="delivery_code", type="string", example="", description="快递单号"),
     *                           @SWG\Property(property="delivery_time", type="string", example="1612343255", description="发货时间"),
     *                           @SWG\Property(property="delivery_status", type="string", example="DONE", description="发货状态。可选值有 DONE—已发货;PENDING—待发货;PARTAIL-部分发货"),
     *                           @SWG\Property(property="member_discount", type="string", example="4", description="会员折扣金额，以分为单位"),
     *                           @SWG\Property(property="coupon_discount", type="string", example="0", description="优惠券抵扣金额，以分为单位"),
     *                           @SWG\Property(property="coupon_discount_desc", type="string", example="", description="优惠券使用详情"),
     *                           @SWG\Property(property="member_discount_desc", type="string", example="", description="会员折扣使用详情"),
     *                           @SWG\Property(property="order_class", type="string", example="normal", description="订单种类。可选值有 normal:普通订单;groups:拼团订单;;community 社区活动订单;bargain:助力订单;seckill:秒杀订单;shopguide:导购订单;pointsmall:积分商城"),
     *                           @SWG\Property(property="cancel_status", type="string", example="NO_APPLY_CANCEL", description="取消订单状态。可选值有 NO_APPLY_CANCEL 未申请;WAIT_PROCESS 等待审核;REFUND_PROCESS 退款处理;SUCCESS 取消成功;FAILS 取消失败"),
     *                           @SWG\Property(property="end_time", type="string", example="", description="订单完成时间"),
     *                           @SWG\Property(property="promoter_user_id", type="string", example="", description=""),
     *                           @SWG\Property(property="fee_type", type="string", example="CNY", description="货币类型"),
     *                           @SWG\Property(property="fee_rate", type="string", example="1", description="货币汇率"),
     *                           @SWG\Property(property="fee_symbol", type="string", example="￥", description="货币符号"),
     *                           @SWG\Property(property="salesman_id", type="string", example="0", description="导购员ID"),
     *                           @SWG\Property(property="promoter_shop_id", type="string", example="0", description=""),
     *                           @SWG\Property(property="source_name", type="string", example="-", description=""),
     *                           @SWG\Property(property="create_date", type="string", example="2021-02-03 17:06:12", description=""),
     *                 ),
     *               ),
     *              @SWG\Property(property="pager", type="object", description="",
     *                   @SWG\Property(property="count", type="string", example="7999", description="总记录数"),
     *                   @SWG\Property(property="page_no", type="integer", example="1", description="页码"),
     *                   @SWG\Property(property="page_size", type="integer", example="20", description="每页记录条数"),
     *              ),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function getOrdersList(Request $request)
    {
        if (!$request->input('user_id') || !$request->input('mobile')) {
            return $this->response->array([]);
        }
        $page = $request->input('page', 1);
        $limit = $request->input('page_size', 20);

        $filter['user_id'] = $request->input('user_id');
        $filter['mobile'] = $request->input('mobile');
        $filter['order_type'] = $request->input('order_type', 'service');
        if ($request->input('order_class')) {
            $filter['order_class'] = $request->input('order_class');
        }
        if ($request->input('order_id')) {
            $filter['order_id|contains'] = $request->input('order_id');
        }
        $status = $request->input('status', 0) ? $request->input('status') : 0;

        switch ($status) {
            case 1:    //待发货 待收货
                $filter['order_status|in'] = ['PAYED','WAIT_BUYER_CONFIRM'];
                if ($filter['order_type'] != 'service') {
                    $filter['ziti_status'] = 'NOTZITI';
                }
                break;
            case 2:  //之前是待收货，之后此项被废弃
                $filter['order_status'] = 'WAIT_BUYER_CONFIRM';
                if ($filter['order_type'] != 'service') {
                    $filter['delivery_status'] = 'DONE';
                }
                break;
            case 3:  //已完成
                $filter['order_status'] = 'DONE';
                if ($filter['order_type'] != 'service') {
                    $filter['delivery_status'] = 'DONE';
                    $filter['ziti_status'] = 'DONE';
                }
                break;
            case 4:  //待自提
                $filter['order_status'] = 'PAYED';
                if ($filter['order_type'] != 'service') {
                    $filter['ziti_status'] = 'PENDING';
                }
                break;
            case 5:  //待付款
                $filter['order_status'] = 'NOTPAY';
                break;
            default:
                $filter['order_status|in'] = ['PAYED','WAIT_BUYER_CONFIRM','DONE'];
                break;
        }
        $authInfo = $this->auth->user();
        // if ($authInfo['salesperson_id'] ?? 0) {
        //     $filter['salesman_id'] = $authInfo['salesperson_id'];
        // }
        $isOnlineOrder = $request->get('is_online_order');
        if ($isOnlineOrder == 'true') {
            $filter['order_source'] = ['shop_online', 'member'];
        } elseif ($isOnlineOrder == 'false') {
            $filter['order_source'] = 'shop_offline';
        }
        $filter['company_id'] = $authInfo['company_id'];
        $orderService = $this->getOrderService($filter['order_type']);
        $result = $orderService->getOrderList($filter, $page, $limit);
        if ($result['list'] ?? null) {
            //获取会员信息
            $memberService = new MemberService();
            $uf = [
                'user_id' => array_column($result['list'], 'user_id'),
            ];
            $userlist = $memberService->membersInfoRepository->getDataList($uf);
            $userdata = array_column($userlist, 'username', 'user_id');
            foreach ($result['list'] as &$list) {
                $list['username'] = $userdata[$list['user_id']] ?? '';
            }
        }

        $result['total_count'] = $result['pager']['count'] ?? 0;
        unset($result['pager']);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/order/getinfo",
     *     summary="获取订单详情",
     *     tags={"订单"},
     *     description="获取订单详情",
     *     operationId="getOrdersInfo",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="order_id", in="query", description="订单号", required=true, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *              @SWG\Property(property="orderInfo", type="object", description="",
     *                   @SWG\Property(property="order_id", type="string", example="3319460000470394", description="订单号"),
     *                   @SWG\Property(property="title", type="string", example="测试商品会员价导入...", description="订单标题"),
     *                   @SWG\Property(property="company_id", type="string", example="1", description="企业ID"),
     *                   @SWG\Property(property="user_id", type="string", example="20394", description="购买用户"),
     *                   @SWG\Property(property="act_id", type="string", example="", description="营销活动ID，团购ID，社区拼团ID，秒杀活动ID等"),
     *                   @SWG\Property(property="mobile", type="string", example="17621502659", description="购买用户手机号"),
     *                   @SWG\Property(property="freight_fee", type="integer", example="0", description="运费价格，以分为单位"),
     *                   @SWG\Property(property="freight_type", type="string", example="cash", description="运费类型-用于积分商城 cash:现金 point:积分"),
     *                   @SWG\Property(property="item_fee", type="string", example="100", description="商品总金额，以分为单位"),
     *                   @SWG\Property(property="item_point", type="integer", example="0", description="商品积分"),
     *                   @SWG\Property(property="cost_fee", type="integer", example="100", description="商品成本价，以分为单位"),
     *                   @SWG\Property(property="total_fee", type="string", example="0", description="应付总金额,以分为单位"),
     *                   @SWG\Property(property="step_paid_fee", type="integer", example="0", description="分阶段付款已支付金额，以分为单位"),
     *                   @SWG\Property(property="total_rebate", type="integer", example="0", description="总分销金额，以分为单位"),
     *                   @SWG\Property(property="distributor_id", type="string", example="103", description="门店ID"),
     *                   @SWG\Property(property="receipt_type", type="string", example="logistics", description="收货方式。可选值有 logistics:物流;ziti:店铺自提"),
     *                   @SWG\Property(property="ziti_code", type="string", example="0", description="店铺自提码"),
     *                   @SWG\Property(property="shop_id", type="string", example="0", description="门店ID"),
     *                   @SWG\Property(property="ziti_status", type="string", example="NOTZITI", description="店铺自提状态。可选值有 PENDING:等待自提;DONE:自提完成;NOTZITI:自提完成; APPROVE:审核通过,药品自提需要审核"),
     *                   @SWG\Property(property="order_status", type="string", example="WAIT_BUYER_CONFIRM", description="订单状态。可选值有 DONE—订单完成;NOTPAY—未支付;PART_PAYMENT-部分付款;WAIT_GROUPS_SUCCESS-等待拼团成功;PAYED-已支付;CANCEL—已取消;WAIT_BUYER_CONFIRM-待用户收货"),
     *                   @SWG\Property(property="order_source", type="string", example="member", description="订单来源。可选值有 member-用户自主下单;shop-商家代客下单"),
     *                   @SWG\Property(property="order_type", type="string", example="normal", description="订单类型。可选值有 normal:普通实体订单"),
     *                   @SWG\Property(property="order_class", type="string", example="normal", description="订单种类。可选值有 normal:普通订单;groups:拼团订单;;community 社区活动订单;bargain:助力订单;seckill:秒杀订单;shopguide:导购订单;pointsmall:积分商城"),
     *                   @SWG\Property(property="auto_cancel_time", type="string", example="1612150545", description="订单自动取消时间"),
     *                   @SWG\Property(property="auto_cancel_seconds", type="integer", example="-12452", description=""),
     *                   @SWG\Property(property="auto_finish_time", type="string", example="1612755464", description="订单自动完成时间"),
     *                   @SWG\Property(property="is_distribution", type="string", example="1", description="是否分销订单"),
     *                   @SWG\Property(property="source_id", type="string", example="0", description="订单来源id"),
     *                   @SWG\Property(property="monitor_id", type="string", example="0", description="订单监控页面id"),
     *                   @SWG\Property(property="salesman_id", type="string", example="0", description="导购员ID"),
     *                   @SWG\Property(property="delivery_corp", type="string", example="", description="快递公司"),
     *                   @SWG\Property(property="delivery_corp_source", type="string", example="kuaidi100", description="快递代码来源"),
     *                   @SWG\Property(property="delivery_code", type="string", example="", description="快递单号"),
     *                   @SWG\Property(property="delivery_img", type="string", example="", description="快递发货凭证"),
     *                   @SWG\Property(property="delivery_status", type="string", example="DONE", description="发货状态。可选值有 DONE—已发货;PENDING—待发货"),
     *                   @SWG\Property(property="cancel_status", type="string", example="NO_APPLY_CANCEL", description="取消订单状态。可选值有 NO_APPLY_CANCEL 未申请;WAIT_PROCESS 等待审核;REFUND_PROCESS 退款处理;SUCCESS 取消成功;FAILS 取消失败"),
     *                   @SWG\Property(property="delivery_time", type="integer", example="1612150664", description="发货时间"),
     *                   @SWG\Property(property="end_time", type="string", example="", description="订单完成时间"),
     *                   @SWG\Property(property="end_date", type="string", example="", description=""),
     *                   @SWG\Property(property="receiver_name", type="string", example="1232", description="收货人姓名"),
     *                   @SWG\Property(property="receiver_mobile", type="string", example="17653569856", description="收货人手机号"),
     *                   @SWG\Property(property="receiver_zip", type="string", example="000000", description="收货人邮编"),
     *                   @SWG\Property(property="receiver_state", type="string", example="北京市", description="收货人所在省份"),
     *                   @SWG\Property(property="receiver_city", type="string", example="北京市", description="收货人所在城市"),
     *                   @SWG\Property(property="receiver_district", type="string", example="东城", description="收货人所在地区"),
     *                   @SWG\Property(property="receiver_address", type="string", example="123123", description="收货人详细地址"),
     *                   @SWG\Property(property="member_discount", type="integer", example="20", description="会员折扣金额，以分为单位"),
     *                   @SWG\Property(property="coupon_discount", type="integer", example="0", description="优惠券抵扣金额，以分为单位"),
     *                   @SWG\Property(property="discount_fee", type="integer", example="20", description="订单优惠金额"),
     *                   @SWG\Property(property="create_time", type="integer", example="1612150245", description="订单创建时间"),
     *                   @SWG\Property(property="update_time", type="integer", example="1612150664", description="订单更新时间"),
     *                   @SWG\Property(property="fee_type", type="string", example="CNY", description="货币类型"),
     *                   @SWG\Property(property="fee_rate", type="integer", example="1", description="货币汇率"),
     *                   @SWG\Property(property="fee_symbol", type="string", example="￥", description="货币符号"),
     *                   @SWG\Property(property="cny_fee", type="integer", example="0", description=""),
     *                   @SWG\Property(property="point", type="integer", example="16", description="商品总积分"),
     *                   @SWG\Property(property="pay_type", type="string", example="point", description="支付方式。wxpay-微信支付;deposit-预存款支付;pos-刷卡;point-积分"),
     *                   @SWG\Property(property="remark", type="string", example="", description="订单备注"),
     *                   @SWG\Property(property="third_params", type="object", description="",
     *                           @SWG\Property(property="is_liveroom", type="string", example="1", description=""),
     *                   ),
     *                   @SWG\Property(property="invoice", type="string", example="", description="发票信息(DC2Type:json_array)"),
     *                   @SWG\Property(property="send_point", type="integer", example="0", description="是否分发积分0否 1是"),
     *                   @SWG\Property(property="is_rate", type="string", example="", description="是否评价"),
     *                   @SWG\Property(property="is_invoiced", type="string", example="", description="是否已开发票"),
     *                   @SWG\Property(property="invoice_number", type="string", example="", description="发票号"),
     *                   @SWG\Property(property="audit_status", type="string", example="processing", description="跨境订单审核状态 approved成功 processing审核中 rejected审核拒绝"),
     *                   @SWG\Property(property="audit_msg", type="string", example="正在审核订单", description="审核意见"),
     *                   @SWG\Property(property="point_fee", type="integer", example="80", description="积分抵扣时分摊的积分的金额，以分为单位"),
     *                   @SWG\Property(property="point_use", type="integer", example="16", description="积分抵扣使用的积分数"),
     *                   @SWG\Property(property="uppoint_use", type="integer", example="0", description="积分抵扣使用的积分升值数"),
     *                   @SWG\Property(property="point_up_use", type="integer", example="0", description=""),
     *                   @SWG\Property(property="pay_status", type="string", example="PAYED", description="支付状态。可选值有 NOTPAY—未支付;PAYED-已支付;ADVANCE_PAY-预付款完成;TAIL_PAY-支付尾款中"),
     *                   @SWG\Property(property="get_points", type="integer", example="0", description="商品获取积分"),
     *                   @SWG\Property(property="bonus_points", type="integer", example="0", description="购物赠送积分"),
     *                   @SWG\Property(property="get_point_type", type="integer", example="1", description="获取积分类型，0 老订单按订单完成时送,1 新订单按下单时计算送"),
     *                   @SWG\Property(property="pack", type="string", example="", description="包装"),
     *                   @SWG\Property(property="is_shopscreen", type="string", example="", description="是否门店订单"),
     *                   @SWG\Property(property="is_logistics", type="string", example="", description="门店缺货商品总部快递发货"),
     *                   @SWG\Property(property="is_profitsharing", type="integer", example="1", description="是否分账订单 1不分账 2分账"),
     *                   @SWG\Property(property="profitsharing_status", type="integer", example="1", description="分账状态 1未分账 2已分账"),
     *                   @SWG\Property(property="order_auto_close_aftersales_time", type="string", example="", description="自动关闭售后时间"),
     *                   @SWG\Property(property="profitsharing_rate", type="integer", example="0", description="分账费率"),
     *                   @SWG\Property(property="bind_auth_code", type="string", example="", description="订单订单验证码"),
     *                   @SWG\Property(property="extra_points", type="integer", example="0", description="订单获取额外积分"),
     *                   @SWG\Property(property="type", type="integer", example="0", description="订单类型，0普通订单,1跨境订单,....其他"),
     *                   @SWG\Property(property="taxable_fee", type="integer", example="0", description="计税总价，以分为单位"),
     *                   @SWG\Property(property="identity_id", type="string", example="", description="身份证号码"),
     *                   @SWG\Property(property="identity_name", type="string", example="", description="身份证姓名"),
     *                   @SWG\Property(property="total_tax", type="integer", example="0", description="总税费"),
     *                   @SWG\Property(property="discount_info", type="array", description="",
     *                     @SWG\Items(
     *                          @SWG\Property(property="id", type="integer", example="0", description="ID"),
     *                          @SWG\Property(property="type", type="string", example="member_price", description="订单类型，0普通订单,1跨境订单,....其他"),
     *                          @SWG\Property(property="info", type="string", example="会员价", description=""),
     *                          @SWG\Property(property="rule", type="string", example="会员价直减0.20", description="分润规则(DC2Type:json_array)"),
     *                          @SWG\Property(property="discount_fee", type="integer", example="20", description="订单优惠金额"),
     *                     ),
     *                   ),
     *                   @SWG\Property(property="can_apply_aftersales", type="integer", example="0", description=""),
     *                   @SWG\Property(property="distributor_name", type="string", example="中关村东路123号院3号楼", description=""),
     *                   @SWG\Property(property="items", type="array", description="",
     *                     @SWG\Items(
     *                                           @SWG\Property(property="id", type="string", example="8997", description="ID"),
     *                                           @SWG\Property(property="order_id", type="string", example="3319460000470394", description="订单号"),
     *                                           @SWG\Property(property="company_id", type="string", example="1", description="企业ID"),
     *                                           @SWG\Property(property="user_id", type="string", example="20394", description="购买用户"),
     *                                           @SWG\Property(property="act_id", type="string", example="", description="营销活动ID，团购ID，社区拼团ID，秒杀活动ID等"),
     *                                           @SWG\Property(property="item_id", type="string", example="5461", description="商品id"),
     *                                           @SWG\Property(property="item_bn", type="string", example="gyct2021001", description="商品编码"),
     *                                           @SWG\Property(property="item_name", type="string", example="测试商品会员价导入", description="商品名称"),
     *                                           @SWG\Property(property="pic", type="string", example="", description="商品图片"),
     *                                           @SWG\Property(property="num", type="integer", example="1", description="购买商品数量"),
     *                                           @SWG\Property(property="price", type="integer", example="100", description="单价，以分为单位"),
     *                                           @SWG\Property(property="total_fee", type="integer", example="0", description="应付总金额,以分为单位"),
     *                                           @SWG\Property(property="templates_id", type="integer", example="1", description="运费模板id"),
     *                                           @SWG\Property(property="rebate", type="integer", example="0", description="单个分销金额，以分为单位"),
     *                                           @SWG\Property(property="total_rebate", type="integer", example="0", description="总分销金额，以分为单位"),
     *                                           @SWG\Property(property="item_fee", type="integer", example="100", description="商品总金额，以分为单位"),
     *                                           @SWG\Property(property="cost_fee", type="integer", example="100", description="商品成本价，以分为单位"),
     *                                           @SWG\Property(property="item_unit", type="string", example="", description="商品计量单位"),
     *                                           @SWG\Property(property="member_discount", type="integer", example="20", description="会员折扣金额，以分为单位"),
     *                                           @SWG\Property(property="coupon_discount", type="integer", example="0", description="优惠券抵扣金额，以分为单位"),
     *                                           @SWG\Property(property="discount_fee", type="integer", example="20", description="订单优惠金额"),
     *                                           @SWG\Property(property="discount_info", type="array", description="",
     *                                             @SWG\Items(
     *                                                          @SWG\Property(property="id", type="integer", example="0", description="ID"),
     *                                                          @SWG\Property(property="type", type="string", example="member_price", description="订单类型，0普通订单,1跨境订单,....其他"),
     *                                                          @SWG\Property(property="info", type="string", example="会员价", description=""),
     *                                                          @SWG\Property(property="rule", type="string", example="会员价直减0.20", description="分润规则(DC2Type:json_array)"),
     *                                                          @SWG\Property(property="discount_fee", type="integer", example="20", description="订单优惠金额"),
     *                                             ),
     *                                           ),
     *                                           @SWG\Property(property="shop_id", type="string", example="0", description="门店ID"),
     *                                           @SWG\Property(property="is_total_store", type="string", example="1", description="是否是总部库存(true:总部库存，false:店铺库存)"),
     *                                           @SWG\Property(property="distributor_id", type="string", example="103", description="门店ID"),
     *                                           @SWG\Property(property="create_time", type="integer", example="1612150245", description="订单创建时间"),
     *                                           @SWG\Property(property="update_time", type="integer", example="1612150664", description="订单更新时间"),
     *                                           @SWG\Property(property="delivery_corp", type="string", example="", description="快递公司"),
     *                                           @SWG\Property(property="delivery_code", type="string", example="", description="快递单号"),
     *                                           @SWG\Property(property="delivery_img", type="string", example="", description="快递发货凭证"),
     *                                           @SWG\Property(property="delivery_time", type="string", example="", description="发货时间"),
     *                                           @SWG\Property(property="delivery_status", type="string", example="DONE", description="发货状态。可选值有 DONE—已发货;PENDING—待发货"),
     *                                           @SWG\Property(property="aftersales_status", type="string", example="", description="售后状态。可选值有 WAIT_SELLER_AGREE 0 等待商家处理;WAIT_BUYER_RETURN_GOODS 1 商家接受申请，等待消费者回寄;WAIT_SELLER_CONFIRM_GOODS 2 消费者回寄，等待商家收货确认;SELLER_REFUSE_BUYER 3 售后驳回;SELLER_SEND_GOODS 4 卖家重新发货 换货完成;REFUND_SUCCESS 5 退款成功;REFUND_CLOSED 6 退款关闭;CLOSED 7 售后关闭"),
     *                                           @SWG\Property(property="refunded_fee", type="integer", example="0", description="退款金额，以分为单位"),
     *                                           @SWG\Property(property="fee_type", type="string", example="CNY", description="货币类型"),
     *                                           @SWG\Property(property="fee_rate", type="integer", example="1", description="货币汇率"),
     *                                           @SWG\Property(property="fee_symbol", type="string", example="￥", description="货币符号"),
     *                                           @SWG\Property(property="cny_fee", type="integer", example="0", description=""),
     *                                           @SWG\Property(property="item_point", type="integer", example="0", description="商品积分"),
     *                                           @SWG\Property(property="point", type="integer", example="16", description="商品总积分"),
     *                                           @SWG\Property(property="item_spec_desc", type="string", example="颜色:粉红大格110cm,尺码:20cm", description="商品规格描述"),
     *                                           @SWG\Property(property="order_item_type", type="string", example="normal", description="订单商品类型,normal:正常商品，gift: 赠品, plus_buy: 加价购商品"),
     *                                           @SWG\Property(property="volume", type="integer", example="0", description="商品体积"),
     *                                           @SWG\Property(property="weight", type="integer", example="0", description="商品重量"),
     *                                           @SWG\Property(property="is_rate", type="string", example="", description="是否评价"),
     *                                           @SWG\Property(property="auto_close_aftersales_time", type="string", example="", description="自动关闭售后时间"),
     *                                           @SWG\Property(property="share_points", type="integer", example="16", description="积分抵扣时分摊的积分值"),
     *                                           @SWG\Property(property="point_fee", type="integer", example="80", description="积分抵扣时分摊的积分的金额，以分为单位"),
     *                                           @SWG\Property(property="is_logistics", type="string", example="", description="门店缺货商品总部快递发货"),
     *                                           @SWG\Property(property="delivery_item_num", type="integer", example="1", description="发货单发货数量"),
     *                                           @SWG\Property(property="get_points", type="integer", example="0", description="商品获取积分"),
     *                     ),
     *                   ),
     *                   @SWG\Property(property="order_status_des", type="string", example="WAIT_BUYER_CONFIRM", description=""),
     *                   @SWG\Property(property="order_status_msg", type="string", example="待收货", description=""),
     *                   @SWG\Property(property="latest_aftersale_time", type="integer", example="0", description=""),
     *                   @SWG\Property(property="estimate_get_points", type="string", example="0", description=""),
     *                   @SWG\Property(property="delivery_type", type="string", example="new", description=""),
     *                   @SWG\Property(property="is_all_delivery", type="string", example="1", description=""),
     *              ),
     *              @SWG\Property(property="tradeInfo", type="object", description="",
     *                   @SWG\Property(property="tradeId", type="string", example="12345673319460000520394", description=""),
     *                   @SWG\Property(property="orderId", type="string", example="3319460000470394", description=""),
     *                   @SWG\Property(property="shopId", type="string", example="0", description=""),
     *                   @SWG\Property(property="userId", type="string", example="20394", description=""),
     *                   @SWG\Property(property="mobile", type="string", example="17621502659", description="购买用户手机号"),
     *                   @SWG\Property(property="openId", type="string", example="", description=""),
     *                  @SWG\Property(property="discountInfo", type="object", description="",
     *                          @SWG\Property(property="member_price0", type="object", description="",
     *                                           @SWG\Property(property="id", type="integer", example="0", description="ID"),
     *                                           @SWG\Property(property="type", type="string", example="member_price", description="订单类型，0普通订单,1跨境订单,....其他"),
     *                                           @SWG\Property(property="info", type="string", example="会员价", description=""),
     *                                           @SWG\Property(property="rule", type="string", example="会员价直减0.20", description="分润规则(DC2Type:json_array)"),
     *                                           @SWG\Property(property="discount_fee", type="integer", example="20", description="订单优惠金额"),
     *                          ),
     *                  ),
     *                   @SWG\Property(property="mchId", type="string", example="", description=""),
     *                   @SWG\Property(property="totalFee", type="integer", example="0", description=""),
     *                   @SWG\Property(property="discountFee", type="integer", example="20", description=""),
     *                   @SWG\Property(property="feeType", type="string", example="CNY", description=""),
     *                   @SWG\Property(property="payFee", type="integer", example="16", description=""),
     *                   @SWG\Property(property="tradeState", type="string", example="SUCCESS", description=""),
     *                   @SWG\Property(property="payType", type="string", example="point", description=""),
     *                   @SWG\Property(property="transactionId", type="string", example="", description=""),
     *                   @SWG\Property(property="wxaAppid", type="string", example="", description=""),
     *                   @SWG\Property(property="bankType", type="string", example="积分", description=""),
     *                   @SWG\Property(property="body", type="string", example="测试商品会员价导入...", description="交易商品简单描述"),
     *                   @SWG\Property(property="detail", type="string", example="测试商品会员价导入...", description="交易商品详情"),
     *                   @SWG\Property(property="timeStart", type="string", example="1612150245", description=""),
     *                   @SWG\Property(property="timeExpire", type="string", example="1612150245", description=""),
     *                   @SWG\Property(property="companyId", type="string", example="1", description=""),
     *                   @SWG\Property(property="authorizerAppid", type="string", example="", description=""),
     *                   @SWG\Property(property="curFeeType", type="string", example="CNY", description=""),
     *                   @SWG\Property(property="curFeeRate", type="integer", example="1", description=""),
     *                   @SWG\Property(property="curFeeSymbol", type="string", example="￥", description=""),
     *                   @SWG\Property(property="curPayFee", type="integer", example="16", description=""),
     *                   @SWG\Property(property="distributorId", type="string", example="103", description=""),
     *                   @SWG\Property(property="tradeSourceType", type="string", example="normal", description=""),
     *                   @SWG\Property(property="couponFee", type="integer", example="0", description=""),
     *                   @SWG\Property(property="couponInfo", type="string", example="", description=""),
     *                   @SWG\Property(property="initalRequest", type="string", example="", description=""),
     *                   @SWG\Property(property="initalResponse", type="string", example="", description=""),
     *                   @SWG\Property(property="payDate", type="string", example="2021-02-01 11:30:45", description=""),
     *              ),
     *              @SWG\Property(property="distributor", type="object", description="",
     *                   @SWG\Property(property="distributor_id", type="string", example="103", description="门店ID"),
     *                   @SWG\Property(property="shop_id", type="string", example="0", description="门店ID"),
     *                   @SWG\Property(property="is_distributor", type="string", example="1", description=""),
     *                   @SWG\Property(property="company_id", type="string", example="1", description="企业ID"),
     *                   @SWG\Property(property="mobile", type="string", example="17638125092", description="购买用户手机号"),
     *                   @SWG\Property(property="address", type="string", example="淀区中关村东路123号院", description=""),
     *                   @SWG\Property(property="name", type="string", example="中关村东路123号院3号楼", description=""),
     *                   @SWG\Property(property="auto_sync_goods", type="string", example="1", description=""),
     *                   @SWG\Property(property="logo", type="string", example="https://wemall-media-dev.s3.cn-northwest-1.amazonaws.com.cn/1606288539555.maomi_laoshi-003.jpg", description=""),
     *                   @SWG\Property(property="contract_phone", type="string", example="17638125092", description=""),
     *                   @SWG\Property(property="banner", type="string", example="", description=""),
     *                   @SWG\Property(property="contact", type="string", example="孙帅帅", description=""),
     *                   @SWG\Property(property="is_valid", type="string", example="true", description=""),
     *                   @SWG\Property(property="lng", type="string", example="116.333545", description=""),
     *                   @SWG\Property(property="lat", type="string", example="39.969303", description=""),
     *                   @SWG\Property(property="child_count", type="integer", example="0", description=""),
     *                   @SWG\Property(property="is_default", type="integer", example="0", description=""),
     *                   @SWG\Property(property="is_audit_goods", type="string", example="1", description=""),
     *                   @SWG\Property(property="is_ziti", type="string", example="1", description=""),
     *                   @SWG\Property(property="regions_id", type="array", description="",
     *                      @SWG\Items(
     *                         type="string", example="110000", description=""
     *                      ),
     *                   ),
     *                   @SWG\Property(property="regions", type="array", description="",
     *                      @SWG\Items(
     *                         type="string", example="北京市", description=""
     *                      ),
     *                   ),
     *                   @SWG\Property(property="is_domestic", type="integer", example="1", description=""),
     *                   @SWG\Property(property="is_direct_store", type="integer", example="1", description=""),
     *                   @SWG\Property(property="province", type="string", example="北京市", description=""),
     *                   @SWG\Property(property="is_delivery", type="string", example="1", description=""),
     *                   @SWG\Property(property="city", type="string", example="北京市", description=""),
     *                   @SWG\Property(property="area", type="string", example="东城区", description=""),
     *                   @SWG\Property(property="hour", type="string", example="08:00-21:00", description=""),
     *                   @SWG\Property(property="created", type="integer", example="1606288943", description=""),
     *                   @SWG\Property(property="updated", type="integer", example="1611123611", description=""),
     *                   @SWG\Property(property="shop_code", type="string", example="1234567", description=""),
     *                   @SWG\Property(property="wechat_work_department_id", type="integer", example="0", description=""),
     *                   @SWG\Property(property="distributor_self", type="integer", example="0", description=""),
     *                   @SWG\Property(property="regionauth_id", type="string", example="2", description=""),
     *                   @SWG\Property(property="is_open", type="string", example="true", description=""),
     *                   @SWG\Property(property="rate", type="string", example="1.00", description=""),
     *                   @SWG\Property(property="store_address", type="string", example="北京市东城区淀区中关村东路123号院", description=""),
     *                   @SWG\Property(property="store_name", type="string", example="中关村东路123号院3号楼", description=""),
     *                   @SWG\Property(property="phone", type="string", example="17638125092", description=""),
     *              ),
     *               @SWG\Property(property="cancelData", type="string", description=""),
     *               @SWG\Property(property="profit", type="object", description="",
     *                   @SWG\Property(property="id", type="string", example="3960", description="ID"),
     *                   @SWG\Property(property="order_id", type="string", example="3319460000470394", description="订单号"),
     *                   @SWG\Property(property="order_profit_status", type="string", example="1", description="0 无效分润 1 冻结分润 2 分润成功"),
     *                   @SWG\Property(property="company_id", type="string", example="1", description="企业ID"),
     *                   @SWG\Property(property="total_fee", type="string", example="0", description="应付总金额,以分为单位"),
     *                   @SWG\Property(property="pay_fee", type="string", example="0", description="支付金额"),
     *                   @SWG\Property(property="profit_type", type="integer", example="2", description="分润类型 1 总部分润 2 自营门店分润 3 加盟门店分润"),
     *                   @SWG\Property(property="user_id", type="string", example="20394", description="购买用户"),
     *                   @SWG\Property(property="dealer_id", type="string", example="0", description="区域经销商id"),
     *                   @SWG\Property(property="distributor_id", type="string", example="0", description="门店ID"),
     *                   @SWG\Property(property="order_distributor_id", type="string", example="103", description="下单当前所在门店id"),
     *                   @SWG\Property(property="distributor_nid", type="string", example="0", description="拉新导购当前所在门店id"),
     *                   @SWG\Property(property="seller_id", type="string", example="0", description="拉新导购id"),
     *                   @SWG\Property(property="popularize_distributor_id", type="string", example="0", description="推广门店id"),
     *                   @SWG\Property(property="popularize_seller_id", type="string", example="0", description="推广导购id"),
     *                   @SWG\Property(property="proprietary", type="string", example="2", description="判断拉新门店 0 无门店 1 自营门店 2 加盟门店"),
     *                   @SWG\Property(property="popularize_proprietary", type="string", example="2", description="判断推广门店 0 无门店 1 自营门店 2 加盟门店"),
     *                   @SWG\Property(property="dealers", type="string", example="0", description="区域经销商分成"),
     *                   @SWG\Property(property="distributor", type="string", example="0", description="拉新门店分成"),
     *                   @SWG\Property(property="seller", type="string", example="0", description="拉新导购分成（分给门店）"),
     *                   @SWG\Property(property="popularize_distributor", type="string", example="0", description="推广门店分成"),
     *                   @SWG\Property(property="popularize_seller", type="string", example="0", description="推广导购分成（分给门店）"),
     *                   @SWG\Property(property="commission", type="string", example="0", description="总部手续费"),
     *                   @SWG\Property(property="rule", type="object", description="",
     *                           @SWG\Property(property="show", type="string", example="1", description=""),
     *                           @SWG\Property(property="seller", type="string", example="50", description="拉新导购分成（分给门店）"),
     *                           @SWG\Property(property="distributor", type="string", example="50", description="拉新门店分成"),
     *                           @SWG\Property(property="plan_limit_time", type="string", example="0", description=""),
     *                           @SWG\Property(property="popularize_seller", type="string", example="50", description="推广导购分成（分给门店）"),
     *                           @SWG\Property(property="distributor_seller", type="string", example="50", description=""),
     *                   ),
     *                   @SWG\Property(property="created", type="integer", example="1612150245", description=""),
     *                   @SWG\Property(property="updated", type="integer", example="1612150245", description=""),
     *                   @SWG\Property(property="plan_close_time", type="string", example="1927510245", description="计划结算时间"),
     *                   @SWG\Property(property="distributor_info", type="string", description=""),
     *                   @SWG\Property(property="seller_info", type="string", description=""),
     *                   @SWG\Property(property="popularize_seller_info", type="string", description=""),
     *              ),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function getOrdersInfo(Request $request)
    {
        if (!$request->input('order_id')) {
            throw new BadRequestHttpException('订单号必填');
        }
        $authInfo = $this->auth->user();
        $orderAssociationService = new OrderAssociationService();
        $order = $orderAssociationService->getOrder($authInfo['company_id'], $request->input('order_id'));
        $result = [];
        if (!$order) {
            throw new BadRequestHttpException('订单不存在');
        }
        $orderService = $this->getOrderServiceByOrderInfo($order);
        $result = $orderService->getOrderInfo($authInfo['company_id'], $request->input('order_id'));
        if (method_exists($orderService, 'orderRecombine')) {
            $result = $orderService->orderRecombine($result); //订单售后数量重新计算
        }
        if ($authInfo['distributor_id'] != $result['orderInfo']['distributor_id']) {
            throw new BadRequestHttpException('此订单不是本店订单');
        }
//        if ('ziti' == $result['orderInfo']['receipt_type']) {
//            throw new BadRequestHttpException('此订单不属于自提订单，请自行退款');
//        }
        //获取会员信息
        $memberService = new MemberService();
        $uf = [
            'user_id' => $result['orderInfo']['user_id'],
        ];
        $userinfo = $memberService->getMemberInfo($uf);
        $result['orderInfo']['username'] = $userinfo['username'] ?? '';
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/admin/wxapp/order/getsalespersonlist",
     *     summary="获取导购分润订单列表",
     *     tags={"订单"},
     *     description="获取导购分润订单列表",
     *     operationId="cartCheckout",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide"),
     *     @SWG\Parameter( name="page", in="query", description="页数", required=true, type="integer", default="1"),
     *     @SWG\Parameter( name="page_size", in="query", description="每页数量", required=true, type="integer", default="20"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="数据集合",
     *               @SWG\Property(property="total_count", type="integer", example="1", description="总记录条数"),
     *               @SWG\Property(property="list", type="array", description="数据列表",
     *                 @SWG\Items(
     *                           @SWG\Property(property="order_id", type="string", example="3255536000050134", description="订单号"),
     *                           @SWG\Property(property="title", type="string", example="黄金...", description="订单标题"),
     *                           @SWG\Property(property="company_id", type="string", example="1", description="公司id"),
     *                           @SWG\Property(property="user_id", type="string", example="20134", description="用户id"),
     *                           @SWG\Property(property="mobile", type="string", example="13095920688", description="手机号"),
     *                           @SWG\Property(property="total_fee", type="string", example="16", description="订单金额，以分为单位"),
     *                           @SWG\Property(property="total_rebate", type="string", example="0", description="订单总分销金额，以分为单位"),
     *                           @SWG\Property(property="distributor_id", type="string", example="21", description="分销商id"),
     *                           @SWG\Property(property="order_status", type="string", example="PAYED", description="订单状态。可选值有 DONE—订单完成;NOTPAY—未支付;PART_PAYMENT-部分付款;WAIT_GROUPS_SUCCESS-等待拼团成功;PAYED-已支付;CANCEL—已取消;WAIT_BUYER_CONFIRM-待用户收货"),
     *                           @SWG\Property(property="order_source", type="string", example="member", description="订单来源。可选值有 member-用户自主下单;shop-商家代客下单"),
     *                           @SWG\Property(property="order_type", type="string", example="normal", description="订单类型。可选值有 normal:普通实体订单"),
     *                           @SWG\Property(property="auto_cancel_time", type="string", example="1606627501", description="订单自动取消时间"),
     *                           @SWG\Property(property="is_distribution", type="string", example="1", description="是否分销订单"),
     *                           @SWG\Property(property="source_id", type="string", example="0", description="订单来源id"),
     *                           @SWG\Property(property="monitor_id", type="string", example="0", description="订单监控页面id"),
     *                           @SWG\Property(property="delivery_corp", type="string", example="SF", description="快递公司"),
     *                           @SWG\Property(property="delivery_code", type="string", example="SF2020112900001", description="快递单号"),
     *                           @SWG\Property(property="delivery_time", type="string", example="", description="发货时间"),
     *                           @SWG\Property(property="delivery_status", type="string", example="DONE", description="发货状态。可选值有 DONE—已发货;PENDING—待发货;PARTAIL-部分发货"),
     *                           @SWG\Property(property="receiver_name", type="string", example="张三", description="收货人姓名"),
     *                           @SWG\Property(property="receiver_mobile", type="string", example="13095920688", description="收货人手机号"),
     *                           @SWG\Property(property="receiver_zip", type="string", example="510000", description="收货人邮编"),
     *                           @SWG\Property(property="receiver_state", type="string", example="广东省", description="收货人所在省份"),
     *                           @SWG\Property(property="receiver_city", type="string", example="广州市", description="收货人所在城市"),
     *                           @SWG\Property(property="receiver_district", type="string", example="海珠区", description="收货人所在地区"),
     *                           @SWG\Property(property="receiver_address", type="string", example="新港中路397号", description="收货人详细地址"),
     *                           @SWG\Property(property="create_time", type="string", example="1606627441", description="订单创建时间"),
     *                           @SWG\Property(property="update_time", type="string", example="1606630242", description="订单更新时间"),
     *                           @SWG\Property(property="freight_fee", type="string", example="10", description="运费价格，以分为单位"),
     *                           @SWG\Property(property="item_fee", type="string", example="6", description="商品金额，以分为单位"),
     *                           @SWG\Property(property="member_discount", type="string", example="0", description="会员折扣金额，以分为单位"),
     *                           @SWG\Property(property="coupon_discount", type="string", example="0", description="优惠券抵扣金额，以分为单位"),
     *                           @SWG\Property(property="coupon_discount_desc", type="string", example="", description="优惠券使用详情"),
     *                           @SWG\Property(property="member_discount_desc", type="string", example="", description="会员折扣使用详情"),
     *                           @SWG\Property(property="shop_id", type="string", example="0", description="门店id"),
     *                           @SWG\Property(property="receipt_type", type="string", example="logistics", description="收货方式。可选值有 logistics:物流;ziti:店铺自提"),
     *                           @SWG\Property(property="ziti_code", type="string", example="0", description="店铺自提码"),
     *                           @SWG\Property(property="ziti_status", type="string", example="NOTZITI", description="店铺自提状态。可选值有 PENDING:等待自提;DONE:自提完成;NOTZITI:自提完成; APPROVE:审核通过,药品自提需要审核"),
     *                           @SWG\Property(property="end_time", type="string", example="", description="订单完成时间"),
     *                           @SWG\Property(property="cancel_status", type="string", example="NO_APPLY_CANCEL", description="取消订单状态。可选值有 NO_APPLY_CANCEL 未申请;WAIT_PROCESS 等待审核;REFUND_PROCESS 退款处理;SUCCESS 取消成功;FAILS 取消失败"),
     *                           @SWG\Property(property="cost_fee", type="string", example="20000", description="商品成本价，以分为单位"),
     *                           @SWG\Property(property="fee_type", type="string", example="CNY", description="货币类型"),
     *                           @SWG\Property(property="fee_rate", type="string", example="1", description="货币汇率"),
     *                           @SWG\Property(property="fee_symbol", type="string", example="￥", description="货币符号"),
     *                           @SWG\Property(property="act_id", type="string", example="", description="营销活动ID，团购ID，社区拼团ID，秒杀活动ID等"),
     *                           @SWG\Property(property="order_class", type="string", example="normal", description="订单种类。可选值有 normal:普通订单;groups:拼团订单;;community 社区活动订单;bargain:助力订单;seckill:秒杀订单;shopguide:导购订单;pointsmall:积分商城"),
     *                           @SWG\Property(property="salesman_id", type="string", example="0", description="导购员ID"),
     *                           @SWG\Property(property="auto_finish_time", type="string", example="", description="订单自动完成时间"),
     *                           @SWG\Property(property="discount_fee", type="string", example="0", description="订单优惠金额，以分为单位"),
     *                           @SWG\Property(property="discount_info", type="string", example="0", description="订单优惠详情"),
     *                           @SWG\Property(property="point", type="string", example="0", description="消费积分"),
     *                           @SWG\Property(property="pay_type", type="string", example="wxpay", description="支付方式"),
     *                           @SWG\Property(property="remark", type="string", example="", description="订单备注"),
     *                           @SWG\Property(property="third_params", type="string", example="", description="第三方特殊字段存储(DC2Type:json_array)"),
     *                           @SWG\Property(property="invoice", type="string", example="", description="发票信息(DC2Type:json_array)"),
     *                           @SWG\Property(property="send_point", type="string", example="0", description="是否分发积分0否 1是"),
     *                           @SWG\Property(property="step_paid_fee", type="string", example="0", description="分阶段付款已支付金额，以分为单位"),
     *                           @SWG\Property(property="delivery_corp_source", type="string", example="", description="快递代码来源"),
     *                           @SWG\Property(property="is_rate", type="string", example="0", description="是否评价"),
     *                           @SWG\Property(property="invoice_number", type="string", example="", description="发票号"),
     *                           @SWG\Property(property="is_invoiced", type="string", example="0", description="是否已开发票"),
     *                           @SWG\Property(property="is_online_order", type="string", example="1", description="是否为线上订单"),
     *                           @SWG\Property(property="delivery_img", type="string", example="", description="快递发货凭证"),
     *                           @SWG\Property(property="pay_status", type="string", example="PAYED", description="支付状态。可选值有 NOTPAY—未支付;PAYED-已支付;ADVANCE_PAY-预付款完成;TAIL_PAY-支付尾款中"),
     *                           @SWG\Property(property="type", type="string", example="0", description="订单类型，0普通订单,1跨境订单,....其他"),
     *                           @SWG\Property(property="taxable_fee", type="string", example="0", description="计税总价，以分为单位"),
     *                           @SWG\Property(property="identity_id", type="string", example="", description="身份证号码"),
     *                           @SWG\Property(property="identity_name", type="string", example="", description="身份证姓名"),
     *                           @SWG\Property(property="total_tax", type="string", example="0", description="总税费"),
     *                           @SWG\Property(property="audit_status", type="string", example="processing", description="跨境订单审核状态 approved成功 processing审核中 rejected审核拒绝"),
     *                           @SWG\Property(property="audit_msg", type="string", example="正在审核订单", description="审核意见"),
     *                           @SWG\Property(property="point_fee", type="string", example="0", description="积分抵扣金额，以分为单位"),
     *                           @SWG\Property(property="point_use", type="string", example="0", description="积分抵扣使用的积分数"),
     *                           @SWG\Property(property="get_point_type", type="string", example="1", description="获取积分类型，0 老订单按订单完成时送,1 新订单按下单时计算送"),
     *                           @SWG\Property(property="get_points", type="string", example="0", description="订单获取积分"),
     *                           @SWG\Property(property="bonus_points", type="string", example="0", description="购物赠送积分"),
     *                           @SWG\Property(property="is_shopscreen", type="string", example="0", description="是否门店订单"),
     *                           @SWG\Property(property="is_logistics", type="string", example="0", description="门店缺货商品总部快递发货"),
     *                           @SWG\Property(property="is_profitsharing", type="string", example="1", description="是否分账订单 1不分账 2分账"),
     *                           @SWG\Property(property="profitsharing_status", type="string", example="1", description="分账状态 1未分账 2已分账"),
     *                           @SWG\Property(property="profitsharing_rate", type="string", example="0", description="分账费率"),
     *                           @SWG\Property(property="order_auto_close_aftersales_time", type="string", example="", description="自动关闭售后时间"),
     *                           @SWG\Property(property="pack", type="string", example="", description="包装"),
     *                           @SWG\Property(property="freight_type", type="string", example="cash", description="运费类型-用于积分商城 cash:现金 point:积分"),
     *                           @SWG\Property(property="item_point", type="string", example="0", description="商品消费总积分"),
     *                           @SWG\Property(property="uppoint_use", type="string", example="0", description="积分抵扣商家补贴的积分数(基础积分-使用的升值积分)"),
     *                           @SWG\Property(property="extra_points", type="string", example="0", description="订单获取额外积分"),
     *                           @SWG\Property(property="bind_auth_code", type="string", example="", description="订单订单验证码"),
     *                           @SWG\Property(property="point_up_use", type="string", example="0", description="积分抵扣使用的积分升值数"),
     *                           @SWG\Property(property="order_status_msg", type="string", example="待发货", description=""),
     *                           @SWG\Property(property="order_status_des", type="string", example="PAYED", description=""),
     *                           @SWG\Property(property="source_name", type="string", example="-", description=""),
     *                          @SWG\Property(property="distributor_info", type="object", description="",
     *                                           @SWG\Property(property="distributor_id", type="string", example="21", description="分销商id"),
     *                                           @SWG\Property(property="company_id", type="string", example="1", description="公司id"),
     *                                           @SWG\Property(property="mobile", type="string", example="18098987759", description="手机号"),
     *                                           @SWG\Property(property="address", type="string", example="鹿岭路6号三亚悦榕庄", description=""),
     *                                           @SWG\Property(property="name", type="string", example="标准版测试用店铺，开启自提自动同步", description=""),
     *                                           @SWG\Property(property="created", type="string", example="1571302265", description=""),
     *                                           @SWG\Property(property="updated", type="string", example="1595559722", description=""),
     *                                           @SWG\Property(property="is_valid", type="string", example="true", description=""),
     *                                           @SWG\Property(property="province", type="string", example="海南省", description=""),
     *                                           @SWG\Property(property="city", type="string", example="三亚", description=""),
     *                                           @SWG\Property(property="area", type="string", example="天涯区", description=""),
     *                                           @SWG\Property(property="regions_id", type="array", description="",
     *                                             @SWG\Items(
     *                                                 type="string", example="460000", description=""
     *                                             ),
     *                                           ),
     *                                           @SWG\Property(property="regions", type="array", description="",
     *                                             @SWG\Items(
     *                                                 type="string", example="海南省", description=""
     *                                             ),
     *                                           ),
     *                                           @SWG\Property(property="contact", type="string", example="松子", description=""),
     *                                           @SWG\Property(property="child_count", type="string", example="0", description=""),
     *                                           @SWG\Property(property="shop_id", type="string", example="0", description="门店id"),
     *                                           @SWG\Property(property="is_default", type="string", example="1", description=""),
     *                                           @SWG\Property(property="is_ziti", type="string", example="1", description=""),
     *                                           @SWG\Property(property="lng", type="string", example="109.498", description=""),
     *                                           @SWG\Property(property="lat", type="string", example="18.21967", description=""),
     *                                           @SWG\Property(property="hour", type="string", example="08:00-21:00", description=""),
     *                                           @SWG\Property(property="auto_sync_goods", type="string", example="1", description=""),
     *                                           @SWG\Property(property="logo", type="string", example="http://mmbiz.qpic.cn/mmbiz_gif/1nDJByqmW2cfI9RKuteqnL3P5AW0cNlWGP9TTnPgYakZECiafK6Tl43UxQzI598U2OZbnMagIRQCEdTbaSvbhRQ/0?wx_fmt=gif", description=""),
     *                                           @SWG\Property(property="banner", type="string", example="http://mmbiz.qpic.cn/mmbiz_jpg/1nDJByqmW2fN5gAtA3mq4kJK7fUeDLuJia1XicD09yExRV5h3mm3x8s9TjpiczDLLaLY655MnyKcHdicnCSjvAiaY0A/0?wx_fmt=jpeg", description=""),
     *                                           @SWG\Property(property="is_audit_goods", type="string", example="1", description=""),
     *                                           @SWG\Property(property="is_delivery", type="string", example="1", description=""),
     *                                           @SWG\Property(property="shop_code", type="string", example="abc12QQ", description=""),
     *                                           @SWG\Property(property="review_status", type="string", example="0", description=""),
     *                                           @SWG\Property(property="source_from", type="string", example="1", description=""),
     *                                           @SWG\Property(property="distributor_self", type="string", example="0", description=""),
     *                                           @SWG\Property(property="is_distributor", type="string", example="1", description=""),
     *                                           @SWG\Property(property="contract_phone", type="string", example="0", description=""),
     *                                           @SWG\Property(property="is_domestic", type="string", example="1", description=""),
     *                                           @SWG\Property(property="is_direct_store", type="string", example="1", description=""),
     *                                           @SWG\Property(property="wechat_work_department_id", type="string", example="5", description=""),
     *                                           @SWG\Property(property="regionauth_id", type="string", example="0", description=""),
     *                                           @SWG\Property(property="is_open", type="string", example="false", description=""),
     *                                           @SWG\Property(property="rate", type="string", example="", description=""),
     *                          ),
     *                           @SWG\Property(property="create_date", type="string", example="2020-11-29 13:24:01", description=""),
     *                           @SWG\Property(property="items", type="array", description="",
     *                             @SWG\Items(
     *                                                                           @SWG\Property(property="id", type="string", example="43", description=""),
     *                                                                           @SWG\Property(property="order_id", type="string", example="3255536000050134", description="订单号"),
     *                                                                           @SWG\Property(property="company_id", type="string", example="1", description="公司id"),
     *                                                                           @SWG\Property(property="user_id", type="string", example="20134", description="用户id"),
     *                                                                           @SWG\Property(property="act_id", type="string", example="", description="营销活动ID，团购ID，社区拼团ID，秒杀活动ID等"),
     *                                                                           @SWG\Property(property="item_id", type="string", example="5019", description=""),
     *                                                                           @SWG\Property(property="item_bn", type="string", example="hj001", description=""),
     *                                                                           @SWG\Property(property="item_name", type="string", example="黄金", description=""),
     *                                                                           @SWG\Property(property="pic", type="string", example="http://bbctest.aixue7.com/image/1/2020/08/03/b22be4303a51a762fa32e226dd40418cnIUa2NLGrtuWGiEt27ZY1npobUQlhLc4", description=""),
     *                                                                           @SWG\Property(property="num", type="integer", example="2", description=""),
     *                                                                           @SWG\Property(property="price", type="integer", example="2", description=""),
     *                                                                           @SWG\Property(property="total_fee", type="integer", example="4", description="订单金额，以分为单位"),
     *                                                                           @SWG\Property(property="templates_id", type="integer", example="94", description=""),
     *                                                                           @SWG\Property(property="rebate", type="integer", example="0", description=""),
     *                                                                           @SWG\Property(property="total_rebate", type="integer", example="0", description="订单总分销金额，以分为单位"),
     *                                                                           @SWG\Property(property="item_fee", type="integer", example="4", description="商品金额，以分为单位"),
     *                                                                           @SWG\Property(property="cost_fee", type="integer", example="20000", description="商品成本价，以分为单位"),
     *                                                                           @SWG\Property(property="item_unit", type="string", example="", description=""),
     *                                                                           @SWG\Property(property="member_discount", type="integer", example="0", description="会员折扣金额，以分为单位"),
     *                                                                           @SWG\Property(property="coupon_discount", type="integer", example="0", description="优惠券抵扣金额，以分为单位"),
     *                                                                           @SWG\Property(property="discount_fee", type="integer", example="0", description="订单优惠金额，以分为单位"),
     *                                                                           @SWG\Property(property="discount_info", type="string", description="",
     *                                                                           ),
     *                                                                           @SWG\Property(property="shop_id", type="string", example="0", description="门店id"),
     *                                                                           @SWG\Property(property="is_total_store", type="string", example="1", description=""),
     *                                                                           @SWG\Property(property="distributor_id", type="string", example="21", description="分销商id"),
     *                                                                           @SWG\Property(property="create_time", type="integer", example="1606627441", description="订单创建时间"),
     *                                                                           @SWG\Property(property="update_time", type="integer", example="1606630242", description="订单更新时间"),
     *                                                                           @SWG\Property(property="delivery_corp", type="string", example="", description="快递公司"),
     *                                                                           @SWG\Property(property="delivery_code", type="string", example="", description="快递单号"),
     *                                                                           @SWG\Property(property="delivery_img", type="string", example="", description="快递发货凭证"),
     *                                                                           @SWG\Property(property="delivery_time", type="string", example="", description="发货时间"),
     *                                                                           @SWG\Property(property="delivery_status", type="string", example="DONE", description="发货状态。可选值有 DONE—已发货;PENDING—待发货;PARTAIL-部分发货"),
     *                                                                           @SWG\Property(property="aftersales_status", type="string", example="", description=""),
     *                                                                           @SWG\Property(property="refunded_fee", type="integer", example="0", description=""),
     *                                                                           @SWG\Property(property="fee_type", type="string", example="CNY", description="货币类型"),
     *                                                                           @SWG\Property(property="fee_rate", type="integer", example="1", description="货币汇率"),
     *                                                                           @SWG\Property(property="fee_symbol", type="string", example="￥", description="货币符号"),
     *                                                                           @SWG\Property(property="cny_fee", type="integer", example="4", description=""),
     *                                                                           @SWG\Property(property="item_point", type="integer", example="0", description="商品消费总积分"),
     *                                                                           @SWG\Property(property="point", type="integer", example="0", description="消费积分"),
     *                                                                           @SWG\Property(property="item_spec_desc", type="string", example="", description=""),
     *                                                                           @SWG\Property(property="order_item_type", type="string", example="normal", description=""),
     *                                                                           @SWG\Property(property="volume", type="integer", example="0", description=""),
     *                                                                           @SWG\Property(property="weight", type="integer", example="0", description=""),
     *                                                                           @SWG\Property(property="is_rate", type="string", example="", description="是否评价"),
     *                                                                           @SWG\Property(property="auto_close_aftersales_time", type="string", example="", description=""),
     *                                                                           @SWG\Property(property="share_points", type="integer", example="0", description=""),
     *                                                                           @SWG\Property(property="point_fee", type="integer", example="0", description="积分抵扣金额，以分为单位"),
     *                                                                           @SWG\Property(property="is_logistics", type="string", example="", description="门店缺货商品总部快递发货"),
     *                                                                           @SWG\Property(property="delivery_item_num", type="integer", example="2", description=""),
     *                                                                           @SWG\Property(property="get_points", type="integer", example="0", description="订单获取积分"),
     *                             ),
     *                           ),
     *                           @SWG\Property(property="distributor_name", type="string", example="标准版测试用店铺，开启自提自动同步", description=""),
     *                           @SWG\Property(property="delivery_type", type="string", example="new", description=""),
     *                           @SWG\Property(property="orders_delivery_id", type="string", example="1", description=""),
     *                           @SWG\Property(property="is_all_delivery", type="string", example="", description=""),
     *                           @SWG\Property(property="delivery_corp_name", type="string", example="顺丰快递", description=""),
     *                          @SWG\Property(property="profit_info", type="object", description="",
     *                                           @SWG\Property(property="id", type="string", example="830", description=""),
     *                                           @SWG\Property(property="order_id", type="string", example="3255536000050134", description="订单号"),
     *                                           @SWG\Property(property="order_profit_status", type="string", example="0", description=""),
     *                                           @SWG\Property(property="company_id", type="string", example="1", description="公司id"),
     *                                           @SWG\Property(property="total_fee", type="string", example="16", description="订单金额，以分为单位"),
     *                                           @SWG\Property(property="profit_type", type="string", example="2", description=""),
     *                                           @SWG\Property(property="user_id", type="string", example="20134", description="用户id"),
     *                                           @SWG\Property(property="dealer_id", type="string", example="0", description=""),
     *                                           @SWG\Property(property="distributor_id", type="string", example="21", description="分销商id"),
     *                                           @SWG\Property(property="order_distributor_id", type="string", example="21", description=""),
     *                                           @SWG\Property(property="distributor_nid", type="string", example="0", description=""),
     *                                           @SWG\Property(property="seller_id", type="string", example="0", description=""),
     *                                           @SWG\Property(property="popularize_distributor_id", type="string", example="0", description=""),
     *                                           @SWG\Property(property="popularize_seller_id", type="string", example="0", description=""),
     *                                           @SWG\Property(property="proprietary", type="string", example="2", description=""),
     *                                           @SWG\Property(property="popularize_proprietary", type="string", example="2", description=""),
     *                                           @SWG\Property(property="dealers", type="string", example="0", description=""),
     *                                           @SWG\Property(property="distributor", type="string", example="0", description=""),
     *                                           @SWG\Property(property="seller", type="string", example="0", description=""),
     *                                           @SWG\Property(property="popularize_distributor", type="string", example="0", description=""),
     *                                           @SWG\Property(property="popularize_seller", type="string", example="0", description=""),
     *                                           @SWG\Property(property="commission", type="string", example="0", description=""),
     *                                           @SWG\Property(property="rule", type="string", example="", description=""),
     *                                           @SWG\Property(property="pay_fee", type="string", example="16", description=""),
     *                                           @SWG\Property(property="created", type="string", example="1606627442", description=""),
     *                                           @SWG\Property(property="updated", type="string", example="1606627442", description=""),
     *                                           @SWG\Property(property="plan_close_time", type="string", example="", description=""),
     *                                           @SWG\Property(property="title", type="string", example="黄金...", description="订单标题"),
     *                                           @SWG\Property(property="mobile", type="string", example="13095920688", description="手机号"),
     *                                           @SWG\Property(property="total_rebate", type="string", example="0", description="订单总分销金额，以分为单位"),
     *                                           @SWG\Property(property="order_status", type="string", example="PAYED", description="订单状态。可选值有 DONE—订单完成;NOTPAY—未支付;PART_PAYMENT-部分付款;WAIT_GROUPS_SUCCESS-等待拼团成功;PAYED-已支付;CANCEL—已取消;WAIT_BUYER_CONFIRM-待用户收货"),
     *                                           @SWG\Property(property="order_source", type="string", example="member", description="订单来源。可选值有 member-用户自主下单;shop-商家代客下单"),
     *                                           @SWG\Property(property="order_type", type="string", example="normal", description="订单类型。可选值有 normal:普通实体订单"),
     *                                           @SWG\Property(property="auto_cancel_time", type="string", example="1606627501", description="订单自动取消时间"),
     *                                           @SWG\Property(property="is_distribution", type="string", example="1", description="是否分销订单"),
     *                                           @SWG\Property(property="source_id", type="string", example="0", description="订单来源id"),
     *                                           @SWG\Property(property="monitor_id", type="string", example="0", description="订单监控页面id"),
     *                                           @SWG\Property(property="delivery_corp", type="string", example="", description="快递公司"),
     *                                           @SWG\Property(property="delivery_code", type="string", example="", description="快递单号"),
     *                                           @SWG\Property(property="delivery_time", type="string", example="", description="发货时间"),
     *                                           @SWG\Property(property="delivery_status", type="string", example="DONE", description="发货状态。可选值有 DONE—已发货;PENDING—待发货;PARTAIL-部分发货"),
     *                                           @SWG\Property(property="receiver_name", type="string", example="张三", description="收货人姓名"),
     *                                           @SWG\Property(property="receiver_mobile", type="string", example="13095920688", description="收货人手机号"),
     *                                           @SWG\Property(property="receiver_zip", type="string", example="510000", description="收货人邮编"),
     *                                           @SWG\Property(property="receiver_state", type="string", example="广东省", description="收货人所在省份"),
     *                                           @SWG\Property(property="receiver_city", type="string", example="广州市", description="收货人所在城市"),
     *                                           @SWG\Property(property="receiver_district", type="string", example="海珠区", description="收货人所在地区"),
     *                                           @SWG\Property(property="receiver_address", type="string", example="新港中路397号", description="收货人详细地址"),
     *                                           @SWG\Property(property="create_time", type="string", example="1606627441", description="订单创建时间"),
     *                                           @SWG\Property(property="update_time", type="string", example="1606630242", description="订单更新时间"),
     *                                           @SWG\Property(property="freight_fee", type="string", example="10", description="运费价格，以分为单位"),
     *                                           @SWG\Property(property="item_fee", type="string", example="6", description="商品金额，以分为单位"),
     *                                           @SWG\Property(property="member_discount", type="string", example="0", description="会员折扣金额，以分为单位"),
     *                                           @SWG\Property(property="coupon_discount", type="string", example="0", description="优惠券抵扣金额，以分为单位"),
     *                                           @SWG\Property(property="coupon_discount_desc", type="string", example="", description="优惠券使用详情"),
     *                                           @SWG\Property(property="member_discount_desc", type="string", example="", description="会员折扣使用详情"),
     *                                           @SWG\Property(property="shop_id", type="string", example="0", description="门店id"),
     *                                           @SWG\Property(property="receipt_type", type="string", example="logistics", description="收货方式。可选值有 logistics:物流;ziti:店铺自提"),
     *                                           @SWG\Property(property="ziti_code", type="string", example="0", description="店铺自提码"),
     *                                           @SWG\Property(property="ziti_status", type="string", example="NOTZITI", description="店铺自提状态。可选值有 PENDING:等待自提;DONE:自提完成;NOTZITI:自提完成; APPROVE:审核通过,药品自提需要审核"),
     *                                           @SWG\Property(property="end_time", type="string", example="", description="订单完成时间"),
     *                                           @SWG\Property(property="cancel_status", type="string", example="NO_APPLY_CANCEL", description="取消订单状态。可选值有 NO_APPLY_CANCEL 未申请;WAIT_PROCESS 等待审核;REFUND_PROCESS 退款处理;SUCCESS 取消成功;FAILS 取消失败"),
     *                                           @SWG\Property(property="cost_fee", type="string", example="20000", description="商品成本价，以分为单位"),
     *                                           @SWG\Property(property="fee_type", type="string", example="CNY", description="货币类型"),
     *                                           @SWG\Property(property="fee_rate", type="string", example="1", description="货币汇率"),
     *                                           @SWG\Property(property="fee_symbol", type="string", example="￥", description="货币符号"),
     *                                           @SWG\Property(property="act_id", type="string", example="", description="营销活动ID，团购ID，社区拼团ID，秒杀活动ID等"),
     *                                           @SWG\Property(property="order_class", type="string", example="normal", description="订单种类。可选值有 normal:普通订单;groups:拼团订单;;community 社区活动订单;bargain:助力订单;seckill:秒杀订单;shopguide:导购订单;pointsmall:积分商城"),
     *                                           @SWG\Property(property="salesman_id", type="string", example="0", description="导购员ID"),
     *                                           @SWG\Property(property="auto_finish_time", type="string", example="", description="订单自动完成时间"),
     *                                           @SWG\Property(property="discount_fee", type="string", example="0", description="订单优惠金额，以分为单位"),
     *                                           @SWG\Property(property="discount_info", type="string", example="0", description="订单优惠详情"),
     *                                           @SWG\Property(property="point", type="string", example="0", description="消费积分"),
     *                                           @SWG\Property(property="pay_type", type="string", example="wxpay", description="支付方式"),
     *                                           @SWG\Property(property="remark", type="string", example="", description="订单备注"),
     *                                           @SWG\Property(property="third_params", type="string", example="", description="第三方特殊字段存储(DC2Type:json_array)"),
     *                                           @SWG\Property(property="invoice", type="string", example="", description="发票信息(DC2Type:json_array)"),
     *                                           @SWG\Property(property="send_point", type="string", example="0", description="是否分发积分0否 1是"),
     *                                           @SWG\Property(property="step_paid_fee", type="string", example="0", description="分阶段付款已支付金额，以分为单位"),
     *                                           @SWG\Property(property="delivery_corp_source", type="string", example="", description="快递代码来源"),
     *                                           @SWG\Property(property="is_rate", type="string", example="0", description="是否评价"),
     *                                           @SWG\Property(property="invoice_number", type="string", example="", description="发票号"),
     *                                           @SWG\Property(property="is_invoiced", type="string", example="0", description="是否已开发票"),
     *                                           @SWG\Property(property="is_online_order", type="string", example="1", description="是否为线上订单"),
     *                                           @SWG\Property(property="delivery_img", type="string", example="", description="快递发货凭证"),
     *                                           @SWG\Property(property="pay_status", type="string", example="PAYED", description="支付状态。可选值有 NOTPAY—未支付;PAYED-已支付;ADVANCE_PAY-预付款完成;TAIL_PAY-支付尾款中"),
     *                                           @SWG\Property(property="type", type="string", example="0", description="订单类型，0普通订单,1跨境订单,....其他"),
     *                                           @SWG\Property(property="taxable_fee", type="string", example="0", description="计税总价，以分为单位"),
     *                                           @SWG\Property(property="identity_id", type="string", example="", description="身份证号码"),
     *                                           @SWG\Property(property="identity_name", type="string", example="", description="身份证姓名"),
     *                                           @SWG\Property(property="total_tax", type="string", example="0", description="总税费"),
     *                                           @SWG\Property(property="audit_status", type="string", example="processing", description="跨境订单审核状态 approved成功 processing审核中 rejected审核拒绝"),
     *                                           @SWG\Property(property="audit_msg", type="string", example="正在审核订单", description="审核意见"),
     *                                           @SWG\Property(property="point_fee", type="string", example="0", description="积分抵扣金额，以分为单位"),
     *                                           @SWG\Property(property="point_use", type="string", example="0", description="积分抵扣使用的积分数"),
     *                                           @SWG\Property(property="get_point_type", type="string", example="1", description="获取积分类型，0 老订单按订单完成时送,1 新订单按下单时计算送"),
     *                                           @SWG\Property(property="get_points", type="string", example="0", description="订单获取积分"),
     *                                           @SWG\Property(property="bonus_points", type="string", example="0", description="购物赠送积分"),
     *                                           @SWG\Property(property="is_shopscreen", type="string", example="0", description="是否门店订单"),
     *                                           @SWG\Property(property="is_logistics", type="string", example="0", description="门店缺货商品总部快递发货"),
     *                                           @SWG\Property(property="is_profitsharing", type="string", example="1", description="是否分账订单 1不分账 2分账"),
     *                                           @SWG\Property(property="profitsharing_status", type="string", example="1", description="分账状态 1未分账 2已分账"),
     *                                           @SWG\Property(property="profitsharing_rate", type="string", example="0", description="分账费率"),
     *                                           @SWG\Property(property="order_auto_close_aftersales_time", type="string", example="", description="自动关闭售后时间"),
     *                                           @SWG\Property(property="pack", type="string", example="", description="包装"),
     *                                           @SWG\Property(property="freight_type", type="string", example="cash", description="运费类型-用于积分商城 cash:现金 point:积分"),
     *                                           @SWG\Property(property="item_point", type="string", example="0", description="商品消费总积分"),
     *                                           @SWG\Property(property="uppoint_use", type="string", example="0", description="积分抵扣商家补贴的积分数(基础积分-使用的升值积分)"),
     *                                           @SWG\Property(property="extra_points", type="string", example="0", description="订单获取额外积分"),
     *                                           @SWG\Property(property="bind_auth_code", type="string", example="", description="订单订单验证码"),
     *                                           @SWG\Property(property="point_up_use", type="string", example="0", description="积分抵扣使用的积分升值数"),
     *                          ),
     *                           @SWG\Property(property="profit_fee", type="string", example="0", description=""),
     *                           @SWG\Property(property="username", type="string", example="", description=""),
     *                           @SWG\Property(property="avatar", type="string", example="", description=""),
     *                 ),
     *               ),
     *               @SWG\Property(property="seller_count", type="integer", example="0", description=""),
     *               @SWG\Property(property="popularize_seller_count", type="integer", example="0", description=""),
     *               @SWG\Property(property="total_fee_count", type="integer", example="16", description=""),
     *               @SWG\Property(property="pager", type="object", description="",
     *                   @SWG\Property(property="count", type="integer", example="1", description=""),
     *               ),
     *               @SWG\Property(property="profit_fee_count", type="integer", example="0", description=""),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function getSalespersonOrderList(Request $request)
    {
        $authInfo = $this->auth->user();

        $page = $request->input('page', 1);
        $limit = $request->input('page_size', $request->input('pageSize', 20));

        $orderType = 'normal';

        $filter['company_id'] = $authInfo['company_id'];
        $orderProfitFilter['ono.company_id'] = $authInfo['company_id'];
        $orderServiceType = $orderType;
        $filter['order_type'] = $orderType;
        $orderProfitFilter['ono.order_type'] = $orderType;
        $filter['pay_status'] = 'PAYED';
        $orderProfitFilter['ono.pay_status'] = 'PAYED';
        $isOnlineOrder = $request->get('is_online_order');
        if ($isOnlineOrder == 'true') {
            $filter['order_source'] = ['shop_online', 'member'];
            $orderProfitFilter['ono.order_source'] = ['shop_online', 'member'];
        } elseif ($isOnlineOrder == 'false') {
            $filter['order_source'] = 'shop_offline';
            $orderProfitFilter['ono.order_source'] = 'shop_offline';
        }

        $date = $request->input('date', 'other');
        if ('other' == $date) {
            $startTime = $request->input('start_time');
            $startTime = $startTime ?: date('Ym01 00:00:00', time());
            $j = date("t", strtotime($startTime)); //获取月份天数
            $startTime = strtotime($startTime); //每隔一天赋值给数组
            $endTime = $startTime + $j * 86400 - 1; //每隔一天赋值给数组
        } else {
            switch ($date) {
            case 'today':
                $startTime = strtotime(date('Ymd 00:00:00', time()));
                break;
            case 'week':
                $startTime = strtotime(date('Ymd', strtotime('-7 days')));
                break;
            case 'month':
                $startTime = strtotime(date('Ymd', strtotime('-30 days')));
                break;
            }
            $endTime = strtotime(date('Ymd 23:59:59', time()));
        }
        $orderProfitFilter['op.order_profit_status'] = 1;
        if ($request->input('is_popularize_seller', 0)) {
            $orderProfitFilter['op.popularize_seller_id'] = $authInfo['salesperson_id'];
        } elseif ($request->input('is_seller', 0)) {
            $orderProfitFilter['op.seller_id'] = $authInfo['salesperson_id'];
        } else {
            $orderProfitFilter['salesperson_id'] = $authInfo['salesperson_id'];
        }
        $orderProfitFilter['op.created|gte'] = $startTime;
        $orderProfitFilter['op.created|lte'] = $endTime;

        $orderProfitService = new OrderProfitService();
        $orderProfitResult = $orderProfitService->lists($orderProfitFilter, '*', $page, $limit, ['op.created' => 'DESC']);
        $result['total_count'] = $orderProfitResult['total_count'];
        $result['list'] = [];
        if ($orderProfitResult['total_count'] ?? 0) {
            $orderIds = array_column($orderProfitResult['list'], 'order_id');
            $userIds = array_column($orderProfitResult['list'], 'user_id');
            $orderServiceType = $orderType;
            $orderService = $this->getOrderService($orderServiceType);
            $filter['order_type'] = $orderType;
            $filter['order_id'] = $orderIds;
            $orderResult = $orderService->getOrderList($filter);
            $orderProfitList = ($orderProfitResult['list'] ?? 0) ? array_column($orderProfitResult['list'], null, 'order_id') : [];

            $wechatUserService = new WechatUserService();
            $userFilter = [
                'company_id' => $authInfo['company_id'],
                'user_id' => $userIds,
            ];
            $userWeachatList = $userWechatUser = $wechatUserService->getWechatUserList($userFilter);
            $userWechatData = array_column($userWeachatList, null, 'user_id');

            foreach ($orderResult['list'] as &$v) {
                $v['profit_info'] = $orderProfitList[$v['order_id']] ?? [];
                $v['profit_fee'] = isset($orderProfitList[$v['order_id']]) ? $orderProfitList[$v['order_id']]['popularize_distributor'] : 0;
                $v['username'] = isset($userWechatData[$v['user_id']]) ? $userWechatData[$v['user_id']]['nickname'] : '';
                $v['avatar'] = isset($userWechatData[$v['user_id']]) ? $userWechatData[$v['user_id']]['headimgurl'] : '';
            }
            $result['list'] = $orderResult['list'];
            $totalFeeCount = $orderProfitService->sum($orderProfitFilter, 'op.pay_fee');
            $totalCount = $orderProfitService->count($orderProfitFilter);
            $result['seller_count'] = $orderProfitService->sum($orderProfitFilter, 'op.seller');
            $result['popularize_seller_count'] = $orderProfitService->sum($orderProfitFilter, 'op.popularize_seller');
            $result['total_fee_count'] = $totalFeeCount;
            $result['pager']['count'] = $totalCount;
            $result['profit_fee_count'] = $result['seller_count'] + $result['popularize_seller_count'];
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/admin/wxapp/order/getSalepersonOrdersList",
     *     summary="获取店铺订单列表",
     *     tags={"订单"},
     *     description="获取用户店铺订单列表,mobile下的所有compaby_id的订单",
     *     operationId="getOrdersList",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide"),
     *     @SWG\Parameter( name="page", in="query", description="页数", required=true, type="integer", default="1"),
     *     @SWG\Parameter( name="page_size", in="query", description="每页数量", required=true, type="integer", default="20"),
     *     @SWG\Parameter( name="order_type", in="query", description="订单类型", type="string" ),
     *     @SWG\Parameter( name="status", in="query", description="订单状态", type="string" ),
     *     @SWG\Parameter( name="time_start_begin", in="query", description="查询开始时间", type="string" ),
     *     @SWG\Parameter( name="time_start_end", in="query", description="查询结束时间", type="string" ),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="list", type="array", description="",
     *                 @SWG\Items(
     *                           @SWG\Property(property="order_id", type="string", example="3321684000300350", description="订单号"),
     *                           @SWG\Property(property="title", type="string", example="测试多规格1...", description="订单标题"),
     *                           @SWG\Property(property="total_fee", type="string", example="16", description="订单金额，以分为单位"),
     *                           @SWG\Property(property="company_id", type="string", example="1", description="公司id"),
     *                           @SWG\Property(property="shop_id", type="string", example="0", description="门店id"),
     *                           @SWG\Property(property="store_name", type="string", example="", description=""),
     *                           @SWG\Property(property="user_id", type="string", example="20350", description="用户id"),
     *                           @SWG\Property(property="mobile", type="string", example="17638125092", description="手机号"),
     *                           @SWG\Property(property="order_type", type="string", example="normal", description="订单类型。可选值有 normal:普通实体订单"),
     *                           @SWG\Property(property="order_status", type="string", example="PAYED", description="订单状态。可选值有 DONE—订单完成;NOTPAY—未支付;PART_PAYMENT-部分付款;WAIT_GROUPS_SUCCESS-等待拼团成功;PAYED-已支付;CANCEL—已取消;WAIT_BUYER_CONFIRM-待用户收货"),
     *                           @SWG\Property(property="create_time", type="string", example="1612343172", description="订单创建时间"),
     *                           @SWG\Property(property="update_time", type="string", example="1612343255", description="订单更新时间"),
     *                           @SWG\Property(property="source_id", type="string", example="0", description="订单来源id"),
     *                           @SWG\Property(property="monitor_id", type="string", example="0", description="订单监控页面id"),
     *                           @SWG\Property(property="authorizer_appid", type="string", example="", description=""),
     *                           @SWG\Property(property="wxa_appid", type="string", example="", description=""),
     *                           @SWG\Property(property="is_distribution", type="string", example="1", description="是否分销订单"),
     *                           @SWG\Property(property="total_rebate", type="string", example="0", description="订单总分销金额，以分为单位"),
     *                           @SWG\Property(property="delivery_corp", type="string", example="", description="快递公司"),
     *                           @SWG\Property(property="delivery_code", type="string", example="", description="快递单号"),
     *                           @SWG\Property(property="delivery_time", type="string", example="1612343255", description="发货时间"),
     *                           @SWG\Property(property="delivery_status", type="string", example="DONE", description="发货状态。可选值有 DONE—已发货;PENDING—待发货;PARTAIL-部分发货"),
     *                           @SWG\Property(property="member_discount", type="string", example="4", description="会员折扣金额，以分为单位"),
     *                           @SWG\Property(property="coupon_discount", type="string", example="0", description="优惠券抵扣金额，以分为单位"),
     *                           @SWG\Property(property="coupon_discount_desc", type="string", example="", description="优惠券使用详情"),
     *                           @SWG\Property(property="member_discount_desc", type="string", example="", description="会员折扣使用详情"),
     *                           @SWG\Property(property="order_class", type="string", example="normal", description="订单种类。可选值有 normal:普通订单;groups:拼团订单;;community 社区活动订单;bargain:助力订单;seckill:秒杀订单;shopguide:导购订单;pointsmall:积分商城"),
     *                           @SWG\Property(property="cancel_status", type="string", example="NO_APPLY_CANCEL", description="取消订单状态。可选值有 NO_APPLY_CANCEL 未申请;WAIT_PROCESS 等待审核;REFUND_PROCESS 退款处理;SUCCESS 取消成功;FAILS 取消失败"),
     *                           @SWG\Property(property="end_time", type="string", example="", description="订单完成时间"),
     *                           @SWG\Property(property="promoter_user_id", type="string", example="", description=""),
     *                           @SWG\Property(property="fee_type", type="string", example="CNY", description="货币类型"),
     *                           @SWG\Property(property="fee_rate", type="string", example="1", description="货币汇率"),
     *                           @SWG\Property(property="fee_symbol", type="string", example="￥", description="货币符号"),
     *                           @SWG\Property(property="salesman_id", type="string", example="0", description="导购员ID"),
     *                           @SWG\Property(property="promoter_shop_id", type="string", example="0", description=""),
     *                           @SWG\Property(property="source_name", type="string", example="-", description=""),
     *                           @SWG\Property(property="create_date", type="string", example="2021-02-03 17:06:12", description=""),
     *                 ),
     *               ),
     *              @SWG\Property(property="pager", type="object", description="",
     *                   @SWG\Property(property="count", type="string", example="7999", description="总记录数"),
     *                   @SWG\Property(property="page_no", type="integer", example="1", description="页码"),
     *                   @SWG\Property(property="page_size", type="integer", example="20", description="每页记录条数"),
     *              ),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function getSalespersonOrdersList(Request $request)
    {
        $authInfo = $this->auth->user();

        $filter['company_id'] = $authInfo['company_id'];
        $filter['distributor_id'] = $authInfo['distributor_id'];

        $page = $request->input('page', 1);
        $limit = $request->input('page_size', 20);

        $filter['order_type'] = $request->input('order_type', 'normal');
        if ($request->input('order_class')) {
            $filter['order_class'] = $request->input('order_class');
        }
        $status = $request->input('status', 0) ? $request->input('status') : 0;
        switch ($status) {
        case 1:    // 待发货
            $filter['order_status'] = ['PAYED'];
            $filter['ziti_status'] = 'NOTZITI';
            $filter['cancel_status|in'] = ['NO_APPLY_CANCEL', 'FAILS'];
            break;
        case 2:  // 已发货
            $filter['order_status'] = ['WAIT_BUYER_CONFIRM', 'DONE'];
            $filter['ziti_status'] = 'NOTZITI';
            break;
        }

        // 发货时间
        if ($request->input('time_start_begin', 0) && $request->input('time_start_end', 0)) {
            $filter['delivery_time|gte'] = strtotime($request->input('time_start_begin', 0));
            $filter['delivery_time|lte'] = strtotime($request->input('time_start_end', 0)) + 86399;
        }

        // $filter['salesman_id'] = $authInfo['salesperson_id'];
        $orderService = $this->getOrderService($filter['order_type']);
        $result = $orderService->getOrderList($filter, $page, $limit);

        $result['total_count'] = $result['pager']['count'] ?? 0;
        unset($result['pager']);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/admin/wxapp/order/delivery",
     *     summary="订单发货",
     *     tags={"订单"},
     *     description="订单发货",
     *     operationId="delivery",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="order_id", in="query", description="订单id", required=true, type="string"),
     *     @SWG\Parameter( name="delivery_corp", in="query", description="物流公司编码", required=true, type="string"),
     *     @SWG\Parameter( name="delivery_code", in="query", description="物流公司快递号", required=true, type="string"),
     *     @SWG\Parameter( name="type", in="query", description="发货类型 new新发货单 old 旧发货单", required=true, type="string"),
     *     @SWG\Parameter( name="sepInfo", in="query", description="拆单发货json数据", required=false, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="order_id", type="string", example="3308712000100353", description="订单号"),
     *               @SWG\Property(property="authorizer_appid", type="string", example="", description="公众号的appid"),
     *               @SWG\Property(property="wxa_appid", type="string", example="", description="小程序的appid"),
     *               @SWG\Property(property="title", type="string", example="大屏测试...", description="订单标题"),
     *               @SWG\Property(property="total_fee", type="string", example="1", description="订单金额，以分为单位"),
     *               @SWG\Property(property="company_id", type="string", example="1", description="公司id"),
     *               @SWG\Property(property="shop_id", type="string", example="0", description="店铺id"),
     *               @SWG\Property(property="store_name", type="string", example="", description="店铺名称"),
     *               @SWG\Property(property="user_id", type="string", example="20353", description="用户id"),
     *               @SWG\Property(property="salesman_id", type="string", example="0", description="导购员ID"),
     *               @SWG\Property(property="promoter_user_id", type="string", example="", description="推广员user_id"),
     *               @SWG\Property(property="promoter_shop_id", type="string", example="0", description="推广员店铺id，实际为推广员的user_id"),
     *               @SWG\Property(property="source_id", type="string", example="0", description="订单来源id"),
     *               @SWG\Property(property="monitor_id", type="string", example="0", description="订单监控页面id"),
     *               @SWG\Property(property="mobile", type="string", example="18530870713", description="手机号"),
     *               @SWG\Property(property="order_class", type="string", example="normal", description="订单种类。可选值有 normal:普通订单;groups:拼团订单;;community 社区活动订单;bargain:助力订单;seckill:秒杀订单;shopguide:导购订单"),
     *               @SWG\Property(property="order_type", type="string", example="normal", description="订单类型。可选值有 service 服务业订单;bargain 砍价订单;distribution 分销订单;normal 普通实体订单"),
     *               @SWG\Property(property="order_status", type="string", example="PAYED", description="订单状态。可选值有 DONE—订单完成;PAYED-已支付;NOTPAY—未支付;CANCEL—已取消;WAIT_BUYER_CONFIRM-待用户收货"),
     *               @SWG\Property(property="create_time", type="integer", example="1611222541", description="订单创建时间"),
     *               @SWG\Property(property="update_time", type="integer", example="1611908346", description="订单更新时间"),
     *               @SWG\Property(property="is_distribution", type="string", example="", description="是否是分销订单"),
     *               @SWG\Property(property="total_rebate", type="integer", example="0", description="订单总分销金额，以分为单位"),
     *               @SWG\Property(property="delivery_corp", type="string", example="", description="快递公司"),
     *               @SWG\Property(property="delivery_code", type="string", example="", description="快递单号"),
     *               @SWG\Property(property="member_discount", type="integer", example="0", description="会员折扣金额，以分为单位"),
     *               @SWG\Property(property="coupon_discount", type="integer", example="0", description="优惠券抵扣金额，以分为单位"),
     *               @SWG\Property(property="coupon_discount_desc", type="array", description="",
     *                 @SWG\Items(
     *                 ),
     *               ),
     *               @SWG\Property(property="member_discount_desc", type="array", description="",
     *                 @SWG\Items(
     *                 ),
     *               ),
     *               @SWG\Property(property="delivery_status", type="string", example="DONE", description="发货状态。可选值有 DONE—已发货;PENDING—待发货;PARTAIL_DELIVERY-部分发货"),
     *               @SWG\Property(property="delivery_time", type="integer", example="1611908346", description="发货时间"),
     *               @SWG\Property(property="cancel_status", type="string", example="NO_APPLY_CANCEL", description="取消订单状态。可选值有 NO_APPLY_CANCEL 未申请;WAIT_PROCESS 等待审核;REFUND_PROCESS 退款处理;SUCCESS 取消成功;FAILS 取消失败"),
     *               @SWG\Property(property="end_time", type="string", example="", description="订单完成时间"),
     *               @SWG\Property(property="fee_type", type="string", example="CNY", description="货币类型"),
     *               @SWG\Property(property="fee_rate", type="integer", example="1", description="货币汇率"),
     *               @SWG\Property(property="fee_symbol", type="string", example="￥", description="货币符号"),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function delivery(Request $request)
    {
        $authInfo = $this->auth->user();
        $params = $request->all();
        $rules = [
            'order_id' => ['required', '订单号缺少！'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $params['company_id'] = $authInfo['company_id'];
        $params['type'] = 'new';
        $params['delivery_type'] = 'batch';
        $params['operator_type'] = 'salesperson';
        $params['operator_id'] = $authInfo['salesperson_id'];
        $orderAssociationService = new OrderAssociationService();
        $order = $orderAssociationService->getOrder($params['company_id'], $params['order_id']);
        if (!$order) {
            return $this->response->error('此订单不存在！', 422);
        }
        $orderService = $this->getOrderServiceByOrderInfo($order);
        $result = $orderService->delivery($params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/wxapp/trackerpull",
     *     summary="物流查询",
     *     tags={"订单"},
     *     description="物流查询",
     *     operationId="trackerpull",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="order_id", in="query", description="订单号", type="string", ),
     *     @SWG\Parameter( name="order_type", in="query", description="订单类型", type="string", ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                   @SWG\Property(property="AcceptTime", type="string", description="物流时间"),
     *                   @SWG\Property(property="AcceptStation", type="string", description="物流描述"),
     *             ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function trackerpull(Request $request)
    {
        $orderType = $request->input('order_type') ? $request->input('order_type') : 'normal';
        $orderService = $this->getOrderService($orderType);
        $orderId = $request->input('order_id');
        $authInfo = $this->auth->user();
        $order = $orderService->getOrderInfo($authInfo['company_id'], $orderId);

        if (!$order || $authInfo['distributor_id'] != $order['orderInfo']['distributor_id']) {
            return $this->response->array([['AcceptTime' => time(), 'AcceptStation' => '暂无物流信息']]);
        }
        try {
            $tracker = new LogisticTracker();
            if ($result = $tracker->sfbspCheck($order['orderInfo']['delivery_code'], $order['orderInfo']['delivery_corp'], $authInfo['company_id'], $order['orderInfo']['receiver_mobile'])) {
                return $this->response->array($result);
            }

            if (isset($order['orderInfo']['delivery_corp_source']) && $order['orderInfo']['delivery_corp_source'] == 'kuaidi100') {
                $result = $tracker->kuaidi100($order['orderInfo']['delivery_corp'], $order['orderInfo']['delivery_code'], $authInfo['company_id'], $order['orderInfo']['receiver_mobile']);
            } else {
                //需要根据订单
                $result = $tracker->pullFromHqepay($order['orderInfo']['delivery_code'], $order['orderInfo']['delivery_corp'], $authInfo['company_id'], $order['orderInfo']['receiver_mobile']);
            }
        } catch (\Exception $exception) {
            return $this->response->array([['AcceptTime' => time(), 'AcceptStation' => '暂无物流信息']]);
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/order/process/{order_id}",
     *     summary="查看订单操作记录",
     *     tags={"订单"},
     *     description="查看订单操作记录",
     *     operationId="getOrderProcessLog",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="order_id", in="path", description="订单号", required=true, type="integer", ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="status", type="string"),
     *                 )
     *             ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function getOrderProcessLog($orderId, Request $request)
    {
        $authInfo = $this->auth->user();
        $companyId = $authInfo['company_id'];
        $orderProcessLogService = new OrderProcessLogService();
        $filter = [
            'order_id' => $orderId,
            'company_id' => $companyId,
        ];
        $result = $orderProcessLogService->getLists($filter, '*', 1, -1, ['create_time' => 'desc', 'id' => 'desc']);
        return $result;
    }
}
