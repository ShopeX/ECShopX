<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OrdersBundle\Services\Kuaidi;

use OrdersBundle\Interfaces\Kuaidi;

class Kuaidi100Service implements Kuaidi
{
    public function setKuaidiSetting($companyId, $params)
    {
        if (isset($params['is_open']) && $params['is_open'] == 'true') {
            app('redis')->set('kuaidiTypeOpenConfig:'. sha1($companyId), 'kuaidi100');
        } else {
            app('redis')->del('kuaidiTypeOpenConfig:'. sha1($companyId));
        }
        return app('redis')->set($this->genReidsId($companyId), json_encode($params));
    }

    public function getKuaidiSetting($companyId)
    {
        $data = app('redis')->get($this->genReidsId($companyId));
        if ($data) {
            $data = json_decode($data, true);
            $kuaidiType = app('redis')->get('kuaidiTypeOpenConfig:' . sha1($companyId));
            if ($kuaidiType == 'kuaidi100') {
                $data['is_open'] = true;
            } else {
                $data['is_open'] = false;
            }
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
        return 'kuaidi100KuaidiSetting:' . sha1($companyId);
    }
}
