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

use Doctrine\ORM\EntityRepository;
use KaquanBundle\Entities\UserDiscount;
use Dingo\Api\Exception\ResourceException;

class UserDiscountRepository extends EntityRepository
{
    public $table = "kaquan_user_discount";

    public function userGetCard($params, $cardInfo)
    {
        $related = null;
        $filter = [
            'code' => $params['code'],
            'card_id' => $params['card_id'],
        ];
        $userCardData = $this->findOneBy($filter);
        if ($userCardData && $userCardData->getUserId() == $params['user_id']) {
            throw new ResourceException(trans('KaquanBundle.already_received_coupon'));
        } elseif ($userCardData && $userCardData->getUserId() != $params['user_id']) {
            throw new ResourceException(trans('KaquanBundle.failed_to_receive_coupon'));
        }

        $userCardData = new UserDiscount();
        $userCardData->setCompanyId($params['company_id']);
        $userCardData->setCardId($params['card_id']);
        $userCardData->setCode($params['code']);
        $userCardData->setStatus($params['status']);
        $userCardData->setUserId($params['user_id']);
        $userCardData->setSourceType($params['source_type']);
        $userCardData->setActivityName($params['activity_name'] ?? '');

        //优惠券数据冗余
        $userCardData->setUseScenes($cardInfo['use_scenes']);
        $userCardData->setTitle($cardInfo['title']);
        $userCardData->setColor($cardInfo['color']);
        $userCardData->setDiscount($cardInfo['discount']);
        $userCardData->setCardType($cardInfo['card_type']);
        $userCardData->setLeastCost($cardInfo['least_cost']);
        $userCardData->setReduceCost($cardInfo['reduce_cost']);
        $userCardData->setUseCondition($cardInfo['use_condition']);
        $userCardData->setRelShopsIds($cardInfo['rel_shops_ids']);
        $userCardData->setUseBound($cardInfo['use_bound']);
        $userCardData->setRelDistributorIds($cardInfo['distributor_id']);
        $userCardData->setRelItemIds($cardInfo['rel_item_ids']);
        //$userCardData->setRelCategoryIds($cardInfo['rel_category_ids']);
        $userCardData->setBeginDate($cardInfo['begin_date']);
        $userCardData->setEndDate($cardInfo['end_date']);
        $userCardData->setUsePlatform($cardInfo['use_platform']);
        $userCardData->setMostCost($cardInfo['most_cost']);
        $userCardData->setGetDate(time());
        $userCardData->setSalespersonId($params['salesperson_id'] ?? 0);
        $userCardData->setApplyScope($cardInfo["apply_scope"] ?? '');
        if (isset($params['dm_card_code'])) {
            $userCardData->setDmCardCode($params['dm_card_code']);
        }
        //优惠券领取量更新
        $em = $this->getEntityManager();
        $em->persist($userCardData);
        $em->flush();
        return ['status' => true];
    }

    /**
     * 核销事件更新用户卡券数据
     */
    public function userConsumeCardUpdate($params, $filter)
    {
        $related = null;
        $em = $this->getEntityManager();
        $userCardData = $this->findOneBy($filter);
        if (!$userCardData) {
            return false;
        }

        //修改卡券库存和已领取量
        //if ($userCardData->getStatus() != 2) {
        //$related = $this->__cardNumberSet($userCardData->getCardId(), $filter['company_id'], "consumeCard");
        //}

        if (isset($params['consume_source'])) {
            $userCardData->setConsumeSource($params['consume_source']);
        }
        if (isset($params['location_name'])) {
            $userCardData->setLocationName($params['location_name']);
        }
        if (isset($params['location_id'])) {
            $userCardData->setLocationId($params['location_id']);
        }

        //扫码核销 核销员Id
        if (isset($params['staff_open_id'])) {
            $userCardData->setStaffOpenId($params['staff_open_id']);
        }
        if (isset($params['verify_code'])) {
            $userCardData->setVerifyCode($params['verify_code']);
        }

        //自助核销 备注金额
        if (isset($params['remark_amount'])) {
            $userCardData->setRemarkAmount($params['remark_amount']);
        }
        if (isset($params['consume_outer_str'])) {
            $userCardData->setConsumeOuterStr($params['consume_outer_str']);
        }

        //买单核销 订单号
        if (isset($params['trans_id'])) {
            $userCardData->setTransId($params['trans_id']);
        }

        //买单核销 实付金额
        if (isset($params['fee'])) {
            $userCardData->setFee($params['fee']);
        }

        //买单核销 应付金额
        if (isset($params['original_fee'])) {
            $userCardData->setOriginalFee($params['original_fee']);
        }

        $userCardData->setStatus($params['status']);

        $em = $this->getEntityManager();
        $em->persist($userCardData);
        $em->flush();

        return ['status' => true];
    }

