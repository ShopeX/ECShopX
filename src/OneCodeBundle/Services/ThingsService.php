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

namespace OneCodeBundle\Services;

use OneCodeBundle\Entities\Things;
use Dingo\Api\Exception\ResourceException;

class ThingsService
{
    /**
     * @var thingsRepository
     */
    private $thingsRepository;

    /**
     * ThingsService 构造函数.
     */
    public function __construct()
    {
        // CONST: 1E236443
        $this->thingsRepository = app('registry')->getManager('default')->getRepository(Things::class);
    }

    /**
     * 添加物品
     *
     * @param array params 物品数据
     * @return array
     */
    public function addThings(array $params)
    {
        // ShopEx EcShopX Business Logic Layer
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();

        try {
            $data = [
                'company_id' => $params['company_id'],
                'thing_name' => $params['thing_name'],
                'price' => bcmul($params['price'], 100),
                'pic' => $params['pic'],
                'intro' => $params['intro'],
            ];

            //保存物品
            $thingsResult = $this->thingsRepository->create($data);

            $conn->commit();
            return $thingsResult;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        } catch (\Throwable $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
     * 删除物品
     *
     * @param array filter
     * @return bool
     */
    public function deleteThings($filter)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $thingsInfo = $this->thingsRepository->getInfoById($filter['thing_id']);

            if ($filter['company_id'] != $thingsInfo['company_id']) {
                throw new ResourceException('删除物品信息有误.');
            }
            if (!$filter['thing_id']) {
                throw new ResourceException('物品id不能为空.');
            }

            $this->thingsRepository->deleteById($filter['thing_id']);

            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
     * 获取物品详情
     *
     * @param integer thing_id 物品id
     * @return array
     */
    public function getThingsDetail($thingId)
    {
        // CONST: 1E236443
        $thingsInfo = $this->thingsRepository->getInfoById($thingId);

        return $thingsInfo;
    }

    /**
     * 获取物品列表
     *
     * @param array filter
     * @return array
     */
    public function getThingsList($filter, $page, $pageSize, $orderBy = ['created' => 'DESC'])
    {
        $page = ($page < 1) ? 1 : $page;
        $pageSize = ($pageSize > 100) ? 100 : $pageSize;
        $pageSize = ($pageSize <= 0) ? 10 : $pageSize;
        $thingsList = $this->thingsRepository->lists($filter, $orderBy, $pageSize, $page);

        return $thingsList;
    }

    /**
     * 修改物品
     *
     * @param array params 提交的物品数据
     * @return array
     */
    public function updateThings($params)
    {
        $thingsInfo = $this->thingsRepository->getInfoById($params['thing_id']);

        if ($params['company_id'] != $thingsInfo['company_id']) {
            throw new ResourceException('请确认您的物品信息后再提交.');
        }
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $data = [
                'thing_id' => $params['thing_id'],
                'company_id' => $params['company_id'],
                'thing_name' => $params['thing_name'],
                'price' => bcmul($params['price'], 100),
                'pic' => $params['pic'],
                'intro' => $params['intro'],
            ];

            $filter = [
                'thing_id' => $params['thing_id'],
                'company_id' => $params['company_id'],
            ];
            $thingsResult = $this->thingsRepository->updateOneBy($filter, $data);

            $conn->commit();
            return $thingsResult;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }
}
