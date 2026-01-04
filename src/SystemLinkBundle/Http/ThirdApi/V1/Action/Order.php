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

namespace SystemLinkBundle\Http\ThirdApi\V1\Action;

use Illuminate\Http\Request;

use SystemLinkBundle\Http\Controllers\Controller as Controller;

use SystemLinkBundle\Events\TradeFinishEvent;
use SystemLinkBundle\Events\TradeRefundEvent;
use SystemLinkBundle\Events\TradeAftersalesEvent;

use OrdersBundle\Entities\ServiceOrders;

use SystemLinkBundle\Services\ShopexErp\OrderService;


use OrdersBundle\Services\OrderAssociationService;

use OrdersBundle\Services\Orders\NormalOrderService;

use PromotionsBundle\Entities\PromotionGroupsTeam;

use AftersalesBundle\Entities\Aftersales;

use OrdersBundle\Services\TradeService;
use OrdersBundle\Services\UserOrderInvoiceService;

class Order extends Controller
{
    // TEST-普通订单测试触发
    public function testEvent($order_id)
    {
        // $eventData = new ServiceOrders;
        // // $eventData->setOrderId('2565683000268913');
        // $eventData->setOrderId('2544746002481773');
        // $eventData->setCompanyId(1);
        // $eventData->sourceType = 'normal';

        $tradeService = new TradeService();
        $tradeData = $tradeService->tradeRepository->getInfo(['order_id' => $order_id]);
        $tradeId = $tradeData['trade_id'];
        $eventData = $tradeService->tradeRepository->find($tradeId);
        $tradeService->finishEvents($eventData);
        dd($eventData);
        exit;
        // event(new TradeFinishEvent($eventData));
    }

    // TEST-拼团订单测试触发
    public function testGroupEvent()
    {
        $eventData = new PromotionGroupsTeam();

        $eventData->setTeamId('2614889000068945');
        $eventData->sourceType = 'groups';
        // $eventData['order_class'] = 'groups';

        event(new TradeFinishEvent($eventData));
    }

    // TEST-发送退款单
    public function testRefundEvent()
    {
        $eventData = new ServiceOrders();
        // $eventData->setOrderId('2565683000268913');
        $eventData->setOrderId('2551713000111791');
        $eventData->setCompanyId(1);
        $eventData->setOrderSource('normal');
        event(new TradeRefundEvent($eventData));
    }

    // TEST-发送售后申请
    public function testAftersalesEvent()
    {
        $eventData = new Aftersales();
        // $eventData->setOrderId('2565683000268913');
        $eventData->setOrderId('2566577000098916');
        $eventData->setCompanyId(1);
        $eventData->setItemId(270);
        event(new TradeAftersalesEvent($eventData));
    }

    // TEST-更新退货物流信息
    public function testAfterLogiEvent()
    {
        $eventData = new Aftersales();
        $eventData->setOrderId('2566577000098916');
        $eventData->setCompanyId(1);
        $eventData->setAftersalesBn('1812201010300951');
        // dd($eventData);exit;
        event(new TradeAftersalesEvent($eventData));
    }

    // TEST-用户取消售后
    public function testAftersalesCancelEvent()
    {
        $eventData = new Aftersales();
        $eventData->setOrderId('2566577000098916');
        $eventData->setCompanyId(1);
        $eventData->setAftersalesBn('1812201010300951');
        // dd($eventData);exit;
        event(new TradeAftersalesEvent($eventData));
    }

    /**
     * @SWG\Post(
     *     path="/systemlink/ome/getOrderInfo",
     *     summary="OMS获取订单详情",
     *     tags={"omeapi"},
     *     description="OMS获取订单详情",
     *     operationId="getOrderInfo",
     *     @SWG\Parameter( name="method", in="query", description="接口方法名", default="store.trade.fullinfo.get", required=true, type="string"),
     *     @SWG\Parameter( name="sign", in="query", description="参数签名", required=true, type="string"),
     *     @SWG\Parameter( name="tid", in="query", description="订单号", default="3285622000030261", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="rsp", type="string", example="succ", description="操作结果"),
     *          @SWG\Property( property="code", type="string", example="0", description="code"),
     *          @SWG\Property( property="err_msg", type="string", example="操作成功", description="提示信息"),
     *          @SWG\Property( property="data", description="返回数据",
     *              @SWG\Property( property="trade", description="订单信息",
     *                  ref="#/definitions/OrderInfo"
     *              ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SystemLinkErrorResponse") ) )
     * )
     */
    public function getOrderInfo(Request $request)
    {
        $params = $request->all();

        $rules = [
            'tid' => ['required', '订单号缺少！'],
        ];

        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            $this->api_response('fail', $errorMessage);
        }

