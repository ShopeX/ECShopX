<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OrdersBundle\Services\TradeSetting;

use OrdersBundle\Interfaces\TradeSettingInterface;

class CancelService implements TradeSettingInterface
{
    public function setSetting($companyId, $params)
    {
        return app('redis')->set($this->genReidsId($companyId), json_encode($params));
    }

    public function getSetting($companyId)
    {
        $data = app('redis')->get($this->genReidsId($companyId));
        if ($data) {
            return json_decode($data, true);
        } else {
            return ['repeat_cancel' => false];
        }
    }

    /**
     * 获取redis存储的ID
     */
    private function genReidsId($companyId)
    {
        return 'tradeCancelSetting:' . sha1($companyId);
    }
}
