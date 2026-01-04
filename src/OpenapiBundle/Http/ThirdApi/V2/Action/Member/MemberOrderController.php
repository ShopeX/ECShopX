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

namespace OpenapiBundle\Http\ThirdApi\V2\Action\Member;

use Illuminate\Http\Request;
use OpenapiBundle\Filter\Order\OrderFilter;
use OpenapiBundle\Http\Controllers\Controller;
use OpenapiBundle\Services\Order\OrdersNormalOrdersService;
use OpenapiBundle\Traits\Member\MemberOrderTrait;
use OpenapiBundle\Traits\Member\MemberTrait;
use Swagger\Annotations as SWG;

/**
 * 会员订单相关
 * Class MemberOrderController
 * @package OpenapiBundle\Http\Api\V2\Action\Member
 */
class MemberOrderController extends Controller
{
    use MemberTrait, MemberOrderTrait {
        // MemberOrderTrait中的handleDataToList来代替MemberTrait中的handleDataToList方法
        MemberOrderTrait::handleDataToList insteadof MemberTrait;
        // MemberTrait中的handleDataToList方法的别名为 memberTraitHandleDataToList
        MemberTrait::handleDataToList as memberTraitHandleDataToList;
    }

    /**
     * @SWG\Get(
     *     path="/ecx.member_order.list",
     *     tags={"会员"},
     *     summary="查询会员订单 - 批量",
     *     description="查询会员订单 - 批量",
     *     operationId="list",
     *
     *     @SWG\Parameter(name="app_key", in="query", description="app_key", required=true, type="string"),
     *     @SWG\Parameter(name="version", in="query", description="版本号", required=true, type="string"),
     *     @SWG\Parameter(name="timestamp", in="query", description="请求时间(合法的日期时间)", required=true, type="string"),
     *     @SWG\Parameter(name="sign", in="query", description="签名", required=true, type="string"),
     *     @SWG\Parameter(name="method", in="query", description="请求的方法名", required=true, type="string"),
     *
     *     @SWG\Parameter(name="mobile", in="query", description="会员手机号", required=true, type="string"),
     *     @SWG\Parameter(name="start_date", in="query", description="开始时间的时间戳（订单创建时间, 时间格式：yyyy-MM-dd HH:mm:ss）,默认是今年的第一天的0点0分", required=false, type="string"),
     *     @SWG\Parameter(name="end_date", in="query", description="结束时间的时间戳（订单创建时间, 时间格式：yyyy-MM-dd HH:mm:ss）", required=false, type="string"),
     *     @SWG\Parameter(name="page", in="query", description="当前页数	1", required=false, type="integer"),
     *     @SWG\Parameter(name="page_size", in="query", description="每页显示数量（不填默认20条）", required=false, type="integer"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(required={"code","message","data"},
     *            @SWG\Property(property="code", type="string", default="E0000", description=""),
     *            @SWG\Property(property="message", type="string", default="success", description=""),
     *            @SWG\Property(property="data", type="object", description="",required={"pager","total_count","is_last_page","list"},
     *                 @SWG\Property(property="pager", type="object", description="分页相关信息",required={"page","page_size"},
     *                      @SWG\Property(property="page", type="integer", default="1", description="当前页数"),
     *                      @SWG\Property(property="page_size", type="integer", default="10", description="每页显示数量（默认20条）"),
     *                 ),
     *                 @SWG\Property(property="total_count", type="integer", default="8", description="列表数据总数量"),
     *                 @SWG\Property(property="is_last_page", type="integer", default="1", description="是否最后一页【0 不是最后一页】【1 是最后一页】"),
     *                 @SWG\Property(property="list", type="array", description="",
     *                 @SWG\Items(required={"order_id","create_time","total_fee","order_class","order_type","type","order_status","ziti_status","cancel_status","pay_status","audit_status","distributor_id","distributor_name"},
     *                           @SWG\Property(property="order_id", type="string", default="3466713000240576", description="订单号"),
     *                           @SWG\Property(property="create_time", type="string", default="1624982400", description="订单创建时间，时间戳"),
     *                           @SWG\Property(property="total_fee", type="string", default="13.00", description="订单实付金额（以元为单位）"),
     *                           @SWG\Property(property="order_class", type="string", default="normal", description="订单种类 【bargain 助力订单】【community 社区活动订单】【crossborder 跨境订单】【groups 拼团订单】【normal 普通订单】【pointsmall 积分商城】【seckill 秒杀订单】【shopguide 导购订单】"),
     *                           @SWG\Property(property="order_type", type="string", default="normal", description="订单类型 【normal 普通实体订单】"),
     *                           @SWG\Property(property="type", type="string", default="0", description="订单类型，【0 普通订单】【1 跨境订单】"),
     *                           @SWG\Property(property="order_status", type="string", default="PAYED", description="订单状态，【CANCEL 已取消】【DONE 订单完成】【NOTPAY 未支付】【PART_PAYMENT 部分付款】【PAYED 已支付】【REFUND_SUCCESS 退款成功】【WAIT_BUYER_CONFIRM 等待用户收货】【WAIT_GROUPS_SUCCESS 等待拼团成功】"),
     *                           @SWG\Property(property="ziti_status", type="string", default="NOTZITI", description="店铺自提状态，【APPROVE 审核通过,药品自提需要审核】【DONE 自提完成】【NOTZITI 自提完成】【PENDING 等待自提】"),
     *                           @SWG\Property(property="cancel_status", type="string", default="NO_APPLY_CANCEL", description="取消订单状态，【NO_APPLY_CANCEL 未申请】【WAIT_PROCESS 等待审核】【REFUND_PROCESS 退款处理】【SUCCESS 取消成功】【FAILS 取消失败】"),
     *                           @SWG\Property(property="pay_status", type="string", default="PAYED", description="支付状态，【NOTPAY 未支付】【PAYED 已支付】【ADVANCE_PAY 预付款完成】【TAIL_PAY 支付尾款中】"),
     *                           @SWG\Property(property="audit_status", type="string", default="processing", description="跨境订单审核状态，【approved 成功】【processing 审核中】【rejected 审核拒绝】"),
     *                           @SWG\Property(property="distributor_id", type="integer", default="145", description="销售门店ID"),
     *                           @SWG\Property(property="distributor_name", type="string", default="测试店铺3", description="销售门店名称	"),
     *                 ),
     *               ),
     *            ),

     *         ),
     *     ),
     * )
     */
    public function list(Request $request)
    {
        // 过滤条件
        $filter = (new OrderFilter($request->only(["mobile", "start_date", "end_date"])))->get();
        $result = (new OrdersNormalOrdersService())->list($filter, $this->getPage(), $this->getPageSize(), ["order_id" => "DESC"], "order_id,create_time,total_fee,order_class,order_type,type,order_status,ziti_status,cancel_status,pay_status,audit_status,distributor_id", true);
        if (!empty($result["list"])) {
            // 处理订单信息
            $this->handleDataToList($result["list"]);
            // 追加门店信息
            $this->appendDistributorInfoToList($filter["company_id"], $result["list"]);
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/ecx.member_point_order.list",
     *     tags={"会员"},
     *     summary="查询会员订单积分 - 批量",
     *     description="查询会员订单积分 - 批量",
     *     operationId="pointList",
     *
     *     @SWG\Parameter(name="app_key", in="query", description="app_key", required=true, type="string"),
     *     @SWG\Parameter(name="version", in="query", description="版本号", required=true, type="string"),
     *     @SWG\Parameter(name="timestamp", in="query", description="请求时间(合法的日期时间)", required=true, type="string"),
     *     @SWG\Parameter(name="sign", in="query", description="签名", required=true, type="string"),
     *     @SWG\Parameter(name="method", in="query", description="请求的方法名", required=true, type="string"),
     *
     *     @SWG\Parameter(name="mobile", in="query", description="会员手机号", required=true, type="string"),
     *     @SWG\Parameter(name="order_id", in="query", description="订单ID", required=false, type="string"),
     *     @SWG\Parameter(name="page", in="query", description="当前页数	1", required=false, type="integer"),
     *     @SWG\Parameter(name="page_size", in="query", description="每页显示数量（不填默认20条）", required=false, type="integer"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(required={"code","message","data"},
     *             @SWG\Property(property="code", type="string", default="E0000", description=""),
     *             @SWG\Property(property="message", type="string", default="success", description=""),
     *             @SWG\Property(property="data", type="object", description="",required={"list","total_count","is_last_page","pager"},
     *               @SWG\Property(property="list", type="array", description="",
     *                 @SWG\Items(required={"order_id","mobile","username","create_time","get_points","bonus_points","extra_points","point_use","point_fee","total_points"},
     *                           @SWG\Property(property="order_id", type="string", default="3462563000280552", description="订单号"),
     *                           @SWG\Property(property="mobile", type="string", default="15901871111", description="会员手机号"),
     *                           @SWG\Property(property="username", type="string", default="111111111", description="会员姓名/昵称"),
     *                           @SWG\Property(property="create_time", type="string", default="2021-06-24 14:05:53", description="订单创建时间(日期格式:yyyy-MM-dd HH:mm:ss)"),
     *                           @SWG\Property(property="get_points", type="integer", default="0", description="订单可获得积分数"),
     *                           @SWG\Property(property="bonus_points", type="integer", default="0", description="购物赠送积分数"),
     *                           @SWG\Property(property="extra_points", type="integer", default="0", description="可获得额外积分数"),
     *                           @SWG\Property(property="point_use", type="integer", default="1", description="积分抵扣使用的积分数"),
     *                           @SWG\Property(property="point_fee", type="string", default="0.01", description="积分实际抵扣金额（以元为单位）"),
     *                           @SWG\Property(property="total_points", type="integer", default="0", description="订单可获得积分总数（订单可获得积分数+购物赠送积分数+可获得额外积分数）"),
     *                 ),
     *               ),
     *               @SWG\Property(property="total_count", type="integer", default="6", description=""),
     *               @SWG\Property(property="is_last_page", type="integer", default="1", description=""),
     *               @SWG\Property(property="pager", type="object", description="",required={"page","page_size"},
     *                   @SWG\Property(property="page", type="integer", default="1", description=""),
     *                   @SWG\Property(property="page_size", type="integer", default="20", description=""),
     *              ),
     *            ),

     *         ),
     *     ),
     * )
     */
    public function pointList(Request $request)
    {
        // 过滤条件
        $filter = (new OrderFilter($request->only(["mobile", "order_id"])))->get();
        // 过滤条件
        $result = (new OrdersNormalOrdersService())->list($filter, $this->getPage(), $this->getPageSize(), ["order_id" => "DESC"], "order_id,user_id,mobile,create_time,get_points,bonus_points,extra_points,point_use,point_fee", true);
        if (!empty($result["list"])) {
            // 处理订单信息
            $this->handleDataToList($result["list"]);
            // 追加总积分信息
            $this->appendTotalPointToList($result["list"]);

            $this->appendDetailToList($filter["company_id"], $result["list"]);
        }
        return $this->response->array($result);
    }
}
