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
 * @SWG\Definition(type="object", @SWG\Xml(name="WechatMenu"))
 */
class WechatMenu
{
    /**
     * @SWG\Property(format="int64", example="1")
     * @var int
     */
    public $id;

    /**
     * @SWG\Property(example="0")
     * @var string
     */
    public $pid;

    /**
     * @SWG\Property(example="活动中心")
     * @var string
     */
    public $name;

    /**
     * @SWG\Property(example="抽奖")
     * @var string
     */
    public $keyword;

    /**
     * @SWG\Property(example="http://onex.shopex.cn")
     * @var string
     */
    public $url;

    /**
     * @SWG\Property(example="1")
     * @var int
     */
    public $is_show;

    /**
     * @SWG\Property(format="int64", example="1")
     * @var int
     */
    public $sort;

    /**
     * @SWG\Property(example="3")
     * @var string
     */
    public $menu_type;

    /**
     * @SWG\Property(example="news")
     * @var string
     */
    public $news_type;


    /**
     * @SWG\Property(example="scancode_waitmsg")
     * @var string
     */
    public $wxsys;

    /**
     * @SWG\Property(example="我是文字消息回复")
     * @var string
     */
    public $text;

    /**
     * @SWG\Property(example="pages/index/index")
     * @var string
     */
    public $pagepath;

    /**
     * @SWG\Property(example="MEDIA_ID2")
     * @var string
     */
    public $media_id;

    /**
     * @SWG\Property(example="wx0a732efe4e66d8ea")
     * @var string
     */
    public $app_id;

    /**
     * @SWG\Property(example="wx0a732efe4e66d8eawx0a732efe4e66d8ea")
     * @var string
     */
    public $card_id;

    /**
     * @SWG\Property(example="")
     * @var string
     */
    public $content;

    /**
     * @SWG\Property(
     *      type="array",
     *      @SWG\Items(
     *           @SWG\Property(property="id", type="integer", example=1),
     *           @SWG\Property(property="name", type="string", example="抽奖"),
     *           @SWG\Property(property="app_id", type="string", example="dfasd"),
     *           @SWG\Property(property="card_id", type="string", example="wx0a732efe4e66d8eawx0a732efe4e66d8ea"),
     *           @SWG\Property(property="news_type", type="string", example=""),
     *           @SWG\Property(property="media_id", type="string", example=""),
     *           @SWG\Property(property="text", type="string", example=""),
     *           @SWG\Property(property="pagepath", type="string", example=""),
     *           @SWG\Property(property="url", type="string", example=""),
     *           @SWG\Property(property="sort", type="integer", example=3),
     *           @SWG\Property(property="content", type="string", example="sdfas"),
     *      )
     * )
     */
    public $submenu;
}
