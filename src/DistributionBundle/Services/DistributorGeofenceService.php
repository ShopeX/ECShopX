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

namespace DistributionBundle\Services;

use DistributionBundle\Entities\DistributorGeofence;
use EspierBundle\Services\BaseService;
use ThirdPartyBundle\Entities\MapConfig;
use ThirdPartyBundle\Entities\MapConfigService;
use ThirdPartyBundle\Services\Map\AMap\Track\GeofenceService;
use ThirdPartyBundle\Services\Map\AMap\Track\GeofenceService as AMapTrackGeofenceService;
use ThirdPartyBundle\Services\Map\MapService;
use ThirdPartyBundle\Services\Map\MapServiceService;

class DistributorGeofenceService extends BaseService
{
    public function getEntityClass(): string
    {
        return DistributorGeofence::class;
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
     * 更新围栏，如果存在则覆盖更新，如果不存在则创建
     * @param array $createData 创建的数据
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function save(int $companyId, string $distributorId, array $createData): array
    {
        $distributorInfo = (new DistributorService())->getInfoSimple([
            "company_id" => $companyId,
            "distributor_id" => $distributorId
        ]);
        if (empty($distributorInfo)) {
            throw new \Exception("店铺不存在！");
        }
        if (empty($createData["data"]) || !is_array($createData["data"])) {
            throw new \Exception("围栏信息有误！");
        }

        if (count($createData["data"]) < 3) {
            throw new \Exception("围栏顶点个数不能少于3个！");
        }

        // 获取地图配置信息
        $configInfo = MapService::make($companyId)->getConfigInfo();
        if (empty($configInfo)) {
            throw new \Exception("地图信息有误！");
        }
        $mapServiceService = new MapServiceService();
        // 获取服务信息
        $serviceInfo = $mapServiceService->initDistributorService($companyId);
        if (empty($serviceInfo["id"])) {
            throw new \Exception("服务初始化失败！");
        }

        // 定义围栏信息
        $geofenceInfo = [
            "geofence_id" => null, // 围栏id
            "type" => $createData["type"] ?? GeofenceService::TYPE_POLYGON, // 围栏类型
            "name" => sprintf("geofence_distributor_%s_%d%d", $distributorId, time(), rand(0, 999)), // 围栏名称
            "params" => [] // 围栏参数
        ];

        switch ($geofenceInfo["type"]) {
            case GeofenceService::TYPE_POLYGON:
                // 定义多边形顶点坐标
                $points = "";
                foreach ($createData["data"] as $geofenceItem) {
                    if (empty($geofenceItem["lng"]) || empty($geofenceItem["lat"])) {
                        throw new \Exception("围栏的经纬度不能为空！");
                    }
                    $points .= sprintf("%s,%s;", $geofenceItem["lng"], $geofenceItem["lat"]);
                }
                $geofenceInfo["params"] = ["points" => trim($points, ";")];
                break;
            default:
                throw new \Exception("围栏类型有误！");
        }

        // 根据id获取到店铺围栏信息
        if (isset($createData["id"])) {
            $distributorGeofenceInfo = $this->find([
                "id" => $createData["id"],
                "company_id" => $companyId,
                "distributor_id" => $distributorId
            ]);
            if (empty($distributorGeofenceInfo)) {
                throw new \Exception("该围栏信息不属于当前店铺！");
            }
            $geofenceInfo["name"] = $distributorGeofenceInfo["geofence_data"]["name"] ?? null;
            $geofenceInfo["geofence_id"] = $distributorGeofenceInfo["geofence_id"];

            // 如果定位信息没有发生变化，则不做更新，避免调用第三方接口
            if (!$this->checkRequestToUpdate($distributorGeofenceInfo["geofence_data"] ?? [], $geofenceInfo)) {
                return $distributorGeofenceInfo;
            }
            $geofenceResult = $mapServiceService->saveGeofenceFromThirdParty($configInfo, $serviceInfo, $geofenceInfo, true);
            $geofenceInfo["geofence_id"] = $geofenceResult["geofence_id"];
            // 更新
            $distributorGeofenceResult = parent::updateDetail([
                "id" => $createData["id"],
                "company_id" => $companyId,
                "distributor_id" => $distributorId
            ], [
                "geofence_id" => $geofenceResult["geofence_id"],
                "geofence_data" => jsonEncode($geofenceInfo),
                "status" => self::STATUS_ENABLE
            ]);
        } else {
            $geofenceResult = $mapServiceService->saveGeofenceFromThirdParty($configInfo, $serviceInfo, $geofenceInfo, false);
            $geofenceInfo["geofence_id"] = $geofenceResult["geofence_id"];
            // 创建
            $distributorGeofenceResult = parent::create([
                "company_id" => $companyId,
                "distributor_id" => $distributorId,
                "config_service_local_id" => $serviceInfo["id"],
                "geofence_id" => $geofenceResult["geofence_id"],
                "geofence_data" => jsonEncode($geofenceInfo),
                "status" => self::STATUS_ENABLE
            ]);
        }

        return $this->handleData($distributorGeofenceResult);
    }

    /**
     * 检查是否需要更新
     * @param array $dbInfo
     * @param array $requestInfo
     * @return bool 【true 需要去请求第三方做覆盖更新】【false 不需要做更新】
     */
    protected function checkRequestToUpdate(array $dbInfo, array $requestInfo): bool
    {
        // Hash: 0d723eca
        return !(jsonEncode($dbInfo) == jsonEncode($requestInfo));
    }

    /**
     * 查询数据
     * @param array $filter
     * @return array
     */
    public function find(array $filter): array
    {
        return $this->handleData(parent::find($filter));
    }

    /**
     * 根据店铺id来删除店铺的围栏信息
     * @param int $companyId 公司id
     * @param int $distributorId 店铺id
     * @param int|null $distributorGeofenceId 店铺围栏的主键id，如果为null则删除店铺下的所有围栏
     * @return int
     * @throws \Throwable
     */
    public function deleteByDistributorId(int $companyId, int $distributorId, ?int $distributorGeofenceId): int
    {
        // 获取店铺围栏信息
        $filter = [
            "company_id" => $companyId,
            "distributor_id" => $distributorId,
        ];
        if (!is_null($distributorGeofenceId)) {
            $filter["id"] = $distributorGeofenceId;
        }

        $data = $this->listsWithJoin($filter, [], [], ["*"], ["service_id"], ["app_key", "type as config_type"]);
        if (empty($data["list"]) || !is_array($data["list"])) {
            return 1;
        }

        // 批量事物操作
        foreach ($data["list"] as $datum) {
            $this->transaction(function () use ($datum) {
                // 获取主键id
                $distributorGeofenceId = $datum["id"] ?? null;
                // 获取公司id
                $companyId = $datum["company_id"] ?? null;
                // 第三方平台的围栏id
                $geofenceId = $datum["geofence_id"] ?? null;
                // 获取第三方平台的服务id
                $serviceId = $datum["service_id"] ?? null;
                // 获取第三方平台的key
                $appKey = $datum["app_key"] ?? null;
                // 获取地图配置的类型
                $configType = $datum["config_type"] ?? null;
                // 先删除DB数据
                $row = parent::delete([
                    "id" => $distributorGeofenceId,
                    "company_id" => $companyId
                ]);
                // 再删除第三方的服务数据
                if (!$row || empty($geofenceId)) {
                    return [];
                }

                switch ($configType) {
                    case MapService::TYPE_AMAP:
                        $aMapTrackGeofenceService = new AMapTrackGeofenceService($appKey, $serviceId);
                        $result = $aMapTrackGeofenceService->delete($geofenceId);
                        if (empty($result["gfids"])) {
                            throw new \Exception("删除围栏失败！");
                        }
                        break;
                }
                return [];
            });
        }
        return 1;
    }

    /**
     * 查询信息查询（携带join）
     * @param array $distributorGeofenceFilter 店铺围栏的过滤条件
     * @param array $mapConfigServiceFilter 地图配置服务的过滤条件
     * @param array $mapConfigFilter 地图配置的过滤条件
     * @param array $distributorGeofenceSelect 店铺围栏的查询字段
     * @param array $mapConfigServiceSelect 地图配置服务的查询字段
     * @param array $mapConfigSelect 地图配置的查询字段
     * @param int $page 当前页
     * @param int $pageSize 每页数量
     * @param array $orderBy 排序方式
     * @return array
     */
    public function listsWithJoin(
        array $distributorGeofenceFilter = [],
        array $mapConfigServiceFilter = [],
        array $mapConfigFilter = [],
        array $distributorGeofenceSelect = ["*"],
        array $mapConfigServiceSelect = ["*"],
        array $mapConfigSelect = ["*"],
        int   $page = 0,
        int $pageSize = 0,
        array $orderBy = [],
        bool $needCount = true
    ): array
    {
        // 获取表名
        $distributorGeofenceTable = $this->getRepository()->table;
        $configTable = $this->getRepository(MapConfig::class)->table;
        $serviceTable = $this->getRepository(MapConfigService::class)->table;

        // 设置过滤条件
        $filter = [];
        foreach ($distributorGeofenceFilter as $column => $value) {
            $filter[sprintf("%s.%s", $distributorGeofenceTable, $column)] = $value;
        }
        foreach ($mapConfigServiceFilter as $column => $value) {
            $filter[sprintf("%s.%s", $serviceTable, $column)] = $value;
        }
        foreach ($mapConfigFilter as $column => $value) {
            $filter[sprintf("%s.%s", $configTable, $column)] = $value;
        }
        // 设置查询字段
        $select = "";
        foreach ($distributorGeofenceSelect as $column) {
            $select .= sprintf("%s.%s,", $distributorGeofenceTable, $column);
        }
        foreach ($mapConfigServiceSelect as $column) {
            $select .= sprintf("%s.%s,", $serviceTable, $column);
        }
        foreach ($mapConfigSelect as $column) {
            $select .= sprintf("%s.%s,", $configTable, $column);
        }

        // 列表查询
        return $this->getRepository()->listsWithJoin($filter, trim($select, ","), $page, $pageSize, $orderBy, $needCount);
    }

    /**
     * 处理返回的数据
     * @param array $data
     * @return array
     */
    public function handleData(array $data): array
    {
        if (isset($data["geofence_data"])) {
            $data["geofence_data"] = (array)jsonDecode($data["geofence_data"] ?? null);
        }

        // 将围栏信息整理成可供前端处理的数据格式
        if (isset($data["geofence_data"]) && isset($data["config_type"])) {
            $data["geofence_list"] = [];
            switch ($data["config_type"]) {
                case MapService::TYPE_AMAP:
                    // 获取围栏类型
                    $geofenceType = $data["geofence_data"]["type"] ?? null;
                    switch ($geofenceType) {
                        case GeofenceService::TYPE_POLYGON:
                            $points = explode(";", $data["geofence_data"]["params"]["points"] ?? "");
                            array_walk($points, function (&$value) {
                                $lngAndLat = explode(",", $value);
                                $value = [
                                    "lng" => array_shift($lngAndLat),
                                    "lat" => array_shift($lngAndLat)
                                ];
                            });
                            $data["geofence_list"] = $points;
                            break;
                    }
            }
        }
        return $data;
    }

    /**
     * 判断当前的坐标是否在围栏内
     * @param int $companyId 公司id
     * @param array $distributorId 多个门店id
     * @param array $orderData 订单信息
     * @return bool 【true 在围栏内】【false 不在围栏内】
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function inRange(int $companyId, array $distributorId, array $orderData): bool
    {
        // 获取配置信息
        $configInfo = MapService::make($companyId)->getConfigInfo();
        if (empty($configInfo["id"]) || empty($configInfo["type"]) || empty($configInfo["app_key"])) {
            throw new \Exception("不存在地图配置信息");
        }

        // 获取配置服务信息
        $serviceData = (new MapServiceService())->list([
            "company_id" => $companyId,
            "config_id" => $configInfo["id"],
            "type" => MapServiceService::TYPE_DISTRIBUTOR,
            "status" => MapServiceService::STATUS_ENABLE
        ], 0, 0, [], "id,service_id", false);
        if (empty($serviceData["list"])) {
            throw new \Exception("地图服务为空！");
        }

        // 获取所有的服务本地id
        $serviceArray = array_column($serviceData["list"], "service_id", "id");

        // 获取店铺围栏
        $distributorGeofenceData = $this->list([
            "company_id" => $companyId,
            "distributor_id" => $distributorId,
            "config_service_local_id" => array_keys($serviceArray),
            "status" => self::STATUS_ENABLE
        ], 0, 0, ["id" => "DESC"], "config_service_local_id,geofence_id", false);
        if (empty($distributorGeofenceData["list"])) {
            throw new \Exception("店铺围栏为空！");
        }

        $requestData = [];
        foreach ($distributorGeofenceData["list"] as $distributorGeofenceDatum) {
            // 获取本地的服务id
            $serviceLocalId = $distributorGeofenceDatum["config_service_local_id"] ?? null;
            // 获取第三方的服务id
            $serviceId = $serviceArray[$serviceLocalId] ?? null;
            // 获取第三方的围栏id
            $requestData[$serviceId][] = $distributorGeofenceDatum["geofence_id"] ?? null;
        }

        if (empty($orderData['receiver_city']) || empty($orderData['receiver_address'])) {
            return false;
        }

        // 定位
        $mapData = MapService::make($companyId)->getLatAndLng((string)$orderData["receiver_city"], fixeddecrypt((string)$orderData["receiver_address"]));

        if (empty($mapData->getLng()) || empty($mapData->getLat())) {
            throw new \Exception("经纬度不存在！");
        }

        // 获取当前的坐标 （经度, 纬度）
        $location = sprintf("%s,%s", $mapData->getLng(), $mapData->getLat());

        // 遍历数据并去第三方平台查询服务
        foreach ($requestData as $serviceId => $geofenceIdArray) {
            // 最大支持100个围栏id作为一次查询的条件
            $geofenceIdChunkArray = array_chunk($geofenceIdArray, 100);
            // 根据地图配置信息去不同的服务商里判断围栏
            switch ($configInfo["type"]) {
                // 高德地图服务
                case MapService::TYPE_AMAP:
                    // 获取高德地图的围栏服务
                    $geofenceService = new GeofenceService($configInfo["app_key"], $serviceId);
                    foreach ($geofenceIdChunkArray as $geofenceIds) {
                        $result = $geofenceService->check(["location" => $location, "gfids" => $geofenceIds]);
                        // 如果没有数据返回，可能接口报错了
                        if (!isset($result["results"]) || !is_array($result["results"])) {
                            throw new \Exception("围栏API请求有误！");
                        }
                        // 匹配到了直接返回
                        foreach ($result["results"] as $item) {
                            if (!empty($item["in"]) && $item["in"] == 1) {
                                return true;
                            }
                        }
                    }
                    // no break
                default:
                    throw new \Exception("操作有误！");
            }
        }
        return false;
    }
}
