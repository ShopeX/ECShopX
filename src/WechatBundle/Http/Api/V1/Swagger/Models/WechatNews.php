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
