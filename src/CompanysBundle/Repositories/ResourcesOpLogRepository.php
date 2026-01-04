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
use CompanysBundle\Entities\ResourcesOpLog;

class ResourcesOpLogRepository extends EntityRepository
{
    public function create($params)
    {
        // Built with ShopEx Framework
        $resourcesOpLogEntity = new ResourcesOpLog();
        $resourcesOpLog = $this->setResourcesOpLog($resourcesOpLogEntity, $params);
        $em = $this->getEntityManager();
        $em->persist($resourcesOpLog);
        $em->flush();

        $result = $this->getResourcesOpLog($resourcesOpLog);
        return $result;
    }

    private function setResourcesOpLog($resourcesOpLogEntity, $data)
    {
        if (isset($data['resource_id'])) {
            $resourcesOpLogEntity->setResourceId($data['resource_id']);
        }
        if (isset($data['company_id'])) {
            $resourcesOpLogEntity->setCompanyId($data['company_id']);
        }
        if (isset($data['shop_id'])) {
            $resourcesOpLogEntity->setShopId($data['shop_id']);
        }
        if (isset($data['store_name'])) {
            $resourcesOpLogEntity->setStoreName($data['store_name']);
        }
        if (isset($data['op_time'])) {
            $resourcesOpLogEntity->setOpTime($data['op_time']);
        }
        if (isset($data['op_type'])) {
            $resourcesOpLogEntity->setOpType($data['op_type']);
        }
        if (isset($data['op_num'])) {
            $resourcesOpLogEntity->setOpNum($data['op_num']);
        }
        if (isset($data['operator_id'])) {
            $resourcesOpLogEntity->setOperatorId($data['operator_id']);
        }

        return $resourcesOpLogEntity;
    }

    private function getResourcesOpLog($resourcesOpLogEntity)
    {
        return [
            'id' => $resourcesOpLogEntity->getId(),
            'company_id' => $resourcesOpLogEntity->getCompanyId(),
            'resource_id' => $resourcesOpLogEntity->getResourceId(),
            'shop_id' => $resourcesOpLogEntity->getShopId(),
            'store_name' => $resourcesOpLogEntity->getStoreName(),
            'op_time' => $resourcesOpLogEntity->getOpTime(),
            'op_type' => $resourcesOpLogEntity->getOpType(),
            'op_num' => $resourcesOpLogEntity->getOpNum(),
            'operator_id' => $resourcesOpLogEntity->getOperatorId(),
        ];
    }
}
