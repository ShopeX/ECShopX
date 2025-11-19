<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
