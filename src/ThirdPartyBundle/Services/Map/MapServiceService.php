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

namespace ThirdPartyBundle\Services\Map;

use EspierBundle\Services\BaseService;
use ThirdPartyBundle\Entities\MapConfigService;
use ThirdPartyBundle\Services\Map\AMap\Track\GeofenceService as AMapTrackGeofenceService;
use ThirdPartyBundle\Services\Map\AMap\Track\ServiceService as AMapTrackServiceService;

/**
 * 地图配置下的服务
 */
class MapServiceService extends BaseService
{
    public function getEntityClass(): string
    {
        return MapConfigService::class;
    }

    /**
     * 状态 - 启用
     */
    public const STATUS_ENABLE = 1;

    /**
     * 状态 - 禁用
     */
    public const STATUS_DISABLE = 0;

    /**
     * 服务类型 - 店铺服务
     */
    public const TYPE_DISTRIBUTOR = 11;

    /**
     * 初始化店铺服务
     * @param int $companyId
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function initDistributorService(int $companyId): array
    {
        return $this->initService(MapService::make($companyId)->getConfigInfo(), self::TYPE_DISTRIBUTOR, "service_distributor");
    }

    /**
     * 初始化服务
     * @param array $configInfo 地图配置信息
     * @param int $serviceType 服务类型
     * @param string $serviceName 服务名称
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function initService(array $configInfo, int $serviceType, string $serviceName): array
    {
        if (!isset($configInfo["company_id"], $configInfo["id"], $configInfo["app_key"], $configInfo["type"])) {
            throw new \Exception("地图配置信息有误！");
        }

        // 获取服务信息
        $filter = [
            "company_id" => $configInfo["company_id"],
            "config_id" => $configInfo["id"],
            "type" => $serviceType,
        ];
        if ($info = $this->find($filter)) {
            return $info;
        }

        switch ($configInfo["type"]) {
            // 高德地图
            case MapService::TYPE_AMAP:
                $mapTrackServiceService = new AMapTrackServiceService($configInfo["app_key"]);
                $result = $mapTrackServiceService->create($serviceName, "");
                if (empty($result)) {
                    throw new \Exception("创建失败！");
                }
                $serviceId = (string)($result["sid"] ?? "");
                $serviceData = [
                    "sid" => $serviceId,
                    "name" => $result["name"] ?? "",
                    "description" => ""
                ];
                break;
            default:
                return [];
        }

        return parent::create(array_merge($filter, [
            "service_id" => $serviceId,
            "service_data" => (string)jsonEncode($serviceData),
            "status" => self::STATUS_ENABLE
        ]));
    }

    /**
     * 从第三方平台获取围栏信息
     * @param array $configInfo 地图配置信息
     * @param array $configServiceInfo 地图配置的服务信息
     * @param array $geofenceInfo 需要传递给第三方平台的围栏数据
     * @param bool $isUpdate 是否是更新操作 【true 更新】【false 创建】
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function saveGeofenceFromThirdParty(array $configInfo, array $configServiceInfo, array $geofenceInfo, bool $isUpdate = false): array
    {
        switch ($configInfo["type"]) {
            // 高德地图
            case MapService::TYPE_AMAP:
                $aMapTrackGeofenceService = new AMapTrackGeofenceService($configInfo["app_key"], $configServiceInfo["service_id"]);
                if ($isUpdate) {
                    $bool = $aMapTrackGeofenceService->update(
                        $geofenceInfo["geofence_id"] ?? null,
                        $geofenceInfo["type"] ?? null,
                        $geofenceInfo["name"] ?? null,
                        $geofenceInfo["description"] ?? null,
                        $geofenceInfo["params"] ?? null,
                    );
                    if (!$bool) {
                        throw new \Exception("更新围栏失败！");
                    }
                    $geofenceId = $geofenceInfo["geofence_id"] ?? null;
                } else {
                    $result = $aMapTrackGeofenceService->create(
                        $geofenceInfo["type"] ?? null,
                        $geofenceInfo["name"] ?? null,
                        $geofenceInfo["description"] ?? null,
                        $geofenceInfo["params"] ?? null,
                    );
                    if (empty($result)) {
                        throw new \Exception("创建围栏失败！");
                    }
                    $geofenceId = $result["gfid"];
                }
                break;
            default:
                $geofenceId = "";
                break;
        }

        return [
            "geofence_id" => $geofenceId,
        ];
    }
}
