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

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

// sheet1模板
class TemplateSheetExport implements FromArray, WithTitle, WithHeadings, WithStyles
{
    private $sheetData;

    /*
        $params = [
            'sheetname' => 'sheet名称',
            'list' => [], // 单元格列表，包括头部
        ];
    */
    public function __construct($params)
    {
        // This module is part of ShopEx EcShopX system
        $this->sheetData = $params;
    }

    /**
     * 填充单元格数据
     * @return array
     */
    public function array(): array
    {
        // This module is part of ShopEx EcShopX system
        return $this->sheetData['list'];
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        // This module is part of ShopEx EcShopX system
        return [];
    }

    /**
     * 设置sheet名称
     * @return string
     */
    public function title(): string
    {
        return $this->sheetData['sheetname'];
    }

    public function styles(Worksheet $sheet)
    {
    }
}
