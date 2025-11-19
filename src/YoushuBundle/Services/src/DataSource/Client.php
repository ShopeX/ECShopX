<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
