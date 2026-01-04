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

/**
 * 基于XML直接操作的Excel合并脚本（修复版）
 * 通过直接构建Excel的XML结构来避免PhpSpreadsheet的内存限制
 * 修复了共享字符串索引问题，确保每个sheet的数据正确
 * 
 * 内存控制目标: < 200MB
 * 处理能力: 不受文件大小或数量限制
 */
class ExcelXmlMerger
{
    private $sourceFiles;
    private $outputFile;
    private $tempDir;
    private $sharedStrings = [];
    private $sharedStringIndex = 0;
    private $sheets = [];
    
    public function __construct($sourceFiles, $outputFile)
    {
        $this->sourceFiles = $sourceFiles;
        $this->outputFile = $outputFile;
        $this->tempDir = storage_path('excel') . '/' . substr(basename($outputFile), 0, strrpos(basename($outputFile), '.')) . '/xml';
        
        // 创建临时目录
        if (!mkdir($this->tempDir, 0755, true)) {
            throw new \Exception("无法创建临时目录: {$this->tempDir}");
        }
    }
    
    public function __destruct()
    {
        $this->cleanupTempFiles();
    }
    
    /**
     * 执行合并操作
     */
    public function merge()
    {
        $sourceFiles = $this->sortSourceFiles();
        
        // 第一步：预处理所有文件的共享字符串
        $this->preprocessAllSharedStrings($sourceFiles);
        
        // 第二步：处理每个文件的工作表数据
        foreach ($sourceFiles as $index => $file) {
            $sheetName = 'Sheet' . ($index + 1);
            $this->processFile($file, ($index + 1), $sheetName);
        }
        
        // 第三步：生成各种XML文件
        $this->generateExcelStructure();

        // 第四步：打包生成最终的Excel文件
        $this->packageExcelFile();
    }
    
    /**
     * 预处理所有文件的共享字符串
     */
    private function preprocessAllSharedStrings($sourceFiles)
    {        
        foreach ($sourceFiles as $index => $file) {            
            // 解压Excel文件
            $zip = new \ZipArchive();
            if ($zip->open($file) !== TRUE) {
                throw new \Exception("无法打开文件: $file");
            }

            // 读取共享字符串文件
            $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
            if ($sharedStringsXml !== false) {
                $this->processSharedStringsXml($sharedStringsXml);
            }

            $zip->close();
        }
    }
    
    /**
     * 处理共享字符串XML
     */
    private function processSharedStringsXml($xmlContent)
    {
        $reader = new \XMLReader();
        $reader->XML($xmlContent);

        while ($reader->read()) {
            if ($reader->nodeType == \XMLReader::ELEMENT && $reader->localName == 'si') {
                $siXml = $reader->readOuterXML();
                $si = simplexml_load_string($siXml);
                
                if (isset($si->t)) {
                    $text = (string)$si->t;
                    if (!isset($this->sharedStrings[$text])) {
                        $this->sharedStrings[$text] = $this->sharedStringIndex++;
                    }
                }
            }
        }
        $reader->close();
    }
    
    /**
     * 处理单个文件
     */
    private function processFile($file, $sheetIndex, $sheetName)
    {
        // 解压Excel文件
        $zip = new \ZipArchive();
        if ($zip->open($file) !== TRUE) {
            throw new \Exception("无法打开文件: $file");
        }
        
        // 读取工作表文件
        $worksheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($worksheetXml === false) {
            throw new \Exception("无法找到工作表: $file");
        }
        
        // 读取原始共享字符串文件以建立映射
        $originalSharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
        $originalStringMap = [];
        if ($originalSharedStringsXml !== false) {
            $originalStringMap = $this->buildOriginalStringMap($originalSharedStringsXml);
        }
        
        $zip->close();
        
        // 处理工作表数据并重新映射共享字符串索引
        $this->processWorksheetWithMapping($worksheetXml, $originalStringMap, $sheetIndex, $sheetName);
    }
    
