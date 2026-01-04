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
 * 轨迹的终端管理
 * https://lbs.amap.com/api/track/lieying-kaifa/api/terminal
 */
class TerminalService extends BaseRequest
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
     * 增加terminal
     */
    public const URL_ADD = "/v1/track/terminal/add";

    /**
     * 删除terminal
     */
    public const URL_DELETE = "/v1/track/terminal/delete";

    /**
     * 修改terminal
     */
    public const URL_UPDATE = "/v1/track/terminal/update";

    /**
     * 查询terminal
     */
    public const URL_GET = "/v1/track/terminal/list";

    /**
     * 获取多个终端信息
     * @param array $filter 过滤条件
     *                  sid: 查询的服务ID
     *                  tid: 查询的设备ID
     *                  name: 查询的设备名称
     * @param int $page
     * @return array
     * @throws \Exception
     */
    public function get(array $filter = [], int $page = 1): array
    {
        $data = [
            "key" => $this->key,
            "sid" => $this->serviceId,
            "page" => $page
        ];
        // 查询的terminal id
        if (!empty($filter["tid"])) {
            $data["tid"] = $filter["tid"];
        }
        // 查询的terminal name，如果存在tid，则以tid为准
        if (!empty($filter["name"])) {
            $data["name"] = $filter["name"];
        }
        // 请求api
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
     * 创建终端
     * 自定义字段的创建：https://lbs.amap.com/api/track/lieying-kaifa/api/terminal-column
     * @param string $serviceId 服务id
     * @param string $terminalName 终端的名字，仅支持中文、英文大小字母、英文下划线"_"、英文横线"-"和数字，最长不得超过128字符,不能以"_"开头
     * @param string|null $terminalDescription 终端的描述，仅支持中文、英文大小字母、英文下划线"_"、英文横线"-"和数字，最长不得超过128字符,不能以"_"开头
     * @param string|null $props 自定义字段，仅支持中文、英文大小字母、英文下划线"_"、英文横线"-"、数字和”.”，,不允许"_"开头。
     * @return array|null
     *                  {"name":"test_service_1_terminal_1","tid":459107858,"sid":536138}
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function create(string $terminalName, ?string $terminalDescription = null, ?string $props = null): ?array
    {
        if (empty($terminalName)) {
            return null;
        }
        $data = [
            "key" => $this->key,
            "sid" => $this->serviceId,
            "name" => $terminalName
        ];
        if (!is_null($terminalDescription)) {
            $data["desc"] = $terminalDescription;
        }
        if (!is_null($props)) {
            $data["props"] = $props;
        }
        // 请求api
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
     * 删除终端
     * @param string $terminalId 终端id
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function delete(string $terminalId): bool
    {
        $data = [
            "key" => $this->key,
            "sid" => $this->serviceId,
            "tid" => $terminalId,
        ];
        // 请求api
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
     * 更新终端信息
     * @param string $terminalId 终端id
     * @param string|null $terminalName 终端名字
     * @param string|null $terminalDescription 终端描述
     * @param string|null $props 终端自定义字段，【如果内容为空，则清空原来的所有字段】【如果某个字段为空，则将该字段清空】【如果某个字段有值，则只更新该字段的类型】
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function update(string $terminalId, ?string $terminalName, ?string $terminalDescription = null, ?string $props = null): bool
    {
        if (is_null($terminalName) && is_null($terminalDescription) && is_null($props)) {
            return true;
        }
        $data = [
            "key" => $this->key,
            "sid" => $this->serviceId,
            "tid" => $terminalId,
        ];
        // 终端名字
        if (!is_null($terminalName)) {
            $data["name"] = $terminalName;
        }
        // 终端描述
        if (!is_null($terminalDescription)) {
            $data["desc"] = $terminalDescription;
        }
        // 终端自定义字段
        if (!is_null($props)) {
            $data["props"] = $props;
        }
        // 请求api
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
            return false;
        }

        return true;
    }
}