    /**
     * 更新用户卡券有效期数据
     */
    public function updateUserCard($params, $filter)
    {
        $em = $this->getEntityManager();
        /** @var UserDiscount $userCardData */
        $userCardData = $this->findOneBy($filter);
        if (!$userCardData) {
            return false;
        }

        if (isset($params['begin_date'])) {
            $userCardData->setBeginDate($params['begin_date']);
        }
        if (isset($params['end_date'])) {
            $userCardData->setEndDate($params['end_date']);
        }
        if (isset($params['rel_item_ids'])) {
            $userCardData->setRelItemIds($params['rel_item_ids']);
        }
        if (isset($params['rel_distributor_ids'])) {
            $userCardData->setRelDistributorIds($params['rel_distributor_ids']);
        }
        if (isset($params['status'])) {
            $userCardData->setStatus($params['status']);
        }
        if (isset($params['used_time'])) {
            $userCardData->setUsedTime($params['used_time']);
        }
        if (isset($params['expired_time'])) {
            $userCardData->setExpiredTime($params['expired_time']);
        }

        $em = $this->getEntityManager();
        $em->persist($userCardData);
        $em->flush();

        return ['status' => true];
    }

    public function userDelCard($filter)
    {
        $userCardData = $this->findOneBy($filter);
        if (!$userCardData) {
            return false;
        }
        $this->getEntityManager()->remove($userCardData);
        $this->getEntityManager()->flush($userCardData);
        return true;
    }

