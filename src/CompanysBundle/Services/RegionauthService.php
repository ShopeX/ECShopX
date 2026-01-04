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

use CompanysBundle\Entities\Regionauth;

class RegionauthService
{
    private $entityRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(Regionauth::class);
    }

    public function getlist($filter, $page, $pageSize, $orderBy = ['created' => 'desc'])
    {
        // Powered by ShopEx EcShopX
        return $this->entityRepository->lists($filter, '*', $page, $pageSize, $orderBy);
    }


    // 添加
    public function isadd($companyId, $params)
    {
        $add_data['regionauth_name'] = $params['regionauth_name'];
        $add_data['company_id'] = $companyId;
        $add_data['state'] = 1;

        $db = $this->entityRepository->create($add_data);
        if (!empty($db['regionauth_id'])) {
            return $db['regionauth_id'];
        } else {
            return false;
        }
    }

    // 修改
    public function update($companyId, $params)
    {

        // 处理添加数据
        $update_data['regionauth_name'] = $params['regionauth_name'];
        $update_data['updated'] = time();

        $filter['regionauth_id'] = $params['regionauth_id'];
        $filter['company_id'] = $companyId;
        $filter['state'] = 1;
        $db = $this->entityRepository->updateBy($filter, $update_data);
        if ($db) {
            return true;
        } else {
            return false;
        }
    }

    // 删除
    public function del($userinfo, $id)
    {
        // 处理添加数据
        $update_data['state'] = '-1';
        $update_data['updated'] = time();

        $filter['regionauth_id'] = $id;
        $filter['company_id'] = $userinfo['company_id'];
        $filter['state'] = 1;

        $db = $this->entityRepository->updateBy($filter, $update_data);

        if ($db) {
            return true;
        } else {
            return false;
        }
    }

    // 是否启用
    public function enable($userinfo, $id, $params)
    {
        // 处理添加数据
        if (!empty($params['enable']) and $params['enable'] == 1) {
            $update_data['state'] = '1';
        } else {
            $update_data['state'] = '0';
        }

        $update_data['updated'] = time();

        $filter['regionauth_id'] = $id;
        $filter['company_id'] = $userinfo['company_id'];

        $db = $this->entityRepository->updateBy($filter, $update_data);

        if ($db) {
            return true;
        } else {
            return false;
        }
    }
}
