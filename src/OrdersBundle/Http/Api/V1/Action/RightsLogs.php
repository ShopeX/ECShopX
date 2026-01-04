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

use App\Http\Controllers\Controller as Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Dingo\Api\Exception\ResourceException;
use OrdersBundle\Services\Rights\LogsService;

class RightsLogs extends Controller
{
    /**
     * @SWG\Get(
     *     path="/rights/log",
     *     summary="获取权益核销列表",
     *     tags={"订单"},
     *     description="获取权益核销列表",
     *     operationId="getLogsList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="当前页数", type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页数量", type="string"),
     *     @SWG\Parameter( name="shop_id", in="query", description="店铺名称", type="string"),
     *     @SWG\Parameter( name="mobile", in="query", description="服务人员手机号", type="string"),
     *     @SWG\Parameter( name="name", in="query", description="服务人员名称", type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="total_count", type="integer", example="7", description=""),
     *               @SWG\Property(property="list", type="array", description="",
     *                 @SWG\Items(
     *                           @SWG\Property(property="rights_log_id", type="string", example="7", description="权益日志ID"),
     *                           @SWG\Property(property="rights_id", type="string", example="231", description="权益ID"),
     *                           @SWG\Property(property="company_id", type="string", example="1", description="公司ID"),
     *                           @SWG\Property(property="user_id", type="string", example="20133", description="用户id"),
     *                           @SWG\Property(property="shop_id", type="string", example="26", description="门店ID"),
     *                           @SWG\Property(property="rights_name", type="string", example="次卡-3次", description="权益标题"),
     *                           @SWG\Property(property="rights_subname", type="string", example="物料2", description="权益子标题"),
     *                           @SWG\Property(property="consum_num", type="string", example="1", description="消耗次数"),
     *                           @SWG\Property(property="attendant", type="string", example="啦啦啦", description="服务员"),
     *                           @SWG\Property(property="salesperson_mobile", type="string", example="15618429140", description="核销员手机号"),
     *                           @SWG\Property(property="end_time", type="string", example="1592386130", description="权益结束时间"),
     *                           @SWG\Property(property="created", type="string", example="1592386130", description=""),
     *                           @SWG\Property(property="shop_name", type="string", example="断桥残雪", description=""),
     *                           @SWG\Property(property="name", type="string", example="小号", description=""),
     *                           @SWG\Property(property="user_name", type="string", example="未知", description=""),
     *                           @SWG\Property(property="user_sex", type="string", example="未知", description=""),
     *                           @SWG\Property(property="user_mobile", type="string", example="未知", description=""),
     *                 ),
     *               ),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function getLogsList(Request $request)
    {
        $rightsService = new LogsService();
        $filter = array();
        $orderBy = ['created' => 'DESC'];

        $filter['company_id'] = app('auth')->user()->get('company_id');

        $pageSize = $request->input('pageSize', 20);
        $page = $request->input('page', 1);
        $params = $request->all('mobile', 'name', 'shop_id', 'time_start_begin', 'time_start_end', 'user_id');
        if (intval($params['mobile'])) {
            $filter['salesperson_mobile'] = intval($params['mobile']);
        }
        if (intval($params['name'])) {
            $filter['name'] = intval($params['name']);
        }

        if (intval($params['user_id'])) {
            $filter['user_id'] = intval($params['user_id']);
        }

        if (intval($params['shop_id'])) {
            $filter['shop_id'] = intval($params['shop_id']);
        }

        if ($params['time_start_begin']) {
            if (!is_numeric($params['time_start_begin'])) {
                throw new resourceexception('导出有误，日期时间参数有误');
            }
            $filter['time_start_begin'] = $params['time_start_begin'];
        }
        if ($params['time_start_end']) {
            if (!is_numeric($params['time_start_end'])) {
                throw new resourceexception('导出有误，日期时间参数有误');
            }
            $filter['time_start_end'] = $params['time_start_end'];
        }

        $listdata = $rightsService->getList($filter, $page, $pageSize, $orderBy);
        // 是否有权限查看加密数据
        $datapassBlock = $request->get('x-datapass-block');
        if ($listdata['list']) {
            foreach ($listdata['list'] as $key => $value) {
                if ($datapassBlock) {
                    $listdata['list'][$key]['salesperson_mobile'] = data_masking('mobile', (string) $value['salesperson_mobile']);
                    $value['user_name'] != '未知' and $listdata['list'][$key]['user_name'] = data_masking('truename', (string) $value['user_name']);
                    $value['user_mobile'] != '未知' and $listdata['list'][$key]['user_mobile'] = data_masking('mobile', (string) $value['user_mobile']);
                }
            }
        }
        return $this->response->array($listdata);
    }
}
