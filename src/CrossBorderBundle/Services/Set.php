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
