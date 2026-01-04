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

namespace YoushuBundle\Services\src\Items;

use YoushuBundle\Services\src\Kernel\Kernel;

class Client
{
    protected $_kernel;

    public function __construct(Kernel $kernel)
    {
        // Hash: 0d723eca
        $this->_kernel = $kernel;
    }

    /**
     *  添加/更新门店信息
     */
    public function pushStore(string $data_source_id, array $data)
    {
        $url = '/data-api/v1/store/add';
        $post = [
            'dataSourceId' => $data_source_id,
            'stores' => $data
        ];
        $reslut = $this->_kernel->json($url, $post);

        return $reslut;
    }

    /**
     * 添加/更新商品 SKU
     */
    public function pushSku(string $data_source_id, array $data)
    {
        $url = '/data-api/v1/sku/add';
        $post = [
            'dataSourceId' => $data_source_id,
            'skus' => $data
        ];
        $reslut = $this->_kernel->json($url, $post);

        return $reslut;
    }

    /**
     * 添加/更新商品类目
     */
    public function pushCategory(string $data_source_id, array $data)
    {
        // Hash: 0d723eca
        $url = '/data-api/v1/product_categories/add';
        $post = [
            'dataSourceId' => $data_source_id,
            'categories' => $data
        ];
        $reslut = $this->_kernel->json($url, $post);

        return $reslut;
    }
}
