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

namespace SalespersonBundle\Http\AdminApi\V1\Action;

use App\Http\Controllers\Controller as Controller;
use SalespersonBundle\Entities\SalespersonStatistics;
use SalespersonBundle\Services\SalespersonService;
use Illuminate\Http\Request;
use PromotionsBundle\Entities\SalespersonActiveArticleStatistics;

class StatisticsController extends Controller
{
    private $typeList = [
        1 => '订单销售额',
        2 => '订单数',
        3 => '会员数',
//        4 => '拉新分润',
        5 => '推广提成',
        6 => '活动转发数',
        7 => '优惠券发放数',
    ];

    /**
     * @SWG\Get(
     *     path="/admin/wxapp/statistics",
     *     summary="导购端统计数据",
     *     tags={"导购"},
     *     description="导购端统计数据",
     *     operationId="lists",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="type", in="query", description="N日数据， 0是今天", type="integer", default="1"),
     *     @SWG\Parameter( name="date", in="query", description="other其他数据，week7日内，month30天内", type="integer"),
     *     @SWG\Parameter( name="start_time", in="query", description="date为other的时候，指定月份2020年10月份 20201001", type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="name", type="string", example="订单销售额(2021-01-03至2021-02-01数据)", description="统计数据名称"),
     *                  @SWG\Property( property="lists", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="date", type="string", example="2021-02-01", description="统计日期 Ymd"),
     *                          @SWG\Property( property="count", type="string", example="101.00", description="销售额"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse")))
     * )
     */
    public function lists(Request $request)
    {
        $type = $request->input('type', 1);
        $date = $request->input('date', 'other');
        if ('other' == $date) {
            $time = $request->input('start_time');
            $j = date("t", strtotime($time)); //获取月份天数
            $startTime = date('Ymd', strtotime($time)); //每隔一天赋值给数组
            $endTime = date('Ymd', strtotime($time) + $j * 86400 - 1); //每隔一天赋值给数组
        } else {
            switch ($date) {
                case 'week':
                    $startTime = date('Ymd', strtotime('-6 days'));
                    $endTime = date('Ymd', time());
                    break;
                case 'month':
                    $startTime = date('Ymd', strtotime('-29 days'));
                    $endTime = date('Ymd', time());
                    break;
            }
        }

        $authInfo = $this->auth->user();
        $filter = [
            'salesperson_id' => $authInfo['salesperson_id'],
            'statistic_title' => 'normal',
            'statistic_type' => 'orderPayFee',
            'add_date|gte' => $startTime,
            'add_date|lte' => $endTime,
        ];
        $listsTemp = [];

//        1 => '订单销售额',
//        2 => '订单数',
//        3 => '会员数',
//        4 => '拉新分润',
//        5 => '推广提成',
//        6 => '活动转发数',
//        7 => '优惠券发放数',
        $salespersonStatistics = app('registry')->getManager('default')->getRepository(SalespersonStatistics::class);
        $salespersonService = new SalespersonService();
        $start = strtotime(date('Y-m-d', strtotime('-' . (-1) . 'day')));
        $end = strtotime(date('Y-m-d')) + 24 * 3600 - 1;
        $todayData = $salespersonService->getSalespersonCountData($authInfo, $start, $end);
        switch ($type) {
            case 1:
                $filter['statistic_title'] = 'orderPayFee';
                $filter['statistic_type'] = 'normal';
                $listsTemp = $salespersonStatistics->getLists($filter);
                $today = $todayData['orderPayFee'];
                break;
            case 2:
                $filter['statistic_title'] = 'orderPayNum';
                $filter['statistic_type'] = 'normal';
                $listsTemp = $salespersonStatistics->getLists($filter);
                $today = $todayData['orderPayNum'];
                break;
            case 3:
                $filter['statistic_title'] = 'newAddMember';
                $filter['statistic_type'] = 'normal';
                $listsTemp = $salespersonStatistics->getLists($filter);
                $today = $todayData['newUserNum'];
                break;
            case 4:
                $filter['statistic_title'] = 'newGuestDivided';
                $filter['statistic_type'] = 'member';
                $listsTemp = $salespersonStatistics->getLists($filter);
                $today = $todayData['newGuestDivided'];
                break;
            case 5:
                $filter['statistic_title'] = 'salesCommission';
                $filter['statistic_type'] = 'member';
                $listsTemp = $salespersonStatistics->getLists($filter);
                $today = $todayData['salesCommission'];
                break;
            case 6:
                $filter = [
                    'salesperson_id' => $authInfo['salesperson_id'],
                    'add_date|gte' => $startTime,
                    'add_date|lte' => $endTime,
                ];
                $salespersonActiveArticleStatistics = app('registry')->getManager('default')->getRepository(SalespersonActiveArticleStatistics::class);
                $listsTemp = $salespersonActiveArticleStatistics->getLists($filter);
                $today = $todayData['activityForward'];
                break;
            case 7:
                $filter['statistic_title'] = 'salespersonGiveCoupons';
                $filter['statistic_type'] = 'member';
                $listsTemp = $salespersonStatistics->getLists($filter);
                $today = $todayData['sendCouponsNum'];
                break;
        }
        $lists = [];
        foreach ($listsTemp as $v) {
            if ($v['data_value'] ?? 0 && $v['add_date'] ?? 0) {
                $lists[] = [
                    'date' => date('Y-m-d', strtotime($v['add_date'])),
                    'count' => in_array($type, [1, 4, 5]) ? bcdiv($v['data_value'], 100, 2) : $v['data_value'],
                ];
            }
        }
        if ('other' != $date || $time == date('Y-m')) {
            $lists[] = [
                'date' => date('Y-m-d'),
                'count' => in_array($type, [1, 4, 5]) ? bcdiv($today, 100, 2) : $today,
            ];
        }
        $result = [
            'name' => $this->getType($type) . '(' . date('Y-m-d', strtotime($startTime)) . '至' . date('Y-m-d', strtotime($endTime)) . '数据)',
            'lists' => $lists
        ];
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/admin/wxapp/statistics/typelist",
     *     summary="导购端统计类型",
     *     tags={"导购"},
     *     description="导购端统计类型",
     *     operationId="typeList",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="array",
     *              @SWG\Items( type="object",
     *                  @SWG\Property( property="id", type="string", example="1", description="ID"),
     *                  @SWG\Property( property="label", type="string", example="订单销售额", description="统计类型"),
     *               ),
     *          ),
     *     )),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse")))
     * )
     */
    public function typeList()
    {
        $list = [];
        foreach ($this->typeList as $k => $v) {
            $list[] = [
                'id' => $k,
                'label' => $v,
            ];
        }
        return $this->response->array($list);
    }

    private function getType($type = 1)
    {
        return $this->typeList[$type] ?? 0;
    }
}
