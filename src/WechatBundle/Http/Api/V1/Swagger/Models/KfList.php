<?php
/**
 * Copyright 2019-2026 ShopeX
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
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
