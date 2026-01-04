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

use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;

use App\Http\Controllers\Controller as Controller;

use OrdersBundle\Entities\OrderAssociations;
use MembersBundle\Services\MemberService;
use OrdersBundle\Traits\GetOrderServiceTrait;
use PaymentBundle\Services\PaymentService;

class NormalOrder extends Controller
{
     use GetOrderServiceTrait;

     /**
      * @SWG\Post(
      *     path="/api/checkout",
      *     summary="购物车结算列表",
      *     tags={"订单"},
      *     description="购物车结算列表",
      *     operationId="checkout",
      *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
      *     @SWG\Parameter( name="user_id", in="formData", description="用户id", required=false, type="string"),
      *     @SWG\Parameter( name="distributor_id", in="formData", description="店铺id", required=false, type="string"),
      *     @SWG\Parameter( name="pay_type", in="formData", description="支付类型 wxpay 微信支付", required=false,  type="string"),
      *     @SWG\Parameter( name="not_use_coupon", in="formData", description="不使用优惠券", required=false, type="boolean"),
      *     @SWG\Parameter( name="coupon_discount", in="formData", description="优惠券优惠码", required=false, type="string"),
      *     @SWG\Parameter( name="point_use", in="formData", description="使用的积分数", required=false, type="string"),
      *     @SWG\Response(
      *         response=200,
      *         description="成功返回结构",
      *         @SWG\Schema(
      *             @SWG\Property(
      *                 property="data",
      *                 type="object",
      *                 @SWG\Property(property="order_id", type="string", description="订单号"),
      *                 @SWG\Property(property="company_id", type="integer", description="公司ID"),
      *                 @SWG\Property(property="user_id", type="integer", description="用户ID"),
      *                 @SWG\Property(property="distributor_id", type="integer", description="店铺ID"),
      *                 @SWG\Property(property="is_distribution", type="boolean", description="是否店铺订单"),
      *                 @SWG\Property(property="remark", type="string", description="订单备注"),
      *                 @SWG\Property(property="mobile", type="string", description="订单号"),
      *                 @SWG\Property(property="order_source", type="string", description="订单来源"),
      *                 @SWG\Property(property="order_class", type="string", description="订单种类。可选值有  normal:普通订单;groups:拼团订单;community 社区活动订单;bargain:助力订单;seckill:秒杀订单;shopguide:导购订单"),
      *                 @SWG\Property(property="order_type", type="string", description="订单类型。可选值有  normal:普通实体订单"),
      *                 @SWG\Property(property="order_status", type="string", description="订单状态。可选值有 DONE—订单完成;NOTPAY—未支付;PART_PAYMENT-部分付款;WAIT_GROUPS_SUCCESS-等待拼团成功;PAYED-已支付;CANCEL—已取消;WAIT_BUYER_CONFIRM-待用户收货"),
      *                 @SWG\Property(property="auto_cancel_time", type="number", description="订单自动取消时间"),
      *                 @SWG\Property(property="discount_fee", type="number", description="订单优惠金额，以分为单位"),
      *                 @SWG\Property(
      *                     property="discount_info",
      *                     type="array",
      *                     @SWG\Items(
      *                         type="object",
      *                         @SWG\Property(property="type", type="string", description="优惠类型"),
      *                         @SWG\Property(property="rule", type="string", description="促销规则"),
      *                         @SWG\Property(property="discount_fee", type="integer", description="优惠金额"),
      *                     )
      *                 ),
      *                 @SWG\Property(property="pay_type", type="string", description="支付方式"),
      *                 @SWG\Property(property="salesman_id", type="integer", description="导购员ID"),
      *                 @SWG\Property(property="point_use", type="integer", description="积分抵扣使用的积分数"),
      *                 @SWG\Property(property="point_fee", type="integer", description="积分抵扣金额，以分为单位"),
      *                 @SWG\Property(property="get_point_type", type="integer", description="获取积分类型，0  老订单按订单完成时送,1 新订单按下单时计算送"),
      *                 @SWG\Property(property="is_profitsharing", type="integer", description="是否分账订单 1不分账  2分账"),
      *                 @SWG\Property(property="profitsharing_rate", type="integer", description="分账费率"),
      *                 @SWG\Property(property="pack", type="integer", description="包装"),
      *                 @SWG\Property(property="fee_rate", type="number", description="货币汇率"),
      *                 @SWG\Property(property="fee_type", type="string", description="货币类型"),
      *                 @SWG\Property(property="fee_symbol", type="string", description="货币符号"),
      *                 @SWG\Property(property="source_id", type="integer", description="订单来源id"),
      *                 @SWG\Property(property="monitor_id", type="integer", description="订单监控页面id"),
      *                 @SWG\Property(property="point", type="integer", description="消费积分"),
      *                 @SWG\Property(property="item_fee", type="string", description="商品金额，以分为单位"),
      *                 @SWG\Property(property="cost_fee", type="integer", description="商品成本价，以分为单位"),
      *                 @SWG\Property(property="total_fee", type="string", description="订单金额，以分为单位"),
      *                 @SWG\Property(property="total_rebate", type="integer", description="订单总分销金额，以分为单位"),
      *                 @SWG\Property(property="title", type="string", description="订单标题"),
      *                 @SWG\Property(property="totalItemNum", type="integer", description="商品商品数量"),
      *                 @SWG\Property(property="goods_discount", type="integer", description="订单优惠金额"),
      *                 @SWG\Property(property="member_discount", type="integer", description="会员优惠"),
      *                 @SWG\Property(property="freight_fee", type="integer", description="运费价格，以分为单位"),
      *                 @SWG\Property(property="is_shopscreen", type="boolean", description="是否门店大屏订单"),
      *                 @SWG\Property(property="coupon_discount", type="integer", description="优惠券优惠金额"),
      *                 @SWG\Property(
      *                     property="coupon_info",
      *                     type="array",
      *                     @SWG\Items(
      *                         type="object",
      *                         @SWG\Property(property="id", type="integer", description="优惠券ID"),
      *                         @SWG\Property(property="coupon_code", type="string", description="优惠券码"),
      *                         @SWG\Property(property="info", type="string", description="优惠信息"),
      *                         @SWG\Property(property="type", type="string", description="优惠类型"),
      *                         @SWG\Property(property="rule", type="string", description="促销规则"),
      *                         @SWG\Property(property="discount_fee", type="integer", description="优惠金额"),
      *                     )
      *                 ),
      *                 @SWG\Property(property="get_points", type="integer", description="订单获取积分"),
      *                 @SWG\Property(property="bonus_points", type="integer", description="购物赠送积分"),
      *                 @SWG\Property(property="user_point", type="integer", description="用户积分"),
      *                 @SWG\Property(property="max_point", type="integer", description="本单会员最大可抵扣积分"),
      *                 @SWG\Property(property="limit_point", type="integer", description="本单最大可抵扣积分"),
      *                 @SWG\Property(
      *                     property="items",
      *                     type="array",
      *                     @SWG\Items(
      *                         type="object",
      *                         @SWG\Property(property="order_id", type="string", description="订单号"),
      *                         @SWG\Property(property="item_id", type="integer", description="商品id"),
      *                         @SWG\Property(property="item_bn", type="string", description="商品编码"),
      *                         @SWG\Property(property="company_id", type="integer", description="公司id"),
      *                         @SWG\Property(property="user_id", type="integer", description="用户id"),
      *                         @SWG\Property(property="item_name", type="string", description="商品名称"),
      *                         @SWG\Property(property="templates_id", type="integer", description="运费模板id"),
      *                         @SWG\Property(property="num", type="number", description="购买商品数量"),
      *                         @SWG\Property(property="price", type="number", description="单价，以分为单位"),
      *                         @SWG\Property(property="item_fee", type="number", description="商品总金额，以分为单位"),
      *                         @SWG\Property(property="cost_fee", type="number", description="商品成本价，以分为单位"),
      *                         @SWG\Property(property="item_unit", type="string", description="商品计量单位"),
      *                         @SWG\Property(property="total_fee", type="number", description="订单金额，以分为单位"),
      *                         @SWG\Property(property="discount_fee", type="number",  description="订单优惠金额，以分为单位"),
      *                         @SWG\Property(
      *                             property="discount_info",
      *                             type="array",
      *                             @SWG\Items(
      *                                 type="object",
      *                                 @SWG\Property(property="type", type="string", description="优惠类型"),
      *                                 @SWG\Property(property="rule", type="string", description="促销规则"),
      *                                 @SWG\Property(property="discount_fee", type="integer", description="优惠金额"),
      *                             )
      *                         ),
      *                         @SWG\Property(property="rebate", type="number", description="单个分销金额，以分为单位"),
      *                         @SWG\Property(property="total_rebate", type="number", description="总分销金额，以分为单位"),
      *                         @SWG\Property(property="distributor_id", type="integer", description="分销商id"),
      *                         @SWG\Property(property="is_total_store", type="boolean", description="是否是总部库存"),
      *                         @SWG\Property(property="fee_rate", type="number", description="货币汇率"),
      *                         @SWG\Property(property="fee_type", type="string", description="货币类型"),
      *                         @SWG\Property(property="fee_symbol", type="string", description="货币符号"),
      *                         @SWG\Property(property="item_point", type="integer", description="商品积分"),
      *                         @SWG\Property(property="point", type="integer", description="商品总积分"),
      *                         @SWG\Property(property="volume", type="number", description="商品体积"),
      *                         @SWG\Property(property="weight", type="number", description="商品重量"),
      *                         @SWG\Property(property="order_item_type", type="string", description="订单商品类型"),
      *                         @SWG\Property(property="type", type="integer", description="订单类型，0普通订单,1跨境订单"),
      *                         @SWG\Property(property="crossborder_tax_rate", type="number", description="商品跨境税费"),
      *                         @SWG\Property(property="member_discount", type="number",  description="会员折扣金额，以分为单位"),
      *                         @SWG\Property(property="coupon_discount", type="number",  description="优惠券抵扣金额，以分为单位"),
      *                     )
      *                 ),
      *             )
      *         )
      *     ),
      *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items( ref="#/definitions/OrdersErrorRespones") ) )
      * )
      */
     public function checkout(Request $request)
     {
         $input = $request->all();
         $authInfo = app('auth')->user()->get();
         $input['distributor_id'] = $request->input('distributor_id', 0);
 
         $params = $this->_getOrderParams($input, $authInfo);
         $orderService = $this->getOrderService($params['order_type']);
         $result = $orderService->getOrderTempInfo($params);

         $result['item_fee_new'] += $result['member_discount']; //加上会员折扣，暂时先这么改
 
         return $this->response->array($result);
     }

