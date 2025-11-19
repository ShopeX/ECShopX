<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
