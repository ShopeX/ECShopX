<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace PointsmallBundle\Services;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use GuzzleHttp\Client as Client;

class NormalGoodsStoreUploadService
{
    public $itemName = null;

    public $defaultItemId = null;

    public $header = [
        '商品编码' => 'item_bn',
        '库存' => 'store',
    ];

    public $headerInfo = [
        '商品编码' => ['size' => 32, 'remarks' => '', 'is_need' => true],
        '库存' => ['size' => 255, 'remarks' => '库存为0-999999999的整数', 'is_need' => true],
    ];

    public $isNeedCols = [
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
        return ['all' => $this->header, 'is_need' => $this->isNeedCols, 'headerInfo' => $this->headerInfo];
    }

    public function handleRow($companyId, $row)
    {
        $rules = [
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
        $itemInfo = $itemsService->getInfo(['item_bn' => $row['item_bn']]);
        if (!$itemInfo) {
            throw new BadRequestHttpException('商品不存在');
        }
        $itemId = $itemInfo['item_id'];
        $store = intval($row['store']);

        $itemStoreService = new ItemStoreService();
        $itemsService->updateStore($itemId, $store);
        return $itemStoreService->saveItemStore($itemId, $store);
    }
}
