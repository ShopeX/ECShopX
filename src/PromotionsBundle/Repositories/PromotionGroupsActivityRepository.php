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
use PromotionsBundle\Entities\PromotionGroupsActivity;

use Dingo\Api\Exception\ResourceException;


class PromotionGroupsActivityRepository extends EntityRepository
{
    use MagicLangTrait;
    public $table = "promotions_point_upvaluation";
    public $prk = 'groups_activity_id';
    private $multiLangField = [
        'act_name',
        'pics',
        'share_desc'
    ];
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
        $entity = new PromotionGroupsActivity();
        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        $dataRet =  $this->getColumnNamesData($entity);

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

    public function updateStore($groupId, $store)
    {
        $itemsEnt = $this->find($groupId);
        if (!$itemsEnt) {
            return true;
        }

        $itemsEnt->setStore($store);

        $em = $this->getEntityManager();
        $em->persist($itemsEnt);
        $em->flush();

        $result = $this->getColumnNamesData($itemsEnt);

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
            throw new \Exception(trans("PromotionsBundle.delete_data_not_exist"));
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
            throw new \Exception(trans("PromotionsBundle.delete_data_not_exist"));
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
    public function lists($filter, $orderBy = ["created" => "DESC"], $pageSize = 100, $page = 1)
    {
        $view = isset($filter['view']) ? $filter['view'] : 0;
        unset($filter['view']);
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
        switch ($view) {
            case 1:
                $criteria = $criteria->andWhere(Criteria::expr()->gt('begin_time', time()));
                $criteria = $criteria->andWhere(Criteria::expr()->gt('end_time', time()));
                break;
            case 2:
                $criteria = $criteria->andWhere(Criteria::expr()->lte('begin_time', time()));
                $criteria = $criteria->andWhere(Criteria::expr()->gte('end_time', time()));
                break;
            case 3:
                $criteria = $criteria->andWhere(Criteria::expr()->lt('end_time', time()));
                break;
        }
        $total = $this->getEntityManager()
            ->getUnitOfWork()
            ->getEntityPersister($this->getEntityName())
            ->count($criteria);
        $res["total_count"] = intval($total);

        $lists = [];
        if ($res["total_count"]) {
            $criteria = $criteria->orderBy($orderBy)
                ->setFirstResult($pageSize * ($page - 1))
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
     * 获取时间段内是否有拼团活动
     *
     * @param $filter 更新的条件
     */
    public function getIsHave($goodId, $begin_time, $end_time, $groupId = '')
    {
        $criteria = Criteria::create();
        $goodId = (array)$goodId;
        $criteria = $criteria->andWhere(Criteria::expr()->in('goods_id', $goodId));
        $criteria = $criteria->andWhere(Criteria::expr()->eq('disabled', false));
        if ($groupId) {
            $criteria = $criteria->andWhere(Criteria::expr()->neq('groups_activity_id', $groupId));
        }
        $criteria = $criteria->andWhere(Criteria::expr()->orX(
            Criteria::expr()->andX(
                Criteria::expr()->lte('begin_time', $begin_time),
                Criteria::expr()->gte('end_time', $begin_time)
            ),
            Criteria::expr()->andX(
                Criteria::expr()->lte('begin_time', $end_time),
                Criteria::expr()->gte('end_time', $end_time)
            )
        ));

        $entityList = $this->matching($criteria);
        $lists = [];
        foreach ($entityList as $entity) {
            $lists[] = $this->getColumnNamesData($entity);
        }
        $lists = $this->getLangService()->getListAddLang($lists,$this->multiLangField,$this->table,$this->getLang(),$this->prk);
        return $lists;
    }

    /**
     * 设置entity数据，用于插入和更新操作
     *
     * @param $entity
     * @param $data
     */
    private function setColumnNamesData($entity, $data)
    {
        if (isset($data["groups_activity_id"]) && $data["groups_activity_id"]) {
            $entity->setGroupsActivityId($data["groups_activity_id"]);
        }
        if (isset($data["company_id"]) && $data["company_id"]) {
            $entity->setCompanyId($data["company_id"]);
        }
        if (isset($data["act_name"]) && $data["act_name"]) {
            $entity->setActName($data["act_name"]);
        }
        if (isset($data["goods_id"]) && $data["goods_id"]) {
            $entity->setGoodsId($data["goods_id"]);
        }
        if (isset($data["pics"]) && $data["pics"]) {
            $entity->setPics($data["pics"]);
        }
        if (isset($data["act_price"]) && $data["act_price"]) {
            $entity->setActPrice($data["act_price"]);
        }
        if (isset($data["person_num"]) && $data["person_num"]) {
            $entity->setPersonNum($data["person_num"]);
        }
        if (isset($data["begin_time"]) && $data["begin_time"]) {
            $entity->setBeginTime($data["begin_time"]);
        }
        if (isset($data["end_time"]) && $data["end_time"]) {
            $entity->setEndTime($data["end_time"]);
        }
        if (isset($data["limit_buy_num"])) {
            $entity->setLimitBuyNum($data["limit_buy_num"]);
        }
        if (isset($data["limit_time"]) && $data["limit_time"]) {
            $entity->setLimitTime($data["limit_time"]);
        }

        if (isset($data["group_goods_type"])) {
            $entity->setGroupGoodsType($data["group_goods_type"]);
        }

        if (isset($data["store"]) && $data["store"]) {
            $entity->setStore($data["store"]);
        }
        //当前字段非必填
        if (isset($data["free_post"])) {
            $entity->setFreePost($data["free_post"]);
        }
        //当前字段非必填
        if (isset($data["rig_up"])) {
            $entity->setRigUp($data["rig_up"]);
        }
        //当前字段非必填
        if (isset($data["robot"])) {
            $entity->setRobot($data["robot"]);
        }
        //当前字段非必填
        if (isset($data["share_desc"]) && $data["share_desc"]) {
            $entity->setShareDesc($data["share_desc"]);
        }
        //当前字段非必填
        if (isset($data["disabled"])) {
            $entity->setDisabled($data["disabled"]);
        }
        if (isset($data["created"]) && $data["created"]) {
            $entity->setCreated($data["created"]);
        }
        //当前字段非必填
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
        if ($entity->getBeginTime() > time()) {
            $remainingTime = $entity->getBeginTime() - time();
            $status = 'nostart';
        } else {
            $remainingTime = $entity->getEndTime() - time() > 0 ? $entity->getEndTime() - time() : 0;
            $status = 'noend';
        }

        return [
            'groups_activity_id' => $entity->getGroupsActivityId(),
            'company_id' => $entity->getCompanyId(),
            'act_name' => $entity->getActName(),
            'goods_id' => $entity->getGoodsId(),
            'group_goods_type' => $entity->getGroupGoodsType(),
            'pics' => $entity->getPics(),
            'act_price' => $entity->getActPrice(),
            'person_num' => (int)$entity->getPersonNum(),
            'begin_time' => $entity->getBeginTime(),
            'end_time' => $entity->getEndTime(),
            'limit_buy_num' => $entity->getLimitBuyNum(),
            'limit_time' => $entity->getLimitTime(),
            'store' => $entity->getStore(),
            'free_post' => $entity->getFreePost(),
            'rig_up' => $entity->getRigUp(),
            'robot' => $entity->getRobot(),
            'share_desc' => $entity->getShareDesc(),
            'disabled' => $entity->getDisabled(),
            'created' => $entity->getCreated(),
            'updated' => $entity->getUpdated(),
            'remaining_time' => $remainingTime,
            'last_seconds' => $remainingTime,
            'show_status' => $status,
        ];
    }

    /**
     * 获取未结束的拼团活动
     * @param $goodId
     * @param $end_time
     * @return array
     */
    public function getNotFinished($goodId, $end_time)
    {
        $criteria = Criteria::create();
        $goodId = (array)$goodId;
        $criteria = $criteria->andWhere(Criteria::expr()->in('goods_id', $goodId));
        $criteria = $criteria->andWhere(Criteria::expr()->eq('disabled', false));
        $criteria = $criteria->andWhere(Criteria::expr()->gte('end_time', $end_time));

        $entityList = $this->matching($criteria);
        $lists = [];
        foreach ($entityList as $entity) {
            $lists[] = $this->getColumnNamesData($entity);
        }
        $lists = $this->getLangService()->getListAddLang($lists,$this->multiLangField,$this->table,$this->getLang(),$this->prk);

        return $lists;
    }
}
