<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ThemeBundle\Services;

use ThemeBundle\Entities\PagesTemplateSet;

class PagesTemplateSetServices
{
    private $pagesTemplateSetRepository;

    public function __construct()
    {
        // $this->pagesTemplateSetRepository = app('registry')->getManager('default')->getRepository(PagesTemplateSet::class);
        $this->pagesTemplateSetRepository = getRepositoryLangue(PagesTemplateSet::class);
    }

    /**
     * 保存数据
     */
    public function saveData($params)
    {
        //判断数据是否存着
        $info = $this->pagesTemplateSetRepository->getInfo(['company_id' => $params['company_id'], 'pages_template_id' => ($params['pages_template_id'] ?? 0)]);
        if (empty($info)) {
            $result = $this->pagesTemplateSetRepository->create($params);
        } else {
            $result = $this->pagesTemplateSetRepository->updateOneBy(['company_id' => $params['company_id'], 'pages_template_id' => ($params['pages_template_id'] ?? 0)], $params);
        }

        return $result;
    }

    /**
     * 获取设置信息
     */
    public function getInfo($params)
    {
        //判断数据是否存着
        $info = $this->pagesTemplateSetRepository->getInfo(['company_id' => $params['company_id'], 'pages_template_id' => ($params['pages_template_id'] ?? 0)]);

        return $info;
    }
}
