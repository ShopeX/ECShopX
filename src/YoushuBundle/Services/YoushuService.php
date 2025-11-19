<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
