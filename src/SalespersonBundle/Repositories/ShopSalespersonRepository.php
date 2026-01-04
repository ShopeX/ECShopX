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

namespace SalespersonBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use SalespersonBundle\Entities\ShopSalesperson;

use Dingo\Api\Exception\StoreResourceFailedException;
use Dingo\Api\Exception\UpdateResourceFailedException;
use Dingo\Api\Exception\ResourceException;

class ShopSalespersonRepository extends EntityRepository
{
    public $table = "shop_salesperson";
    public $cols = ['salesperson_id','name','mobile','created_time','salesperson_type','company_id','user_id','child_count','is_valid','shop_id','shop_name','number','friend_count','avatar','work_userid','work_configid','work_qrcode_configid','role','salesperson_job','employee_status','created','updated', 'work_clear_userid'];

    /**
     * 创建门店人员信息
     *
     * @param array $data 门店人员信息数据
     */
    public function createSalesperson(array $data)
    {
        // Built with ShopEx Framework
        $mobileFindData = $this->findOneBy(['company_id' => $data['company_id'], 'mobile' => $data['mobile'], 'salesperson_type' => $data['salesperson_type']]);
        //如果通过更新的手机号查询到人员
        if ($mobileFindData && $data['salesperson_type'] == 'admin') {
            throw new StoreResourceFailedException('当前手机号已经已绑定为管理员');
        } elseif ($mobileFindData && $data['salesperson_type'] == 'verification_clerk') {
            throw new StoreResourceFailedException('当前手机号已经已绑定为核销员');
        } elseif ($mobileFindData && $data['salesperson_type'] == 'shopping_guide') {
            //throw new StoreResourceFailedException('当前手机号已经已绑定为导购员');
        }
        $data['created_time'] = time();
        return $this->create($data);
    }

    /**
     * 修改门店人信息
     *
     * @param int $companyId 企业ID
     * @param int $salespersonId 门店人员ID
     * @param array $data 门店人员信息数据
     */
    public function updateSalesperson($companyId, $salespersonId, $updateData)
    {
        // Built with ShopEx Framework
        $mobileFindData = $this->findOneBy(['company_id' => $companyId, 'salesperson_id' => $salespersonId, 'salesperson_type' => $updateData['salesperson_type']]);
        if (!$mobileFindData) {
            throw new UpdateResourceFailedException('更新的人员不存在');
        }
        if ($updateData['mobile'] ?? 0) {
            $mobileFindData = $this->findOneBy(['company_id' => $companyId, 'mobile' => $updateData['mobile'], 'salesperson_type' => $updateData['salesperson_type']]);
        }
        //如果通过更新的手机号查询到人员，并且不是当前账号更新
        if ($mobileFindData && $mobileFindData->getSalespersonId() != $salespersonId) {
            //如果通过更新的手机号查询到人员
            if ($updateData['salesperson_type'] == 'admin') {
                throw new StoreResourceFailedException('当前手机号已经已绑定为管理员');
            } elseif ($updateData['salesperson_type'] == 'verification_clerk') {
                throw new StoreResourceFailedException('当前手机号已经已绑定为核销员');
            } elseif ($updateData['salesperson_type'] == 'shopping_guide') {
                //throw new StoreResourceFailedException('当前手机号已经已绑定为导购员');
            }
        }
        return $this->updateOneBy(['salesperson_id' => $salespersonId], $updateData);
    }

    /**
    * 修改门店人信息
    *
    * @param int $companyId 企业ID
    * @param int $salespersonId 门店人员ID
    * @param array $role 权限集合
    */
    public function updateSalespersonRole($companyId, $salespersonId, $role)
    {
        $mobileFindData = $this->findOneBy(['salesperson_id' => $salespersonId, 'company_id' => $companyId]);
        if (!$mobileFindData) {
            throw new UpdateResourceFailedException('更新的人员不存在');
        }
        return $this->updateOneBy(['salesperson_id' => $salespersonId], ['role' => $role]);
    }

