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

namespace AftersalesBundle\Http\AdminApi\V1\Action;

use DistributionBundle\Services\DistributorService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use AftersalesBundle\Services\AftersalesService;
use Dingo\Api\Exception\ResourceException;
use EspierBundle\Traits\GetExportServiceTraits;
use OrdersBundle\Entities\NormalOrders;
use OrdersBundle\Entities\Trade;
use OrdersBundle\Services\Orders\NormalOrderService;

class Aftersales extends Controller
{
    use GetExportServiceTraits;

    /**
     * @SWG\Definition(
     *     definition="Aftersales",
     *     type="object",
     *     @SWG\Property(property="aftersales_bn", type="string", example="202012025055505", description="售后编号"),
     *     @SWG\Property(property="order_id", type="string", example="3258750000110027", description="订单号"),
     *     @SWG\Property(property="company_id", type="integer", example="1",description="公司id"),
     *     @SWG\Property(property="user_id", type="integer", example="111",description="用户id"),
     *     @SWG\Property(property="salesman_id", type="integer", example="1",description="导购员ID"),
     *     @SWG\Property(property="shop_id", type="integer", example="1",description="店铺id"),
     *     @SWG\Property(property="distributor_id", type="integer", example="1",description="分销商id"),
     *     @SWG\Property(property="aftersales_type", type="string", example="ONLY_REFUND",description="售后类型"),
     *     @SWG\Property(property="aftersales_status", type="integer", example="4",description="售后状态"),
     *     @SWG\Property(property="progress", type="integer", example="1",description="进度"),
     *     @SWG\Property(property="refund_fee", type="integer", example="1",description="售后金额"),
     *     @SWG\Property(property="refund_point", type="string", example="100",description="售后积分"),
     *     @SWG\Property(property="description", type="string", example="ddd",description="描述"),
     *     @SWG\Property(property="evidence_pic", type="array", @SWG\Items(), description="图片凭证信息"),
     *     @SWG\Property(property="refuse_reason", type="string", example="",description="拒绝原因"),
     *     @SWG\Property(property="memo", type="string", example="",description="售后备注"),
     *     @SWG\Property(property="sendback_data", type="object",
     *         @SWG\Property(property="corp_code", type="string", example="",description="物流公司代码"),
     *         @SWG\Property(property="logi_no", type="string", example="",description="快递单号"),
     *         @SWG\Property(property="receiver_address", type="string", example="",description="收货地址"),
     *         @SWG\Property(property="receiver_mobile", type="string", example="",description="收货人电话"),
     *     ),
     *     @SWG\Property(property="sendconfirm_data", type="array", @SWG\Items(), description="商家重新发货物流信息"),
     *     @SWG\Property(property="create_time", type="string", example="1606916210",description="创建时间"),
     *     @SWG\Property(property="update_time", type="string", example="1607936870",description="更新时间"),
     *     @SWG\Property(property="third_data", type="string", example="",description="第三方返回的数据"),
     *     @SWG\Property(property="aftersales_address", type="array", @SWG\Items(), description=""),
     *     @SWG\Property(property="detail", type="array", @SWG\Items(
     *         ref="#/definitions/AftersalesDetail"
     *     )),
     *     @SWG\Property(property="order_info", type="object",
     *         ref="#/definitions/OrderInfo"
     *     ),
     *     @SWG\Property(property="salesman", type="object",
     *         @SWG\Property(property="avatar", type="string", example="",description="头像"),
     *         @SWG\Property(property="child_count", type="ingeter", example=0, example="", description="导购注册人员数量"),
     *         @SWG\Property(property="company_id", type="ingeter", example="1",description="公司id"),
     *         @SWG\Property(property="created", type="string", example="1611908897",description="创建时间"),
     *         @SWG\Property(property="created_time", type="string", example="1611908897",description="创建时间"),
     *         @SWG\Property(property="friend_count", type="ingeter", example=1, description="导购员会员好友数"),
     *         @SWG\Property(property="is_valid", type="string", example="true",description="是否有效"),
     *         @SWG\Property(property="mobile", type="string", example="188***383",description="手机号"),
     *         @SWG\Property(property="name", type="string", example="zhangsan",description="姓名"),
     *         @SWG\Property(property="number", type="string", example="1",description="编号"),
     *         @SWG\Property(property="role", type="string", example="1",description="角色"),
     *         @SWG\Property(property="salesperson_id", type="string", example="1",description="导购员id"),
     *         @SWG\Property(property="salesperson_type", type="string", example="shopping_guide",description="导购员类型"),
     *         @SWG\Property(property="shop_id", type="string", example="1",description="门店id"),
     *         @SWG\Property(property="shop_name", type="string", example="",description="门店名称"),
     *         @SWG\Property(property="updated", type="string", example="1611908918",description="更新时间"),
     *         @SWG\Property(property="user_id", type="integer", example=1, description="会员id"),
     *         @SWG\Property(property="work_configid", type="string", example="a212630df988da53f113424d1cc56ad2",description="企业微信userid"),
     *         @SWG\Property(property="work_qrcode_configid", type="string", example="17c6c1a04172fd0f82f32e5d8d1de408",description="企业微信userid"),
     *         @SWG\Property(property="work_userid", type="string", example="s1743",description="企业微信userid"),
     *     ),
     * )
     */

