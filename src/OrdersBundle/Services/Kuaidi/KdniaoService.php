<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OrdersBundle\Services\Kuaidi;

use OrdersBundle\Interfaces\Kuaidi;

class KdniaoService implements Kuaidi
{
    public function setKuaidiSetting($companyId, $params)
    {
        if (isset($params['is_open']) && $params['is_open'] == 'true') {
            app('redis')->set('kuaidiTypeOpenConfig:'. sha1($companyId), 'kdniao');
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
            $kuaidiType = app('redis')->get('kuaidiTypeOpenConfig:'. sha1($companyId));
            if ($kuaidiType == 'kdniao') {
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
        return 'kdniaoKuaidiSetting:'. sha1($companyId);
    }

    /**
    * 存储京东快递的青龙物流编码
    * @param $companyId:企业Id
    * @param $code:青龙物流编码
    */
    public function setQingLongCode($companyId, $code)
    {
        return app('redis')->set($this->genQingLongReidsId($companyId), $code);
    }

    /**
    * 存储京东快递的青龙物流编码
    * @param $companyId:企业Id
    * @param $code:青龙物流编码
    */
    public function getQingLongCode($companyId)
    {
        $qinglong_code = app('redis')->get($this->genQingLongReidsId($companyId));
        return $qinglong_code ?? '';
    }

    /**
     * 获取青龙物流编码redis存储的ID
     */
    private function genQingLongReidsId($companyId)
    {
        return 'kdniaoQingLongSetting:'. sha1($companyId);
    }
}
