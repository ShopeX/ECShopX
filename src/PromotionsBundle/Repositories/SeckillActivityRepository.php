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
use Doctrine\Common\Collections\Criteria;
use GoodsBundle\Services\MultiLang\MagicLangTrait;
use GoodsBundle\Services\MultiLang\MultiLangOutsideItemService;
use PromotionsBundle\Entities\SeckillActivity;

use Dingo\Api\Exception\ResourceException;


class SeckillActivityRepository extends EntityRepository
{
    use MagicLangTrait;
    private $multiLangField = [
        'activity_name',
        'ad_pic',
        'description'
    ];
    public $prk = 'seckill_id';
    private $table = 'promotions_seckill_activity';

    public function getLangService()
    {
        return new MultiLangOutsideItemService($this->table,$this->table,$this->multiLangField);
    }
    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        $entity = new SeckillActivity();
        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        $dataRet = $this->getColumnNamesData($entity);

        $this->getLangService()->addMultiLangByParams($dataRet[$this->prk],$data,$this->table);

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
            throw new ResourceException(trans("PromotionsBundle.no_update_data_found"));
        }

        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
        if(isset($filter[$this->prk])){
            $this->getLangService()->updateLangData($data,$this->table,$filter[$this->prk]);
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
            $tmp = $this->getColumnNamesData($entityProp);
            if(isset($filter[$this->prk])){
                $this->getLangService()->updateLangData($data,$this->table,$filter[$this->prk]);
            }

            $result[] = $tmp;
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

        $result =  $this->getColumnNamesData($entity);

        if ($result){
            $this->getLangService()->getOneLangData($result,$this->multiLangField,$this->table,$this->getLang(),$result[$this->prk],$this->table);
        }

        return $result;
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
        $result = $this->getColumnNamesData($entity);

        if ($result){
            $this->getLangService()->getOneLangData($result,$this->multiLangField,$this->table,$this->getLang(),$result[$this->prk],$this->table);
        }

        return $result;
    }

    /**
     * 统计数量
     */
    public function count($filter)
    {
        $criteria = Criteria::create();
        foreach ($filter as $field => $value) {
            if ($field == 'or') {
                foreach ($filter['or'] as $orField => $orValue) {
                    $list = explode("|", $orField);
                    if (count($list) > 1) {
                        list($v, $k) = $list;
                        $orWhere[] = Criteria::expr()->$k($v, $orValue);
                    } elseif (is_array($orValue)) {
                        $orWhere[] = Criteria::expr()->in($orField, $orValue);
                    } else {
                        $orWhere[] = Criteria::expr()->eq($orField, $orValue);
                    }
                }
                $criteria->andWhere(
                    $criteria->expr()->orX(...$orWhere)
                );
                continue;
            }

            $list = explode("|", $field);
            if (count($list) > 1) {
                list($v, $k) = $list;
                $criteria = $criteria->andWhere(Criteria::expr()->$k($v, $value));
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
        $criteria = Criteria::create();
        foreach ($filter as $field => $value) {
            if ($field == 'or') {
                foreach ($filter['or'] as $orField => $orValue) {
                    $list = explode("|", $orField);
                    if (count($list) > 1) {
                        list($v, $k) = $list;
                        $orWhere[] = Criteria::expr()->$k($v, $orValue);
                    } elseif (is_array($orValue)) {
                        $orWhere[] = Criteria::expr()->in($orField, $orValue);
                    } else {
                        $orWhere[] = Criteria::expr()->eq($orField, $orValue);
                    }
                }
                $criteria->andWhere(
                    $criteria->expr()->orX(...$orWhere)
                );
                continue;
            }

            $list = explode("|", $field);
            if (count($list) > 1) {
                list($v, $k) = $list;
                $criteria = $criteria->andWhere(Criteria::expr()->$k($v, $value));
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
            if ($orderBy) {
                $criteria = $criteria->orderBy($orderBy);
            }
            $criteria->setFirstResult($pageSize * ($page - 1))
                ->setMaxResults($pageSize);
            $entityList = $this->matching($criteria);
            foreach ($entityList as $entity) {
                $lists[] = $this->getColumnNamesData($entity);
            }
        }

        $res["list"] = $lists;
        $res["list"] = $this->getLangService()->getListAddLang($res["list"],$this->multiLangField,$this->table,$this->getLang(),$this->prk);
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
        //当前字段非必填
        if (isset($data["activity_name"]) && $data["activity_name"]) {
            $entity->setActivityName($data["activity_name"]);
        }
        //当前字段非必填
        if (isset($data["ad_pic"]) && $data["ad_pic"]) {
            $entity->setAdPic($data["ad_pic"]);
        }
        if (isset($data["activity_start_time"]) && $data["activity_start_time"]) {
            $entity->setActivityStartTime($data["activity_start_time"]);
        }
        if (isset($data["activity_end_time"]) && $data["activity_end_time"]) {
            $entity->setActivityEndTime($data["activity_end_time"]);
        }
        if (isset($data["activity_release_time"]) && $data["activity_release_time"]) {
            $entity->setActivityReleaseTime($data["activity_release_time"]);
        }
        if (isset($data["is_activity_rebate"])) {
            if (!$data["is_activity_rebate"] || $data["is_activity_rebate"] === 'false') {
                $entity->setIsActivityRebate(false);
            } else {
                $entity->setIsActivityRebate(true);
            }
        }
        if (isset($data["is_free_shipping"])) {
            if (!$data["is_free_shipping"] || $data["is_free_shipping"] === 'false') {
                $entity->setIsFreeShipping(false);
            } else {
                $entity->setIsFreeShipping(true);
            }
        }

        if (isset($data["distributor_id"]) && $data["distributor_id"]) {
            if (!is_array($data["distributor_id"])) {
                $ids = trim($data["distributor_id"], ',');
                $data["distributor_id"] = explode(',', $data["distributor_id"]);
            }
            $distributorId = ','.implode(',', $data["distributor_id"]).',';
            $entity->setDistributorId($distributorId);
        }

        if (isset($data["seckill_type"]) && $data["seckill_type"]) {
            $entity->setSeckillType($data["seckill_type"]);
        }
        //当前字段非必填
        if (isset($data["validity_period"])) {
            $entity->setValidityPeriod($data["validity_period"]);
        }

        if (isset($data["limit_total_money"])) {
            $entity->setLimitTotalMoney($data["limit_total_money"]);
        }

        if (isset($data["limit_money"])) {
            $entity->setLimitMoney($data["limit_money"]);
        }

        if (isset($data["otherext"])) {
            $entity->setOtherext(json_encode($data["otherext"]));
        }

        if (isset($data["description"]) && $data["description"]) {
            $entity->setDescription($data["description"]);
        }
        if (isset($data["item_type"]) && $data["item_type"]) {
            $entity->setItemType($data["item_type"]);
        }
        if (isset($data["created"]) && $data["created"]) {
            $entity->setCreated($data["created"]);
        }
        //当前字段非必填
        if (isset($data["updated"]) && $data["updated"]) {
            $entity->setUpdated($data["updated"]);
        }
        if (isset($data["use_bound"])) {
            $entity->setUseBound($data['use_bound']);
        }
        if (isset($data["source_id"])) {
            $entity->setSourceId(floatval($data["source_id"]));
        }
        if (isset($data["source_type"])) {
            $entity->setSourceType($data["source_type"]);
        }
        if (isset($data["disabled"]) && $data["disabled"]) {
            $entity->setDisabled($data["disabled"]);
        }
        return $entity;
    }

    /**
     * 获取时间段内是否有拼团活动
     *
     * @param $filter 更新的条件
     */
    // public function getIsHave($begin_time, $end_time)
    // {
    //     $criteria = Criteria::create();
    //     $criteria = $criteria->andWhere(Criteria::expr()->neq('status', 'closed'));
    //
    //     $criteria = $criteria->andWhere(Criteria::expr()->orX(
    //         Criteria::expr()->andX(
    //             Criteria::expr()->lte('activity_start_time', $begin_time),
    //             Criteria::expr()->gte('activity_end_time', $begin_time)
    //         ),
    //         Criteria::expr()->andX(
    //             Criteria::expr()->lte('activity_start_time', $end_time),
    //             Criteria::expr()->gte('activity_end_time', $end_time)
    //         )
    //     ));
    //     $criteria->orderBy(["created" => "DESC"]);
    //     $entityList = $this->matching($criteria);
    //
    //     $lists = [];
    //     foreach ($entityList as $entity) {
    //         $lists[] = $this->getColumnNamesData($entity);
    //     }
    //
    //     return isset($lists[0]) ? $lists[0] : [];
    // }

    /**
     * 获取数据表字段数据
     *
     * @param entity
     */
    private function getColumnNamesData($entity)
    {
        $result = [
            'seckill_id' => $entity->getSeckillId(),
            'company_id' => $entity->getCompanyId(),
            'activity_name' => $entity->getActivityName(),
            'distributor_id' => $entity->getDistributorId(),
            'seckill_type' => $entity->getSeckillType(),
            'limit_total_money' => $entity->getLimitTotalMoney(),
            'otherext' => $entity->getOtherext() ? json_decode($entity->getOtherext(), true) : null,
            'limit_money' => $entity->getLimitMoney(),
            'ad_pic' => $entity->getAdPic(),
            'activity_start_time' => $entity->getActivityStartTime(),
            'activity_end_time' => $entity->getActivityEndTime(),
            'activity_release_time' => $entity->getActivityReleaseTime(),
            'is_activity_rebate' => $entity->getIsActivityRebate(),
            'is_free_shipping' => $entity->getIsFreeShipping(),
            'validity_period' => $entity->getValidityPeriod(),
            'description' => $entity->getDescription(),
            'item_type' => $entity->getItemType(),
            'created' => $entity->getCreated(),
            'updated' => $entity->getUpdated(),
            'use_bound' => $entity->getUseBound(),
            'source_id' => $entity->getSourceId(),
            'source_type' => $entity->getSourceType(),
            'disabled' => $entity->getDisabled(),
        ];
        if ($result['distributor_id']) {
            $ids = trim($result['distributor_id'], ',');
            $result['distributor_id'] = explode(',', $ids);
        } else {
            $result['distributor_id'] = null;
        }
        $nowTime = time();
        if ($nowTime >= $result['activity_end_time'] || $result['disabled'] == 1) {
            $result['status'] = 'it_has_ended';    //已结束
        } elseif ($nowTime >= $result['activity_start_time'] && $nowTime < $result['activity_end_time'] && $result['disabled'] == 0) {
            $result['status'] = 'in_sale';         //售卖中
            $result['last_seconds'] = ($result['activity_end_time'] - $nowTime) > 0 ? ($result['activity_end_time'] - $nowTime) : 0;
        } elseif ($nowTime >= $result['activity_release_time'] && $nowTime < $result['activity_start_time'] && $result['disabled'] == 0) {
            $result['status'] = 'in_the_notice';   //预览中
            $result['last_seconds'] = ($result['activity_start_time'] - $nowTime) > 0 ? ($result['activity_start_time'] - $nowTime) : 0;
        } elseif ($nowTime < $result['activity_release_time'] && $result['disabled'] == 0) {
            $result['status'] = 'waiting';   //等待中
        }

        $result['activity_start_date'] = date('Y-m-d H:i:s', $result['activity_start_time']);
        $result['activity_end_date'] = date('Y-m-d H:i:s', $result['activity_end_time']);
        $result['activity_release_date'] = date('Y-m-d H:i:s', $result['activity_release_time']);
        $result['created_date'] = date('Y-m-d H:i:s', $result['created']);
        $result['updated_date'] = date('Y-m-d H:i:s', $result['updated']);
        return $result;
    }
}