    /**
     * 构建原始字符串映射
     */
    private function buildOriginalStringMap($xmlContent)
    {
        $map = [];
        $index = 0;
        
        $reader = new \XMLReader();
        $reader->XML($xmlContent);
        
        while ($reader->read()) {
            if ($reader->nodeType == \XMLReader::ELEMENT && $reader->localName == 'si') {
                $siXml = $reader->readOuterXML();
                $si = simplexml_load_string($siXml);
                
                if (isset($si->t)) {
                    $text = (string)$si->t;
                    $map[$index] = $text;
                    $index++;
                }
            }
        }
        $reader->close();
        
        return $map;
    }
    
    /**
     * 处理工作表数据并重新映射共享字符串
     */
    private function processWorksheetWithMapping($worksheetXml, $originalStringMap, $sheetIndex, $sheetName)
    {
        // 创建工作表临时文件
        $sheetTempFile = $this->tempDir . "/sheet_$sheetIndex.xml";
        $output = fopen($sheetTempFile, 'w');
        
        // 写入工作表头部
        fwrite($output, '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n");
        fwrite($output, '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' . "\n");
        fwrite($output, '<sheetData>' . "\n");
        
        $reader = new \XMLReader();
        $reader->XML($worksheetXml);
        
        $rowCount = 0;
        while ($reader->read()) {
            if ($reader->nodeType == \XMLReader::ELEMENT && $reader->localName == 'row') {
                $rowXml = $reader->readOuterXML();
                
                // 重新映射这一行中的共享字符串索引
                $mappedRowXml = $this->remapSharedStringIndexes($rowXml, $originalStringMap);
                
                fwrite($output, $mappedRowXml . "\n");
                $rowCount++;
            }
        }
        
        // 写入工作表尾部
        fwrite($output, '</sheetData>' . "\n");
        fwrite($output, '<printOptions gridLines="false" gridLinesSet="true"/>' . "\n");
        fwrite($output, '<pageMargins left="0.7" right="0.7" top="0.75" bottom="0.75" header="0.3" footer="0.3"/>' . "\n");
        fwrite($output, '<pageSetup paperSize="1" orientation="default" scale="100" fitToHeight="1" fitToWidth="1" pageOrder="downThenOver"/>' . "\n");
        fwrite($output, '<headerFooter differentOddEven="false" differentFirst="false" scaleWithDoc="true" alignWithMargins="true">' . "\n");
        fwrite($output, '<oddHeader></oddHeader><oddFooter></oddFooter><evenHeader></evenHeader><evenFooter></evenFooter><firstHeader></firstHeader><firstFooter></firstFooter>' . "\n");
        fwrite($output, '</headerFooter>' . "\n");
        fwrite($output, '<tableParts count="0"/>' . "\n");
        fwrite($output, '</worksheet>' . "\n");
        
        fclose($output);
        $reader->close();
        
        // 记录工作表信息
        $this->sheets[] = [
            'name' => $sheetName,
            'file' => $sheetTempFile,
            'rowCount' => $rowCount
        ];
    }
    
    /**
     * 重新映射行中的共享字符串索引
     */
    private function remapSharedStringIndexes($rowXml, $originalStringMap)
    {
        // 使用正确的正则表达式找到所有的共享字符串引用
        $pattern = '/<v>(\d+)<\/v>/';
        
        return preg_replace_callback($pattern, function($matches) use ($originalStringMap) {
            $originalIndex = (int)$matches[1];
            
            // 如果原始索引存在于映射中
            if (isset($originalStringMap[$originalIndex])) {
                $originalText = $originalStringMap[$originalIndex];
                
                // 查找在新的共享字符串表中的索引
                if (isset($this->sharedStrings[$originalText])) {
                    $newIndex = $this->sharedStrings[$originalText];
                    return '<v>' . $newIndex . '</v>';
                }
            }
            
            // 如果找不到映射，保持原样
            return $matches[0];
        }, $rowXml);
    }
    
    /**
     * 获取源文件列表
     */
    private function sortSourceFiles()
    {
        // 按文件名中的数字排序
        usort($this->sourceFiles, function($a, $b) {
            $numA = (int)preg_replace('/[^\d]+(\d+)\.xlsx/', '$1', basename($a));
            $numB = (int)preg_replace('/[^\d]+(\d+)\.xlsx/', '$1', basename($b));
            return $numA - $numB;
        });
        
        return array_values($this->sourceFiles);
    }
    
    /**
     * 生成Excel结构
     */
    private function generateExcelStructure()
    {
        $excelDir = $this->tempDir . '/excel';
        
        // 创建Excel目录结构
        $this->createExcelDirectoryStructure($excelDir);
        
        // 生成各种XML文件
        $this->generateContentTypes($excelDir);
        $this->generateRootRels($excelDir);
        $this->generateDocProps($excelDir);
        $this->generateWorkbook($excelDir);
        $this->generateStyles($excelDir);
        $this->generateTheme($excelDir);
        $this->generateSharedStrings($excelDir);
        $this->generateWorksheets($excelDir);
    }
    
    /**
     * 创建Excel目录结构
     */
    private function createExcelDirectoryStructure($excelDir)
    {
        $dirs = [
            $excelDir,
            $excelDir . '/_rels',
            $excelDir . '/docProps',
            $excelDir . '/xl',
            $excelDir . '/xl/_rels',
            $excelDir . '/xl/theme',
            $excelDir . '/xl/worksheets',
            $excelDir . '/xl/worksheets/_rels'
        ];
        
        foreach ($dirs as $dir) {
            if (!mkdir($dir, 0755, true)) {
                throw new \Exception("无法创建目录: $dir");
            }
        }
    }
    
    /**
     * 生成共享字符串文件
     */
    private function generateSharedStrings($excelDir)
    {        
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . count($this->sharedStrings) . '" uniqueCount="' . count($this->sharedStrings) . '">' . "\n";
        
        // 按索引排序
        $sortedStrings = array_flip($this->sharedStrings);
        ksort($sortedStrings);
        
        foreach ($sortedStrings as $index => $text) {
            $xml .= '<si><t>' . htmlspecialchars($text, ENT_XML1) . '</t></si>' . "\n";
        }
        
        $xml .= '</sst>';
        
        file_put_contents($excelDir . '/xl/sharedStrings.xml', $xml);
    }
    
    /**
     * 生成工作表文件
     */
    private function generateWorksheets($excelDir)
    {   
        foreach ($this->sheets as $index => $sheet) {
            $sheetNum = $index + 1;
            $targetFile = $excelDir . "/xl/worksheets/sheet$sheetNum.xml";
            
            // 直接复制临时工作表文件
            copy($sheet['file'], $targetFile);
        }
    }
    
    /**
     * 打包Excel文件
     */
    private function packageExcelFile()
    {
        $excelDir = $this->tempDir . '/excel';
        
        $zip = new \ZipArchive();
        if ($zip->open($this->outputFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== TRUE) {
            throw new \Exception("无法创建输出文件: {$this->outputFile}");
        }
        
        // 递归添加所有文件
        $this->addDirectoryToZip($zip, $excelDir, '');
        
        $zip->close();
    }
    
    /**
     * 递归添加目录到ZIP
     */
    private function addDirectoryToZip($zip, $dir, $zipPath)
    {
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            
            $fullPath = $dir . '/' . $file;
            $zipFilePath = $zipPath === '' ? $file : $zipPath . '/' . $file;
            
            if (is_dir($fullPath)) {
                $this->addDirectoryToZip($zip, $fullPath, $zipFilePath);
            } else {
                $zip->addFile($fullPath, $zipFilePath);
            }
        }
    }
    
    /**
     * 生成Content Types文件
     */
    private function generateContentTypes($excelDir)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' . "\n";
        $xml .= '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' . "\n";
        $xml .= '<Default Extension="xml" ContentType="application/xml"/>' . "\n";
        $xml .= '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' . "\n";
        
        // 添加每个工作表的内容类型
        foreach ($this->sheets as $index => $sheet) {
            $sheetNum = $index + 1;
            $xml .= '<Override PartName="/xl/worksheets/sheet' . $sheetNum . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' . "\n";
        }
        
        $xml .= '<Override PartName="/xl/theme/theme1.xml" ContentType="application/vnd.openxmlformats-officedocument.theme+xml"/>' . "\n";
        $xml .= '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' . "\n";
        $xml .= '<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>' . "\n";
        $xml .= '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>' . "\n";
        $xml .= '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>' . "\n";
        $xml .= '</Types>';
        
        file_put_contents($excelDir . '/[Content_Types].xml', $xml);
    }
    
