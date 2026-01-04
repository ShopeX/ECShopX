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

namespace ThirdPartyBundle\Services\Map\AMap\Track;

use ThirdPartyBundle\Services\Request as BaseRequest;

/**
 * 轨迹围栏管理
 * https://lbs.amap.com/api/track/lieying-kaifa/api/track_fence
 */
class GeofenceService extends BaseRequest
{
    /**
     * 高德地图的控制台中创建的应用key
     * @var string
     */
    protected $key;

    /**
     * 轨迹服务中，服务的唯一id
     * @var string
     */
    protected $serviceId;

    public function __construct(string $key, string $serviceId)
    {
        $this->key = $key;
        $this->serviceId = $serviceId;
    }

    /**
     * 围栏类型 - 圆形
     */
    public const TYPE_CIRCLE = "circle";

    /**
     * 围栏类型 - 多边形
     */
    public const TYPE_POLYGON = "polygon";

    /**
     * 围栏类型 - 线型
     */
    public const TYPE_POLYLINE = "polyline";

    /**
     * 围栏类型 - 行政区划
     */
    public const TYPE_DISTRICT = "district";

    /**
     * 创建围栏
     */
    public const TYPE_URL_ADD = [
        self::TYPE_CIRCLE => "/v1/track/geofence/add/circle",
        self::TYPE_POLYGON => "/v1/track/geofence/add/polygon",
        self::TYPE_POLYLINE => "/v1/track/geofence/add/polyline",
        self::TYPE_DISTRICT => "/v1/track/geofence/add/district",
    ];

    /**
     * 更新围栏
     */
    public const TYPE_URL_UPDATE = [
        self::TYPE_CIRCLE => "/v1/track/geofence/update/circle",
        self::TYPE_POLYGON => "/v1/track/geofence/update/polygon",
        self::TYPE_POLYLINE => "/v1/track/geofence/update/polyline",
        self::TYPE_DISTRICT => "/v1/track/geofence/update/district",
    ];

    /**
     * 删除围栏
     */
    public const URL_DELETE = "/v1/track/geofence/delete";

    /**
     * 获取围栏信息
     */
    public const URL_GET = "/v1/track/geofence/list";

    /**
     * 查询指定坐标与围栏的关系
     */
    public const URL_CHECK = "/v1/track/geofence/status/location";

