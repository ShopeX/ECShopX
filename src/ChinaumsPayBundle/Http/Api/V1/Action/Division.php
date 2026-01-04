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

namespace ChinaumsPayBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as Controller;
use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;

use EspierBundle\Jobs\ExportFileJob;

use ChinaumsPayBundle\Services\ChinaumsPayDivisionService;


class Division extends Controller
{

    /**
     * @SWG\Get(
     *     path="/division/list",
     *     summary="银联商务分账单",
     *     tags={"银联商务"},
     *     description="银联商务分账单",
     *     operationId="getList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="order_id", in="query", description="订单号", required=true, type="string"),
     *     @SWG\Parameter( name="back_status", in="query", description="回盘状态 0:未处理、1:进行中、2:成功、3:部分成功、4:失败", required=true, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *                   @SWG\Property(property="delivery_list", type="array", description="失败日志",
     *                       @SWG\Items(
     *                          @SWG\Property(property="id", type="string", description="Id"),
     *                          @SWG\Property(property="company_id", type="string", description="公司id"),
     *                          @SWG\Property(property="type", type="string", description="类型 division:分账;transfer:划付;"),
     *                          @SWG\Property(property="order_id", type="string", description="订单号"),
     *                          @SWG\Property(property="distributor_id", type="string", description="店铺ID"),
     *                          @SWG\Property(property="total_fee", type="string", description="订单金额，以分为单位"),
     *                          @SWG\Property(property="commission_rate", type="string", description="收单手续费费率"),
     *                          @SWG\Property(property="division_fee", type="string", description="分账金额，以分为单位"),
     *                          @SWG\Property(property="backsucc_fee", type="string", description="回盘成功金额，以分为单位"),
     *                          @SWG\Property(property="rate_fee", type="string", description="银联商务该笔指令收取的业务处理费，以分为单位"),
     *                          @SWG\Property(property="back_status", type="string", description="回盘状态 0:未处理、1:进行中、2:成功、3:部分成功、4:失败"),
     *                          @SWG\Property(property="chinaumspay_id", type="integer", description="银商内部ID"),
     *                          @SWG\Property(property="back_status_msg", type="string", description="回盘状态描述"),
     *                          @SWG\Property(property="created", type="integer", description="创建时间"),
     *                          @SWG\Property(property="updated", type="integer", description="修改时间"),
     *                       ),
     *                   ),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/ChinaumsPayErrorResponse") ) )
     * )
     */
    public function getList(Request $request)
    {
        $filter = [];
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $input = $request->all('back_status', 'time_start_begin', 'time_start_end');

        if ($input['back_status'] != '') {
            $filter['back_status'] = $input['back_status'];
        }
        if (isset($input['time_start_begin'], $input['time_start_end']) && $input['time_start_begin'] && $input['time_start_end']) {
            $filter['create_time|gte'] = $input['time_start_begin'];
            $filter['create_time|lte'] = $input['time_start_end'];
        }
        if (app('auth')->user()->get('operator_type') == 'distributor') {
            $filter['distributor_id'] = app('auth')->user()->get('distributor_id');
        }
        $divisionService = new ChinaumsPayDivisionService();

        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 20);
        $orderBy = ['id' => 'DESC'];

