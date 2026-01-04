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

namespace ThemeBundle\Services;

use ThemeBundle\Entities\OpenScreenAd;

class OpenScreenAdServices
{
    public $entityRepository;

    /**
     * 构造函数.
     */
    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(OpenScreenAd::class);
    }

    /**
     * 设置信息
     */
    public function getInfo($company_id)
    {
        // 查询条件
        $filter['company_id'] = $company_id;
        $info = $this->entityRepository->getInfo($filter);
        if (!empty($info['ad_url'])) {
            $info['ad_url'] = json_decode($info['ad_url'], true);
        }
        return $info;
    }

    /**
     * 设置保存
     */
    public function Save($company_id, $params = [])
    {
        $params['ad_url'] = json_encode($params['ad_url'], true);
        $info = $this->getInfo($company_id);
        if (empty($info)) {
            $saveAdd['company_id'] = $company_id;
            $saveAdd['ad_material'] = $params['ad_material'];
            $saveAdd['is_enable'] = $params['is_enable'];
            $saveAdd['show_time'] = $params['show_time'];
            $saveAdd['position'] = $params['position'];
            $saveAdd['is_jump'] = $params['is_jump'];
            $saveAdd['material_type'] = $params['material_type'];
            $saveAdd['waiting_time'] = $params['waiting_time'];
            $saveAdd['ad_url'] = $params['ad_url'];
            $saveAdd['app'] = $params['app'];

            return $this->saveAdd($saveAdd);
        } else {
            $saveUpdate['ad_material'] = $params['ad_material'];
            $saveUpdate['is_enable'] = $params['is_enable'];
            $saveUpdate['show_time'] = $params['show_time'];
            $saveUpdate['position'] = $params['position'];
            $saveUpdate['is_jump'] = $params['is_jump'];
            $saveUpdate['material_type'] = $params['material_type'];
            $saveUpdate['waiting_time'] = $params['waiting_time'];
            $saveUpdate['ad_url'] = $params['ad_url'];
            $saveUpdate['app'] = $params['app'];
            $saveUpdate['updated'] = time();

            return $this->saveUpdate($company_id, $saveUpdate);
        }
    }

    /**
     * 设置添加
     */
    private function saveAdd($add_data)
    {
        $db = $this->entityRepository->create($add_data);
        if (!empty($db['id'])) {
            return $db['id'];
        } else {
            return false;
        }
    }

    /**
     * 设置修改
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
