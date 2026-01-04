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

class NormalGoodsTagUploadService
{
    public $header = [
        '商品货号' => 'item_bn',
        '标签名称' => 'tag_name',
    ];

    public $headerInfo = [
        '商品货号' => ['size' => 32, 'remarks' => '', 'is_need' => true],
        '标签名称' => ['size' => 255, 'remarks' => '商品标签为全量覆盖，不填表示清空商品所有标签。多个标签用英文逗号“,”隔开', 'is_need' => false],
    ];

    public $isNeedCols = [
        '商品货号' => 'item_bn',
        '标签名称' => 'tag_name',
    ];
    public $tmpTarget = null;

    /**
     * 验证上传的实体商品信息
     */
    public function check($fileObject)
    {
        $extension = $fileObject->getClientOriginalExtension();
        if ($extension != 'xlsx') {
            throw new BadRequestHttpException('实体商品批量打标签信息上传只支持Excel文件格式(xlsx)');
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
        return ['all' => $this->header, 'is_need' => $this->isNeedCols, 'headerInfo' => $this->headerInfo];
    }

    public function handleRow($companyId, $row)
    {
        app('log')->info('NormalGoodsTagUploadService companyId:'.$companyId.',row===>'.var_export($row, 1));
        $rules = [
            'item_bn' => ['required', '请填写商品货号'],
        ];
        $errorMessage = validator_params($row, $rules, false);
        if ($errorMessage) {
            $msg = implode(', ', $errorMessage);
            throw new BadRequestHttpException($msg);
        }
        // 检查商品是否存在
        $itemsService = new ItemsService();
        $itemInfo = $itemsService->getInfo(['item_bn' => $row['item_bn']]);
        if (!$itemInfo) {
            throw new BadRequestHttpException('未查询到对应商品');
        }
        $itemId = $itemInfo['item_id'];
        $tag_name = [];
        if ($row['tag_name'] !== null) {
            $tag_name = explode(',', $row['tag_name']);
        }
        $itemsTagsService = new ItemsTagsService();
        $tagIds = false;
        if ($tag_name) {
            $filter = [
                'company_id' => $companyId,
                'tag_name' => $tag_name,
            ];
            $tagsList = $itemsTagsService->getListTags($filter, 1, -1);
            $tagIds = array_column($tagsList['list'], 'tag_id');
            if (count($tag_name) > count($tagIds)) {
                throw new BadRequestHttpException('未查询到对应标签');
            }
        }
        if ($tagIds) {
            $result = $itemsTagsService->checkActivity($itemId, $tagIds, $companyId);
            if (!$result) {
                throw new BadRequestHttpException('商品标签导致活动冲突');
            }
        }
        try {
            $itemsTagsService->createRelTagsByItemId($itemId, $tagIds, $companyId);
        } catch (\Exception $e) {
            throw new BadRequestHttpException('更新商品标签数据失败，请重新上传或联系客服处理');
        }
        return true;
    }
}
