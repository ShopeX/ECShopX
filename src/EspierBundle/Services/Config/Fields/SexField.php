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

namespace EspierBundle\Services\Config\Fields;

class SexField implements FieldInterface
{
    /**
     * 将描述转成值
     * @param string $description
     * @return string
     */
    public function toValue(string $description): string
    {
        $value = "0";
        // 参数转换
        switch ($description) {
            case "男":
            case "男性":
                $value = "1";
                break;
            case "女":
            case "女性":
                $value = "2";
                break;
        }
        return $value;
    }

    /**
     * 将值转成描述
     * @param string $value
     * @return string
     */
    public function toDescription(string $value): string
    {
        $description = "未知";
        switch ($value) {
            case "1":
                $description = "男";
                break;
            case "2":
                $description = "女";
                break;
        }
        return $description;
    }
}
