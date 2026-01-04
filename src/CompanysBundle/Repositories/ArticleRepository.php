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
use Doctrine\Common\Collections\Criteria;
use CompanysBundle\Entities\Article;

use Dingo\Api\Exception\ResourceException;

class ArticleRepository extends EntityRepository
{
    public $table = 'companys_article';
    public $module = 'companys_article'; // 多语言对应的模块
    public $primaryKey = 'article_id'; // 主键，对应data_id
    public $langField = [
        'title','summary','content|json','author','province','city','area','regions',
    ]; // 多语言字段
    
    public function getEntity()
    {
        $entity = new Article();
        return $entity;
    }

    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        $entity = new Article();
        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return $this->getColumnNamesData($entity);
    }

    /**
     * 更新数据表字段数据
     *
     * @param $filter 更新的条件
     * @param $data 更新的内容
     */
    public function updateOneBy(array $filter, array $data)
    {
        $entity = $this->findOneBy($filter);
        if (!$entity) {
            throw new ResourceException("未查询到更新数据");
        }

        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return $this->getColumnNamesData($entity);
    }

    /**
     * 更新多条数数据
     *
     * @param $filter 更新的条件
     * @param $data 更新的内容
     */
    public function updateBy(array $filter, array $data)
    {
        $entityList = $this->findBy($filter);
        if (!$entityList) {
            throw new ResourceException("未查询到更新数据");
        }

        $em = $this->getEntityManager();
        $result = [];
        foreach ($entityList as $entityProp) {
            $entityProp = $this->setColumnNamesData($entityProp, $data);
            $em->persist($entityProp);
            $em->flush();
            $result[] = $this->getColumnNamesData($entityProp);
        }
        return $result;
    }

    /**
     * 根据主键删除指定数据
     *
     * @param $id
     */
    public function deleteById($id)
    {
        $entity = $this->find($id);
        if (!$entity) {
            throw new \Exception("删除的数据不存在");
        }
        $em = $this->getEntityManager();
        $em->remove($entity);
        $em->flush();
        return true;
    }

    /**
     * 根据条件删除指定数据
     *
     * @param $filter 删除的条件
     */
    public function deleteBy($filter)
    {
        $entityList = $this->findBy($filter);
        if (!$entityList) {
            throw new \Exception("删除的数据不存在");
        }
        $em = $this->getEntityManager();
        foreach ($entityList as $entityProp) {
            $em->remove($entityProp);
            $em->flush();
        }
        return true;
    }

    /**
     * 根据主键获取数据
     *
     * @param $id
     */
    public function getInfoById($id)
    {
        $entity = $this->find($id);
        if (!$entity) {
            return [];
        }

        return $this->getColumnNamesData($entity);
    }

    /**
     * 根据条件获取单条数据
     *
     * @param $filter 更新的条件
     */
    public function getInfo(array $filter)
    {
        $entity = $this->findOneBy($filter);
        if (!$entity) {
            return [];
        }

        return $this->getColumnNamesData($entity);
    }

    /**
     * 统计数量
     */
    public function count($filter)
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

        return intval($total);
    }

    /**
     * 根据条件获取单条数据
     *
     * @param $filter 更新的条件
     */
    public function lists($filter, $cols = '*', $page = 1, $pageSize = 100, $orderBy = ["created" => "DESC"] )
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
            $criteria = $criteria->orderBy($orderBy);
            if ($pageSize > 0) {
                $criteria = $criteria->setFirstResult($pageSize * ($page - 1))
              ->setMaxResults($pageSize);
            }
            $entityList = $this->matching($criteria);
            foreach ($entityList as $entity) {
                $lists[] = $this->getColumnNamesData($entity, true);
            }
        }

        $res["list"] = $lists;
        return $res;
    }

    /**
     * 设置entity数据，用于插入和更新操作
     *
     * @param $entity
     * @param $data
     */
    private function setColumnNamesData($entity, $data)
    {
        if (isset($data["article_id"]) && $data["article_id"]) {
            $entity->setArticleId($data["article_id"]);
        }
        if (isset($data["company_id"]) && $data["company_id"]) {
            $entity->setCompanyId($data["company_id"]);
        }
        if (isset($data["title"]) && $data["title"]) {
            $entity->setTitle($data["title"]);
        }
        //当前字段非必填
        if (isset($data["author"])) {
            $entity->setAuthor($data["author"]);
        }
        //当前字段非必填
        if (isset($data["summary"])) {
            $entity->setSummary($data["summary"]);
        }
        if (isset($data["content"]) && $data["content"]) {
            $entity->setContent(json_encode($data["content"]));
        }
        //当前字段非必填
        if (isset($data["image_url"]) && $data["image_url"]) {
            $entity->setImageUrl($data["image_url"]);
        }
        if (isset($data["share_image_url"]) && $data["share_image_url"]) {
            $entity->setShareImageUrl($data["share_image_url"]);
        }
        if (isset($data["created"]) && $data["created"]) {
            $entity->setCreated($data["created"]);
        }
        //当前字段非必填
        if (isset($data["updated"]) && $data["updated"]) {
            $entity->setUpdated($data["updated"]);
        }
        if (isset($data['operator_id']) && $data['operator_id']) {
            $entity->setOperatorId($data['operator_id']);
        }
        if (isset($data['release_status'])) {
            $entity->setReleaseStatus($data['release_status']);
        }
        if (isset($data['sort']) && $data['sort']) {
            $entity->setSort($data['sort']);
        }

        if (isset($data['release_time']) && $data['release_time']) {
            $entity->setReleaseTime($data['release_time']);
        }
        if (isset($data['article_type']) && $data['article_type']) {
            $entity->setArticleType($data['article_type']);
        }
        //当前字段非必填
        if (isset($data["head_portrait"])) {
            $entity->setHeadPortrait($data["head_portrait"]);
        }

        if (isset($data['category_id'])) {
            $entity->setCategoryId($data['category_id']);
        }
        if (isset($data['province']) && $data['province']) {
            $entity->setProvince($data['province']);
        }

        if (isset($data['city']) && $data['city']) {
            $entity->setCity($data['city']);
        }
        if (isset($data['distributor_id'])) {
            $entity->setDistributorId($data['distributor_id']);
        }
        if (isset($data['area']) && $data['area']) {
            $entity->setArea($data['area']);
        }
        //当前字段非必填
        if (isset($data["regions"]) && $data["regions"]) {
            $entity->setRegions($data["regions"]);
        }
        if (isset($data["regions_id"]) && $data["regions_id"]) {
            $entity->setRegionsId($data["regions_id"]);
        }
        // 处理is_ai字段
        if (isset($data["is_ai"])) {
            $entity->setIsAi($data["is_ai"]);
        }
        return $entity;
    }

    //获取文章中所有省份的id列表
    public function getAllProvince($companyId)
    {
        $cols = 'province, regions_id';
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()->select($cols)->from($this->table);
        $qb = $qb->andWhere($qb->expr()->eq('company_id', $qb->expr()->literal($companyId)));
        $qb->groupBy('province');
        $lists = $qb->execute()->fetchAll();
        $result = [];
        foreach ($lists as $value) {
            if ($value['regions_id']) {
                $regionsId = json_decode($value['regions_id'], true);
                $result[] = reset($regionsId);
            }
        }
        return $result;
    }

    /**
     * 获取数据表字段数据
     *
     * @param entity
     */
    private function getColumnNamesData($entity, $isNoContent = false)
    {
        $result = [
            'article_id' => $entity->getArticleId(),
            'company_id' => $entity->getCompanyId(),
            'title' => $entity->getTitle(),
            'summary' => $entity->getSummary(),
            'content' => '',
            'sort' => $entity->getSort(),
            'image_url' => $entity->getImageUrl(),
            'share_image_url' => $entity->getShareImageUrl(),
            'created' => $entity->getCreated(),
            'updated' => $entity->getUpdated(),
            'author' => $entity->getAuthor(),
            'operator_id' => $entity->getOperatorId(),
            'release_status' => $entity->getReleaseStatus(),
            'release_time' => $entity->getReleaseTime(),
            'article_type' => $entity->getArticleType(),
            'distributor_id' => $entity->getDistributorId(),
            'head_portrait' => $entity->getHeadPortrait(),
            'province' => $entity->getProvince(),
            'city' => $entity->getCity(),
            'area' => $entity->getArea(),
            'regions' => $entity->getRegions(),
            'regions_id' => $entity->getRegionsId(),
            'category_id' => $entity->getCategoryId(),
            'is_ai' => $entity->getIsAi(),

        ];
        if (!$isNoContent) {
            $content = json_decode($entity->getContent(), true);
            $result['content'] = $content ?: $entity->getContent();
        }
        return $result;
    }
}
