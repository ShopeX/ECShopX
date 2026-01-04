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

namespace MembersBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use MembersBundle\Entities\Members;

use Doctrine\Common\Collections\Criteria;

use Dingo\Api\Exception\StoreResourceFailedException;
use Dingo\Api\Exception\UpdateResourceFailedException;

class MembersRepository extends EntityRepository
{
    /**
     * 当前表名称
     */
    public $table = 'members';

    public function create($params)
    {
        // ShopEx EcShopX Business Logic Layer
        $filter = [
            'company_id' => $params['company_id'],
            'mobile' => $params['mobile'],
        ];
        $filter = $this->fixedencryptCol($filter);
        $user = $this->findOneBy($filter);
        if ($user) {
            throw new StoreResourceFailedException("手机号为{$params['mobile']}的会员已存在！");
        }

        $userEntity = new Members();
        $user = $this->setUserData($userEntity, $params);

        if (isset($params['created'])) {
            $user->setCreated($params['created']);
            $user->setCreatedYear(date('Y', $params['created']));
            $user->setCreatedMonth(date('m', $params['created']));
            $user->setCreatedDay(date('d', $params['created']));
        } else {
            $user->setCreatedYear(date('Y'));
            $user->setCreatedMonth(date('m'));
            $user->setCreatedDay(date('d'));
        }

        $em = $this->getEntityManager();
        $em->persist($user);
        $em->flush();

        $result = $this->getUserData($user);

        return $result;
    }

    public function get($filter)
    {
        $filter = $this->fixedencryptCol($filter);
        $userEntity = $this->findOneBy($filter);
        $result = [];
        if ($userEntity) {
            $result = $this->getUserData($userEntity);
        }
        return $result;
    }

    public function getMobileByUserIds($companyId, $userIds)
    {
        $criteria = Criteria::create();
        $criteria = $criteria->where(Criteria::expr()->eq('company_id', $companyId));
        $criteria = $criteria->andWhere(Criteria::expr()->in('user_id', $userIds));

        $list = [];
        $entityList = $this->matching($criteria);
        foreach ($entityList as $entity) {
            $userId = $entity->getUserId();
            $list[$userId] = $entity->getMobile();
        }
        return $list;
    }

    public function getList($filter, $offset = 0, $limit = -1, $orderBy = ['created' => 'DESC'])
    {
        $filter = $this->fixedencryptCol($filter);
        $commonKey = ['company_id','user_id','created', 'updated', 'created_month', 'created_day', 'created_year'];
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('count(*)')
           ->from($this->table, 'm')
           ->leftJoin('m', 'members_info', 'i', 'm.company_id = i.company_id and m.user_id = i.user_id');
        if ($filter) {
            foreach ($filter as $field => $value) {
                $list = explode('|', $field);
                if (count($list) > 1) {
                    list($v, $k) = $list;
                    if (in_array($k, ['like', 'notlike'])) {
                        $qb->andWhere($qb->expr()->$k($v, $qb->expr()->literal('%'.$value.'%')));
                    } elseif (in_array($k, ['in', 'notIn'])) {
                        $qb->andWhere($qb->expr()->$k('m.'.$v, $value));
                    } else {
                        $qb->andWhere($qb->expr()->$k('m'.$v, $qb->expr()->literal($value)));
                    }
                    continue;
                } elseif (in_array($field, $commonKey)) {
                    if (is_array($value)) {
                        $qb->andWhere($qb->expr()->in('m.'.$field, $value));
                    } else {
                        $qb->andWhere($qb->expr()->eq('m.'.$field, $qb->expr()->literal($value)));
                    }
                } else {
                    $qb->andWhere($qb->expr()->eq($field, $qb->expr()->literal($value)));
                }
                $qb->andWhere($qb->expr()->isNotNull('m.mobile'));
                $qb->andWhere($qb->expr()->isNotNull('m.user_card_code'));
            }
        }
        $res['total_count'] = $qb->execute()->fetchColumn();
        if ($limit > 0) {
            $qb->setFirstResult($offset)->setMaxResults($limit);
        }
        foreach ($orderBy as $key => $value) {
            if (in_array($key, $commonKey)) {
                $qb->addOrderBy('m.'.$key, $value);
            } else {
                $qb->addOrderBy($key, $value);
            }
        }
        $res['list'] = $qb->select('m.*, i.username, i.sex')->execute()->fetchAll();
        foreach ($res['list'] as &$v) {
            $v['created_date'] = date('Y-m-d H:i:s', $v['created']);
            $v['mobile'] = fixeddecrypt($v['mobile']);
            $v['username'] = fixeddecrypt($v['username']);
        }
        return $res;
    }

