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

namespace EspierBundle\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use EspierBundle\Services\ExcelXmlMerger;

/**
 * Excel导出服务
 * 支持百万级数据导出，内存消耗控制在200M以内
 * 使用临时文件分段写入，避免内存溢出
 */
class ExportExcelService
{
    const ROWS_PER_SHEET = 60000; // 每个sheet最大行数，按用户要求调整
    
    private $spreadsheet;
    private $currentSheet;
    private $currentRow = 1;
    private $currentSheetIndex = 0;
    private $fileName;
    private $tempDir;
    private $tempFiles = []; // 临时文件列表
    private $headers = []; // 保存表头信息
    
    public function __construct()
    {
        $this->tempDir = storage_path('excel');
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0777, true);
        }
    }
    
    /**
     * 保存当前sheet到临时文件
     */
    private function saveCurrentSheetToTemp()
    {
        if (!$this->spreadsheet || $this->currentSheetIndex < 0) {
            return;
        }
        
        $tempFile = $this->tempDir . '/' . $this->fileName . '/sheet' . ($this->currentSheetIndex + 1) . '.xlsx';
        $writer = new Xlsx($this->spreadsheet);
        
        // 设置WPS兼容性配置
        $writer->setOffice2003Compatibility(true);
        $writer->setPreCalculateFormulas(false);
        
        $writer->save($tempFile);
        $this->tempFiles[] = $tempFile;
    }
    
    /**
     * 清理当前Spreadsheet对象
     */
    private function clearCurrentSpreadsheet()
    {
        if ($this->spreadsheet) {
            $this->spreadsheet->disconnectWorksheets();
            unset($this->spreadsheet);
            $this->spreadsheet = null;
        }
        
        $this->currentSheet = null;
        $this->currentRow = 1;
    }
    
    /**
     * 重新创建Spreadsheet对象
     */
    private function recreateSpreadsheet()
    {
        $this->spreadsheet = new Spreadsheet();
        $this->spreadsheet->removeSheetByIndex(0);
        
        // 不要重置currentSheetIndex，保持当前值
        $this->createNewSheet($this->headers);
    }
    
    /**
     * 开始导出
     * @param string $fileName 文件名
     * @param array $headers 表头
     * @return string 文件路径
     */
    public function startExport($fileName, $headers = [])
    {
        mkdir($this->tempDir . '/' . $fileName, 0777, true);

        $this->fileName = $fileName;
        $this->headers = $headers; // 保存表头信息
        $this->tempFiles = []; // 重置临时文件列表
        
        // 创建新的Spreadsheet对象
        $this->spreadsheet = new Spreadsheet();
        $this->spreadsheet->removeSheetByIndex(0); // 删除默认sheet
        
        // 创建第一个sheet
        $this->createNewSheet($headers);
        
        return $this->fileName;
    }
    
    /**
     * 创建新的sheet
     * @param array $headers 表头
     */
    private function createNewSheet($headers = [])
    {
        $this->currentSheet = new Worksheet($this->spreadsheet, 'Sheet' . ($this->currentSheetIndex + 1));
        
        // 添加sheet到末尾，不指定索引位置
        $this->spreadsheet->addSheet($this->currentSheet);
        
        // 设置活动sheet索引为最后一个sheet
        $actualSheetCount = $this->spreadsheet->getSheetCount();
        $this->spreadsheet->setActiveSheetIndex($actualSheetCount - 1);
        
        $this->currentRow = 1;
        
        // 写入表头
        if (!empty($headers)) {
            $this->writeHeaders($headers);
        }
    }
    
    /**
     * 写入表头
     * @param array $headers
     */
    private function writeHeaders($headers)
    {
        $col = 1;
        foreach ($headers as $header) {
            $this->currentSheet->setCellValueByColumnAndRow($col, $this->currentRow, $header);
            $col++;
        }

        $this->currentRow++;
    }
    
    /**
     * 批量写入数据
     * @param array $dataList 数据列表
     * @param callable $dataProcessor 数据处理回调函数
     */
    public function writeBatchData($dataList, $dataProcessor = null)
    {   
        // 如果Spreadsheet对象被清理了，需要重新创建
        if (!$this->spreadsheet) {
            $this->recreateSpreadsheet();
        }
        
        foreach ($dataList as $data) {
            // 检查是否需要创建新的sheet
            if ($this->currentRow > self::ROWS_PER_SHEET) {
                // 保存当前sheet到临时文件
                $this->saveCurrentSheetToTemp();
                $this->clearCurrentSpreadsheet();
                
                // 创建新的sheet
                $this->currentSheetIndex++;
                $this->recreateSpreadsheet();
            }
            
            // 处理数据
            if ($dataProcessor && is_callable($dataProcessor)) {
                $data = $dataProcessor($data);
            }
            
            // 写入数据行
            $this->writeDataRow($data);
        }
    }
    
    /**
     * 写入单行数据
     * @param array $rowData
     */
    private function writeDataRow($rowData)
    {
        $col = 1;
        foreach ($rowData as $value) {
            // 处理特殊字符和数据类型
            $cellValue = $this->formatCellValue($value);
            $this->currentSheet->setCellValueByColumnAndRow($col, $this->currentRow, $cellValue);
            $col++;
        }
        
        $this->currentRow++;
    }
    
    /**
     * 格式化单元格值
     * @param mixed $value
     * @return mixed
     */
    private function formatCellValue($value)
    {
        // 处理null值
        if ($value === null) {
            return '';
        }
        
        // 处理布尔值
        if (is_bool($value)) {
            return $value ? '是' : '否';
        }
        
        // 处理数组或对象
        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        
        // 转换为字符串
        $value = (string) $value;
        
        // 处理长数字，避免科学计数法
        if (is_numeric($value) && strlen($value) > 10) {
            return "'" . $value;
        }
        
        // 处理以特殊字符开头的字符串，避免被Excel解析为公式
        if (strlen($value) > 0 && in_array($value[0], ['=', '+', '-', '@'])) {
            return "'" . $value;
        }
        
        // 处理包含制表符或换行符的字符串
        $value = str_replace(["\t", "\r\n", "\r", "\n"], [" ", " ", " ", " "], $value);
        
        return $value;
    }
    
    /**
     * 完成导出并保存文件
     * @return string 文件路径
     */
    public function finishExport()
    {
        // 保存当前正在处理的sheet
        if ($this->spreadsheet) {
            $this->saveCurrentSheetToTemp();
            $this->clearCurrentSpreadsheet();
        }

        $outputFile = $this->tempDir . '/' . $this->fileName . '.xlsx';
        $excelXmlMerger = new ExcelXmlMerger($this->tempFiles, $outputFile);
        $excelXmlMerger->merge();
        
        $this->cleanupTempFiles();
        
        return $outputFile;
    }

    
    /**
     * 清理临时文件
     */
    private function cleanupTempFiles()
    {
        if (is_dir($this->tempDir . '/' . $this->fileName)) {
            $this->removeDirectory($this->tempDir . '/' . $this->fileName);
        }
    }

    /**
     * 递归删除目录
     */
    private function removeDirectory($dir)
    {
        if (!is_dir($dir)) return;
        
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            
            $fullPath = $dir . '/' . $file;
            if (is_dir($fullPath)) {
                $this->removeDirectory($fullPath);
            } else {
                unlink($fullPath);
            }
        }
        rmdir($dir);
    }
    
    /**
     * 获取文件下载信息
     * @param string $fileName
     * @return array
     */
    public function getDownloadInfo($fileName, $file)
    {
        $filesystem = app('filesystem')->disk('import-file');
        $remotePath = 'export/excel/' . $fileName . '.xlsx';
        
        // 上传到云存储
        $filesystem->put($remotePath, file_get_contents($file));
        
        return [
            'filedir' => 'export/excel/',
            'filename' => $fileName . '.xlsx',
            'url' => $filesystem->privateDownloadUrl($remotePath, 86400)
        ];
    }
    
    /**
     * 流式导出大量数据
     * @param string $fileName 文件名
     * @param array $headers 表头
     * @param \Generator $dataGenerator 数据生成器
     * @param callable $dataProcessor 数据处理回调
     * @return array 下载信息
     */
    public function export($fileName, $headers, $dataGenerator, $dataProcessor = null)
    {
        $this->startExport($fileName, $headers);
        
        $totalRows = 0;
        
        foreach ($dataGenerator as $batchData) {
            $this->writeBatchData($batchData, $dataProcessor);
            $totalRows += count($batchData);
        }
        
        $filePath = $this->finishExport();
        
        return $this->getDownloadInfo($fileName, $filePath);
    }
}
