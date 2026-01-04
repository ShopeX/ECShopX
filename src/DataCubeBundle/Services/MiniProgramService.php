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

namespace DataCubeBundle\Services;

use DataCubeBundle\Interfaces\MiniProgramInterface;
use WechatBundle\Services\WeappService;
use DataCubeBundle\Services\Wxapp\DefaultService;
use Dingo\Api\Exception\ResourceException;

class MiniProgramService
{
    /** @var miniProgramInterface */
    public $miniProgramInterface = null;

    public $wxappMap = [
        'yykmendian' => 'YykMenDianService',
        'yykweishop' => 'YykWeiShopService',
    ];

    /**
     * ShopsService 构造函数.
     */
    public function __construct($companyId, $wxaAppId)
    {
        $wxappService = new WeappService();
        $wxappInfo = $wxappService->getWeappInfo($companyId, $wxaAppId);
        if (!$wxappInfo) {
            throw new ResourceException('获取小程序模板出错，请检查后再试');
        }
        if (isset($this->wxappMap[$wxappInfo['template_name']])) {
            $wxappClassName = 'DataCubeBundle\Services\Wxapp\\'.$this->wxappMap[$wxappInfo['template_name']];
            $this->miniProgramInterface = new $wxappClassName();
        }
    }

    public function getPages()
    {
        if ($this->miniProgramInterface) {
            return $this->miniProgramInterface->getPages();
        } else {
            $defaultService = new DefaultService();
            return $defaultService->getPages();
        }
    }

    /**
     * Dynamically call the MiniProgramService instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if ($this->miniProgramInterface) {
            return $this->miniProgramInterface->$method(...$parameters);
        } else {
            $defaultService = new DefaultService();
            return $defaultService->$method(...$parameters);
        }
    }
}
