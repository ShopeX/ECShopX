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

namespace ThirdPartyBundle\Services\MarketingCenter;

use DistributionBundle\Services\DistributorService;

class SalespersonService
{
    public function getSalespersonAndDistributorByWorkUserid($companyId, $workUserid, $isApp = false)
    {
        $request = new Request();
        $params['work_userid'] = $workUserid;
        $infodata = $request->call($companyId, 'basics.salesperson.info', $params)['data'] ?? [];
        if (empty($infodata)) {
            return [];
        }
        app('log')->info('[getSalespersonInfoByWorkUserid] infodata===>'. json_encode($infodata));
        $distributorService = new DistributorService();
        $distributor_filter = [
            'company_id' => $companyId,
            'shop_code' => $infodata['infodata']['store_bn'] ?? '',
        ];
        $distributorInfo = $distributorService->getInfoSimple($distributor_filter);
        app('log')->info('[getSalespersonInfoByWorkUserid] distributor_filter===>'. json_encode($distributor_filter));
        app('log')->info('[getSalespersonInfoByWorkUserid] distributorInfo===>'. json_encode($distributorInfo));

        if ($distributorInfo) {
            $infodata['distributorInfo'] = $distributorInfo;
        }
        return $infodata;
    }    
    public function getSalespersonInfoByWorkUserid($companyId, $workUserid, $isApp = false)
    {
        $request = new Request();
        $params['work_userid'] = $workUserid;
        $infodata = $request->call($companyId, 'basics.salesperson.info', $params)['data'] ?? [];
        if (empty($infodata)) {
            return [];
        }
        app('log')->info('[getSalespersonInfoByWorkUserid] infodata===>'. json_encode($infodata));
        $distributorService = new DistributorService();
        $distributorCodes = array_column($infodata['stores'], null, 'store_bn');
        $distributor_filter = [
            'company_id' => $companyId,
            'is_valid' => 'true',
        ];
        //如果导购没有挂靠门店，则获取所有启用门店
        if ($distributorCodes) {
            $distributor_filter['shop_code'] = array_keys($distributorCodes);
        }
        $distributors = $distributorService->lists($distributor_filter, ["created" => "DESC"])['list'] ?? [];

        foreach ($distributors as $value) {
            $store[] = [
                'distributor_id' => $value['distributor_id'],
                'name' => $value['store_name'] ?? $value['name'],
                'shop_code' => $value['shop_code'],
                'logo' => $value['logo'],
                'is_center' => false,
            ];
            if (isset($distributorCodes[$value['shop_code']])) {
                $distributorCodes[$value['shop_code']]['distributor_id'] = $value['distributor_id'];
            }
            if (empty($distributorCodes)) {
                $distributorCodes[] = [
                    'distributor_id' => $value['distributor_id'],
                    'store_name' => $value['store_name'] ?? $value['name'],
                ];
            }
        }
        $result = [
            'company_id' => $companyId,
            "work_userid" => $infodata['work_userid'] ?? '',
            'mobile' => $infodata['mobile'],
            'special_identity' => $infodata['special_identity'],
            'head_portrait' => $infodata['salesperson_avatar'],
            'username' => $infodata['salesperson_name'],
            'distributor_ids' => array_values($distributorCodes),
            'shop_area_ids' => $infodata['groups'] ?? [],
            'logintype' => 'salesperson_workwechat',
        ];
        if ($isApp) {
            $result['distributors'] = $store;
        }
        return $result;
    }
}
