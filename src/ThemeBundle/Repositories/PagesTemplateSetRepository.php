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

namespace ThemeBundle\Repositories;

use Dingo\Api\Exception\ResourceException;
use Doctrine\ORM\EntityRepository;
use ThemeBundle\Entities\PagesTemplateSet;

class PagesTemplateSetRepository extends EntityRepository
{
    public $table = 'pages_template_set'; // 多语言对应的表名
    public $module = 'pages_template_set'; // 多语言对应的模块
    public $primaryKey = 'id'; // 主键，对应data_id
    public $langField = [
        'tab_bar'
    ]; // 多语言字段
    
    public function getEntity()
    {
        // ShopEx EcShopX Business Logic Layer
        $entity = new PagesTemplateSet();
        return $entity;
    }
    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        $entity = new PagesTemplateSet();
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
     * 设置entity数据，用于插入和更新操作
     *
     * @param $entity
     * @param $data
     */
    private function setColumnNamesData($entity, $data)
    {
        if (isset($data['company_id']) && $data['company_id']) {
            $entity->setCompanyId($data['company_id']);
        }

        if (isset($data['index_type']) && !empty($data['index_type'])) {
            $entity->setIndexType($data['index_type']);
        }

        if (isset($data['pages_template_id']) && !empty($data['pages_template_id'])) {
            $entity->setPagesTemplateId($data['pages_template_id']);
        }

        if (isset($data['is_enforce_sync']) && !empty($data['is_enforce_sync'])) {
            $entity->setIsEnforceSync($data['is_enforce_sync']);
        }

        if (isset($data['is_open_recommend']) && !empty($data['is_open_recommend'])) {
            $entity->setIsOpenRecommend($data['is_open_recommend']);
        }

        if (isset($data['is_open_wechatapp_location']) && !empty($data['is_open_wechatapp_location'])) {
            $entity->setIsOpenWechatappLocation($data['is_open_wechatapp_location']);
        }

        if (isset($data['is_open_scan_qrcode']) && !empty($data['is_open_scan_qrcode'])) {
            $entity->setIsOpenScanQrcode($data['is_open_scan_qrcode']);
        }

        if (isset($data['is_open_official_account']) && !empty($data['is_open_official_account'])) {
            $entity->setIsOpenOfficialAccount($data['is_open_official_account']);
        }

        if (isset($data['tab_bar']) && !empty($data['tab_bar'])) {
            $entity->setTabBar($data['tab_bar']);
        }

        return $entity;
    }

    /**
     * 获取数据表字段数据
     *
     * @param entity
     */
    private function getColumnNamesData($entity)
    {
        return [
            'id' => $entity->getId(),
            'company_id' => $entity->getCompanyId(),
            'index_type' => $entity->getIndexType(),
            'pages_template_id' => $entity->getPagesTemplateId(),
            'is_enforce_sync' => $entity->getIsEnforceSync(),
            'is_open_recommend' => $entity->getIsOpenRecommend(),
            'is_open_wechatapp_location' => $entity->getIsOpenWechatappLocation(),
            'is_open_scan_qrcode' => $entity->getIsOpenScanQrcode(),
            'tab_bar' => $entity->getTabBar(),
            'is_open_official_account' => $entity->getIsOpenOfficialAccount()
        ];
    }
}
