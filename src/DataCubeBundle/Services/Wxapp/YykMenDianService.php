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

namespace DataCubeBundle\Services\Wxapp;

use DataCubeBundle\Entities\Monitors;
use DataCubeBundle\Interfaces\MiniProgramInterface;

class YykMenDianService implements MiniProgramInterface
{
    /** @var openPlatform */
    private $openPlatform;

    private $monitorsRepository;

    /** @var pages */
    public $pages = [
        [
            'page' => 'pages/index',
            'label' => '首页',
            'pathParams' => [],
        ],
        // [
        //     'page'  => 'pages/course',
        //     'label' => '课程列表页',
        //     'pathParams' => [],
        // ],
        [
            'page' => 'pages/course_detail',
            'label' => '课程详情页',
            'pathParams' => [
                [
                  'param_name' => 'id',
                  'param_label' => '课程ID',
                ],
            ],
        ],
    ];

    /**
     * YykMenDianService 构造函数.
     */
    public function __construct()
    {
        $this->monitorsRepository = app('registry')->getManager('default')->getRepository(Monitors::class);
    }

    // 获取小程序对应的页面路径及参数信息
    public function getPages()
    {
        return $this->pages;
    }

    // 生成小程序码对应的路径
    public function generatePath(array $pathInfo)
    {
        return '';
    }

    /**
     * 应用规则
     *
     * @param array params 跟踪链接数据
     * @return void
     */
    public function rule(array $params)
    {
    }
}
