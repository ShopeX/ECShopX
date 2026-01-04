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

use EspierBundle\Traits\GetExportServiceTraits;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use Dingo\Api\Exception\ResourceException;

class ExportData extends Controller
{
    use GetExportServiceTraits;
 
    /**
     * 导出csv数据
     * path = "/espier/exportCsvData"
     */
    public function exportCsvData(Request $request)
    {
        $redis = app('redis');
        $page = $request->input('page', 0);
        $randNum = $request->input('key', 0);
        $totalCount = $request->input('total_count', 0);
        $redisKey = 'export_key:' . $randNum;
        $filter = $redis->get($redisKey);
        if (!$filter) {
            $this->response->error('key error', 500);
        }
        $filter = json_decode($filter, true);
        $exportType = $filter['export_type'] ?? 'items';
        unset($filter['export_type']);

        $exportService = $this->getService($exportType);
        $fileName = $exportService->getFileName($filter);
        $title = $exportService->getTitle($filter);
        $res = [];
        if ($page == 1) {
            $res[] = array_values($title);
        }
        unset($filter['is_default']);
        unset($filter['operator_type']);
        unset($filter['item_source']);//item_source
        $pageSize = 100;
        app('log')->info(__FUNCTION__.':'.__LINE__.':totalCount:'.$totalCount);
        if (!$totalCount) {
            $totalCount = $exportService->getCount($filter);
            app('log')->info(__FUNCTION__.':'.__LINE__.':totalCount:'.$totalCount);
        }
        unset($filter['isGetSkuList']);

        $dataList = $exportService->getListsApiReturn($filter, $page, $pageSize);
        $percent = ceil($page * $pageSize * 100/$totalCount);
        $percent = min($percent, 100);
        if (!$dataList or $percent>=150) {
            $redis->del($redisKey);
            return $this->response->array(['data' => [], 'percent' => 100,'total_count' => $totalCount, 'file_name' => $fileName]);
        }
        foreach ($dataList as $v) {
            $res[] = array_values($v);
        }
        return $this->response->array(['data' => $res, 'percent' => $percent,'total_count' => $totalCount,  'file_name' => $fileName]);
    }
}
