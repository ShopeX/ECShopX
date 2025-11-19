<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
