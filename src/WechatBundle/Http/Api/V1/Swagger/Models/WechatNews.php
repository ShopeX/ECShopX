<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace WechatBundle\Http\Api\V1\Swagger\Models;

/**
 * @SWG\Definition(type="object", @SWG\Xml(name="WechatNews"))
 */
class WechatNews
{
    /**
     * @SWG\Property(format="string", example="xxx")
     * @var string
     */
    public $title;

    /**
     * @SWG\Property(example="")
     * @var string
     */
    public $thumb_media_id;

    /**
     * @SWG\Property(example="0", description="是否显示封面，0为false，即不显示，1为true，即显示")
     * @var string
     */
    public $show_cover_pic;

    /**
     * @SWG\Property(description="作者")
     * @var string
     */
    public $author;

    /**
     * @SWG\Property(description="图文消息的摘要")
     * @var string
     */
    public $digest;

    /**
     * @SWG\Property(description="图文消息的具体内容，支持HTML标签，必须少于2万字符，小于1M，且此处会去除JS")
     * @var string
     */
    public $content;

    /**
     * @SWG\Property(description="图文消息的原文地址，即点击“阅读原文”后的URL")
     * @var string
     */
    public $content_source_url;
}
