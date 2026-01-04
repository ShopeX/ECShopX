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

namespace EspierBundle\Services\Export\Template;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

// 通用导出模板
class TemplateExport implements WithMultipleSheets
{
    private $data;
    /*
         $params = [[
                 'sheetname' => 'sheet名称',
                 'list' => [], // 单元格列表，包括头部
             ]
         ];
     */
    public function __construct($params)
    {
        $this->data = $params;
    }

    // 多sheet
    public function sheets(): array
    {
        $sheets = [];
        foreach ($this->data  as $item) {
            $sheets[] = new TemplateSheetExport($item);
        }

        return $sheets;
    }
}