    /**
     * 更新会员信息
     * @param $params
     * @param $filter
     * @param array $userInfo 修改前的用户信息（用于外部的用户操作记录）
     * @return array
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function update($params, $filter, array &$userInfo = [])
    {
        $filter = $this->fixedencryptCol($filter);
        unset($params['company_id']);
        $userEntity = $this->findOneBy($filter);
        if (!$userEntity) {
            $userId = $filter['user_id'] ?? 0;
            throw new UpdateResourceFailedException("user_id={$userId}的会员不存在");
        } else {
            $userInfo = $this->getUserData($userEntity);
        }
        $user = $this->setUserData($userEntity, $params);
        $em = $this->getEntityManager();
        $em->persist($user);
        $em->flush();

        $result = $this->getUserData($user);

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
        $conn = app("registry")->getConnection("default");
        $qb = $conn->createQueryBuilder()->update($this->table);
        foreach ($data as $key => $val) {
            $qb = $qb->set($key, $qb->expr()->literal($val));
        }
        $qb = $this->_filter($filter, $qb);
        return $qb->execute();
    }

    /**
     * 根据数据生成entity对象
     * @param Members $data
     * @return array
     */
    public function getDataByEntity(Members $entity)
    {
        return $this->getUserData($entity);
    }

    private function getUserData($userEntity)
    {
        /**
         * @var \MembersBundle\Entities\Members $userEntity
         */
        $result = [
            'user_id' => $userEntity->getUserId(),
            'company_id' => $userEntity->getCompanyId(),
            'grade_id' => $userEntity->getGradeId(),
            'mobile' => $userEntity->getMobile(),
            'region_mobile' => $userEntity->getRegionMobile(),
            'mobile_country_code' => $userEntity->getMobileCountryCode(),
            'user_card_code' => $userEntity->getUserCardCode(),
            'offline_card_code' => $userEntity->getOfflineCardCode(),

            'inviter_id' => $userEntity->getInviterId(),
            'source_from' => $userEntity->getSourceFrom(),
            'source_id' => $userEntity->getSourceId(),
            'monitor_id' => $userEntity->getMonitorId(),
            'latest_source_id' => $userEntity->getLatestSourceId(),
            'latest_monitor_id' => $userEntity->getLatestMonitorId(),
            'authorizer_appid' => $userEntity->getAuthorizerAppid(),

            'use_point' => $userEntity->getUsePoint(),
            'wxa_appid' => $userEntity->getWxaAppid(),
            'alipay_appid' => $userEntity->getAlipayAppid(),
            'created' => $userEntity->getCreated(),
            'updated' => $userEntity->getUpdated(),
            'disabled' => $userEntity->getDisabled(),
            'remarks' => $userEntity->getRemarks(),
            'third_data' => $userEntity->getThirdData(),
        ];

        return $result;
    }

