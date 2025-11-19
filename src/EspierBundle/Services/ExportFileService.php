<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Services;

use Madnest\Madzipper\Madzipper;
use EspierBundle\Services\Export\Template\TemplateExport;

class ExportFileService
{
    protected $cellLetter = [
        'A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q',
        'R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD',
        'AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN',
        'AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ'
    ];

    private function isStyleExport($sheet, $dataList)
    {
        if (isset($dataList['style'])) {
            if (isset($dataList['style']['width'])) {
                foreach ($dataList['style']['width'] as $column => $width) {
                    $sheet->setWidth($column, $width);
                }
            }

            if (isset($dataList['style']['allBorders'])) {
                $sheet->setAllBorders('thin');
            }

            if (isset($dataList['style']['fontSize'])) {
                $sheet->setFontSize($dataList['style']['fontSize']);
            }

            if (isset($dataList['style']['mergeColumn']) && $dataList['style']['mergeColumn']['rows']) {
                $sheet->setMergeColumn(array(
                    'columns' => $dataList['style']['mergeColumn']['columns'],
                    'rows' => $dataList['style']['mergeColumn']['rows']
                ));
            }
            unset($dataList['style']);
        }

        $line = 1;
        foreach ($dataList as $key => $row) {
            $cellKey = '';
            $i = 0;
            foreach ($row as $k => $v) {
                if (strstr($k, ':')) {
                    $sheet->mergeCells($k)->cell($k, function ($cells) use ($v) {
                        if (isset($v['background']) && $v['background']) {
                            $cells->setBackground($v['background']);
                        }
                        if (isset($v['alignment']) && $v['alignment']) {
                            $cells->setAlignment($v['alignment']);
                        }
                        if (isset($v['fontSize']) && $v['fontSize']) {
                            $cells->setFontSize($v['fontSize']);
                        }
                        if (isset($v['fontWeight']) && $v['fontWeight']) {
                            $cells->setFontWeight($v['fontWeight']);
                        }
                    });
                    $cellKey = explode(':', $k)[0];
                } elseif (!is_int($k)) {
                    $cellKey = $k;
                } else {
                    $cellKey = $this->cellLetter[$i].$line;
                }

                if (is_array($v)) {
                    $sheet->cell($cellKey, function ($cells) use ($v) {
                        $cells->setValue($v['data']);
                        if (!isset($v['alignment'])) {
                            $cells->setAlignment('left');
                            $cells->setValignment('top');
                        }
                    });
                    $sheet->setHeight($i, isset($v['height']) ? $v['height'] : 20);
                } else {
                    $sheet->cell($cellKey, function ($cells) use ($v) {
                        $cells->setValue($v);
                        $cells->setAlignment('left');
                        $cells->setValignment('top');
                    });
                }
                $i++;
            }
            $line++;
        }
    }


    public function export($dataList, $fileName, $nowNum = 1, $totalNum = 1, $isStyle = false, $dirName = '')
    {
        $execl = app('excel');
        if ($dirName) {
            $fileDir = 'excel/'.$dirName;
            $fullDir = storage_path('excel/'.$dirName);
        } else {
            $fileDir = 'excel/'.$fileName;
            $fullDir = storage_path('excel/'.$fileName);
        }

        // $sheetName = '导出';
        // 第一个sheet，主要返回当前模板中需要填写的内容
        $data = [
            [
                'sheetname' => '导出',
                'list' => $dataList,
            ]
        ];
        $templateObj = new TemplateExport($data);
        app('excel')->store($templateObj, $fileDir.'/'.$fileName.'-'.$nowNum.'.xlsx');
        // $myFile = $execl->create($fileName."-".$nowNum, function($excel) use ($dataList, $sheetName, $fileDir, $isStyle) {
        //     $excel->sheet($sheetName, function($sheet) use ($dataList, $fileDir, $isStyle){
        //         //$sheet->setOrientation('landscape');
        //         if (!$isStyle) {
        //             $sheet->rows($dataList);
        //             $sheet->setFontSize(15);
        //         } else {
        //             $this->isStyleExport($sheet, $dataList);
        //         }
        //     });
        // });

        // $myFile->store('xlsx', $fileDir);
        $fileArr = $fullDir."/".$fileName."-".$nowNum.".xlsx";

        if ($dirName) {
            $zipFilePath = $this->addFileToZip($fileArr, $dirName);
        } else {
            $zipFilePath = $this->addFileToZip($fileArr, $fileName);
        }

        if ($nowNum == $totalNum) {
            if ($dirName) {
                return $this->downloadZipFile($dirName, $zipFilePath);
            } else {
                return $this->downloadZipFile($fileName, $zipFilePath);
            }
        }
        return true;
    }

