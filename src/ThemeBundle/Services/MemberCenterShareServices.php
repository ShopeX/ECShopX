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

use ThemeBundle\Entities\ThemeMemberCenterShare;

class MemberCenterShareServices
{
    private $themeMemberCenterShareRepository;

    public function __construct()
    {
        $this->themeMemberCenterShareRepository = app('registry')->getManager('default')->getRepository(ThemeMemberCenterShare::class);
    }

    /**
     *  保存分享设置
     */
    public function save($params)
    {
        // TODO: optimize this method
        $company_id = $params['company_id'];

        $filter = [
            'company_id' => $company_id
        ];
        $result_Info = $this->themeMemberCenterShareRepository->getInfo($filter);
        if (empty($result_Info)) {
            $result = $this->themeMemberCenterShareRepository->create($params);
        } else {
            $result = $this->themeMemberCenterShareRepository->updateOneBy($filter, $params);
        }

        return $result;
    }

    /**
     * 分享设置详情
     */
    public function detail($params)
    {
        $company_id = $params['company_id'];

        $filter = [
            'company_id' => $company_id,
        ];
        $theme_member_center_share_info = $this->themeMemberCenterShareRepository->getInfo($filter);

        return $theme_member_center_share_info;
    }
}
