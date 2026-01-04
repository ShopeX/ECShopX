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

namespace CompanysBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use CompanysBundle\Entities\Companys;
use Doctrine\Common\Collections\Criteria;
use Illuminate\Support\Arr;
use Dingo\Api\Exception\UpdateResourceFailedException;

class CompanysRepository extends EntityRepository
{
    public $table = "companys";

    public $module = 'companys'; // 多语言对应的模块
    public $primaryKey = 'company_id'; // 主键，对应data_id
    public $langField = [
        'company_name','industry','third_params'
    ]; // 多语言字段
    
    public function getEntity()
    {
        $entity = new Companys();
        return $entity;
    }


    public $cols = ['company_id','company_name','pc_domain','h5_domain','eid','passport_uid','company_admin_operator_id','industry','created','updated','expiredAt','is_disabled','third_params','salesman_limit','is_open_pc_template','is_open_domain_setting','menu_type','deleted_at'];

    public function getAllCompanys()
    {
        return app('registry')->getConnection('default')->fetchAssoc("select * from companys");
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

        return $this->getCompanyData($entity);
    }

    public function get($filter)
    {
        return $this->findOneBy($filter);
    }

    /**
     * 复位自增ID
     *
     * @param int $companyId 自增ID
     */
    public function resetCompanyId($companyId = 1)
    {
        try {
            $em = $this->getEntityManager();
            $em->getConnection()->prepare("ALTER TABLE {$this->table} AUTO_INCREMENT = {$companyId} ")->execute();
        } catch (\Exception $e) {
        }
    }

    public function create($params)
    {
        $companyEntity = new Companys();
        $company = $this->__setCompanyData($companyEntity, $params);

        $em = $this->getEntityManager();
        $em->persist($company);
        $em->flush();

        $result = $this->getCompanyData($company);

        return $result;
    }

    /**
     * 插入数据
     */
    public function add($params)
    {
        $data = Arr::only($params, $this->cols);
        if (isset($data['third_params']) && is_array($data['third_params'])) {
            $data['third_params'] = json_encode($data['third_params']);
        }

        $data['created'] = time();
        $data['updated'] = time();
        $conn = app('registry')->getConnection('default');
        $conn->insert($this->table, $data);

        $return = ['company_id' => $conn->lastInsertId()];
        return $return;
    }

    public function update($filter, $updateInfo)
    {
        $companyEntity = $this->findOneBy($filter);
        if (!$companyEntity) {
            throw new UpdateResourceFailedException("企业账号为{$filter['company_id']}不存在！");
        }
        $company = $this->__setCompanyData($companyEntity, $updateInfo);
        $em = $this->getEntityManager();
        $em->persist($company);
        $em->flush();

        return $updateInfo;
    }

    private function __setCompanyData($companyEntity, $updateInfo)
    {
        if (isset($updateInfo['company_name'])) {
            $companyEntity->setCompanyName($updateInfo['company_name']);
        }
        if (isset($updateInfo['eid'])) {
            $companyEntity->setEid($updateInfo['eid']);
        }
        if (isset($updateInfo['passport_uid'])) {
            $companyEntity->setPassportUid($updateInfo['passport_uid']);
        }
        if (isset($updateInfo['company_admin_operator_id'])) {
            $companyEntity->setCompanyAdminOperatorId($updateInfo['company_admin_operator_id']);
        }
        if (isset($updateInfo['industry'])) {
            $companyEntity->setIndustry($updateInfo['industry']);
        }
        if (isset($updateInfo['expiredAt'])) {
            $companyEntity->setExpiredAt($updateInfo['expiredAt']);
        }
        if (isset($updateInfo['is_disabled'])) {
            $companyEntity->setIsDisabled($updateInfo['is_disabled']);
        }
        if (isset($updateInfo['third_params'])) {
            $companyEntity->setThirdParams($updateInfo['third_params']);
        }
        if (isset($updateInfo['salesman_limit'])) {
            $companyEntity->setSalesmanLimit($updateInfo['salesman_limit']);
        }
        if (isset($updateInfo['is_open_pc_template'])) {
            $companyEntity->setIsOpenPcTemplate($updateInfo['is_open_pc_template']);
        }
        if (isset($updateInfo['is_open_domain_setting'])) {
            $companyEntity->setIsOpenDomainSetting($updateInfo['is_open_domain_setting']);
        }

        if (isset($updateInfo['h5_domain'])) {
            $companyEntity->setH5Domain($updateInfo['h5_domain']);
        }

        if (isset($updateInfo['pc_domain'])) {
            $companyEntity->setPcDomain($updateInfo['pc_domain']);
        }
        if (isset($updateInfo['menu_type'])) {
            $companyEntity->setMenuType($updateInfo['menu_type']);
        }

        return $companyEntity;
    }

    /**
     * 根据条件获取商家列表
     *
     * @param $filter 更新的条件
     */
    public function lists($filter, $cols = '*', $page = 1, $pageSize = 100, $orderBy = array())
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
        $res["total_count"] = intval($total);

        $lists = [];
        if ($res["total_count"]) {
            if ($orderBy) {
                $criteria = $criteria->orderBy($orderBy);
            }
            $criteria->setFirstResult($pageSize * ($page - 1))
                ->setMaxResults($pageSize);
            $entityList = $this->matching($criteria);
            foreach ($entityList as $entity) {
                $lists[] = $this->getCompanyData($entity);
            }
        }

        $res["list"] = $lists;
        return $res;
    }

    public function getCompanyData($companyEntity)
    {
        return [
            'company_id' => $companyEntity->getCompanyId(),
            'company_name' => $companyEntity->getCompanyName(),
            'eid' => $companyEntity->getEid(),
            'passport_uid' => $companyEntity->getPassportUid(),
            'company_admin_operator_id' => $companyEntity->getCompanyAdminOperatorId(),
            'industry' => $companyEntity->getIndustry(),
            'created' => $companyEntity->getCreated(),
            'created_date' => date('Y-m-d H:i:s', $companyEntity->getCreated()),
            'expiredAt' => $companyEntity->getExpiredAt(),
            'expiredAt_date' => date('Y-m-d H:i:s', $companyEntity->getExpiredAt()),
            'is_disabled' => $companyEntity->getIsDisabled(),
            'third_params' => $companyEntity->getThirdParams(),
            'salesman_limit' => $companyEntity->getSalesmanLimit(),
            'is_open_pc_template' => $companyEntity->getIsOpenPcTemplate(),
            'is_open_domain_setting' => $companyEntity->getIsOpenDomainSetting(),
            'h5_domain' => $companyEntity->getH5Domain(),
            'pc_domain' => $companyEntity->getPcDomain(),
            'menu_type' => $companyEntity->getMenuType()
        ];
    }

    public function getByCompanyId($id)
    {
        return $this->find($id);
    }
}
