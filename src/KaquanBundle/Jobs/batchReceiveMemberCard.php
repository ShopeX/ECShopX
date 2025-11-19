<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
