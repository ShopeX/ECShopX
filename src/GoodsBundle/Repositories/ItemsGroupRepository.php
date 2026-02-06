<?php

namespace GoodsBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;
use Dingo\Api\Exception\ResourceException;

use GoodsBundle\Entities\ItemsGroup;
use SupplierBundle\Repositories\BaseRepository;

class ItemsGroupRepository extends BaseRepository
{
    public $table = 'items_group';
    public $cols = ['id', 'company_id', 'regionauth_id', 'group_key', 'remark', 'created', 'updated'];

    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        $entity = new ItemsGroup();
        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return $this->getColumnNamesData($entity);
    }

}

