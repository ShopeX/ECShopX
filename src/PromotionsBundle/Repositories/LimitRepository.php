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

namespace PromotionsBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use GoodsBundle\Services\MultiLang\MagicLangTrait;
use GoodsBundle\Services\MultiLang\MultiLangService;
use PromotionsBundle\Entities\LimitPromotions;

use Dingo\Api\Exception\ResourceException;

class LimitRepository extends EntityRepository
{
    use MagicLangTrait;
    public $table = "promotions_limit";
    public $cols = ['limit_id','company_id','limit_name','total_item_num','valid_item_num','error_desc','limit_type','valid_grade','rule','start_time','end_time','created','updated', 'source_type', 'source_id'];


    private $prk = 'limit_id';
    private $multiLangField = [
        'limit_name',
    ];
    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        $entity = new LimitPromotions();
        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
        $service = new MultiLangService();
        $result =$this->getColumnNamesData($entity);
        $service->addMultiLangByParams($result[$this->prk],$data,$this->table);
        return $result;
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
            throw new ResourceException(trans("PromotionsBundle.no_update_data_found"));
        }

        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
        if(isset($filter[$this->prk])){
            $service = new MultiLangService();
            $service->updateLangData($data,$this->table,$filter[$this->prk]);
        }

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
            throw new ResourceException(trans("PromotionsBundle.no_update_data_found"));
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
                if ($k == 'contains') {
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

    /**
     * 根据条件获取列表数据
     *
     * @param $filter 更新的条件
     */
    public function lists($filter, $cols = '*', $page = 1, $pageSize = -1, $orderBy = array())
    {
        $result['total_count'] = $this->count($filter);
        if ($result['total_count'] > 0) {
            $conn = app('registry')->getConnection('default');
            $qb = $conn->createQueryBuilder()->select($cols)->from($this->table);
            $qb = $this->_filter($filter, $qb);
            if ($orderBy) {
                foreach ($orderBy as $filed => $val) {
                    $qb->orderBy($filed, $val);
                }
            }
            if ($pageSize > 0) {
                $qb->setFirstResult(($page - 1) * $pageSize)
                  ->setMaxResults($pageSize);
            }
            $lists = $qb->execute()->fetchAll();
        }
        $result['list'] = $lists ?? [];
        $service = new MultiLangService();
        $result['list'] = $service->getListAddLang($result['list'],$this->multiLangField,$this->table,$this->getLang(),$this->prk);
        return $result;
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
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('count(*)')
             ->from($this->table);
        if ($filter) {
            $this->_filter($filter, $qb);
        }
        $count = $qb->execute()->fetchColumn();
        return intval($count);
    }

    /**
     * 设置entity数据，用于插入和更新操作
     *
     * @param $entity
     * @param $data
     */
    private function setColumnNamesData($entity, $data)
    {
        if (isset($data["limit_id"]) && $data["limit_id"]) {
            $entity->setLimitId($data["limit_id"]);
        }
        if (isset($data["company_id"]) && $data["company_id"]) {
            $entity->setCompanyId($data["company_id"]);
        }
        if (isset($data["limit_name"]) && $data["limit_name"]) {
            $entity->setLimitName($data["limit_name"]);
        }
        if (isset($data["limit_type"]) && $data["limit_type"]) {
            $entity->setLimitType($data["limit_type"]);
        }
        if (isset($data["valid_grade"]) && $data["valid_grade"]) {
            $entity->setValidGrade($data["valid_grade"]);
        }
        if (isset($data["rule"]) && $data["rule"]) {
            $entity->setRule($data["rule"]);
        }
        if (isset($data["start_time"]) && $data["start_time"]) {
            $entity->setStartTime($data["start_time"]);
        }
        if (isset($data["end_time"]) && $data["end_time"]) {
            $entity->setEndTime($data["end_time"]);
        }
        if (isset($data["use_bound"])) {
            $entity->setUseBound($data['use_bound']);
        }
        if (isset($data["tag_ids"])) {
            $entity->setTagIds(json_encode($data["tag_ids"]));
        }
        if (isset($data["brand_ids"])) {
            $entity->setBrandIds(json_encode($data["brand_ids"]));
        }
        if (isset($data["created"]) && $data["created"]) {
            $entity->setCreated($data["created"]);
        }
        //当前字段非必填
        if (isset($data["updated"]) && $data["updated"]) {
            $entity->setUpdated($data["updated"]);
        }
        if (isset($data["total_item_num"])) {
            $entity->setTotalItemNum($data["total_item_num"]);
        }
        if (isset($data["valid_item_num"])) {
            $entity->setValidItemNum($data["valid_item_num"]);
        }
        if (isset($data["error_desc"])) {
            $entity->setErrorDesc($data["error_desc"]);
        }
        if (isset($data["source_type"])) {
            $entity->setSourceType($data["source_type"]);
        }
        if (isset($data["source_id"])) {
            $entity->setSourceId(floatval($data["source_id"]));
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
            'limit_id' => $entity->getLimitId(),
            'company_id' => $entity->getCompanyId(),
            'limit_name' => $entity->getLimitName(),
            'limit_type' => $entity->getLimitType(),
            'total_item_num' => $entity->getTotalItemNum(),
            'valid_item_num' => $entity->getValidItemNum(),
            'error_desc' => $entity->getErrorDesc(),
            'valid_grade' => explode(',', $entity->getValidGrade()),
            'rule' => $entity->getRule(),
            'start_time' => $entity->getStartTime(),
            'end_time' => $entity->getEndTime(),
            'use_bound' => $entity->getUseBound(),
            'tag_ids' => json_decode($entity->getTagIds(), true) ? json_decode($entity->getTagIds(), true) : [],
            'brand_ids' => json_decode($entity->getBrandIds(), true) ? json_decode($entity->getBrandIds(), true) : [],
            'created' => $entity->getCreated(),
            'updated' => $entity->getUpdated(),
            'source_type' => $entity->getSourceType(),
            'source_id' => $entity->getSourceId(),
        ];
    }
}
