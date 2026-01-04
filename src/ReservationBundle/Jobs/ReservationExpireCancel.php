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
