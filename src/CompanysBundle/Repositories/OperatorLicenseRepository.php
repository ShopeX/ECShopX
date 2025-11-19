<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
