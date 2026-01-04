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

namespace HfPayBundle\Repositories;

use Dingo\Api\Exception\ResourceException;
use Doctrine\ORM\EntityRepository;
use HfPayBundle\Entities\HfpayEnterapply;

class HfpayEnterapplyRepository extends EntityRepository
{
    public $table = "hfpay_enterapply";
    public $cols = ['hfpay_enterapply_id','company_id','distributor_id','user_id','user_cust_id','acct_id','apply_type','corp_license_type','corp_name','business_code','institution_code','tax_code','social_credit_code','license_start_date','license_end_date','controlling_shareholder','legal_name','legal_id_card_type','legal_id_card','legal_cert_start_date','legal_cert_end_date','legal_mobile','contact_name','contact_mobile','contact_email','bank_acct_name','bank_id','bank_name','bank_acct_num','bank_prov','bank_prov_name','bank_area','bank_area_name','solo_name','solo_business_address','solo_reg_address','solo_fixed_telephone','business_scope','occupation','user_name','id_card_type','id_card','user_mobile','hf_order_id','hf_order_date','hf_apply_id','status','business_code_img','business_code_img_local','institution_code_img','institution_code_img_local','tax_code_img','tax_code_img_local','social_credit_code_img','social_credit_code_img_local','legal_card_imgz','legal_card_imgz_local','legal_card_imgf','legal_card_imgf_local','bank_acct_img','bank_acct_img_local','resp_code','resp_desc','created_at','updated_at','bank_branch','bank_acct_num_imgz','bank_acct_num_imgf','bank_acct_num_imgz_local','bank_acct_num_imgf_local','contact_cert_num','open_license_no'];
    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        $entity = new HfpayEnterapply();
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
        if (!$entity) {
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
        foreach ($data as $key => $val) {
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

    private function setColumnNamesData($entity, $params)
    {
        foreach ($this->cols as $col) {
            if (isset($params[$col])) {
                $fun = "set". str_replace(" ", "", ucwords(str_replace("_", " ", $col)));
                if (method_exists($entity, $fun)) {
                    $entity->$fun($params[$col]);
                }
            }
        }
        return $entity;
    }

    private function getColumnNamesData($entity, $cols = [], $ignore = [])
    {
        if (!$cols) {
            $cols = $this->cols;
        }

        $values = [];
        foreach ($cols as $col) {
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
     * 根据条件获取列表数据
     *
     * @param $filter 更新的条件
     */
    public function getLists($filter, $cols = '*', $page = 1, $pageSize = -1, $orderBy = array())
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()->select($cols)->from($this->table);
        $qb = $this->_filter($filter, $qb);
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
        return $lists;
    }

    /**
     * 根据条件获取列表数据,包含数据总数条数
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
        return $result;
    }

    /**
     * @param $filter
     * @param int $page
     * @param int $pageSize
     * @param array $orderBy
     * @return mixed
     *
     * 店铺进件列表联合查询
     */
    public function getJoinLists($filter, $page = 1, $pageSize = -1, $orderBy = array())
    {
        $cols = 'hfpay_enterapply_id, enterapply.distributor_id, status, distributor.name';
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('count(*)')
            ->from('hfpay_enterapply', 'enterapply')
            ->leftJoin('enterapply', 'distribution_distributor', 'distributor', 'enterapply.distributor_id = distributor.distributor_id');
        if (!empty($filter['company_id'])) {
            $qb = $qb->andWhere($qb->expr()->eq('enterapply.company_id', $qb->expr()->literal($filter['company_id'])));
        }
        if (!empty($filter['apply_type'])) {
            $value = $filter['apply_type'];
            array_walk($value, function (&$colVal) use ($qb) {
                $colVal = $qb->expr()->literal($colVal);
            });
            $qb = $qb->andWhere($qb->expr()->in('apply_type', $value));
        }
        if (!empty($filter['name|contains'])) {
            $value = '%' . $filter['name|contains'] . '%';
            $qb = $qb->andWhere($qb->expr()->like('name', $qb->expr()->literal($value)));
        }
        if (!empty($filter['distributor_id'])) {
            $value = $filter['distributor_id'];
            $qb = $qb->andWhere($qb->expr()->eq('distributor.distributor_id', $qb->expr()->literal($value)));
        }
        if (!empty($filter['province'])) {
            $value = $filter['province'];
            $qb = $qb->andWhere($qb->expr()->eq('province', $qb->expr()->literal($value)));
        }
        if (!empty($filter['city'])) {
            $value = $filter['city'];
            $qb = $qb->andWhere($qb->expr()->eq('city', $qb->expr()->literal($value)));
        }
        if (!empty($filter['area'])) {
            $value = $filter['area'];
            $qb = $qb->andWhere($qb->expr()->eq('area', $qb->expr()->literal($value)));
        }

        $result['total_count'] = intval($qb->execute()->fetchColumn());
        $result['list'] = [];
        if ($result['total_count'] > 0) {
            if ($orderBy) {
                foreach ($orderBy as $filed => $val) {
                    $qb->addOrderBy($filed, $val);
                }
            }
            if ($pageSize > 0) {
                $qb->setFirstResult(($page - 1) * $pageSize)
                    ->setMaxResults($pageSize);
            }
            $lists = $qb->select($cols)->execute()->fetchAll();
            $result['list'] = $lists;
        }

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
        $qb->select('count(hfpay_enterapply_id)')
             ->from($this->table);
        if ($filter) {
            $this->_filter($filter, $qb);
        }
        $count = $qb->execute()->fetchColumn();
        return intval($count);
    }
}