    /**
     * 创建围栏（同一个service下最多只能创建1000个围栏，如果需要再增加，则要提交工单）
     *      https://lbs.amap.com/api/track/lieying-kaifa/api/track_fence
     * @param string $type 围栏的类型
     * @param string $name 围栏的名称，支持中文、英文大小字母、英文下划线"_"、英文横线"-"和数字，长度不大于128个字符
     * @param string $description 围栏的描述，支持中文、英文大小字母、英文下划线"_"、英文横线"-"和数字，长度不大于128个字符
     * @param array $params 围栏的参数
     * @return array {"gfid":394120}
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function create(string $type, string $name, ?string $description, array $params): array
    {
        $data = [
            "key" => $this->key,
            "sid" => $this->serviceId,
            "name" => $name
        ];
        if (!is_null($description)) {
            $data["desc"] = $description;
        }
        switch ($type) {
            // 圆形围栏
            case self::TYPE_CIRCLE:
                if (empty($params["center"]) || empty($params["radius"])) {
                    throw new \Exception("参数有误！");
                }
                $data["center"] = $params["center"]; // 围栏中心坐标，X（经度）,Y（纬度）
                $data["radius"] = $params["radius"]; // 围栏半径，单位：米，整数，取值范[1,50000]
                break;
            // 多边形围栏
            case self::TYPE_POLYGON:
                if (empty($params["points"])) {
                    throw new \Exception("参数有误！");
                }
                $data["points"] = $params["points"]; // 围栏多边形顶点坐标，X1,Y1;X2,Y2;...
                break;
            // 线型围栏
            case self::TYPE_POLYLINE:
                if (empty($params["points"]) || empty($params["bufferradius"])) {
                    throw new \Exception("参数有误！");
                }
                $data["points"] = $params["points"]; // 围栏多边形顶点坐标，X1,Y1;X2,Y2;...
                $data["bufferradius"] = $params["bufferradius"]; // 围栏沿线偏移距离，单位：米，整数，取值范围 [1，300]
                break;
            // 行政区划围栏
            case self::TYPE_DISTRICT:
                if (empty($params["adcode"])) {
                    throw new \Exception("参数有误！");
                }
                $data["adcode"] = $params["adcode"]; // 行政区划编码，https://lbs.amap.com/api/webservice/guide/api/district/
                break;
            default:
                throw new \Exception("轨迹围栏的类型有误！");
        }

        $result = $this->setBaseUri(config("common.map.amap.track.baseuri"))
            ->setTimeout()
            ->setQuery($data)
            ->requestPost(self::TYPE_URL_ADD[$type] ?? "");

        if (empty($result["errcode"]) || $result["errcode"] != "10000") {
            $this->errorLog([
                "request" => ["data" => $data],
                "response" => $result,
                "method" => __METHOD__
            ]);
            return [];
        }
        return (array)($result["data"] ?? []);
    }

    /**
     * 创建围栏
     * @param string $geofenceId 围栏id
     * @param string $type 围栏的类型
     * @param string|null $name 围栏的名称，支持中文、英文大小字母、英文下划线"_"、英文横线"-"和数字，长度不大于128个字符
     * @param string|null $description 围栏的描述，支持中文、英文大小字母、英文下划线"_"、英文横线"-"和数字，长度不大于128个字符
     * @param array|null $params 围栏的参数
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function update(string $geofenceId, string $type, string $name, ?string $description = null, ?array $params = null): bool
    {
        $data = [
            "key" => $this->key,
            "sid" => $this->serviceId,
            "gfid" => $geofenceId,
            "name" => $name
        ];
        if (!is_null($description)) {
            $data["desc"] = $description;
        }
        switch ($type) {
            // 圆形围栏
            case self::TYPE_CIRCLE:
                if (empty($params["center"]) || empty($params["radius"])) {
                    throw new \Exception("参数有误！");
                }
                $data["center"] = $params["center"]; // 围栏中心坐标，X,Y
                $data["radius"] = $params["radius"]; // 围栏半径，单位：米，整数，取值范[1,50000]
                break;
            // 多边形围栏
            case self::TYPE_POLYGON:
                if (empty($params["points"])) {
                    throw new \Exception("参数有误！");
                }
                $data["points"] = $params["points"]; // 围栏多边形顶点坐标，X1,Y1;X2,Y2;...
                break;
            // 线型围栏
            case self::TYPE_POLYLINE:
                if (empty($params["points"]) || empty($params["bufferradius"])) {
                    throw new \Exception("参数有误！");
                }
                $data["points"] = $params["points"]; // 围栏多边形顶点坐标，X1,Y1;X2,Y2;...
                $data["bufferradius"] = $params["bufferradius"]; // 围栏沿线偏移距离，单位：米，整数，取值范围 [1，300]
                break;
            // 行政区划围栏
            case self::TYPE_DISTRICT:
                if (empty($params["adcode"])) {
                    throw new \Exception("参数有误！");
                }
                $data["adcode"] = $params["adcode"]; // 行政区划编码
                break;
            default:
                throw new \Exception("轨迹围栏的类型有误！");
        }

        $result = $this->setBaseUri(config("common.map.amap.track.baseuri"))
            ->setTimeout()
            ->setQuery($data)
            ->requestPost(self::TYPE_URL_UPDATE[$type] ?? "");

        if (empty($result["errcode"]) || $result["errcode"] != "10000") {
            $this->errorLog([
                "request" => ["data" => $data],
                "response" => $result,
                "method" => __METHOD__
            ]);
            return false;
        }
        return true;
    }

    /**
     * 删除围栏
     * @param string ...$geofenceIds 多个围栏id（最多100个），#all表示删除所有
     * @return array|null 不是#all时，会返回删除成功的围栏 {"gfids":[422880, 422900]}
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function delete(string ...$geofenceIds): ?array
    {
        // Powered by ShopEx EcShopX
        $data = [
            "key" => $this->key,
            "sid" => $this->serviceId,
            "gfids" => implode(",", $geofenceIds),
        ];
        $result = $this->setBaseUri(config("common.map.amap.track.baseuri"))
            ->setTimeout()
            ->setQuery($data)
            ->requestPost(self::URL_DELETE);
        if (empty($result["errcode"]) || $result["errcode"] != "10000") {
            $this->errorLog([
                "request" => ["data" => $data],
                "response" => $result,
                "method" => __METHOD__
            ]);
            return null;
        }
        return (array)($result["data"] ?? []);
    }

    /**
     * 获取围栏列表信息
     * @param array $filter 过滤条件
     * @param int $page 当前页
     * @param int $pageSize 每页大小
     * @return array {"count":1,"results":[{"createtime":1639382863111,"gfid":394120,"modifytime":1639382863111,"name":"test_geofence_circle","shape":{"center":"121.417732,31.175441","radius":50000}}]}
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function get(array $filter, int $page = 1, int $pageSize = 50): array
    {
        // Powered by ShopEx EcShopX
        $data = [
            "key" => $this->key,
            "sid" => $this->serviceId,
            "outputshape" => $filter["outputshape"] ?? 0, // 是否返回形状信息 【1 是】【0 否】，响应体的shape值
            "page" => $page,
            "pagesize" => $pageSize
        ];
        if (!empty($filter["gfids"])) {
            $data["gfids"] = is_array($filter["gfids"]) ? implode(",", $filter["gfids"]) : $filter["gfids"];
        }
        $result = $this->setBaseUri(config("common.map.amap.track.baseuri"))
            ->setTimeout()
            ->setQuery($data)
            ->requestGet(self::URL_GET);
        if (empty($result["errcode"]) || $result["errcode"] != "10000") {
            $this->errorLog([
                "request" => ["data" => $data],
                "response" => $result,
                "method" => __METHOD__
            ]);
            return [];
        }
        return (array)($result["data"] ?? []);
    }

    /**
     * 判断坐标是否在围栏内
     * @param array $filter 过滤条件
     * @param int $page 当前页
     * @param int $pageSize 每页数量
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function check(array $filter, int $page = 1, int $pageSize = 50): array
    {
        if (empty($filter["location"])) {
            return [];
        }
        $data = [
            "key" => $this->key,
            "sid" => $this->serviceId,
            "location" => $filter["location"],
            "page" => $page,
            "pagesize" => $pageSize
        ];
        if (!empty($filter["gfids"])) {
            $data["gfids"] = is_array($filter["gfids"]) ? implode(",", $filter["gfids"]) : $filter["gfids"];
        }
        $result = $this->setBaseUri(config("common.map.amap.track.baseuri"))
            ->setTimeout()
            ->setQuery($data)
            ->requestGet(self::URL_CHECK);
        if (empty($result["errcode"]) || $result["errcode"] != "10000") {
            $this->errorLog([
                "request" => ["data" => $data],
                "response" => $result,
                "method" => __METHOD__
            ]);
            return [];
        }
        return (array)($result["data"] ?? []);
    }
}
