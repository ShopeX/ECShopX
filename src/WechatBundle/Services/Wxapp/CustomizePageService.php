<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace WechatBundle\Services\Wxapp;

use WechatBundle\Entities\WeappCustomizePage;

class CustomizePageService
{
    public $customizePageRepository;

    public function __construct()
    {
        // $this->customizePageRepository = app('registry')->getManager('default')->getRepository(WeappCustomizePage::class);
        $this->customizePageRepository = getRepositoryLangue(WeappCustomizePage::class);
    }


    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->customizePageRepository->$method(...$parameters);
    }

    /** 
     * 获取导购货架首页的自定义页面id
     * @param  int $companyId    企业id
     * @param  string $templateName 小程序模板名称
     * @return int               自定义页面ID
     */
    public function getSalespersonCustomId($companyId, $templateName)
    {
        $filter = [
            'company_id' => $companyId,
            'template_name' => $templateName,
            'page_type' => 'salesperson',
        ];
        $info = $this->getInfo($filter);
        return $info['id'] ?? 0;
    }

    public function getMyCustomId($companyId, $regionauthId = '')
    {
        $filter = [
            'company_id' => $companyId,
            'page_type' => 'my',
            'is_open' => 1,
        ];
        $pageList = $this->lists($filter, 'id', 1, 1);
        if ($pageList['list']) {
            return $pageList['list'][0]['id'];
        }
        return 0;
    }    
}
