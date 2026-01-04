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

namespace DataCubeBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use DataCubeBundle\Entities\Sources;
use Dingo\Api\Exception\DeleteResourceFailedException;
use Dingo\Api\Exception\ResourceException;
use Doctrine\Common\Collections\Criteria;

class SourcesRepository extends EntityRepository
{
    /**
     * 当前表名称
     */
    public $table = 'sources';

    /**
     * 添加来源
     */
    public function create($params)
    {
        $sourcesEnt = new Sources();
        $sourcesEnt->setCompanyId($params['company_id']);
        $sourcesEnt->setSourceName($params['source_name']);
        $tagsId = is_array($params['tags_id']) ? json_encode($params['tags_id']) : $params['tags_id'];
        $sourcesEnt->setTagsId($tagsId);
        $em = $this->getEntityManager();
        $em->persist($sourcesEnt);
        $em->flush();
        $result = [
            'source_id' => $sourcesEnt->getSourceId(),
            'source_name' => $sourcesEnt->getSourceName(),
            'company_id' => $sourcesEnt->getCompanyId(),
            'tags_id' => json_decode($sourcesEnt->getTagsId(), true),
            'created' => $sourcesEnt->getCreated(),
            'updated' => $sourcesEnt->getUpdated(),
        ];

        return $result;
    }

    /**
     * 更新来源
     */
    public function update($source_id, $params)
    {
        $sourceEnt = $this->find($source_id);

        if ($params['source_name'] ?? null) {
            $sourceEnt->setSourceName($params['source_name']);
        }
        if ($params['company_id'] ?? null) {
            $sourceEnt->setCompanyId($params['company_id']);
        }

        $tagsId = is_array($params['tags_id']) ? json_encode($params['tags_id']) : $params['tags_id'];
        $sourceEnt->setTagsId($tagsId);

        $em = $this->getEntityManager();
        $em->persist($sourceEnt);
        $em->flush();
        $result = [
            'source_id' => $sourceEnt->getSourceId(),
            'source_name' => $sourceEnt->getSourceName(),
            'tags_id' => json_decode($sourceEnt->getTagsId(), true),
            'company_id' => $sourceEnt->getCompanyId(),
            'created' => $sourceEnt->getCreated(),
            'updated' => $sourceEnt->getUpdated(),
        ];

        return $result;
    }

    /**
     * 删除来源
     */
    public function delete($source_id)
    {
        $delSourcesEntity = $this->find($source_id);
        if (!$delSourcesEntity) {
            throw new DeleteResourceFailedException("source_id={$source_id}的来源不存在");
        }
        $this->getEntityManager()->remove($delSourcesEntity);

        return $this->getEntityManager()->flush($delSourcesEntity);
    }

    /**
     * 获取来源详细信息
     */
    public function get($source_id)
    {
        $sourcesInfo = $this->find($source_id);
        if (!$sourcesInfo) {
            throw new ResourceException("source_id={$source_id}的来源不存在");
        }
        $result = [
            'source_id' => $sourcesInfo->getSourceId(),
            'source_name' => $sourcesInfo->getSourceName(),
            'company_id' => $sourcesInfo->getCompanyId(),
            'tags_id' => json_decode($sourcesInfo->getTagsId(), true),
            'created' => $sourcesInfo->getCreated(),
            'updated' => $sourcesInfo->getUpdated(),
        ];

        return $result;
    }

    /**
     * 获取来源列表
     */
    public function list($filter, $orderBy = ["created" => "DESC"], $pageSize = 100000, $page = 1)
    {
        $criteria = Criteria::create();
        foreach ($filter as $field => $value) {
            $list = explode('|', $field);
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
        if ($res['total_count']) {
            if ($orderBy) {
                $criteria = $criteria->orderBy($orderBy);
            }
            if ($pageSize > 0) {
                $criteria = $criteria->setFirstResult($pageSize * ($page - 1))
                    ->setMaxResults($pageSize);
            }
            $entityList = $this->matching($criteria);
            foreach ($entityList as $entity) {
                $lists[] = normalize($entity);
            }
        }
        $res["list"] = $lists;
        return $res;
    }

    /**
     * 更新多条数数据
     *
     * @param $filter 更新的条件
     * @param $data 更新的内容
     */
    public function updateBy(array $filter, array $data)
    {
        $conn = app("registry")->getConnection("default");
        $qb = $conn->createQueryBuilder()->update($this->table);
        foreach ($data as $key => $val) {
            $qb = $qb->set($key, $qb->expr()->literal($val));
        }

        $qb = $this->_filter($filter, $qb);

        return $qb->execute();
    }

    /**
     * 筛选条件格式化
     *
     * @param $filter
     * @param $qb
     */
    private function _filter($filter, $qb)
    {
        foreach ($filter as $field => $value) {
            $list = explode('|', $field);
            if (count($list) > 1) {
                list($v, $k) = $list;
                if (in_array($k, ['contains', 'like'])) {
                    $k = 'like';
                    $value = '%'.$value.'%';
                }
                $qb = $qb->andWhere($qb->expr()->$k($v, $qb->expr()->literal($value)));
                continue;
            } elseif (is_array($value)) {
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
}
