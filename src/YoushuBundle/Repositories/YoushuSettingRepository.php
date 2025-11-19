<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace YoushuBundle\Repositories;

use Dingo\Api\Exception\ResourceException;
use Doctrine\ORM\EntityRepository;
use YoushuBundle\Entities\YoushuSetting;

class YoushuSettingRepository extends EntityRepository
{
    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        $entity = new YoushuSetting();
        $entity = $this->setColumnNamesData($entity, $data);
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return $this->getColumnNamesData($entity);
    }

    /**
     * 更新数据表字段数据
     *
     * @param array $filter 更新的条件
     * @param array $data 更新的内容
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
     * @param array $filter 更新的条件
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
     * @return mixed
     * 获取数据列表
     */
    public function getAll()
    {
        $pages_template_list = $this->findAll();
        $list = [];
        foreach ($pages_template_list as $v) {
            $list[] = $this->getColumnNamesData($v);
        }
        $res['list'] = $list;

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
        if (isset($data['company_id']) && $data['company_id']) {
            $entity->setCompanyId($data['company_id']);
        }

        if (isset($data['merchant_id'])) {
            $entity->setMerchantId($data['merchant_id']);
        }

        if (isset($data['app_id'])) {
            $entity->setAppId($data['app_id']);
        }

        if (isset($data['app_secret'])) {
            $entity->setAppSecret($data['app_secret']);
        }

        if (isset($data['api_url'])) {
            $entity->setApiUrl($data['api_url']);
        }

        if (isset($data['sandbox_app_id'])) {
            $entity->setSandboxAppId($data['sandbox_app_id']);
        }

        if (isset($data['sandbox_app_secret'])) {
            $entity->setSandboxAppSecret($data['sandbox_app_secret']);
        }

        if (isset($data['sandbox_api_url'])) {
            $entity->setSandboxApiUrl($data['sandbox_api_url']);
        }

        if (isset($data['weapp_name'])) {
            $entity->setWeappName($data['weapp_name']);
        }

        if (isset($data['weapp_app_id'])) {
            $entity->setWeappAppId($data['weapp_app_id']);
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
            'company_id' => $entity->getCompanyId(),
            'merchant_id' => $entity->getMerchantId(),
            'app_id' => $entity->getAppId(),
            'app_secret' => $entity->getAppSecret(),
            'api_url' => $entity->getApiUrl(),
            'sandbox_app_id' => $entity->getSandboxAppId(),
            'sandbox_app_secret' => $entity->getSandboxAppSecret(),
            'sandbox_api_url' => $entity->getSandboxApiUrl(),
            'weapp_name' => $entity->getWeappName(),
            'weapp_app_id' => $entity->getWeappAppId(),
        ];
    }
}
