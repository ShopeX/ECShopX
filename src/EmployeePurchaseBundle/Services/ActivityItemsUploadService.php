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

namespace EmployeePurchaseBundle\Services;

use GoodsBundle\Services\ItemsService;
use DistributionBundle\Services\DistributorItemsService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use GuzzleHttp\Client as Client;

class ActivityItemsUploadService
{
    public $header = [
        'SKU编码' => 'item_bn',
        '活动价格' => 'activity_price',
        '限购数量' => 'limit_num',
        '限购金额' => 'limit_fee',
        '状态' => 'shelf_status',
    ];

    public $activityStoreHeader = [
        '活动库存' => 'activity_store',
    ];

    public $headerInfo = [
        'SKU编码' => ['size' => 20, 'remarks' => 'SKU编码', 'is_need' => true],
        '活动价格' => ['size' => 20, 'remarks' => '活动价格', 'is_need' => true],
        '限购数量' => ['size' => 20, 'remarks' => '限购数量', 'is_need' => false],
        '限购金额' => ['size' => 20, 'remarks' => '限购金额', 'is_need' => false],
        '状态' => ['size' => 5, 'remarks' => '1-上架，0-下架，留空默认上架', 'is_need' => false],
    ];

    public $activityStoreHeaderInfo = [
        '活动库存' => ['size' => 20, 'remarks' => '活动库存', 'is_need' => true],
    ];

    public $isNeedCols = [
        'SKU编码' => 'item_bn',
        '活动价格' => 'activity_price',
    ];

    /**
     * 验证上传的白名单
     */
    public function check($fileObject)
    {
        $extension = $fileObject->getClientOriginalExtension();
        if ($extension != 'xlsx') {
            throw new BadRequestHttpException('内购活动商品只支持上传Excel文件格式');
        }
    }

    public $tmpTarget = null;

    /**
     * getFilePath function
     *
     * @return void
     */
    public function getFilePath($filePath, $fileExt = '')
    {
        if (env('DISK_DRIVER') == 'local') {
            //本地用这个
            $content = file_get_contents(storage_path('app/public/' . $filePath));
        } else {
            $url = $this->getFileSystem()->privateDownloadUrl($filePath);
            $client = new Client();
            $content = $client->get($url)->getBody()->getContents();
        }

        $this->tmpTarget = tempnam('/tmp', 'import-file') . $fileExt;
        file_put_contents($this->tmpTarget, $content);

        return $this->tmpTarget;
    }

    public function finishHandle()
    {
        unlink($this->tmpTarget);
        return true;
    }

    public function getFileSystem()
    {
        return app('filesystem')->disk('import-file');
    }

    /**
     * 获取头部标题
     */
    public function getHeaderTitle($companyId = 0, $operatorType = '', $relationId = 0)
    {
        $header = $this->header;
        $headerInfo = $this->headerInfo;
        $isNeedCols = $this->isNeedCols;
        $activity = $this->getActivity($companyId, $relationId);
        if (!$activity['if_share_store']) {
            $header = array_merge(
                array_slice($header, 0, 2, true),
                $this->activityStoreHeader,
                array_slice($header, 2, null, true)
            );
            $headerInfo = array_merge(
                array_slice($headerInfo, 0, 2, true),
                $this->activityStoreHeaderInfo,
                array_slice($headerInfo, 2, null, true)
            );
            $isNeedCols = array_merge(
                array_slice($isNeedCols, 0, 2, true),
                $this->activityStoreHeader,
                array_slice($isNeedCols, 2, null, true)
            );
        }

        return ['all' => $header, 'is_need' => $isNeedCols, 'headerInfo' => $headerInfo];
    }

    private function getActivity($companyId, $relationId)
    {
        if (!$relationId) {
            throw new BadRequestHttpException('关联id不能为空');
        }

        $activitiesService = new ActivitiesService();
        $activity = $activitiesService->entityRepository->getInfo(['company_id' => $companyId, 'id' => $relationId]);
        if (!$activity) {
            throw new BadRequestHttpException('内购活动不存在');
        }

        return $activity;
    }

    private function _formatData($row)
    {
        $columns = ['distributor_id', 'item_bn', 'activity_price', 'activity_store', 'limit_fee', 'limit_num', 'sort', 'shelf_status', 'relation_id'];
        $data = [];
        foreach ($row as $k => $v) {
            if (in_array($k, $columns)) {
                $data[$k] = trim($row[$k]);
            }
        }
        return $data;
    }

