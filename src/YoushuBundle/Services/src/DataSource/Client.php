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

namespace YoushuBundle\Services\src\DataSource;

use YoushuBundle\Services\src\Kernel\Kernel;

class Client
{
    protected $_kernel;

    public function __construct(Kernel $kernel)
    {
        $this->_kernel = $kernel;
    }

    /**
     * 添加数据仓库
     */
    public function add($merchant_id, $data_source_type)
    {
        $url = '/data-api/v1/data_source/add';
        $post = [
            'merchantId' => $merchant_id,
            // 'dataSourceType' => $data_source_type,
            'multi' => true,
        ];
        $reslut = $this->_kernel->json($url, $post);

        return $reslut;
    }

    /**
     * 获取数据仓库
     */
    public function get($merchant_id, $data_source_type)
    {
        $post = [
            'merchantId' => $merchant_id,
            // 'dataSourceType' => $data_source_type,
        ];
        $url = '/data-api/v1/data_source/get';
        $reslut = $this->_kernel->get($url, $post);

        return $reslut;
    }
}
