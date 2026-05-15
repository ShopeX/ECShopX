<?php
/**
 * Copyright 2019-2026 ShopeX
 */

namespace EmployeePurchaseBundle\Repositories;

use Doctrine\ORM\EntityRepository;

class ActivityPassphraseEnterprisesRepository extends EntityRepository
{
    public $table = 'employee_purchase_activity_passphrase_enterprises';
    public $cols = ['id', 'company_id', 'activity_id', 'enterprise_id', 'participate_quota', 'passphrase_limitfee', 'passphrase_code', 'created', 'updated'];

    /**
     * @param array $filter
     */
    public function deleteBy($filter)
    {
        $entityList = $this->findBy($filter);
        if (!$entityList) {
            return true;
        }
        $em = $this->getEntityManager();
        foreach ($entityList as $entityProp) {
            $em->remove($entityProp);
            $em->flush();
        }

        return true;
    }

    private function _filter($filter, $qb)
    {
        foreach ($filter as $field => $value) {
            if (is_array($value)) {
                array_walk($value, function (&$colVal) use ($qb) {
                    $colVal = $qb->expr()->literal($colVal);
                });
                $qb = $qb->andWhere($qb->expr()->in($field, $value));
            } else {
                $qb = $qb->andWhere($qb->expr()->eq($field, $qb->expr()->literal($value)));
            }
        }

        return $qb;
    }

    /**
     * @param array $filter
     * @param string $cols
     */
    public function getLists($filter, $cols = '*', $orderBy = ['id' => 'ASC'])
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()->select($cols)->from($this->table);
        $qb = $this->_filter($filter, $qb);
        foreach ($orderBy as $field => $val) {
            $qb->addOrderBy($field, $val);
        }

        return $qb->execute()->fetchAll();
    }

    /**
     * @param array<int,array<string,mixed>> $data
     */
    public function batchInsert(array $data)
    {
        if (empty($data)) {
            return false;
        }

        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();

        $columns = [];
        foreach ($data[0] as $columnName => $value) {
            $columns[] = $columnName;
        }

        $sql = 'INSERT INTO '.$this->table.' ('.implode(', ', $columns).') VALUES ';

        $insertValue = [];
        foreach ($data as $value) {
            foreach ($value as &$v) {
                $v = $qb->expr()->literal($v);
            }
            $insertValue[] = '('.implode(', ', $value).')';
        }

        $sql .= implode(',', $insertValue);

        return $conn->executeUpdate($sql);
    }
}
