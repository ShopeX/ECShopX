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

use CompanysBundle\Entities\WxExternalRoutes;
use Dingo\Api\Exception\ResourceException;
use CompanysBundle\Entities\WxExternalConfig;

class WxExternalRoutesService
{
    private $entityRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(WxExternalRoutes::class);
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
     * 获取外部小程序路径列表
     * @param  array $filter
     * @param  string $cols
     * @param  integer $page
     * @param  integer $page_size
     * @return array
     */
    public function getWxExternalRoutesList($filter, $cols = '*', $page = 1, $page_size = 10)
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
     * 创建外部小程序路径
     * @param  array $params
     * @param  integer $companyId
     * @return array
     */
    public function createWxExternalRoutes($companyId, $params)
    {
        if (!isset($params['wx_external_config_id']) || empty($params['wx_external_config_id'])) {
            throw new ResourceException("外部小程序配置ID错误");
        }
        $configInfo = app('registry')->getManager('default')->getRepository(WxExternalConfig::class)->getInfoById($params['wx_external_config_id']);
        if (empty($configInfo)) {
            throw new ResourceException("外部小程序配置不存在");
        }
        $info = $this->entityRepository->getInfo(['wx_external_config_id' => $params['wx_external_config_id'], 'route_info' => $params['route_info']]);
        if ($info) {
            throw new ResourceException("路径已存在");
        }
        $data = [
            'company_id' => $companyId,
            'wx_external_config_id' => $params['wx_external_config_id'],
            'route_name' => $params['route_name'],
            'route_info' => $params['route_info'],
            'route_desc' => isset($params['route_desc']) ? $params['route_desc'] : '',
        ];
        return $this->entityRepository->create($data);
    }

    /**
     * 修改外部小程序路径
     * @param  array $params
     * @param  integer $companyId
     * @return array
     */
    public function updateWxExternalRoutes($companyId, $wx_external_routes_id, $params)
    {
        $filter = [
            'company_id' => $companyId,
            'wx_external_routes_id' => $wx_external_routes_id,
        ];
        $info = $this->entityRepository->getInfo(['route_info' => $params['route_info'], 'wx_external_config_id' => $params['wx_external_config_id']]);
        if ($info && $info['wx_external_routes_id'] != $wx_external_routes_id) {
            throw new ResourceException("路径已存在");
        }
        $data = [
            'route_name' => $params['route_name'],
            'route_info' => $params['route_info'],
        ];
        if (isset($params['route_desc'])) {
            $data['route_desc'] = $params['route_desc'];
        }
        return $this->entityRepository->updateOneBy($filter, $data);
    }


    /**
     * 删除外部小程序路径
     * @param  array $params
     * @param  integer $companyId
     * @return array
     */
    public function deleteWxExternalRoutes($companyId, $wxExternalRoutesId)
    {
        $filter = [
            'company_id' => $companyId,
            'wx_external_routes_id' => $wxExternalRoutesId,
        ];

        $this->entityRepository->deleteBy($filter);

        return true;
    }
}
