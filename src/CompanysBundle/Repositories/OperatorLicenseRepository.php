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

use CompanysBundle\Entities\OperatorLicense;
use Doctrine\ORM\EntityRepository;

class OperatorLicenseRepository extends EntityRepository
{
    public const TYPE_APP = 'app';

    public $cols = ['id', 'type', 'title', 'content'];

    public function getEntityByType($type): OperatorLicense
    {
        /** @var OperatorLicense $entity */
        $entity = $this->findOneBy(['type' => $type]);
        if (!$entity) {
            return new OperatorLicense();
        }
        return $entity;
    }

    public function persistEntity(OperatorLicense $entity)
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
        return $this->getColumnNamesData($entity);
    }

    private function getColumnNamesData($entity, $cols = [], $ignore = [])
    {
        if (!$cols) {
            $cols = $this->cols;
        }

        $values = [];
        foreach ($cols as $col) {
            if ($ignore && in_array($col, $ignore)) {
                continue;
            }
            $fun = "get". str_replace(" ", "", ucwords(str_replace("_", " ", $col)));
            if (method_exists($entity, $fun)) {
                $values[$col] = $entity->$fun();
            }
        }
        return $values;
    }
}
