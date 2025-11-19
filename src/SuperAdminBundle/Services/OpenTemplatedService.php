<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace SuperAdminBundle\Services;

use Dingo\Api\Exception\ResourceException;

/**
 * 微信开放平台-代码模版库设置
 */
class OpenTemplatedService
{
    /**
     * 获取代码草稿列表
     */
    public function gettemplatedraftlist()
    {
        $draft_list = [];
        $openPlatform = app('easywechat.manager')->openPlatform();
        $reslut = $openPlatform->code_template->getDrafts();
        if ($reslut['errcode'] == 0) {
            $draft_list = $reslut['draft_list'];
        }

        return $draft_list;
    }

    /**
     * 将草稿添加到代码模板库
     */
    public function addtotemplate($draft_id)
    {
        $openPlatform = app('easywechat.manager')->openPlatform();
        $reslut = $openPlatform->code_template->createFromDraft($draft_id);
        if ($reslut['errcode'] == 0) {
            return true;
        } else {
            throw new ResourceException($reslut['errmsg']);
        }
    }

    /**
     * 获取代码模板列表
     */
    public function gettemplatelist()
    {
        $template_list = [];
        $openPlatform = app('easywechat.manager')->openPlatform();
        $reslut = $openPlatform->code_template->list();
        if ($reslut['errcode'] == 0) {
            $template_list = $reslut['template_list'];
        }

        return $template_list;
    }

    /**
     * 删除指定代码模板
     */
    public function deletetemplate($template_id)
    {
        $openPlatform = app('easywechat.manager')->openPlatform();
        $reslut = $openPlatform->code_template->delete($template_id);
        if ($reslut['errcode'] == 0) {
            return true;
        } else {
            throw new ResourceException($reslut['errmsg']);
        }
    }
}