     /**
      * @SWG\Post(
      *     path="/api/order/create",
      *     summary="代客下单",
      *     tags={"订单"},
      *     description="代客下单",
      *     operationId="createUserOrder",
      *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
      *     @SWG\Parameter( name="user_id", in="formData", description="用户id", required=false, type="string"),
      *     @SWG\Parameter( name="distributor_id", in="formData", description="店铺id", required=false, type="string"),
      *     @SWG\Parameter( name="pay_type", in="formData", description="支付类型 wxpay 微信支付", required=false,  type="string"),
      *     @SWG\Parameter( name="not_use_coupon", in="formData", description="不使用优惠券", required=false, type="boolean"),
      *     @SWG\Parameter( name="coupon_discount", in="formData", description="优惠券优惠码", required=false, type="string"),
      *     @SWG\Parameter( name="point_use", in="formData", description="使用的积分数", required=false, type="string"),
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
      *             )
      *         )
      *     ),
      *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items( ref="#/definitions/OrdersErrorRespones") ) )
      * )
      */
     public function createUserOrder(Request $request)
     {
         $input = $request->all();
         $authInfo = app('auth')->user()->get();
         $input['distributor_id'] = $request->input('distributor_id', 0);

         $params = $this->_getOrderParams($input, $authInfo);
         $orderService = $this->getOrderService($params['order_type']);
         $result = $orderService->create($params);
 
         return $this->response->array($result);
     }

