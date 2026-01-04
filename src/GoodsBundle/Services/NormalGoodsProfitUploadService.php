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

class NormalGoodsProfitUploadService
{
    public $header = [
        '商品编码' => 'item_bn',
        '分润类型' => 'profit_type',
        '拉新分润' => 'profit',
        '推广分润' => 'popularize_profit',
    ];

    public $headerInfo = [
        '商品编码' => ['size' => 32, 'remarks' => '', 'is_need' => true],
        '分润类型' => ['size' => 255, 'remarks' => '分润类型:0,1或2, 0默认分润 1固定比例分润 2固定金额分润', 'is_need' => true],
        '拉新分润' => ['size' => 255, 'remarks' => '1:按照比例分润 1-100, 2:按照固定金额分润(元)，最多两位小数', 'is_need' => true],
        '推广分润' => ['size' => 255, 'remarks' => '1:按照比例分润 1-100, 2:按照固定金额分润(元)，最多两位小数', 'is_need' => true],
    ];

    public $isNeedCols = [
        '商品编码' => 'item_bn',
        '分润类型' => 'profit_type',
        '拉新分润' => 'profit',
        '推广分润' => 'popularize_profit',
    ];

    public $tmpTarget = null;
    /**
     * 验证上传的实体商品信息
     */
    public function check($fileObject)
    {
        $extension = $fileObject->getClientOriginalExtension();
        if ($extension != 'xlsx') {
            throw new BadRequestHttpException('实体商品分润信息上传只支持Excel文件格式(xlsx)');
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
        // TODO: optimize this method
        unlink($this->tmpTarget);
        return true;
    }

    /**
     * 获取头部标题
     */
    public function getHeaderTitle()
    {
        // TODO: optimize this method
        return ['all' => $this->header, 'is_need' => $this->isNeedCols, 'headerInfo' => $this->headerInfo];
    }

    public function handleRow($companyId, $row)
    {
        app('log')->info('NormalGoodsProfitUploadService companyId:'.$companyId.',row===>'.var_export($row, 1));
        $rules = [
            'item_bn' => ['required', '请填写商品编码'],
            'profit_type' => ['required', '请填写分润类型'],
        ];
        if ($row['profit_type']) {
            $rules = [
                'profit' => ['required', '请填写拉新分润'],
                'popularize_profit' => ['required', '请填写推广分润'],
            ];
        }
        $errorMessage = validator_params($row, $rules, false);
        if ($errorMessage) {
            $msg = implode(', ', $errorMessage);
            throw new BadRequestHttpException($msg);
        }

        $itemsProfitService = new ItemsProfitService();

        if (!in_array($row['profit_type'], [$itemsProfitService::STATUS_PROFIT_DEFAULT, $itemsProfitService::STATUS_PROFIT_SCALE, $itemsProfitService::STATUS_PROFIT_FEE])) {
            throw new BadRequestHttpException('分润类型错误');
        }

        // 检查商品是否存在
        $itemsService = new ItemsService();
        $itemInfo = $itemsService->getInfo(['item_bn' => $row['item_bn']]);
        if (!$itemInfo) {
            throw new BadRequestHttpException('商品不存在');
        }
        $itemId = $itemInfo['item_id'];

        $itemsProfitService->deleteBy(['item_id' => $itemId, 'company_id' => $companyId]);
        if ($itemsProfitService::STATUS_PROFIT_DEFAULT != $row['profit_type']) {
//            $profitConfData = [
//                'profit' => bcmul(bcdiv($row['profit'], 100, 4), $itemInfo['price']),
//                'popularize_profit' => bcmul(bcdiv($row['popularize_profit'], 100, 4), $itemInfo['price']),
//            ];
            if ($row['profit_type'] == $itemsProfitService::STATUS_PROFIT_SCALE) {
                $profitConfData = [
                    'profit' => $row['profit'],
                    'popularize_profit' => $row['popularize_profit'],
                ];
            } else {
                $profitConfData = [
                    'profit' => bcmul($row['profit'], 100),
                    'popularize_profit' => bcmul($row['popularize_profit'], 100),
                ];
            }
            $itemProfitInfo = [
                'item_id' => $itemId,
                'company_id' => $companyId,
                'profit_type' => $row['profit_type'],
                'profit_conf' => $profitConfData,
            ];
            $profitType = $itemsProfitService::STATUS_PROFIT_SCALE == $row['profit_type'] ? $itemsProfitService::PROFIT_ITEM_PROFIT_SCALE : $itemsProfitService::PROFIT_ITEM_PROFIT_FEE;
            $profitFee = $itemsProfitService::STATUS_PROFIT_SCALE == $row['profit_type'] ? bcmul(bcdiv($row['popularize_profit'], 100, 4), $itemInfo['price']) : $row['popularize_profit'];
            $result = $itemsProfitService->create($itemProfitInfo);
            $itemsService->updateBy(['item_id' => $itemId], ['profit_type' => $profitType, 'profit_fee' => $profitFee]);
        } else {
            $profitConfData = [
                'profit' => '',
                'popularize_profit' => '',
            ];
            $itemProfitInfo = [
                'item_id' => $itemId,
                'company_id' => $companyId,
                'profit_type' => $row['profit_type'],
                'profit_conf' => $profitConfData,
            ];
            $itemsProfitService->create($itemProfitInfo);
        }
    }
}
