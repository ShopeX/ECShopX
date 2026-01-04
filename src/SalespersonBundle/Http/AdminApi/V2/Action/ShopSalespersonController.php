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

namespace SalespersonBundle\Http\AdminApi\V2\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;

use SalespersonBundle\Services\SalespersonService;

class ShopSalespersonController extends Controller
{
    /**
     * @SWG\Get(
     *     path="/admin/wxapp/salespersonCount",
     *     summary="获取导购当月统计信息",
     *     tags={"导购"},
     *     description="获取导购当月统计信息",
     *     operationId="getSalespersonCountData",
     *     @SWG\Parameter( name="Accept", in="header", description="V2版本接口标识", required=true, type="string", default="application/vnd.espier.v2+json" ),
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide"),
     *     @SWG\Parameter( name="month", in="query", description="指定月份2020年10月份 20201001", required=true, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="popularize_order_fee", type="string", example="0", description="预计客户推广提成"),
     *                  @SWG\Property( property="popularize_order_count", type="string", example="0", description="推广订单数"),
     *                  @SWG\Property( property="offline_order_fee", type="string", example="0", description="门店开单金额"),
     *                  @SWG\Property( property="offline_order_count", type="string", example="0", description="门店开单数"),
     *                  @SWG\Property( property="order_fee", type="string", example="0", description="销售额"),
     *                  @SWG\Property( property="order_count", type="string", example="0", description="新增订单数"),
     *                  @SWG\Property( property="total_refund_fee", type="string", example="0", description="退款金额"),
     *                  @SWG\Property( property="total_refund_count", type="string", example="0", description="退款单数"),
     *                  @SWG\Property( property="bind_count", type="string", example="0", description="绑定客户数"),
     *                  @SWG\Property( property="friend_count", type="string", example="0", description="添加好友数"),
     *                  @SWG\Property( property="sale_count", type="string", example="0", description="销售客户数"),
     *                  @SWG\Property( property="seller_fee", type="string", example="0", description="预计绑定会员提成"),
     *                  @SWG\Property( property="popularize_seller_fee", type="string", example="0", description="预计客户推广提成"),
     *                  @SWG\Property( property="offline_seller_fee", type="string", example="0", description="预计门店开单提成"),
     *                  @SWG\Property( property="sales_fee", type="string", example="0", description="预计销售提成"),
     *          ),
     *     )),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse")))
     * )
     */
    public function getSalespersonCountData(Request $request)
    {
        $salesperson_info = $authInfo = $this->auth->user();
        $salesperson_service = new SalespersonService();
        $start = null;
        $end = null;
        $month = $request->input('month', null);
        if ($month) {
            $start = strtotime($month, time());
            $end = strtotime("$month +1 month -1 day") + 86399;
        }
        $result = $salesperson_service->getCurrentMonthStatisticsInfo($authInfo['company_id'], $authInfo['salesperson_id'], $start, $end);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/admin/wxapp/salespersonFee",
     *     summary="统计导购分润信息",
     *     tags={"导购"},
     *     description="统计导购分润信息",
     *     operationId="getSalespersonProfitFee",
     *     @SWG\Parameter( name="Accept", in="header", description="V2版本接口标识", required=true, type="string", default="application/vnd.espier.v2+json" ),
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide"),
     *     @SWG\Parameter( name="month", in="query", description="指定月份(2020-10)", required=true, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="popularize_order_fee", type="string", example="0", description="预计客户推广提成"),
     *                  @SWG\Property( property="popularize_order_count", type="string", example="0", description="推广订单数"),
     *                  @SWG\Property( property="offline_order_fee", type="string", example="0", description="门店开单金额"),
     *                  @SWG\Property( property="offline_order_count", type="string", example="0", description="门店开单数"),
     *                  @SWG\Property( property="order_fee", type="string", example="0", description="销售额"),
     *                  @SWG\Property( property="order_count", type="string", example="0", description="新增订单数"),
     *                  @SWG\Property( property="total_refund_fee", type="string", example="0", description="退款金额"),
     *                  @SWG\Property( property="total_refund_count", type="string", example="0", description="退款单数"),
     *                  @SWG\Property( property="bind_count", type="string", example="0", description="绑定客户数"),
     *                  @SWG\Property( property="friend_count", type="string", example="0", description="添加好友数"),
     *                  @SWG\Property( property="sale_count", type="string", example="0", description="销售客户数"),
     *                  @SWG\Property( property="seller_fee", type="string", example="0", description="预计绑定会员提成"),
     *                  @SWG\Property( property="popularize_seller_fee", type="string", example="0", description="预计客户推广提成"),
     *                  @SWG\Property( property="offline_seller_fee", type="string", example="0", description="预计门店开单提成"),
     *                  @SWG\Property( property="sales_fee", type="string", example="0", description="预计销售提成"),
     *                  @SWG\Property( property="unconfirmed_seller_fee", type="string", example="0", description="未确认绑定会员提成"),
     *                  @SWG\Property( property="confirm_seller_fee", type="string", example="0", description="已确认绑定会员提成"),
     *                  @SWG\Property( property="unconfirmed_offline_seller_fee", type="string", example="0", description="未确认门店开单提成"),
     *                  @SWG\Property( property="confirm_offline_seller_fee", type="string", example="0", description="已确认门店开单提成"),
     *                  @SWG\Property( property="unconfirmed_popularize_seller_fee", type="string", example="0", description="未确认客户推广提成"),
     *                  @SWG\Property( property="confirm_popularize_seller_fee", type="string", example="0", description="已确认客户推广提成"),
     *                  @SWG\Property( property="unconfirmed_fee", type="string", example="0", description="未确认金额"),
     *                  @SWG\Property( property="confirm_fee", type="string", example="0", description="已确认金额"),
     *          ),
     *     )),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse")))
     * )
     */
    public function getSalespersonProfitFee(Request $request)
    {
        $salesperson_info = $authInfo = $this->auth->user();
        $salesperson_service = new SalespersonService();
        $start = null;
        $end = null;
        $month = $request->input('month', null);
        if ($month) {
            $start = strtotime($month, time());
            $end = strtotime("$month +1 month -1 day") + 86399;
        }
        $result = $salesperson_service->profitFee($authInfo['company_id'], $authInfo['salesperson_id'], $start, $end);
        return $this->response->array($result);
    }
}
