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

use Carbon\Carbon;
use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;
use GoodsBundle\Services\MultiLang\MagicLangTrait;
use GoodsBundle\Services\MultiLang\MultiLangOutsideItemService;
use PromotionsBundle\Entities\MarketingActivity;

use Dingo\Api\Exception\ResourceException;


class MarketingActivityRepository extends EntityRepository
{
    use MagicLangTrait;
    private $multiLangField = [
        'title','description'
    ];
    public $prk = 'marketing_id';
    public $table = 'promotions_marketing_activity';
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
        $entity = new MarketingActivity();
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

        $result = $this->getColumnNamesData($entity);
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
    public function lists($filter, $page = 1, $pageSize = -1, $orderBy = array())
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
            if ($orderBy) {
                $criteria = $criteria->orderBy($orderBy);
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
        if (isset($data["marketing_type"]) && $data["marketing_type"]) {
            $entity->setMarketingType($data["marketing_type"]);
        }
        //当前字段非必填
        if (isset($data["rel_marketing_id"]) && $data["rel_marketing_id"]) {
            $entity->setRelMarketingId($data["rel_marketing_id"]);
        }
        if (isset($data["marketing_name"]) && $data["marketing_name"]) {
            $entity->setMarketingName($data["marketing_name"]);
        }
        if (isset($data["activity_background"]) && $data["activity_background"]) {
            $entity->setActivityBackground($data["activity_background"]);
        }
        if (isset($data["navbar_color"]) && $data["navbar_color"]) {
            $entity->setNavbarColor($data["navbar_color"]);
        }
        if (isset($data["timeBackgroundColor"]) && $data["timeBackgroundColor"]) {
            $entity->settimeBackgroundColor($data["timeBackgroundColor"]);
        }
        if (isset($data["marketing_desc"]) && $data["marketing_desc"]) {
            $entity->setMarketingDesc($data["marketing_desc"]);
        }
        if (isset($data["start_time"]) && $data["start_time"]) {
            $entity->setStartTime($data["start_time"]);
        }
        if (isset($data["end_time"]) && $data["end_time"]) {
            $entity->setEndTime($data["end_time"]);
        }
        //当前字段非必填
        if (isset($data["release_time"]) && $data["release_time"]) {
            $entity->setReleaseTime($data["release_time"]);
        }
        if (isset($data["used_platform"])) {
            $entity->setUsedPlatform($data["used_platform"]);
        }
        if (isset($data["use_bound"])) {
            $entity->setUseBound($data["use_bound"]);
        }
        if (isset($data["tag_ids"]) && $data["tag_ids"]) {
            $entity->setTagIds(json_encode($data["tag_ids"]));
        }
        if (isset($data["brand_ids"]) && $data["brand_ids"]) {
            $entity->setBrandIds(json_encode($data["brand_ids"]));
        }
        if (isset($data["use_shop"])) {
            $entity->setUseShop($data["use_shop"]);
        }
        //当前字段非必填
        if (isset($data["shop_ids"]) && $data['shop_ids']) {
            $shopIds = ','.implode(',', $data['shop_ids']).",";
            $entity->setShopIds($shopIds);
        }
        //当前字段非必填
        if (isset($data["valid_grade"]) && $data["valid_grade"]) {
            $entity->setValidGrade(json_encode($data["valid_grade"]));
        }
        if (isset($data["condition_type"]) && $data["condition_type"]) {
            $entity->setConditionType($data["condition_type"]);
        }
        if (isset($data["condition_value"]) && $data["condition_value"]) {
            $entity->setConditionValue(json_encode($data["condition_value"]));
        }
        //当前字段非必填
        if (isset($data["in_proportion"])) {
            $inProportion = ($data["in_proportion"] === 'false' || !$data["in_proportion"]) ? false : true;
            $entity->setInProportion($inProportion);
        }
        //当前字段非必填
        if (isset($data["canjoin_repeat"])) {
            $canjoinRepeat = ($data["canjoin_repeat"] === 'false' || !$data["canjoin_repeat"]) ? false : true;
            $entity->setCanjoinRepeat($canjoinRepeat);
        }
        if (isset($data["join_limit"])) {
            $entity->setJoinLimit($data["join_limit"]);
        }
        if (isset($data["free_postage"])) {
            $freePostage = ($data["free_postage"] === 'false' || !$data["free_postage"]) ? false : true;
            $entity->setFreePostage($freePostage);
        }
        if (isset($data["promotion_tag"]) && $data["promotion_tag"]) {
            $entity->setPromotionTag($data["promotion_tag"]);
        }
        if (isset($data["check_status"]) && $data["check_status"]) {
            $entity->setCheckStatus($data["check_status"]);
        }
        //当前字段非必填
        if (isset($data["reason"]) && $data["reason"]) {
            $entity->setReason($data["reason"]);
        }
        if (isset($data["item_type"]) && $data["item_type"]) {
            $entity->setItemType($data["item_type"]);
        }
        if (isset($data["is_increase_purchase"])) {
            $isIncreasePurchase = ($data["is_increase_purchase"] === 'false' || !$data["is_increase_purchase"]) ? false : true;
            $entity->setIsIncreasePurchase($isIncreasePurchase);
        }
        if (isset($data["company_id"]) && $data["company_id"]) {
            $entity->setCompanyId($data["company_id"]);
        }
        if (isset($data["created"]) && $data["created"]) {
            $entity->setCreated($data["created"]);
        }
        //当前字段非必填
        if (isset($data["updated"]) && $data["updated"]) {
            $entity->setUpdated($data["updated"]);
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
        $return = [
            'marketing_id' => $entity->getMarketingId(),
            'marketing_type' => $entity->getMarketingType(),
            'rel_marketing_id' => $entity->getRelMarketingId(),
            'marketing_name' => $entity->getMarketingName(),
            'marketing_desc' => $entity->getMarketingDesc(),
            'start_time' => $entity->getStartTime(),
            'end_time' => $entity->getEndTime(),
            'start_date' => date('Y/m/d H:i:s', $entity->getStartTime()),
            'end_date' => date('Y/m/d H:i:s', $entity->getEndTime()),
            'release_time' => $entity->getReleaseTime(),
            'used_platform' => $entity->getUsedPlatform(),
            'use_bound' => $entity->getUseBound(),
            'tag_ids' => json_decode($entity->getTagIds(), true) ? json_decode($entity->getTagIds(), true) : [],
            'brand_ids' => json_decode($entity->getBrandIds(), true) ? json_decode($entity->getBrandIds(), true) : [],
            'use_shop' => $entity->getUseShop(),
            'shop_ids' => $entity->getShopIds() ? explode(',', $entity->getShopIds()) : [],
            'valid_grade' => json_decode($entity->getValidGrade(), true) ? json_decode($entity->getValidGrade(), true) : [],
            'condition_type' => $entity->getConditionType(),
            'condition_value' => json_decode($entity->getConditionValue(), true) ? json_decode($entity->getConditionValue(), true) : [],
            'in_proportion' => $entity->getInProportion(),
            'canjoin_repeat' => $entity->getCanjoinRepeat(),
            'join_limit' => $entity->getJoinLimit(),
            'free_postage' => $entity->getFreePostage(),
            'promotion_tag' => $entity->getPromotionTag(),
            'check_status' => $entity->getCheckStatus(),
            'activity_background' => $entity->getActivityBackground(),
            'navbar_color' => $entity->getNavbarColor(),
            'timeBackgroundColor' => $entity->gettimeBackgroundColor(),
            'reason' => $entity->getReason(),
            'item_type' => $entity->getItemType(),
            'is_increase_purchase' => $entity->getIsIncreasePurchase(),
            'company_id' => $entity->getCompanyId(),
            'created' => $entity->getCreated(),
            'created_date' => date('Y/m/d H:i:s', $entity->getCreated()),
            'updated' => $entity->getUpdated(),
            'source_type' => $entity->getSourceType(),
            'source_id' => $entity->getSourceId(),
        ];

        $nowTime = time();
        if ($nowTime >= $return['end_time']) {
            $return['status'] = 'end';    //已结束
        } elseif ($nowTime >= $return['start_time'] && $nowTime < $return['end_time']) {
            $return['status'] = 'ongoing';         //进行中
            $return['last_seconds'] = ($return['end_time'] - $nowTime) > 0 ? ($return['end_time'] - $nowTime) : 0;
        } elseif ($nowTime < $return['start_time']) {
            $return['status'] = 'waiting';   //未开始
        }
        return $return;
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
                }
                if ($k == 'like') {
                    $value = '%'.$value.'%';
                }
                if (is_array($value)) {
                    array_walk($value, function (&$colVal) use ($qb) {
                        $colVal = $qb->expr()->literal($colVal);
                    });
                    $qb = $qb->andWhere($qb->expr()->$k($field, $value));
                } else {
                    $qb = $qb->andWhere($qb->expr()->$k($v, $qb->expr()->literal($value)));
                }
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
     * 获取正在进行中的卡券
     * @param string $columns 获取的列
     * @param array $filter 过滤的条件
     * @param int $page 当前页
     * @param int $pageSize 每页数量
     * @param array $orderBy 排序条件
     * @return array 卡券列表数据
     */
    public function getOngoingList(string $columns, array $filter, int $page = 1, int $pageSize = 10, array $orderBy = []): array
    {
        $now = Carbon::now()->getTimestamp();

        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $criteria->select($columns)->from($this->table);
        // 正在进行中的
        $criteria->andWhere($criteria->expr()->and(
            $criteria->expr()->lte("start_time", $now),
            $criteria->expr()->gte("end_time", $now)
        ));

        // 过滤条件
        $this->_filter($filter, $criteria);

        // 分页
        if ($page > 0) {
            $criteria->setFirstResult(($page - 1) * $pageSize)
                ->setMaxResults($pageSize);
        }

        // 排序
        foreach ($orderBy as $filed => $val) {
            $criteria->addOrderBy($filed, $val);
        }

        $list =  $criteria->execute()->fetchAll();

        $list = $this->getLangService()->getListAddLang($list,$this->multiLangField,$this->table,$this->getLang(),$this->prk);

        return $list;
    }
}
