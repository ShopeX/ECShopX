<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
