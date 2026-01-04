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
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use GuzzleHttp\Client as Client;

class ActivityItemsUploadService
{
    public $header = [
        '活动名称' => 'activity_name',
        '商品编码' => 'item_bn',
        '活动价格' => 'activity_price',
        '活动库存' => 'activity_store',
        '每人限购金额' => 'limit_fee',
        '每人限购数量' => 'limit_num',
        '排序' => 'sort',
    ];

    public $headerInfo = [
        '活动名称' => ['size' => 255, 'remarks' => '内购活动名称，可以在内购活动列表查询', 'is_need' => true],
        '商品编码' => ['size' => 20, 'remarks' => '商品编码', 'is_need' => true],
        '活动价格' => ['size' => 20, 'remarks' => '活动价格', 'is_need' => true],
        '活动库存' => ['size' => 20, 'remarks' => '活动库存', 'is_need' => false],
        '每人限购金额' => ['size' => 20, 'remarks' => '每人限购金额', 'is_need' => false],
        '每人限购数量' => ['size' => 20, 'remarks' => '每人限购数量', 'is_need' => false],
        '排序' => ['size' => 5, 'remarks' => '排序', 'is_need' => false],
    ];

    public $isNeedCols = [
        '活动名称' =>'activity_name',
        '商品编码' => 'item_bn',
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
    public function getHeaderTitle()
    {
        return ['all' => $this->header, 'is_need' => $this->isNeedCols, 'headerInfo' => $this->headerInfo];
    }

    private function _formatData($row)
    {
        $columns = ['activity_name', 'item_bn', 'activity_price', 'activity_store', 'limit_fee', 'limit_num', 'sort'];
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
        if(!isset($data['activity_price']) || $data['activity_price'] <= 0 || $data['activity_price'] == ''){
            throw new BadRequestHttpException('内购活动商品价格异常');
        }

        $activitiesService = new ActivitiesService();
        $activity = $activitiesService->entityRepository->getInfo(['company_id' => $companyId, 'name' => $data['activity_name']]);
        if (!$activity) {
            throw new BadRequestHttpException('内购活动不存在');
        }

        $filter = [
            'company_id' => $companyId,
            'item_bn' => $data['item_bn'],
        ];
        $itemsService = new ItemsService();
        $item = $itemsService->getItem($filter);
        if (!$item) {
            throw new BadRequestHttpException('商品不存在:' . $data['item_bn']);
        }

        $itemData = [
            'activity_id' => $activity['id'],
            'company_id' => $companyId,
            'item_id' => $item['item_id'],
            'goods_id' => $item['goods_id'],
            'activity_price' => bcmul($data['activity_price'], 100, 2),
            'activity_store' => $data['activity_store'] ?: 0,
            'limit_fee' => $data['limit_fee'] ? bcmul($data['limit_fee'], 100, 2) : 0,
            'limit_num' => $data['limit_num'] ?: 0,
            'sort' => $data['sort'] ?: 0,
        ];

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
}
