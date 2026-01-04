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

namespace EspierBundle\Services\Reflection;

use EspierBundle\Services\Cache\RedisCacheService;

class ReflectionConstantDocument
{
    /**
     * 企业id
     * @var int
     */
    protected $companyId;

    /**
     * 对类的反射
     * @var \ReflectionClass
     */
    protected $reflectionClass;

    /**
     * 缓存的过期时间
     * @var int
     */
    protected $cacheTtl;

    public function __construct(int $companyId, string $classString)
    {
        $this->companyId = $companyId;
        $this->reflectionClass = new \ReflectionClass($classString);
        $this->cacheTtl = 60; // 现在是写死的60秒有效时间，后期可以通过env里配置缓存的有效时间
    }

    /**
     * 获取全部的常量注释
     * @return array
     */
    public function getAll(): array
    {
        $cacheService = new RedisCacheService($this->companyId, sprintf("ReflectionConstantDocument:%s", md5($this->reflectionClass->getName())), $this->cacheTtl);

        return $cacheService->getByPrevention(function () {
            // 扫描文件
            $fileContent = file_get_contents($this->reflectionClass->getFileName());
            // 代码解析
            $tokens = token_get_all($fileContent);

            $data = [];
            // 常量定义的起始行数
            $constStartLine = -1;
            // 是否是常量注释的标识符
            $isConst = false;
            foreach ($tokens as $token) {
                // 获取token的3个参数，类型 文本内容和行数
                [$type, $document, $line] = $token;

                // 如果定义了起始行，且起始行大于当前的行数 且不存在常量的名字和值，则直接跳过
                if ($constStartLine > 0 && $constStartLine < $line && (!isset($data[$constStartLine]["name"], $data[$constStartLine]["value"]))) {
                    $isConst = false;
                    unset($data[$constStartLine]);
                }

                // 根据token的类型来做处理
                switch ($type) {
                    // 获取常量的注释
                    case T_DOC_COMMENT:
                        $constStartLine = $line + substr_count($document, "\n") + 1;
                        $data[$constStartLine]["document"] = $document;
                        break;
                    // 常量的关键词 const
                    case T_CONST:
                        $isConst = true;
                        break;
                    // 获取常量的名字
                    case T_STRING:
                        if ($constStartLine == $line) {
                            if ($isConst) {
                                $data[$constStartLine]["name"] = $document;
                            } else {
                                unset($data[$constStartLine]);
                                $isConst = false;
                            }
                        }
                        break;
                    // 获取常量的值
                    case T_CONSTANT_ENCAPSED_STRING:
                        if ($constStartLine == $line) {
                            if ($isConst) {
                                $data[$constStartLine]["value"] = str_replace("\"", "", $document);
                            } else {
                                unset($data[$constStartLine]);
                                $isConst = false;
                            }
                        }
                        break;

                }
            }

            // 去掉注释内的无关信息
            foreach ($data as &$datum) {
                preg_match('/@message\("(.*)"\)/', $datum["document"], $matchResult);
                $datum["document"] = $matchResult[1] ?? "";
            }

            return (array)array_column($data, "document", "value");
        });
    }

    /**
     * 获取某个常量的文本信息
     * @param string $value 常量的值
     * @return string 返回注释内容
     */
    public function get(string $value): string
    {
        $all = $this->getAll();
        return $all[$value] ?? "";
    }
}
