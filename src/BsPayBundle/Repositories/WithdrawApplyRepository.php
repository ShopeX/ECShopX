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

namespace BsPayBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use BsPayBundle\Entities\WithdrawApply;
use BsPayBundle\Enums\WithdrawStatus;
use Dingo\Api\Exception\ResourceException;

class WithdrawApplyRepository extends EntityRepository
{

    public $table = "bspay_withdraw_apply";
    public $cols = ['id','company_id','merchant_id','distributor_id','operator_type','operator_id','operator','huifu_id','amount','withdraw_type','invoice_file','status','audit_time','auditor','auditor_operator_id','audit_remark','hf_seq_id','req_seq_id','request_time','failure_reason','created','updated'];
    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        $entity = new WithdrawApply();
        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return $this->getColumnNamesData($entity);
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
        if( !$entity ) {
            throw new ResourceException("未查询到更新数据");
        }

        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

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
        $conn = app("registry")->getConnection("default");
        $qb = $conn->createQueryBuilder()->update($this->table);
        foreach($data as $key=>$val) {
            $qb = $qb->set($key, $qb->expr()->literal($val));
        }

        $qb = $this->_filter($filter, $qb);

        return $qb->execute();
    }

    /**
     * 根据主键删除指定数据
     *
     * @param $id
     */
    public function deleteById($id)
    {
        $entity = $this->find($id);
        if(!$entity) {
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
        if(!$entityList) {
            return true;
        }
        $em = $this->getEntityManager();
        foreach($entityList as $entityProp) {
            $em->remove($entityProp);
            $em->flush();
        }
        return true;
    }

    private function setColumnNamesData($entity, $params)
    {
        foreach($this->cols as $col) {
            if (isset($params[$col])) {
                $fun = "set". str_replace(" ", "", ucwords(str_replace("_", " ", $col)));
                if (method_exists($entity, $fun)) {
                     $entity->$fun($params[$col]);
                }
            }
        }
        return $entity;
    }

    private function getColumnNamesData($entity, $cols=[], $ignore=[])
    {
        if (!$cols) $cols = $this->cols;

        $values = [];
        foreach($cols as $col) {
            if ($ignore && in_array($col, $ignore)) {
                continue;
            }
            $fun = "get". str_replace(" ", "", ucwords(str_replace("_", " ", $col)));
            if (method_exists($entity, $fun)) {
                $values[$col] = $entity->$fun();
            }
        }
        return $values;
    }

    /**
     * 筛选条件格式化
     *
     * @param $filter
     * @param $qb
     */
    private function _filter($filter, $qb)
    {
        foreach($filter as $field => $value) {
            $list = explode('|', $field);
            if (count($list) > 1) {
                list($v,$k) = $list;
                if ($k == 'contains') {
                    $k = 'like';
                }
                if ($k == 'like') {
                    $value = '%'.$value.'%';
                }
                if (is_array($value)) {
                    array_walk($value, function(&$colVal) use ($qb) {
                        $colVal = $qb->expr()->literal($colVal);
                    });
                    $qb = $qb->andWhere($qb->expr()->$k($field, $value));
                } else {
                    $qb =$qb->andWhere($qb->expr()->$k($v, $qb->expr()->literal($value)));
                }
                continue;
            } elseif (is_array($value)) {
                array_walk($value, function(&$colVal) use ($qb) {
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
    public function getLists($filter, $cols='*', $page = 1, $pageSize = -1, $orderBy = array())
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()->select($cols)->from($this->table);
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
     * @param $filter 更新的条件
     */
    public function lists($filter, $cols='*', $page = 1, $pageSize = -1, $orderBy = array())
    {
        $result['total_count'] = $this->count($filter);
        if ($result['total_count'] > 0) {
            $conn = app('registry')->getConnection('default');
            $qb = $conn->createQueryBuilder()->select($cols)->from($this->table);
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
     * 根据主键获取数据
     *
     * @param $id
     */
    public function getInfoById($id)
    {
        $entity = $this->find($id);
        if( !$entity ) {
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
        if( !$entity ) {
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
         $qb->select('count(id)')
             ->from($this->table);
         if ($filter) {
             $this->_filter($filter, $qb);
         }
         $count = $qb->execute()->fetchColumn();
         return intval($count);
      }

    /**
     * 数量求和
     */
    public function sum($filter, $field)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('sum(' . $field . ')')
            ->from($this->table);
        if ($filter) {
            $this->_filter($filter, $qb);
        }
        $sum = $qb->execute()->fetchColumn();
        return floatval($sum ?: 0);
    }

    /**
      * 获取审核中提现余额
      *
      * @param int $companyId 企业ID
      * @param int $merchantId 商户ID
      * @param int $distributorId 店铺ID
      * @param string $operatorType 操作类型
      * @return int 审核中提现余额（分）
      */
         public function getPendingBalance($companyId, $merchantId = null, $distributorId = null, $operatorType = '')
    {
        $filter = ['company_id' => $companyId, 'operator_type' => $operatorType];

        if ($operatorType === 'merchant' && $merchantId > 0) {
            $filter['merchant_id'] = $merchantId;
        } elseif ($operatorType === 'distributor' && $distributorId > 0) {
            $filter['distributor_id'] = $distributorId;
        }

        $qb = $this->createQueryBuilder('w')
            ->select('SUM(w.amount)')
            ->where('w.company_id = :company_id')
            ->andWhere('w.status IN (:status_list)')
            ->andWhere('w.operator_type = :operator_type')
            ->setParameter('company_id', $companyId)
            ->setParameter('status_list', WithdrawStatus::$pendingStatuses) // 审核中+审核通过+处理中
            ->setParameter('operator_type', $operatorType);
        
        // 根据用户类型构建过滤条件
        if ($operatorType === 'merchant' && $merchantId > 0) {
            $qb->andWhere('w.merchant_id = :merchant_id')
               ->setParameter('merchant_id', $merchantId);
        } elseif ($operatorType === 'distributor' && $distributorId > 0) {
            $qb->andWhere('w.distributor_id = :distributor_id')
               ->setParameter('distributor_id', $distributorId);
        }
        
        $amount = $qb->getQuery()->getSingleScalarResult() ?: 0;
        
        // 金额已经是分为单位，直接返回
        return intval($amount);
    }

    /**
     * 获取申请中的提现金额总和
     *
     * @param array $filter 查询条件
     * @return int 申请中的提现金额（分）
     */
    public function getPendingAmount($filter)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('sum(amount)')
            ->from($this->table);

        if ($filter) {
            $this->_filter($filter, $qb);
        }

        $sum = $qb->execute()->fetchColumn();
        return intval($sum);
    }

    /**
     * 根据状态获取提现金额总和
     *
     * @param string $companyId 企业ID
     * @param string $operatorType 操作者类型
     * @param string $operatorId 操作者ID
     * @param int|array $status 状态或状态数组
     * @return int 提现金额总和（分）
     */
    public function getAmountByStatus($companyId, $operatorType, $operatorId, $status)
    {
        try {
            $qb = $this->createQueryBuilder('w')
                ->select('SUM(w.amount)')
                ->where('w.company_id = :company_id')
                ->andWhere('w.operator_type = :operator_type')
                ->andWhere('w.operator_id = :operator_id')
                ->setParameter('company_id', $companyId)
                ->setParameter('operator_type', $operatorType)
                ->setParameter('operator_id', $operatorId);

            if (is_array($status)) {
                $qb->andWhere('w.status IN (:status)')
                   ->setParameter('status', $status);
            } else {
                $qb->andWhere('w.status = :status')
                   ->setParameter('status', $status);
            }

            return intval($qb->getQuery()->getSingleScalarResult() ?: 0);

        } catch (\Exception $e) {
            app('log')->error("查询提现金额失败：" . $e->getMessage());
            throw new ResourceException("查询提现金额失败：" . $e->getMessage());
        }
    }
}
