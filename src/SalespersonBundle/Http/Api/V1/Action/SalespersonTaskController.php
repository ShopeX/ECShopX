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

namespace SalespersonBundle\Http\Api\V1\Action;

use SalespersonBundle\Services\SalespersonTaskService;
use App\Http\Controllers\Controller as Controller;
use Illuminate\Http\Request;

class SalespersonTaskController extends Controller
{
    /**
     * @SWG\Get(
     *     path="/salesperson/task",
     *     summary="获取导购任务列表",
     *     tags={"导购"},
     *     description="获取导购任务列表",
     *     operationId="lists",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="status", in="query", description="导购任务状态", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="分页页数", required=true, type="integer"),
     *     @SWG\Parameter( name="page_size", in="query", description="分页条数", required=true, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="4", description="自行更改字段描述"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="task_id", type="string", example="4", description="ID"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="start_time", type="string", example="1609430400", description="开始时间"),
     *                          @SWG\Property( property="end_time", type="string", example="1614268800", description="结束时间"),
     *                          @SWG\Property( property="task_name", type="string", example="测试任务", description="任务名称"),
     *                          @SWG\Property( property="task_type", type="string", example="1", description="任务类型 1 转发分享 2 获取新客 3 客户下单 4 会员福利"),
     *                          @SWG\Property( property="task_quota", type="string", example="3", description="任务指标"),
     *                          @SWG\Property( property="task_content", type="string", example="", description="任务内容"),
     *                          @SWG\Property( property="use_all_distributor", type="string", example="1", description="是否是全部店铺"),
     *                          @SWG\Property( property="disabled", type="string", example="ACTIVE", description="是否终止"),
     *                          @SWG\Property( property="created", type="string", example="1610940599", description="created"),
     *                          @SWG\Property( property="updated", type="string", example="1610940599", description="updated"),
     *                          @SWG\Property( property="pics", type="string", example="[https://bbctest.aixue7.com/image/..., https://bbctest.aixue7.com/image/...]", description="素材图片地址(DC2Type:json_array)"),
     *                          @SWG\Property( property="status", type="string", example="ongoing", description="status"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse") ) )
     * )
     */
    public function lists(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $status = $request->input('status');
        $salespersonTaskService = new SalespersonTaskService();
        $pageSize = $request->input('page_size', 10);
        $page = $request->input('page', 1);
        $result = $salespersonTaskService->getTaskList($companyId, $status, $page, $pageSize);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/salesperson/task/{taskId}",
     *     summary="获取导购任务信息",
     *     tags={"导购"},
     *     description="获取导购任务信息",
     *     operationId="info",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="taskId", in="path", description="导购商品id", required=true, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="task_id", type="integer", example="2", description="ID"),
     *                  @SWG\Property( property="company_id", type="integer", example="1", description="公司id"),
     *                  @SWG\Property( property="start_time", type="string", example="1604246400", description="开始时间"),
     *                  @SWG\Property( property="end_time", type="string", example="1606665600", description="结束时间"),
     *                  @SWG\Property( property="task_name", type="string", example="生日关怀", description="任务名称"),
     *                  @SWG\Property( property="task_type", type="string", example="4", description="任务类型 1 转发分享 2 获取新客 3 客户下单 4 会员福利"),
     *                  @SWG\Property( property="task_quota", type="string", example="4", description="任务指标"),
     *                  @SWG\Property( property="pics", type="array",
     *                      @SWG\Items( type="string", example="undefined", description="素材图片url地址"),
     *                  ),
     *                  @SWG\Property( property="task_content", type="string", example="看看", description="任务内容"),
     *                  @SWG\Property( property="use_all_distributor", type="string", example="true", description="是否是全部店铺"),
     *                  @SWG\Property( property="disabled", type="string", example="ACTIVE", description="任务指标"),
     *                  @SWG\Property( property="created", type="string", example="1602833117", description="created"),
     *                  @SWG\Property( property="updated", type="string", example="1604918109", description="updated"),
     *                  @SWG\Property( property="distributor_info", type="array",
     *                      @SWG\Items( type="string", example="undefined", description="门店信息"),
     *                  ),
     *                  @SWG\Property( property="distributor_id", type="array",
     *                      @SWG\Items( type="string", example="undefined", description="门店ID"),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse") ) )
     * )
     */
    public function info($taskId)
    {
        $companyId = app('auth')->user()->get('company_id');
        $salespersonTaskService = new SalespersonTaskService();
        $result = $salespersonTaskService->getTaskInfo($taskId, $companyId);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/salesperson/task",
     *     summary="创建导购任务",
     *     tags={"导购"},
     *     description="创建导购任务",
     *     operationId="create",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="start_time", in="formData", description="开始时间", required=true, type="string"),
     *     @SWG\Parameter( name="end_time", in="formData", description="结束时间", required=true, type="string"),
     *     @SWG\Parameter( name="task_name", in="formData", description="导购任务名称", required=true, type="string"),
     *     @SWG\Parameter( name="task_type", in="formData", description="导购任务类型", required=true, type="string"),
     *     @SWG\Parameter( name="task_quota", in="formData", description="导购任务完成指标", required=true, type="string"),
     *     @SWG\Parameter( name="task_content", in="formData", description="导购任务内容", required=true, type="string"),
     *     @SWG\Parameter( name="distributor", in="formData", description="导购关联店铺", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="创建结果"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse") ) )
     * )
     */
    public function create(Request $request)
    {
        $params = $request->all('start_time', 'end_time', 'task_name', 'task_type', 'task_quota', 'pics', 'task_content', 'use_all_distributor', 'distributor_id');
        $params['company_id'] = app('auth')->user()->get('company_id');
        $salespersonTaskService = new SalespersonTaskService();
        $result = $salespersonTaskService->createTask($params);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Put(
     *     path="/salesperson/task/{taskId}",
     *     summary="修改导购任务",
     *     tags={"导购"},
     *     description="修改导购任务",
     *     operationId="update",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="taskId", in="path", description="任务ID", required=true, type="integer"),
     *     @SWG\Parameter( name="start_time", in="formData", description="开始时间", required=true, type="string"),
     *     @SWG\Parameter( name="end_time", in="formData", description="结束时间", required=true, type="string"),
     *     @SWG\Parameter( name="task_name", in="formData", description="导购任务名称", required=true, type="string"),
     *     @SWG\Parameter( name="task_type", in="formData", description="导购任务类型", required=true, type="string"),
     *     @SWG\Parameter( name="task_quota", in="formData", description="导购任务完成指标", required=true, type="string"),
     *     @SWG\Parameter( name="task_content", in="formData", description="导购任务内容", required=true, type="string"),
     *     @SWG\Parameter( name="distributor", in="formData", description="导购关联店铺", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="修改结果"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse") ) )
     * )
     */
    public function update($taskId, Request $request)
    {
        $params = $request->all('start_time', 'end_time', 'task_name', 'task_type', 'task_quota', 'pics', 'task_content', 'use_all_distributor', 'distributor_id');
        $params['company_id'] = app('auth')->user()->get('company_id');
        $salespersonTaskService = new SalespersonTaskService();
        $result = $salespersonTaskService->updateTask($taskId, $params);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Delete(
     *     path="/salesperson/task/{taskId}",
     *     summary="取消导购任务",
     *     tags={"导购"},
     *     description="取消导购任务",
     *     operationId="cancel",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="taskId", in="path", description="导购任务id", required=true, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="取消结果"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse") ) )
     * )
     */
    public function cancel($taskId)
    {
        $companyId = app('auth')->user()->get('company_id');
        $salespersonTaskService = new SalespersonTaskService();
        $result = $salespersonTaskService->cancelTask($taskId, $companyId);
        return $this->response->array(['status' => true]);
    }


    /**
     * @SWG\Get(
     *     path="/salesperson/task/statistics",
     *     summary="获取导购任务统计数据",
     *     tags={"导购"},
     *     description="获取导购任务统计数据",
     *     operationId="statistics",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="task_id", in="query", description="导购任务id", required=true, type="integer"),
     *     @SWG\Parameter( name="page", in="query", description="分页页数", required=true, type="integer"),
     *     @SWG\Parameter( name="page_size", in="query", description="分页条数", required=true, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="1", description="自行更改字段描述"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="id", type="string", example="1", description="ID"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="task_id", type="string", example="1", description="任务ID"),
     *                          @SWG\Property( property="distributor_id", type="string", example="1", description="店铺id"),
     *                          @SWG\Property( property="salesperson_id", type="string", example="13", description="导购id"),
     *                          @SWG\Property( property="times", type="string", example="10", description="次数"),
     *                          @SWG\Property( property="salesperson_name", type="string", example="陈慧", description="导购姓名"),
     *                          @SWG\Property( property="task_quota", type="string", example="3", description="任务指标"),
     *                          @SWG\Property( property="percentage", type="string", example="334%", description="完成占比"),
     *                       ),
     *                  ),
     *                  @SWG\Property( property="task", type="object",
     *                          @SWG\Property( property="task_id", type="string", example="1", description="ID"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="start_time", type="string", example="1604160000", description="开始时间"),
     *                          @SWG\Property( property="end_time", type="string", example="1606665600", description="结束时间"),
     *                          @SWG\Property( property="task_name", type="string", example="客户拉新", description="任务名称"),
     *                          @SWG\Property( property="task_type", type="string", example="2", description="任务类型 1 转发分享 2 获取新客 3 客户下单 4 会员福利"),
     *                          @SWG\Property( property="task_quota", type="string", example="3", description="任务指标"),
     *                          @SWG\Property( property="pics", type="array",
     *                              @SWG\Items( type="string", example="undefined", description="任务素材图片url"),
     *                          ),
     *                          @SWG\Property( property="task_content", type="string", example="给会员发券", description="任务内容"),
     *                          @SWG\Property( property="use_all_distributor", type="string", example="true", description="是否是全部店铺"),
     *                          @SWG\Property( property="disabled", type="string", example="ACTIVE", description="任务指标"),
     *                          @SWG\Property( property="created", type="string", example="1600931707", description="创建时间"),
     *                          @SWG\Property( property="updated", type="string", example="1604918196", description="修改时间"),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse") ) )
     * )
     */
    public function statistics(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $taskId = $request->input('task_id');
        $pageSize = $request->input('page_size', 10);
        $page = $request->input('page', 1);
        $salespersonTaskService = new SalespersonTaskService();
        $result = $salespersonTaskService->getDistributorTaskListByTaskId($companyId, $taskId, $page, $pageSize);
        return $this->response->array($result);
    }
}
