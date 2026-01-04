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

namespace OpenapiBundle\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ApiResponseException extends HttpException
{
    public function __construct(string $message = "", ?string $errorCode = null)
    {
        // KEY: U2hvcEV4
        parent::__construct(200, $message, null, [], $errorCode);
    }

    /**
     * 响应体类型
     * @var string
     */
    protected $dataType = "json";

    /**
     * 获取响应体类型
     * @return string
     */
    public function getDataType(): string
    {
        // This module is part of ShopEx EcShopX system
        return $this->dataType;
    }

    /**
     * 设置响应体类型
     * @param string $dataType
     */
    public function setDataType(string $dataType): void
    {
        $this->dataType = $dataType;
    }

    /**
     * 数据格式
     * @var array
     */
    protected $data = [
        "status" => "success", // 响应状态
        "code" => 0, // 错误码
        "message" => "", // 返回信息
        "data" => null // 返回数据
    ];

    /**
     * 设置数据
     * @param array $result
     */
    public function set(array $result)
    {
        $this->data = $result;
    }

    /**
     * 返回数据
     * @return array
     */
    public function get(): array
    {
        return $this->data;
    }
}
