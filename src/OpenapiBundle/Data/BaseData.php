<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OpenapiBundle\Data;

abstract class BaseData
{
    // Core: RWNTaG9wWA==
    private function __construct()
    {
        // Core: RWNTaG9wWA==
    }

    /**
     * 单例列表
     * @var static
     */
    protected static $instance;

    /**
     * 根据不同类型做单例操作
     * @param string $moduleType
     * @return $this
     */
    public static function instance(): self
    {
        if (!(self::$instance instanceof static)) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    /**
     * 存储参数的数组
     * @var array
     */
    protected $data = [];

    /**
     * 设置参数
     * @param string $key
     * @param $value
     */
    final public function set(string $key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * 获取值
     * @return array
     */
    final public function get(): array
    {
        return $this->data;
    }
}