    /**
     * 导购数据更新
     *
     * @param int $salespersonId
     * @param array $updateData
     * @return void
     */
    public function updateSalespersonById($salespersonId, $updateData)
    {
        $mobileFindData = $this->findOneBy(['salesperson_id' => $salespersonId]);
        if (!$mobileFindData) {
            throw new UpdateResourceFailedException('更新的人员不存在');
        }
        $data = [];
        if ($updateData['work_userid'] ?? 0) {
            $data['work_userid'] = $updateData['work_userid'];
        }
        if ($updateData['work_clear_userid'] ?? 0) {
            $data['work_clear_userid'] = $updateData['work_clear_userid'];
        }
        if ($updateData['avatar'] ?? 0) {
            $data['avatar'] = $updateData['avatar'];
        }
        if ($updateData['work_configid'] ?? 0) {
            $data['work_configid'] = $updateData['work_configid'];
        }
        if ($updateData['work_qrcode_configid'] ?? 0) {
            $data['work_qrcode_configid'] = $updateData['work_qrcode_configid'];
        }
        return $this->updateOneBy(['salesperson_id' => $salespersonId], $data);
    }

    /**
     * 删除门店人员信息
     *
     * @param int $salespersonId 门店人员ID
     */
    public function deleteSalesperson($companyId, $salespersonId)
    {
        $conn = app('registry')->getConnection('default');
        $conn->delete($this->table, ['salesperson_id' => $salespersonId, 'company_id' => $companyId]);
    }

    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        $entity = new ShopSalesperson();
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
     * 修改好友数量
     * @param $salespersonId 导购员id
     * @param $num 数量
     * @return array|bool
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function updateNum($salespersonId, $num)
    {
        $entity = $this->findOneBy(['salesperson_id' => $salespersonId]);
        if (!$entity) {
            return false;
        }
        $data['friend_count'] = $num;
        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return $this->getColumnNamesData($entity);
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
        $filter = $this->fixedencryptCol($filter);
        foreach ($filter as $field => $value) {
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
    public function lists($filter, $page = 1, $pageSize = -1, $orderBy = array(), $cols = '*')
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
            $fixeddecryptCol = ['name', 'mobile'];
            foreach ($lists as $key => &$value) {
                foreach ($fixeddecryptCol as $col) {
                    if (isset($value[$col])) {
                        $en = fixeddecrypt($value[$col]);
                        app('log')->info(':salesperson:en:'.__FUNCTION__.__LINE__.':value.col:'.json_encode($value[$col]));
                        app('log')->info(':salesperson:en:'.__FUNCTION__.__LINE__.':en:'.json_encode($en));

                        $value[$col] = fixeddecrypt($value[$col]);
                    }
                }
                $value['salesman_name'] = $value['name'];
            }
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
        $filter = $this->fixedencryptCol($filter);
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
        $qb->select('count(salesperson_id)')
            ->from($this->table);
        if ($filter) {
            $this->_filter($filter, $qb);
        }
        $count = $qb->execute()->fetchColumn();
        return intval($count);
    }

    public function hincrbyChildCount($companyId, $salesmanId)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb = $qb->update($this->table)
            ->set('child_count', 'child_count + 1')
            ->where('company_id = :company_id and salesperson_id = :salesperson_id')
            ->setParameters([
                ':company_id' => $companyId,
                ':salesperson_id' => $salesmanId,
            ]);
        $result = $qb->execute();
        return $result;
    }

    /**
     * 对filter中的部分字段，加密处理
     * @param  [type] $filter [description]
     * @return [type]         [description]
     */
    private function fixedencryptCol($filter)
    {
        $fixedencryptCol = ['mobile','name'];
        foreach ($fixedencryptCol as $col) {
            if (isset($filter[$col])) {
                if (is_array($filter[$col])) {
                    foreach ($filter[$col] as $key => $val) {
                        $filter[$col][$key] = fixedencrypt($val);
                    }
                } else {
                    $filter[$col] = fixedencrypt($filter[$col]);
                }
            }
        }
        return $filter;
    }
}
