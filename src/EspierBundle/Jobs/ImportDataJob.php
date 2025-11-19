<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
