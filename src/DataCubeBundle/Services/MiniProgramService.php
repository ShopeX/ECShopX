<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
