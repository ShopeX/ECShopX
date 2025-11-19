<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ThirdPartyBundle\Services\ShansongCenter;

use Dingo\Api\Exception\ResourceException;
use ThirdPartyBundle\Services\ShansongCenter\Api\AddStoresApi;
use ThirdPartyBundle\Services\ShansongCenter\Api\StoreOperationApi;
use ThirdPartyBundle\Services\ShansongCenter\Client\Request;

class ShopService
{
    private $businessList = [
        '1' => '文件',
        '3' => '数码',
        '5' => '蛋糕',
        '6' => '餐饮',
        '7' => '鲜花',
        '9' => '汽配',
        '10' => '其他',
        '12' => '母婴',
        '13' => '医药健康',
        '15' => '商超',
        '16' => '水果',
    ];

    /**
     * 门店创建
     * @param string $companyId 企业Id
     * @param array $data 门店信息
     * @return mixed 创建结果
     */
    public function createShop($companyId, $data)
    {
        $params = [];
        foreach ($data as $key => $value) {
            $params[] = [
                'thirdStoreId' => $value['shop_code'],
                'storeName' => $value['name'],
                'cityName' => $value['city'],
                'address' => $value['address'],
                'addressDetail' => $value['house_number'],
                'latitude' => $value['lat'],
                'longitude' => $value['lng'],
                'phone' => $value['mobile'],
                'goodType' => $value['business'],
            ];
        }
        $addStoresApi = new AddStoresApi(json_encode($params));
        $client = new Request($companyId, $addStoresApi);
        $resp = $client->makeRequest();
        if ($resp->status == 'fail') {
            throw new ResourceException($resp->msg);
        }

        // 返回字段mapping达达返回字段
        $successList = [];
        foreach ($resp->result['successList'] as $row) {
            $successList[] = [
                'phone' => $row['dto']['phone'],
                'business' => $row['dto']['goodType'],
                'lng' => $row['dto']['longitude'],
                'lat' => $row['dto']['latitude'],
                'stationName' => $row['dto']['storeName'],
                'originShopId' => $row['storeId'],
                'contactName' => '',
                'stationAddress' => $row['dto']['address'],
                'cityName' => $row['dto']['cityName'],
                'areaName' => '',
            ];
        }
        $resp->result['successList'] = $successList;

        $failedList = [];
        foreach ($resp->result['failList'] as $row) {
            $failedList[] = [
                'shopNo' => '',
                'msg' => $row['reason'],
                'shopName' => $row['dto']['storeName']
            ];
        }
        $resp->result['failedList'] = $failedList;

        return $resp->result;
    }

    /**
     * 门店更新
     * @param string $companyId 企业Id
     * @param array $data 门店信息
     * @return mixed 更新结果
     */
    public function updateShop($companyId, $data)
    {
        $params = [
            'storeId' => $data['shansong_store_id'],
            'storeName' => $data['name'],
            'cityName' => $data['city'],
            'address' => $data['address'],
            'addressDetail' => $data['house_number'],
            'latitude' => $data['lat'],
            'longitude' => $data['lng'],
            'phone' => $data['mobile'],
            'goodType' => $data['business'],
            'operationType' => 2,
        ];
        $storeOperationApi = new StoreOperationApi(json_encode($params));
        $client = new Request($companyId, $storeOperationApi);
        $resp = $client->makeRequest();
        if ($resp->status == 'fail') {
            throw new ResourceException($resp->msg);
        }
        return $resp->result;
    }

    /**
     * 获取业务类型列表
     * @return array 业务类型列表
     */
    public function getBusinessList()
    {
        return $this->businessList;
    }
}