    public function handleRow($companyId, $row)
    {
        $data = $this->_formatData($row);
        if (!isset($data['activity_price']) || $data['activity_price'] <= 0 || $data['activity_price'] == '') {
            throw new BadRequestHttpException('内购活动商品价格异常');
        }
        if (!isset($data['item_bn']) || $data['item_bn'] === '') {
            throw new BadRequestHttpException('SKU编码不能为空');
        }

        $activity = $this->getActivity($companyId, $data['relation_id'] ?? 0);
        $activitiesService = new ActivitiesService();
        $distributorId = (int) ($data['distributor_id'] ?? 0);
        $item = $this->resolveImportItem($companyId, $data['item_bn'], $distributorId);

        $itemData = [
            'activity_id' => $activity['id'],
            'company_id' => $companyId,
            'item_id' => $item['item_id'],
            'goods_id' => $item['goods_id'],
            'activity_price' => bcmul($data['activity_price'], 100, 2),
            'activity_store' => 0,
            'limit_fee' => ($data['limit_fee'] ?? 0) ? bcmul($data['limit_fee'], 100, 2) : 0,
            'limit_num' => ($data['limit_num'] ?? '') !== '' ? $data['limit_num'] : 0,
            'sort' => ($data['sort'] ?? '') !== '' ? $data['sort'] : 0,
        ];
        if (!$activity['if_share_store']) {
            if (!isset($data['activity_store']) || $data['activity_store'] === '') {
                throw new BadRequestHttpException('活动库存不能为空');
            }
            if (!is_numeric($data['activity_store']) || $data['activity_store'] < 0) {
                throw new BadRequestHttpException('活动库存必须大于等于0');
            }
            $itemData['activity_store'] = (int) $data['activity_store'];
        }

        $shelfStatus = 1;
        if (isset($data['shelf_status']) && $data['shelf_status'] !== '') {
            if (!in_array((string) $data['shelf_status'], ['0', '1'], true)) {
                throw new BadRequestHttpException('状态只能填写0或1');
            }
            $shelfStatus = (int) $data['shelf_status'];
        }
        $itemData['shelf_status'] = $shelfStatus;

        $goodsData = [
            'activity_id' => $activity['id'],
            'company_id' => $companyId,
            'goods_id' => $item['goods_id'],
        ];

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $filter = [
                'activity_id' => $activity['id'],
                'company_id' => $companyId,
                'item_id' => $item['item_id'],
            ];
            $activityItem = $activitiesService->itemsEntityRepository->getInfo($filter);
            if (!$activityItem) {
                $activitiesService->itemsEntityRepository->create($itemData);
            } else {
                if ($activity['if_share_store']) {
                    unset($itemData['activity_store']);
                }
                $activitiesService->itemsEntityRepository->updateBy($filter, $itemData);
            }

            $activityGoods = $activitiesService->goodsEntityRepository->getInfo($goodsData);
            if (!$activityGoods) {
                $activitiesService->goodsEntityRepository->create($goodsData);
            }

            // 更新活动关联的商品分类
            $activityItemsService = new ActivityItemsService();
            $activityItemsService->storeActivityItemsCategory($companyId, $activity['id']);
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw new BadRequestHttpException($e->getMessage());
        }
    }

    /**
     * 按 distributor_id 解析导入 SKU：平台走主商城商品库，店铺走店铺可售商品。
     */
    private function resolveImportItem($companyId, $itemBn, $distributorId)
    {
        $filter = [
            'company_id' => $companyId,
            'item_bn' => $itemBn,
        ];
        if ($distributorId === 0) {
            $filter['distributor_id'] = 0;
        }

        $itemsService = new ItemsService();
        $item = $itemsService->getItem($filter);
        if (!$item) {
            $label = $distributorId > 0 ? '店铺' : '平台';
            throw new BadRequestHttpException($label . '商品不存在:' . $itemBn);
        }

        if ($distributorId > 0) {
            $distributorItemsService = new DistributorItemsService();
            $distributorItem = $distributorItemsService->getValidDistributorItemSkuInfo($companyId, $item['item_id'], $distributorId);
            if (!$distributorItem) {
                throw new BadRequestHttpException('店铺商品不存在:' . $itemBn);
            }
        } elseif ((int) ($item['distributor_id'] ?? 0) !== 0) {
            throw new BadRequestHttpException('平台商品不存在:' . $itemBn);
        }

        return $item;
    }
}
