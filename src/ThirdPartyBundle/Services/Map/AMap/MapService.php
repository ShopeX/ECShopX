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

namespace ThirdPartyBundle\Services\Map\AMap;

use ThirdPartyBundle\Data\MapData;
use ThirdPartyBundle\Services\Map\MapInterface;
use ThirdPartyBundle\Services\Request as BaseRequest;

/**
 * 高德地图
 * 错误code码查看地址：https://lbs.amap.com/api/webservice/guide/tools/info/
 * Class AMapService
 * @package ThirdPartyBundle\Services\Map
 */
class MapService extends BaseRequest implements MapInterface
{
    public function __construct()
    {
    }

    /**
     * 地理编码
     * https://lbs.amap.com/api/webservice/guide/api/georegeo#geo
     */
    public const URL_GEOCODE_GEO = "/v3/geocode/geo";

    /**
     * 逆地理编码
     * https://lbs.amap.com/api/webservice/guide/api/georegeo#regeo
     */
    public const URL_GEOCODE_REGEO = "/v3/geocode/regeo";

    /**
     * 根据详细的地址获取对应的经纬度
     * @param array $positionInfo 详细的地址信息
     * @return array 经纬度信息
     * [{"formatted_address":"上海市徐汇区宜山路|700号","country":"中国","province":"上海市","citycode":"021","city":"上海市","district":"徐汇区","township":[],"neighborhood":{"name":[],"type":[]},"building":{"name":[],"type":[]},"adcode":"310104","street":"宜山路","number":"700号","location":"121.417732,31.175441","level":"门牌号"}]
     */
    public function getLatAndLngByPosition(array $positionInfo): array
    {
        if (empty($positionInfo["address"])) {
            return [];
        }
        $data = [
            "key" => config("common.map.amap.app_key"),
            "address" => null, // 单个查询，如果需要批量查询，多个地址之间用 "|" 隔开，批量最多10个
            "batch" => false, // 单个查询，如果为true表示批量查询，值需要为字符串
            "city" => null, // 指定查询的城市，如果不指定默认是全国范围内查询
        ];

        if (is_array($positionInfo["address"])) {
            $data["address"] = implode("|", $positionInfo["address"]);
            $data["batch"] = "true";
        } else {
            $data["address"] = $positionInfo["address"];
            $data["batch"] = "false";
        }

        if (!empty($positionInfo["region"])) {
            $data["city"] = $positionInfo["region"];
        } else {
            unset($data["city"]);
        }

        $data["sig"] = $this->makeSign($data);
        $result = $this->setBaseUri(config("common.map.amap.baseuri"))
            ->setTimeout()
            ->setQuery($data)
            ->requestGet(self::URL_GEOCODE_GEO);

        if (empty($result["status"])) {
            $this->errorLog([
                "request" => ["data" => $data],
                "response" => $result
            ]);
            return [];
        }
        return (array)($result["geocodes"] ?? []);
    }

    /**
     * 根据经纬度来获取详细的地址信息
     * @param string $lat 经度
     * @param string $lng 纬度
     * @return array 地址信息
     * {"addressComponent":{"city":[],"province":"上海市","adcode":"310104","district":"徐汇区","towncode":"310104011000","streetNumber":{"number":"704-1号","location":"121.417647,31.175174","direction":"南","distance":"30.7959","street":"宜山路"},"country":"中国","township":"田林街道","businessAreas":[{"location":"121.416403,31.167910","name":"漕河泾","id":"310104"},{"location":"121.422417,31.170128","name":"田林","id":"310104"},{"location":"121.400383,31.174148","name":"虹梅路","id":"310104"}],"building":{"name":[],"type":[]},"neighborhood":{"name":[],"type":[]},"citycode":"021"},"formatted_address":"上海市徐汇区田林街道枫林科创园B1号楼"}
     */
    public function getPositionByLatAndLng(string $lat, string $lng): array
    {
        if ($lat === "" || $lng === "") {
            return [];
        }
        $data = [
            "key" => config("common.map.amap.app_key"),
            "location" => sprintf("%s,%s", $lng, $lat), // 单个查询，如果要批量查询，则在每个经纬度中间加入 "|"，批量最多20个
//            "extensions" => "all"
//            "radius" => "1000", // 搜索半径，（单位：米），默认是1000，范围为0到3000
//            "batch" => "false", // 不适用批量查询
        ];
        $data["sig"] = $this->makeSign($data);
        $result = $this->setBaseUri(config("common.map.amap.baseuri"))
            ->setTimeout()
            ->setQuery($data)
            ->requestGet(self::URL_GEOCODE_REGEO);

        if (empty($result["status"])) {
            $this->errorLog([
                "request" => ["data" => $data],
                "response" => $result
            ]);
            return [];
        }
        return (array)($result["regeocode"] ?? []);
    }

    /**
     * 数字签名的生成规则
     * https://lbs.amap.com/faq/quota-key/key/41181
     * @param array $data
     * @return string
     */
    protected function makeSign(array $data): string
    {
        ksort($data);
        // 拼接查询条件
        $queryString = '';
        foreach ($data as $key => $value) {
            $queryString .= sprintf('%s=%s&', $key, $value);
        }
        $queryString = trim($queryString, '&');
        return md5(sprintf("%s%s", $queryString, config("common.map.amap.app_secret")));
    }

    /**
     * 整理经纬度
     * @param array $dataFromGetLatAndLngByPosition
     * @return array|MapData[]
     */
    public function handleLatAndLngByPosition(array $dataFromGetLatAndLngByPosition): array
    {
        $result = [];
        foreach ($dataFromGetLatAndLngByPosition as $index => $item) {
            $result[$index] = new MapData();

            if (!empty($item["location"])) {
                [$lng, $lat] = explode(",", $item["location"]); // 高德地图是 经度，纬度
                $result[$index]->setLat($lat);
                $result[$index]->setLng($lng);
            }
        }

        return $result;
    }

    /**
     * 整理地址信息
     * @param array $dataFromGetPositionByLatAndLng
     * @return array|MapData[]
     */
    public function handlePositionByLatAndLng(array $dataFromGetPositionByLatAndLng): array
    {
        $item = new MapData();
        $item->setAddress($dataFromGetPositionByLatAndLng["formatted_address"] ?? null);

        $addressComponent = [
            "nation" => $dataFromGetPositionByLatAndLng["addressComponent"]["country"] ?? "",
            "province" => $dataFromGetPositionByLatAndLng["addressComponent"]["province"] ?? "",
            "city" => $dataFromGetPositionByLatAndLng["addressComponent"]["city"] ?? "",
            "district" => $dataFromGetPositionByLatAndLng["addressComponent"]["district"] ?? "",
            "street" => $dataFromGetPositionByLatAndLng["addressComponent"]["streetNumber"]["street"] ?? "",
            "street_number" => $dataFromGetPositionByLatAndLng["addressComponent"]["streetNumber"]["number"] ?? "",
        ];
        if (!empty($addressComponent["province"]) && empty($addressComponent["city"])) {
            $addressComponent["city"] = $addressComponent["province"];
        }
        $addressComponent["street_number"] = sprintf("%s%s", $addressComponent["street"], $addressComponent["street_number"]);
        $item->setAddressComponent($addressComponent);
        return [$item];
    }
}
