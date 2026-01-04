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
