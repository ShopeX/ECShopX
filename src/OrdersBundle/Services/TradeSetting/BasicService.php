<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OrdersBundle\Services\TradeSetting;

use OrdersBundle\Interfaces\TradeSettingInterface;

class BasicService implements TradeSettingInterface
{
    public function setSetting($companyId, $params)
    {
        return app('redis')->set($this->genReidsId($companyId), json_encode($params));
    }

    public function getSetting($companyId)
    {
        $data = app('redis')->get($this->genReidsId($companyId));
        if ($data) {
            $data = json_decode($data, true);
            $data['is_open'] = (isset($data['is_open']) && $data['is_open'] == 'true') ? true : false;
            return $data;
        } else {
            return [];
        }
    }


    /**
     * 获取redis存储的ID
     */
    private function genReidsId($companyId)
    {
        return 'tradeBasicSetting:' . sha1($companyId);
    }
}
