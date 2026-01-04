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

namespace EspierBundle\Services\Export;

use phpqrcode;; // qrcode类库
use EasyWeChat\Kernel\Http\StreamResponse;
use EspierBundle\Interfaces\ExportFileInterface;
use EspierBundle\Services\ExportFileService;

use GoodsBundle\Services\ItemsService;
use SupplierBundle\Services\SupplierItemsService;
use CompanysBundle\Services\CompanysService;
use DistributionBundle\Services\DistributorService;

/**
 * 导出商品码（太阳码或H5二维码）
 */
class NormalItemsCodeExportService implements ExportFileInterface
{
    public $operator_type='';
    public function exportData($filter)
    {
        unset($filter['merchant_id']);
        $export_type = $filter['export_type'];
        $wxaappid = $filter['wxaappid'] ?? '';
        $item_source = $filter['item_source'] ?? 'item';// item:商品；distributor:店铺商品
        unset($filter['item_source'], $filter['export_type'], $filter['wxaappid']);
        if ($item_source == 'distributor' && empty($filter['distributor_id'])) {
            return [];
        }
        if(isset($filter['operator_type'])){
            $this->operator_type = $filter['operator_type'];
            unset($filter['operator_type']);
        }
        if (isset($filter['item_id']) && $filter['item_id']) {
            // 保留 supplier_id 相关的条件（包括 supplier_id|gt 等）
            $supplierFilter = [];
            foreach ($filter as $key => $value) {
                if (strpos($key, 'supplier_id') === 0) {
                    $supplierFilter[$key] = $value;
                }
            }
            $filter = [
                'company_id' => $filter['company_id'],
                'item_id' => $filter['item_id'],
                'is_default' => $filter['is_default'],
                'distributor_id' => $filter['distributor_id'] ?? 0,
            ];
            // 合并 supplier_id 相关条件
            $filter = array_merge($filter, $supplierFilter);
            $filter['default_item_id'] = $filter['item_id'];
            unset($filter['item_id']);
        }
        app('log')->info('this->operator_type===>'.$this->operator_type);
        // 移除业务逻辑参数，避免传递到数据库查询
        unset($filter['isGetSkuList']);
        if($this->operator_type == 'supplier'){
            $itemsService = new SupplierItemsService();
        } else {
            $itemsService = new ItemsService();
        }
        $count = $itemsService->getItemCount($filter);
        app('log')->info('NormalItemsCodeExportService:filter====>'.json_encode($filter));
        app('log')->info('NormalItemsCodeExportService:count===>'.$count);
        if (!$count) {
            return [];
        }
        $tarName = date('YmdHis').'productcode_'.$export_type;
        if ($item_source == 'distributor') {
            $tarName .= '_distributor';
        }
        $dirName = 'uploads/'.$tarName.'/';
        $itemList = $this->getLists($filter, $count);
        if ($export_type == 'h5') {
            if ($item_source == 'distributor') {
                $this->codeH5Distributor($dirName, $itemList);
            } else {
                $this->codeH5($dirName, $itemList);
            }
        } else {
            if ($item_source == 'distributor') {
                $this->codeWxaDistributor($dirName, $itemList, $wxaappid);
            } else {
                $this->codeWxa($dirName, $itemList, $wxaappid);
            }
        }
        // 打包下载
        $exportService = new ExportFileService();
        $result = $exportService->exportItemCode($dirName, $tarName);
        return $result;
    }

    private function getLists($filter, $count)
    {
        if ($count > 0) {
            $itemData = [];
            if ($filter['distributor_id'] ?? 0) {
                $distributorService = new DistributorService();

                $distributorList = $distributorService->getDistributorOriginalList(['distributor_id' => $filter['distributor_id'], 'company_id' => $filter['company_id']], 1, -1);
                $_distributorList = array_column($distributorList['list'], null, 'distributor_id');
            }
            if($this->operator_type == 'supplier'){
                $itemsService = new SupplierItemsService();
            } else {
                $itemsService = new ItemsService();
            }
            $limit = 2;
            $fileNum = ceil($count / $limit);
            for ($page = 1; $page <= $fileNum; $page++) {
                $itemData = [];
                $result = $itemsService->getItemsList($filter, $page, $limit);
                foreach ($result['list'] as $key => $items) {
                    $itemData[$key] = $items;
                    $itemData[$key]['distributor_name'] = $_distributorList[$items['distributor_id']]['name'] ?? '';
                }
                yield $itemData;
            }
        }
    }

