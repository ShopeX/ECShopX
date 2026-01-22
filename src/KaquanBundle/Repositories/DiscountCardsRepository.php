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

use Carbon\Carbon;
use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;
use GoodsBundle\Services\MultiLang\MagicLangTrait;
use GoodsBundle\Services\MultiLang\MultiLangOutsideItemService;
use KaquanBundle\Entities\DiscountCards;

use Dingo\Api\Exception\ResourceException;
use KaquanBundle\Entities\UserDiscount;


class DiscountCardsRepository extends EntityRepository
{
    use MagicLangTrait;
    private $multiLangField = [
        'title','description'
    ];
    public $table = 'kaquan_discount_cards';
    public $prk = 'card_id';

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

        $entity = new DiscountCards();
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
    public function update(array $data, array $filter)
    {
        $entity = $this->findOneBy($filter);
        if (!$entity) {
            throw new ResourceException(trans('KaquanBundle.no_update_data_found'));
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
            throw new ResourceException(trans('KaquanBundle.no_update_data_found'));
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

    public function delete($filter)
    {
        $discountCard = $this->findOneBy($filter);
        if (!$discountCard) {
            return true;
        }
        $em = $this->getEntityManager();
        $em->remove($discountCard);
        $em->flush();
        return true;
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
     * [getList description]
     * @param  array   $cols
     * @param  array   $filter
     * @param  integer $offset
     * @param  integer $limit
     * @param  array   $orderBy
     * @return array
     */
    public function getList($cols = '*', $filter = [], $offset = 0, $limit = 50, $orderBy = ['created' => 'DESC'])
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();

        $qb->select($cols)
            ->from($this->table)
            ->orderBy('created', 'DESC');
        if ($offset >= 0) {
            $qb->setFirstResult($offset)
            ->setMaxResults($limit);
        }
        if ($filter) {
            $this->_filter($filter, $qb);
        }
        foreach ($orderBy as $key => $value) {
            $qb->addOrderBy($key, $value);
        }
        $listData = $qb->execute()->fetchAll();

        $listData = $this->getLangService()->getListAddLang($listData,$this->multiLangField,$this->table,$this->getLang(),$this->prk);
        return $listData;
    }



    /**
     * 获取可用优惠券列表
     *
     * @param $filter 更新的条件
     */
    public function effectiveLists($filter, $orderBy = ["created" => "DESC"], $pageSize = 100, $page = 1)
    {
        $criteria = Criteria::create();
        $criteria = $criteria->andWhere($criteria->expr()->eq('company_id', $filter['company_id']));
        if (isset($filter['card_type'])) {
            $criteria = $criteria->andWhere($criteria->expr()->eq('card_type', $filter['card_type']));
        }
        if (isset($filter['source_id'])) {
            $criteria = $criteria->andWhere($criteria->expr()->eq('source_id', $filter['source_id']));
        }
        $criteria = $criteria->andWhere(
            $criteria->expr()->orX(
                $criteria->expr()->andX(
                    $criteria->expr()->in('date_type', $filter['date_type']),
                    $criteria->expr()->eq('end_date', 0)
                ),
                $criteria->expr()->orX(
                    $criteria->expr()->gt('end_date', $filter['end_date'])
                )
            )
        );

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

        $lists = $this->getLangService()->getListAddLang($lists,$this->multiLangField,$this->table,$this->getLang(),$this->prk);
        $res["list"] = $lists;
        return $res;
    }

    /**
     * 获取可用优惠券列表
     *
     * @param $filter 更新的条件
     */
    public function effectiveFilterLists($filter, $orderBy = ["created" => "DESC"], $pageSize = 100, $page = 1)
    {
        $criteria = Criteria::create();
        $criteria = $criteria->andWhere($criteria->expr()->eq('company_id', $filter['company_id']));
        $criteria = $criteria->andWhere(
            $criteria->expr()->orX(
                $criteria->expr()->andX(
                    $criteria->expr()->eq('date_type', $filter['date_type']),
                    $criteria->expr()->eq('end_date', 0)
                ),
                $criteria->expr()->orX(
                    $criteria->expr()->gt('end_date', $filter['end_date'])
                )
            )
        );
        if ($filter['distributor_id'] ?? 0) {
            $criteria = $criteria->andWhere($criteria->expr()->orX(
                $criteria->expr()->contains('distributor_id', $filter['distributor_id']),
                $criteria->expr()->eq('distributor_id', ',')
            ));
        }
        unset($filter['company_id'], $filter['date_type'], $filter['distributor_id'], $filter['end_date']);
        foreach ($filter as $field => $value) {
            $list = explode("|", $field);
            if (count($list) > 1) {
                list($v, $k) = $list;
                $criteria = $criteria->andWhere($criteria->expr()->$k($v, $value));
                continue;
            } elseif (is_array($value)) {
                $criteria = $criteria->andWhere($criteria->expr()->in($field, $value));
            } else {
                $criteria = $criteria->andWhere($criteria->expr()->eq($field, $value));
            }
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

        $lists = $this->getLangService()->getListAddLang($lists,$this->multiLangField,$this->table,$this->getLang(),$this->prk);
        $res["list"] = $lists;
        return $res;
    }

    /**
     * [totalNum]
     * @param  array  $filter
     * @return int
     */
    public function totalNum($filter = array())
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('count(card_id)')
            ->from($this->table);
        if ($filter) {
            $this->_filter($filter, $qb);
        }
        $count = $qb->execute()->fetchColumn();
        return intval($count);
    }

    /**
     * [_filter description]
     * @param  [type] $filter
     * @param  [type] &$qb
     */
    public function _filter($filter, &$qb)
    {
        if (isset($filter['or']) && $filter['or']) {
            $this->__orFilter($filter['or'], $qb);
            unset($filter['or']);
        }
        if (isset($filter['end_date'])) {
            $filterValue = $qb->expr()->literal($filter['end_date']);
            $qb->andWhere($qb->expr()->andX(
                $qb->expr()->gt('end_date', $filterValue)
            ));
            $qb->orWhere($qb->expr()->andX(
                $qb->expr()->eq('end_date', 0)
            ));
            $qb->orWhere($qb->expr()->andX(
                $qb->expr()->isNull('end_date')
            ));
            unset($filter['end_date']);
        }
        if ($filter) {
            foreach ($filter as $field => $value) {
                $list = explode('|', $field);
                if (count($list) > 1) {
                    list($v, $k) = $list;
                    if ($k == 'contains') {
                        $k = 'like';
                    }
                    if ($k == 'like') {
                        $value = '%' . $value . '%';
                    }
                    if (is_array($value)) {
                        $qb = $qb->andWhere($qb->expr()->$k($v, $value));
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
                    if ($field == 'date_status') {
                        switch ($value) {
                            case 1:
                                $qb->andWhere(
                                    $qb->expr()->andX(
                                        $qb->expr()->gt('begin_date', 0),
                                        $qb->expr()->gt('begin_date', time())
                                    )
                                );
                                break;
                            case 2:
                                $qb->andWhere(
                                    $qb->expr()->orX(
                                        $qb->expr()->andX(
                                            $qb->expr()->lt('begin_date', time()),
                                            $qb->expr()->gt('end_date', time())
                                        ),
                                        $qb->expr()->orX(
                                            $qb->expr()->eq('end_date', 0)
                                        )
                                    )
                                );
                                break;
                            case 3:
                                $qb->andWhere(
                                    $qb->expr()->andX(
                                        $qb->expr()->gt('end_date', 0),
                                        $qb->expr()->lt('end_date', time())
                                    )
                                );
                                break;
                        }
                    } else {
                        $qb = $qb->andWhere($qb->expr()->eq($field, $qb->expr()->literal($value)));
                    }
                }
            }
        }
    }

    private function __orFilter($filter, &$qb)
    {
        foreach ($filter as $key => $filterValue) {
            if (is_array($filterValue)) {
                array_walk($filterValue, function (&$value) use ($qb) {
                    $value = $qb->expr()->literal($value);
                });
            } elseif (!is_numeric($filterValue)) {
                $filterValue = $qb->expr()->literal($filterValue);
            }
            $list = explode('|', $key);
            if (count($list) > 1) {
                list($v, $k) = $list;
                $qb->orWhere($qb->expr()->andX(
                    $qb->expr()->$k($v, $filterValue)
                ));
            } elseif (is_array($filterValue)) {
                $qb->orWhere($qb->expr()->andX(
                    $qb->expr()->in($key, $filterValue)
                ));
            } else {
                $qb->orWhere($qb->expr()->andX(
                    $qb->expr()->eq($key, $filterValue)
                ));
            }
        }
    }

    public function get($filter = [])
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('*')
            ->from($this->table)
            ->orderBy('updated', 'DESC');
        if ($filter) {
            foreach ($filter as $key => $value) {
                $qb->andWhere($qb->expr()->andX(
                    $qb->expr()->eq($key, $qb->expr()->literal($value))
                ));
            }
        }
        $list = $qb->execute()->fetchAll();
        if (!$list) {
            return false;
        }

        $list = $this->getLangService()->getListAddLang($list,$this->multiLangField,$this->table,$this->getLang(),$this->prk);
        return $list;
    }

    /**
     * [updateStore description]
     * @param  [type] $cardId
     * @param  [type] $companyId
     * @param  [type] $store
     * @param  [type] $type
     * @return boolean
     */
    public function updateStore($cardId, $companyId, $store, $type)
    {
        $store = abs($store);
        $filter['card_id'] = $cardId;
        $filter['company_id'] = $companyId;
        $data = $this->findOneBy($filter);

        if ($data) {
            $oldstore = intval($data->getQuantity());

            $relFilter['card_id'] = $cardId;
            $relFilter['company_id'] = $companyId;
            $cardRelatedRepository = app('registry')->getManager('default')->getRepository(UserDiscount::class);
            $getNum = $cardRelatedRepository->getTotalNum($relFilter);

            switch ($type) {
            case "reduce":
                if ($oldstore - $getNum - $store <= 0) {
                    $lastNum = $getNum;
                } else {
                    $lastNum = $oldstore - $store;
                }
                break;
            case "increase":
                $lastNum = $oldstore + $store;
                break;
            }
            if ($lastNum > 2147483647) {
                throw new ResourceException(trans('KaquanBundle.stock_field_exceeds_max'));
            }
            $em = $this->getEntityManager();
            $data->setQuantity($lastNum);
            $em->persist($data);
            $em->flush();
        }
        return [];
    }

    /**
     * 设置entity数据，用于插入和更新操作
     *
     * @param DiscountCards $entity
     * @param $data
     */
    private function setColumnNamesData($entity, $data)
    {
        if (isset($data["company_id"]) && $data["company_id"]) {
            $entity->setCompanyId($data["company_id"]);
        }
        if (isset($data["card_type"]) && $data["card_type"]) {
            $entity->setCardType($data["card_type"]);
        }
        if (isset($data["title"]) && $data["title"]) {
            $entity->setTitle($data["title"]);
        }
        if (isset($data["color"]) && $data["color"]) {
            $entity->setColor($data["color"]);
        }
        if (isset($data["description"]) && $data["description"]) {
            $entity->setDescription($data["description"]);
        }
        if (isset($data["date_type"]) && $data["date_type"]) {
            $entity->setDateType($data["date_type"]);
        }
        //当前字段非必填
        if (isset($data["begin_date"])) {
            $entity->setBeginDate($data["begin_date"]);
        }
        //当前字段非必填
        if (isset($data["end_date"])) {
            $entity->setEndDate($data["end_date"]);
        }
        //当前字段非必填
        if (isset($data["fixed_term"]) && $data["fixed_term"]) {
            $entity->setFixedTerm($data["fixed_term"]);
        }
        //当前字段非必填
        if (isset($data["service_phone"]) && $data["service_phone"]) {
            $entity->setServicePhone($data["service_phone"]);
        }

        //当前字段非必填
        if (isset($data["custom_url_name"]) && $data["custom_url_name"]) {
            $entity->setCustomUrlName($data["custom_url_name"]);
        }
        //当前字段非必填
        if (isset($data["custom_url"]) && $data["custom_url"]) {
            $entity->setCustomUrl($data["custom_url"]);
        }
        //当前字段非必填
        if (isset($data["custom_url_sub_title"]) && $data["custom_url_sub_title"]) {
            $entity->setCustomUrlSubTitle($data["custom_url_sub_title"]);
        }

        //当前字段非必填
        if (isset($data["get_limit"]) && $data["get_limit"]) {
            $entity->setGetLimit($data["get_limit"]);
        }
        //当前字段非必填
        if (isset($data["use_limit"]) && $data["use_limit"]) {
            $entity->setUseLimit($data["use_limit"]);
        }

        //当前字段非必填
        if (isset($data["abstract"]) && $data["abstract"]) {
            $entity->setAbstract($data["abstract"]);
        }
        //当前字段非必填
        if (isset($data["icon_url_list"]) && $data["icon_url_list"]) {
            $entity->setIconUrlList($data["icon_url_list"]);
        }
        //当前字段非必填
        if (isset($data["text_image_list"]) && $data["text_image_list"]) {
            $entity->setTextImageList($data["text_image_list"]);
        }
        //当前字段非必填
        if (isset($data["time_limit"]) && $data["time_limit"]) {
            $entity->setTimeLimit($data["time_limit"]);
        }
        //当前字段非必填
        if (isset($data["gift"]) && $data["gift"]) {
            $entity->setGift($data["gift"]);
        }
        //当前字段非必填
        if (isset($data["default_detail"]) && $data["default_detail"]) {
            $entity->setDefaultDetail($data["default_detail"]);
        }
        //当前字段非必填
        if (isset($data["discount"]) && $data["discount"]) {
            $discount = bcsub(100, bcmul($data['discount'], 10));
            $entity->setDiscount($discount);
        }
        //当前字段非必填
        if (isset($data["least_cost"]) && $data["least_cost"]) {
            $leastCost = bcmul($data['least_cost'], 100);
            $entity->setLeastCost($leastCost);
        }
        //当前字段非必填
        if (isset($data["reduce_cost"]) && $data["reduce_cost"]) {
            $reduceCost = bcmul($data['reduce_cost'], 100);
            $entity->setReduceCost($reduceCost);
        }
        //当前字段非必填
        if (isset($data["deal_detail"]) && $data["deal_detail"]) {
            $entity->setDealDetail($data["deal_detail"]);
        }
        //当前字段非必填
        if (isset($data["accept_category"]) && $data["accept_category"]) {
            $entity->setAcceptCategory($data["accept_category"]);
        }
        //当前字段非必填
        if (isset($data["reject_category"]) && $data["reject_category"]) {
            $entity->setRejectCategory($data["reject_category"]);
        }
        //当前字段非必填
        if (isset($data["object_use_for"]) && $data["object_use_for"]) {
            $entity->setObjectUseFor($data["object_use_for"]);
        }
        //当前字段非必填
        if (isset($data['can_use_with_other_discount'])) {
            if ($data['can_use_with_other_discount'] == "true") {
                $entity->setCanUseWithOtherDiscount(true);
            } else {
                $entity->setCanUseWithOtherDiscount(false);
            }
        }
        //当前字段非必填
        if (isset($data["use_platform"])) {
            $entity->setUsePlatform($data["use_platform"]);
        }
        if (isset($data["quantity"]) && $data["quantity"]) {
            $entity->setQuantity($data["quantity"]);
        }
        //当前字段非必填
        if (isset($data['use_all_shops'])) {
            if ($data['use_all_shops'] == 'true') {
                $entity->setUseAllShops(true);
                $entity->setRelShopsIds(',');
                $entity->setDistributorId(',');
            } else {
                $entity->setUseAllShops(false);

                //指定门店
                $data['rel_shops_ids'] = isset($data['rel_shops_ids']) && is_array($data['rel_shops_ids']) ? $data['rel_shops_ids'] : [];
                if ($data['rel_shops_ids']) {
                    $entity->setRelShopsIds(',' . implode(',', $data['rel_shops_ids']) . ',');
                } else {
                    $entity->setRelShopsIds(',');
                }

                //指定店铺
                $data['distributor_id'] = isset($data['distributor_id']) && is_array($data['distributor_id']) ? $data['distributor_id'] : [];
                if ($data['distributor_id']) {
                    $entity->setDistributorId(',' . implode(',', $data['distributor_id']) . ',');
                } else {
                    $entity->setDistributorId(',');
                }
            }
        }
        //当前字段非必填
        if (isset($data["use_scenes"]) && $data["use_scenes"]) {
            $entity->setUseScenes($data["use_scenes"]);
        }
        //当前字段非必填
        if (isset($data["self_consume_code"])) {
            $entity->setSelfConsumeCode($data["self_consume_code"]);
        }
        //当前字段非必填
        if (isset($data['receive'])) {
            $entity->setReceive($data['receive']);
        }
        if (isset($data["created"]) && $data["created"]) {
            $entity->setCreated($data["created"]);
        }
        if (isset($data["updated"]) && $data["updated"]) {
            $entity->setUpdated($data["updated"]);
        }
        if (isset($data["most_cost"]) && $data["most_cost"]) {
            $mostCost = bcmul($data['most_cost'], 100);
            $entity->setMostCost($mostCost);
        }
        if (isset($data["use_bound"])) {
            $entity->setUseBound($data['use_bound']);
        }
        if (isset($data["tag_ids"])) {
            $entity->setTagIds($data["tag_ids"]);
        }
        if (isset($data["brand_ids"])) {
            $entity->setBrandIds($data["brand_ids"]);
        }
        if (isset($data["apply_scope"])) {
            $entity->setApplyScope($data["apply_scope"]);
        }
        if (isset($data["card_code"])) {
            $entity->setCardCode($data["card_code"]);
        }
        if (isset($data["card_rule_code"])) {
            $cardRuleCode = $data["card_rule_code"] ?? '';
            $entity->setCardRuleCode($cardRuleCode);
        }
        if (isset($data['send_end_time'])) {
            $entity->setSendEndTime($data['send_end_time']);
        }
        if (isset($data['send_begin_time'])) {
            $entity->setSendBeginTime($data['send_begin_time']);
        }
        if (isset($data['kq_status'])) {
            $entity->setKqStatus($data['kq_status']);
        }
        if (isset($data['lock_time'])) {
            $entity->setLockTime($data['lock_time']);
        }
        if (isset($data['grade_ids'])) {
            if (is_array($data['grade_ids'])) {
                if ($data['grade_ids']) {
                    $data['grade_ids'] = ','.implode(',', $data['grade_ids']).',';
                } else {
                    $data['grade_ids'] = '';
                }
            }
            $entity->setGradeIds($data['grade_ids']);
        }
        if (isset($data['vip_grade_ids'])) {
            if (is_array($data['vip_grade_ids'])) {
                if ($data['vip_grade_ids']) {
                    $data['vip_grade_ids'] = ','.implode(',', $data['vip_grade_ids']).',';
                } else {
                    $data['vip_grade_ids'] = '';
                }
            }
            $entity->setVipGradeIds($data['vip_grade_ids']);
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
     * @param DiscountCards $entity
     */
    private function getColumnNamesData($entity)
    {
        return [
            'card_id' => $entity->getCardId(),
            'company_id' => $entity->getCompanyId(),
            'card_type' => $entity->getCardType(),
            'brand_name' => $entity->getBrandName(),
            'logo_url' => $entity->getLogoUrl(),
            'title' => $entity->getTitle(),
            'color' => $entity->getColor(),
            'notice' => $entity->getNotice(),
            'description' => $entity->getDescription(),
            'date_type' => $entity->getDateType(),
            'begin_date' => $entity->getBeginDate(),
            'end_date' => $entity->getEndDate(),
            'begin_time' => $entity->getBeginDate(),
            'end_time' => $entity->getEndDate(),
            'fixed_term' => $entity->getFixedTerm(),
            'service_phone' => $entity->getServicePhone(),
            'center_title' => $entity->getCenterTitle(),
            'center_sub_title' => $entity->getCenterSubTitle(),
            'center_url' => $entity->getCenterUrl(),
            'custom_url_name' => $entity->getCustomUrlName(),
            'custom_url' => $entity->getCustomUrl(),
            'custom_url_sub_title' => $entity->getCustomUrlSubTitle(),
            'promotion_url_name' => $entity->getPromotionUrlName(),
            'promotion_url' => $entity->getPromotionUrl(),
            'promotion_url_sub_title' => $entity->getPromotionUrlSubTitle(),
            'get_limit' => $entity->getGetLimit(),
            'use_limit' => $entity->getUseLimit(),
            'can_share' => $entity->getCanShare(),
            'can_give_friend' => $entity->getCanGiveFriend(),
            'abstract' => $entity->getAbstract(),
            'icon_url_list' => $entity->getIconUrlList(),
            'text_image_list' => $entity->getTextImageList(),
            'time_limit' => $entity->getTimeLimit(),
            'gift' => $entity->getGift(),
            'default_detail' => $entity->getDefaultDetail(),
            'discount' => $entity->getDiscount(),
            'least_cost' => $entity->getLeastCost(),
            'reduce_cost' => $entity->getReduceCost(),
            'deal_detail' => $entity->getDealDetail(),
            'accept_category' => $entity->getAcceptCategory(),
            'reject_category' => $entity->getRejectCategory(),
            'object_use_for' => $entity->getObjectUseFor(),
            'can_use_with_other_discount' => $entity->getCanUseWithOtherDiscount(),
            'use_platform' => $entity->getUsePlatform(),
            'quantity' => $entity->getQuantity(),
            'use_all_shops' => $entity->getUseAllShops(),
            'rel_shops_ids' => $entity->getRelShopsIds(),
            'use_scenes' => $entity->getUseScenes(),
            'self_consume_code' => $entity->getSelfConsumeCode(),
            'receive' => $entity->getReceive(),
            'distributor_id' => $entity->getDistributorId(),
            'created' => $entity->getCreated(),
            'updated' => $entity->getUpdated(),
            'most_cost' => $entity->getMostCost(),
            'use_bound' => $entity->getUseBound(),
            'tag_ids' => array_filter(explode(',', $entity->getTagIds())),
            'brand_ids' => array_filter(explode(',', $entity->getBrandIds())),
            'apply_scope' => $entity->getApplyScope(),
            'card_code' => $entity->getCardCode(),
            'card_rule_code' => $entity->getCardRuleCode(),
            'kq_status' => $entity->getKqStatus(),
            'send_begin_time' => $entity->getSendBeginTime(),
            'send_end_time' => $entity->getSendEndTime(),
            'lock_time' => $entity->getLockTime(),
            'source_type' => $entity->getSourceType(),
            'source_id' => $entity->getSourceId(),
            'grade_ids' => $entity->getGradeIds() ? explode(',', trim($entity->getGradeIds(), ',')) : [],
            'vip_grade_ids' => $entity->getVipGradeIds() ? explode(',', trim($entity->getVipGradeIds(), ',')) : [],
        ];
    }

    /**
     * 根据条件获取列表数据
     *
     * @param $filter array
     */
    public function getLists($filter, $cols = '*', $page = 1, $pageSize = -1, $orderBy = array())
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()->select($cols)->from($this->table);
        $this->_filter($filter, $qb);
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
        foreach ($lists as &$v) {
            if ($v['created'] ?? 0) {
                $v['created_date'] = date('Y-m-d H:i:s', $v['created']);
            }
        }

        $lists = $this->getLangService()->getListAddLang($lists,$this->multiLangField,$this->table,$this->getLang(),$this->prk);
        return $lists;
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
        $criteria->andWhere($criteria->expr()->or(
            $criteria->expr()->eq("date_type", $criteria->expr()->literal("DATE_TYPE_FIX_TERM")),
            $criteria->expr()->and(
                $criteria->expr()->eq("date_type", $criteria->expr()->literal("DATE_TYPE_FIX_TIME_RANGE")),
                $criteria->expr()->lte("begin_date", $now),
                $criteria->expr()->gte("end_date", $now)
            ),
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

        $dataList = $criteria->execute()->fetchAll();

        $dataList = $this->getLangService()->getListAddLang($dataList,$this->multiLangField,$this->table,$this->getLang(),$this->prk);
        return  $dataList;

    }
}
