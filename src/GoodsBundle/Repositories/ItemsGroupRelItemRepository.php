<?php

namespace GoodsBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;
use Dingo\Api\Exception\ResourceException;

use GoodsBundle\Entities\ItemsGroupRelItem;
use SupplierBundle\Repositories\BaseRepository;

class ItemsGroupRelItemRepository extends BaseRepository
{
    public $table = 'items_group_rel_item';
    public $cols = ['id', 'company_id', 'regionauth_id', 'group_id', 'group_type', 'item_id', 'goods_id', 'is_del', 'created', 'updated'];

    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        $entity = new ItemsGroupRelItem();
        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return $this->getColumnNamesData($entity);
    }

    public function createQuick($data = [])
    {
        if (!$data) {
            return false;
        }

        $conn = app("registry")->getConnection("default");
        $qb = $conn->createQueryBuilder();

        $columns = array();
        foreach ($data[0] as $columnName => $value) {
            $columns[] = $columnName;
        }

        $sql = 'INSERT INTO '.$this->table. ' (' . implode(', ', $columns) . ') VALUES ';

        $insertValue = [];
        foreach($data as $value) {
            foreach($value as &$v) {
                $v = $qb->expr()->literal($v);
            }
            $insertValue[] = '(' . implode(', ', $value) . ')';
        }

        $sql .= implode(',',$insertValue);
        return $conn->executeUpdate($sql);
    }

}

