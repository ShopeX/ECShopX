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

namespace CompanysBundle\Services;

use CompanysBundle\Entities\WxExternalConfig;
use CompanysBundle\Entities\WxExternalRoutes;
use Dingo\Api\Exception\ResourceException;

class WxExternalConfigService
{
    private $entityRepository;
    private $routesEntityRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(WxExternalConfig::class);
        $this->routesEntityRepository = app('registry')->getManager('default')->getRepository(WxExternalRoutes::class);
    }

    /**
     *
     *
     * @param  string $method
     * @param  array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }

    /**
     * 获取小程序配置列表
     * @param  array $filter
     * @param  string $cols
     * @param  integer $page
     * @param  integer $page_size
     * @return array
     */
    public function getWxExternalConfigList($filter, $cols = '*', $page = 1, $page_size = 10)
    {
        if (isset($filter['app_name'])) {
            $filter['app_name|like'] = trim($filter['app_name']);
            unset($filter['app_name']);
        }
        $list = $this->entityRepository->lists($filter, $cols, $page, $page_size, ['created_at' => 'desc']);
        if (empty($list['list'])) {
            return $list;
        }
        return $list;
    }

    /**
     * 创建小程序配置
     * @param  array $params
     * @param  integer $companyId
     * @return array
     */
    public function createWxExternalConfig($companyId, $params)
    {
        $info = $this->entityRepository->getInfo(['app_id' => $params['app_id']]);
        if ($info) {
            throw new ResourceException("app_id已存在");
        }
        $data = [
            'company_id' => $companyId,
            'app_id' => $params['app_id'],
            'app_name' => $params['app_name'],
            'app_desc' => isset($params['app_desc']) ? $params['app_desc'] : '',
        ];
        return $this->entityRepository->create($data);
    }

    /**
     * 修改小程序配置
     * @param  array $params
     * @param  integer $companyId
     * @return array
     */
    public function updateWxExternalConfig($companyId, $wx_external_config_id, $params)
    {
        // ShopEx EcShopX Service Component
        $filter = [
            'company_id' => $companyId,
            'wx_external_config_id' => $wx_external_config_id,
        ];
        $data = [
            'app_name' => $params['app_name'],
            // 'app_desc' => $params['app_desc'],
        ];
        if (isset($params['app_id']) && $params['app_id']) {
            $info = $this->entityRepository->getInfo(['app_id' => $params['app_id']]);
            if ($info && $info['wx_external_config_id'] != $wx_external_config_id) {
                throw new ResourceException("app_id已存在");
            }
            $data['app_id'] = $params['app_id'];
        }
        if (isset($params['app_desc'])) {
            $data['app_desc'] = $params['app_desc'];
        }
        return $this->entityRepository->updateOneBy($filter, $data);
    }

    /**
     * 获取外部小程序配置路径列表
     * @param  array $filter
     * @param  string $cols
     * @param  integer $page
     * @param  integer $page_size
     * @return array
     */
    public function getConfigRoutesList($params, $offset = 1, $limit = 10)
    {
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $criteria->select('count(*)');
        $criteria->from('wx_external_config', 'config')
            ->rightJoin('config', 'wx_external_routes', 'routes', 'config.wx_external_config_id = routes.wx_external_config_id');
        $row = 'config.wx_external_config_id,config.app_id,config.app_name,config.app_desc,routes.wx_external_routes_id,routes.route_name,routes.route_info,routes.route_desc';

        $criteria = $criteria->andWhere($criteria->expr()->eq('config.company_id', $criteria->expr()->literal($params['company_id'])));

        if (isset($params['app_id'])) {
            $criteria = $criteria->andWhere($criteria->expr()->like('config.app_id', $criteria->expr()->literal($params['app_id'])));
        }
        if (isset($params['route_info'])) {
            $criteria = $criteria->andWhere($criteria->expr()->like('routes.route_info', $criteria->expr()->literal('%'.$params['route_info'].'%')));
        }
        $result['total_count'] = (int)$criteria->execute()->fetchColumn();

        if ($limit > 0) {
            $criteria->setFirstResult(($offset - 1) * $limit)->setMaxResults($limit);
        }

        $lists = $criteria->select($row)->execute()->fetchAll();
        $result['list'] = $lists;
        return $result;
    }


    /**
     * 删除外部小程序配置
     * @param  array $params
     * @param  integer $companyId
     * @return array
     */
    public function deleteWxExternalConfig($companyId, $wxExternalConfigId)
    {
        $filter = [
            'company_id' => $companyId,
            'wx_external_config_id' => $wxExternalConfigId,
        ];

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();

        try {
            $this->entityRepository->deleteBy($filter);
            $this->routesEntityRepository->deleteBy($filter);
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }

        return true;
    }
}
