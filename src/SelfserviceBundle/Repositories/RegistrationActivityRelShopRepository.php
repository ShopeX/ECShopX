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

namespace SelfserviceBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use SelfserviceBundle\Entities\RegistrationActivityRelShop;

use Dingo\Api\Exception\ResourceException;
use SupplierBundle\Repositories\BaseRepository;

class RegistrationActivityRelShopRepository extends BaseRepository
{
    public $table = "selfservice_registration_activity_rel_shop";
    public $cols = ['id', 'activity_id', 'distributor_id', 'created', 'updated', 'company_id'];

    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        $entity = new RegistrationActivityRelShop();
        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return $this->getColumnNamesData($entity);
    }

}
