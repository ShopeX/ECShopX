<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
