<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
