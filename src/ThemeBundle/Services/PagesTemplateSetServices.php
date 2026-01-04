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
