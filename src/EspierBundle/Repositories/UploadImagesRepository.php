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

namespace EspierBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;
use EspierBundle\Entities\UploadImages;

use Dingo\Api\Exception\ResourceException;

class UploadImagesRepository extends EntityRepository
{
    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        $entity = new UploadImages();
        $entity = $this->setImagesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return $this->getImagesData($entity);
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
            throw new ResourceException('未查询到更新数据');
        }

        $entity = $this->setImagesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return $this->getImagesData($entity);
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
            throw new ResourceException('未查询到更新数据');
        }

        $em = $this->getEntityManager();
        $result = [];
        foreach ($entityList as $entityProp) {
            $entityProp = $this->setImagesData($entityProp, $data);
            $em->persist($entityProp);
            $em->flush();
            $result[] = $this->getImagesData($entityProp);
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
            throw new \Exception('删除的数据不存在');
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
            throw new \Exception('删除的数据不存在');
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

        return $this->getImagesData($entity);
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

        return $this->getImagesData($entity);
    }

    /**
     * 统计数量
     */
    public function count($filter)
    {
        $criteria = $this->__filter($filter);

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
    public function lists($filter, $page = 1, $pageSize = 100, $orderBy = ["created" => "DESC"])
    {
        $criteria = $this->__filter($filter);

        $total = $this->getEntityManager()
            ->getUnitOfWork()
            ->getEntityPersister($this->getEntityName())
            ->count($criteria);
        $res['total_count'] = intval($total);

        $lists = [];
        if ($res['total_count']) {
            $criteria = $criteria->orderBy($orderBy)
                ->setFirstResult($pageSize * ($page - 1))
                ->setMaxResults($pageSize);
            $entityList = $this->matching($criteria);
            foreach ($entityList as $entity) {
                $lists[] = $this->getImagesData($entity);
            }
        }

        $res['list'] = $lists;
        return $res;
    }

    private function __filter($filter)
    {
        $filter['disabled'] = (isset($filter['disabled']) && $filter['disabled']) ? 1 : 0;
        $criteria = Criteria::create();
        foreach ($filter as $field => $value) {
            if (is_numeric($value) || is_bool($value) || $value) {
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
        }

        return $criteria;
    }

    /**
     * 设置entity数据，用于插入和更新操作
     *
     * @param $entity
     * @param $data
     */
    private function setImagesData($entity, $data)
    {
        if (isset($data['company_id']) && $data['company_id']) {
            $entity->setCompanyId($data['company_id']);
        }
        if (isset($data['storage']) && $data['storage']) {
            $entity->setStorage($data['storage']);
        }
        if (isset($data['image_name']) && $data['image_name']) {
            $entity->setImageName($data['image_name']);
        }
        if (isset($data['brief']) && $data['brief']) {
            $entity->setBrief($data['brief']);
        }
        if (isset($data['image_cat_id'])) {
            $entity->setImageCatId($data['image_cat_id']);
        }
        if (isset($data['image_type']) && $data['image_type']) {
            $entity->setImageType($data['image_type']);
        }
        if (isset($data['image_full_url']) && $data['image_full_url']) {
            $entity->setImageFullUrl($data['image_full_url']);
        }
        if (isset($data['image_url']) && $data['image_url']) {
            $entity->setImageUrl($data['image_url']);
        }
        if (isset($data['disabled'])) {
            $entity->setDisabled($data['disabled']);
        }
        if (isset($data['distributor_id'])) {
            $entity->setDistributorId($data['distributor_id']);
        }
        if (isset($data['created']) && $data['created']) {
            $entity->setCreated($data['created']);
        }
        if (isset($data['updated']) && $data['updated']) {
            $entity->setUpdated($data['updated']);
        }
        if (isset($data["supplier_id"])) {
            $entity->setSupplierId(intval($data["supplier_id"]));
        }
        if (isset($data["merchant_id"])) {
            $entity->setMerchantId(intval($data["merchant_id"]));
        }
        return $entity;
    }

    /**
     * 获取数据表字段数据
     *
     * @param entity
     */
    private function getImagesData($entity)
    {
        return [
            'image_id' => $entity->getImageId(),
            'company_id' => $entity->getCompanyId(),
            'storage' => $entity->getStorage(),
            'image_name' => $entity->getImageName(),
            'brief' => $entity->getBrief(),
            'image_cat_id' => $entity->getImageCatId(),
            'image_type' => $entity->getImageType(),
            'image_full_url' => $entity->getImageFullUrl(),
            'image_url' => $entity->getImageUrl(),
            'disabled' => $entity->getDisabled(),
            'distributor_id' => $entity->getDistributorId(),
            'created' => $entity->getCreated(),
            'updated' => $entity->getUpdated(),
            'supplier_id' => $entity->getSupplierId(),
            'merchant_id' => $entity->getMerchantId(),
        ];
    }
}
