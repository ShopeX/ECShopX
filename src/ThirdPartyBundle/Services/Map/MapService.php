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
use EspierBundle\Services\Cache\RedisCacheService;
use ThirdPartyBundle\Data\MapData;
use ThirdPartyBundle\Entities\MapConfig;
use ThirdPartyBundle\Services\Map\AMap\MapService as AMapService;
use ThirdPartyBundle\Services\Map\Tencent\MapService as TencentMapService;

class MapService extends BaseService
{
    public function getEntityClass(): string
    {
        return MapConfig::class;
    }

    /**
     * 是否默认 - 默认
     */
    public const DEFAULT_YES = 1;

    /**
     * 是否默认 - 非默认
     */
    public const DEFAULT_NO = 0;

    /**
     * 地图类型 - 高德地图
     */
    public const TYPE_AMAP = "amap";

    /**
     * 地图类型 - 腾讯地图
     */
    public const TYPE_TENCENT = "tencent";

    /**
     * 获取实际业务逻辑的地图服务
     * @var MapInterface
     */
    protected $thirdPartyMapService;

    /**
     * @return MapInterface
     */
    public function getThirdPartyMapService(): MapInterface
    {
        return $this->thirdPartyMapService;
    }

    /**
     * @param MapInterface $thirdPartyMapService
     */
    public function setThirdPartyMapService(MapInterface $thirdPartyMapService): void
    {
        $this->thirdPartyMapService = $thirdPartyMapService;
    }

    /**
     * 数据库中查询出来的配置信息
     * @var array
     */
    protected $configInfo = [];

    /**
     * 获取配置信息
     * @return array
     */
    public function getConfigInfo(): array
    {
        return $this->configInfo;
    }

    /**
     * 设置配置信息
     * @param array $configInfo
     */
    public function setConfigInfo(array $configInfo): void
    {
        $this->configInfo = $configInfo;
    }

    /**
     * 实例对象
     * @var static
     */
    protected static $instance;

    /**
     * 生成对应地图类型的服务
     * @param int $companyId
     * @return static
     * @throws \Exception
     */
    final public static function make(int $companyId): self
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        // 获取默认的配置项
        $defaultInfo = self::$instance->getDefaultConfig($companyId);
        // 注册配置信息
        self::$instance->setConfigInfo($defaultInfo);

        switch ($defaultInfo["type"]) {
            case self::TYPE_AMAP:
                self::$instance->setThirdPartyMapService(new AMapService());
                config([
                    "common.map.amap.app_key" => $defaultInfo["app_key"] ?? "",
                    "common.map.amap.app_secret" => $defaultInfo["app_secret"] ?? "",
                ]);
                break;
            case self::TYPE_TENCENT:
                self::$instance->setThirdPartyMapService(new TencentMapService());
                config([
                    "common.map.tencent.app_key" => $defaultInfo["app_key"] ?? "",
                    "common.map.tencent.app_secret" => $defaultInfo["app_secret"] ?? "",
                ]);
                break;
            default:
                throw new \Exception("参数有误");
        }
        return self::$instance;
    }

    /**
     * 获取配置列表
     * @param int $companyId
     * @return array
     */
    public function getConfigList(int $companyId, array $filter = []): array
    {
        $filter["company_id"] = $companyId;
        $list = $this->getRepository()->lists($filter, "*", 1, -1, ["id" => "DESC"]);
        if (!empty($list["total_count"])) {
            return $list;
        }
        return [
            "total_count" => 1,
            "list" => [
                $this->getDefaultConfig($companyId)
            ]
        ];
    }

    /**
     * 设置配置项
     * @param array $filter
     * @param array $data
     * @return array
     */
    public function setConfig(int $companyId, string $mapType, array $data): array
    {
        try {
            $filter = ["company_id" => $companyId, "type" => $mapType];
            // 事务操作
            return $this->transaction(function () use ($filter, $data) {
                // 如果存在is_default，则需要确保配置项只有一个
                if (isset($data["is_default"])) {
                    if ($data["is_default"] == self::DEFAULT_YES) {
                        // 更新为 默认项 时，关闭其他类型的所有默认项
                        $this->getRepository()->updateBy([
                            "company_id" => $filter["company_id"],
                        ], [
                            "is_default" => self::DEFAULT_NO
                        ]);
                    } else {
                        // 更新为 非默认项 时，开启另外一个地图配置项
                        $this->getRepository()->updateBy([
                            "company_id" => $filter["company_id"],
                            "type" => $filter["type"] == self::TYPE_TENCENT ? self::TYPE_AMAP : self::TYPE_TENCENT
                        ], [
                            "is_default" => self::DEFAULT_YES
                        ]);
                    }
                }

                // 更新或新增
                if ($this->getRepository()->count($filter)) {
                    return $this->getRepository()->updateOneBy($filter, $data);
                } else {
                    return $this->getRepository()->create(array_merge($data, $filter));
                }
            });
        } catch (\Exception $exception) {
            app("log")->info(sprintf("%s_%s:%s", static::class, __METHOD__, jsonEncode([
                "message" => $exception->getMessage(),
                "file" => $exception->getFile(),
                "line" => $exception->getLine()
            ])));
            return [];
        }
    }

    /**
     * 获取默认的配置项
     * @param int $companyId
     * @return array
     */
    public function getDefaultConfig(int $companyId): array
    {
        return (new RedisCacheService($companyId, "map_default_info", 60))->getByPrevention(function () use ($companyId) {
            $defaultInfo = $this->getRepository()->getInfo(["company_id" => $companyId, "is_default" => self::DEFAULT_YES]);
            if (empty($defaultInfo)) {
                //app_key、app_secret为空，不插入，避免报错
                if (empty(config("common.map.amap.app_key", "")) || empty(config("common.map.amap.app_secret", ""))) {
                    return [];
                }
                $defaultInfo = $this->create([
                    "company_id" => $companyId,
                    "type" => self::TYPE_AMAP,
                    "app_key" => config("common.map.amap.app_key", ""),
                    "app_secret" => config("common.map.amap.app_secret", ""),
                    "is_default" => self::DEFAULT_YES,
                ]);
            }
            return $defaultInfo;
        });
    }

    /**
     * 获取经纬度
     * @param string $region 地址所在的城市
     * @param string $address 地址（需要包含城市名称）
     * @return MapData
     */
    public function getLatAndLng(string $region, string $address): MapData
    {
        $response = $this->thirdPartyMapService->getLatAndLngByPosition([
            "address" => empty($region) ? $address : sprintf("%s%s", $region, $address),
            "region" => $region
        ]);
        
        $result = $this->thirdPartyMapService->handleLatAndLngByPosition($response['result']);
        app("log")->info(sprintf("%s_%s:%s", static::class, __METHOD__, jsonEncode([
            "result" => $result
        ])));
        return array_first($result) ?: new MapData();
    }

    /**
     * 获取地址
     * @param string $lat 纬度
     * @param string $lng 经度
     * @return MapData
     */
    public function getPosition(string $lat, string $lng): MapData
    {
        $response = $this->thirdPartyMapService->getPositionByLatAndLng($lat, $lng);
        $result = $this->thirdPartyMapService->handlePositionByLatAndLng($response);
        return array_first($result) ?: new MapData();
    }
}