     private function _getOrderParams($input, $authInfo)
     { 
          if (isset($input['markdown'])) {
               $rules = [
                   'down_type' => ['in:total,items', '确认整单还是按件改价'],
                   'total_fee'   => ['required_if:down_type,total', '整单改价金额必填'],
                   'items' => ['required_if:down_type,items', '按件改价商品ID必填'],
                   'items.*.item_id' => ['required', '按件改价商品ID必填'],
                   'items.*.total_fee' => ['required_without:items.*.discount', '按件改价商品总价和折扣必须设置一个'],
                   'items.*.discount' => ['required_without:items.*.total_fee', '按件改价商品总价和折扣必须设置一个'],
               ];
               $errorMessage = validator_params($input['markdown'], $rules);
               if($errorMessage) {
                   throw new ResourceException($errorMessage);
               }
               $params['markdown'] = $input['markdown'];
          }

          if (isset($input['user_id']) && $input['user_id'] > 0) {
               $memberService = new MemberService();
               $userinfo = $memberService->getMemberInfo(['user_id' => $input['user_id']]);
               if (!$userinfo) {
                   throw new ResourceException('会员信息有误');
               }
          }
  
          $params['promotion'] = 'normal';
          $params['order_source'] = 'shop_offline';
          $params['is_online_order'] = false;
          $params['receipt_type'] = 'ziti';
          $params['order_type'] = 'normal_shopadmin';
          $params['pay_type'] = $input['pay_type'] ?? 'pos';
          $params['mobile'] = $userinfo['mobile'] ?? '';
          $params['authorizer_appid'] = $userinfo['woa_appid'] ?? '';
          $params['wxa_appid'] = $userinfo['wxapp_appid'] ?? '';
          $params['user_id'] = $input['user_id'] ?? 0;
          $params['not_use_coupon'] = $input['not_use_coupon'] ?? 0;
          $params['coupon_discount'] = $input['coupon_discount'] ?? 0;
          $params['operator_id'] = $authInfo['operator_id'];
          $params['company_id'] = $authInfo['company_id'];
          $params['distributor_id'] = $input['distributor_id'];
          $params['point_use'] = $input['point_use'] ?? 0;
          $params['remark'] = $input['remark'] ?? '';
          $params['source_from'] = 'dianwu';
          return $params;
     }

