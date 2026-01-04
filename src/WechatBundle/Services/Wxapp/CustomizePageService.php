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