    /**
     * 获取所有过期的兑换券id列表
     */
    public function getExpiredCardIds($limit = 100)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $cols = ['id'];
        $qb->select($cols)
            ->from($this->table)
            ->orderBy('expired_time', 'DESC')
            ->setMaxResults($limit);
        $this->__filter([
            'expired_time|lte' => time(),
            'status' => 10,
        ], $qb);
        return $qb->execute()->fetchAll();
    }

    /**
     * 获取用户可用的优惠券列表
     */
    public function getUserCardList($filter, $offset = 0, $limit = 100)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $cols = ['*'];
        $qb->select($cols)
            ->from($this->table)
            ->orderBy('status', 'ASC')
            ->orderBy('end_date', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);
        if ($filter) {
            $this->__filter($filter, $qb);
        }
        $listData = $qb->execute()->fetchAll();
        return $listData;
    }

    /**
     * 获取用户卡券领取以及使用信息
     * @param $filter 查询条件
     * @param int $offset 分页开始条数
     * @param int $limit 分页条数
     * @return mixed
     */
    public function getCardUserList($filter, $offset = 0, $limit = 100)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $cols = ['*'];
        $qb->select($cols)
            ->from($this->table)
            ->orderBy('end_date', 'ASC')
            ->orderBy('status', 'ASC')
            ->orderBy('id', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);
        if ($filter) {
            $this->__filter($filter, $qb);
        }
        $listData = $qb->execute()->fetchAll();
        return $listData;
    }

    private function __filter($filter, &$qb)
    {
        if (isset($filter['or']) && $filter['or']) {
            $this->__orFilter($filter['or'], $qb);
            unset($filter['or']);
        }
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

    /**private function __filter($filter, &$qb)
    {
        if (isset($filter['or']) && $filter['or']) {
            $this->__orFilter($filter['or'], $qb);
            unset($filter['or']);
        }

        foreach ($filter as $key => $filterValue) {
            if (is_array($filterValue)) {
                array_walk($filterValue, function (&$value) use ($qb) {
                    $value = $qb->expr()->literal($value);
                });
            } elseif (!is_numeric($filterValue) || $key == 'code') {
                $filterValue = $qb->expr()->literal($filterValue);
            }
            $list = explode('|', $key);
            if (count($list) > 1) {
                list($v, $k) = $list;
                $qb->andWhere($qb->expr()->andX(
                    $qb->expr()->$k($v, $filterValue)
                ));
            } elseif (is_array($filterValue)) {
                $qb->andWhere($qb->expr()->andX(
                    $qb->expr()->in($key, $filterValue)
                ));
            } else {
                $qb->andWhere($qb->expr()->andX(
                    $qb->expr()->eq($key, $filterValue)
                ));
            }
        }
    }**/

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

    /**
     * 获取用户可用的优惠券总数
     */
    public function getTotalNum($filter)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('count(id)')
            ->from($this->table);
        if ($filter) {
            $this->__filter($filter, $qb);
        }
        $count = $qb->execute()->fetchColumn();
        return intval($count);
    }


    public function getTotalNumGroupBy(array $filter, string $select = 'count(*) as total_num,card_id', string $groupBy = 'card_id'): array
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select($select)
            ->from($this->table)
            ->groupBy($groupBy);

        if ($filter) {
            $this->__filter($filter, $qb);
        }
        return $qb->execute()->fetchAll();
    }


    /**
     * [get 获取指定领取的优惠券详情]
     * @param  array $filter
     * @return array
     */
    public function get($filter)
    {
        $data = $this->findOneBy($filter);
        return $data;
    }

    public function getDiscountInfoBy($filter, $row = ['card_id', 'card_type', 'description', 'code', 'user_id'])
    {
        $f = ['card_id', 'company_id', 'card_type', 'use_platform', 'use_scenes','most_cost'];
        if ($row) {
            foreach ($row as $r) {
                if (in_array($r, $f)) {
                    $r = 'kud'.$r;
                }
                $cols[] = $r;
            }
        } else {
            $cols = ['card_id', 'card_type', 'description', 'code', 'user_id'];
        }
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select($cols)
            ->from('kaquan_discount_cards', 'kdc')
            ->leftjoin('kdc', 'kaquan_user_discount', 'kud', 'kdc.card_id = kri.card_id');
        if ($filter) {
            $f = ['card_id', 'company_id', 'card_type', 'use_platform', 'use_scenes','most_cost'];
            foreach ($filter as $key => $filterValue) {
                if (is_array($filterValue)) {
                    array_walk($filterValue, function (&$value) use ($qb) {
                        $value = $qb->expr()->literal($value);
                    });
                } elseif (!is_numeric($filterValue) || $key == 'code') {
                    $filterValue = $qb->expr()->literal($filterValue);
                }
                if (in_array($key, $f)) {
                    $key = 'kud'.$key;
                }
                $list = explode('|', $key);
                if (count($list) > 1) {
                    list($v, $k) = $list;
                    if (in_array($v, $f)) {
                        $v = 'kud'.$v;
                    }
                    $qb->andWhere($qb->expr()->andX(
                        $qb->expr()->$k($v, $filterValue)
                    ));
                } elseif (is_array($filterValue)) {
                    $qb->andWhere($qb->expr()->andX(
                        $qb->expr()->in($key, $filterValue)
                    ));
                } else {
                    $qb->andWhere($qb->expr()->andX(
                        $qb->expr()->eq($key, $filterValue)
                    ));
                }
            }
        }
        $listData = $qb->execute()->fetchAll();
        return $listData;
    }
}
