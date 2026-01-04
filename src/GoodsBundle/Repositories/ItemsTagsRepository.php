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

namespace GoodsBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;
use GoodsBundle\Entities\ItemsTags;

use Dingo\Api\Exception\ResourceException;
use GoodsBundle\Services\MultiLang\MagicLangTrait;
use GoodsBundle\Services\MultiLang\MultiLangService;

class ItemsTagsRepository extends EntityRepository
{
    use MagicLangTrait;
    private  $table = 'items_tags';
    private $multiLangField = [
        'tag_name','description'
    ];

    private $prk = 'tag_id';
    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        $service = new MultiLangService();
//        $dataTmp = $service->getLangData($data,$this->multiLangField);
//        $data = $dataTmp['data'];
//        $langBag = $dataTmp['langBag'];
        $entity = new ItemsTags();
        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        $dataRet = $this->getColumnNamesData($entity);
        $service->addMultiLangByParams($dataRet[$this->prk],$data,$this->table);
//        $service->saveLang($dataRet['company_id'],$data,$this->table,$dataRet['category_id'],$this->table);
        return $dataRet;
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

        $service = new MultiLangService();
        $service->updateLangData($data,$this->table,$filter[$this->prk]);
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
            return true;
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
            return true;
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
    public function lists($filter, $page = 1, $pageSize = 100, $orderBy = array())
    {
        $result['total_count'] = $this->count($filter);
        if ($result['total_count'] > 0) {
            $conn = app('registry')->getConnection('default');
            $qb = $conn->createQueryBuilder()->select("*")->from("items_tags");

            foreach ($filter as $field => $value) {
                $list = explode("|", $field);
                if (count($list) > 1) {
                    list($v, $k) = $list;
                    if ($k == 'contains') {
                        $k = 'like';
                    }
                    if ($k == 'like') {
                        $value = '%'.$value.'%';
                    }
                    if (is_array($value)) {
                        if (!$value) continue;
                        array_walk($value, function (&$colVal) use ($qb) {
                            $colVal = $qb->expr()->literal($colVal);
                        });
                        $qb = $qb->andWhere($qb->expr()->$k($v, $value));
                    } else {
                        if (is_string($value)) {
                            $qb = $qb->andWhere($qb->expr()->$k($v, $qb->expr()->literal($value)));
                        } else {
                            $qb = $qb->andWhere($qb->expr()->$k($v, is_bool($value) ? ($value ? 1 : 0) : $value));
                        }
                    }
                } else {
                    if (is_array($value)) {
                        if (!$value) continue;
                        array_walk($value, function (&$colVal) use ($qb) {
                            $colVal = $qb->expr()->literal($colVal);
                        });
                        $qb = $qb->andWhere($qb->expr()->in($field, $value));
                    } else {
                        if (is_string($value)) {
                            $qb = $qb->andWhere($qb->expr()->eq($field, $qb->expr()->literal($value)));
                        } else {
                            $qb = $qb->andWhere($qb->expr()->eq($field, is_bool($value) ? ($value ? 1 : 0) : $value));
                        }
                    }
                }
            }

            if ($orderBy) {
                foreach ($orderBy as $filed => $val) {
                    $qb->addOrderBy($filed, $val);
                }
            }
            if ($pageSize > 0) {
                $qb->setFirstResult(($page - 1) * $pageSize)
                    ->setMaxResults($pageSize);
            }
            $lists = $qb->execute()->fetchAll();
        }
        $result['list'] = $lists ?? [];
//        $res["list"] = $lists;
        $service = new MultiLangService();
        $result['list'] = $service->getListAddLang($result['list'],$this->multiLangField,$this->table,$this->getLang(),'tag_id');
        return $result;


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
            if (is_array($orderBy)) {
                foreach ($orderBy as $column => $option) {
                    $criteria = $criteria->orderBy($column, $option);
                }
            }
            if ($pageSize > 0) {
                $criteria->setFirstResult($pageSize * ($page - 1))
                         ->setMaxResults($pageSize);
            }
            $entityList = $this->matching($criteria);
            foreach ($entityList as $entity) {
                $lists[] = $this->getColumnNamesData($entity);
            }
        }

        $res["list"] = $lists;
        $service = new MultiLangService();
        $res["list"] = $service->getListAddLang($res["list"],$this->multiLangField,$this->table,$this->getLang(),'tag_id');
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
        if (isset($data["company_id"]) && $data["company_id"]) {
            $entity->setCompanyId($data["company_id"]);
        }
        if (isset($data["tag_name"]) && $data["tag_name"]) {
            $entity->setTagName($data["tag_name"]);
        }
        if (isset($data["tag_color"]) && $data["tag_color"]) {
            $entity->setTagColor($data["tag_color"]);
        }
        if (isset($data["font_color"]) && $data["font_color"]) {
            $entity->setFontColor($data["font_color"]);
        }
        if (isset($data["distributor_id"])) {
            $entity->setDistributorId($data["distributor_id"]);
        }
        //当前字段非必填
        if (isset($data["description"]) && $data["description"]) {
            $entity->setDescription($data["description"]);
        }
        //当前字段非必填
        if (isset($data["tag_icon"]) && $data["tag_icon"]) {
            $entity->setTagIcon($data["tag_icon"]);
        }
        //当前字段非必填
        if (isset($data["front_show"])) {
            $entity->setFrontShow($data["front_show"]);
        }
        if (isset($data["created"]) && $data["created"]) {
            $entity->setCreated($data["created"]);
        }
        if (isset($data["updated"]) && $data["updated"]) {
            $entity->setUpdated($data["updated"]);
        }
        return $entity;
    }

    /**
     * 获取数据表字段数据
     *
     * @param entity
     */
    private function getColumnNamesData($entity)
    {
        return [
            'tag_id' => $entity->getTagId(),
            'company_id' => $entity->getCompanyId(),
            'distributor_id' => $entity->getDistributorId(),
            'tag_name' => $entity->getTagName(),
            'tag_color' => $entity->getTagColor(),
            'font_color' => $entity->getFontColor(),
            'description' => $entity->getDescription(),
            'tag_icon' => $entity->getTagIcon(),
            'front_show' => $entity->getFrontShow(),
            'created' => $entity->getCreated(),
            'updated' => $entity->getUpdated(),
        ];
    }
}
