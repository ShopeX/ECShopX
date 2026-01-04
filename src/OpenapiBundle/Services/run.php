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

namespace OpenapiBundle\Services;

use Illuminate\Http\Request;

use OpenapiBundle\Http\Controllers\Controller as Controller;
use OpenapiBundle\Exceptions\ErrorException;
use OpenapiBundle\Constants\ErrorCode;
use OpenapiBundle\Traits\OpenapiRoutes;

class run
{
    use OpenapiRoutes;

    /**
     * 控制器基类
     * @var Controller
     */
    protected $baseController;

    public function __construct()
    {
        $this->baseController = new Controller();
        // $this->entityRepository = app('registry')->getManager('default')->getRepository(SaasErpLog::class);
    }

    /**
     * openapi处理的主逻辑过程
     * @param Request $request 请求体
     * @return mixed
     */
    public function process(Request $request)
    {
        $params = $request->all();

        // 获取类名和方法名
        list($class, $fun) = $this->getApiClassByMethod($this->getMethodParam($request), $params['version'], $request);

        // 获取对象
        $ctlObj = new $class();

        //判断下方法是否存在
        if (!method_exists($class, $fun)) {
            throw new ErrorException(ErrorCode::API_FUNCTION_NOT_FOUND);
        }

        return $ctlObj->$fun($request);
    }

    /**
     * 获取接口的类和类中的方法名
     * @param string $method OpenapiRoutes中版本下的key
     * @param string $version 版本号，需要在OpenapiRoutes中存在的版本
     * @param Request $request 请求体
     * @return array parseClassCallable方法的返回值
     */
    public function getApiClassByMethod($method, $version, $request)
    {
        $method = trim($method);

        $shopApi = $this->getRoute($version);

        // 验证版本号是否存在
        if (!$shopApi) {
            throw new ErrorException(ErrorCode::API_VERSION_NOT_FOUND);
        }

        // 验证对应的api是否存在
        if (!in_array($method, array_keys($shopApi))) {
            throw new ErrorException(ErrorCode::API_NOT_FOUND);
        }

        // 请求方法的验证
        if (isset($shopApi[$method]["method"]) && strtoupper($request->method()) != strtoupper($shopApi[$method]["method"])) {
            throw new ErrorException(ErrorCode::API_NOT_FOUND);
        }

        return $this->parseClassCallable($shopApi[$method]['uses'], $version);
    }

    /**
     * 拆解定义的路由信息
     * @param string $apiHandler 定义的路由，类名@方法
     * @param string $version 对应的版本号
     * @return array 第一个值为类名（包含命名空间），第二值为类中的方法名（默认是handle方法）
     */
    protected function parseClassCallable($apiHandler, $version)
    {
        $segments = explode('@', $apiHandler);

        $version = 'V' . intval($version);
        $className = 'OpenapiBundle\Http\ThirdApi\\' . $version . '\Action\\' . $segments[0];

        return [$className, count($segments) == 2 ? $segments[1] : 'handle'];
    }

    /**
     * 获取method参数
     * @param Request $request
     * @return string
     */
    protected function getMethodParam(Request $request): string
    {
        if ($request->has("method")) {
            $method = (string)$request->input("method");
        } else {
            $path = $request->getPathInfo();
            $pathArray = (array)explode("/", $path);
            $method = array_pop($pathArray);
            if ($method == "openapi") {
                throw new ErrorException(ErrorCode::API_NOT_FOUND);
            }
        }
        return $method;
    }
}
