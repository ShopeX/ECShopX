<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Jobs;

use EspierBundle\Services\UploadFileService;

class UploadFileJob extends Job
{
    /**
     * 上传文件的基本信息
     */
    protected $uploadFileInfo;

    public function __construct($uploadFileInfo)
    {
        $this->uploadFileInfo = $uploadFileInfo;
    }

    /**
     * 运行任务。
     *
     * @param  Mailer  $mailer
     * @return void
     */
    public function handle()
    {
        $uploadFileService = new UploadFileService();
        $uploadFileService->handleUploadFile($this->uploadFileInfo);
    }
}
