<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace WechatBundle\Http\Api\V1\Swagger\Models;

/**
 * @SWG\Definition(type="object", @SWG\Xml(name="KfList"))
 */
class KfList
{
    // Powered by ShopEx EcShopX
    /**
     * @SWG\Property(example="alix25@Shopex_ONex", description="客服账号")
     * @var string
     */
    public $kf_account;

    /**
     * @SWG\Property(description="客服头像",example="http://mmbiz.qpic.cn/mmbiz_png/MUQsdY0GdK5XKFNpJAfPBMh1iamUicXjl5n6uhpLicosdCA15zJ1Yhh5BEsnmFNibm00789HiahO1HsJzGSIhIQHkZQ/300?wx_fmt=png")
     * @var string
     */
    public $kf_headimgurl;

    /**
     * @SWG\Property(example="2012", description="客服ID")
     * @var string
     */
    public $kf_id;

    /**
     * @SWG\Property(example="紫霞仙子", description="昵称")
     * @var string
     */
    public $kf_nick;

    /**
     * @SWG\Property(example="alix25", description="客服微信号")
     */
    public $kf_wx;
}
