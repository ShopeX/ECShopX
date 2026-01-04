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

namespace GoodsBundle\Services;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use GuzzleHttp\Client as Client;

use DistributionBundle\Services\DistributorItemsService;
use CompanysBundle\Ego\CompanysActivationEgo;
use DistributionBundle\Services\DistributorService;

class NormalGoodsStoreUploadService
{
    public $itemName = null;

    public $defaultItemId = null;

    public $header = [
        '店铺ID' => 'did',
        '商品编码' => 'item_bn',
        '库存' => 'store',
    ];

    public $headerInfo = [
        '店铺ID' => ['size' => 255, 'remarks' => 'ID=0更新总部库存', 'is_need' => true],
        '商品编码' => ['size' => 32, 'remarks' => '', 'is_need' => true],
        '库存' => ['size' => 255, 'remarks' => '库存为0-999999999的整数', 'is_need' => true],
    ];

    public $isNeedCols = [
        '店铺ID' => 'did',
        '商品编码' => 'item_bn',
        '库存' => 'store',
    ];
    public $tmpTarget = null;

    /**
     * 验证上传的实体商品信息
     */
    public function check($fileObject)
    {
        $extension = $fileObject->getClientOriginalExtension();
        if ($extension != 'xlsx') {
            throw new BadRequestHttpException('实体商品库存信息上传只支持Excel文件格式(xlsx)');
        }
    }

    /**
     * getFilePath function
     *
     * @return void
     */
    public function getFilePath($filePath, $fileExt = '')
    {
        $url = $this->getFileSystem()->privateDownloadUrl($filePath);

        $client = new Client();
        $content = $client->get($url)->getBody()->getContents();

        $this->tmpTarget = tempnam('/tmp', 'import-file') . $fileExt;
        file_put_contents($this->tmpTarget, $content);

        return $this->tmpTarget;
    }

    public function getFileSystem()
    {
        return app('filesystem')->disk('import-file');
    }

    public function finishHandle()
    {
        unlink($this->tmpTarget);
        return true;
    }

    /**
     * 获取头部标题
     */
    public function getHeaderTitle()
    {
        // XXX: review this code
        return ['all' => $this->header, 'is_need' => $this->isNeedCols, 'headerInfo' => $this->headerInfo];
    }

    public function handleRow($companyId, $row)
    {
        app('log')->info('NormalGoodsStoreUploadService companyId:'.$companyId.',row===>'.var_export($row, 1));
        $rules = [
            'did' => ['required', '请填写店铺ID'],
            'item_bn' => ['required', '请填写商品编码'],
            'store' => ['required|integer|min:0|max:999999999', '库存为0-999999999的整数'],
        ];
        $errorMessage = validator_params($row, $rules, false);
        if ($errorMessage) {
            $msg = implode(', ', $errorMessage);
            throw new BadRequestHttpException($msg);
        }
        // 检查商品是否存在
        $itemsService = new ItemsService();
        $itemInfo = $itemsService->getInfo(['company_id' => $companyId, 'item_bn' => $row['item_bn']]);
        if (!$itemInfo) {
            throw new BadRequestHttpException('商品不存在');
            //todo 这里暂时不支持供应商商品库存导入
            // $itemsService = new SupplierItemsService();
            // $itemInfo = $itemsService->repository->getInfo(['company_id' => $companyId, 'item_bn' => $row['item_bn']]);
            // if (!$itemInfo) {
            //     throw new BadRequestHttpException('商品不存在');
            // }
        }
        if ($row['distributor_id'] > 0 && $row['did'] != $row['distributor_id']) {
            throw new BadRequestHttpException('只能导入所属店铺的商品库存');
        }
        if ($row['merchant_id'] > 0) {
            if (!$row['did']) {
                throw new BadRequestHttpException('只能导入所属经销商关联店铺的商品库存');
            }
            $distributorService = new DistributorService();
            $distributorList = $distributorService->getLists(['company_id' => $companyId, 'merchant_id' => $row['merchant_id'], 'distributor_id' => $row['did']]);
            if (!$distributorList) {
                throw new BadRequestHttpException('只能导入所属经销商关联店铺的商品库存');
            }
        }

        $itemId = $itemInfo['item_id'];
        $store = intval($row['store']);
        $distributorId = $row['did'];

        $distributorItemsService = new DistributorItemsService();
        $itemStoreService = new ItemStoreService();

        $company = (new CompanysActivationEgo())->check($companyId);
        if ($distributorId > 0 && $company['product_model'] == 'standard') {
            $distributorItem = $distributorItemsService->getValidDistributorItemSkuInfo($companyId, $itemId, $distributorId);
            if (!$distributorItem) {
                throw new BadRequestHttpException('店铺商品不存在');
            }

            if ($distributorItem['is_total_store'] ?? true) {
                throw new BadRequestHttpException('门店库存为总部库存');
            } else {
                $filter = [
                    'company_id' => $companyId,
                    'item_id' => $itemId,
                    'distributor_id' => $distributorId
                ];

                app('log')->info('NormalGoodsStoreUploadService item_id:'.$itemId.',store:'.$store.',distributor_id:'.$distributorId.',line:'.__LINE__);
                $distributorItemsService->updateOneBy($filter, ['store' => $store]);
                return $itemStoreService->saveItemStore($itemId, $store, $distributorId);
            }
        } else {
            app('log')->info('NormalGoodsStoreUploadService item_id:'.$itemId.',store:'.$store.',line:'.__LINE__);
            $itemsService->updateStore($itemId, $store, true);
            return $itemStoreService->saveItemStore($itemId, $store);
        }
    }
}
