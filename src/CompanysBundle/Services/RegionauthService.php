<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
