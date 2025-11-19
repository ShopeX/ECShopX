<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace PromotionsBundle\Services\PromotionActivity;

use PromotionsBundle\Interfaces\PromotionActivityInterface;

use CompanysBundle\Services\Shops\WxShopsService;
use CompanysBundle\Services\ShopsService;

// 会员升级
class MemberUpgrade implements PromotionActivityInterface
{
    /**
     * 当前活动可以同时创建有效的营销次数
     */
    public $validNum = 1;

    /**
     * 发送短信模版名称
     */
    public $tmplName = 'member_upgrade';

    /**
     * 保存会员生日营销活动检查
     *
     * @param array $data 保存的参数
     */
    public function checkActivityParams(array $data)
    {
        return true;
    }

    public function getSourceFromStr()
    {
        return '会员升级送';
    }
}
