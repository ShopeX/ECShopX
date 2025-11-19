<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Services\Config\Fields;

interface FieldInterface
{
    /**
     * 将描述转成值
     * @param string $description
     * @return string
     */
    public function toValue(string $description): string;

    /**
     * 将值转成描述
     * @param string $value
     * @return string
     */
    public function toDescription(string $value): string;
}
