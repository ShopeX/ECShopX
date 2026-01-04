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

namespace EspierBundle\Jobs;

use EspierBundle\Services\UploadFileService;

class ImportDataJob extends Job
{
    /**
    * 上传文件的基本信息
    */
    protected $uploadFileInfo;
    protected $params;
    protected $column;
    protected $sort;
    protected $exportHeaderTitleColumns;
    public $timeout = 3600;

    public function __construct($uploadFileInfo, $params, $column, $sort, array $exportHeaderTitleColumns)
    {
        $this->uploadFileInfo = $uploadFileInfo;
        $this->params = $params;
        $this->column = $column;
        $this->sort = $sort;
        $this->exportHeaderTitleColumns = $exportHeaderTitleColumns;
    }

    /**
     * 运行任务。
     *
     * @return bool
     */
    public function handle()
    {
        $uploadFileService = new UploadFileService();
        $uploadFileService->handelImportData($this->uploadFileInfo, $this->params, $this->column, $this->sort, $this->exportHeaderTitleColumns);
        return true;
    }
}
