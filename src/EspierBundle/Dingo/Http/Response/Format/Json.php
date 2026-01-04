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

namespace EspierBundle\Dingo\Http\Response\Format;

use Dingo\Api\Http\Response\Format\Json as DingoJson;
use Illuminate\Http\Request;

class Json extends DingoJson
{
    public function formatArray($content)
    {
        $content = $this->morphToArray($content);

        array_walk_recursive($content, function (&$value) {
            $value = $this->morphToArray($value);
        });

        if (!$this->isOpenApiRequest()) {
            if (!isset($content['error']) && !isset($content['_ignore_data']) && !isset($content['data'])) {
                $content = [
                    'data' => $content
                ];
            }
        } else {
            // openapi的响应体格式的处理已经移动到 OpenapiBundle\Middleware\HandleResponseMiddleware中间件中操作了
        }

        return $this->encode($content);
    }

    /**
     * openapi路由的前缀
     * @var string
     */
    private $openapiRoutePrefix = "/api/openapi";

    /**
     * 判断当前请求是否是openapi那边转过来的
     * @return bool
     */
    private function isOpenApiRequest()
    {
        $pathInfo = $this->request->getPathInfo();
        return $this->openapiRoutePrefix == substr($pathInfo, 0, strlen($this->openapiRoutePrefix));
    }

    /**
     * 处理结果集中的data值
     * openapi中，data的返回值是object类型，所以不能是空数组，这里将空数组转成null
     * @param array $result
     */
    private function handleDataValue(array &$result)
    {
        // 在不是post、patch、put的情况下，不作处理
//        if (!in_array($this->request->method(), [Request::METHOD_PATCH, Request::METHOD_PUT, Request::METHOD_POST, Request::METHOD_DELETE], true)) {
//            return;
//        }
        // 内容转换
        if (empty($result["data"])) {
            $result["data"] = null;
        }
    }
}
