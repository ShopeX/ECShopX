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

use AliyunsmsBundle\Services\RecordService;
use AliyunsmsBundle\Services\SignService;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;


class Record extends Controller
{
    /**
     * @SWG\Get(
     *     path="/aliyunsms/record/list",
     *     summary="短信记录列表",
     *     tags={"阿里短信"},
     *     description="短信记录列表",
     *     operationId="getList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="status", in="query", description="发送状态:1-发送中;2-发送失败;3-发送成功", required=false, type="integer"),
     *     @SWG\Parameter( name="task_name", in="query", description="任务名称", required=false, type="integer"),
     *     @SWG\Parameter( name="template_type", in="query", description="短信类型:0-验证码 1-短信通知 2-推广短信", required=false, type="integer"),
     *     @SWG\Parameter( name="template_code", in="query", description="模板code", required=false, type="string"),
     *     @SWG\Parameter( name="mobile", in="query", description="手机号", required=false, type="string"),
     *     @SWG\Parameter( name="sms_content", in="query", description="短信内容", required=false, type="string"),
     *     @SWG\Parameter( name="time_start", in="query", description="开始时间:", required=false, type="string"),
     *     @SWG\Parameter( name="page_size", in="query", description="", required=false, type="integer"),
     *     @SWG\Parameter( name="page", in="query", description="", required=false, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="count", type="string", example="2", description="短信记录列表"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="id", type="string", example="", description=""),
     *                          @SWG\Property( property="mobile", type="string", example="", description="手机号"),
     *                          @SWG\Property( property="template_code", type="string", example="", description="模板CODE"),
     *                          @SWG\Property( property="sms_content", type="string", example="", description="短信内容"),
     *                          @SWG\Property( property="template_type", type="integer", example="", description="短信类型: 0-验证码 1-短信通知 2-推广短信"),
     *                          @SWG\Property( property="scene_name", type="string", example="", description="短信场景"),
     *                          @SWG\Property( property="created", type="string", example="", description="发送时间"),
     *                          @SWG\Property( property="status", type="string", example="", description="发送状态:1-发送中;2-发送失败;3-发送成功"),
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
        $pageSize = $params['pageSize'] ?? 10;

        $recordService = new RecordService();
        $filter = [];
        if(isset($params['template_type'])) {
            $filter['template_type'] = $params['template_type'];
        }
        if(isset($params['status'])) {
            $filter['status'] = $params['status'];
        }
        if($params['template_code'] ?? 0) {
            $filter['template_code|contains'] = $params['template_code'];
        }
        if($params['mobile'] ?? 0) {
            $filter['mobile'] = $params['mobile'];
        }
        if($params['sms_content'] ?? 0) {
            $filter['sms_content|contains'] = $params['sms_content'];
        }
        if($params['task_name'] ?? 0) {
            $filter['task_name'] = $params['task_name'];
        }
        if($params['time_start'] ?? 0) {
            $begin = strtotime($params['time_start'][0]);
            $end = strtotime($params['time_start'][1]);
            $filter['created|gte'] = $begin;
            $filter['created|lte'] = $end;
        }
        if($params['task_id'] ?? 0) {
            $filter['task_id'] = $params['task_id'];
        }
        $filter['company_id'] = $companyId;
        $cols = ['id','company_id','mobile','scene_id','template_code', 'template_type','sms_content','status', 'created'];
        $list = $recordService->getList($filter, $cols, $page, $pageSize);
        // 是否有权限查看加密数据
        $datapassBlock = $request->get('x-datapass-block', 0);
        $list['datapass_block'] = $datapassBlock;
        if ($list['list']) {
            foreach ($list['list'] as $key => $value) {
                if ($datapassBlock) {
                    $list['list'][$key]['mobile'] = data_masking('mobile', (string) $value['mobile']);
                }
            }
        }
        return $this->response->array($list);
    }
}
