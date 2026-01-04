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
 * @SWG\Definition(type="object", @SWG\Xml(name="MenuTree"))
 */
class MenuTree
{
    /**
     * @SWG\Property(format="int64", example="1")
     * @var int
     */
    public $id;

    /**
     * @SWG\Property(example="活动中心")
     * @var string
     */
    public $name;

    /**
     * @SWG\Property(example="3")
     * @var string
     */
    public $menu_type;

    /**
     * @SWG\Property(example="微信扩展菜单-微信扫码带提示")
     * @var string
     */
    public $type_value;

    /**
     * @SWG\Property(
     *      type="array",
     *      @SWG\Items(
     *           @SWG\Property(property="id", type="integer", example=1),
     *           @SWG\Property(property="name", type="string", example="抽奖"),
     *           @SWG\Property(property="menu_type", type="integer", example=3),
     *           @SWG\Property(property="type_value", type="string", example="微信扩展菜单-微信扫码带提示"),
     *      )
     * )
     */
    public $submenu;
}
