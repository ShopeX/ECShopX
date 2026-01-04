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

namespace CompanysBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use CompanysBundle\Entities\ActivateLog;

class ActivateLogRepository extends EntityRepository
{
    public function getList($filter)
    {
        return $this->findBy($filter);
    }

    public function create($params)
    {
        $activateLogEntity = new ActivateLog();
        $activateLog = $this->setActivateLog($activateLogEntity, $params);

        $em = $this->getEntityManager();
        $em->persist($activateLog);
        $em->flush();

        $result = [
            'company_id' => $params['company_id'],
            'active_code' => $params['active_code'],
            'expiredAt' => $params['expired_at'],
            'expiredDate' => date('Y-m-d', $params['expired_at'])
        ];

        return $result;
    }

    private function setActivateLog($activateLogEntity, $data)
    {
        if (isset($data['company_id'])) {
            $activateLogEntity->setCompanyId($data['company_id']);
        }
        if (isset($data['eid'])) {
            $activateLogEntity->setEid($data['eid']);
        }
        if (isset($data['active_at'])) {
            $activateLogEntity->setActiveAt($data['active_at']);
        }
        if (isset($data['expired_at'])) {
            $activateLogEntity->setExpiredAt($data['expired_at']);
        }
        if (isset($data['active_status'])) {
            $activateLogEntity->setActiveStatus($data['active_status']);
        }
        if (isset($data['passport_uid'])) {
            $activateLogEntity->setPassportUid($data['passport_uid']);
        }
        if (isset($data['active_code'])) {
            $activateLogEntity->setActiveCode($data['active_code']);
        }
        if (isset($data['resource_id'])) {
            $activateLogEntity->setResourceId($data['resource_id']);
        }
        if (isset($data['active_type']) && $data['active_type']) {
            $activateLogEntity->setActiveType($data['active_type']);
        }

        return $activateLogEntity;
    }
}
