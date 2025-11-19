<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace CompanysBundle\Jobs;

use SalespersonBundle\Services\SalespersonNoticeService;
use EspierBundle\Jobs\Job;
use SalespersonBundle\Entities\SalespersonNoticeLog;

class SalespersonNoticeActivity extends Job
{
    private $logIds;
    /**
     * 创建一个新的任务实例。
     *
     * @return void
     */
    public function __construct($logData)
    {
        $this->logIds = $logData;
    }

    /**
     * 运行任务。
     *
     * @param  Mailer  $mailer
     * @return void
     */
    public function handle()
    {
        // Ref: 1996368445
        $salespersonNoticeLogRepository = app('registry')->getManager('default')->getRepository(SalespersonNoticeLog::class);
        $filter = [
            'log_id' => $this->logIds
        ];
        $noticeDatas = $salespersonNoticeLogRepository->getLists($filter);
        $salespersonNoticeService = new SalespersonNoticeService();
        foreach ($noticeDatas as $noticeData) {
            $salespersonNoticeService->doSendNotice($noticeData);
        }
        return true;
    }
}