    /**
     * 保存小程序码到目录（根据店铺做为目录）
     * @param  string $dirName  本地存储的目录路径
     * @param  array $itemList 商品列表
     * @param  string $wxaappid 小程序appid
     */
    private function codeWxaDistributor($dirName, $itemList, $wxaappid)
    {
        $itemsService = new ItemsService();
        // if($this->operator_type == 'supplier'){
        //     $itemsService = new SupplierItemsService();
        // }
        foreach ($itemList as $data) {
            foreach ($data as $item) {
                // 生成小程序码
                $response = $itemsService->getDistributionGoodsWxaCode($wxaappid, $item['item_id'], $item['distributor_id']);

                $_dirName = $dirName . $item['distributor_name'] . '_' . $item['distributor_id'] . '/';
                $fileDir = storage_path($_dirName);
                if (!is_dir($fileDir)) {
                    mkdir($fileDir, 0777, true);
                }
                $filename = $item['item_bn'].'.png';
                //保存文件到本地
                if ($response instanceof StreamResponse) {
                    $response->saveAs($fileDir,$filename);  //保存文件的操作
                }
            }

        }

        return true;;
    }

    /**
     * 保存小程序码到目录
     * @param  string $dirName  本地存储的目录路径
     * @param  array $itemList 商品列表
     * @param  string $wxaappid 小程序appid
     */
    private function codeWxa($dirName, $itemList, $wxaappid)
    {
        $fileDir = storage_path($dirName);
        if (!is_dir($fileDir)) {
            mkdir($fileDir, 0777, true);
        }
        $itemsService = new ItemsService();
        // if($this->operator_type == 'supplier'){
        //     $itemsService = new SupplierItemsService();
        // }
        foreach ($itemList as $data) {
            foreach ($data as $item) {
                // 生成小程序码
                $response = $itemsService->getDistributionGoodsWxaCode($wxaappid, $item['item_id'], 0);
                $filename = $item['item_bn'].'.png';
                //保存文件到本地
                if ($response instanceof StreamResponse) {
                    $response->saveAs($fileDir,$filename);  //保存文件的操作
                }
            }

        }

        return true;
    }

    /**
     * 保存H5二维码图片到目录（根据店铺做为目录）
     * @param  string $dirName  本地存储的目录路径
     * @param  array $itemList 商品列表
     */
    private function codeH5Distributor($dirName, $itemList)
    {

        foreach ($itemList as $data) {
            foreach ($data as $item) {
                $_dirName = $dirName . $item['distributor_name'] . '_' . $item['distributor_id'] . '/';
                $fileDir = storage_path($_dirName);
                if (!is_dir($fileDir)) {
                    mkdir($fileDir, 0777, true);
                }
                $filename = $fileDir.$item['item_bn'].'.png';
                $h5url = $this->getH5Url($item['company_id'], $item['item_id'], $item['distributor_id']);
                // 根据h5url,生成二维码
                $this->qrCode($filename, $h5url);
            }

        }

        return true;
    }

    /**
     * 保存H5二维码图片到目录
     * @param  string $dirName  本地存储的目录路径
     * @param  array $itemList 商品列表
     */
    private function codeH5($dirName, $itemList)
    {
        $fileDir = storage_path($dirName);
        if (!is_dir($fileDir)) {
            mkdir($fileDir, 0777, true);
        }
        foreach ($itemList as $data) {
            foreach ($data as $item) {
                $filename = $fileDir.$item['item_bn'].'.png';
                $h5url = $this->getH5Url($item['company_id'], $item['item_id'], $item['distributor_id']);
                // 根据h5url,生成二维码
                $this->qrCode($filename, $h5url);
            }
        }
        return true;
    }

    /**
     * 生成二维码
     * @param  string $filename 文件名称
     * @param  string $content    二维码内容
     */
    private function qrCode($filename, $content){
        $img = new \QRcode();
        $errorCorrectionLevel = 'L';//容错级别
        $matrixPointSize = 6; // 生成图片大小
        //生成二维码图片
        $img->png($content, $filename, $errorCorrectionLevel, $matrixPointSize, 2);
        return true;
    }

    /**
     * 获取商品H5url,获取域名设置中的h5_domain,再根据商品ID、店铺ID，拼接url
     * @param  string  $companyId     企业ID
     * @param  string  $itemId        商品ID
     * @param  string $distributorId 店铺ID
     */
    private function getH5Url($companyId, $itemId, $distributorId = 0)
    {
        $companysService = new CompanysService();
        $domainInfo = $companysService->getDomainInfo(['company_id' => $companyId]);
        $h5urlDomain = $domainInfo['h5_domain'] != "" ? $domainInfo['h5_domain'] : $domainInfo['h5_default_domain'];
        $h5url = sprintf('https://%s/pages/item/espier-detail?id=%s&dtid=%s', $h5urlDomain, $itemId, $distributorId);
        return $h5url;
    }
}
