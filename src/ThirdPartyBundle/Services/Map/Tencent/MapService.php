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

namespace ThirdPartyBundle\Services\Map\Tencent;

use Dingo\Api\Exception\ResourceException;
use ThirdPartyBundle\Data\MapData;
use ThirdPartyBundle\Services\Map\MapInterface;
use ThirdPartyBundle\Services\Request as BaseRequest;

/**
 * 腾讯地图
 * Class TencentMapService
 * @package ThirdPartyBundle\Services\Map
 */
class MapService extends BaseRequest implements MapInterface
{
    public const URL_SUGGESTION = "/ws/place/v1/suggestion";

    public const URL_GEOCODER = "/ws/geocoder/v1";

    /**
     * 根据具体的地址来查询经纬度
     * https://lbs.qq.com/service/webService/webServiceGuide/webServiceGeocoder
     * @param array $positionInfo 详细地址的信息，
     *          【address 必须要携带市区（可以不需要省），可以将position方法中的$region和$keyword做拼接】
     *          【region 所在的城市, 可以不填】
     * @return array
     * {"title":"宜山路700号","location":{"lng":121.41778,"lat":31.17538},"ad_info":{"adcode":"310104"},"address_components":{"province":"上海市","city":"上海市","district":"徐汇区","street":"宜山路","street_number":"700"},"similarity":0.8,"deviation":1000,"reliability":7,"level":9}
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getLatAndLngByPosition(array $positionInfo): array
    {
        // ShopEx EcShopX Business Logic Layer
        if (empty($positionInfo["address"])) {
            return [];
        }

        $data = [
            "address" => str_replace(["\r\n", "\r", "\n", "null", ' '], "", $positionInfo["address"]), // 地址（注：地址中请包含城市名称，否则会影响解析效果），格式：address=北京市海淀区彩和坊路海淀西大街74号
            "key" => config("common.map.tencent.app_key", "")
        ];
        if (!empty($positionInfo["region"])) {
            $data["region"] = $positionInfo["region"]; // 地址所在城市（若地址中包含城市名称侧可不传）, 格式：region=北京
        }
        ksort($data);
        $data['sig'] = $this->makeSign(array_merge($data, ["url" => self::URL_GEOCODER]));

        $responseBodyContent = $this->setBaseUri(config("common.map.tencent.baseuri", ""))
            ->setTimeout()
            ->setQuery($data)
            ->requestGet(self::URL_GEOCODER);
        if (is_null($responseBodyContent) || (!isset($responseBodyContent["status"]) || $responseBodyContent["status"] != 0)) {
            $this->errorLog([
                "request" => ["data" => $data],
                "response" => $responseBodyContent
            ]);
            return ['status' => 0, 'message' => '', 'result' => []];
        }
        return (array)($responseBodyContent ?? []);
    }

    /**
     * 基于经纬度来定位当前位置
     * https://lbs.qq.com/service/webService/webServiceGuide/webServiceGcoder
     * @param string $lat 经度
     * @param string $lng 纬度
     * @return array 返回结果集
     * {"location":{"lat":39.984154,"lng":116.30749},"address":"北京市海淀区北四环西路66号","formatted_addresses":{"recommend":"海淀区中关村中国技术交易大厦(彩和坊路)","rough":"海淀区中关村中国技术交易大厦(彩和坊路)"},"address_component":{"nation":"中国","province":"北京市","city":"北京市","district":"海淀区","street":"北四环西路","street_number":"北四环西路66号"},"ad_info":{"nation_code":"156","adcode":"110108","city_code":"156110000","name":"中国,北京市,北京市,海淀区","location":{"lat":40.045132,"lng":116.375},"nation":"中国","province":"北京市","city":"北京市","district":"海淀区"},"address_reference":{"business_area":{"id":"14178584199053362783","title":"中关村","location":{"lat":39.980598,"lng":116.310997},"_distance":0,"_dir_desc":"内"},"famous_area":{"id":"14178584199053362783","title":"中关村","location":{"lat":39.980598,"lng":116.310997},"_distance":0,"_dir_desc":"内"},"crossroad":{"id":"529979","title":"海淀大街\/彩和坊路(路口)","location":{"lat":39.982498,"lng":116.30809},"_distance":185.8,"_dir_desc":"北"},"town":{"id":"110108012","title":"海淀街道","location":{"lat":39.990639,"lng":116.303162},"_distance":0,"_dir_desc":"内"},"street_number":{"id":"595672509379194165901290","title":"北四环西路66号","location":{"lat":39.984089,"lng":116.308037},"_distance":47.4,"_dir_desc":""},"street":{"id":"9217092216709107946","title":"彩和坊路","location":{"lat":39.980396,"lng":116.308205},"_distance":46.6,"_dir_desc":"西"},"landmark_l2":{"id":"3629720141162880123","title":"中国技术交易大厦","location":{"lat":39.984253,"lng":116.307472},"_distance":0,"_dir_desc":"内"}}}
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getPositionByLatAndLng(string $lat, string $lng): array
    {
        if (!is_numeric($lat) || !is_numeric($lng)) {
            throw new ResourceException("参数有误！经纬度不是数字！");
        }
        $data = [
            "location" => sprintf("%s,%s", $lat, $lng), // 经纬度（GCJ02坐标系），格式：location=lat<纬度>,lng<经度>
            "get_poi" => 0, // 是否返回周边地点（POI）列表, 例：【0 不返回(默认)】 【1 返回】
            "poi_options" => "address_format=short", // 控制返回场景，
            "key" => config("common.map.tencent.app_key", "")
        ];
        ksort($data);
        $data['sig'] = $this->makeSign(array_merge($data, ["url" => self::URL_GEOCODER]));

        $responseBodyContent = $this->setBaseUri(config("common.map.tencent.baseuri", ""))
            ->setTimeout()
            ->setQuery($data)
            ->requestGet(self::URL_GEOCODER);

        if (is_null($responseBodyContent) || (!isset($responseBodyContent["status"]) || $responseBodyContent["status"] != 0)) {
            $this->errorLog([
                "request" => ["data" => $data],
                "response" => $responseBodyContent
            ]);
            return [];
        }
        return (array)($responseBodyContent["result"] ?? []);
    }

    /**
     * 获取经纬度
     * @param string $lng 经度
     * @param string $lat 纬度
     * @param string $region 关键词
     * @param string $keyword 城市范围，如上海
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getLngAndLat(string &$lng, string &$lat, string $region, string $keyword)
    {
        $data = $this->getLatAndLngByPosition([
            "address" => sprintf("%s%s", $region, $keyword),
            "region" => $region
        ]);
        $lng = (string)($data["location"]["lng"] ?? "");
        $lat = (string)($data["location"]["lat"] ?? "");
//        $data = $this->position($region, $keyword);
//        $first = (array)array_shift($data);
//        $lng = (string)($first["location"]["lng"] ?? "");
//        $lat = (string)($first["location"]["lat"] ?? "");
    }

    /**
     * 根据地址获取经纬度信息.
     * https://lbs.qq.com/webservice_v1/guide-suggestion.html.
     * @param string $region 城市范围，如上海 （取city）
     * @param string $keyword 关键词
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function position(string $region, string $keyword): array
    {
        $data = [
            "keyword" => str_replace(["\r\n", "\r", "\n", "null", ' '], "", $keyword), // 关键词
            "region" => $region, // 城市范围，如上海
            "region_fix" => 1, // 固定在当前城市，0为如在当前城市找不到就扩大范围到全国匹配
            "policy" => 1, // 定位策略类型，[0: 常规策略] [1: 收货地址、上门服务地址]
            "key" => config("common.map.tencent.app_key", "")
        ];
        ksort($data);
        $data['sig'] = $this->makeSign(array_merge($data, ["url" => self::URL_SUGGESTION]));

        $responseBodyContent = $this->setBaseUri(config("common.map.tencent.baseuri", ""))
            ->setTimeout()
            ->setQuery($data)
            ->requestGet(self::URL_SUGGESTION);

        if (is_null($responseBodyContent) || (!isset($responseBodyContent["status"]) || $responseBodyContent["status"] != 0)) {
            return [];
        }
        return (array)($responseBodyContent["data"] ?? []);
    }

    /**
     * 生成签名
     * @param array $data
     * @return string
     */
    protected function makeSign(array $data): string
    {
        $url = (string)($data['url'] ?? '');

        unset($data['url']);

        ksort($data);

        // 拼接查询条件
        $queryString = '';
        foreach ($data as $key => $value) {
            $queryString .= sprintf('%s=%s&', $key, $value);
        }
        if (!empty($queryString)) {
            $queryString = sprintf('?%s', $queryString);
        }
        $queryString = trim($queryString, '&');

        return strtolower(md5(sprintf('%s%s%s', $url, $queryString, config("common.map.tencent.app_secret", ''))));
    }

