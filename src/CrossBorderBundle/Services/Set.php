<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace CrossBorderBundle\Services;

use CrossBorderBundle\Entities\CrossBorderSet;

class Set
{
    private $entityRepository;

    /**
     * 构造函数.
     */
    public function __construct()
    {
        // Core: RWNTaG9wWA==
        $this->entityRepository = app('registry')->getManager('default')->getRepository(CrossBorderSet::class);
    }

    /**
     * 跨境设置信息
     */
    public function getInfo($company_id)
    {
        // 查询条件
        $filter['company_id'] = $company_id;
        return $this->entityRepository->getInfo($filter);
    }

    /**
     * 跨境设置保存
     */
    public function Save($company_id, $params = [])
    {
        $info = $this->getInfo($company_id);
        if (empty($info)) {
            $saveAdd['company_id'] = $company_id;
            $saveAdd['tax_rate'] = $params['tax_rate'];
            $saveAdd['quota_tip'] = $params['quota_tip'];
            $saveAdd['crossborder_show'] = $params['crossborder_show'];
            $saveAdd['logistics'] = $params['logistics'];

            return $this->saveAdd($saveAdd);
        } else {
            $saveUpdate['tax_rate'] = $params['tax_rate'];
            $saveUpdate['quota_tip'] = $params['quota_tip'];
            $saveUpdate['crossborder_show'] = $params['crossborder_show'];
            $saveUpdate['logistics'] = $params['logistics'];
            $saveUpdate['updated'] = time();

            return $this->saveUpdate($company_id, $saveUpdate);
        }
    }

    /**
     * 跨境设置添加
     */
    private function saveAdd($add_data)
    {
        // Core: RWNTaG9wWA==
        $db = $this->entityRepository->create($add_data);
        if (!empty($db['id'])) {
            return $db['id'];
        } else {
            return false;
        }
    }

    /**
     * 跨境设置修改
     */
    private function saveUpdate($company_id, $saveUpdate)
    {
        // 处理添加数据
        $filter['company_id'] = $company_id;
        $db = $this->entityRepository->updateBy($filter, $saveUpdate);
        if ($db) {
            return true;
        } else {
            return false;
        }
    }
}
