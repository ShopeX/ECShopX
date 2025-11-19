<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace KaquanBundle\Http\Api\V1\Swagger;

/**
 * @SWG\Definition(type="object")
 */
class MemberCard
{
    /**
     * @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     * @SWG\Property( property="brand_name", type="string", example="111", description="商户名称"),
     * @SWG\Property( property="logo_url", type="string", example="http://mmbiz.qpic.cn/mmbiz_png/Hw4Ssicubkrea6fnM3LTSgicDZNfnf6DnpmqgoHL9k1k09oVqlarzOUSBw11h1LiaW1wYazQY1j0KsUm3dbPs9ciaQ/0?wx_fmt=png", description="logo"),
     * @SWG\Property( property="title", type="string", example="2222", description="标题"),
     * @SWG\Property( property="color", type="string", example="#409EFF", description="券颜色值"),
     * @SWG\Property( property="code_type", type="string", example="CODE_TYPE_QRCODE", description="卡券码类型(CODE_TYPE_TEXT CODE_TYPE_BARCODE CODE_TYPE_QRCODE CODE_TYPE_ONLY_QRCODE CODE_TYPE_ONLY_BARCODE CODE_TYPE_NONE)"),
     * @SWG\Property( property="background_pic_url", type="string", example="", description="会员卡背景图"),
     * @SWG\Property( property="created", type="string", example="1604545567", description=""),
     * @SWG\Property( property="updated", type="string", example="1604545567", description="修改时间"),
     */
}