        // $orderAssociationService = new OrderAssociationService();
        $normalOrderService = new NormalOrderService();

        //2565607000050583
        $filter = ['order_id' => $params['tid']];
        // $tradeInfo = $orderAssociationService->get($filter);
        $tradeInfo = $normalOrderService->getList($filter);

        if (!$tradeInfo) {
            $this->api_response('fail', '未找到订单');
        }

        $order_class = ['normal', 'groups', 'seckill'];

        if (!in_array($tradeInfo[0]['order_class'], $order_class)) {
            $this->api_response('fail', $tradeInfo[0]['order_class'].'类型订单禁止同步');
        }

        $orderService = new OrderService();
        $orderStruct = $orderService->getOmeOrderInfo($tradeInfo[0]['company_id'], $params['tid'], $tradeInfo[0]['order_class']);

        if (!$orderStruct) {
            $this->api_response('fail', '获取订单信息失败');
        }

        $result['trade'] = $orderStruct;

        $this->api_response('true', '操作成功', $result);
    }

    /**
     * @SWG\Post(
     *     path="/systemlink/ome/ReceiveOrderInvoice",
     *     summary="OMS同步发票下载地址",
     *     tags={"omeapi"},
     *     description="发票开票成功后记录发票下载地址",
     *     operationId="ReceiveOrderInvoice",
     *     @SWG\Parameter( name="method", in="query", description="接口方法名", default="ome.user.up", required=true, type="string"),
     *     @SWG\Parameter( name="sign", in="query", description="参数签名", required=true, type="string"),
     *     @SWG\Parameter( name="order_id", in="query", description="订单ID", required=true, type="string"),
     *     @SWG\Parameter( name="invoice_url", in="query", description="发票下载地址", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="rsp", type="string", example="succ", description="操作结果"),
     *          @SWG\Property( property="code", type="string", example="0", description="code"),
     *          @SWG\Property( property="err_msg", type="string", example="操作成功", description="提示信息"),
     *          @SWG\Property( property="data", type="string", example="null", description="返回数据"),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SystemLinkErrorResponse") ) )
     * )
     */
    public function ReceiveOrderInvoice(Request $request)
    {
        $orderId = $request->get('order_id');
        $invoice = $request->get('invoice_url');
        $orderInvoiceService = new UserOrderInvoiceService();
        $result = $orderInvoiceService->saveData($orderId, $invoice);
    }

    /**
     * @SWG\Post(
     *     path="/systemlink/ome/updateOrderReviewStatus",
     *     summary="OMS同步订单状态",
     *     tags={"omeapi"},
     *     description="OMS同步订单状态",
     *     operationId="updateOrderReviewStatus",
     *     @SWG\Parameter( name="method", in="query", description="接口方法名", default="ome.user.up", required=true, type="string"),
     *     @SWG\Parameter( name="sign", in="query", description="参数签名", required=true, type="string"),
     *     @SWG\Parameter( name="order_id", in="query", description="订单ID", required=true, type="string"),
     *     @SWG\Parameter( name="order_status", in="query", description="订单状态", required=true, type="string"),
     *     @SWG\Parameter( name="order_review_time", in="query", description="订单发货时间", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="rsp", type="string", example="succ", description="操作结果"),
     *          @SWG\Property( property="code", type="string", example="0", description="code"),
     *          @SWG\Property( property="err_msg", type="string", example="操作成功", description="提示信息"),
     *          @SWG\Property( property="data", type="string", example="null", description="返回数据"),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SystemLinkErrorResponse") ) )
     * )
     */
    public function updateOrderReviewStatus(Request $request)
    {
        $orderId = $request->get('order_id');
        $status = $request->get('order_status');
        $reviewTime = $request->get('order_review_time');
        $orderService = new OrderService();
        $result = $orderService->updateOrderStatusReview($orderId, $status, $reviewTime);
        if ($result) {
            $this->api_response('true', '操作成功', ['status' => true]);
        } else {
            $this->api_response('fail', '订单信息有误');
        }
    }

    // oms全额退款需要更新状态，以用来关闭订单，回滚库存
    /**
     * @SWG\Post(
     *     path="/systemlink/ome/updateOrderStatus",
     *     summary="OMS同步商品库存",
     *     tags={"omeapi"},
     *     description="OMS同步商品库存",
     *     operationId="updateOrderStatus",
     *     @SWG\Parameter( name="method", in="query", description="接口方法名", default="ome.user.up", required=true, type="string"),
     *     @SWG\Parameter( name="sign", in="query", description="参数签名", required=true, type="string"),
     *     @SWG\Parameter( name="tid", in="query", description="订单ID", required=true, type="string"),
     *     @SWG\Parameter( name="status", in="query", description="订单状态", default="TRADE_CLOSED", required=true, type="string"),
     *     @SWG\Parameter( name="type", in="query", description="订单类型", default="status", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="rsp", type="string", example="succ", description="操作结果"),
     *          @SWG\Property( property="code", type="string", example="0", description="code"),
     *          @SWG\Property( property="err_msg", type="string", example="操作成功", description="提示信息"),
     *          @SWG\Property( property="data", type="string", example="null", description="返回数据"),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SystemLinkErrorResponse") ) )
     * )
     */
    public function updateOrderStatus(Request $request)
    {
        // tid=CN3007292745708000176748
        // status=TRADE_CLOSED
        // type=status
        // modify=2019-07-08 19:20:49
        // is_update_trade_status=true
        // reason=订单全额退款后取消！
        $orderId = $request->get('tid');
        $status = $request->get('status');
        $type = $request->get('type');
        $modify = $request->get('modify');
        $is_update_trade_status = $request->get('is_update_trade_status');
        if (!$orderId) {
            $this->api_response('fail', '订单信息有误');
        }
        $flag = false;
        if ($status == 'TRADE_CLOSED' && $type == 'status') {
            $normalOrderService = new NormalOrderService();
            $filter = [
                'order_id' => $orderId,
            ];
            $result = $normalOrderService->normalOrdersRepository->getInfo($filter);
            if ($result['cancel_status'] == 'SUCCESS') {
                $flag = true;
            }
        }


        if ($flag) {
            $this->api_response('true', '操作成功', ['status' => true]);
        } else {
            $this->api_response('fail', '订单信息有误');
        }
    }

    /**
     * @SWG\Definition(
     *     definition="OrderInfo",
     *     type="object",
     *     @SWG\Property( property="promotion_details", type="array",
     *          @SWG\Items( type="object",
     *              @SWG\Property( property="pmt_amount", type="string", example="0.02", description="折扣金额"),
     *              @SWG\Property( property="pmt_id", type="string", example="3285622000030261", description="促销ID"),
     *              @SWG\Property( property="pmt_describe", type="string", example="会员折扣优惠9", description="促销方案"),
     *          ),
     *     ),
     *                  @SWG\Property( property="buyer_name", type="string", example="石头剪刀布", description="买家"),
     *                  @SWG\Property( property="is_cod", type="string", example="false", description="是否货到付款"),
     *                  @SWG\Property( property="receiver_email", type="string", example="", description="收货人email"),
     *                  @SWG\Property( property="point_fee", type="string", example="0", description="积分抵扣金额，以分为单位"),
     *                  @SWG\Property( property="total_goods_fee", type="string", example="0.03", description="商品总金额"),
     *                  @SWG\Property( property="currency", type="string", example="CNY", description="货币类型"),
     *                  @SWG\Property( property="total_weight", type="string", example="0.000", description="商品总重量"),
     *                  @SWG\Property( property="total_currency_fee", type="string", example="0.01", description="总金额"),
     *                  @SWG\Property( property="shipping_type", type="string", example="null", description="物流类型"),
     *                  @SWG\Property( property="receiver_address", type="string", example="收货人详细地址", description="收货人详细地址"),
     *                  @SWG\Property( property="payment_tid", type="string", example="wxpay", description="付款方式"),
     *                  @SWG\Property( property="orders", type="object",
     *                          @SWG\Property( property="order", type="array",
     *                              @SWG\Items( type="object",
     *                                  @SWG\Property( property="consign_time", type="string", example="", description="发货时间"),
     *                                  @SWG\Property( property="weight", type="string", example="2", description="商品重量"),
     *                                  @SWG\Property( property="title", type="string", example="测试商品...", description="商品名称"),
     *                                  @SWG\Property( property="discount_fee", type="string", example="0.02", description="订单优惠金额，以分为单位"),
     *                                  @SWG\Property( property="type", type="string", example="goods", description="商品类型"),
     *                                  @SWG\Property( property="price", type="string", example="0.03", description="购买价格,单位为‘分’"),
     *                                  @SWG\Property( property="oid", type="string", example="3285622000030261", description="子订单ID"),
     *                                  @SWG\Property( property="order_status", type="string", example="SHIP_NO", description="订单状态"),
     *                                  @SWG\Property( property="order_items", type="object",
     *                                          @SWG\Property( property="orderitem", type="array",
     *                                              @SWG\Items( type="object",
     *                                                  @SWG\Property( property="sku_id", type="string", example="5290", description="sku_id"),
     *                                                  @SWG\Property( property="name", type="string", example="测试商品", description="商品名称"),
     *                                                  @SWG\Property( property="weight", type="string", example="2", description="商品重量"),
     *                                                  @SWG\Property( property="iid", type="string", example="5290", description="iid"),
     *                                                  @SWG\Property( property="discount_fee", type="string", example="0.00", description="优惠金额，以分为单位"),
     *                                                  @SWG\Property( property="bn", type="string", example="1234", description="商品货号"),
     *                                                  @SWG\Property( property="sku_properties", type="array",
     *                                                      @SWG\Items( type="string", example="undefined", description="SKU属性"),
     *                                                  ),
     *                                                  @SWG\Property( property="item_status", type="string", example="normal", description="商品状态"),
     *                                                  @SWG\Property( property="weblink", type="string", example="", description="weblink"),
     *                                                  @SWG\Property( property="item_type", type="string", example="product", description="商品类型"),
     *                                                  @SWG\Property( property="num", type="string", example="1", description="购买商品数量"),
     *                                                  @SWG\Property( property="sendnum", type="string", example="0", description="发货数量"),
     *                                                  @SWG\Property( property="sale_price", type="string", example="0.03", description="销售价"),
     *                                                  @SWG\Property( property="score", type="string", example="0", description="积分"),
     *                                                  @SWG\Property( property="price", type="string", example="0.03", description="购买价格,单位为‘分’"),
     *                                                  @SWG\Property( property="total_item_fee", type="string", example="0.03", description="商品金额合计"),
     *                                                  @SWG\Property( property="divide_order_fee", type="string", example="0.01", description="实付金额"),
     *                                                  @SWG\Property( property="part_mjz_discount", type="string", example="0.02", description="优惠分摊"),
     *                                               ),
     *                                          ),
     *                                  ),
     *                                  @SWG\Property( property="iid", type="string", example="5290", description="iid"),
     *                                  @SWG\Property( property="type_alias", type="string", example="商品区块", description="商品类型别名"),
     *                                  @SWG\Property( property="total_order_fee", type="string", example="0.01", description="订单总金额"),
     *                                  @SWG\Property( property="items_num", type="string", example="1", description="商品总数"),
     *                                  @SWG\Property( property="orders_bn", type="string", example="3285622000030261", description="订单编号"),
     *                                  @SWG\Property( property="is_gift", type="string", example="0", description="是否为赠品"),
     *                                  @SWG\Property( property="is_mileage", type="string", example="0", description="积分支付"),
     *                               ),
     *                          ),
     *                  ),
     *                  @SWG\Property( property="trade_memo", type="string", example="null", description="订单备注"),
     *                  @SWG\Property( property="lastmodify", type="string", example="2020-12-29 16:02:03", description="更新时间"),
     *                  @SWG\Property( property="receiver_district", type="string", example="东城", description="收货人所在地区"),
     *                  @SWG\Property( property="receiver_city", type="string", example="北京市", description="收货人所在城市"),
     *                  @SWG\Property( property="title", type="string", example="测试商品...", description="商品名称"),
     *                  @SWG\Property( property="orders_discount_fee", type="string", example="0.02", description="订单折扣金额"),
     *                  @SWG\Property( property="buyer_memo", type="string", example="", description="买家备注"),
     *                  @SWG\Property( property="receiver_state", type="string", example="北京市", description="收货人所在省份"),
     *                  @SWG\Property( property="tid", type="string", example="3285622000030261", description="订单号"),
     *                  @SWG\Property( property="protect_fee", type="string", example="0.000", description="保价费用"),
     *                  @SWG\Property( property="receiver_phone", type="string", example="", description="收件人电话"),
     *                  @SWG\Property( property="pay_status", type="string", example="PAY_FINISH", description="支付状态"),
     *                  @SWG\Property( property="buyer_id", type="string", example="20261", description="买家ID"),
     *                  @SWG\Property( property="status", type="string", example="TRADE_FINISHED", description="订单状态"),
     *                  @SWG\Property( property="total_trade_fee", type="string", example="0.01", description="订单总金额"),
     *                  @SWG\Property( property="buyer_address", type="string", example="null", description="收货人地址"),
     *                  @SWG\Property( property="pay_cost", type="string", example="0.000", description="交易手续费"),
     *                  @SWG\Property( property="buyer_uname", type="string", example="15121097923", description="买家用户名"),
     *                  @SWG\Property( property="buyer_email", type="string", example="null", description="买家email"),
     *                  @SWG\Property( property="receiver_time", type="string", example="任意时间,任意时间段", description="收货时间"),
     *                  @SWG\Property( property="buyer_zip", type="string", example="null", description="买家邮编"),
     *                  @SWG\Property( property="payment_lists", type="object",
     *                          @SWG\Property( property="payment_list", type="array",
     *                              @SWG\Items( type="object",
     *                                  @SWG\Property( property="tid", type="string", example="3285622000030261", description="订单号"),
     *                                  @SWG\Property( property="payment_id", type="string", example="3285622000060261", description="付款单ID"),
     *                                  @SWG\Property( property="seller_bank", type="string", example="微信支付", description="收款银行"),
     *                                  @SWG\Property( property="seller_account", type="string", example="oHxgH0TeytApG70umvdz0mNGO69A", description="卖家账号"),
     *                                  @SWG\Property( property="buyer_account", type="string", example="1313844301", description="买家账号"),
     *                                  @SWG\Property( property="currency", type="string", example="CNY", description="货币类型"),
     *                                  @SWG\Property( property="paycost", type="string", example="0.000", description="交易手续费"),
     *                                  @SWG\Property( property="pay_type", type="string", example="online", description="支付类型"),
     *                                  @SWG\Property( property="payment_name", type="string", example="微信支付", description="支付方式"),
     *                                  @SWG\Property( property="payment_code", type="string", example="wxpay", description="支付方式编码"),
     *                                  @SWG\Property( property="t_begin", type="string", example="2020-12-29 15:33:12", description="支付开始时间"),
     *                                  @SWG\Property( property="t_end", type="string", example="2020-12-29 15:33:19", description="支付完成时间"),
     *                                  @SWG\Property( property="status", type="string", example="SUCC", description="支付状态"),
     *                                  @SWG\Property( property="memo", type="string", example="", description="备注"),
     *                                  @SWG\Property( property="outer_no", type="string", example="4200000783202012296469814948", description="第三方交易单号"),
     *                                  @SWG\Property( property="pay_fee", type="string", example="0.01", description="支付金额"),
     *                                  @SWG\Property( property="currency_fee", type="string", example="0.01", description="支付金额"),
     *                               ),
     *                          ),
     *                  ),
     *                  @SWG\Property( property="receiver_mobile", type="string", example="13116521920", description="收货人手机号"),
     *                  @SWG\Property( property="buyer_mobile", type="string", example="15121097923", description="买家手机号"),
     *                  @SWG\Property( property="goods_discount_fee", type="string", example="0.000", description="商品折扣金额"),
     *                  @SWG\Property( property="orders_number", type="string", example="1", description="购买数量"),
     *                  @SWG\Property( property="shipping_tid", type="string", example="0", description="shipping_tid"),
     *                  @SWG\Property( property="created", type="string", example="2020-12-29 15:33:12", description="创建时间"),
     *                  @SWG\Property( property="ship_status", type="string", example="SHIP_FINISH", description="发货状态"),
     *                  @SWG\Property( property="payed_fee", type="string", example="0.01", description="付款金额"),
     *                  @SWG\Property( property="has_invoice", type="string", example="0", description="是否需要发票"),
     *                  @SWG\Property( property="invoice_title", type="string", example="0", description="发票抬头"),
     *                  @SWG\Property( property="invoice_fee", type="string", example="0.00", description="发票金额"),
     *                  @SWG\Property( property="modified", type="string", example="2020-12-29 16:02:03", description="更新时间"),
     *                  @SWG\Property( property="is_protect", type="string", example="false", description="是否保价"),
     *                  @SWG\Property( property="discount_fee", type="string", example="0.000", description="优惠金额"),
     *                  @SWG\Property( property="buyer_obtain_point_fee", type="string", example="0.000", description="积分抵扣金额"),
     *                  @SWG\Property( property="payment_type", type="string", example="微信支付", description="付款类型"),
     *                  @SWG\Property( property="buyer_phone", type="string", example="15121097923", description="买家电话"),
     *                  @SWG\Property( property="receiver_name", type="string", example="小小", description="收货人姓名"),
     *                  @SWG\Property( property="shipping_fee", type="string", example="0.00", description="运费"),
     *                  @SWG\Property( property="receiver_zip", type="string", example="100000", description="邮编"),
     *                  @SWG\Property( property="currency_rate", type="string", example="1", description="汇率"),
     *                  @SWG\Property( property="sales_org", type="string", example="", description="销售组织"),
     *                  @SWG\Property( property="customer_code", type="string", example="", description="客户编码"),
     *                  @SWG\Property( property="customer_name", type="string", example="", description="客户名称"),
     *                  @SWG\Property( property="brand_code", type="string", example="", description="品牌编码"),
     * )
     */
}
