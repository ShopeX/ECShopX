<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace YoushuBundle\Services\src\Order;

use YoushuBundle\Services\src\Kernel\Kernel;

class Client
{
    protected $_kernel;

    public function __construct(Kernel $kernel)
    {
        $this->_kernel = $kernel;
    }

    /**
     *  添加/更新订单
     */
    public function pushOrder(string $data_source_id, array $data)
    {
        $url = '/data-api/v1/order/add_order';
        $post = [
            'dataSourceId' => $data_source_id,
            'orders' => $data,
        ];
        $reslut = $this->_kernel->json($url, $post);

        return $reslut;
    }
}