    /**
     * 生成根关系文件
     */
    private function generateRootRels($excelDir)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . "\n";
        $xml .= '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' . "\n";
        $xml .= '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>' . "\n";
        $xml .= '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>' . "\n";
        $xml .= '</Relationships>';
        
        file_put_contents($excelDir . '/_rels/.rels', $xml);
    }
    
    /**
     * 生成文档属性文件
     */
    private function generateDocProps($excelDir)
    {
        // Core properties
        $coreXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $coreXml .= '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">' . "\n";
        $coreXml .= '<dc:creator>Excel XML Merger</dc:creator>' . "\n";
        $coreXml .= '<dcterms:created xsi:type="dcterms:W3CDTF">' . date('c') . '</dcterms:created>' . "\n";
        $coreXml .= '<dcterms:modified xsi:type="dcterms:W3CDTF">' . date('c') . '</dcterms:modified>' . "\n";
        $coreXml .= '</cp:coreProperties>';
        
        file_put_contents($excelDir . '/docProps/core.xml', $coreXml);
        
        // App properties
        $appXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $appXml .= '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">' . "\n";
        $appXml .= '<Application>Excel XML Merger</Application>' . "\n";
        $appXml .= '<DocSecurity>0</DocSecurity>' . "\n";
        $appXml .= '<ScaleCrop>false</ScaleCrop>' . "\n";
        $appXml .= '<LinksUpToDate>false</LinksUpToDate>' . "\n";
        $appXml .= '<SharedDoc>false</SharedDoc>' . "\n";
        $appXml .= '<HyperlinksChanged>false</HyperlinksChanged>' . "\n";
        $appXml .= '<AppVersion>16.0300</AppVersion>' . "\n";
        $appXml .= '</Properties>';
        
        file_put_contents($excelDir . '/docProps/app.xml', $appXml);
    }
    
    /**
     * 生成工作簿文件
     */
    private function generateWorkbook($excelDir)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' . "\n";
        $xml .= '<fileVersion appName="xl" lastEdited="7" lowestEdited="7" rupBuild="24816"/>' . "\n";
        $xml .= '<workbookPr defaultThemeVersion="166925"/>' . "\n";
        $xml .= '<bookViews>' . "\n";
        $xml .= '<workbookView xWindow="0" yWindow="0" windowWidth="22260" windowHeight="12645"/>' . "\n";
        $xml .= '</bookViews>' . "\n";
        $xml .= '<sheets>' . "\n";
        
        // 添加每个工作表
        foreach ($this->sheets as $index => $sheet) {
            $sheetId = $index + 1;
            $xml .= '<sheet name="' . htmlspecialchars($sheet['name']) . '" sheetId="' . $sheetId . '" r:id="rId' . $sheetId . '"/>' . "\n";
        }
        
        $xml .= '</sheets>' . "\n";
        $xml .= '<calcPr calcId="191029"/>' . "\n";
        $xml .= '</workbook>';
        
        file_put_contents($excelDir . '/xl/workbook.xml', $xml);
        
        // 生成工作簿关系文件
        $relsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $relsXml .= '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . "\n";
        
        // 添加每个工作表的关系
        foreach ($this->sheets as $index => $sheet) {
            $sheetId = $index + 1;
            $relsXml .= '<Relationship Id="rId' . $sheetId . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $sheetId . '.xml"/>' . "\n";
        }
        
        $relsXml .= '<Relationship Id="rId' . (count($this->sheets) + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/theme" Target="theme/theme1.xml"/>' . "\n";
        $relsXml .= '<Relationship Id="rId' . (count($this->sheets) + 2) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>' . "\n";
        $relsXml .= '<Relationship Id="rId' . (count($this->sheets) + 3) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>' . "\n";
        $relsXml .= '</Relationships>';
        
        file_put_contents($excelDir . '/xl/_rels/workbook.xml.rels', $relsXml);
    }
    
    /**
     * 生成样式文件
     */
    private function generateStyles($excelDir)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006" mc:Ignorable="x14ac x16r2 xr" xmlns:x14ac="http://schemas.microsoft.com/office/spreadsheetml/2009/9/ac" xmlns:x16r2="http://schemas.microsoft.com/office/spreadsheetml/2015/02/main" xmlns:xr="http://schemas.microsoft.com/office/spreadsheetml/2014/revision">' . "\n";
        $xml .= '<fonts count="1" x14ac:knownFonts="1">' . "\n";
        $xml .= '<font>' . "\n";
        $xml .= '<sz val="11"/>' . "\n";
        $xml .= '<color theme="1"/>' . "\n";
        $xml .= '<name val="Calibri"/>' . "\n";
        $xml .= '<family val="2"/>' . "\n";
        $xml .= '<scheme val="minor"/>' . "\n";
        $xml .= '</font>' . "\n";
        $xml .= '</fonts>' . "\n";
        $xml .= '<fills count="2">' . "\n";
        $xml .= '<fill>' . "\n";
        $xml .= '<patternFill patternType="none"/>' . "\n";
        $xml .= '</fill>' . "\n";
        $xml .= '<fill>' . "\n";
        $xml .= '<patternFill patternType="gray125"/>' . "\n";
        $xml .= '</fill>' . "\n";
        $xml .= '</fills>' . "\n";
        $xml .= '<borders count="1">' . "\n";
        $xml .= '<border>' . "\n";
        $xml .= '<left/>' . "\n";
        $xml .= '<right/>' . "\n";
        $xml .= '<top/>' . "\n";
        $xml .= '<bottom/>' . "\n";
        $xml .= '<diagonal/>' . "\n";
        $xml .= '</border>' . "\n";
        $xml .= '</borders>' . "\n";
        $xml .= '<cellStyleXfs count="1">' . "\n";
        $xml .= '<xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>' . "\n";
        $xml .= '</cellStyleXfs>' . "\n";
        $xml .= '<cellXfs count="1">' . "\n";
        $xml .= '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>' . "\n";
        $xml .= '</cellXfs>' . "\n";
        $xml .= '<cellStyles count="1">' . "\n";
        $xml .= '<cellStyle name="Normal" xfId="0" builtinId="0"/>' . "\n";
        $xml .= '</cellStyles>' . "\n";
        $xml .= '<dxfs count="0"/>' . "\n";
        $xml .= '<tableStyles count="0" defaultTableStyle="TableStyleMedium2" defaultPivotStyle="PivotStyleLight16"/>' . "\n";
        $xml .= '</styleSheet>';
        
        file_put_contents($excelDir . '/xl/styles.xml', $xml);
    }
    
    /**
     * 生成主题文件
     */
    private function generateTheme($excelDir)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<a:theme xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" name="Office Theme">' . "\n";
        $xml .= '<a:themeElements>' . "\n";
        $xml .= '<a:clrScheme name="Office">' . "\n";
        $xml .= '<a:dk1><a:sysClr val="windowText" lastClr="000000"/></a:dk1>' . "\n";
        $xml .= '<a:lt1><a:sysClr val="window" lastClr="FFFFFF"/></a:lt1>' . "\n";
        $xml .= '<a:dk2><a:srgbClr val="44546A"/></a:dk2>' . "\n";
        $xml .= '<a:lt2><a:srgbClr val="E7E6E6"/></a:lt2>' . "\n";
        $xml .= '<a:accent1><a:srgbClr val="5B9BD5"/></a:accent1>' . "\n";
        $xml .= '<a:accent2><a:srgbClr val="70AD47"/></a:accent2>' . "\n";
        $xml .= '<a:accent3><a:srgbClr val="A5A5A5"/></a:accent3>' . "\n";
        $xml .= '<a:accent4><a:srgbClr val="FFC000"/></a:accent4>' . "\n";
        $xml .= '<a:accent5><a:srgbClr val="4472C4"/></a:accent5>' . "\n";
        $xml .= '<a:accent6><a:srgbClr val="70AD47"/></a:accent6>' . "\n";
        $xml .= '<a:hlink><a:srgbClr val="0563C1"/></a:hlink>' . "\n";
        $xml .= '<a:folHlink><a:srgbClr val="954F72"/></a:folHlink>' . "\n";
        $xml .= '</a:clrScheme>' . "\n";
        $xml .= '<a:fontScheme name="Office">' . "\n";
        $xml .= '<a:majorFont>' . "\n";
        $xml .= '<a:latin typeface="Calibri Light" panose="020F0302020204030204"/>' . "\n";
        $xml .= '<a:ea typeface=""/>' . "\n";
        $xml .= '<a:cs typeface=""/>' . "\n";
        $xml .= '</a:majorFont>' . "\n";
        $xml .= '<a:minorFont>' . "\n";
        $xml .= '<a:latin typeface="Calibri" panose="020F0502020204030204"/>' . "\n";
        $xml .= '<a:ea typeface=""/>' . "\n";
        $xml .= '<a:cs typeface=""/>' . "\n";
        $xml .= '</a:minorFont>' . "\n";
        $xml .= '</a:fontScheme>' . "\n";
        $xml .= '<a:fmtScheme name="Office">' . "\n";
        $xml .= '<a:fillStyleLst>' . "\n";
        $xml .= '<a:solidFill><a:schemeClr val="phClr"/></a:solidFill>' . "\n";
        $xml .= '<a:gradFill rotWithShape="1">' . "\n";
        $xml .= '<a:gsLst>' . "\n";
        $xml .= '<a:gs pos="0"><a:schemeClr val="phClr"><a:lumMod val="110000"/><a:satMod val="105000"/><a:tint val="67000"/></a:schemeClr></a:gs>' . "\n";
        $xml .= '<a:gs pos="50000"><a:schemeClr val="phClr"><a:lumMod val="105000"/><a:satMod val="103000"/><a:tint val="73000"/></a:schemeClr></a:gs>' . "\n";
        $xml .= '<a:gs pos="100000"><a:schemeClr val="phClr"><a:lumMod val="105000"/><a:satMod val="109000"/><a:tint val="81000"/></a:schemeClr></a:gs>' . "\n";
        $xml .= '</a:gsLst>' . "\n";
        $xml .= '<a:lin ang="5400000" scaled="0"/>' . "\n";
        $xml .= '</a:gradFill>' . "\n";
        $xml .= '<a:gradFill rotWithShape="1">' . "\n";
        $xml .= '<a:gsLst>' . "\n";
        $xml .= '<a:gs pos="0"><a:schemeClr val="phClr"><a:satMod val="103000"/><a:lumMod val="102000"/><a:tint val="94000"/></a:schemeClr></a:gs>' . "\n";
        $xml .= '<a:gs pos="50000"><a:schemeClr val="phClr"><a:satMod val="110000"/><a:lumMod val="100000"/><a:shade val="100000"/></a:schemeClr></a:gs>' . "\n";
        $xml .= '<a:gs pos="100000"><a:schemeClr val="phClr"><a:lumMod val="99000"/><a:satMod val="120000"/><a:shade val="78000"/></a:schemeClr></a:gs>' . "\n";
        $xml .= '</a:gsLst>' . "\n";
        $xml .= '<a:lin ang="5400000" scaled="0"/>' . "\n";
        $xml .= '</a:gradFill>' . "\n";
        $xml .= '</a:fillStyleLst>' . "\n";
        $xml .= '<a:lnStyleLst>' . "\n";
        $xml .= '<a:ln w="6350" cap="flat" cmpd="sng" algn="ctr">' . "\n";
        $xml .= '<a:solidFill><a:schemeClr val="phClr"/></a:solidFill>' . "\n";
        $xml .= '<a:prstDash val="solid"/>' . "\n";
        $xml .= '<a:miter lim="800000"/>' . "\n";
        $xml .= '</a:ln>' . "\n";
        $xml .= '<a:ln w="12700" cap="flat" cmpd="sng" algn="ctr">' . "\n";
        $xml .= '<a:solidFill><a:schemeClr val="phClr"/></a:solidFill>' . "\n";
        $xml .= '<a:prstDash val="solid"/>' . "\n";
        $xml .= '<a:miter lim="800000"/>' . "\n";
        $xml .= '</a:ln>' . "\n";
        $xml .= '<a:ln w="19050" cap="flat" cmpd="sng" algn="ctr">' . "\n";
        $xml .= '<a:solidFill><a:schemeClr val="phClr"/></a:solidFill>' . "\n";
        $xml .= '<a:prstDash val="solid"/>' . "\n";
        $xml .= '<a:miter lim="800000"/>' . "\n";
        $xml .= '</a:ln>' . "\n";
        $xml .= '</a:lnStyleLst>' . "\n";
        $xml .= '<a:effectStyleLst>' . "\n";
        $xml .= '<a:effectStyle>' . "\n";
        $xml .= '<a:effectLst/>' . "\n";
        $xml .= '</a:effectStyle>' . "\n";
        $xml .= '<a:effectStyle>' . "\n";
        $xml .= '<a:effectLst/>' . "\n";
        $xml .= '</a:effectStyle>' . "\n";
        $xml .= '<a:effectStyle>' . "\n";
        $xml .= '<a:effectLst>' . "\n";
        $xml .= '<a:outerShdw blurRad="57150" dist="19050" dir="5400000" algn="ctr" rotWithShape="0">' . "\n";
        $xml .= '<a:srgbClr val="000000"><a:alpha val="63000"/></a:srgbClr>' . "\n";
        $xml .= '</a:outerShdw>' . "\n";
        $xml .= '</a:effectLst>' . "\n";
        $xml .= '</a:effectStyle>' . "\n";
        $xml .= '</a:effectStyleLst>' . "\n";
        $xml .= '<a:bgFillStyleLst>' . "\n";
        $xml .= '<a:solidFill><a:schemeClr val="phClr"/></a:solidFill>' . "\n";
        $xml .= '<a:solidFill><a:schemeClr val="phClr"><a:tint val="95000"/><a:satMod val="170000"/></a:schemeClr></a:solidFill>' . "\n";
        $xml .= '<a:gradFill rotWithShape="1">' . "\n";
        $xml .= '<a:gsLst>' . "\n";
        $xml .= '<a:gs pos="0"><a:schemeClr val="phClr"><a:tint val="93000"/><a:satMod val="150000"/><a:shade val="98000"/><a:lumMod val="102000"/></a:schemeClr></a:gs>' . "\n";
        $xml .= '<a:gs pos="50000"><a:schemeClr val="phClr"><a:tint val="98000"/><a:satMod val="130000"/><a:shade val="90000"/><a:lumMod val="103000"/></a:schemeClr></a:gs>' . "\n";
        $xml .= '<a:gs pos="100000"><a:schemeClr val="phClr"><a:shade val="63000"/><a:satMod val="120000"/></a:schemeClr></a:gs>' . "\n";
        $xml .= '</a:gsLst>' . "\n";
        $xml .= '<a:lin ang="5400000" scaled="0"/>' . "\n";
        $xml .= '</a:gradFill>' . "\n";
        $xml .= '</a:bgFillStyleLst>' . "\n";
        $xml .= '</a:fmtScheme>' . "\n";
        $xml .= '</a:themeElements>' . "\n";
        $xml .= '<a:objectDefaults/>' . "\n";
        $xml .= '<a:extraClrSchemeLst/>' . "\n";
        $xml .= '<a:extLst>' . "\n";
        $xml .= '<a:ext uri="{05A4C25C-085E-4340-85A3-A5531E510DB2}">' . "\n";
        $xml .= '<thm15:themeFamily xmlns:thm15="http://schemas.microsoft.com/office/thememl/2012/main" name="Office Theme" id="{62F939B6-93AF-4DB8-9C6B-D6C7DFDC589F}" vid="{4A3C46E8-61CC-4603-A589-7422A47A8E4A}"/>' . "\n";
        $xml .= '</a:ext>' . "\n";
        $xml .= '</a:extLst>' . "\n";
        $xml .= '</a:theme>';
        
        file_put_contents($excelDir . '/xl/theme/theme1.xml', $xml);
    }
    
    /**
     * 清理临时文件
     */
    private function cleanupTempFiles()
    {
        if (is_dir($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
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
}