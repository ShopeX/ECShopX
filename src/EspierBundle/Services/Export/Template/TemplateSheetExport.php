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
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

// sheet1模板
class TemplateSheetExport implements FromArray, WithTitle, WithHeadings, WithStyles
{
    private $sheetData;

    /*
        $params = [
            'sheetname' => 'sheet名称',
            'list' => [], // 单元格列表，包括头部
            'textColumns' => [], // 需要设置为文本格式的列索引数组（从1开始）或列名数组
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

    /**
     * 设置单元格样式
     * @param Worksheet $sheet
     */
    public function styles(Worksheet $sheet)
    {
        // 如果指定了需要设置为文本格式的列
        if (isset($this->sheetData['textColumns']) && !empty($this->sheetData['textColumns'])) {
            $textColumns = $this->sheetData['textColumns'];
            
            // 获取表头行（第一行）
            $headerRow = isset($this->sheetData['list'][0]) ? $this->sheetData['list'][0] : [];
            
            foreach ($textColumns as $column) {
                $colIndex = null;
                
                // 如果传入的是列名，查找对应的列索引
                if (is_string($column)) {
                    $foundIndex = array_search($column, $headerRow, true);
                    if ($foundIndex !== false) {
                        $colIndex = $foundIndex + 1; // 转换为1-based索引
                    }
                } elseif (is_numeric($column)) {
                    // 如果传入的是数字索引（1-based）
                    $colIndex = (int)$column;
                }
                
                if ($colIndex !== null && $colIndex > 0) {
                    // 将数字索引转换为列字母（A, B, C, ...）
                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                    
                    // 设置整列为文本格式（从第2行开始，第1行是表头）
                    $highestRow = $sheet->getHighestRow();
                    if ($highestRow < 2) {
                        $highestRow = 1000; // 如果只有表头，设置一个默认行数
                    }
                    
                    // 设置整列的格式为文本
                    $sheet->getStyle($colLetter . '2:' . $colLetter . $highestRow)
                        ->getNumberFormat()
                        ->setFormatCode(NumberFormat::FORMAT_TEXT);
                }
            }
        }
    }
}
