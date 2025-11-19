<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace SystemLinkBundle\Jobs;

use EspierBundle\Jobs\Job;
use SystemLinkBundle\Services\WdtErp\Client\WdtErpClient;
use SystemLinkBundle\Services\WdtErp\ItemService;
use SystemLinkBundle\Services\WdtErpSettingService;
use DistributionBundle\Services\DistributorService;
use Exception;

class UploadItemsToWdtErpJob extends Job
{
    protected $companyId;
    protected $itemIds;
    protected $distributorId;

    public function __construct($companyId, $itemIds, $distributorId)
    {
        $this->companyId = $companyId;
        $this->itemIds = $itemIds;
        $this->distributorId = $distributorId;
    }

    /**
     * 运行任务。
     *
     * @return bool
     */
    public function handle()
    {
        // ShopEx EcShopX Business Logic Layer
        $companyId = $this->companyId;

        // 判断是否开启旺店通ERP
        $wdtErpSettingService = new WdtErpSettingService();
        $setting = $wdtErpSettingService->getWdtErpSetting($companyId);
        if (!isset($setting) || !$setting['is_open']) {
            app('log')->debug('companyId:'.$companyId.",msg:未开启旺店通ERP");
            return true;
        }

        $shopNo = $setting['shop_no'];
        if ($this->distributorId > 0) {
            $distributorService = new DistributorService();
            $distributorInfo = $distributorService->getInfoSimple(['company_id' => $companyId, 'distributor_id' => $this->distributorId]);
            if (!$distributorInfo || !$distributorInfo['wdt_shop_no']) {
                app('log')->debug('companyId:'.$companyId.",msg:店铺没有绑定旺店通ERP门店");
                return true;
            }

            $shopNo = $distributorInfo['wdt_shop_no'];
        }

        $itemService = new ItemService();
        $wdtErpClient = new WdtErpClient(config('wdterp.api_base_url'), $setting['sid'], $setting['app_key'], $setting['app_secret']);

        foreach ($this->itemIds as $itemId) {
            $itemStruct = $itemService->getItemStruct($companyId, $itemId, $this->distributorId);
            if (!$itemStruct) {
                app('log')->debug('获取商品信息失败:companyId:'.$companyId.",itemId:".$itemId);
                continue;
            }

            $this->goodsPush($wdtErpClient, $itemStruct['goodsPush']['goods'], $itemStruct['goodsPush']['specList']);
            $this->apiGoodsUpload($wdtErpClient, $shopNo, $itemStruct['apiGoodsUpload']);
        }

        return true;
    }

    /**
     * @param WdtErpClient $wdtErpClient
     * @param $goods
     * @param $specList
     * @return void
     */
    private function goodsPush(WdtErpClient $wdtErpClient, $goods, $specList)
    {
        $method = config('wdterp.methods.item_add');
        try {
            app('log')->debug('UploadItemsToWdtErpJob=>method:'.$method.",request:\r\n". var_export(['goods' => $goods, 'specList' => $specList], 1));
            $result = $wdtErpClient->call($method, $goods, $specList);
            app('log')->debug('UploadItemsToWdtErpJob=>method:'.$method.",result:\r\n". var_export($result, 1));
        } catch (Exception $e) {
            app('log')->debug('旺店通请求失败:'. $e->getMessage());
        }
    }

    /**
     * @param WdtErpClient $wdtErpClient
     * @param $shopNo
     * @param $goodsList
     * @return void
     */
    private function apiGoodsUpload(WdtErpClient $wdtErpClient, $shopNo, $goodsList)
    {
        $method = config('wdterp.methods.item_api_add');
        try {
            $param = new \stdClass();
            $param->shop_no = $shopNo;
            $param->goods_list = $goodsList;
            app('log')->debug('UploadItemsToWdtErpJob=>method:'.$method.",request:\r\n". var_export($param, 1));
            $result = $wdtErpClient->call($method, $param);
            app('log')->debug('UploadItemsToWdtErpJob=>method:'.$method.",result:\r\n". var_export($result, 1));
        } catch (Exception $e) {
            app('log')->debug('旺店通请求失败:'. $e->getMessage());
        }
    }
}
