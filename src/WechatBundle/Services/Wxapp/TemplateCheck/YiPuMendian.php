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

namespace WechatBundle\Services\Wxapp\TemplateCheck;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * 源源客会员小程序
 * 参数配置类
 */
class YiPuMendian
{
    public $permissionAppIdArr = [
        'wx8cc024a091c10b09',//预发布
        'wx0a732efe4e66d8ea',//正式
        'wxe4d71857568b84f5',//测试
        'wx40ec5d079c5732de',//一普测试
        'wx5928eedb65acd618',//一普正式
        'wx6b8c2837f47e8a09',//demo
    ];

    /**
     * 保存配置参数
     */
    public function check($authorizerAppId, $wxaAppId, $templateName, $wxaName)
    {
        //if (!$this->checkPermission($authorizerAppId)) {
        //    throw new BadRequestHttpException('当前小程序为客户定制，你无此权限');
        //}
        return true;
    }

    //检查公众号是否有权限限制
    public function checkPermission($authorizerAppId)
    {
        //return in_array($authorizerAppId, $this->permissionAppIdArr);
        return true;
    }
}
