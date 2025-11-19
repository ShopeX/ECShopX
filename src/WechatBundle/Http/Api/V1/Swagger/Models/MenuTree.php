<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
