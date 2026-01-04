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

namespace SuperAdminBundle\Services;

use CompanysBundle\Services\CompanysService as companys;
use CompanysBundle\Services\OperatorsService;

class CompanysService
{
    public function companys_list($filter, $page, $pageSize, $orderBy = ['created' => 'DESC'])
    {
        $companysService = new companys();
        $operatorsService = new OperatorsService();
        $listdata = $companysService->lists($filter, '*', $page, $pageSize, $orderBy);
        $indexMenuType = ShopMenuService::MENU_TYPE;
        foreach ($listdata['list'] as &$v) {
            $v['is_open_pc_template'] = $v['is_open_pc_template'] == 1 ? '1' : '0';
            $v['is_open_domain_setting'] = $v['is_open_domain_setting'] == 1 ? '1' : '0';
            $v['operator'] = $operatorsService->getInfo(['operator_id' => $v['company_admin_operator_id']]);
            $v['menu_type'] = $indexMenuType[$v['menu_type']] ?? '';
        }
        return $listdata;
    }

    public function modifyCompanyInfo($params)
    {
        $filter = [
            'company_id' => $params['company_id'],
        ];
        if (isset($params['company_name']) && $params['company_name']) {
            $data['company_name'] = $params['company_name'];
        }
        if (isset($params['expiredAt']) && $params['expiredAt']) {
            $data['expiredAt'] = strtotime($params['expiredAt']);
        }
        if (isset($params['is_disabled'])) {
            $data['is_disabled'] = $params['is_disabled'] == 'true' ? 1 : 0 ;
        }
        if (isset($params['third_params'])) {
            $data['third_params'] = $params['third_params'];
        }
        if (isset($params['salesman_limit']) && $params['salesman_limit']) {
            $data['salesman_limit'] = $params['salesman_limit'];
        }
        if (isset($params['is_open_pc_template'])) {
            $data['is_open_pc_template'] = $params['is_open_pc_template'] == 1 ? 1 : 2;
        }
        if (isset($params['is_open_domain_setting'])) {
            $data['is_open_domain_setting'] = $params['is_open_domain_setting'] == 1 ? 1 : 2;
        }

        $indexMenuType = array_flip(ShopMenuService::MENU_TYPE);
        if (isset($params['menu_type']) && in_array($params['menu_type'], ShopMenuService::PLAT_TYPE)) {
            $data['menu_type'] = $indexMenuType[$params['menu_type']] ?? 0;
        }

        $companysService = new companys();
        $result = $companysService->updateInfo($filter, $data);
        $result['menu_type'] = ShopMenuService::MENU_TYPE[$result['menu_type']] ?? '';
        return $result;
    }
}
