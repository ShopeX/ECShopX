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

namespace AliyunsmsBundle\Http\Api\V1\Action;

use AliyunsmsBundle\Services\TaskService;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;


class Task extends Controller
{
    /**
     * @SWG\Post(
     *     path="/aliyunsms/task/add",
     *     summary="添加短信任务",
     *     tags={"阿里短信"},
     *     description="添加短信任务",
     *     operationId="addTask",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="task_name", in="formData", description="任务名称", required=true, type="string"),
     *     @SWG\Parameter( name="sign_id", in="formData", description="签名id", required=true, type="integer"),
     *     @SWG\Parameter( name="template_id", in="formData", description="模板id", required=true, type="integer"),
     *     @SWG\Parameter( name="send_at", in="formData", description="发送时间", required=false, type="string"),
     *     @SWG\Parameter( name="user_id[]", in="formData", description="会员id", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="status", type="boolean", description="状态", example=true),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AliyunsmsErrorResponse") ) )
     * )
     */
    public function addTask(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $params = $request->all();
        $rules = [
            'task_name' => ['required', '任务名称必填'],
            'sign_id' => ['required|integer|min:1', '签名必填'],
            'template_id' => ['required|integer|min:1', '模板必填'],
        ];
        $params['task_name'] = trim($params['task_name']);
        if(!isset($params['send_at']) || !$params['send_at']) {
            $params['send_at'] = time();
        } else {
            $params['send_at'] = substr($params['send_at'],0,strlen($params['send_at'])-3);
        }
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $params['company_id'] = $companyId;
        $taskService = new TaskService();
        $taskService->addTask($params);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Post(
     *     path="/aliyunsms/task/modify",
     *     summary="编辑短信任务",
     *     tags={"阿里短信"},
     *     description="编辑短信任务",
     *     operationId="addTask",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="id", in="formData", description="任务id", required=true, type="integer"),
     *     @SWG\Parameter( name="task_name", in="formData", description="任务名称", required=true, type="string"),
     *     @SWG\Parameter( name="sign_id", in="formData", description="签名id", required=true, type="integer"),
     *     @SWG\Parameter( name="template_id", in="formData", description="模板id", required=true, type="integer"),
     *     @SWG\Parameter( name="send_at", in="formData", description="发送时间", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="status", type="boolean", description="状态", example=true),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AliyunsmsErrorResponse") ) )
     * )
     */
    public function modifyTask(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $params = $request->all();
        $rules = [
            'id' => ['required', 'id必填'],
            'task_name' => ['required', '任务名称必填'],
            'sign_id' => ['required|integer|min:1', '签名必填'],
            'template_id' => ['required|integer|min:1', '模板必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $params['task_name'] = trim($params['task_name']);
        if(!isset($params['send_at']) || !$params['send_at']) {
            $params['send_at'] = time();
        } else {
            $params['send_at'] = substr($params['send_at'],0,strlen($params['send_at'])-3);
        }
        $params['company_id'] = $companyId;
        $taskService = new TaskService();
        $taskService->modifyTask($params);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Post(
     *     path="/aliyunsms/task/revoke",
     *     summary="撤销短信任务",
     *     tags={"阿里短信"},
     *     description="撤销短信任务",
     *     operationId="addTask",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="id", in="formData", description="任务id", required=true, type="integer"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="status", type="boolean", description="状态", example=true),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AliyunsmsErrorResponse") ) )
     * )
     */
    public function revokeTask(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $params = $request->all();
        $rules = [
            'id' => ['required', 'id必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $params['company_id'] = $companyId;
        $taskService = new TaskService();
        $taskService->revokeTask($params);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/aliyunsms/task/list",
     *     summary="群发短信任务列表",
     *     tags={"阿里短信"},
     *     description="群发短信任务列表",
     *     operationId="getList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="status", in="query", description="发送状态:1-等待中;2-群发成功;3-群发失败;4-已撤销", required=false, type="string"),
     *     @SWG\Parameter( name="task_name", in="query", description="任务名称", required=false, type="string"),
     *     @SWG\Parameter( name="time_start", in="query", description="开始时间", required=false, type="string"),
     *     @SWG\Parameter( name="template_name", in="query", description="模板名称", required=false, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="签名名称", required=false, type="integer"),
     *     @SWG\Parameter( name="page_size", in="query", description="签名名称", required=false, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="count", type="string", example="2", description="自行更改字段描述"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="id", type="string", example="", description=""),
     *                          @SWG\Property( property="task_name", type="string", example="", description="任务名称"),
     *                          @SWG\Property( property="send_at", type="string", example="", description="定时发送时间"),
     *                          @SWG\Property( property="template_name", type="string", example="", description="短信模板"),
     *                          @SWG\Property( property="created", type="string", example="", description="创建时间"),
     *                          @SWG\Property( property="status", type="string", example="", description="任务状态"),
     *                          @SWG\Property( property="total_num", type="integer", example="", description="号码数"),
     *                          @SWG\Property( property="failed_num", type="integer", example="", description="失败号码数"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AliyunsmsErrorResponse") ) )
     * )
     */
    public function getList(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $params = $request->all();
        $page = $params['page'] ?? 1;
        $pageSize = $params['pagSize'] ?? 10;
        $taskService = new TaskService();
        $filter = [];
        if($params['task_name'] ?? 0) {
            $filter['task_name|contains'] = $params['task_name'];
        }
        if($params['template_name'] ?? 0) {
            $filter['template_name|contains'] = $params['template_name'];
        }
        if(isset($params['status'])) {
            $filter['status'] = $params['status'];
        }
        if($params['time_start'] ?? 0) {
            $begin = strtotime($params['time_start'][0]);
            $end = strtotime($params['time_start'][1]);
            $filter['send_at|gte'] = $begin;
            $filter['send_at|lte'] = $end;
        }
        $filter['company_id'] = $companyId;
        $cols = ['id','task_name','template_name','send_at', 'total_num', 'failed_num', 'status', 'created'];
        $data = $taskService->lists($filter, $cols, $page, $pageSize, ['created' => 'DESC']);
        return $this->response->array($data);
    }

    /**
     * @SWG\Get(
     *     path="/aliyunsms/task/info",
     *     summary="短信任务详情",
     *     tags={"阿里短信"},
     *     description="短信任务详情",
     *     operationId="getInfo",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="id", in="query", description="签名ID", required=false, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="id", type="string", example="", description=""),
     *                  @SWG\Property( property="task_name", type="string", example="", description="任务名称"),
     *                  @SWG\Property( property="send_at", type="string", example="", description="定时发送时间"),
     *                  @SWG\Property( property="template_id", type="string", example="", description="短信模板"),
     *                  @SWG\Property( property="sign_id", type="string", example="", description="短信签名"),
     *                  @SWG\Property( property="created", type="string", example="", description="创建时间"),
     *                  @SWG\Property( property="status", type="string", example="", description="任务状态"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AliyunsmsErrorResponse") ) )
     * )
     */
    public function getInfo(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $id = $request->input('id');
        $taskService = new TaskService();
        $data = $taskService->getInfo(['id' => $id]);
        return $this->response->array($data);
    }
}