    /**
     * 处理响应的内容
     * @param array|null $responseBodyContent
     * @return array|null
     */
    protected function handleResponseBodyContent(?array $responseBodyContent): ?array
    {
        if (is_null($responseBodyContent)) {
            return null;
        }
        if (!isset($responseBodyContent["status"]) || $responseBodyContent["status"] != 0) {
            return [];
        }
        return (array)($responseBodyContent["result"] ?? []);
    }

    /**
     * 整理经纬度
     * @param array $dataFromGetLatAndLngByPosition
     * @return array|MapData[]
     */
    public function handleLatAndLngByPosition(array $dataFromGetLatAndLngByPosition): array
    {
        $item = new MapData();
        $item->setLng($dataFromGetLatAndLngByPosition["location"]["lng"] ?? null);
        $item->setLat($dataFromGetLatAndLngByPosition["location"]["lat"] ?? null);

        return [$item];
    }

    /**
     * 整理地址信息
     * @param array $dataFromGetPositionByLatAndLng
     * @return array|MapData[]
     */
    public function handlePositionByLatAndLng(array $dataFromGetPositionByLatAndLng): array
    {
        $item = new MapData();
        $item->setAddress($dataFromGetPositionByLatAndLng["address"] ?? null);
        $item->setAddressComponent($dataFromGetPositionByLatAndLng["address_component"] ?? null);
        $item->setAddressComponent([
            "nation" => $dataFromGetPositionByLatAndLng["address_component"]["nation"] ?? "",
            "province" => $dataFromGetPositionByLatAndLng["address_component"]["province"] ?? "",
            "city" => $dataFromGetPositionByLatAndLng["address_component"]["city"] ?? "",
            "district" => $dataFromGetPositionByLatAndLng["address_component"]["district"] ?? "",
            "street" => $dataFromGetPositionByLatAndLng["address_component"]["street"] ?? "",
            "street_number" => $dataFromGetPositionByLatAndLng["address_component"]["street_number"] ?? "",
        ]);
        return [$item];
    }
}
