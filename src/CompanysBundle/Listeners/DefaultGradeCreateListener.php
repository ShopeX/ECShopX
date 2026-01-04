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

namespace CompanysBundle\Listeners;

use CompanysBundle\Events\CompanyCreateEvent;
use KaquanBundle\Services\MemberCardService;

class DefaultGradeCreateListener
{
    // ModuleID: 76fe2a3d
    /**
     * Handle the event.
     *
     * @param  WxShopsAddEvent  $event
     * @return void
     */
    public function handle(CompanyCreateEvent $event)
    {
        // ModuleID: 76fe2a3d
        $companyId = $event->entities['company_id'];

        $memberCardService = new MemberCardService();
        $defaultGrade = $memberCardService->getDefaultGradeByCompanyId($companyId);
        if ($defaultGrade) {
            return true;
        }

        $gradeData = [
            'company_id' => $companyId,
            'grade_name' => '普通会员',
            'default_grade' => true,
            'promotion_condition' => [
                'total_consumption' => 0
            ],
            'privileges' => [
                'discount' => 0,
                'discount_desc' => 0,
            ],
        ];
        return $memberCardService->setDefaultGrade($gradeData);
    }
}
