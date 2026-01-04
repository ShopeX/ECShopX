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

namespace WechatBundle\Repositories;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use WechatBundle\Entities\WeappSetting;

class WeappSettingRepository extends EntityRepository
{
    /**
     * 当前表名称
     */
    public $table = 'wechat_weapp_setting';
    public $module = 'wechat_weapp_setting'; // 多语言对应的模块
    public $primaryKey = 'id'; // 主键，对应data_id
    public $langField = [
        'page_name','name','params|serialize'
    ]; // 多语言字段
    
    public function getEntity()
    {
        $entity = new WeappSetting();
        return $entity;
    }

    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        $entity = new WeappSetting();
        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return $this->getColumnNamesData($entity);
    }

    public function setParams($companyId, $templateName, $pageName, $configName, array $params, $version = 'v1.0.0', $pages_template_id = 0, $prkid = 0, $sortBy = 0)
    {
        if ($prkid > 0) {
            $conn = app('registry')->getConnection('default');
            $saveData['company_id'] = $companyId;
            $saveData['template_name'] = $templateName;
            $saveData['name'] = $configName;
            $saveData['page_name'] = $pageName;
            $saveData['version'] = $version;
            $saveData['params'] = serialize($params);
            $saveData['pages_template_id'] = $pages_template_id;
            $saveData['sort_by'] = $sortBy;
            $conn->update($this->table, $saveData, ['id' => $prkid]);
            $id = $prkid;
        }else {
            $conn = app('registry')->getConnection('default');
            $saveData['company_id'] = $companyId;
            $saveData['template_name'] = $templateName;
            $saveData['name'] = $configName;
            $saveData['page_name'] = $pageName;
            $saveData['version'] = $version;
            $saveData['params'] = serialize($params);
            $saveData['pages_template_id'] = $pages_template_id;
            $saveData['sort_by'] = $sortBy;
            $conn->insert($this->table, $saveData);
            $id = $conn->lastInsertId(); 
            // 兼容多语言问题，必须得重新保存下自己的数据params里面的id
            if ($id) {
                $params['id'] = $id;
                $saveData['params'] = serialize($params);
                $conn->update($this->table, $saveData, ['id' => $id]);
            }
        }
       
        return $id;
    }

    /**
     * 获取商家对于小程序的配置参数
     * @param $companyId
     * @param $templateName
     * @param null $pageName
     * @param null $configName
     * @param string $version
     * @param int $pages_template_id
     * @param int $id
     * @param array $orderBy
     * @return array
     */
    public function getParamByTempName($companyId, $templateName, $pageName = null, $configName = null, $version = 'v1.0.0', $pages_template_id = 0, $id = 0, $orderBy = [])
    {
        $filter = ['company_id' => $companyId, 'template_name' => $templateName];

        if ($version) {
            $filter['version'] = $version;
        }

        if ($configName) {
            $filter['name'] = $configName;
        }

        if ($pageName) {
            $filter['page_name'] = $pageName;
        }

        if ($pages_template_id) {
            $filter['pages_template_id'] = $pages_template_id;
        }

        if ($id) {
            $filter['id'] = $id;
        }

        if (!$orderBy) {
            $orderBy = ['sort_by' => 'ASC'];//默认按ID升序，和显示顺序一致
        }

        $data = $this->findBy($filter, $orderBy);
        // $list = [];
        // if ($data) {
        //     foreach($data as $row) {
        //         $pageName = $row->getPageName();
        //         $list[] = [
        //             'id' => $row->getId(),
        //             'template_name' => $row->getTemplateName(),
        //             'company_id' => $row->getCompanyId(),
        //             'name' => $row->getName(),
        //             'page_name' => $pageName ? $pageName : 'index',
        //             'params' => unserialize($row->getParams()),
        //         ];
        //     }
        // }
        return $data;
    }

    /**
     * 根据条件删除指定数据
     *
     * @param array $filter 删除的条件
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

    /**
     * 更新指定id的配置
     */
    public function updateParamsById($id, $companyId, $params)
    {
        $conn = app('registry')->getConnection('default');
        $saveData['params'] = serialize($params);
        return $conn->update($this->table, $saveData, ['id' => $id, 'company_id' => $companyId]);
    }

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

    public function lists($filter, $cols = '*', $page = 1, $pageSize = 100, $orderBy = ["sort_by" => "ASC"])
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
            if ($pageSize > 0) {
                $criteria = $criteria->setFirstResult($pageSize * ($page - 1))
                    ->setMaxResults($pageSize);
            }
            if ($orderBy) {
                $criteria = $criteria->orderBy($orderBy);
            }
            $entityList = $this->matching($criteria);
            foreach ($entityList as $entity) {
                $lists[] = $this->getColumnNamesData($entity);
            }
        }

        $res["list"] = $lists;
        return $res;
    }

    /**
     * 设置entity数据，用于插入和更新操作
     *
     * @param object $entity
     * @param array $data
     */
    private function setColumnNamesData($entity, $data)
    {
        if (isset($data["company_id"]) && $data["company_id"]) {
            $entity->setCompanyId($data["company_id"]);
        }
        if (isset($data["template_name"])) {
            $entity->setTemplateName($data["template_name"]);
        }
        if (isset($data["page_name"])) {
            $entity->setPageName($data["page_name"]);
        }
        if (isset($data["name"])) {
            $entity->setName($data["name"]);
        }
        if (isset($data["version"])) {
            $entity->setVersion($data["version"]);
        }
        if (isset($data["params"])) {
            $entity->setParams($data["params"]);
        }
        if (isset($data["pages_template_id"])) {
            $entity->setPagesTemplateId($data["pages_template_id"]);
        }
        if (isset($data["sort_by"])) {
            $entity->setSortBy($data["sort_by"]);
        }
        if (isset($data["created"]) && $data["created"]) {
            $entity->setCreated($data["created"]);
        }
        //当前字段非必填
        if (isset($data["updated"]) && $data["updated"]) {
            $entity->setUpdated($data["updated"]);
        }
        return $entity;
    }

    /**
     * 获取数据表字段数据
     *
     * @param object $entity
     */
    private function getColumnNamesData($entity)
    {
        return [
            'id' => $entity->getId(),
            'template_name' => $entity->getTemplateName(),
            'company_id' => $entity->getCompanyId(),
            'page_name' => $entity->getPageName(),
            'name' => $entity->getName(),
            'version' => $entity->getVersion(),
            'params' => $entity->getParams(),
            'pages_template_id' => $entity->getPagesTemplateId(),
            'sort_by' => $entity->getSortBy(),
        ];
    }
}
