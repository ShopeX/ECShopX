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

namespace CompanysBundle\Services;

use CompanysBundle\Entities\Setting;
use Dingo\Api\Exception\ResourceException;
use GoodsBundle\Entities\Items;
use GoodsBundle\Repositories\ItemsRepository;
use GoodsBundle\Services\MultiLang\MagicLangTrait;
use ThirdPartyBundle\Entities\CompanyRelKuaizhen;
use ThirdPartyBundle\Repositories\CompanyRelKuaizhenRepository;

class SettingService
{
    use MagicLangTrait;
    private $entityRepository;
    /** @var CompanyRelKuaizhenRepository $companyRelKuaizhenRepository */
    private $companyRelKuaizhenRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(Setting::class);
        $this->companyRelKuaizhenRepository = app('registry')->getManager('default')->getRepository(CompanyRelKuaizhen::class);
    }

    /**
     * Dynamically call the shopsservice instance.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        // ShopEx EcShopX Service Component
        return $this->entityRepository->$method(...$parameters);
    }

    public function selfdeliveryAddressSave($companyId, $params)
    {
        $key = 'selfDeliveryAddress:' . $companyId;
        $params = json_encode($params);
        app('redis')->connection('companys')->set($key, $params);
        return true;
    }

    public function selfdeliveryAddressGet($companyId)
    {
        // IDX: 2367340174
        $key = 'selfDeliveryAddress:' . $companyId;
        $params = app('redis')->connection('companys')->get($key);
        $params = json_decode($params, 1);
        if ($params) {
            return $params;
        }
        return [];
    }

    /**
     * 获取会员白名单设置
     * @param $companyId :企业Id
     * @return array|mixed
     */
    public function getWhitelistSetting($companyId)
    {
        $key = 'WhitelistSetting:' . $companyId;
        $inputData = app('redis')->connection('companys')->get($key);
        $inputData = $inputData ? json_decode($inputData, true) : [];
        $inputData['whitelist_status'] = $inputData['whitelist_status'] ?? false;
        $inputData['whitelist_tips'] = $inputData['whitelist_tips'] ?? '登录失败，手机号不在白名单内！';
        return $inputData;
    }

    /**
     * 获取预售提货码状态
     * @param $companyId
     * @return array|mixed
     */
    public function presalePickupcodeGet($companyId)
    {
        $key = 'PresalePickupcodeSetting:' . $companyId;
        $data = app('redis')->connection('companys')->get($key);
        $data = $data ? json_decode($data, true) : ['pickupcode_status' => false];
        return $data;
    }


    /**
     * 获取前端店铺展示关闭状态
     * @param $companyId
     * @return array|mixed
     */
    public function getNostoresSetting($companyId)
    {
        $key = 'NostoresSetting:' . $companyId;
        $inputData = app('redis')->connection('companys')->get($key);
        $inputData = $inputData ? json_decode($inputData, true) : [];
        $inputData['nostores_status'] = ($inputData['nostores_status'] ?? 'false') === 'true' ? true : false;
        return $inputData;
    }

    /**
     * 设置前端店铺展示关闭状态
     * @param $companyId
     * @param $data
     * @return bool
     */
    public function setNostoresSetting($companyId, $data)
    {
        $key = 'NostoresSetting:' . $companyId;
        app('redis')->connection('companys')->set($key, json_encode($data));
        return true;
    }

    /**
     * 获取储值功能状态
     * @param $companyId
     * @return array|mixed
     */
    public function getRechargeSetting($companyId)
    {
        $key = 'PresaleRechargeSetting:' . $companyId;
        $inputData = app('redis')->connection('companys')->get($key);
        $inputData = $inputData ? json_decode($inputData, true) : ['recharge_status' => true];
        return $inputData;
    }

    /**
     * 设置储值功能状态
     * @param $companyId
     * @param $data
     * @return bool
     */
    public function setRechargeSetting($companyId, $inputdata)
    {
        $key = 'PresaleRechargeSetting:' . $companyId;
        if (isset($inputdata['recharge_status'])) {
            $data['recharge_status'] = ($inputdata['recharge_status'] == 'false') ? false : true;
            app('redis')->connection('companys')->set($key, json_encode($data));
        }
        return $data;
    }

    /**
     * 获取库存显示状态
     * @param $companyId
     * @return array|mixed
     */
    public function getItemStoreSetting($companyId)
    {
        $key = 'ItemStoreSetting:' . $companyId;
        $inputData = app('redis')->connection('companys')->get($key);
        $inputData = $inputData ? json_decode($inputData, true) : ['item_store_status' => true];
        return $inputData;
    }

    /**
     * 设置库存显示状态
     * @param $companyId
     * @param $data
     * @return bool
     */
    public function setItemStoreSetting($companyId, $inputdata)
    {
        $key = 'ItemStoreSetting:' . $companyId;
        if (isset($inputdata['item_store_status'])) {
            $data['item_store_status'] = ($inputdata['item_store_status'] ?? false) === true;
            // $data['item_store_status'] = ($inputdata['item_store_status'] == 'false') ? false : true;
            app('redis')->connection('companys')->set($key, json_encode($data));
        }
        return $data;
    }


    /**
     * 设置库存显示状态
     * @param $companyId
     * @param $data
     * @return bool
     */
    public function setOpenDistributorDivided($companyId, $inputdata)
    {
        $key = 'OpenDistributorDivided:' . $companyId;
        if (isset($inputdata['open_distributor_divided'])) {
            $data['open_distributor_divided'] = $inputdata['open_distributor_divided'];
            app('redis')->connection('companys')->set($key, json_encode($data));
        }
        return $data;
    }

    /**
     * 获取库存显示状态
     * @param $companyId
     * @return array|mixed
     */
    public function getOpenDistributorDivided($companyId)
    {
        $key = 'OpenDistributorDivided:' . $companyId;
        $inputData = app('redis')->connection('companys')->get($key);
        if(empty($inputData)){
            return ['status'=>false,'template_id'=>0];
        }
        $inputData = json_decode($inputData, true);
        if(!empty($inputData['open_distributor_divided']['status']) && $inputData['open_distributor_divided']['status'] == 'true'){
            $inputData['open_distributor_divided']['status'] = true;
        }else{
            $inputData['open_distributor_divided']['status'] = false;
        }
        return $inputData['open_distributor_divided'];
    }

    /**
     * 获取商品销量显示状态
     * @param $companyId
     * @return array|mixed
     */
    public function getItemSalesSetting($companyId)
    {
        $key = 'ItemSalesSetting:' . $companyId;
        $inputData = app('redis')->connection('companys')->get($key);
        $inputData = $inputData ? json_decode($inputData, true) : ['item_sales_status' => true];
        return $inputData;
    }

    /**
     * 设置商品销量显示状态
     * @param $companyId
     * @param $data
     * @return bool
     */
    public function setItemSalesSetting($companyId, $inputdata)
    {
        $key = 'ItemSalesSetting:' . $companyId;
        if (isset($inputdata['item_sales_status'])) {
            // $data['item_sales_status'] = ($inputdata['item_sales_status'] == 'false') ? false : true;
            $data['item_sales_status'] = ($inputdata['item_sales_status'] ?? false) === true;
            app('redis')->connection('companys')->set($key, json_encode($data));
        }
        return $data;
    }
    public function checkoutInvoiceStatus($orderInfo){

        app('log')->info(':orderInfo:'.__FUNCTION__.__LINE__.':orderInfo:' . json_encode($orderInfo));
        $invoiceSetting = $this->getInvoiceSetting($orderInfo['company_id']);
        app('log')->info(':invoiceSetting:'.__FUNCTION__.__LINE__.':invoiceSetting:' . json_encode($invoiceSetting));
        if(empty($invoiceSetting['invoice_status'])){
            return 0;
        }
        if($invoiceSetting['invoice_status'] == 0){
            app('log')->info(':invoiceSetting:'.__FUNCTION__.__LINE__.':invoiceSetting:' . json_encode($invoiceSetting['invoice_status']));
            return 0;
        }

        if($orderInfo['order_status'] == 'CANCEL' ){
            return 0;
        }
        if( isset($orderInfo['left_aftersales_num']) && $orderInfo['left_aftersales_num'] = 0 ){
            return 0;
        }
        if( isset($orderInfo['cancel_status']) && $orderInfo['cancel_status'] == 'WAIT_PROCESS' ){
            return 0;
        }

        // pay status !== "NOTPAY" // - **apply_type**：开票申请方式（1=结算页，2=已支付订单）
        if($orderInfo['order_status'] == 'NOTPAY' && isset($invoiceSetting['apply_type']) && $invoiceSetting['apply_type'] == 2){
            app('log')->info(':orderInfo:'.__FUNCTION__.__LINE__.':orderInfo:' . json_encode($orderInfo['order_status']));
            app('log')->info(':invoiceSetting:'.__FUNCTION__.__LINE__.':invoiceSetting:' . json_encode($invoiceSetting['apply_type']));
            return 0;
        }
        return 1;
    }
    /**
     * 获取发票选项显示状态
     * @param $companyId
     * @return array|mixed
     */
    public function getInvoiceSetting($companyId)
    {
        // $default = [
        //     'invoice_status' => 'false',
        //     'invoice_limit' => 'item', //item sku维度 order 订单维度
        //     'invoice_method' => 'offline',// offline 线下 online 线上
        //     'invoice_open_term' => 6, //可开票期限 0不限制 月
        // ];
        // 验证参数
        // - **invoice_status**：是否启用开票功能（true/false）
        // - **invoice_limit**：开票维度（item=SKU维度，order=订单维度）
        // - **invoice_method**：开票渠道（offline=线下，online=线上）
        // - **channel**：开票渠道（1=线下，2=线上），与 invoice_method 可做映射
        // - **freight_invoice**：运费是否开票（1=不支持，2=支持）
        // - **freight_name**：运费开票名称（freight_invoice=2 时必填）
        // - **apply_type**：开票申请方式（1=结算页，2=已支付订单）
        // - **invoice_open_term**：可开票期限（月，0为不限制）
        // - **special_invoice**：专用发票展示（1=企业抬头可选，2=不可选）
        // - **apply_node**：申请开票节点（1=确认收货，2=过售后期）


        $key = 'InvoiceSetting:' . $companyId;
        $inputData = app('redis')->connection('companys')->get($key);
        $inputData = $inputData ? json_decode($inputData, true) : [];
        // $inputData = $inputData ? json_decode($inputData, true) : $default;
        // $inputData = array_merge($default, $inputData);
        // $inputData['invoice_status'] = $inputData['invoice_status'] == 'false' ? false : true;
        return $inputData;
    }

    /**
     * 设置发票选项显示状态
     * @param $companyId
     * @param $data
     * @return bool
     */
    public function setInvoiceSetting($companyId, $inputdata)
    {

        $key = 'InvoiceSetting:' . $companyId;
        app('redis')->connection('companys')->set($key, json_encode($inputdata));
        app('log')->info(__FUNCTION__.':'.__LINE__.':key:'.$key);
        app('log')->info(__FUNCTION__.':'.__LINE__.':inputdata:'.json_encode($inputdata));
        return true;
    }

    /**
     * 获取商品分享设置
     * @param $companyId  企业ID
     * @return array|mixed
     */
    public function getItemShareSetting($companyId)
    {
        $default = [
            'is_open' => 'false',
            'valid_grade' => [],
            'msg' => '',
            'page' => []
        ];
        $key = 'ItemShareSetting:' . $companyId;
        $inputData = app('redis')->connection('companys')->get($key);
        $inputData = $inputData ? json_decode($inputData, true) : $default;
        $inputData = array_merge($default, $inputData);
        $inputData['is_open'] = $inputData['is_open'] == 'false' ? false : true;
        return $inputData;
    }

    /**
     * 保存商品分享设置
     * @param $companyId  企业ID
     * @param $inputdata  保存数据
     * @return bool
     */
    public function setItemShareSetting($companyId, $inputdata)
    {
        $key = 'ItemShareSetting:' . $companyId;
        app('redis')->connection('companys')->set($key, json_encode($inputdata));
        return true;
    }

    /**
     * 获取小程序分享参数设置
     * @param $companyId  企业ID
     * @return array|mixed
     */
    public function getShareParametersSetting($companyId)
    {
        $key = 'ShareParametersSetting:' . $companyId;
        $inputData = app('redis')->connection('companys')->get($key);
        $inputData = $inputData ? json_decode($inputData, true) : ['distributor_param_status' => false];
        return $inputData;
    }

    /**
     * 保存小程序分享参数设置
     * @param $companyId  企业ID
     * @param $inputdata  保存数据
     * @return array
     */
    public function saveShareParametersSetting($companyId, $inputdata)
    {
        $key = 'ShareParametersSetting:' . $companyId;
        $data['distributor_param_status'] = ($inputdata['distributor_param_status'] ?? false) === true;
        // $data['distributor_param_status'] = ($inputdata['distributor_param_status'] == 'false') ? false : true;
        app('redis')->connection('companys')->set($key, json_encode($data));
        return $data;
    }

    public function getDianwuSetting($companyId)
    {
        $key = 'DianwuSetting:' . $companyId;
        $inputData = app('redis')->connection('companys')->get($key);
        $inputData = $inputData ? json_decode($inputData, true) : ['dianwu_show_status' => false];
        return $inputData;
    }

    public function saveDianwuSetting($companyId, $inputdata)
    {
        $key = 'DianwuSetting:' . $companyId;
        $data['dianwu_show_status'] = ($inputdata['dianwu_show_status'] ?? false) === true;
        app('redis')->connection('companys')->set($key, json_encode($data));
        return $data;
    }

    public function getItemPriceSetting($companyId)
    {
        $key = 'ItemPriceSetting:' . $companyId;
        $data = app('redis')->connection('companys')->get($key);
        if ($data) {
            $data = json_decode($data, true);
        } else {
            $data['cart_page'] = [
                'market_price' => true,
            ];

            $data['order_page'] = [
                'market_price' => true,
            ];

            $data['item_page'] = [
                'market_price' => true,
                'member_price' => false,
                'svip_price' => false,
            ];
        }
        return $data;
    }

    public function saveItemPriceSetting($companyId, $inputdata)
    {
        $data = $this->getItemPriceSetting($companyId);
        foreach ($data as $key => $value) {
            if (isset($inputdata[$key])) {
                $data[$key] = array_merge($value, $inputdata[$key]);
            }
        }
        $key = 'ItemPriceSetting:' . $companyId;
        app('redis')->connection('companys')->set($key, json_encode($data));
        return $data;
    }

    public function getCategoryPageSetting($companyId)
    {
        $key = 'CategoryPageSetting:' . $companyId;
        $data = app('redis')->connection('companys')->get($key);
        if ($data) {
            $data = json_decode($data, true);
        } else {
            $data['style'] = 'category';
        }
        return $data;
    }

    public function saveCategoryPageSetting($companyId, $inputdata)
    {
        $data['style'] = $inputdata['style'] ?? 'category';
        $key = 'CategoryPageSetting:' . $companyId;
        app('redis')->connection('companys')->set($key, json_encode($data));
        return $data;
    }

    public function saveMedicineSetting($companyId, $inputdata)
    {
        $data['is_pharma_industry'] = $inputdata['is_pharma_industry'] ?? 0;
        $data['use_third_party_system'] = !empty($inputdata['use_third_party_system']) ? $inputdata['use_third_party_system'] : '';

        // 关闭医药行业开关，检查是否有处方药商品没有下架
        if (!$data['is_pharma_industry']) {
            /** @var ItemsRepository $itemsRep */
            $itemsRep = app('registry')->getManager('default')->getRepository(Items::class);
            $itemCount = $itemsRep->count([
                'company_id' => $companyId,
                'is_medicine' => 1,
                'is_prescription' => 1,
                'approve_status|neq' => 'instock',
            ]);
            if ($itemCount) {
                throw new ResourceException('关闭医药行业开关需要下架所有处方药商品');
            }
        }

        $key = 'PharmaIndustrySetting:' . $companyId;
        app('redis')->connection('companys')->set($key, json_encode($data));

        // 保存数据库
        if ($data['use_third_party_system'] == 'kuaizhen580' && !empty($inputdata['kuaizhen580_config'])) {
            if (!is_array($inputdata['kuaizhen580_config'])) {
                $inputdata['kuaizhen580_config'] = json_decode($inputdata['kuaizhen580_config'], true);
            }
            if (empty($inputdata['kuaizhen580_config']['clientId'])) {
                throw new ResourceException('请填写580clientId');
            }
            if (empty($inputdata['kuaizhen580_config']['clientSecret'])) {
                throw new ResourceException('请填写580clientSecret');
            }
            if (empty($inputdata['kuaizhen580_config']['storeId'])) {
                throw new ResourceException('请填写580门店Id');
            }
            $updateData = [
                'client_id' => $inputdata['kuaizhen580_config']['clientId'],
                'client_secret' => $inputdata['kuaizhen580_config']['clientSecret'],
                'kuaizhen_store_id' => $inputdata['kuaizhen580_config']['storeId'],
                'online' => env('IS_DEV_MODE', false) ? 0 : 1, // IS_DEV_MODE是否为开发环境，配置为true时调测试环境
            ];
            $config = $this->companyRelKuaizhenRepository->getInfo(['company_id' => $companyId]);
            if ($config) {
                $result = $this->companyRelKuaizhenRepository->updateOneBy(['id' => $config['id']], $updateData);
            } else {
                $updateData['online'] = env('IS_DEV_MODE', false) ? 0 : 1; // IS_DEV_MODE是否为开发环境，配置为true时调测试环境
                $updateData['is_open'] = 1;
                $updateData['company_id'] = $companyId;
                $result = $this->companyRelKuaizhenRepository->create($updateData);
            }

            $data['kuaizhen580'] = $result;
        }
        return $data;
    }

    public function getMedicineSetting($companyId)
    {
        $key = 'PharmaIndustrySetting:' . $companyId;
        $data = app('redis')->connection('companys')->get($key);
        if ($data) {
            $data = json_decode($data, true);
            // 快诊配置
            if ($data['use_third_party_system'] == 'kuaizhen580') {
                $kuaizhenConfig = $this->companyRelKuaizhenRepository->getInfo(['company_id' => $companyId]);
                $data['kuaizhen580_config']['client_id'] = $kuaizhenConfig['client_id'] ?? '';
                $data['kuaizhen580_config']['client_secret'] = $kuaizhenConfig['client_secret'] ?? '';
                $data['kuaizhen580_config']['kuaizhen_store_id'] = $kuaizhenConfig['kuaizhen_store_id'] ?? 0;
            }
        } else {
            $data = [
                'is_pharma_industry' => 0,
                'use_third_party_system' => '',
                'kuaizhen580_config' => [
                    'client_id' => '',
                    'client_secret' => '',
                    'kuaizhen_store_id' => 0,
                ],
            ];
        }
        return $data;
    }

    /**
     * 获取库存显示状态
     * @param $companyId
     * @return array|mixed
     */
    public function getItemStartNumSetting($companyId)
    {
        $key = 'ItemStartNum:' . $companyId;
        $inputData = app('redis')->connection('companys')->get($key);
        $inputData = $inputData ? json_decode($inputData, true) : ['item_start_num' => true];
        return $inputData;
    }

    public function setItemStartNumSetting($companyId, $inputdata)
    {
        $key = 'ItemStartNum:' . $companyId;
        if (isset($inputdata['item_start_num'])) {
            $data['item_start_num'] = ($inputdata['item_start_num'] == 'false') ? false : true;
            app('redis')->connection('companys')->set($key, json_encode($data));
        }
        return $data;
    }

    public function getSupplierItemStartNumSetting($companyId)
    {
        $key = 'SupplierItemStartNum:' . $companyId;
        $inputData = app('redis')->connection('companys')->get($key);
        $inputData = $inputData ? json_decode($inputData, true) : ['supplier_item_start_num' => true];
        return $inputData;
    }

    public function setSupplierItemStartNumSetting($companyId, $inputdata)
    {
        $key = 'SupplierItemStartNum:' . $companyId;
        if (isset($inputdata['supplier_item_start_num'])) {
            $data['supplier_item_start_num'] = ($inputdata['supplier_item_start_num'] == 'false') ? false : true;
            app('redis')->connection('companys')->set($key, json_encode($data));
        }
        return $data;
    }

    /**
     * 获取PC商城和H5商城隐私设置
     * @param $companyId
     * @return array|mixed
     */
    public function getPrivacySetting($companyId)
    {
        $lang = $this->getLang();
        $key = 'PrivacySetting:' . $companyId.'_'.$lang;
        $inputData = app('redis')->connection('companys')->get($key);
        $inputData = $inputData ? json_decode($inputData, true) : [
            'pc_privacy_content' => '',
            'h5_privacy_content' => ''
        ];
        return $inputData;
    }

    /**
     * 设置PC商城和H5商城隐私设置
     * @param $companyId
     * @param $inputdata
     * @return array
     */
    public function setPrivacySetting($companyId, $inputdata)
    {
        $lang = $this->getLang();
        $key = 'PrivacySetting:' . $companyId.'_'.$lang;
        // $key = 'PrivacySetting:' . $companyId;
        // 先获取现有数据
        $data = $this->getPrivacySetting($companyId);
        // 更新传入的字段
        if (isset($inputdata['pc_privacy_content'])) {
            $data['pc_privacy_content'] = $inputdata['pc_privacy_content'];
        }
        if (isset($inputdata['h5_privacy_content'])) {
            $data['h5_privacy_content'] = $inputdata['h5_privacy_content'];
        }
        app('redis')->connection('companys')->set($key, json_encode($data));
        return $data;
    }

}