    private function setUserData($userEntity, $userData)
    {
        /**
         * @var \MembersBundle\Entities\Members $userEntity
         */
        if (isset($userData['company_id'])) {
            $userEntity->setCompanyId($userData['company_id']);
        }
        if (isset($userData['grade_id'])) {
            $userEntity->setGradeId($userData['grade_id']);
        }
        if (isset($userData['mobile'])) {
            $userEntity->setMobile($userData['mobile']);
        }
        if (isset($userData['region_mobile'])) {
            $userEntity->setRegionMobile($userData['region_mobile']);
        }
        if (isset($userData['mobile_country_code'])) {
            $userEntity->setMobileCountryCode($userData['mobile_country_code']);
        }
        if (isset($userData['password'])) {
            $userEntity->setPassword($userData['password']);
        }
        if (isset($userData['user_card_code'])) {
            $userEntity->setUserCardCode($userData['user_card_code']);
        }
        if (isset($userData['offline_card_code'])) {
            $userEntity->setOfflineCardCode($userData['offline_card_code']);
        }
        if (isset($userData['authorizer_appid'])) {
            $userEntity->setAuthorizerAppid($userData['authorizer_appid']);
        }
        if (isset($userData['wxa_appid'])) {
            $userEntity->setWxaAppid($userData['wxa_appid']);
        }
        if (isset($userData['alipay_appid'])) {
            $userEntity->setAlipayAppid($userData['alipay_appid']);
        }
        if (isset($userData['inviter_id'])) {
            $userEntity->setInviterId($userData['inviter_id']);
        }
        if (isset($userData['source_from'])) {
            $userEntity->setSourceFrom($userData['source_from']);
        }
        if (isset($userData['source_id'])) {
            $userEntity->setSourceId($userData['source_id']);
        }
        if (isset($userData['monitor_id'])) {
            $userEntity->setMonitorId($userData['monitor_id']);
        }
        if (isset($userData['latest_source_id'])) {
            $userEntity->setLatestSourceId($userData['latest_source_id']);
        }
        if (isset($userData['latest_monitor_id'])) {
            $userEntity->setLatestMonitorId($userData['latest_monitor_id']);
        }
        if (isset($userData['disabled'])) {
            $userEntity->setDisabled($userData['disabled']);
        }
        if (isset($userData['remarks'])) {
            $userEntity->setRemarks($userData['remarks']);
        }
        if (isset($userData['use_point'])) {
            $userEntity->setUsePoint($userData['use_point']);
        }
        if (isset($userData['third_data'])) {
            $userEntity->setThirdData($userData['third_data']);
        }
        return $userEntity;
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
     * @param array $filter 过滤条件
     * @param string[] $orderBy
     * @param int $pageSize
     * @param int $page
     * @param bool $needCountSql true表示需要使用count语句查询总数量
     * @return mixed
     */
    public function lists($filter, $orderBy = ["created" => "DESC"], $pageSize = 100, $page = 1, bool $needCountSql = true)
    {
        $filter = $this->fixedencryptCol($filter);
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

        if ($needCountSql) {
            $total = $this->getEntityManager()
                ->getUnitOfWork()
                ->getEntityPersister($this->getEntityName())
                ->count($criteria);
        } else {
            $total = 0;
        }
        $res["total_count"] = intval($total);

        $lists = [];
        if (!$needCountSql || $res["total_count"]) {
            $criteria = $criteria->orderBy($orderBy);
            if ($page > 0) {
                $criteria->setFirstResult($pageSize * ($page - 1))->setMaxResults($pageSize);
            }
            $entityList = $this->matching($criteria);
            foreach ($entityList as $entity) {
                $lists[] = $this->getUserData($entity);
            }
        }

        $res["list"] = $lists;
        return $res;
    }

    public function getDataList($filter, $cols = "user_id, mobile", $page = 1, $pageSize = -1)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()->select($cols)->from($this->table);
        $qb = $this->_filter($filter, $qb);
        if ($pageSize > 0) {
            $qb->setFirstResult(($page - 1) * $pageSize)
                 ->setMaxResults($pageSize);
        }
        $lists = $qb->execute()->fetchAll();
        return $lists;
    }

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

    public function deleteBy($filter)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()->delete($this->table);

        $qb = $this->_filter($filter, $qb);
        return $qb->execute();
    }

    /**
     * 对filter中的部分字段，加密处理
     * @param  [type] $filter [description]
     * @return [type]         [description]
     */
    private function fixedencryptCol($filter)
    {
        $fixedencryptCol = ['mobile'];
        foreach ($fixedencryptCol as $col) {
            if (isset($filter[$col])) {
                $filter[$col] = fixedencrypt((string) $filter[$col]);
            }
        }
        return $filter;
    }
}
