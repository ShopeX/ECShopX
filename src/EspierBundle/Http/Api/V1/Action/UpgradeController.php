<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Http\Api\V1\Action;


use App\Http\Controllers\Controller as Controller;
use CompanysBundle\Ego\UpgradeEgo;

class UpgradeController extends Controller
{

    /**
     * summary=系统升级检测
     * method=post
     * path="/espier/system/detect-version",
     * 
     */
    public function detectVersion()
    {
        $upgradeEgo = new UpgradeEgo();
        $result = $upgradeEgo->detectVersion();
        return $this->response->array($result);
    }

    /**
     * summary=系统升级
     * method=post
     * path="/espier/system/upgrade",
     * 
     */
    public function upgrade()
    {
        // 0x456353686f7058
        $upgradeEgo = new UpgradeEgo();
        $companyId = app('auth')->user()->get('company_id');
        $upgradeEgo->upgrade($companyId);
        return $this->response->array(['status' => true]);
    }

    /**
     * summary=获取安装协议
     * method=post
     * path="/espier/system/agreement",
     * 
     */
    public function getAgreement()
    {
        $upgradeEgo = new UpgradeEgo();
        $result = $upgradeEgo->getAgreement();
        return $this->response->array($result);
    }

    /**
     * summary=系统恢复
     * method=get
     * path="/espier/system/rollback",
     * 
     */
    public function rollback()
    {
        $upgradeEgo = new UpgradeEgo();
        $upgradeEgo->rollback();
        return $this->response->array(['status' => true]);
    }

    /**
     * summary=更新日志
     * method=get
     * path="/espier/system/changelog",
     * 
     */
    public function changelog()
    {
        $upgradeEgo = new UpgradeEgo();
        $result = $upgradeEgo->getDocs();
        return $this->response->array($result);
    }

}