     /**
      * @SWG\post(
      *     path="/api/order/payment",
      *     summary="获取支付需要的参数， 积分以及预存款直接扣除",
      *     tags={"订单"},
      *     description="获取支付需要的参数， 积分以及预存款直接扣除",
      *     operationId="payment",
      *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token(h5app必填)", required=true,  type="string"),
      *     @SWG\Parameter( name="pay_type", in="query", description="支付方式", required=true, type="string"),
      *     @SWG\Parameter( name="order_id", in="query", description="订单id", required=true, type="string"),
      *     @SWG\Parameter( name="order_type", in="query", description="订单类型", required=true, type="string"),
      *     @SWG\Response(
      *         response="200",
      *         description="响应信息返回",
      *         @SWG\Schema(
      *            @SWG\Property(property="data", type="object", description="",
      *               @SWG\Property(property="appId", type="string", example="wx912913df9fef6ddd",  description="微信appid"),
      *               @SWG\Property(property="timeStamp", type="string", example="1611649457", description="时间戳"),
      *               @SWG\Property(property="nonceStr", type="string", example="600fd1b138b86", description="随机字符串"),
      *               @SWG\Property(property="package", type="string",  example="prepay_id=wx26162417199116ecacdae0642f7bc10000", description="订单详情扩展字符串"),
      *               @SWG\Property(property="signType", type="string", example="MD5", description="签名方式"),
      *               @SWG\Property(property="paySign", type="string", example="066A398046F333991853D37971191157",  description="签名"),
      *              @SWG\Property(property="trade_info", type="object", description="",
      *                   @SWG\Property(property="order_id", type="string", example="3313653000370376",  description="订单号"),
      *                   @SWG\Property(property="trade_id", type="string", example="3313656000040376",  description="交易单号"),
      *              ),
      *               @SWG\Property(property="team_id", type="string", example="", description=""),
      *               @SWG\Property(property="order_id", type="string", example="3313653000370376", description="订单号"),
      *              @SWG\Property(property="order_info", type="object", description="",
      *                   @SWG\Property(property="order_id", type="string", example="3313653000370376",  description="订单号"),
      *                   @SWG\Property(property="title", type="string", example="测试0119-2...", description="订单标题"),
      *                   @SWG\Property(property="company_id", type="string", example="1", description="公司id"),
      *                   @SWG\Property(property="user_id", type="string", example="20376", description="用户id"),
      *                   @SWG\Property(property="act_id", type="string", example="",  description="营销活动ID，团购ID，社区拼团ID，秒杀活动ID等"),
      *                   @SWG\Property(property="mobile", type="string", example="13095920688", description="手机号"),
      *                   @SWG\Property(property="freight_fee", type="integer", example="1",  description="运费价格，以分为单位"),
      *                   @SWG\Property(property="freight_type", type="string", example="cash",  description="运费类型-用于积分商城 cash:现金 point:积分"),
      *                   @SWG\Property(property="item_fee", type="string", example="1",  description="商品金额，以分为单位"),
      *                   @SWG\Property(property="item_point", type="integer", example="0", description="商品消费总积分"),
      *                   @SWG\Property(property="cost_fee", type="integer", example="10000",  description="商品成本价，以分为单位"),
      *                   @SWG\Property(property="total_fee", type="string", example="2",  description="订单金额，以分为单位"),
      *                   @SWG\Property(property="step_paid_fee", type="integer", example="0",  description="分阶段付款已支付金额，以分为单位"),
      *                   @SWG\Property(property="total_rebate", type="integer", example="0",  description="订单总分销金额，以分为单位"),
      *                   @SWG\Property(property="distributor_id", type="string", example="0", description="分销商id"),
      *                   @SWG\Property(property="receipt_type", type="string", example="logistics",  description="收货方式。可选值有 logistics:物流;ziti:店铺自提"),
      *                   @SWG\Property(property="ziti_code", type="string", example="0", description="店铺自提码"),
      *                   @SWG\Property(property="shop_id", type="string", example="0", description="门店id"),
      *                   @SWG\Property(property="ziti_status", type="string", example="NOTZITI",  description="店铺自提状态。可选值有 PENDING:等待自提;DONE:自提完成;NOTZITI:自提完成; APPROVE:审核通过,药品自提需要审核"),
      *                   @SWG\Property(property="order_status", type="string", example="NOTPAY",  description="订单状态。可选值有 DONE—订单完成;NOTPAY—未支付;PART_PAYMENT-部分付款;WAIT_GROUPS_SUCCESS-等待拼团成功;PAYED- 已支付;CANCEL—已取消;WAIT_BUYER_CONFIRM-待用户收货"),
      *                   @SWG\Property(property="order_source", type="string", example="member",  description="订单来源。可选值有 member-用户自主下单;shop-商家代客下单"),
      *                   @SWG\Property(property="order_type", type="string", example="normal",  description="订单类型。可选值有 normal:普通实体订单"),
      *                   @SWG\Property(property="order_class", type="string", example="normal",  description="订单种类。可选值有 normal:普通订单;groups:拼团订单;;community  社区活动订单;bargain:助力订单;seckill:秒杀订单;shopguide:导购订单;pointsmall:积分商城"),
      *                   @SWG\Property(property="auto_cancel_time", type="string", example="1611649848",  description="订单自动取消时间"),
      *                   @SWG\Property(property="auto_cancel_seconds", type="integer", example="392", description=""),
      *                   @SWG\Property(property="auto_finish_time", type="string", example="",  description="订单自动完成时间"),
      *                   @SWG\Property(property="is_distribution", type="string", example="", description="是否分销订单"),
      *                   @SWG\Property(property="source_id", type="string", example="0", description="订单来源id"),
      *                   @SWG\Property(property="monitor_id", type="string", example="0", description="订单监控页面id"),
      *                   @SWG\Property(property="salesman_id", type="string", example="0", description="导购员ID"),
      *                   @SWG\Property(property="delivery_corp", type="string", example="", description="快递公司"),
      *                   @SWG\Property(property="delivery_corp_source", type="string", example="",  description="快递代码来源"),
      *                   @SWG\Property(property="delivery_code", type="string", example="", description="快递单号"),
      *                   @SWG\Property(property="delivery_img", type="string", example="", description="快递发货凭证"),
      *                   @SWG\Property(property="delivery_status", type="string", example="PENDING",  description="发货状态。可选值有 DONE—已发货;PENDING—待发货;PARTAIL-部分发货"),
      *                   @SWG\Property(property="cancel_status", type="string", example="NO_APPLY_CANCEL",  description="取消订单状态。可选值有 NO_APPLY_CANCEL 未申请;WAIT_PROCESS 等待审核;REFUND_PROCESS 退款处理;SUCCESS  取消成功;FAILS 取消失败"),
      *                   @SWG\Property(property="delivery_time", type="string", example="", description="发货时间"),
      *                   @SWG\Property(property="end_time", type="string", example="", description="订单完成时间"),
      *                   @SWG\Property(property="end_date", type="string", example="", description=""),
      *                   @SWG\Property(property="receiver_name", type="string", example="张三", description="收货人姓名"),
      *                   @SWG\Property(property="receiver_mobile", type="string", example="13095920688",  description="收货人手机号"),
      *                   @SWG\Property(property="receiver_zip", type="string", example="101001",  description="收货人邮编"),
      *                   @SWG\Property(property="receiver_state", type="string", example="北京市",  description="收货人所在省份"),
      *                   @SWG\Property(property="receiver_city", type="string", example="北京市",  description="收货人所在城市"),
      *                   @SWG\Property(property="receiver_district", type="string", example="东城",  description="收货人所在地区"),
      *                   @SWG\Property(property="receiver_address", type="string", example="101",  description="收货人详细地址"),
      *                   @SWG\Property(property="member_discount", type="integer", example="0",  description="会员折扣金额，以分为单位"),
      *                   @SWG\Property(property="coupon_discount", type="integer", example="0",  description="优惠券抵扣金额，以分为单位"),
      *                   @SWG\Property(property="discount_fee", type="integer", example="0",  description="订单优惠金额，以分为单位"),
      *                   @SWG\Property(property="create_time", type="integer", example="1611649249",  description="订单创建时间"),
      *                   @SWG\Property(property="update_time", type="integer", example="1611649249",  description="订单更新时间"),
      *                   @SWG\Property(property="fee_type", type="string", example="CNY", description="货币类型"),
      *                   @SWG\Property(property="fee_rate", type="integer", example="1", description="货币汇率"),
      *                   @SWG\Property(property="fee_symbol", type="string", example="￥", description="货币符号"),
      *                   @SWG\Property(property="cny_fee", type="integer", example="2", description=""),
      *                   @SWG\Property(property="point", type="integer", example="0", description="消费积分"),
      *                   @SWG\Property(property="pay_type", type="string", example="", description="支付方式"),
      *                   @SWG\Property(property="remark", type="string", example="", description="订单备注"),
      *                  @SWG\Property(property="third_params", type="object", description="",
      *                           @SWG\Property(property="is_liveroom", type="string", example="1", description=""),
      *                  ),
      *                   @SWG\Property(property="invoice", type="string", example="", description="发票信息( DC2Type:json_array)"),
      *                   @SWG\Property(property="send_point", type="integer", example="0", description="是否分发积分0否  1是"),
      *                   @SWG\Property(property="is_rate", type="string", example="", description="是否评价"),
      *                   @SWG\Property(property="is_invoiced", type="string", example="", description="是否已开发票"),
      *                   @SWG\Property(property="invoice_number", type="string", example="", description="发票号"),
      *                   @SWG\Property(property="audit_status", type="string", example="processing",  description="跨境订单审核状态 approved成功 processing审核中 rejected审核拒绝"),
      *                   @SWG\Property(property="audit_msg", type="string", example="正在审核订单",  description="审核意见"),
      *                   @SWG\Property(property="point_fee", type="integer", example="0",  description="积分抵扣金额，以分为单位"),
      *                   @SWG\Property(property="point_use", type="integer", example="0",  description="积分抵扣使用的积分数"),
      *                   @SWG\Property(property="pay_status", type="string", example="NOTPAY",  description="支付状态。可选值有 NOTPAY—未支付;PAYED-已支付;ADVANCE_PAY-预付款完成;TAIL_PAY-支付尾款中"),
      *                   @SWG\Property(property="get_points", type="integer", example="2", description="订单获取积分"),
      *                   @SWG\Property(property="bonus_points", type="integer", example="0", description="购物赠送积分"),
      *                   @SWG\Property(property="get_point_type", type="integer", example="1",  description="获取积分类型，0 老订单按订单完成时送,1 新订单按下单时计算送"),
      *                   @SWG\Property(property="pack", type="string", example="", description="包装"),
      *                   @SWG\Property(property="is_shopscreen", type="string", example="", description="是否门店订单"),
      *                   @SWG\Property(property="is_logistics", type="string", example="",  description="门店缺货商品总部快递发货"),
      *                   @SWG\Property(property="is_profitsharing", type="integer", example="1",  description="是否分账订单 1不分账 2分账"),
      *                   @SWG\Property(property="profitsharing_status", type="integer", example="1",  description="分账状态 1未分账 2已分账"),
      *                   @SWG\Property(property="order_auto_close_aftersales_time", type="string", example="",  description="自动关闭售后时间"),
      *                   @SWG\Property(property="profitsharing_rate", type="integer", example="0",  description="分账费率"),
      *                   @SWG\Property(property="bind_auth_code", type="string", example="",  description="订单订单验证码"),
      *                   @SWG\Property(property="extra_points", type="integer", example="0",  description="订单获取额外积分"),
      *                   @SWG\Property(property="type", type="integer", example="0",  description="订单类型，0普通订单,1跨境订单,....其他"),
      *                   @SWG\Property(property="taxable_fee", type="integer", example="0",  description="计税总价，以分为单位"),
      *                   @SWG\Property(property="identity_id", type="string", example="", description="身份证号码"),
      *                   @SWG\Property(property="identity_name", type="string", example="", description="身份证姓名"),
      *                   @SWG\Property(property="total_tax", type="integer", example="0", description="总税费"),
      *                   @SWG\Property(property="discount_info", type="string", description=""),
      *                   @SWG\Property(property="can_apply_aftersales", type="integer", example="0", description=""),
      *                   @SWG\Property(property="distributor_name", type="string", example="北河沿甲柒拾柒号",  description=""),
      *                   @SWG\Property(property="items", type="array", description="",
      *                     @SWG\Items(
      *                                           @SWG\Property(property="id", type="string", example="8905",  description=""),
      *                                           @SWG\Property(property="order_id", type="string",  example="3313653000370376", description="订单号"),
      *                                           @SWG\Property(property="company_id", type="string", example="1",  description="公司id"),
      *                                           @SWG\Property(property="user_id", type="string", example="20376",  description="用户id"),
      *                                           @SWG\Property(property="act_id", type="string", example="",  description="营销活动ID，团购ID，社区拼团ID，秒杀活动ID等"),
      *                                           @SWG\Property(property="item_id", type="string", example="5437",  description=""),
      *                                           @SWG\Property(property="item_bn", type="string", example="dsaksak1191",  description=""),
      *                                           @SWG\Property(property="item_name", type="string",  example="测试0119-2", description=""),
      *                                           @SWG\Property(property="pic", type="string", example=" https://bbctest.aixue7.com/image/1/2021/01/06/e6d2a893739b6640ebb2c86c15ce29786JByhCPBiTPxzjMr8s9STXD01oSb7zJk",  description=""),
      *                                           @SWG\Property(property="num", type="integer", example="1",  description=""),
      *                                           @SWG\Property(property="price", type="integer", example="1",  description=""),
      *                                           @SWG\Property(property="total_fee", type="integer", example="1",  description="订单金额，以分为单位"),
      *                                           @SWG\Property(property="templates_id", type="integer", example="105",  description=""),
      *                                           @SWG\Property(property="rebate", type="integer", example="0",  description=""),
      *                                           @SWG\Property(property="total_rebate", type="integer", example="0",  description="订单总分销金额，以分为单位"),
      *                                           @SWG\Property(property="item_fee", type="integer", example="1",  description="商品金额，以分为单位"),
      *                                           @SWG\Property(property="cost_fee", type="integer", example="10000",  description="商品成本价，以分为单位"),
      *                                           @SWG\Property(property="item_unit", type="string", example="",  description=""),
      *                                           @SWG\Property(property="member_discount", type="integer", example="0",  description="会员折扣金额，以分为单位"),
      *                                           @SWG\Property(property="coupon_discount", type="integer", example="0",  description="优惠券抵扣金额，以分为单位"),
      *                                           @SWG\Property(property="discount_fee", type="integer", example="0",  description="订单优惠金额，以分为单位"),
      *                                           @SWG\Property(property="discount_info", type="array", description="",
      *                                             @SWG\Items(
      *                                             ),
      *                                           ),
      *                                           @SWG\Property(property="shop_id", type="string", example="0",  description="门店id"),
      *                                           @SWG\Property(property="is_total_store", type="string", example="1",  description=""),
      *                                           @SWG\Property(property="distributor_id", type="string", example="0",  description="分销商id"),
      *                                           @SWG\Property(property="create_time", type="integer",  example="1611649249", description="订单创建时间"),
      *                                           @SWG\Property(property="update_time", type="integer",  example="1611649249", description="订单更新时间"),
      *                                           @SWG\Property(property="delivery_corp", type="string", example="",  description="快递公司"),
      *                                           @SWG\Property(property="delivery_code", type="string", example="",  description="快递单号"),
      *                                           @SWG\Property(property="delivery_img", type="string", example="",  description="快递发货凭证"),
      *                                           @SWG\Property(property="delivery_time", type="string", example="",  description="发货时间"),
      *                                           @SWG\Property(property="delivery_status", type="string",  example="PENDING", description="发货状态。可选值有 DONE—已发货;PENDING—待发货;PARTAIL-部分发货"),
      *                                           @SWG\Property(property="aftersales_status", type="string", example="",  description=""),
      *                                           @SWG\Property(property="refunded_fee", type="integer", example="0",  description=""),
      *                                           @SWG\Property(property="fee_type", type="string", example="CNY",  description="货币类型"),
      *                                           @SWG\Property(property="fee_rate", type="integer", example="1",  description="货币汇率"),
      *                                           @SWG\Property(property="fee_symbol", type="string", example="￥",  description="货币符号"),
      *                                           @SWG\Property(property="cny_fee", type="integer", example="1",  description=""),
      *                                           @SWG\Property(property="item_point", type="integer", example="0",  description="商品消费总积分"),
      *                                           @SWG\Property(property="point", type="integer", example="0",  description="消费积分"),
      *                                           @SWG\Property(property="item_spec_desc", type="string", example="",  description=""),
      *                                           @SWG\Property(property="order_item_type", type="string",  example="normal", description=""),
      *                                           @SWG\Property(property="volume", type="integer", example="0",  description=""),
      *                                           @SWG\Property(property="weight", type="integer", example="0",  description=""),
      *                                           @SWG\Property(property="is_rate", type="string", example="",  description="是否评价"),
      *                                           @SWG\Property(property="auto_close_aftersales_time", type="string",  example="", description=""),
      *                                           @SWG\Property(property="share_points", type="integer", example="0",  description=""),
      *                                           @SWG\Property(property="point_fee", type="integer", example="0",  description="积分抵扣金额，以分为单位"),
      *                                           @SWG\Property(property="is_logistics", type="string", example="",  description="门店缺货商品总部快递发货"),
      *                                           @SWG\Property(property="delivery_item_num", type="string", example="",  description=""),
      *                                           @SWG\Property(property="get_points", type="integer", example="1",  description="订单获取积分"),
      *                     ),
      *                   ),
      *                   @SWG\Property(property="order_status_des", type="string", example="NOTPAY", description=""),
      *                   @SWG\Property(property="order_status_msg", type="string", example="待支付", description=""),
      *                   @SWG\Property(property="latest_aftersale_time", type="integer", example="0", description=""),
      *                   @SWG\Property(property="estimate_get_points", type="string", example="2", description=""),
      *                   @SWG\Property(property="delivery_type", type="string", example="new", description=""),
      *                   @SWG\Property(property="is_all_delivery", type="string", example="", description=""),
      *                   @SWG\Property(property="logistics_items", type="string", description=""),
      *              ),
      *           ),
      *        ),
      *     ),
      *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items( ref="#/definitions/OrdersErrorRespones") ) )
      * )
      */
     public function payment(Request $request)
     {
          $authInfo = app('auth')->user()->get();
          $params = $request->all();

          if (!isset($params['order_id']) || !$params['order_id']) {
              throw new BadRequestHttpException('订单号不存在');
          }

          if (!isset($params['auth_code']) || !$params['auth_code']) {
              if (!isset($params['pay_type']) || !$params['pay_type']) {
                  throw new BadRequestHttpException('支付方式必填');
              }
          }

          $paymentService = new PaymentService();
          $payResult = $paymentService->payment($authInfo, $params);

          return $this->response->array($payResult);
     }

