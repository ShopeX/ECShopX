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

use OneCodeBundle\Entities\Batchs;
use OneCodeBundle\Entities\Things;
use Dingo\Api\Exception\ResourceException;
use WechatBundle\Services\OpenPlatform;

class BatchsService
{
    /**
     * @var batchsRepository
     */
    private $batchsRepository;

    /**
     * @var thingsRepository
     */
    private $thingsRepository;

    /**
     * BatchsService 构造函数.
     */
    public function __construct()
    {
        $this->batchsRepository = app('registry')->getManager('default')->getRepository(Batchs::class);
        $this->thingsRepository = app('registry')->getManager('default')->getRepository(Things::class);
    }

    /**
     * 添加物品批次
     *
     * @param array params 物品批次数据
     * @return array
     */
    public function addBatchs(array $params)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();

        try {
            $data = [
                'company_id' => $params['company_id'],
                'thing_id' => $params['thing_id'],
                'batch_number' => $params['batch_number'],
                'batch_name' => $params['batch_name'],
                'batch_quantity' => intval($params['batch_quantity']),
                'show_trace' => $params['show_trace'] ? true : false,
                'trace_info' => $params['trace_info'],
            ];

            //保存物品批次
            $batchsResult = $this->batchsRepository->create($data);

            // 更新物品表批次相关信息，用于展示
            if ($batchsResult) {
                $thingsInfo = $this->thingsRepository->getInfoById($params['thing_id']);
                $thingsData = [
                    'thing_id' => $params['thing_id'],
                    'company_id' => $params['company_id'],
                    'batch_total_count' => $thingsInfo['batch_total_count'] + 1,
                    'batch_total_quantity' => $thingsInfo['batch_total_quantity'] + $params['batch_quantity'],
                ];

                $thingsFilter = [
                    'thing_id' => $params['thing_id'],
                    'company_id' => $params['company_id'],
                ];

                $this->thingsRepository->updateOneBy($thingsFilter, $thingsData);
            }

            $conn->commit();
            return $batchsResult;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
     * 删除物品批次
     *
     * @param array filter
     * @return bool
     */
    public function deleteBatchs($filter)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $batchsInfo = $this->batchsRepository->getInfoById($filter['batch_id']);

            if ($filter['company_id'] != $batchsInfo['company_id']) {
                throw new ResourceException('删除物品批次信息有误.');
            }
            if (!$filter['batch_id']) {
                throw new ResourceException('物品批次id不能为空.');
            }

            $this->batchsRepository->deleteById($filter['batch_id']);

            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
     * 获取物品批次详情
     *
     * @param integer thing_id 物品批次id
     * @return array
     */
    public function getBatchsDetail($batchId)
    {
        $batchsInfo = $this->batchsRepository->getInfoById($batchId);

        return $batchsInfo;
    }

    /**
     * 获取物品批次列表
     *
     * @param array filter
     * @return array
     */
    public function getBatchsList($filter, $page, $pageSize, $orderBy = ['created' => 'DESC'])
    {
        $page = ($page < 1) ? 1 : $page;
        $pageSize = ($pageSize > 100) ? 100 : $pageSize;
        $pageSize = ($pageSize <= 0) ? 10 : $pageSize;
        $batchsList = $this->batchsRepository->lists($filter, $orderBy, $pageSize, $page);

        return $batchsList;
    }

    /**
     * 修改物品批次
     *
     * @param array params 提交的物品批次数据
     * @return array
     */
    public function updateBatchs($params)
    {
        $batchsInfo = $this->batchsRepository->getInfoById($params['batch_id']);

        if ($params['company_id'] != $batchsInfo['company_id']) {
            throw new ResourceException('请确认您的物品批次信息后再提交.');
        }
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $data = [
                'batch_id' => $params['batch_id'],
                'company_id' => $params['company_id'],
                'thing_id' => $params['thing_id'],
                'batch_number' => $params['batch_number'],
                'batch_name' => $params['batch_name'],
                'batch_quantity' => $params['batch_quantity'],
                'show_trace' => $params['show_trace'] ? true : false,
                'trace_info' => $params['trace_info'],
            ];
            $filter = [
                'batch_id' => $params['batch_id'],
                'company_id' => $params['company_id'],
            ];
            $batchsResult = $this->batchsRepository->updateOneBy($filter, $data);

            $conn->commit();
            return $batchsResult;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    // 获取一物一码批次码
    public function getWxaOneCodeStream($wxaappid, $batchId, $num, $isBase64 = 0)
    {
        $openPlatform = new OpenPlatform();
        $app = $openPlatform->getAuthorizerApplication($wxaappid);
        $data['page'] = 'pages/onecode';
        $scene = 'id=' . $batchId . '&num=' . $num;
        $wxaCode = $app->app_code->getUnlimit($scene, $data);
        if ($isBase64) {
            $base64 = 'data:image/jpg;base64,' . base64_encode($wxaCode);
            return ['base64Image' => $base64];
        } else {
            return $wxaCode;
        }
    }
}
