<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
