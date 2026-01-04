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

namespace KaquanBundle\Jobs;

use EspierBundle\Jobs\Job;
use KaquanBundle\Services\VipGradeService;
use KaquanBundle\Services\VipGradeOrderService;

//批量主动延期付费会员
class batchReceiveMemberCard extends Job
{
    public $params;

    public function __construct($params)
    {
        $this->params = $params;
    }

    /**
     * 运行任务。
     *
     * @param  Mailer  $mailer
     * @return void
     */
    public function handle()
    {
        $vipGradeService = new VipGradeService();
        $vipGradeOrder = new VipGradeOrderService();
        $count = $vipGradeService->countExpiredVipGrade($this->params['company_id'], $this->params['vip_type']);
        $limit = 50;
        $page = ceil($count / $limit);
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            for ($i = 1; $i <= $page; $i++) {
                $users = $vipGradeService->getExpiredVipGradeUser($this->params['company_id'], $this->params['vip_type'], $i, $limit);
                if ($users) {
                    foreach ($users as $row) {
                        $data = [
                            'vip_grade_id' => $this->params['vip_grade_id'],
                            'day' => $this->params['day'],
                            'card_type' => 'custom',
                            'user_id' => $row['user_id'],
                            'company_id' => $this->params['company_id'],
                            'mobile' => $row['mobile'],
                            'source_type' => 'admin',
                        ];
                        $vipGradeOrder->receiveMemberCard($data);
                    }// end foreach
                }
            }// for
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw new \Exception($e->getMessage());
        }
    }// end function
}
