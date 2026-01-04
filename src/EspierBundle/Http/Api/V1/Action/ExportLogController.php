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

namespace EspierBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as Controller;
use Dingo\Api\Exception\ResourceException;
use EspierBundle\Services\ExportLogService;
use Illuminate\Http\Request;

class ExportLogController extends Controller
{
    /**
     * @SWG\Get(
     *     path="/espier/exportlog/list",
     *     summary="获取文件导出列表",
     *     tags={"系统"},
     *     description="获取文件导出列表",
     *     operationId="getExportLogList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页的数量", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="分页页数", required=true, type="string"),
     *     @SWG\Parameter( name="export_type", in="query", description="导出类型", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="16", description=""),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="log_id", type="string", example="17", description="导出日志id"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="file_name", type="string", example="202011111829101master.csv", description="导出文件名称"),
     *                          @SWG\Property( property="file_url", type="string", example="https://test.test.test/export/csv/2020111118.csv", description="导出文件下载路径"),
     *                          @SWG\Property( property="export_type", type="string", example="normal_master_order", description="导出类型 member:会员导出,order:订单导出,right:权益导出"),
     *                          @SWG\Property( property="handle_status", type="string", example="finish", description="处理文件状态，可选值有，wait:等待处理,finish:处理完成,processing:处理中,fail:失败"),
     *                          @SWG\Property( property="error_msg", type="string", example="null", description="失败原因"),
     *                          @SWG\Property( property="finish_time", type="string", example="1605090550", description="处理完成时间"),
     *                          @SWG\Property( property="finish_date", type="string", example="2020-11-11 18:29:10", description=""),
     *                          @SWG\Property( property="distributor_id", type="string", example="0", description="店铺ID"),
     *                          @SWG\Property( property="operator_id", type="string", example="1", description="操作员id"),
     *                          @SWG\Property( property="created", type="string", example="1605090550", description=""),
     *                          @SWG\Property( property="updated", type="string", example="1605090550", description="修改时间"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EspierErrorRespones") ) )
     * )
     */
    public function getExportLogList(Request $request)
    {
        $params = $request->all('pageSize', 'page');

        $rules = [
            'page' => ['required|integer|min:1','分页参数错误'],
            'pageSize' => ['required|integer|min:1|max:50','每页最多查询50条数据'],
        ];

        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $companyId = app('auth')->user()->get('company_id');
        $merchantId = app('auth')->user()->get('merchant_id');
        $operatorType = app('auth')->user()->get('operator_type');
        if ($operatorType == 'merchant') {
            // $filter['merchant_id'] = $merchantId;
        }
        $filter['company_id'] = $companyId;
        $filter['export_type'] = $request->input('export_type');
        $filter['operator_id'] = app('auth')->user()->get('operator_id');
        $filter['supplier_id'] = 0;
        if ($operatorType == 'supplier') {
            $filter['supplier_id'] = $filter['operator_id'];
        }

        if ($request->input('time_start_begin') && $request->input('time_start_end')) {
        }

        $exportLogService = new ExportLogService();
        $data = $exportLogService->lists($filter, $params['page'], $params['pageSize'], ["created" => "DESC"]);
        return $this->response->array($data);
    }

    /**
     * @SWG\Get(
     *     path="/espier/exportlog/file/down",
     *     summary="文件资源下载",
     *     tags={"系统"},
     *     description="文件资源下载",
     *     operationId="fileDown",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="log_id", in="query", description="文件导出列表id", required=true, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构",
     *         @SWG\Schema( @SWG\Property( property="data", type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="csv_data", type="stirng"),
     *                     @SWG\Property(property="file_name", type="stirng"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EspierErrorRespones") ) )
     * )
     */
    public function fileDown(Request $request)
    {
        ini_set('memory_limit', -1);
        $log_id = $request->input('log_id');
        $params = [
            'log_id' => $log_id,
        ];
        $rules = [
            'log_id' => ['required', '缺少文件资源id'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $exportLogService = new ExportLogService();
        $data = $exportLogService->getInfoById($log_id);
        if (empty($data)) {
            throw new ResourceException('资源文件不存在');
        }
        $file_url = $data['file_url'];
        $file_url_info = parse_url($file_url);
        try {
            //第三方存储
            if (isset($file_url_info['host']) && !empty($file_url_info['host'])) {
                $stream_opts = [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                    ]
                ];
                $csv_data = file_get_contents($file_url, true, stream_context_create($stream_opts));
            } else {
                $path = $data['file_url'];
                $csv_data = file_get_contents(storage_path($path), true);
            }
        } catch (\Exception $e) {
            throw new ResourceException('文件下载错误，请重新导出');
        }
        $reslut = [
            'csv_data' => $csv_data,
            'file_name' => date('YmdHis'). $data['export_type'] . '.csv'
        ];
        return $this->response->array($reslut);
    }
}