    /**
     * @SWG\Definition(
     *     definition="AftersalesDetail",
     *     type="object",
     *     @SWG\Property(property="aftersales_bn", type="string", example="202012025055505",description="售后单号"),
     *     @SWG\Property(property="order_id", type="string", example="3258750000110027",description="订单号"),
     *     @SWG\Property(property="company_id", type="integer", example="1",description="公司id"),
     *     @SWG\Property(property="user_id", type="integer", example="111",description="用户id"),
     *     @SWG\Property(property="detail_id", type="integer", example="1",description="明细id"),
     *     @SWG\Property(property="sub_order_id", type="integer", example="1",description="子订单id"),
     *     @SWG\Property(property="item_id", type="integer",description="商品id"),
     *     @SWG\Property(property="item_bn", type="string", example="S5FAD27C9C2C44",description="商品编号"),
     *     @SWG\Property(property="item_name", type="integer", example="测试商品",description="商品名"),
     *     @SWG\Property(property="order_item_type", type="string", example="normal",description="商品类型"),
     *     @SWG\Property(property="item_pic", type="string", example="http://mmbiz.qpic.cn/mmbiz_jpg",description="商品图片"),
     *     @SWG\Property(property="num", type="integer", example="100",description="数量"),
     *     @SWG\Property(property="refund_fee", type="string", example="0",description="退还金额"),
     *     @SWG\Property(property="refund_point", type="string", example="1",description="退还积分"),
     *     @SWG\Property(property="aftersales_type", type="string", example="ONLY_REFUND",description="售后类型"),
     *     @SWG\Property(property="progress", type="integer", example="7",description="进度"),
     *     @SWG\Property(property="aftersales_status", type="integer", example="7",description="售后状态"),
     *     @SWG\Property(property="create_time", type="string", example="1606916210",description="申请时间"),
     *     @SWG\Property(property="update_time", type="string", example="1607936870",description="更新时间"),
     *     @SWG\Property(property="auto_refuse_time", type="integer", example="0",description="售后自动驳回时间"),
     * )
     */


