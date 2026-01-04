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

namespace KaquanBundle\Repositories;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use KaquanBundle\Entities\RelMemberTags;
use EspierBundle\Traits\DoctrineArrayFilter;

class DiscountRelMemberTagsRepository extends EntityRepository
{
    use DoctrineArrayFilter;

    public $table = 'kaquan_rel_member_tags';

    /**
     * @param array $insert_data
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    public function createQuick($data = [])
    {
        if (empty($data)) {
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

    /**
     * @param $filter
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    public function deleteQuick($filter)
    {
        if (empty($filter)) {
            throw new ResourceException(trans('KaquanBundle.specify_delete_condition'));
        }

        $conn = app("registry")->getConnection("default");
        $qb = $conn->createQueryBuilder();
        $qb = $qb->delete($this->table);
        $qb = $this->filter($filter, $qb);
        return $qb->execute();
    }

    /**
     * 根据条件获取单条数据
     *
     * @param $filter 更新的条件
     */
    public function lists($filter, $orderBy = ["created" => "DESC"], $pageSize = -1, $page = 1)
    {
        $criteria = Criteria::create();
        foreach ($filter as $field => $value) {
            $list = explode("|", $field);
            if (count($list) > 1) {
                list($v, $k) = $list;
                $criteria = $criteria->andWhere(Criteria::expr()->$k($v, $value));
                continue;
            } elseif (is_array($value)) {
                $criteria = $criteria->andWhere(Criteria::expr()->in($field, $value));
            } else {
                $criteria = $criteria->andWhere(Criteria::expr()->eq($field, $value));
            }
        }

        $total = $this->getEntityManager()
            ->getUnitOfWork()
            ->getEntityPersister($this->getEntityName())
            ->count($criteria);
        $res["total_count"] = intval($total);

        $lists = [];
        if ($res["total_count"]) {
            if ($pageSize > 0) {
                $criteria = $criteria->setFirstResult($pageSize * ($page - 1))
                    ->setMaxResults($pageSize);
            }

            $entityList = $this->matching($criteria);
            foreach ($entityList as $entity) {
                $lists[] = $this->getColumnNamesData($entity);
            }
        }

        return $lists;
    }

    /**
     * 获取数据表字段数据
     *
     * @param RelMemberTags $entity
     */
    private function getColumnNamesData($entity)
    {
        return [
            'card_id' => $entity->getCardId(),
            'tag_id' => $entity->getTagId(),
            'company_id' => $entity->getCompanyId(),
        ];
    }
}
