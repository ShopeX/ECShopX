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

namespace MembersBundle\Services;

use MembersBundle\Entities\MembersAddress;


use MembersBundle\Jobs\UpdateAddressLatAndLngJob;
use ThirdPartyBundle\Data\MapData;
use ThirdPartyBundle\Services\Map\MapService;

class MemberAddressService
{
    private $membersAddressRepository;

    /**
     * MemberAddressService 构造函数.
     */
    public function __construct()
    {
        $this->membersAddressRepository = app('registry')->getManager('default')->getRepository(MembersAddress::class);
    }

    public function createAddress($params)
    {
        $filter = [
            'company_id' => $params['company_id'],
            'user_id' => $params['user_id'],
        ];
        $addrCount = $this->membersAddressRepository->count($filter);
        if ($addrCount >= 20) {
            throw new \Exception(trans('MembersBundle/Members.max_address_limit'));
        }
        $this->appendLngAndLat((int)$params['company_id'], $params);
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            if (0 == $addrCount) {
                $params['is_def'] = 1;
            }
            // 将其他地址改为非默认
            if (0 != $addrCount && $params['is_def'] == 1) {
                $this->membersAddressRepository->updateBy($filter, ['is_def' => '0']);
            }

            $result = $this->membersAddressRepository->create($params);

            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }

        return $result;
    }

    public function updateAddress($filter, $params)
    {
        $this->appendLngAndLat((int)$filter['company_id'], $params);
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            // 将其他地址改为非默认
            if (isset($params['is_def']) && $params['is_def'] == 1) {
                $filter_def = [
                    'user_id' => $filter['user_id'],
                    'company_id' => $filter['company_id'],
                ];
                $this->membersAddressRepository->updateBy($filter_def, ['is_def' => '0']);
            }

            // 防止误修改
            $filter = [
                'address_id' => $filter['address_id'],
                'user_id' => $filter['user_id'],
                'company_id' => $filter['company_id'],
            ];
            $result = $this->membersAddressRepository->updateOneBy($filter, $params);

            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }

        return $result;
    }


    /**
     * Dynamically call the shopsservice instance.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->membersAddressRepository->$method(...$parameters);
    }

    /**
     * 获取地址列表
     * @param array $filter 过滤条件
     * @param int $page 当前页
     * @param int $pageSize 每页数量
     * @param array $orderBy 排序方式
     * @return array
     */
    public function lists($filter, $page, $pageSize, $orderBy): array
    {
        $result = $this->membersAddressRepository->lists($filter, $page, $pageSize, $orderBy);
        if (isset($result["list"]) && is_array($result["list"])) {
            foreach ($result["list"] as &$item) {
                $this->dispatchJob($item);
            }
        }
        return $result;
    }

    public function getDefaultLatAndLng()
    {
    }

    /**
     * 获取默认的地址
     * @param int $companyId 企业id
     * @param int $userId 用户id
     * @return array
     */
    public function getDefaultAddress(int $companyId, int $userId): array
    {
        $detail = $this->membersAddressRepository->getInfo([
            "company_id" => $companyId,
            "user_id" => $userId,
            "is_def" => 1
        ]);
        if (empty($detail)) {
            return [];
        }
        $this->dispatchJob($detail);
        return $detail;
    }

    /**
     * 分发任务
     * @param array $info
     */
    protected function dispatchJob(array $info)
    {
        $lat = (string)($info["lat"] ?? "");
        $lng = (string)($info["lng"] ?? "");
        if (!is_numeric($lat) || !is_numeric($lng)) {
            // 异步更新该收获地址的经纬度
            $queue = (new UpdateAddressLatAndLngJob((int) $info["company_id"], (int) $info["user_id"], (int) $info["address_id"]))->onQueue('slow');
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($queue);
        }
    }

    /**
     * 追加地址的经纬度
     * @param array $params
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function appendLngAndLat(int $companyId, array &$params)
    {
        $params["lat"] = ""; // 经度
        $params["lng"] = ""; // 纬度
        if (isset($params["province"]) && isset($params["city"]) && isset($params["adrdetail"])) {
            $mapData = MapService::make($companyId)->getLatAndLng($params["province"].$params["city"], $params["adrdetail"]);
            $params["lat"] = $mapData->getLat(); // 经度
            $params["lng"] = $mapData->getLng(); // 纬度
//            (new TencentMapRequest)->getLngAndLat($params["lng"],$params["lat"],(string)$params["city"], (string)$params["adrdetail"]);
        }
    }

    /**
     * 根据地址获取经纬度
     * @param int $companyId 公司company_id
     * @param array $address 地址信息
     * @return MapData
     * @throws Exception
     */
    public function getLngAndLatByAddress(int $companyId, array $address): MapData
    {
        $mapData = new MapData();

        // 如果地址有经纬度则直接获取
        if (!empty($address["lat"]) && !empty($address["lng"])) {
            $mapData->setLat((string)$address["lat"]);
            $mapData->setLng((string)$address["lng"]);
            return $mapData;
        }

        // 没有城市和详细地址的话，无法获取经纬度
        if (empty($address["city"]) || empty($address["adrdetail"])) {
            return $mapData;
        }

        // 请求第三方地图接口获取经纬度
        return MapService::make($companyId)->getLatAndLng((string)$address["city"], (string)$address["adrdetail"]);
    }
}