    /**
     * @SWG\Definition(
     *     definition="OrderInfo",
     *     type="object",
     *     @SWG\Property(property="title", type="string", example="测试",description=""),
     *     @SWG\Property(property="order_id", type="string", example="3258750000110027",description="订单id"),
     *     @SWG\Property(property="company_id", type="integer", example="1",description="公司id"),
     *     @SWG\Property(property="user_id", type="integer", example="111",description="用户id"),
     *     @SWG\Property(property="act_id", type="integer", example="1",description="活动id"),
     *     @SWG\Property(property="mobile", type="integer", example="",description="手机号"),
     *     @SWG\Property(property="freight_fee", type="integer",description="运费"),
     *     @SWG\Property(property="freight_type", type="string", example="cash",description="配送方式"),
     *     @SWG\Property(property="item_fee", type="integer", example="7",description="商品金额"),
     *     @SWG\Property(property="item_point", type="integer", example="0",description="商品积分"),
     *     @SWG\Property(property="cost_fee", type="integer", example="10000",description="商品成本价"),
     *     @SWG\Property(property="total_fee", type="integer", example="4",description="订单金额"),
     *     @SWG\Property(property="step_paid_fee", type="integer", example="0",description="分阶段付款已支付金额"),
     *     @SWG\Property(property="total_rebate", type="integer", example="1",description="总分销金额"),
     *     @SWG\Property(property="receipt_type", type="string", example="logistics",description="收货方式"),
     *     @SWG\Property(property="ziti_code", type="integer", example="7",description="自提码"),
     *     @SWG\Property(property="shop_id", type="integer", example="7",description="门店id"),
     *     @SWG\Property(property="ziti_status", type="string", example="NOTZITI",description="自提状态"),
     *     @SWG\Property(property="order_status", type="string", example="DONE",description="订单状态"),
     *     @SWG\Property(property="order_source", type="string", example="member",description="订单来源"),
     *     @SWG\Property(property="order_type", type="string", example="normal",description="订单类型"),
     *     @SWG\Property(property="order_class", type="string", example="normal",description="订单种类"),
     *     @SWG\Property(property="auto_cancel_time", type="string", example="1606906883",description="自动取消时间"),
     *     @SWG\Property(property="auto_cancel_seconds", type="string", example="100",description="自动取消时间"),
     *     @SWG\Property(property="auto_finish_time", type="string", example="",description="自动完成时间"),
     *     @SWG\Property(property="is_distribution", type="boolean", example="true",description="是否分销订单"),
     *     @SWG\Property(property="source_id", type="string", example="",description="订单来源id"),
     *     @SWG\Property(property="monitor_id", type="integer", example="",description="订单监控页面id"),
     *     @SWG\Property(property="salesman_id", type="integer", example="",description="导购员id"),
     *     @SWG\Property(property="delivery_corp", type="string", example="",description="快递公司"),
     *     @SWG\Property(property="delivery_code", type="string", example="",description="快递单号"),
     *     @SWG\Property(property="delivery_img", type="string", example="",description=""),
     *     @SWG\Property(property="delivery_status", type="string", example="DONE",description="物流状态"),
     *     @SWG\Property(property="cancel_status", type="string", example="NO_APPLY_CANCEL",description="取消状态"),
     *     @SWG\Property(property="delivery_time", type="string", example="1606907764",description="发货时间"),
     *     @SWG\Property(property="end_time", type="string", example="1606908751",description="结束时间"),
     *     @SWG\Property(property="end_date", type="string", example="",description="结束日期"),
     *     @SWG\Property(property="receiver_name", type="string", example="",description="收货人姓名"),
     *     @SWG\Property(property="receiver_mobile", type="string", example="",description="收货人手机号"),
     *     @SWG\Property(property="receiver_zip", type="string", example="",description="邮编"),
     *     @SWG\Property(property="receiver_state", type="string", example="",description="收货状态"),
     *     @SWG\Property(property="receiver_district", type="string", example="",description="收货地区"),
     *     @SWG\Property(property="receiver_address", type="string", example="",description="收货地址"),
     *     @SWG\Property(property="member_discount", type="string", example="",description="会员折扣金额"),
     *     @SWG\Property(property="coupon_discount", type="string", example="",description="优惠券折扣"),
     *     @SWG\Property(property="discount_fee", type="string", example="",description="优惠金额"),
     *     @SWG\Property(property="create_time", type="string", example="",description="创建时间"),
     *     @SWG\Property(property="update_time", type="string", example="",description="更新时间"),
     *     @SWG\Property(property="fee_type", type="string", example="",description="货币方式"),
     *     @SWG\Property(property="fee_rate", type="string", example="",description="系统配置货币汇率"),
     *     @SWG\Property(property="fee_symbol", type="string", example="",description="货币符号"),
     *     @SWG\Property(property="cny_fee", type="string", example="",description=""),
     *     @SWG\Property(property="point", type="string", example="",description="消费积分"),
     *     @SWG\Property(property="third_params", type="string", example="",description="第三方特殊字段存储"),
     *     @SWG\Property(property="invoice", type="string", example="",description="发票"),
     *     @SWG\Property(property="send_point", type="string", example="",description="是否分发积分"),
     *     @SWG\Property(property="is_rate", type="string", example="",description="是否评价"),
     *     @SWG\Property(property="is_invoiced", type="string", example="",description="是否开票"),
     *     @SWG\Property(property="invoice_number", type="string", example="",description="发票号"),
     *     @SWG\Property(property="audit_status", type="string", example="",description="跨境订单审核状态"),
     *     @SWG\Property(property="audit_msg", type="string", example="",description="审核意见"),
     *     @SWG\Property(property="point_use", type="string", example="",description="积分抵扣使用的积分数"),
     *     @SWG\Property(property="get_points", type="string", example="",description="订单获取积分"),
     *     @SWG\Property(property="bonus_points", type="string", example="",description="购物赠送积分"),
     *     @SWG\Property(property="get_point_type", type="string", example="",description="获取积分类型"),
     *     @SWG\Property(property="pack", type="string", example="",description="包装"),
     *     @SWG\Property(property="identity_id", type="string", example="",description="身份证号码"),
     *     @SWG\Property(property="identity_name", type="string", example="",description="身份证姓名"),
     *     @SWG\Property(property="total_tax", type="string", example="",description="总税费"),
     *     @SWG\Property(property="discount_info", type="object", @SWG\Items(
     *         @SWG\Property(property="id", type="integer", example="",description=""),
     *         @SWG\Property(property="coupon_code", type="string", example="",description="优惠券编号"),
     *         @SWG\Property(property="info", type="string", example="",description=""),
     *         @SWG\Property(property="type", type="string", example="",description="类型"),
     *         @SWG\Property(property="rule", type="string", example="",description="规则"),
     *         @SWG\Property(property="discount_fee", type="string", example="",description="优惠金额"),
     *     ))
     * )
     */

