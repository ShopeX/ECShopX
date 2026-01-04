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

use SalespersonBundle\Services\SalespersonTaskService;
use App\Http\Controllers\Controller as Controller;
use Illuminate\Http\Request;

class SalespersonTaskController extends Controller
{
    /**
     * @SWG\Get(
     *     path="/admin/wxapp/salesperson/task",
     *     summary="获取导购任务列表",
     *     tags={"导购"},
     *     description="获取导购任务列表",
     *     operationId="lists",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="status", in="query", description="导购任务状态", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="array",
     *              @SWG\Items( type="object",
     *                  @SWG\Property( property="task_id", type="string", example="4", description="ID"),
     *                  @SWG\Property( property="task_name", type="string", example="测试任务", description="任务名称"),
     *                  @SWG\Property( property="task_type", type="string", example="1", description="任务类型 1 转发分享 2 获取新客 3 客户下单 4 会员福利"),
     *                  @SWG\Property( property="task_quota", type="string", example="3", description="任务指标"),
     *                  @SWG\Property( property="times", type="string", example="0", description="任务次数"),
     *               ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse") ) )
     * )
     */
    public function lists(Request $request)
    {
        $authInfo = $this->auth->user();
        $salespersonTaskService = new SalespersonTaskService();
        $companyId = $authInfo['company_id'];
        $distributorId = $authInfo['distributor_id'];
        $salespersonId = $authInfo['salesperson_id'];
        $status = $request->input('status', 'ongoing');
        $result = $salespersonTaskService->getDistributorTaskList($companyId, $distributorId, $salespersonId, $status);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/admin/wxapp/salesperson/task/{taskId}",
     *     summary="获取导购任务列表详情",
     *     tags={"导购"},
     *     description="获取导购任务列表详情",
     *     operationId="info",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="taskId", in="path", description="任务ID", required=true, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="task_id", type="string", example="4", description="ID"),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                  @SWG\Property( property="start_time", type="string", example="1609430400", description="开始时间"),
     *                  @SWG\Property( property="end_time", type="string", example="1614268800", description="结束时间"),
     *                  @SWG\Property( property="task_name", type="string", example="测试任务", description="任务名称"),
     *                  @SWG\Property( property="task_type", type="string", example="1", description="任务类型 1 转发分享 2 获取新客 3 客户下单 4 会员福利"),
     *                  @SWG\Property( property="task_quota", type="string", example="3", description="任务指标"),
     *                  @SWG\Property( property="task_content", type="string", example="", description="任务内容"),
     *                  @SWG\Property( property="use_all_distributor", type="string", example="1", description="是否是全部店铺"),
     *                  @SWG\Property( property="disabled", type="string", example="ACTIVE", description="任务指标"),
     *                  @SWG\Property( property="created", type="string", example="1610940599", description="创建时间戳"),
     *                  @SWG\Property( property="updated", type="string", example="1610940599", description="更新时间戳"),
     *                  @SWG\Property( property="pics", type="string", example="[https://bbctest.aixue7.com/image/..., https://bbctest.aixue7.com/image/...]", description="任务素材图片(DC2Type:json_array)"),
     *                  @SWG\Property( property="times", type="string", example="0", description="任务次数"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse") ) )
     * )
     */
    public function info($taskId, Request $request)
    {
        $authInfo = $this->auth->user();
        $salespersonTaskService = new SalespersonTaskService();
        $companyId = $authInfo['company_id'];
        $distributorId = $authInfo['distributor_id'];
        $salespersonId = $authInfo['salesperson_id'];
        //$status = $request->input('status', 'ongoing');
        $result = $salespersonTaskService->getDistributorTaskInfo($companyId, $taskId, $distributorId, $salespersonId);
        return $this->response->array($result);
    }
}
