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
