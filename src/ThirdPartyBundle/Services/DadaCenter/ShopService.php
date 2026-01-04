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

namespace ThirdPartyBundle\Services\DadaCenter;

use Dingo\Api\Exception\ResourceException;
use ThirdPartyBundle\Services\DadaCenter\Api\AddShopApi;
use ThirdPartyBundle\Services\DadaCenter\Api\UpdateShopApi;
use ThirdPartyBundle\Services\DadaCenter\Client\DadaRequest;

class ShopService
{
    private $businessList = [
        '1' => '食品小吃',
        '2' => '饮料',
        '3' => '鲜花绿植',
        '8' => '文印票务',
        '9' => '便利店',
        '13' => '水果生鲜',
        '19' => '同城电商',
        '20' => '医药',
        '21' => '蛋糕',
        '24' => '酒品',
        '25' => '小商品市场',
        '26' => '服装',
        '27' => '汽修零配',
        '28' => '数码家电',
        '29' => '小龙虾',
        '50' => '个人',
        '51' => '火锅',
        '53' => '个护美妆',
        '55' => '母婴',
        '57' => '家居家纺',
        '59' => '手机',
        '61' => '家装',
        '5' => '其他'
    ];

    /**
     * 门店创建
     * @param string $companyId 企业Id
     * @param array $data 门店信息
     * @return mixed 创建结果
     */
    public function createShop($companyId, $data)
    {
        // ShopEx EcShopX Core Module
        $params = [];
        foreach ($data as $key => $value) {
            $params[] = [
                'station_name' => $value['name'],
                'business' => $value['business'],
                'city_name' => $value['city'],
                'area_name' => $value['area'],
                'station_address' => $value['address'],
                'lng' => $value['lng'],
                'lat' => $value['lat'],
                'contact_name' => $value['contact'],
                'phone' => $value['mobile'],
                'origin_shop_id' => $value['shop_code']
            ];
        }
        $addShopApi = new AddShopApi(json_encode($params));
        $dadaClient = new DadaRequest($companyId, $addShopApi);
        $resp = $dadaClient->makeRequest();
        if ($resp->status == 'fail') {
            throw new ResourceException($resp->msg);
        }
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
            'origin_shop_id' => $data['shop_code'],
            'station_name' => $data['name'],
            'business' => $data['business'],
            'city_name' => $data['city'],
            'area_name' => $data['area'],
            'station_address' => $data['address'],
            'lng' => $data['lng'],
            'lat' => $data['lat'],
            'contact_name' => $data['contact'],
            'phone' => $data['mobile'],
            'status' => empty($data['is_dada']) ? 0 : 1,
        ];
        $addShopApi = new UpdateShopApi(json_encode($params));
        $dadaClient = new DadaRequest($companyId, $addShopApi);
        $resp = $dadaClient->makeRequest();
        if ($resp->status == 'fail') {
            if ($resp->code == '2402') {
                $addParam[] = $data;
                $this->createShop($companyId, $addParam);
            } else {
                throw new ResourceException($resp->msg);
            }
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
