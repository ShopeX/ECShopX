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

namespace MerchantBundle\Repositories;

use CompanysBundle\Entities\Operators;
use Doctrine\ORM\EntityRepository;
use GoodsBundle\Services\MultiLang\MagicLangTrait;
use GoodsBundle\Services\MultiLang\MultiLangOutsideItemService;
use MerchantBundle\Entities\Merchant;
use DistributionBundle\Entities\Distributor;

use Dingo\Api\Exception\ResourceException;

class MerchantRepository extends EntityRepository
{
    use MagicLangTrait;
    public $table = "merchant";

    private $multiLangField = [
        'merchant_name','province','city','area','address','legal_name',
    ];

    public $cols = ['id','company_id','settlement_apply_id','merchant_name','merchant_type_id','settled_type','social_credit_code_id','province','city','area','regions_id','address','legal_name','legal_cert_id','legal_mobile','email','bank_acct_type','card_id_mask','bank_name','bank_mobile','license_url','legal_certid_front_url','legal_cert_id_back_url','bank_card_front_url','contract_url','settled_succ_sendsms','audit_goods','source','disabled','created','updated'];

    public $prk = 'id';

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
        $entity = new Merchant();
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
        // FIXME: check performance
        $entity = $this->findOneBy($filter);
        if (!$entity) {
            throw new ResourceException(trans("MerchantBundle.no_update_data_found"));
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
        $conn = app("registry")->getConnection("default");
        $qb = $conn->createQueryBuilder()->update($this->table);
        foreach ($data as $key => $val) {
            $qb = $qb->set($key, $qb->expr()->literal($val));
        }

        $qb = $this->_filter($filter, $qb);
        if(isset($filter[$this->prk])){
            $this->getLangService()->updateLangData($data,$this->table,$filter[$this->prk]);
        }
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

        $lists = $this->getLangService()->getListAddLang($lists,$this->multiLangField,$this->table,$this->getLang(),$this->prk);


        return $lists;
    }

    /**
     * 根据条件获取列表数据,包含数据总数条数
     *
     * @param $filter 更新的条件
     */
    public function lists($filter, $cols = '*', $page = 1, $pageSize = -1, $orderBy = array())
    {
        $filter = $this->fixedencryptCol($filter);
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
        $fixeddecryptCol = ['legal_name', 'legal_mobile', 'bank_mobile', 'legal_cert_id'];
        foreach ($result['list'] as $key => $list) {
            $list = $this->formatData($list);
            foreach ($list as $k => $v) {
                if (in_array($k, $fixeddecryptCol)) {
                    $list[$k] = fixeddecrypt((string) $v);
                }
            }
            $result['list'][$key] = $list;
        }

        $result['list'] = $this->getLangService()->getListAddLang($result['list'],$this->multiLangField,$this->table,$this->getLang(),$this->prk);
        return $result;
    }

    private function formatData($data)
    {
        if (isset($data['audit_goods']) && is_numeric($data['audit_goods'])) {
            $data['audit_goods'] = $data['audit_goods'] == '1';
        }
        if (isset($data['disabled']) && is_numeric($data['disabled'])) {
            $data['disabled'] = $data['disabled'] == '1';
        }
        return $data;
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
     * 查询商户账号
     */
    public function getOperatorLists($filter, $orderBy = ["created" => "DESC"], $pageSize = 100, $page = 1, $isTotalCount = true, $column = "*", $noHaving = false)
    {
        $conn = app('registry')->getConnection('default');
        $select = 'operator_id,operators.mobile,password,merchant_name,settled_type,is_merchant_main';
        $qb = $conn->createQueryBuilder();
        $operatorsTable = app('registry')->getManager('default')->getRepository(Operators::class)->table;
        $qb->join(
            $this->table,
            $operatorsTable,
            $operatorsTable,
            sprintf("%s.id=%s.merchant_id", $this->table, $operatorsTable)
        );
        $qb->select($select)->from($this->table);
        $filter["$this->table.company_id"] = $filter['company_id'];
        $orderBy["$this->table.created"] = $orderBy['created'];
        unset($orderBy['created']);
        unset($filter['company_id']);
        if (!empty($filter['mobile'])) {
            $filter["$operatorsTable.mobile"] = fixedencrypt($filter['mobile']);
            unset($filter['mobile']);
        }
        $qb = $this->_filter($filter, $qb);
        $res["total_count"] = 0;
        if ($isTotalCount) {
            $totalCountQb = clone $qb;
            $res["total_count"] = count($totalCountQb->execute()->fetchAll());
        }
        // 分页设置
        if ($pageSize > 0) {
            $qb->setFirstResult($pageSize * ($page - 1))->setMaxResults($pageSize);
        }
        // 设置排序方式
        if (is_array($orderBy)) {
            foreach ($orderBy as $key => $value) {
                $qb->addOrderBy($key, $value);
            }
        }
        $lists = $qb->execute()->fetchAll();
        foreach ($lists as &$v) {
            // 解密
            isset($v['mobile']) and $v['mobile'] = fixeddecrypt($v['mobile']);
        }
        $res["list"] = $lists;

        $res['list'] = $this->getLangService()->getListAddLang($res['list'],$this->multiLangField,$this->table,$this->getLang(),$this->prk);
        return $res;
    }

    /**
     * 获取已禁用商家关联的店铺id
     * @param  string $companyId 企业ID
     * @return array            店铺ID
     */
    public function getDisabledDistributorIds($companyId)
    {
        $filter = [
            "$this->table.company_id" => $companyId,
            "$this->table.disabled" => 1,
        ];
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $distributorTable = app('registry')->getManager('default')->getRepository(Distributor::class)->table;
        $select = "$distributorTable.distributor_id";
        $qb->innerJoin(
            $this->table,
            $distributorTable,
            $distributorTable,
            sprintf("%s.id=%s.merchant_id", $this->table, $distributorTable)
        );
        $qb = $this->_filter($filter, $qb);
        $qb->select($select)->from($this->table);
        $lists = $qb->execute()->fetchAll();
        return array_column($lists, 'distributor_id');
    }

    /**
     * 对filter中的部分字段，加密处理
     * @param  [type] $filter [description]
     * @return [type]         [description]
     */
    private function fixedencryptCol($filter)
    {
        $fixedencryptCol = ['legal_name', 'legal_mobile'];
        foreach ($fixedencryptCol as $col) {
            if (isset($filter[$col])) {
                $filter[$col] = fixedencrypt((string) $filter[$col]);
            }
        }
        return $filter;
    }
}
