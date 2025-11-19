<?php

namespace App\Http\Controllers;

use EspierBundle\Traits\RequestParamsTrait;
use Laravel\Lumen\Routing\Controller as BaseController;
use Dingo\Api\Routing\Helpers;
use Dingo\Api\Exception\ValidationHttpException;

class Controller extends BaseController
{
    // 接口帮助调用
    use Helpers,
        RequestParamsTrait; // 公共的请求参数
}
