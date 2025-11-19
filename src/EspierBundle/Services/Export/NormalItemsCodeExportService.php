<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Services\Export;

use phpqrcode;; // qrcode类库
use EasyWeChat\Kernel\Http\StreamResponse;
use EspierBundle\Interfaces\ExportFileInterface;
use EspierBundle\Services\ExportFileService;

use GoodsBundle\Services\ItemsService;
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
        $source = $filter['source'];// item:商品；distributor:店铺商品
        unset($filter['source'], $filter['export_type'], $filter['wxaappid']);
        if ($source == 'distributor' && empty($filter['distributor_id'])) {
            return [];
        }
        if(isset($filter['operator_type'])){
            $this->operator_type = $filter['operator_type'];
            unset($filter['operator_type']);
        }
        if (isset($filter['item_id']) && $filter['item_id']) {
            $filter = [
                'company_id' => $filter['company_id'],
                'item_id' => $filter['item_id'],
                'is_default' => $filter['is_default'],
                'distributor_id' => $filter['distributor_id'] ?? 0,
                'supplier_id'=>$filter['supplier_id'] ?? 0
            ];
            $filter['default_item_id'] = $filter['item_id'];
            unset($filter['item_id']);
        }
        app('log')->info('this->operator_type===>'.$this->operator_type);
        $itemsService = new ItemsService();
        // if($this->operator_type == 'supplier'){
        //     $itemsService = new SupplierItemsService();
        // }else{
        //     unset($filter['supplier_id']);
        // }
        $count = $itemsService->getItemCount($filter);
        app('log')->info('filter====>'.var_export($filter,1));
        app('log')->info('count===>'.$count);
        if (!$count) {
            return [];
        }
        $tarName = date('YmdHis').'productcode_'.$export_type;
        if ($source == 'distributor') {
            $tarName .= '_distributor';
        }
        $dirName = 'uploads/'.$tarName.'/';
        $itemList = $this->getLists($filter, $count);
        if ($export_type == 'h5') {
            if ($source == 'distributor') {
                $this->codeH5Distributor($dirName, $itemList);
            } else {
                $this->codeH5($dirName, $itemList);
            }
        } else {
            if ($source == 'distributor') {
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
            $itemsService = new ItemsService();
            // if($this->operator_type == 'supplier'){
            //     $itemsService = new SupplierItemsService();
            // }
            $limit = 2;
            $fileNum = ceil($count / $limit);
            for ($page = 1; $page <= $fileNum; $page++) {
                $itemData = [];
                $result = $itemsService->getLists($filter, 'company_id,item_id,goods_id,item_bn,distributor_id', $page, $limit);
                foreach ($result as $key => $items) {
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
