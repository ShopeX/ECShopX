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

namespace EspierBundle\Services\Constants;

use EspierBundle\Services\Reflection\ReflectionConstantDocument;

/**
 * 错误码的基础类
 * Class BaseErrorCode
 * @package EspierBundle\Services\Constants
 */
abstract class BaseErrorCode
{
    /**
     * 获取错误的错误码注释
     * @param int $companyId 企业id
     * @return array 所有的常量注释
     */
    final public static function getAll(int $companyId): array
    {
        // 获取文档的常量注释
        $document = new ReflectionConstantDocument($companyId, static::class);
        // 返回对应code的错误消息
        return $document->getAll();
    }

    /**
     * 获取错误码对应的错误信息
     * @param int $companyId 企业id
     * @param string $code 错误码
     * @return string 错误信息
     */
    final public static function get(int $companyId, string $code): string
    {
        // 获取文档的常量注释
        $document = new ReflectionConstantDocument($companyId, static::class);
        // 返回对应code的错误消息
        return $document->get($code);
    }
}