    /**
     * 导出商品码
     * @param  string $fileDir  本地存放目录路径
     * @param  string $fileName 目标文件名称
     */
    public function exportItemCode($fileDir, $fileName)
    {
        $zipFilePath = $this->addItemCodeFileToZip($fileDir, $fileName);
        $result = $this->downloadItemCodeZipFile($fileName, $zipFilePath);
        return $result;
    }

    /**
     * 添加文件到zip
     * @param string $fileDir  本地存放目录路径
     * @param string $fileName 目标文件名称
     */
    private function addItemCodeFileToZip($fileDir, $fileName)
    {
        $dirName = storage_path($fileDir);
        $tarFilePath = storage_path('uploads/zip/'.$fileName.".zip");
        $zipper = new Madzipper();
        $zipper->make($tarFilePath)->add($dirName);
        $zipper->close();

        return $tarFilePath;
    }

    private function downloadItemCodeZipFile($fileName, $tarFilePath)
    {
        $filesystem = app('filesystem')->disk('import-file');
        $filesystem->put('export/zip/'.$fileName.'.zip', file_get_contents($tarFilePath));
        $result['filedir'] = 'export/zip/';
        $result['filename'] = $fileName.'.zip';
        $result['url'] = $filesystem->privateDownloadUrl('export/zip/'.$fileName.'.zip', 86400);
        return $result;
    }

    private function addFileToZip($fileArr, $fileName)
    {
        $zipFilePath = storage_path('excel/zip/'.$fileName.".zip");
        $zipper = new Madzipper();
        $zipper->make($zipFilePath)->add($fileArr);
        $zipper->close();

        return $zipFilePath;
    }

    private function downloadZipFile($fileName, $zipFilePath)
    {
        $filesystem = app('filesystem')->disk('import-file');
        $filesystem->put('export/zip/'.$fileName.'.zip', file_get_contents($zipFilePath));
        $result['filedir'] = 'export/zip/';
        $result['filename'] = $fileName.'.zip';
        $result['url'] = $filesystem->privateDownloadUrl('export/csv/'.$fileName.'.csv', 86400);
        return $result;
    }
    public function exportCsv($fileName, $title, $dataList)
    {
        $fileDir = storage_path('csv');
        if (!is_dir($fileDir)) {
            mkdir($fileDir, 0777, true);
        }
        $file = $fileDir. "/".$fileName.".csv";
        // $fn = $file;
        // $fh = fopen($fn, 'w');
        // fputcsv($fh, $title);
        file_put_contents($file, implode(',', $title).PHP_EOL);

        foreach ($dataList as $data) {
            foreach ($data as $list) {
                // fputcsv($fh, $list);
                file_put_contents($file, implode(',', $list).PHP_EOL, FILE_APPEND);
            }
        }
        // fclose($fh);
        $result = $this->downloadOrderFile($fileName, $file);
        return $result;
    }

    public function downloadOrderFile($fileName, $exlFilePath)
    {
        $filesystem = app('filesystem')->disk('import-file');
        $filesystem->put('export/csv/'.$fileName.'.csv', file_get_contents($exlFilePath));
        $result['filedir'] = 'export/csv/';
        $result['filename'] = $fileName.'.csv';
        $result['url'] = $filesystem->privateDownloadUrl('export/csv/'.$fileName.'.csv');
        return $result;
    }
}
