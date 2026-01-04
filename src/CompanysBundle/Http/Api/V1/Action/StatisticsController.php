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

namespace CompanysBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use CompanysBundle\Services\EmployeeService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use CompanysBundle\Services\CompanysStatisticsService;

use SuperAdminBundle\Services\ShopMenuService;

class StatisticsController extends BaseController
{
    private $companysStatistics;

    public function __construct(CompanysStatisticsService $companysStatisticsService)
    {
        $this->companysStatistics = new $companysStatisticsService();
    }

    /**
     * @SWG\Get(
     *     path="/getStatistics",
     *     summary="获取商城订单统计信息",
     *     tags={"企业"},
     *     description="获取商城订单统计信息",
     *     operationId="getDataList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string", required=true),
     *     @SWG\Parameter( name="shop_id", in="query", description="店铺id", type="string", required=true),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(
     *                     property="today_data",
     *                     type="object",
     *                     @SWG\Property( property="real_payed_fee", type="integer",example=0, description=""),
     *                     @SWG\Property( property="real_payed_orders", type="integer",example=0, description=""),
     *                     @SWG\Property( property="real_refunded_fee", type="integer",example=0, description=""),
     *                     @SWG\Property( property="real_aftersale_count", type="integer",example=0, description=""),
     *                     @SWG\Property( property="real_payed_members", type="integer",example=0, description=""),
     *                     @SWG\Property( property="real_deposit", type="integer",example=0, description=""),
     *                     @SWG\Property( property="real_atv", type="integer",example=0, description="")
     *                 ),
     *                 @SWG\Property(
     *                     property="yesterday_data",
     *                     type="object",
     *                     @SWG\Property( property="real_payed_fee", type="integer",example=0, description=""),
     *                     @SWG\Property( property="real_payed_orders", type="integer",example=0, description=""),
     *                     @SWG\Property( property="real_refunded_fee", type="integer",example=0, description=""),
     *                     @SWG\Property( property="real_aftersale_count", type="integer",example=0, description=""),
     *                     @SWG\Property( property="real_payed_members", type="integer",example=0, description=""),
     *                     @SWG\Property( property="real_deposit", type="integer",example=0, description=""),
     *                     @SWG\Property( property="real_atv", type="integer",example=0, description="")
     *                 ),
     *                 @SWG\Property(
     *                     property="notice_data",
     *                     type="object",
     *                     @SWG\Property( property="wait_delivery_count", type="integer",example=140, description=""),
     *                     @SWG\Property( property="warning_goods_count", type="integer",example=46, description=""),
     *                     @SWG\Property( property="started_seckill_count", type="integer",example=0, description=""),
     *                     @SWG\Property( property="started_gtoups_count", type="integer",example=2, description=""),
     *                     @SWG\Property( property="aftersales_count", type="integer",example=52, description=""),
     *                     @SWG\Property( property="refund_errorlogs_count", type="integer",example=135, description="")
     *                 ),
     *                 @SWG\Property(
     *                     property="member_data",
     *                     type="string",
     *                     example="'20210121': {'newAddMember': 0,'vipMember': 0,'svipMember': 0},'20210122': {'newAddMember': 0,'vipMember': 0,'svipMember': 0},"
     *                 ),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function getDataList(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $merchantId = app('auth')->user()->get('merchant_id');
        $operatorType = app('auth')->user()->get('operator_type');

        $today = date('Ymd');

        if ($request->get('is_app')) {
            $shop_id = $request->get('shop_id');
            $result['today_data'] = $this->companysStatistics->getStatistics($companyId, $today, $shop_id);
            // 获取用户权限信息
            // 这一段代码在 CheckActivedMiddleWare::checkPermission 中有，重复
            $user = app('auth')->user();
            $operatorId = $user->get('operator_id');
            $employeeService = new EmployeeService();
            $shopmenuAliasName = $employeeService->getRoleDataPermission($companyId, $operatorId);
            if ($shopmenuAliasName) {
                $filter['alias_name|in'] = $shopmenuAliasName;
            }
            if ($user->get('operator_type') == 'distributor') {
                $filter['version'] = 3;
            } else {
                $filter['version'] = 1;
            }
            $shopMenuService = new ShopMenuService();
            $apis = $shopMenuService->getApisByShopmenuAliasName($filter);
            $result['apis'] = [
                'order' => 0,
                'aftersales' => 0,
                'order_ziti' => 0,
                'items' => 0,
                'users' => 0,
            ];
            if (in_array('order.list.get', $apis)) {
                $result['apis']['order'] = 1;
                $result['apis']['order_ziti'] = 1;
            }
            if (in_array('aftersales.list', $apis)) {
                $result['apis']['aftersales'] = 1;
            }
            if (in_array('goods.items.lists', $apis)) {
                $result['apis']['items'] = 1;
            }
            if (in_array('member.list', $apis)) {
                $result['apis']['users'] = 1;
            }
            return $this->response->array($result);
        }
        if ($operatorType == 'merchant') {
            $result['today_data'] = $this->companysStatistics->getMerchantStatistics($companyId, $merchantId, $today);
            $yesterday = date('Ymd', strtotime($today) - 3600 * 24);
            $result['yesterday_data'] = $this->companysStatistics->getMerchantStatistics($companyId, $merchantId, $yesterday);
            $result['notice_data'] = $this->companysStatistics->getMerchantNoticeStatisticsData($companyId, $merchantId);
        } else {
            $result['today_data'] = $this->companysStatistics->getStatistics($companyId, $today);

            $yesterday = date('Ymd', strtotime($today) - 3600 * 24);
            $result['yesterday_data'] = $this->companysStatistics->getStatistics($companyId, $yesterday);

            $result['notice_data'] = $this->companysStatistics->getNoticeStatisticsData($companyId);

            $result['member_data'] = $this->companysStatistics->getMemberStatistics($companyId, $today);
        }


        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/getNoticeStatistics",
     *     summary="获取商城总量统计(待处理订单数，待处理商品数，进行中的营销活动数)",
     *     tags={"企业"},
     *     description="获取商城总量统计(待处理订单数，待处理商品数，进行中的营销活动数)",
     *     operationId="getOrderStatusCount",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string", required=true),
     *     @SWG\Parameter( name="shop_id", in="query", description="店铺id", type="string", required=true),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="wait_delivery_count", type="string", example="人民币"),
     *                     @SWG\Property(property="warning_goods_count", type="string", example="￥"),
     *                     @SWG\Property(property="started_seckill_count", type="string", example="1"),
     *                     @SWG\Property(property="started_gtoups_count", type="string", example="1"),
     *                     @SWG\Property(property="aftersales_count", type="integer", example=1),
     *                     @SWG\Property(property="refund_errorlogs_count", type="integer", example=1),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function getOrderStatusCount(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $result = $this->companysStatistics->getNoticeStatisticsData($companyId);
        return $this->response->array($result);
    }
}
