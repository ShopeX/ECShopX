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

namespace OpenapiBundle\Middleware;

use EspierBundle\Exceptions\ErrorException;
use EspierBundle\Services\Log\ErrorLog;
use OpenapiBundle\Constants\ErrorCode;
use OpenapiBundle\Exceptions\ApiResponseException;
use OpenapiBundle\Traits\OpenapiRoutes;

/**
 * 将接口的返回内容做系统性的整理，并将老功能做迭代
 * Class HandleResponseMiddleware
 * @package OpenapiBundle\Middleware
 */
class HandleResponseMiddleware
{
    use OpenapiRoutes;

    /**
     * @var \Illuminate\Http\Response
     */
    protected $response;

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, \Closure $next)
    {
        // 获取请求的方法名
        $method = $request->query("method", "");
        // 获取接口的版本号
        $version = $request->query("version", "1.0");

        $this->response = $next($request);

        // 操作成功
        if (is_null($this->response->exception)) {
            // 判断响应体类型，如果不是json数据的响应体就不需要转成json了
            $responseType = $this->getResponseTypeByVersionAndMethod($version, $method);
            if ($responseType != "json") {
                // 非json的响应体
                return $this->response;
            } else {
                // json响应体
                $this->response->original = [
                    "status" => "success",
                    "code" => 'E0000',
                    "message" => "成功",
                    "data" => $this->response->original ?: null // 在其他语言里，data属于对象，所以不能是一个空数组，只能用null来代替空数组
                ];
                return $this->response;
            }
        } elseif ($this->response->exception instanceof ApiResponseException) { // 旧代码的操作迭代
            $this->response->original = $this->response->exception->get();
            return $this->response;
        }

        // 获取请求对象中的auth信息，用于下文获取企业id
        $auth = (array)$request->attributes->get("auth");

        // 操作失败的响应格式
        if ($this->response->exception instanceof ErrorException || $this->response->exception instanceof \Exception) { // 已知的错误异常
            // 获取错误码
            $code = sprintf("%04d", $this->response->exception->getCode());
            // 如果判断exception对象中是否存在错误信息，如果存在则优先拿验证错误的提示信息
            $message = $this->response->exception->getMessage();
            $this->response->original = [
                "status" => "fail",
                "code" => "E".$code,
                "message" => $message ?: ErrorCode::get((int)$auth["company_id"], $code), // 如果错误信息为空，则默认去拿错误码里的注释信息
                "data" => null
            ];
            return $this->response;
        } elseif ($this->response->exception instanceof \Throwable) { // 未知的错误异常
            // 记录日志
            ErrorLog::serviceError($this->response->exception);
            $this->response->original = [
                "status" => "fail",
                "code" => "E".ErrorCode::SERVICE_ERROR,
                "message" => env("APP_ENV") == "production" ? ErrorCode::get((int)$auth["company_id"], ErrorCode::SERVICE_ERROR) : $this->response->exception->getMessage(),
                "data" => null
            ];
        }
        return $this->response;
    }
}
