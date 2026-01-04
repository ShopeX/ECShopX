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

namespace EmployeePurchaseBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use EmployeePurchaseBundle\Entities\Activities;
use Dingo\Api\Exception\ResourceException;
use MembersBundle\Services\MemberService;

class ActivitiesRepository extends EntityRepository
{
    public $table = 'employee_purchase_activities';
    public $cols = ['id', 'company_id', 'distributor_id', 'operator_id', 'name', 'title', 'pages_template_id', 'pic', 'share_pic', 'enterprise_id', 'display_time', 'employee_begin_time', 'employee_end_time', 'employee_limitfee', 'if_relative_join', 'invite_limit', 'relative_begin_time', 'relative_end_time', 'if_share_limitfee', 'relative_limitfee', 'minimum_amount', 'close_modify_hours_after_activity', 'status', 'if_share_store', 'price_display_config', 'is_discount_description_enabled', 'discount_description', 'created', 'updated'];

    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        $entity = new Activities();
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
                $entity->$fun($params[$col]);
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
            $values[$col] = $entity->$fun();
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
            $now = time();
            if ($field == 'status' && $value == 'warm_up') {
                $qb = $qb->andWhere(
                    $qb->expr()->lt('display_time', $now),
                    $qb->expr()->gt('employee_begin_time', $now),
                    $qb->expr()->orX(
                        $qb->expr()->eq('relative_begin_time', 0),
                        $qb->expr()->gt('relative_begin_time', $now)
                    ),
                    $qb->expr()->eq('status', $qb->expr()->literal('active'))
                );
            } elseif ($field == 'status' && $value == 'ongoing') {
                $qb = $qb->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->lt('employee_begin_time', $now),
                        $qb->expr()->andX(
                            $qb->expr()->gt('relative_begin_time', 0),
                            $qb->expr()->lt('relative_begin_time', $now),
                        )
                    ),
                    $qb->expr()->orX(
                        $qb->expr()->gt('employee_end_time', $now),
                        $qb->expr()->gt('relative_end_time', $now),
                    ),
                    $qb->expr()->eq('status', $qb->expr()->literal('active'))
                );
            } elseif ($field == 'status' && $value == 'pending') {
                $qb = $qb->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->lt('employee_begin_time', $now),
                        $qb->expr()->lt('relative_begin_time', $now),
                    ),
                    $qb->expr()->orX(
                        $qb->expr()->gt('employee_end_time', $now),
                        $qb->expr()->gt('relative_end_time', $now),
                    ),
                    $qb->expr()->eq('status', $qb->expr()->literal('pending'))
                );
            } elseif ($field == 'status' && $value == 'over') {
                $qb = $qb->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->andX(
                            $qb->expr()->lt('employee_end_time', $now),
                            $qb->expr()->lt('relative_end_time', $now),
                        ),
                        $qb->expr()->eq('status', $qb->expr()->literal('over'))
                    )
                );
            } elseif ($field == 'buy_time') {
                if (isset($value['begin']) && isset($value['end'])) {
                    $qb = $qb->andWhere(
                        $qb->expr()->orX(
                            $qb->expr()->andX(
                                $qb->expr()->lt('employee_begin_time', $value['begin']),
                                $qb->expr()->lt('employee_end_time', $value['begin']),
                            ),
                            $qb->expr()->andX(
                                $qb->expr()->lt('employee_begin_time', $value['end']),
                                $qb->expr()->lt('employee_end_time', $value['end']),
                            ),
                            $qb->expr()->andX(
                                $qb->expr()->lt('relative_begin_time', $value['begin']),
                                $qb->expr()->lt('relative_end_time', $value['begin']),
                            ),
                            $qb->expr()->andX(
                                $qb->expr()->lt('relative_begin_time', $value['end']),
                                $qb->expr()->lt('relative_end_time', $value['end']),
                            )
                        )
                    );
                } elseif (isset($value['begin']) && !isset($value['end'])) {
                    $qb = $qb->andWhere(
                        $qb->expr()->orX(
                            $qb->expr()->gt('employee_begin_time', $value['begin']),
                            $qb->expr()->gt('relative_begin_time', $value['begin']),
                            $qb->expr()->andX(
                                $qb->expr()->lt('employee_begin_time', $value['begin']),
                                $qb->expr()->gt('employee_end_time', $value['begin']),
                            ),
                            $qb->expr()->andX(
                                $qb->expr()->lt('relative_begin_time', $value['begin']),
                                $qb->expr()->gt('relative_end_time', $value['begin']),
                            )
                        )
                    );
                } elseif (!isset($value['begin']) && isset($value['end'])) {
                    $qb = $qb->andWhere(
                        $qb->expr()->orX(
                            $qb->expr()->lt('employee_end_time', $value['end']),
                            $qb->expr()->lt('relative_end_time', $value['end']),
                            $qb->expr()->andX(
                                $qb->expr()->lt('employee_begin_time', $value['end']),
                                $qb->expr()->gt('employee_end_time', $value['end']),
                            ),
                            $qb->expr()->andX(
                                $qb->expr()->lt('relative_begin_time', $value['end']),
                                $qb->expr()->gt('relative_end_time', $value['end']),
                            )
                        )
                    );
                }
            } else {
                $list = explode('|', $field);
                if (count($list) > 1) {
                    list($v, $k) = $list;
                    if ($k == 'contains') {
                        $k = 'like';
                    }
                    if ($k == 'like') {
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
        }
        return $qb;
    }

    /**
     * 根据条件获取列表数据
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
        return $qb->execute()->fetchAll();
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
        $qb->select('count(*)')
             ->from($this->table);
        if ($filter) {
            $this->_filter($filter, $qb);
        }
        $count = $qb->execute()->fetchColumn();
        return intval($count);
    }

    public function getUserActivities($filter, $cols = '*', $page = 1, $pageSize = -1, $orderBy = array())
    {
        if (!isset($filter['company_id'], $filter['user_id']) || !$filter['company_id'] || !$filter['user_id']) {
            throw new ResourceException('参数错误');
        }
        $companyId = $filter['company_id'];
        $userId = $filter['user_id'];
        $now = time();
        $subQuery = "SELECT ac.id,ac.company_id,ac.pic,ac.name,ac.title,ac.pages_template_id,ac.display_time,ac.employee_begin_time,ac.employee_end_time,ac.employee_limitfee,ac.if_relative_join,ac.invite_limit,ac.relative_begin_time,ac.relative_end_time,ac.if_share_limitfee,ac.relative_limitfee,ac.minimum_amount,ac.status,ac.if_share_store,ac.created,ac.updated,ac.price_display_config,ac.is_discount_description_enabled,ac.discount_description,ep.enterprise_id,ep.user_id,1 AS is_employee,0 AS is_relative,et.name AS rel_enterprise FROM employee_purchase_activities ac LEFT JOIN employee_purchase_activity_enterprises ae ON ac.id=ae.activity_id LEFT JOIN employee_purchase_employees ep ON ae.enterprise_id=ep.enterprise_id LEFT JOIN employee_purchase_enterprises et ON ae.enterprise_id=et.id WHERE ac.company_id={$companyId} AND ep.user_id={$userId} AND ac.status IN ('active', 'pending') AND et.disabled=0 AND ac.display_time<{$now} AND (ac.employee_end_time>{$now} OR ac.relative_end_time>{$now}) UNION SELECT ac.id,ac.company_id,ac.pic,ac.name,ac.title,ac.pages_template_id,ac.display_time,ac.employee_begin_time,ac.employee_end_time,ac.employee_limitfee,ac.if_relative_join,ac.invite_limit,ac.relative_begin_time,ac.relative_end_time,ac.if_share_limitfee,ac.relative_limitfee,ac.minimum_amount,ac.status,ac.if_share_store,ac.created,ac.updated,ac.price_display_config,ac.is_discount_description_enabled,ac.discount_description,re.enterprise_id,re.user_id,0 AS is_employee,1 AS is_relative,et.name AS rel_enterprise FROM employee_purchase_activities ac LEFT JOIN employee_purchase_relatives re ON ac.id=re.activity_id LEFT JOIN employee_purchase_enterprises et ON re.enterprise_id=et.id WHERE ac.company_id={$companyId} AND re.user_id={$userId} AND ac.status IN ('active', 'pending') AND et.disabled=0 AND ac.display_time<{$now} AND ac.relative_end_time>{$now}";
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb = $qb->select('count(*) as _count')->from('('.$subQuery.')', 't');

        if ($filter) {
            $this->_filter($filter, $qb);
        }
        $result['total_count'] = $qb->execute()->fetchColumn();

        if ($result['total_count'] > 0) {
            $conn = app('registry')->getConnection('default');
            $qb = $conn->createQueryBuilder()->select($cols)->from('('.$subQuery.')', 't');
            if ($filter) {
                $this->_filter($filter, $qb);
            }
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

    public function getActivityUsers($filter, $page = 1, $pageSize = -1) {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb = $qb->select('count(re.id)')
            ->from('employee_purchase_relatives', 're')
            ->leftJoin('re', 'employee_purchase_employees', 'ep', 're.employee_id = ep.id')
            ->leftJoin('re', 'employee_purchase_enterprises', 'et', 're.enterprise_id = et.id')
            ->leftJoin('re', 'employee_purchase_member_activity_aggregate', 'ag', 're.enterprise_id=ag.enterprise_id and re.user_id=ag.user_id and re.activity_id=ag.activity_id and re.disabled=0')
            ->andWhere($qb->expr()->eq('re.company_id', $filter['company_id']))
            ->andWhere($qb->expr()->eq('re.activity_id', $filter['activity_id']));

        if (isset($filter['employee_mobile']) && $filter['employee_mobile']) {
            $qb = $qb->andWhere($qb->expr()->eq('ep.mobile', $filter['employee_mobile']));
        }

        if (isset($filter['relative_mobile']) && $filter['relative_mobile']) {
            $qb = $qb->andWhere($qb->expr()->eq('re.member_mobile', $filter['relative_mobile']));
        }

        $result['total_count'] = $qb->execute()->fetchColumn();

        if ($result['total_count'] > 0) {
            $qb->addOrderBy('re.created', 'DESC');
            if ($pageSize > 0) {
                $qb->setFirstResult(($page - 1) * $pageSize)
                  ->setMaxResults($pageSize);
            }
            $lists = $qb->select('et.name as enterprise_name,et.enterprise_sn,ep.user_id as employee_user_id,ep.mobile as employee_mobile,ep.account as employee_account,re.user_id as relative_user_id,re.member_mobile as relative_mobile,re.created,re.disabled,ag.aggregate_fee')->execute()->fetchAll();

            if ($lists) {
                $userIds = array_column($lists, 'employee_user_id');
                $userIds = array_merge($userIds, array_column($lists, 'relative_user_id'));
                $memberService = new MemberService();
                $memberList = $memberService->getMemberInfoList(['company_id' => $filter['company_id'], 'user_id' => $userIds], 1, -1);
                $memberList = array_column($memberList['list'], null, 'user_id');
                foreach ($lists as $key => $row) {
                    if (isset($memberList[$row['employee_user_id']])) {
                        $result['list'][$key]['employee_username'] = $memberList[$row['employee_user_id']]['username'] ?? '';
                    }

                    if (isset($memberList[$row['relative_user_id']])) {
                        $result['list'][$key]['relative_username'] = $memberList[$row['relative_user_id']]['username'] ?? '';
                    }
                }
            }
        }
        $result['list'] = $lists ?? [];

        return $result;
    }
}
