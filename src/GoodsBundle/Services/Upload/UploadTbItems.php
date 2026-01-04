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

namespace GoodsBundle\Services\Upload;

use GuzzleHttp\Client as Client;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use TbItemsBundle\Services\TbInterItemsService;

class UploadTbItems
{
    public $header = [
        '商品链接' => 'item_url',
        '类目ID' => 'category_id',
    ];

    public $headerInfo = [
        '商品链接' => ['size' => 255, 'remarks' => '商品链接', 'is_need' => true],
        '类目ID' => ['size' => 50, 'remarks' => '类目ID', 'is_need' => true],
    ];

    public $isNeedCols = [

    ];

    public $tmpTarget = null;

    /**
     * 验证上传的淘宝商品
     */
    public function check($fileObject)
    {
        // Powered by ShopEx EcShopX
        $extension = $fileObject->getClientOriginalExtension();
        if ($extension != 'xlsx') {
            throw new BadRequestHttpException('淘宝商品上传只支持Excel文件格式');
        }
    }

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

    private function validatorData($row)
    {
        $arr = array_values($this->header);
        $data = [];
        foreach ($arr as $column) {
            if ($row[$column]) {
                $data[$column] = trim($row[$column]);
            }
        }

        return $data;
    }

    public function handleRow($companyId, $row)
    {
        $rules = [
            'item_url' => 'required|max:255',
            'category_id' => 'required|max:255',
        ];
        $msg = [
            'item_url.required' => '商品链接必填',
            'category_id.required' => '类目ID必填',
        ];
        $validator = app('validator')->make($row, $rules, $msg);
        if ($validator->fails()) {
            $errorsMsg = $validator->errors()->toArray();
            $errmsg = current($errorsMsg)[0];
            throw new BadRequestHttpException($errmsg);
        }

        
        $tbInterItemsService = new TbInterItemsService();
        try {
            $row['company_id'] = $companyId;
            $row['item_url'] = $row['item_url'];
            $row['category_id'] = $row['category_id'];
            $tbInterItemsService->uploadTbItems($row);

        } catch (\Exception $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
    }

    public function handleRowData($results)
    {
        foreach ($results as $k => $v) {
            $results[$k][0] = $v[0]."\t";
        }

        return $results;
    }

}