     /**
      * @SWG\Post(
      *     path="/api/order/payresult",
      *     summary="支付结果查询",
      *     tags={"订单"},
      *     description="支付结果查询",
      *     operationId="queryPayment",
      *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token(h5app必填)", required=true,  type="string"),
      *     @SWG\Parameter( name="trade_id", in="query", description="支付单号", required=true, type="string"),
      *     @SWG\Response(
      *         response="200",
      *         description="响应信息返回",
      *         @SWG\Schema(
      *            @SWG\Property(property="data", type="object", description="",
      *               @SWG\Property(property="appId", type="string", example="wx912913df9fef6ddd",  description="微信appid"),
      *               @SWG\Property(property="timeStamp", type="string", example="1611649457", description="时间戳"),
      *               @SWG\Property(property="nonceStr", type="string", example="600fd1b138b86", description="随机字符串"),
      *               @SWG\Property(property="package", type="string",  example="prepay_id=wx26162417199116ecacdae0642f7bc10000", description="订单详情扩展字符串"),
      *               @SWG\Property(property="signType", type="string", example="MD5", description="签名方式"),
      *               @SWG\Property(property="paySign", type="string", example="066A398046F333991853D37971191157",  description="签名"),
      *               @SWG\Property(property="trade_info", type="object", description="",
      *                   @SWG\Property(property="order_id", type="string", example="3313653000370376",  description="订单号"),
      *                   @SWG\Property(property="trade_id", type="string", example="3313656000040376",  description="交易单号"),
      *              ),
      *            ),
      *         ),
      *     ),
      *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items( ref="#/definitions/OrdersErrorRespones") ) )
      * )
      */
     public function queryPayment(Request $request)
     {
          $authInfo = app('auth')->user()->get();
          $params = $request->all();

          if (isset($params['trade_id']) && !$params['trade_id']) {
              throw new BadRequestHttpException('支付单号不存在');
          }

          $paymentService = new PaymentService();
          $payResult = $paymentService->query($authInfo, $params);

          return $this->response->array($payResult);
     }
}
