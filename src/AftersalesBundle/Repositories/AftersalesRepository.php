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
use AftersalesBundle\Entities\Aftersales;
use Dingo\Api\Exception\ResourceException;
use Doctrine\Common\Collections\Criteria;
use Dingo\Api\Exception\UpdateResourceFailedException;

class AftersalesRepository extends EntityRepository
{
    public $table = 'aftersales';

    public function create($params)
    {
        $filter = [
            'aftersales_bn' => $params['aftersales_bn'],
            'company_id' => $params['company_id'],
            'order_id' => $params['order_id'],
            // 'item_id' => $params['item_id'],
        ];
        $aftersalesEntity = $this->findOneBy($filter);
        if (!$aftersalesEntity) {
            $aftersalesEntity = new Aftersales();
        }
        $aftersales = $this->setAftersalesData($aftersalesEntity, $params);

        $em = $this->getEntityManager();
        $em->persist($aftersales);
        $em->flush();

        $result = $this->getAftersalesData($aftersales);

        return $result;
    }

    public function update($filter, $updateInfo)
    {
        $aftersales = $this->findOneBy($filter);
        if (!$aftersales) {
            throw new UpdateResourceFailedException("售后单号为{$filter['aftersales_bn']}的售后单不存在");
        }
        $aftersales = $this->setAftersalesData($aftersales, $updateInfo);
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
        // CRC: 2367340174
        $criteria = $this->__preFilter($filter);

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
     * 统计数量
     */
    public function sum($filter, $field)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('sum('.$field.')')
            ->from($this->table);
        if ($filter) {
            $this->_filter($filter, $qb);
        }
        $sum = $qb->execute()->fetchColumn();
        return $sum;
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
            if ($field == 'need_order') {
                unset($filter[$field]);
                continue;
            }

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

    public function __preFilter($filter)
    {
        $criteria = Criteria::create();
        if ($filter) {
            if (isset($filter['or']) && $filter['or']) {
                foreach ($filter['or'] as $key => $filterValue) {
                    $list = explode('|', $key);
                    if (count($list) > 1) {
                        list($v, $k) = $list;
                        $orWhere[] = $criteria->expr()->$k($v, $filterValue);
                    } elseif (is_array($filterValue)) {
                        $orWhere[] = $criteria->expr()->in($key, $filterValue);
                    } else {
                        $orWhere[] = $criteria->expr()->eq($key, $filterValue);
                    }
                }
                $criteria->andWhere(
                    $criteria->expr()->orX(...$orWhere)
                );
                unset($filter['or']);
            }

            $fixedencryptCol = ['mobile'];
            foreach ($fixedencryptCol as $col) {
                if (isset($filter[$col])) {
                    $filter[$col] = fixedencrypt($filter[$col]);
                }
            }
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

        // 构建QueryBuilder用于获取SQL
        $qb = $this->createQueryBuilder('a');
        $filterForSql = $filter ?: [];
        if ($filterForSql) {
            if (isset($filterForSql['or']) && $filterForSql['or']) {
                $orWhere = [];
                foreach ($filterForSql['or'] as $key => $filterValue) {
                    $list = explode('|', $key);
                    if (count($list) > 1) {
                        list($v, $k) = $list;
                        if ($k == 'contains') {
                            $k = 'like';
                            $filterValue = '%'.$filterValue.'%';
                        }
                        $orWhere[] = $qb->expr()->$k('a.'.$v, $qb->expr()->literal($filterValue));
                    } elseif (is_array($filterValue)) {
                        array_walk($filterValue, function (&$colVal) use ($qb) {
                            $colVal = $qb->expr()->literal($colVal);
                        });
                        $orWhere[] = $qb->expr()->in('a.'.$key, $filterValue);
                    } else {
                        $orWhere[] = $qb->expr()->eq('a.'.$key, $qb->expr()->literal($filterValue));
                    }
                }
                $qb->andWhere($qb->expr()->orX(...$orWhere));
            }

            $fixedencryptCol = ['mobile'];
            foreach ($fixedencryptCol as $col) {
                if (isset($filterForSql[$col])) {
                    $filterForSql[$col] = fixedencrypt($filterForSql[$col]);
                }
            }
            foreach ($filterForSql as $field => $value) {
                if ($field == 'need_order' || $field == 'or') {
                    continue;
                }
                if (!is_null($value) && $value != '') {
                    $list = explode('|', $field);
                    if (count($list) > 1) {
                        list($v, $k) = $list;
                        if ($k == 'contains') {
                            $k = 'like';
                            $value = '%'.$value.'%';
                        }
                        $qb->andWhere($qb->expr()->$k('a.'.$v, $qb->expr()->literal($value)));
                        continue;
                    } elseif (is_array($value)) {
                        array_walk($value, function (&$colVal) use ($qb) {
                            $colVal = $qb->expr()->literal($colVal);
                        });
                        $qb->andWhere($qb->expr()->in('a.'.$field, $value));
                    } else {
                        $qb->andWhere($qb->expr()->eq('a.'.$field, $qb->expr()->literal($value)));
                    }
                }
            }
        }
        $query = $qb->getQuery();
        $sql = $query->getSQL();
        $params = $query->getParameters();
        $sqlWithParams = $sql;
        // 替换命名参数
        foreach ($params as $key => $value) {
            $placeholder = ':'.$key;
            if (strpos($sqlWithParams, $placeholder) !== false) {
                if (is_string($value)) {
                    $sqlWithParams = str_replace($placeholder, "'".addslashes($value)."'", $sqlWithParams);
                } elseif (is_array($value)) {
                    $sqlWithParams = str_replace($placeholder, "'".implode("','", array_map('addslashes', $value))."'", $sqlWithParams);
                } elseif (is_null($value)) {
                    $sqlWithParams = str_replace($placeholder, 'NULL', $sqlWithParams);
                } else {
                    $sqlWithParams = str_replace($placeholder, $value, $sqlWithParams);
                }
            }
        }
        app('log')->info(':'.__CLASS__.':'.__FUNCTION__.':'.__LINE__.':sql:'.$sqlWithParams);

        return $criteria;
    }

    public function get($filter, $orderBy = null)
    {
        if (!empty($orderBy)) {
            $data = $this->findOneBy($filter, $orderBy);
        } else {
            $data = $this->findOneBy($filter);
        }
        $result = [];
        if ($data) {
            $result = $this->getAftersalesData($data);
        }
        return $result;
    }

    private function setAftersalesData($aftersalesEntity, $data)
    {
        if (isset($data['aftersales_bn'])) {
            $aftersalesEntity->setAftersalesBn($data['aftersales_bn']);
        }

        if (isset($data['order_id'])) {
            $aftersalesEntity->setOrderId($data['order_id']);
        }

        if (isset($data['company_id'])) {
            $aftersalesEntity->setCompanyId($data['company_id']);
        }

        if (isset($data['user_id'])) {
            $aftersalesEntity->setUserId($data['user_id']);
        }

        if (isset($data['salesman_id'])) {
            $aftersalesEntity->setSalesmanId($data['salesman_id']);
        }

        if (isset($data['shop_id'])) {
            $aftersalesEntity->setShopId($data['shop_id']);
        }

        if (isset($data['distributor_id'])) {
            $aftersalesEntity->setDistributorId($data['distributor_id']);
        }

        if (isset($data['supplier_id'])) {
            $aftersalesEntity->setSupplierId($data['supplier_id']);
        }

        // if (isset($data['detail_id'])) {
        //     $aftersalesEntity->setDetailId($data['detail_id']);
        // }

        if (isset($data['aftersales_type'])) {
            $aftersalesEntity->setAftersalesType($data['aftersales_type']);
        }

        if (isset($data['aftersales_status'])) {
            $aftersalesEntity->setAftersalesStatus($data['aftersales_status']);
        }

        if (isset($data['progress'])) {
            $aftersalesEntity->setProgress($data['progress']);
        }

        if (isset($data['refund_fee'])) {
            $aftersalesEntity->setRefundFee($data['refund_fee']);
        }

        if (isset($data['refund_point'])) {
            $aftersalesEntity->setRefundPoint($data['refund_point']);
        }

        if (isset($data['reason'])) {
            $aftersalesEntity->setReason($data['reason']);
        }

        if (isset($data['description'])) {
            $aftersalesEntity->setDescription($data['description']);
        }

        if (isset($data['evidence_pic'])) {
            $aftersalesEntity->setEvidencePic($data['evidence_pic']);
        }

        if (isset($data['refuse_reason'])) {
            $aftersalesEntity->setRefuseReason($data['refuse_reason']);
        }

        if (isset($data['memo'])) {
            $aftersalesEntity->setMemo($data['memo']);
        }

        if (isset($data['sendback_data'])) {
            $aftersalesEntity->setSendbackData($data['sendback_data']);
        }

        if (isset($data['sendconfirm_data'])) {
            $aftersalesEntity->setSendconfirmData($data['sendconfirm_data']);
        }

        // if (isset($data['share_points'])) {
        //     $aftersalesEntity->setSharePoints($data['share_points']);
        // }

        if (isset($data['third_data'])) {
            $aftersalesEntity->setThirdData($data['third_data']);
        }

        if (isset($data['item_bn'])) {
            $aftersalesEntity->setItemBn($data['item_bn']);
        }

        // if (isset($data['item_name'])) {
        //     $aftersalesEntity->setItemName($data['item_name']);
        // }

        // if (isset($data['num'])) {
        //     $aftersalesEntity->setNum($data['num']);
        // }

        // if (isset($data['aftersales_count'])) {
        //     $aftersalesEntity->setAftersalesCount($data['aftersales_count']);
        // }

        // if (isset($data['create_time'])) {
        //     $aftersalesEntity->setCreateTime($data['create_time']);
        // }

        // if (isset($data['update_time'])) {
        //     $aftersalesEntity->setUpdateTime($data['update_time']);
        // }
        if (isset($data['aftersales_address'])) {
            $aftersalesEntity->setAftersalesAddress($data['aftersales_address']);
        }

        if (isset($data['distributor_remark'])) {
            $aftersalesEntity->setDistributorRemark($data['distributor_remark']);
        }
        if (isset($data['contact'])) {
            $aftersalesEntity->setContact($data['contact']);
        }
        if (isset($data['mobile'])) {
            $aftersalesEntity->setMobile($data['mobile']);
        }
        if (isset($data['merchant_id'])) {
            $aftersalesEntity->setMerchantId($data['merchant_id']);
        }
        if (isset($data['is_partial_cancel'])) {
            $aftersalesEntity->setIsPartialCanceld($data['is_partial_cancel']);
        }
        if (isset($data['return_type'])) {
            $aftersalesEntity->setReturnType($data['return_type']);
        }
        if (isset($data['return_distributor_id'])) {
            $aftersalesEntity->setReturnDistributorId($data['return_distributor_id']);
        }
        if (isset($data['self_delivery_operator_id'])) {
            $aftersalesEntity->setSelfDeliveryOperatorId($data['self_delivery_operator_id']);
        }
        if (isset($data['freight'])) {
            $aftersalesEntity->setFreight($data['freight']);
        }
        if (isset($data['freight_type'])) {
            $aftersalesEntity->setFreightType($data['freight_type']);
        }
        return $aftersalesEntity;
    }
    public function getAftersalesData($aftersalesEntity)
    {
        return [
            'aftersales_bn' => $aftersalesEntity->getAftersalesBn(),
            'order_id' => $aftersalesEntity->getOrderId(),
            'company_id' => $aftersalesEntity->getCompanyId(),
            'user_id' => $aftersalesEntity->getUserId(),
            'salesman_id' => $aftersalesEntity->getSalesmanId(),
            'shop_id' => $aftersalesEntity->getShopId(),
            'distributor_id' => $aftersalesEntity->getDistributorId(),
            'supplier_id' => $aftersalesEntity->getSupplierId(),
            'aftersales_type' => $aftersalesEntity->getAftersalesType(),
            'aftersales_status' => $aftersalesEntity->getAftersalesStatus(),
            'progress' => $aftersalesEntity->getProgress(),
            'refund_fee' => $aftersalesEntity->getRefundFee(),
            'refund_point' => $aftersalesEntity->getRefundPoint(),
            'reason' => $aftersalesEntity->getReason(),
            'description' => $aftersalesEntity->getDescription(),
            'evidence_pic' => $aftersalesEntity->getEvidencePic(),
            'refuse_reason' => $aftersalesEntity->getRefuseReason(),
            'memo' => $aftersalesEntity->getMemo(),
            'sendback_data' => $aftersalesEntity->getSendbackData(),
            'sendconfirm_data' => $aftersalesEntity->getSendconfirmData(),
            'create_time' => $aftersalesEntity->getCreateTime(),
            'update_time' => $aftersalesEntity->getUpdateTime(),
            // 'aftersales_count' => $aftersalesEntity->getAftersalesCount(),
            // 'detail_id' => $aftersalesEntity->getDetailId(),
            'item_bn' => $aftersalesEntity->getItemBn(),
            // 'share_points' => $aftersalesEntity->getSharePoints(),
            'third_data' => $aftersalesEntity->getThirdData(),
            // 'item_name' => $aftersalesEntity->getItemName(),
            // 'num' => $aftersalesEntity->getNum(),
            'aftersales_address' => $aftersalesEntity->getAftersalesAddress(),
            'distributor_remark' => $aftersalesEntity->getDistributorRemark(),
            'contact' => $aftersalesEntity->getContact(),
            'mobile' => $aftersalesEntity->getMobile(),
            'merchant_id' => $aftersalesEntity->getMerchantId(),
            'is_partial_cancel' => $aftersalesEntity->getIsPartialCancel(),
            'return_type' => $aftersalesEntity->getReturnType(),
            'return_distributor_id' => $aftersalesEntity->getReturnDistributorId(),
            'self_delivery_operator_id' => $aftersalesEntity->getSelfDeliveryOperatorId(),
            'freight' => $aftersalesEntity->getFreight(),
            'freight_type' => $aftersalesEntity->getFreightType(),
        ];
    }

    /**
     * 获取总的销售数量
     * @param array $aftersalesFilter 订单售后的过滤条件
     * @param array $aftersalesDetailFilter 订单售后详情的过滤条件
     * @param array $orderFilter 订单的过滤条件
     * @return array
     */
    public function getTotalSalesCountByDistributorIds(array $aftersalesFilter = [], array $aftersalesDetailFilter = [], array $orderFilter = []): array
    {
        $aftersalesTable = $this->table;
        $aftersalesDetailTable = "aftersales_detail";
        $normalOrdersTable = "orders_normal_orders";

        // 为订单过滤条件添加别名
        foreach ($orderFilter as $key => $value) {
            $orderFilter[sprintf("%s.%s", $normalOrdersTable, $key)] = $value;
            unset($orderFilter[$key]);
        }

        // 为订单售后的过滤条件添加别名
        foreach ($aftersalesFilter as $key => $value) {
            $aftersalesFilter[sprintf("%s.%s", $aftersalesTable, $key)] = $value;
            unset($aftersalesFilter[$key]);
        }

        // 为订单售后详情的过滤条件添加别名
        foreach ($aftersalesDetailFilter as $key => $value) {
            $aftersalesDetailFilter[sprintf("%s.%s", $aftersalesDetailTable, $key)] = $value;
            unset($aftersalesDetailFilter[$key]);
        }

        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()
            ->select(sprintf("%s.distributor_id, SUM(%s.num) as sales_count", $aftersalesTable, $aftersalesDetailTable))
            ->from($aftersalesTable)
            ->leftJoin(
                $aftersalesTable,
                $aftersalesDetailTable,
                $aftersalesDetailTable,
                sprintf("%s.aftersales_bn = %s.aftersales_bn", $aftersalesTable, $aftersalesDetailTable)
            )
            ->leftJoin(
                $aftersalesTable,
                $normalOrdersTable,
                $normalOrdersTable,
                sprintf("%s.order_id = %s.order_id", $aftersalesTable, $normalOrdersTable)
            )
            ->groupBy(sprintf("%s.distributor_id", $aftersalesTable));

        $qb = $this->_filter($aftersalesFilter, $qb);
        $qb = $this->_filter($aftersalesDetailFilter, $qb);
        $qb = $this->_filter($orderFilter, $qb);

        return $qb->execute()->fetchAll();
    }

    public function getDeliveryStaffDataCountByOperatorId(array $aFilter = [],$rFilter = [])
    {

        $aftersalesTable = $this->table;
        $aftersalesRefundTable = "aftersales_refund";

        // 为售后过滤条件添加别名
        foreach ($aFilter as $key => $value) {
            $aFilter[sprintf("%s.%s", $aftersalesTable, $key)] = $value;
            unset($aFilter[$key]);
        }

        // 为售后退款的过滤条件添加别名
        foreach ($rFilter as $key => $value) {
            $rFilter[sprintf("%s.%s", $aftersalesRefundTable, $key)] = $value;
            unset($rFilter[$key]);
        }
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()
            ->select("aftersales.self_delivery_operator_id,  COUNT(aftersales.order_id) as aftersales_count, SUM(aftersales.refund_fee)as refund_fee_count")
            ->from($this->table)
            ->leftJoin(
                $aftersalesTable,
                $aftersalesRefundTable,
                $aftersalesRefundTable,
                sprintf("%s.aftersales_bn = %s.aftersales_bn", $aftersalesTable, $aftersalesRefundTable)
            )
            ->groupBy('self_delivery_operator_id');

        $qb = $this->_filter($aFilter, $qb);
        $qb = $this->_filter($rFilter, $qb);
        return $qb->execute()->fetchAll();
    }


    /**
     * 根据条件获取列表数据
     *
     * @param $filter array 更新的条件
     */
    public function getLists($filter, $cols='*', $page = 1, $pageSize = -1, $orderBy = array())
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()->select($cols)->from($this->table, 'a');
        $qb = $this->_filter($filter, $qb);
        if ($orderBy) {
            foreach($orderBy as $filed => $val) {
                $qb->addOrderBy($filed, $val);
            }
        }
        if ($pageSize > 0) {
            $qb->setFirstResult(($page-1)*$pageSize)
                ->setMaxResults($pageSize);
        }
        $lists = $qb->execute()->fetchAll();
        return $lists;
    }

    /**
     * 根据条件获取列表数据,包含数据总数条数
     *
     * @param $filter array 更新的条件
     */
    public function lists($filter, $cols='*', $page = 1, $pageSize = -1, $orderBy = array())
    {
        $result['total_count'] = $this->getCount($filter);
        if ($result['total_count'] > 0) {
            $conn = app('registry')->getConnection('default');
            $qb = $conn->createQueryBuilder()->select($cols)->from($this->table, 'a');
            $qb = $this->_filter($filter, $qb);
            if ($orderBy) {
                foreach($orderBy as $filed => $val) {
                    $qb->addOrderBy($filed, $val);
                }
            }
            if ($pageSize > 0) {
                $qb->setFirstResult(($page-1)*$pageSize)
                    ->setMaxResults($pageSize);
            }
            $lists = $qb->execute()->fetchAll();
        }
        $result['list'] = $lists ?? [];
        return $result;
    }

    /**
     * 统计数量
     */
    public function getCount($filter)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('count(id)')
            ->from($this->table);
        if ($filter) {
            $this->_filter($filter, $qb);
        }
        $count = $qb->execute()->fetchColumn();
        return intval($count);
    }