    /**
     * @SWG\Post(
     *     path="/wxapp/aftersales",
     *     summary="创建售后单",
     *     tags={"售后"},
     *     description="创建售后单",
     *     operationId="apply",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="order_id", in="query", description="订单号", required=true, type="string"),
     *     @SWG\Parameter( name="item_id", in="query", description="交易商品ID", required=true, type="string"),
     *     @SWG\Parameter( name="reason", in="query", description="申请售后原因", required=true, type="string"),
     *     @SWG\Parameter( name="contact_info", in="query", description="联系信息", required=true, type="string"),
     *     @SWG\Parameter( name="evidence_pic", in="query", description="图片凭证信息", required=true, type="string"),
     *     @SWG\Parameter( name="aftersales_type", in="query", required=true, description="售后商品明细", required=true, type="string"),
     *     @SWG\Parameter( name="detail", in="query", required=true, description="售后类型， ONLY_REFUND 仅退款 REFUND_GOODS 退货退款 EXCHANGING_GOODS 换货", required=true, type="string"),
     *     @SWG\Parameter( name="description", in="query", description="申请描述", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 ref="#/definitions/Aftersales"
     *             ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AftersalesErrorRespones") ) )
     * )
     */
    public function apply(Request $request)
    {
        $authInfo = $this->auth->user();
        $params = $request->all('order_id', 'detail', 'aftersales_type', 'reason', 'evidence_pic', 'description');
        $params['company_id'] = $authInfo['company_id'];
        $params['distributor_id'] = $authInfo['distributor_id'];
        $rules = [
            'order_id' => ['required', '订单号必填'],
            'detail' => ['required', '售后商品明细必填'],
            //'item_id'  => ['required|integer|min:1', '商品ID必填,商品ID必须为整数'],
            'company_id' => ['required', '企业id必填'],
            'distributor_id' => ['required', '店铺id必填'],
            'aftersales_type' => ['required', '售后类型必选'],
            'reason' => ['required', '售后原因必选'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        if ($authInfo['salesperson_type'] == 'shopping_guide') {
            $params['salesman_id'] = $authInfo['salesperson_id'];
        }

//        $normalOrderService = new NormalOrderService();
//        $orderItem = $normalOrderService->getOrderItemInfo($params['company_id'], $params['order_id'], $params['item_id']);
        $normalOrdersRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
        $orderItem = $normalOrdersRepository->getInfo(['company_id' => $params['company_id'], 'order_id' => $params['order_id']]);
        //$params['number'] = $orderItem['num'];
        //$params['share_points'] = $orderItem['share_points'];
        $params['user_id'] = $orderItem['user_id'];
        $aftersalesService = new AftersalesService();
        $result = $aftersalesService->salespersonCreateAftersales($params, $orderItem);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/aftersales/{aftersales_bn}",
     *     summary="获取售后单详情",
     *     tags={"售后"},
     *     description="获取售后单详情",
     *     operationId="getAftersalesDetail",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="aftersales_bn", in="path", description="售后单号", required=true, type="integer"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 ref="#/definitions/Aftersales"
     *             ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AftersalesErrorRespones") ) )
     * )
     */
    public function getAftersalesDetail(Request $request)
    {
        // 53686f704578
        if (!$request->input('aftersales_bn')) {
            throw new ResourceException('售后单号不能为空');
        }
        $authInfo = $this->auth->user();
        $companyId = $authInfo['company_id'];
        $aftersalesService = new AftersalesService();
        $filter = [
            'company_id' => $companyId,
            'aftersales_bn' => $request->input('aftersales_bn'),
        ];
        $result = $aftersalesService->getAftersales($filter);
        $result['create_time'] = date('Y-m-d H:i:s', $result['create_time']);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/aftersales",
     *     summary="获取售后单列表",
     *     tags={"售后"},
     *     description="获取售后单列表",
     *     operationId="getAftersalesList",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide"),
     *     @SWG\Parameter( name="page", in="query", description="页数", required=true, type="integer", default="1"),
     *     @SWG\Parameter( name="page_size", in="query", description="每页数量", required=true, type="integer", default="20"),
     *     @SWG\Parameter( name="aftersales_bn", in="query", description="售后单号", type="string"),
     *     @SWG\Parameter( name="order_id", in="query", description="订单号", type="string", ),
     *     @SWG\Parameter( name="aftersales_type", in="query", description="售后类型", type="string", ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                    @SWG\Property(property="total_count", type="integer", example=1),
     *                    @SWG\Property(property="list", type="array",
     *                        @SWG\Items(ref="#definitions/Aftersales")
     *                    ),
     *                 )
     *             ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AftersalesErrorRespones") ) )
     * )
     */
    public function getAftersalesList(Request $request)
    {
        // 53686f704578
        $validator = app('validator')->make($request->all(), [
            'page' => 'required|integer|min:1',
            'pageSize' => 'required|integer|min:1|max:50',
        ]);
        if ($request->input('time_start_begin')) {
            $filter['create_time|gte'] = $request->input('time_start_begin');
            $filter['create_time|lte'] = $request->input('time_start_end');
        }

        $filter['aftersales_status'] = $request->input('aftersales_status');

        if ($request->input('aftersales_type')) {
            $filter['aftersales_type'] = $request->input('aftersales_type');
        }
        if ($request->input('aftersales_bn')) {
            $filter['aftersales_bn'] = $request->input('aftersales_bn');
        }
        if ($request->input('item_id')) {
            $filter['item_id'] = $request->input('item_id');
        }
        if ($request->input('order_id')) {
            $filter['order_id'] = $request->input('order_id');
        }
        if ($request->input('shop_id')) {
            $filter['shop_id'] = $request->input('shop_id');
        }
        if ($request->input('user_id')) {
            $filter['user_id'] = $request->input('user_id');
        }
        if ($request->input('mobile')) {
            $filter['mobile'] = $request->input('mobile');
        }

        $authInfo = $this->auth->user();

        $filter['company_id'] = $authInfo['company_id'];
        $filter['distributor_id'] = $authInfo['distributor_id'];
        $page = $request->input('page', 1);
        $limit = $request->input('pageSize', 20);
        $offset = ($page - 1) * $limit;
        $filter['need_order'] = true;

        $aftersalesService = new AftersalesService();
        $result = $aftersalesService->getAftersalesList($filter, $offset, $limit);

        $result['order_info'] = [];
        $result['distributor'] = [];
        if (isset($filter['order_id']) && $filter['order_id']) {
            $normalOrderService = new NormalOrderService();
            $order_filter = [
                'company_id' => $filter['company_id'],
                'order_id' => $filter['order_id'],
            ];
            $result['order_info'] = $normalOrderService->getInfo($order_filter);
            if (!$result['order_info']) {
                throw new ResourceException('订单不存在');
            }
            $result['order_info']['distributor_name'] = '';
            $distributorService = new DistributorService();
            $distributorInfo = [];
            if ($result['order_info']['distributor_id']) {
                $distributor_filter['distributor_id'] = $result['order_info']['distributor_id'];
                $distributor_filter['company_id'] = $filter['company_id'];
                $distributorInfo = $distributorService->getInfo($distributor_filter);
                $result['order_info']['distributor_name'] = isset($distributorInfo['name']) ? $distributorInfo['name'] : "";
            }
            $result['distributor'] = $distributorInfo;

            //获取交易单信息
            $tradeRepository = app('registry')->getManager('default')->getRepository(Trade::class);
            $trade_filter = [
                'company_id' => $filter['company_id'],
                'order_id' => $filter['order_id'],
            ];
            $trade = $tradeRepository->getTradeList($trade_filter);
            $tradeInfo = [];
            if ($trade['list']) {
                $tradeInfo = $trade['list'][0];
            }

            $result['trade'] = $tradeInfo;
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/wxapp/aftersales/review",
     *     summary="售后审核",
     *     tags={"售后"},
     *     description="售后审核",
     *     operationId="aftersalesReview",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="aftersales_bn", in="formData", description="售后单号", required=true, type="string", ),
     *     @SWG\Parameter( name="is_approved", in="formData", description="处理结果", required=true, type="string", ),
     *     @SWG\Parameter( name="refuse_reason", in="formData", description="拒绝原因", type="string", ),
     *     @SWG\Parameter( name="refund_fee", in="formData", description="退款金额", type="string", ),
     *     @SWG\Parameter( name="refund_point", in="formData", description="退还积分", type="string", ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="aftersales_address", type="array", @SWG\Items()),
     *                 @SWG\Property(property="aftersales_bn", type="string", example="202101219952564"),
     *                 @SWG\Property(property="aftersales_status", type="integer", example="0"),
     *                 @SWG\Property(property="aftersales_type", type="string", example="ONLY_REFUND"),
     *                 @SWG\Property(property="company_id", type="integer", example="1"),
     *                 @SWG\Property(property="create_time", type="string", example="1611201484"),
     *                 @SWG\Property(property="description", type="integer", example="321321"),
     *                 @SWG\Property(property="evidence_pic", type="array", @SWG\Items()),
     *                 @SWG\Property(property="memo", type="string", example=""),
     *                 @SWG\Property(property="order_id", type="integer", example="3308477000040347"),
     *                 @SWG\Property(property="progress", type="integer", example="0"),
     *                 @SWG\Property(property="reason", type="string", example="sdadasdas"),
     *                 @SWG\Property(property="refund_fee", type="integer", example="10000"),
     *                 @SWG\Property(property="refund_point", type="integer", example="0"),
     *                 @SWG\Property(property="refuse_reason", type="string", example=""),
     *                 @SWG\Property(property="salesman_id", type="integer", example="0"),
     *                 @SWG\Property(property="sendback_data", type="array", @SWG\Items()),
     *                 @SWG\Property(property="sendconfirm_data", type="array", @SWG\Items()),
     *                 @SWG\Property(property="shop_id", type="integer", example="0"),
     *                 @SWG\Property(property="third_data", type="string", example=""),
     *                 @SWG\Property(property="user_id", type="integer", example="20347"),
     *
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AftersalesErrorRespones") ) )
     * )
     */
    public function aftersalesReview(Request $request)
    {
        $authInfo = $this->auth->user();
        $companyId = $authInfo['company_id'];
        $params = $request->all();
        $params['company_id'] = $companyId;
        $validator = app('validator')->make($params, [
            'aftersales_bn' => 'required|integer',
            'company_id' => 'required',
            'is_approved' => 'required',
            'refuse_reason' => 'required_if:is_approved,0',
        ], [
            'aftersales_bn.*' => '售后单号必填,必须为整数',
            'company_id.*' => '企业id必填',
            'is_approved.*' => '处理结果必选',
            'refuse_reason.*' => '拒绝原因必填',
        ]);
        if ($validator->fails()) {
            $errorsMsg = $validator->errors()->toArray();
            $errmsg = '';
            foreach ($errorsMsg as $v) {
                $msg = implode("，", $v);
                $errmsg .= $msg . "，";
            }
            throw new ResourceException(trim($errmsg, '，'));
        }
        $params['refund_fee'] = intval($params['refund_fee'] ?? 0);
        $params['refund_point'] = $params['refund_point'] ?? 0;
        if ($params['is_approved'] == 1 && !$params['refund_fee'] && !$params['refund_point']) {
            throw new ResourceException('退款金额或积分必填');
        }
        $aftersalesService = new AftersalesService();
        $result = $aftersalesService->review($params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/wxapp/aftersales/sendConfirm",
     *     summary="换货重新发货",
     *     tags={"售后"},
     *     description="换货重新发货",
     *     operationId="sendConfirm",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="aftersales_bn", in="formData", description="售后单号", required=true, type="string"),
     *     @SWG\Parameter( name="corp_code", in="formData", description="快递公司", required=true, type="string"),
     *     @SWG\Parameter( name="logi_no", in="formData", description="快递单号", required=true, type="string"),
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
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AftersalesErrorRespones") ) )
     * )
     */
    public function sendConfirm(Request $request)
    {
        $authInfo = $this->auth->user();
        $companyId = $authInfo['company_id'];
        $params = $request->all();
        $params['company_id'] = $companyId;
        $validator = app('validator')->make($params, [
            'aftersales_bn' => 'required',
            'company_id' => 'required',
            'corp_code' => 'required|min:6|max:30',
            'logi_no' => 'required',
        ], [
            'aftersales_bn.*' => '售后单号必填',
            'company_id.*' => '企业ID必填',
            'corp_code.*' => '物流公司不能为空',
            'logi_no.*' => '物流单号不能为空,运单号不能小于6,运单号不能大于20',
        ]);

        $aftersalesService = new AftersalesService();
        $result = $aftersalesService->sendConfirm($params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/wxapp/aftersales/refundCheck",
     *     summary="退款确认",
     *     tags={"售后"},
     *     description="退款确认",
     *     operationId="refundCheck",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="aftersales_bn", in="formData", description="售后单号", required=true, type="string"),
     *     @SWG\Parameter( name="check_refund", in="formData", description="是否退款", required=true, type="string"),
     *     @SWG\Parameter( name="refund_memo", in="formData", description="退款备注", required=true, type="string"),
     *     @SWG\Parameter( name="refund_fee", in="formData", description="退款金额", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 ref="#/definitions/Aftersales"
     *             )
     *         )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AftersalesErrorRespones") ) )
     * )
     */
    public function refundCheck(Request $request)
    {
        $authInfo = $this->auth->user();
        $companyId = $authInfo['company_id'];
        $params = $request->all();
        $params['company_id'] = $companyId;
        $validator = app('validator')->make($params, [
            'aftersales_bn' => 'required',
            'company_id' => 'required',
            'check_refund' => 'required',
            'refund_memo' => 'required',
            'refund_fee' => 'required_if: check_refund,1|integer',
        ], [
            'aftersales_bn.*' => '售后单号必填',
            'company_id.*' => '企业ID必填',
            'check_refund.*' => '是否退款必选',
            'refund_memo.*' => '退款备注必填',
            'refund_fee.*' => '退款金额必填,以分为单位，必须为整数',
        ]);

        $aftersalesService = new AftersalesService();
        $result = $aftersalesService->confirmRefund($params);

        return $this->response->array($result);
    }
}
