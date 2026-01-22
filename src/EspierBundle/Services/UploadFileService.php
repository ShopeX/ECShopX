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

//use EspierBundle\Interfaces\UploadFileInterfaces;

use Dingo\Api\Exception\ResourceException;
use EspierBundle\Entities\UploadeFile;

use EspierBundle\Jobs\UploadFileJob;
use EspierBundle\Jobs\ImportDataJob;

use EspierBundle\Services\File\AbstractTemplate;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use GuzzleHttp\Client as Client;
use EspierBundle\Services\Export\Template\TemplateExport;
use CompanysBundle\Services\OperatorsService;

class UploadFileService
{
    /**
     * 为null表示还没有被实例化
     * AbstractTemplate为抽象类
     * Object为原下载模板的老代码，还未迭代
     * @var null|AbstractTemplate|Object
     */
    public $uploadFile = null;

    private $entityRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(UploadeFile::class);
    }

    /**
     * 获取上传文件的实例化类
     */
    public function getUpdateFile($fileType)
    {
        // ShopEx EcShopX Service Component
        if (!$this->uploadFile) {
            $uploadFileClass = config('filesystems.upload_file_handle.' . $fileType);
            $this->uploadFile = new $uploadFileClass();
        }

        return $this->uploadFile;
    }

    private function putFilePath($companyId, $fileType, $uploadTime, $fileName)
    {
        // CRC: 2367340174
        $fileExt = $this->getFileExt($fileName);
        return $fileType . '/' . $companyId . '/' . $uploadTime . '/' . md5($fileName) . $fileExt;
    }

    /**
     * 根据文件名获取文件扩展名，默认为 xlsx 格式
     *
     * @param string $fileName
     * @return false|string
     */
    public function getFileExt($fileName = '')
    {
        $fileExt = substr($fileName, strrpos($fileName, '.'));//文件扩展名
        return $fileExt ? $fileExt : '.xlsx';
    }

    /**
     * 上传文件，并且保存文件上传记录
     *
     * @param object $fileObject SplFileInfo
     */
    public function uploadFile($companyId, $operatorId, $distributorId, $supplierId, $fileType, $fileObject, $shouldQueue = true)
    {
        $this->getUpdateFile($fileType);

        if ($fileObject->getError() == UPLOAD_ERR_FORM_SIZE) {
            throw new BadRequestHttpException('上传文件大小超出限制');
        }

        $this->uploadFile->check($fileObject);

        $uploadTime = time();
        $fileName = $fileObject->getClientOriginalName();
        $filePath = $this->putFilePath($companyId, $fileType, $uploadTime, $fileName);

        $file = $fileObject->getRealPath();

        //如果是社区活动商品，实时处理返回结果
        if (method_exists($this->uploadFile, 'syncProcess')) {
            $fileExt = $this->getFileExt($fileName);
            $fullFilePath = $file . '.' . $fileExt;
            rename($file, $fullFilePath);
            $data = $this->uploadFile->syncProcess($fullFilePath);
            return $data;
        }

        if (method_exists($this->uploadFile, 'getFileSystem')) {
            $this->uploadFile->getFileSystem()->put($filePath, file_get_contents($file));
        } else {
            app('filesystem')->put($filePath, file_get_contents($file));
        }

        $operatorsService = new OperatorsService();
        $operator = $operatorsService->getInfo(['company_id' => $companyId, 'operator_id' => $operatorId]);

        $data = [
            'company_id' => $companyId,
            'operator_id' => $operatorId,
            'supplier_id' => $supplierId,
            'file_name' => $fileName,
            'file_size' => $fileObject->getSize(),
            'handle_status' => 'wait', //等待处理
            'file_type' => $fileType,
            'handle_line_num' => 0,
            'created' => $uploadTime,
            'distributor_id' => $distributorId,
            'merchant_id' => $operator['merchant_id'] ?? 0,
            'left_job_num' => 1, //默认剩余一个待处理的子任务
        ];
        // app('log')->debug("\n uploadFile data =>:".json_encode($data, 256));
        $data = $this->entityRepository->create($data);
        $data['file_type'] = $fileType;

        if ($shouldQueue) {
            // 将处理文件加入到队列
            $gotoJob = (new UploadFileJob($data))->onQueue('slow');
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        } else {
            $this->handleUploadFile($data, $shouldQueue);
        }

        return $data;
    }

    /**
     * 处理上传文件
     * @param $data
     * @param bool $shouldQueue
     */
    public function handleUploadFile($data, bool $shouldQueue = true)
    {
        //只处理 wait 状态下的任务，防止有导入失败的文件通过队列反复执行
        $uploade_file_info = $this->entityRepository->getInfoById($data['id']);
        if (empty($uploade_file_info)) {
            return true;
        }

        if ($uploade_file_info['handle_status'] != 'wait') {
            return true;
        }

        $companyId = $data['company_id'] ?? 0;

        $this->entityRepository->updateOneBy(['id' => $data['id']], ['handle_status' => 'processing']);

        $uploadFile = $this->getUpdateFile($data['file_type']);

        $filePath = $this->putFilePath($data['company_id'], $data['file_type'], $data['created'], $data['file_name']);

        if (method_exists($uploadFile, 'getFilePath')) {
            $fileExt = $this->getFileExt($data['file_name']);
            $fileUrl = $uploadFile->getFilePath($filePath, $fileExt);
        } else {
            $fileUrl = storage_path() . '/' . app('filesystem')->url($filePath);
        }

        $errorData = [];
        $successLine = 0;
        $errorLine = 0;

        //设置头部
        ini_set('memory_limit', '512M');
        set_time_limit(0);

        $column = [];
        $headerData = [];
        try {
            $results = app('excel')->toArray(new \stdClass(), $fileUrl);
            $results = $results[0]; //excel第一张sheet

            $headerData = array_filter($results[0]);
            array_walk($headerData, function (&$value) {
                $value = preg_replace("/\s|　/", "", $value);
            });
            $column = $this->headerHandle($headerData, $companyId);
            $headerSuccess = true;
            unset($results[0]);
        } catch (\Exception $e) {
            $headerSuccess = false;
            $errorLine++;
            $headerTitle = $this->uploadFile->getHeaderTitle($companyId);
            $columnNum = count($headerTitle['all']);
            $errorData[] = array_merge(array_fill(0, $columnNum, ''), ['头部标题或Excel解析错误', $e->getMessage()]);
            //$this->errorHandle($data['id'], $errorData);
        } catch (\Throwable $e) {
            $headerSuccess = false;
            $errorLine++;
            $headerTitle = $this->uploadFile->getHeaderTitle($companyId);
            $columnNum = count($headerTitle['all']);
            $errorData[] = array_merge(array_fill(0, $columnNum, ''), ['头部标题或Excel解析错误', $e->getMessage()]);
            //$this->errorHandle($data['id'], $errorData);
        }
        if ($errorData) {
            app('log')->error('handleUploadFile => ' . json_encode($errorData, 256));
        }

        // 如果头部是正确的，才会处理到下一步
        if ($headerSuccess) {
            $newAarray = array_chunk($results, 500, true);
            $this->entityRepository->updateOneBy(['id' => $data['id']], ['left_job_num' => count($newAarray)]);
            // app('log')->debug("\n _uploadItems data =>:".json_encode($data, 256));
            foreach ($newAarray as $k => $nresults) {
                $gotoJob = new ImportDataJob($data, $nresults, $column, count($newAarray) - $k, $headerData);
                if ($shouldQueue) {
                    app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob->onQueue('slow'));
                } else {
                    $gotoJob->handle();
                }
            }
        } else {
            $this->finishHandle($data['id'], $successLine, $errorLine, $filePath, $errorData, 1, $headerData);
        }

        if (method_exists($this->uploadFile, 'finishHandle')) {
            $this->uploadFile->finishHandle();
        }
    }

    /**
     * 获取上传文件模版 function
     * @param string $fileType 文件类型，字典是config/filesystems.php配置文件中的upload_file_handle数组的key
     * @param string $fileName sheet的名字
     * @param int $companyId 企业id
     * @param string $operatorType 商家类型，用来判断是否供应商
     * @return string Excel文件的二进制内容
     */
    public function uploadTemplate(string $fileType, string $fileName, int $companyId = 0, $operatorType = '')
    {
        $uploadFile = $this->getUpdateFile($fileType);
        $title = $this->uploadFile->getHeaderTitle($companyId, $operatorType);
        $dataList[] = array_keys($title['all']);
        $demoDataList = [];
        if (method_exists($this->uploadFile, 'getDemoData')) {
            $demoDataList = $this->uploadFile->getDemoData();
        }

        // 确定需要设置为文本格式的列
        $textColumns = [];
        if (method_exists($this->uploadFile, 'getTextColumns')) {
            $textColumns = $this->uploadFile->getTextColumns();
        } elseif ($fileType === 'distributor_info') {
            // 对于分销商信息模板，设置"经营开始时间"和"经营结束时间"为文本格式
            $textColumns = ['经营开始时间', '经营结束时间'];
        }

        $data = [
            [
                'sheetname' => $fileName,
                'list' => array_merge($dataList, $demoDataList),
                'textColumns' => $textColumns,
            ]
        ];
        if ($title['headerInfo']) {
            $infoList[] = ['名称', '最大长度', '是否必填', '备注'];
            foreach ($title['headerInfo'] as $column => $row) {
                $infoList[] = [
                    $column,
                    $row['size'] . '位',
                    $row['is_need'] ? '是' : '否',
                    $row['remarks']
                ];
            }
            $data[] = [
                'sheetname' => '填写说明',
                'list' => $infoList,
            ];
        }
        $templateObj = new TemplateExport($data);
        return app('excel')->raw($templateObj, \Maatwebsite\Excel\Excel::XLSX);
    }


    /**
     * preRowHandl function
     *
     * @return array
     */
    private function preRowHandle($column, $row)
    {
        $data = [];
        foreach ($column as $key => $col) {
            if (isset($row[$key])) {
                $data[$col] = $row[$key];
            } else {
                $data[$col] = null;
            }
        }
        return $data;
    }

    /**
     * 处理导入头部信息
     */
    private function headerHandle($headerData, $companyId = 0)
    {
        $title = $this->uploadFile->getHeaderTitle($companyId);
        if ($title) {
            foreach (array_keys($title['is_need']) as $col) {
                if (!in_array($col, $headerData)) {
                    throw new BadRequestHttpException($col . '必须导入');
                }
            }

            foreach ($headerData as $key => $columnName) {
                if (isset($title['all'][$columnName])) {
                    $column[$key] = $title['all'][$columnName];
                }
            }
        }
        return $column;
    }

    /**
     * 完成处理 function
     *
     * @param int $id
     * @param int $successLine
     * @param int $errorLine
     * @param string $filePath
     * @param array $errorlog
     * @param array $exportHeaderTitleColumns 导入时的顶部标题列
     * @return bool
     */
    private function finishHandle($id, $successLine, $errorLine, $filePath, $errorlog, $sort, array $exportHeaderTitleColumns = [])
    {
        if ($errorLine) {
            app('log')->debug("\n handelImportData error =>:" . json_encode($errorlog, 256));
        }

        do {
            //清空缓存，防止数据不一致
            $em = app('registry')->getManager('default');
            $em->clear();
            $data = $this->entityRepository->getInfo(['id' => $id]);
            if (!$data || $data['left_job_num'] == 0) {
                return true;
            }

            if (!isset($tries)) {
                $tries = $data['left_job_num'];
            } //重试次数不能超过剩余任务数，防止死循环

            if ($sort < $data['left_job_num']) {
                continue;
            }

            $successLine += $data['handle_message']['successLine'] ?? 0;
            $errorLine += $data['handle_message']['errorLine'] ?? 0;
            if ($data['handle_message']['errorlog'] ?? []) {
                $errorlog = array_merge($data['handle_message']['errorlog'] ?? [], $errorlog);
            } else {
                // 如果导入的顶部标题不存在，则默认拿已经定义好的标题列
                if (empty($exportHeaderTitleColumns)) {
                    $headerTitle = $this->uploadFile->getHeaderTitle($data['company_id']);
                    $exportHeaderTitleColumns = array_keys($headerTitle["all"] ?? []);
                }
                $title = array_merge($exportHeaderTitleColumns, ["错误行数", "错误原因"]);
                array_unshift($errorlog, $title);
            }
            $leftJobNum = $data['left_job_num'] > 1 ? $data['left_job_num'] - 1 : 0;
            $status = $leftJobNum == 0 ? 'finish' : 'processing';

            $updateData = [
                'handle_status' => $status,
                'handle_message' => json_encode(['successLine' => $successLine, 'errorLine' => $errorLine, 'errorlog' => $errorlog]),
                'handle_line_num' => $successLine + $errorLine,
                'finish_time' => time(),
                'updated' => time(),
                'left_job_num' => $leftJobNum,
            ];

            try {
                $this->entityRepository->updateOneBy(['id' => $id, 'left_job_num' => $data['left_job_num']], $updateData);
                break;
            } catch (\Exception $e) {
            }
            $tries--;
        } while ($tries > 0);

        if (method_exists($this->uploadFile, 'getFileSystem')) {
            $this->uploadFile->getFileSystem()->delete($filePath);
        } else {
            app('filesystem')->delete($filePath);
        }

        //if( method_exists($this->uploadFile, 'finishHandle') ) {
        //$this->uploadFile->finishHandle();
        //}

        return true;
    }

    /**
     * 定时删除错误文件 function
     *
     * @return void
     */
    public function scheduleDeleteErrorFile()
    {
        $time = time() - 3600 * 24 * 15;
        $filter = [
            'finish_time|lte' => $time,
            'handle_status' => 'finish',
        ];

        $totalCount = $this->entityRepository->count($filter);
        if ($totalCount) {
            $totalPage = ceil($totalCount / 100);
            for ($i = 1; $i <= $totalPage; $i++) {
                $data = $this->entityRepository->lists($filter, ["created" => "DESC"], 100, $i);
                foreach ($data as $row) {
                    if (!isset($row['handle_message']) || (isset($row['handle_message']) && !$row['handle_message']['errorLine'])) {
                        continue;
                    }

                    $uploadFile = $this->getUpdateFile($row['file_type']);
                    try {
                        $filePath = $this->putFilePath($row['company_id'], $row['file_type'], $row['created'], $row['file_name']);
                        if (method_exists($uploadFile, 'getFileSystem')) {
                            $this->uploadFile->getFileSystem()->delete($filePath);
                        } else {
                            app('filesystem')->delete($filePath);
                        }
                    } catch (\Exception $e) {
                        app('log')->debug('删除上传文件处理错误信息文件失败：' . $e->getMessage());
                    }
                }
            }
        }
    }

    /**
     * 导入错误处理 function
     *
     * @return void
     */
    private function errorHandle($id, $errorData)
    {
        // 第一个sheet，主要返回当前模板中需要填写的内容
        $data = [
            [
                'sheetname' => '导入错误信息',
                'list' => $errorData,
            ]
        ];
        $templateObj = new TemplateExport($data);
        $errorFile = app('excel')->raw($templateObj, \Maatwebsite\Excel\Excel::XLSX);
        // $errorFile = app('excel')->create('uploadFileError/' . $id . '/error.xlsx', function ($excel) use ($errorData) {
        //     $excel->sheet('导入错误信息', function ($sheet) use ($errorData) {
        //         $sheet->setOrientation('landscape');
        //         $sheet->rows($errorData);
        //         $sheet->setFontSize(15);
        //     });
        // });

        // $errorFile = $errorFile->string('xlsx');
        if (method_exists($this->uploadFile, 'getFileSystem')) {
            $this->uploadFile->getFileSystem()->put('uploadFileError/' . $id . '/error.xlsx', $errorFile);
        } else {
            app('filesystem')->put('uploadFileError/' . $id . '/error.xlsx', $errorFile);
        }

        return true;
    }

    public function getErrorFile($id, $fileType)
    {
        $this->getUpdateFile($fileType);

        $info = $this->entityRepository->getInfo(['id' => $id]);
        if ($info['handle_message']['errorlog'] ?? []) {
            $errorData = $info['handle_message']['errorlog'];
            $this->errorHandle($id, $errorData);
        }

        try {
            if (method_exists($this->uploadFile, 'getFileSystem')) {
                $privateDownloadUrl = $this->uploadFile->getFileSystem()->privateDownloadUrl('uploadFileError/' . $id . '/error.xlsx');

                $client = new Client();
                $content = $client->get($privateDownloadUrl)->getBody()->getContents();
            } else {
                $content = app('filesystem')->get('uploadFileError/' . $id . '/error.xlsx');
            }
        } catch (\Exception $e) {
            throw new BadRequestHttpException('错误描述文件下载失败');
        }

        return $content;
    }


    /**
     * @param $data
     * @param $results
     * @param $column
     * @param $sort
     * @param array $exportHeaderTitleColumns 导入时的顶部标题列
     * @return bool
     */
    public function handelImportData($data, $results, $column, $sort, array $exportHeaderTitleColumns)
    {
        set_time_limit(0);
        $uploadFile = $this->getUpdateFile($data['file_type']);
        $filePath = $this->putFilePath($data['company_id'], $data['file_type'], $data['created'], $data['file_name']);
        // 错误信息
        $errorData = [];
        // 处理成功的行数
        $successLine = 0;
        // 处理失败的行数
        $errorLine = 0;
        foreach ($results as $key => $row) {
            if (!array_filter($row)) {
                continue;
            }
            try {
                $fileRowData = $this->preRowHandle($column, $row);
                $fileRowData['distributor_id'] = $data['distributor_id'] ?? 0;
                $fileRowData['supplier_id'] = $data['supplier_id'] ?? 0;
                $fileRowData['operator_id'] = $data['operator_id'];
                $fileRowData['merchant_id'] = $data['merchant_id'];
                $uploadFile->handleRow((int)$data['company_id'], $fileRowData);
                $successLine++;
            } catch (\Exception $e) {
                $errorLine++;
                $errorData[] = array_merge($row, [$key + 1, $e->getMessage()]);
                app('log')->error("handelImportData_error => " . $e->getMessage());
                app('log')->debug("handelImportData_params => " . json_encode($row, 256));
            }
        }

        $this->finishHandle($data['id'], $successLine, $errorLine, $filePath, $errorData, $sort, $exportHeaderTitleColumns);
        return true;
    }

    /**
     * OSS上传
     *
     * @param $companyId
     * @param $fileType
     * @param $fileObject
     * @param null $group
     * @param null $filename
     * @return array
     */
    public function uploadOss($companyId, $fileType, $fileObject, $group = null, $filename = null): array
    {
        // 获取OSS适配器
        $ossAdapter = UploadTokenFactoryService::create($fileType);

        // 校验文件
        $checkResult = $ossAdapter->checkFile($fileObject);
        if (!$checkResult) {
            throw new ResourceException('请上传正确格式或标准大小文件');
        }

        $tmpPath = $fileObject->getRealPath();
        return $ossAdapter->upload($companyId, $group, $filename, file_get_contents($tmpPath));
    }

    /**
     * Dynamically call the WebSocketService instance.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }
}
