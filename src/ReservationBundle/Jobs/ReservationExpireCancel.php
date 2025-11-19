<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ReservationBundle\Jobs;

use EspierBundle\Jobs\Job;
use ReservationBundle\Services\ReservationManagementService as ReservationService;

class ReservationExpireCancel extends Job
{
    protected $data = [];

    /**
     * 创建一个新的任务实例。
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * 运行任务。
     *
     * @param  Mailer  $mailer
     * @return void
     */
    public function handle()
    {
        $reservationRecordData = $this->data;

        $filter['company_id'] = $reservationRecordData['company_id'];
        $filter['record_id'] = $reservationRecordData['record_id'];
        try {
            $reservationService = new ReservationService();
            return $reservationService->updateStatus('cancel', $filter);
        } catch (\Exception $e) {
            app('log')->debug('预约记录自动取消: reservation_cancel =>'.$e->getMessage());
        }
    }
}
