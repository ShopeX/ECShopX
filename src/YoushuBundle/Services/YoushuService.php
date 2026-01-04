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

namespace YoushuBundle\Services;

use YoushuBundle\Entities\YoushuSetting;

class YoushuService
{
    private $youshuSettingRepository;

    public function __construct()
    {
        $this->youshuSettingRepository = app('registry')->getManager('default')->getRepository(YoushuSetting::class);
    }

    /**
     * 保存数据
     */
    public function saveData($params)
    {
        $company_id = $params['company_id'];
        $id = $params['id'] ?? '';
        //判断数据是否存着
        if (!empty($id)) {
            $result = $this->youshuSettingRepository->updateOneBy(['company_id' => $company_id], $params);
        } else {
            $result = $this->youshuSettingRepository->create($params);
        }

        return $result;
    }

    /**
     * 获取设置信息
     */
    public function getInfo($params)
    {
        //判断数据是否存着
        $info = $this->youshuSettingRepository->getInfo(['company_id' => $params['company_id']]);

        return $info;
    }
}
