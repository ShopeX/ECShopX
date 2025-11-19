<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace SystemLinkBundle\Services;

use SystemLinkBundle\Services\Jushuitan\Request;

use Dingo\Api\Exception\StoreResourceFailedException;

class JushuitanSettingService
{
    /**
     * 设置聚水潭ERP配置
     */
    public function setJushuitanSetting($companyId, $data)
    {
        return app('redis')->set($this->genReidsId($companyId), json_encode($data));
    }

    /**
     * 获取聚水潭ERP配置
     */
    public function getJushuitanSetting($companyId)
    {
        $data = app('redis')->get($this->genReidsId($companyId));
        if ($data) {
            $data = json_decode($data, true);
            return $data;
        } else {
            return ['is_open' => false];
        }
    }

    /**
     * 获取redis存储的ID
     */
    private function genReidsId($companyId)
    {
        return 'JushuitanSetting:' . sha1($companyId);
    }
}
