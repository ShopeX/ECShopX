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

namespace EspierBundle\Services\File;

use GuzzleHttp\Client as Client;
use Illuminate\Http\UploadedFile;
use MembersBundle\Traits\GetCodeTrait;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use EspierBundle\Services\Export\Template\TemplateExport;

abstract class AbstractTemplate
{
    use GetCodeTrait;

    /**
     * 模板文件的顶部内容
     *      key为顶部实际输出的内容
     *      value为在导入文件时遍历出来的数组的key
     * @var array
     */
    protected $header;

    /**
     * 模板文件中对顶部内容的描述
     *      key为header中的key
     *      value为一维数组
     *          size 为长度
     *          remarks 为备注
     *          is_need 是否为必填，true为必填，false为非必填
     * @var array
     */
    protected $headerInfo;

    /**
     * 是否是必填的字段 (结构和header相同)
     * @var array
     */
    protected $isNeedCols;

    /**
     * 当前行数据的处理逻辑，第一行的标题内容已经被外部过滤
     * @param int $companyId 企业id
     * @param array $row 行数据，key为header中的value，value为客户自己填写的内容
     */
    abstract public function handleRow(int $companyId, array $row): void;

    /**
     * 该模板支持的文件类型
     * @var string[]
     */
    protected $extensionArray = ["xlsx"];

    /**
     * 检查文件类型
     * @param UploadedFile $fileObject
     */
    public function check(UploadedFile $fileObject)
    {
        // 判断文件大小
        if ($fileObject->getError() == UPLOAD_ERR_FORM_SIZE) {
            throw new BadRequestHttpException('上传文件大小超出限制');
        }
        // 判断文件类型是否有误
        if (!in_array($fileObject->getClientOriginalExtension(), $this->extensionArray, true)) {
            throw new BadRequestHttpException(sprintf("上传文件只支持%s格式", implode("、", $this->extensionArray)));
        }
    }

    final public function getFileSystem()
    {
        return app('filesystem')->disk('import-file');
    }

    public $tmpTarget;

    /**
     * 获取文件的临时路径
     * @param $filePath
     * @return bool|string
     */
    final public function getFilePath($filePath, $fileExt = '')
    {
        $url = $this->getFileSystem()->privateDownloadUrl($filePath);

        $client = new Client();
        $content = $client->get($url)->getBody()->getContents();

        $this->tmpTarget = tempnam('/tmp', 'import-file') . $fileExt;
        file_put_contents($this->tmpTarget, $content);

        return $this->tmpTarget;
    }

    /**
     * 完成处理
     * @return bool
     */
    final public function finishHandle()
    {
        unlink($this->tmpTarget);
        return true;
    }

    /**
     * 获取头部标题
     */
    public function getHeaderTitle()
    {
        return [
            "all" => $this->header,
            "is_need" => $this->isNeedCols,
            "headerInfo" => $this->headerInfo
        ];
    }

    /**
     * 导出模板, 返回的是Excel的写对象，如果要导出二进制内容则需要去外部调用string方法
     * @param string $fileName 导出文件的文件名（）
     * @param string $sheetName 当前模板中sheet的名字
     * @return
     */
    public function export(string $fileName, string $sheetName = "")
    {
        // 第一个sheet，主要返回当前模板中需要填写的内容
        $data = [
            [
                'sheetname' => $sheetName ?: $fileName,
                'list' => [array_keys($this->header)],
            ]
        ];
        // 第二个sheet，主要返回第一个sheet中每个字段的描述内容
        if (!empty($this->headerInfo)) {
            $commentList[] = ["名称", "最大长度", "是否必填", "备注"];
            foreach ($this->headerInfo as $name => $value) {
                $commentList[] = [
                    $name,
                    $value['size'] . '位',
                    $value['is_need'] ? '是' : '否',
                    $value['remarks']
                ];
            }
            $data[] = [
                'sheetname' => '填写说明',
                'list' => $commentList,
            ];
        }
        $templateObj = new TemplateExport($data);
        return app('excel')->raw($templateObj, \Maatwebsite\Excel\Excel::XLSX);

        // return app('excel')->create(sprintf("模板-%s", $fileName), function ($excel) use ($fileName, $sheetName) {
        //     // 第一个sheet，主要返回当前模板中需要填写的内容
        //     $excel->sheet($sheetName ?: $fileName, function ($sheet) {
        //         $sheet->setOrientation('landscape');
        //         //$sheet->getStyle("A1")->getFont()->setBold(true)->getColor()->setRGB('FF0000');
        //         $sheet->rows([array_keys($this->header)]);
        //     });
        //     // 第二个sheet，主要返回第一个sheet中每个字段的描述内容
        //     if (!empty($this->headerInfo)) {
        //         $excel->sheet('填写说明', function ($sheet) {
        //             $commentList[] = ["名称", "最大长度", "是否必填", "备注"];
        //             foreach ($this->headerInfo as $name => $value) {
        //                 $commentList[] = [
        //                     $name,
        //                     $value['size'] . '位',
        //                     $value['is_need'] ? '是' : '否',
        //                     $value['remarks']
        //                 ];
        //             }
        //             $sheet->setOrientation('landscape');
        //             $sheet->rows($commentList);
        //         });
        //     }
        // });
    }
}