    public function listsJoinOrder($filter, $cols='*', $page = 1, $pageSize = -1, $orderBy = array())
    {
        foreach ($filter as $k => $f) {
            if ($k == 'need_order') {
                unset($filter[$k]);
                continue;
            }
            if (!str_contains($k, '.')) {
                $filter['a.' . $k] = $f;
                unset($filter[$k]);
            }
        }

        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('count(1)')
            ->from($this->table, 'a');
        $qb = $qb->leftJoin('a', 'orders_normal_orders', 'o', 'o.order_id = a.order_id');
        if ($filter) {
            $this->_filter($filter, $qb);
        }
        $result['total_count'] = $qb->execute()->fetchColumn();


        if ($result['total_count'] > 0) {
            $conn = app('registry')->getConnection('default');
            $qb = $conn->createQueryBuilder()->select($cols)->from($this->table, 'a');

            $qb = $qb->leftJoin('a', 'orders_normal_orders', 'o', 'o.order_id = a.order_id');

            $qb = $this->_filter($filter, $qb);
            if ($orderBy) {
                foreach($orderBy as $filed => $val) {
                    $qb->addOrderBy($filed, $val);
                }
            }
            if ($pageSize > 0) {
                $qb->setFirstResult(($page-1)*$pageSize)
                    ->setMaxResults($pageSize);
            }
            $lists = $qb->execute()->fetchAll();
        }
        $result['list'] = $lists ?? [];
        return $result;
    }
}
