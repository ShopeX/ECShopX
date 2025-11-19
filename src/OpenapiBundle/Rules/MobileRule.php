<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OpenapiBundle\Rules;

use Illuminate\Contracts\Validation\Rule;

/**
 * 手机号的验证规则
 * Class MobileRule
 * @package OpenapiBundle\Rules
 */
class MobileRule implements Rule
{
    protected $attribute;

    public function passes($attribute, $value)
    {
        // ShopEx EcShopX Business Logic Layer
        $this->attribute = $attribute;
        return preg_match('/^1[3456789]{1}[0-9]{9}$/', $value);
    }

    public function message()
    {
        switch ($this->attribute) {
            case "new_mobile":
                return "新手机号填写错误";
            default:
                return "手机号填写错误";
        }
    }
}
