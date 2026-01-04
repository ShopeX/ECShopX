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

namespace AftersalesBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use AftersalesBundle\Entities\AftersalesDetail;
use Dingo\Api\Exception\ResourceException;
use Doctrine\Common\Collections\Criteria;
use Dingo\Api\Exception\UpdateResourceFailedException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AftersalesDetailRepository extends EntityRepository
{
    public $table = 'aftersales_detail';

    public function create($params)
    {
        // $filter = [
        //     'aftersales_bn' => $params['aftersales_bn'],
        //     'company_id' => $params['company_id'],
        // ];
        // $aftersalesDetail = $this->findBy($filter);
        // if($aftersalesDetail && count($aftersalesDetail) >=2) {
        //     throw new BadRequestHttpException("已申请过售后，不需要再进行申请");
        // }

        $aftersalesDetailEntity = new AftersalesDetail();
        $aftersales = $this->setAftersalesData($aftersalesDetailEntity, $params);

        $em = $this->getEntityManager();
        $em->persist($aftersales);
        $em->flush();

        $result = $this->getAftersalesData($aftersales);

        return $result;
    }

    public function update($filter, $updateInfo)
    {
        $aftersalesDetailEntity = $this->findOneBy($filter);
        if (!$aftersalesDetailEntity) {
            throw new UpdateResourceFailedException("售后单号为{$filter['aftersales_bn']}的售后单不存在");
        }
        $aftersales = $this->setAftersalesData($aftersalesDetailEntity, $updateInfo);
        $em = $this->getEntityManager();
        $em->persist($aftersales);
        $em->flush();

        $result = $this->getAftersalesData($aftersales);

        return $result;
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
            $entityProp = $this->setAftersalesData($entityProp, $data);
            $em->persist($entityProp);
            $em->flush();
            $result[] = $this->getAftersalesData($entityProp);
        }
        return $result;
    }

    public function getList($filter, $offset = 0, $limit = -1, $orderBy = ['create_time' => 'DESC'])
    {
        $criteria = Criteria::create();
        if ($filter) {
            foreach ($filter as $field => $value) {
                if (!is_null($value) && $value != '') {
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
        }

        $total = $this->getEntityManager()
            ->getUnitOfWork()
            ->getEntityPersister($this->getEntityName())
            ->count($criteria);
        $res['total_count'] = intval($total);

        $prderList = [];
        if ($res['total_count']) {
            $criteria = $criteria->orderBy($orderBy);
            if ($limit > 0) {
                $criteria = $criteria->setFirstResult($offset)
                                     ->setMaxResults($limit);
            }
            $list = $this->matching($criteria);
            foreach ($list as $v) {
                $order = $this->getAftersalesData($v);
                $prderList[] = $order;
            }
        }
        $res['list'] = $prderList;

        return $res;
    }

    public function get($filter)
    {
        $data = $this->findOneBy($filter);
        $result = [];
        if ($data) {
            $result = $this->getAftersalesData($data);
        }
        return $result;
    }

    private function setAftersalesData($aftersalesDetailEntity, $data)
    {
        if (isset($data['detail_id'])) {
            $aftersalesDetailEntity->setDetailId($data['detail_id']);
        }
        if (isset($data['company_id'])) {
            $aftersalesDetailEntity->setCompanyId($data['company_id']);
        }
        if (isset($data['user_id'])) {
            $aftersalesDetailEntity->setUserId($data['user_id']);
        }
        if (isset($data['distributor_id'])) {
            $aftersalesDetailEntity->setDistributorId($data['distributor_id']);
        }
        if (isset($data['aftersales_bn'])) {
            $aftersalesDetailEntity->setAftersalesBn($data['aftersales_bn']);
        }
        if (isset($data['order_id'])) {
            $aftersalesDetailEntity->setOrderId($data['order_id']);
        }
        if (isset($data['sub_order_id'])) {
            $aftersalesDetailEntity->setSubOrderId($data['sub_order_id']);
        }
        if (isset($data['goods_id'])) {
            $aftersalesDetailEntity->setGoodsId($data['goods_id']);
        }
        if (isset($data['item_id'])) {
            $aftersalesDetailEntity->setItemId($data['item_id']);
        }
        if (isset($data['item_bn'])) {
            $aftersalesDetailEntity->setItemBn($data['item_bn']);
        }
        if (isset($data['item_name'])) {
            $aftersalesDetailEntity->setItemName($data['item_name']);
        }
        if (isset($data['order_item_type'])) {
            $aftersalesDetailEntity->setOrderItemType($data['order_item_type']);
        }
        if (isset($data['item_pic'])) {
            $aftersalesDetailEntity->setItemPic($data['item_pic']);
        }
        if (isset($data['refund_fee'])) {
            $aftersalesDetailEntity->setRefundFee($data['refund_fee']);
        }
        if (isset($data['refund_point'])) {
            $aftersalesDetailEntity->setRefundPoint($data['refund_point']);
        }
        if (isset($data['num'])) {
            $aftersalesDetailEntity->setNum($data['num']);
        }
        if (isset($data['aftersales_type'])) {
            $aftersalesDetailEntity->setAftersalesType($data['aftersales_type']);
        }

        if (isset($data['progress'])) {
            $aftersalesDetailEntity->setProgress($data['progress']);
        }

        if (isset($data['aftersales_status'])) {
            $aftersalesDetailEntity->setAftersalesStatus($data['aftersales_status']);
        }

        if (isset($data['auto_refuse_time'])) {
            $aftersalesDetailEntity->setAutoRefuseTime($data['auto_refuse_time']);
        }

        // if (isset($data['contact_info'])) {
        //     $aftersalesDetailEntity->setContactInfo($data['contact_info']);
        // }

        // if (isset($data['reason'])) {
        //     $aftersalesDetailEntity->setReason($data['reason']);
        // }

        // if (isset($data['description'])) {
        //     $aftersalesDetailEntity->setDescription($data['description']);
        // }

        // if (isset($data['evidence_pic'])) {
        //     $aftersalesDetailEntity->setEvidencePic($data['evidence_pic']);
        // }

        // if (isset($data['refuse_reason'])) {
        //     $aftersalesDetailEntity->setRefuseReason($data['refuse_reason']);
        // }

        // if (isset($data['memo'])) {
        //     $aftersalesDetailEntity->setMemo($data['memo']);
        // }

        // if (isset($data['sendback_data'])) {
        //     $aftersalesDetailEntity->setSendbackData($data['sendback_data']);
        // }

        // if (isset($data['sendconfirm_data'])) {
        //     $aftersalesDetailEntity->setSendconfirmData($data['sendconfirm_data']);
        // }

        // if (isset($data['create_time'])) {
        //     $aftersalesDetailEntity->setCreateTime($data['create_time']);
        // }

        // if (isset($data['update_time'])) {
        //     $aftersalesDetailEntity->setUpdateTime($data['update_time']);
        // }

        if (isset($data['refunded_num'])) {
            $aftersalesDetailEntity->setRefundedNum($data['refunded_num']);
        }

        return $aftersalesDetailEntity;
    }
    public function getAftersalesData($aftersalesDetailEntity)
    {
        return [
            'detail_id' => $aftersalesDetailEntity->getDetailId(),
            'company_id' => $aftersalesDetailEntity->getCompanyId(),
            'user_id' => $aftersalesDetailEntity->getUserId(),
            'distributor_id' => $aftersalesDetailEntity->getDistributorId(),
            'aftersales_bn' => $aftersalesDetailEntity->getAftersalesBn(),
            'order_id' => $aftersalesDetailEntity->getOrderId(),
            'sub_order_id' => $aftersalesDetailEntity->getSubOrderId(),
            'goods_id' => $aftersalesDetailEntity->getGoodsId(),
            'item_id' => $aftersalesDetailEntity->getItemId(),
            'item_bn' => $aftersalesDetailEntity->getItemBn(),
            'item_name' => $aftersalesDetailEntity->getItemName(),
            'order_item_type' => $aftersalesDetailEntity->getOrderItemType(),
            'item_pic' => $aftersalesDetailEntity->getItemPic(),
            'num' => $aftersalesDetailEntity->getNum(),
            'refund_fee' => $aftersalesDetailEntity->getRefundFee(),
            'refund_point' => $aftersalesDetailEntity->getRefundPoint(),
            'aftersales_type' => $aftersalesDetailEntity->getAftersalesType(),
            'progress' => $aftersalesDetailEntity->getProgress(),
            'aftersales_status' => $aftersalesDetailEntity->getAftersalesStatus(),
            'create_time' => $aftersalesDetailEntity->getCreateTime(),
            'update_time' => $aftersalesDetailEntity->getUpdateTime(),
            'auto_refuse_time' => $aftersalesDetailEntity->getAutoRefuseTime(),
            // 'reason' => $aftersalesDetailEntity->getReason(),
            // 'description' => $aftersalesDetailEntity->getDescription(),
            // 'evidence_pic' => $aftersalesDetailEntity->getEvidencePic(),
            // 'refuse_reason' => $aftersalesDetailEntity->getRefuseReason(),
            // 'memo' => $aftersalesDetailEntity->getMemo(),
            // 'sendback_data' => $aftersalesDetailEntity->getSendbackData(),
            // 'sendconfirm_data' => $aftersalesDetailEntity->getSendconfirmData(),
            'refunded_num' => $aftersalesDetailEntity->getRefundedNum(),
        ];
    }

    public function __preFilter($filter)
    {
        $criteria = Criteria::create();
        if ($filter) {
            foreach ($filter as $field => $value) {
                if ($field == 'need_order') {
                    unset($filter[$field]);
                    continue;
                }
                if (!is_null($value) && $value != '') {
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
        }

        return $criteria;
    }

    public function count($filter)
    {
        $criteria = $this->__preFilter($filter);

        $total = $this->getEntityManager()
            ->getUnitOfWork()
            ->getEntityPersister($this->getEntityName())
            ->count($criteria);

        return $total;
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
     * 求和运算
     */
    public function sum($filter, $cols)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select($cols)
            ->from($this->table);
        if ($filter) {
            $this->_filter($filter, $qb);
        }
        $result = $qb->execute()->fetch();

        return $result;
    }
}
