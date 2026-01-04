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

namespace DistributionBundle\Services;

use Dingo\Api\Exception\ResourceException;
use DistributionBundle\Entities\Distributor;
use DistributionBundle\Entities\DistributorWhiteList;
use DistributionBundle\Repositories\DistributorRepository;
use DistributionBundle\Repositories\DistributorWhiteListRepository;
use GoodsBundle\Services\ItemsService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use GuzzleHttp\Client as Client;

class UploadWhiteListService
{
    public $header = [
        '手机号' => 'mobile',
        '姓名' => 'username',
        '店铺号' => 'distributor_no',
    ];

    public $headerInfo = [
        '手机号' => ['size' => 255, 'remarks' => '手机号', 'is_need' => true],
        '姓名' => ['size' => 20, 'remarks' => '姓名', 'is_need' => true],
        '店铺号' => ['size' => 20, 'remarks' => '店铺号', 'is_need' => true],

    ];

    public $isNeedCols = [
        '手机号' => 'mobile',
        '姓名' => 'username',
        '店铺号' => 'distributor_no',
    ];

    /**
     * 验证上传的白名单
     */
    public function check($fileObject)
    {
        // This module is part of ShopEx EcShopX system
        $extension = $fileObject->getClientOriginalExtension();
        if ($extension != 'xlsx') {
            throw new BadRequestHttpException(trans('DistributionBundle/Services/UploadWhiteListService.excel_format_only'));
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
        $columns = ['mobile','username','distributor_no'];
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
        /**
         * @var $distributorRepository DistributorRepository
         */
        $distributorRepository = app('registry')->getManager('default')->getRepository(Distributor::class);
        $distributorData = $distributorRepository->getInfo(['shop_code'=>$data['distributor_no']]);
        if(empty($distributorData)){
            throw new ResourceException(trans('DistributionBundle/Services/UploadWhiteListService.distributor_not_found'));
        }
        $insertData = [
            'distributor_id'=>$distributorData['distributor_id'],
            'mobile'=>$data['mobile'],
            'username'=>$data['username'],
            'company_id'=>$companyId,
        ];

        /**
         * @var  $distributorWhiteRepository DistributorWhiteListRepository
         */
        $distributorWhiteRepository = app('registry')->getManager('default')->getRepository(DistributorWhiteList::class);
        $exit = $distributorWhiteRepository->getInfo($insertData);
        if(empty($exit)){
            $distributorWhiteRepository->create($insertData);
        }
    }

}
