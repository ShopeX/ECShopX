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
 * 轨迹的服务管理
 * https://lbs.amap.com/api/track/lieying-kaifa/api/service
 */
class ServiceService extends BaseRequest
{
    /**
     * 高德地图的控制台中创建的应用key
     * @var string
     */
    protected $key;

    public function __construct(string $key)
    {
        $this->key = $key;
    }

    /**
     * 增加service
     */
    public const URL_ADD = "/v1/track/service/add";

    /**
     * 删除service
     */
    public const URL_DELETE = "/v1/track/service/delete";

    /**
     * 修改service
     */
    public const URL_UPDATE = "/v1/track/service/update";

    /**
     * 查询service
     */
    public const URL_GET = "/v1/track/service/list";

    /**
     * 查询出这个key下所有的service服务
     * @return array
     *              {"results":[{"desc":"","name":"test_service_1","sid":536098},{"desc":"","name":"test_service_2","sid":536118}]}
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function get(): array
    {
        $data = ["key" => $this->key];

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
     * 创建Service（同一个key下最多15个service）
     * @param string $serviceName 服务的名字, 仅支持中文、英文大小字母、英文下划线"_"、英文横线"-"和数字,不能以"_"开头，最长不得超过128个字符
     * @param string|null $serviceDescription 服务的描述, 仅支持中文、英文大小字母、英文下划线"_"、英文横线"-"和数字,不能以"_"开头，最长不得超过128个字符
     * @return array
     *              {"name":"test_service_1","sid":536098}
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function create(string $serviceName, ?string $serviceDescription = null): array
    {
        if (empty($serviceName)) {
            throw new \Exception("Service Name is required");
        }
        // 更新的参数
        $data = [
            "key" => $this->key,
            "name" => $serviceName,
            "desc" => $serviceDescription
        ];
        if (is_null($data["desc"])) {
            unset($data["desc"]);
        }

        $result = $this->setBaseUri(config("common.map.amap.track.baseuri"))
            ->setTimeout()
            ->setQuery($data)
            ->requestPost(self::URL_ADD);

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
     * 删除service，一旦删除，该service下的所有内容都会被删除
     * @param string $serviceId 服务的唯一id
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function delete(string $serviceId): bool
    {
        $data = [
            "key" => $this->key,
            "sid" => $serviceId,
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
            return false;
        }
        return true;
    }

    /**
     * 更新服务
     * @param string $serviceId 服务的唯一id
     * @param string|null $serviceName 服务的名字，null表示不更新, 仅支持中文、英文大小字母、英文下划线"_"、英文横线"-"和数字,不能以"_"开头，最长不得超过128个字符
     * @param string|null $serviceDescription 服务的描述，null表示不更新, 仅支持中文、英文大小字母、英文下划线"_"、英文横线"-"和数字,不能以"_"开头，最长不得超过128个字符
     * @return array|null null表示不更新
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function update(string $serviceId, ?string $serviceName = null, ?string $serviceDescription = null): ?array
    {
        if (empty($serviceName) && empty($serviceDescription)) {
            return null;
        }
        // 更新的参数
        $data = [
            "key" => $this->key,
            "sid" => $serviceId,
            "name" => $serviceName,
            "desc" => $serviceDescription
        ];
        // 如果服务名称为null，则不更新服务名称
        if (is_null($data["name"])) {
            unset($data["name"]);
        }
        // 如果服务描述为null，则不更新服务描述
        if (is_null($data["desc"])) {
            unset($data["desc"]);
        }
        // 请求接口
        $result = $this->setBaseUri(config("common.map.amap.track.baseuri"))
            ->setTimeout()
            ->setQuery($data)
            ->requestPost(self::URL_UPDATE);
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
