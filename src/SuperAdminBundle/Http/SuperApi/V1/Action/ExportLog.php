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

namespace SuperAdminBundle\Http\SuperApi\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use EspierBundle\Services\ExportLogService;
use Dingo\Api\Exception\ResourceException;

class ExportLog extends Controller
{
    /**
     * @SWG\Get(
     *     path="/superadmin/datacube/exportloglist",
     *     summary="获取文件导出列表",
     *     tags={"统计"},
     *     description="获取文件导出列表",
     *     operationId="getExportLogList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="company_id", in="query", description="公司ID", required=true, type="integer"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页的数量", required=true, type="integer"),
     *     @SWG\Parameter( name="page", in="query", description="分页页数", required=true, type="integer"),
     *     @SWG\Parameter( name="export_type", in="query", description="导出类型", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="1", description="总数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="log_id", type="string", example="29", description="日志ID"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="file_name", type="string", example="20210205180239items.csv", description="导出文件名称"),
     *                          @SWG\Property( property="file_url", type="string", example="https://b-import-cdn.yuanyuanke.cn/export/csv/...", description="导出文件下载路径"),
     *                          @SWG\Property( property="export_type", type="string", example="items", description="导出类型 member:会员导出,order:订单导出,right:权益导出"),
     *                          @SWG\Property( property="handle_status", type="string", example="finish", description="处理文件状态，可选值有，wait:等待处理,finish:处理完成,processing:处理中,fail:失败 | 处理文件状态，可选值有，wait:等待处理"),
     *                          @SWG\Property( property="error_msg", type="string", example="null", description="失败原因"),
     *                          @SWG\Property( property="finish_time", type="string", example="1612519361", description="确认收货时间 | 处理完成时间"),
     *                          @SWG\Property( property="finish_date", type="string", example="2021-02-05 18:02:41", description="自行更改字段描述"),
     *                          @SWG\Property( property="distributor_id", type="string", example="0", description="分销商id"),
     *                          @SWG\Property( property="operator_id", type="string", example="1", description="操作员Id"),
     *                          @SWG\Property( property="created", type="string", example="1612519361", description="创建时间"),
     *                          @SWG\Property( property="updated", type="string", example="1612519361", description="修改时间"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SuperAdminErrorResponse") ) )
     * )
     */
    public function getExportLogList(Request $request)
    {
        // ModuleID: 76fe2a3d
        $params = $request->all('pageSize', 'page');

        $rules = [
            'page' => ['required|integer|min:1','分页参数错误'],
            'pageSize' => ['required|integer|min:1|max:50','每页最多查询50条数据'],
        ];

        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $company_id = $request->input('company_id');
        $filter['export_type'] = $request->input('export_type');
        if (!empty($company_id)) {
            $filter['company_id'] = $company_id;
        }
        //IT端操作员记录为0
        $filter['operator_id'] = 0;

        $exportLogService = new ExportLogService();
        $data = $exportLogService->lists($filter, $params['page'], $params['pageSize'], ["created" => "DESC"]);
        return $this->response->array($data);
    }
}