        $data = $divisionService->lists($filter, '*', $page, $pageSize, $orderBy);
        return $this->response->array($data);
    }

    /**
     * @SWG\Get(
     *     path="/division/detail/list",
     *     summary="银联商务分账单明细",
     *     tags={"银联商务"},
     *     description="银联商务分账单明细",
     *     operationId="getDetailList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="division_id", in="query", description="指令ID", required=false, type="string"),
     *     @SWG\Parameter( name="order_id", in="query", description="订单号", required=false, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *                   @SWG\Property(property="delivery_list", type="array", description="失败日志",
     *                       @SWG\Items(
     *                          @SWG\Property(property="id", type="string", description="Id"),
     *                          @SWG\Property(property="company_id", type="string", description="公司id"),
     *                          @SWG\Property(property="division_id", type="string", description="指令ID"),
     *                          @SWG\Property(property="type", type="string", description="类型 division:分账;transfer:划付;"),
     *                          @SWG\Property(property="order_id", type="string", description="订单号"),
     *                          @SWG\Property(property="distributor_id", type="string", description="店铺ID"),
     *                          @SWG\Property(property="total_fee", type="string", description="订单金额，以分为单位"),
     *                          @SWG\Property(property="commission_rate", type="string", description="收单手续费费率"),
     *                          @SWG\Property(property="division_fee", type="string", description="分账金额，以分为单位"),
     *                          @SWG\Property(property="created", type="integer", description="创建时间"),
     *                          @SWG\Property(property="updated", type="integer", description="修改时间"),
     *                       ),
     *                   ),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/ChinaumsPayErrorResponse") ) )
     * )
     */
    public function getDetailList(Request $request)
    {
        $filter = [];
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $input = $request->all('division_id', 'order_id', 'time_start_begin', 'time_start_end');

        
        if ($input['order_id']) {
            $filter['order_id'] = $input['order_id'];
        }
        if ($input['division_id']) {
            $filter['division_id'] = $input['division_id'];
        }
        if (isset($input['time_start_begin'], $input['time_start_end']) && $input['time_start_begin'] && $input['time_start_end']) {
            $filter['create_time|gte'] = $input['time_start_begin'];
            $filter['create_time|lte'] = $input['time_start_end'];
        }
        if (app('auth')->user()->get('operator_type') == 'distributor') {
            $filter['distributor_id'] = app('auth')->user()->get('distributor_id');
        }
        $divisionService = new ChinaumsPayDivisionService();
        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 20);
        $orderBy = ['id' => 'DESC'];

        $data = $divisionService->getDetailList($filter, '*', $page, $pageSize, $orderBy);
        return $this->response->array($data);
    }

    /**
     * @SWG\Get(
     *     path="/division/errorlog/list",
     *     summary="银联商务分账失败日志",
     *     tags={"银联商务"},
     *     description="银联商务分账失败日志",
     *     operationId="errorlogList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="order_id", in="query", description="订单号", required=true, type="string"),
     *     @SWG\Parameter( name="status", in="query", description="处理状态 all:全部;is_resubmit:已处理;waiting:未处理;", required=true, type="string"),
     *     @SWG\Parameter( name="time_start_begin", in="query", description="创建时间-开始", required=true, type="string"),
     *     @SWG\Parameter( name="time_start_end", in="query", description="创建时间-结束", required=true, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *                   @SWG\Property(property="delivery_list", type="array", description="失败日志",
     *                       @SWG\Items(
     *                          @SWG\Property(property="company_id", type="string", description="公司id"),
     *                          @SWG\Property(property="order_id", type="string", description="订单号"),
     *                          @SWG\Property(property="division_id", type="string", description="分账流水ID"),
     *                          @SWG\Property(property="type", type="string", description="类型 division:分账;transfer:划付;"),
     *                          @SWG\Property(property="distributor_id", type="string", description="店铺ID"),
     *                          @SWG\Property(property="created", type="integer", description="创建时间"),
     *                          @SWG\Property(property="updated", type="integer", description="修改时间"),
     *                          @SWG\Property(property="total_fee", type="string", description="订单金额，以分为单位"),
     *                          @SWG\Property(property="commission_rate", type="string", description="收单手续费费率"),
     *                          @SWG\Property(property="division_fee", type="string", description="分账金额，以分为单位"),
     *                          @SWG\Property(property="status", type="integer", description="错误状态"),
     *                          @SWG\Property(property="error_code", type="string", description="错误原因码"),
     *                          @SWG\Property(property="error_desc", type="string", description="错误描述"),
     *                          @SWG\Property(property="is_resubmit", type="string", description="是否重新提交"),
     *                       ),
     *                   ),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/ChinaumsPayErrorResponse") ) )
     * )
     */
    public function errorlogList(Request $request)
    {
        $filter = [];

        $filter['company_id'] = app('auth')->user()->get('company_id');
        $input = $request->all('status', 'order_id', 'time_start_begin', 'time_start_end');

        if ($input['status'] == 'waiting') {
            $filter['is_resubmit'] = '2';
        } elseif ($input['status'] == 'is_resubmit') {
            $filter['is_resubmit'] = '1';
        } elseif ($input['status'] == 'not') {
            $filter['is_resubmit'] = '0';
        }
        if ($input['order_id']) {
            $filter['order_id'] = $input['order_id'];
        }
        if (isset($input['time_start_begin'], $input['time_start_end']) && $input['time_start_begin'] && $input['time_start_end']) {
            $filter['create_time|gte'] = $input['time_start_begin'];
            $filter['create_time|lte'] = $input['time_start_end'];
        }
        if (app('auth')->user()->get('operator_type') == 'distributor') {
            $filter['distributor_id'] = app('auth')->user()->get('distributor_id');
        }
        $divisionService = new ChinaumsPayDivisionService();

        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 20);
        $orderBy = ['id' => 'DESC'];
        $data = $divisionService->getErrorlogList($filter, '*', $page, $pageSize, $orderBy);

        return $this->response->array($data);
    }

    /**
     * @SWG\Put(
     *     path="/division/errorlog/resubmit/{id}",
     *     summary="银联商务分账失败重试",
     *     tags={"银联商务"},
     *     description="银联商务分账失败重试",
     *     operationId="errrorlogResubmit",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="id", in="path", description="失败记录ID", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="status", type="boolean", example="true"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/ChinaumsPayErrorResponse") ) )
     * )
     */
    public function errrorlogResubmit($id)
    {
        $divisionService = new ChinaumsPayDivisionService();
        $filter = [
            'company_id' => app('auth')->user()->get('company_id'),
            'id' => $id,
        ];
        $info = $divisionService->getErrorlogInfo($filter);
        if (!$info) {
            throw new ResourceException('查询数据失败，请稍后重试');
        }
        if ($info['is_resubmit'] != $divisionService::IS_RESUBMIT_NOT) {
            throw new ResourceException('不需要重新提交，请稍后重试');
        }
        $updateData = ['is_resubmit' => $divisionService::IS_RESUBMIT_WAITING];
        $result = $divisionService->updateErrorLog($filter, $updateData);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Get(
     *     path="/division/exportdata",
     *     summary="导出分账单列表",
     *     tags={"银联商务"},
     *     description="导出银联商务分账单列表",
     *     operationId="exportDivisionData",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="shop_id", in="query", description="店铺名称", type="string"),
     *     @SWG\Parameter( name="mobile", in="query", description="服务人员手机号", type="string"),
     *     @SWG\Parameter( name="name", in="query", description="服务人员名称", type="string"),
     *     @SWG\Parameter( name="time_start_begin", in="query", description="时间筛选开始时间", type="string"),
     *     @SWG\Parameter( name="time_start_end", in="query", description="时间筛选结束时间", type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                     @SWG\Property(property="status", type="string"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/ChinaumsPayErrorResponse") ) )
     * )
     */
    public function exportDivisionData(Request $request)
    {
        $filter = [];
        $type = 'chinaums_division';

        $filter['company_id'] = app('auth')->user()->get('company_id');
        $input = $request->all('back_status', 'time_start_begin', 'time_start_end');

        if ($input['back_status'] != '') {
            $filter['back_status'] = $input['back_status'];
        }
        if (isset($input['time_start_begin'], $input['time_start_end']) && $input['time_start_begin'] && $input['time_start_end']) {
            $filter['create_time|gte'] = $input['time_start_begin'];
            $filter['create_time|lte'] = $input['time_start_end'];
        }
        $divisionService = new ChinaumsPayDivisionService();

        if (app('auth')->user()->get('operator_type') == 'distributor') {
            $filter['distributor_id'] = app('auth')->user()->get('distributor_id');
        }

        $count = $divisionService->count($filter);

        //存储导出操作账号者
        $operator_id = app('auth')->user()->get('operator_id');
        return $this->exportData($count, $type, $filter, $operator_id);
    }

    /**
     * @SWG\Get(
     *     path="/divisiondetail/exportdata",
     *     summary="导出分账单明细列表",
     *     tags={"银联商务"},
     *     description="导出银联商务分账单明细列表",
     *     operationId="exportTradeData",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="shop_id", in="query", description="店铺名称", type="string"),
     *     @SWG\Parameter( name="mobile", in="query", description="服务人员手机号", type="string"),
     *     @SWG\Parameter( name="name", in="query", description="服务人员名称", type="string"),
     *     @SWG\Parameter( name="time_start_begin", in="query", description="时间筛选开始时间", type="string"),
     *     @SWG\Parameter( name="time_start_end", in="query", description="时间筛选结束时间", type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                     @SWG\Property(property="status", type="string"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/ChinaumsPayErrorResponse") ) )
     * )
     */
    public function exportDivisionDetailData(Request $request)
    {
        $type = 'chinaums_division_detail';

        $filter = [];
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $input = $request->all('division_id', 'order_id', 'time_start_begin', 'time_start_end');

        
        if ($input['order_id']) {
            $filter['order_id'] = $input['order_id'];
        }
        if ($input['division_id']) {
            $filter['division_id'] = $input['division_id'];
        }
        if (isset($input['time_start_begin'], $input['time_start_end']) && $input['time_start_begin'] && $input['time_start_end']) {
            $filter['create_time|gte'] = $input['time_start_begin'];
            $filter['create_time|lte'] = $input['time_start_end'];
        }
        if (app('auth')->user()->get('operator_type') == 'distributor') {
            $filter['distributor_id'] = app('auth')->user()->get('distributor_id');
        }

        $divisionService = new ChinaumsPayDivisionService();
        $count = $divisionService->getDetailCount($filter);

        //存储导出操作账号者
        $operator_id = app('auth')->user()->get('operator_id');
        return $this->exportData($count, $type, $filter, $operator_id);
    }

    private function exportData($count, $type, $filter, $operator_id = 0)
    {
        if ($count <= 0) {
            throw new resourceexception('导出有误,暂无数据导出');
        }
        $gotoJob = (new ExportFileJob($type, $filter['company_id'], $filter, $operator_id))->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        $result['status'] = true;
        return response()->json($result);
    }

    
}
