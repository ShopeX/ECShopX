<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace YoushuBundle\Services\src\Analysis;

use YoushuBundle\Services\src\Kernel\Kernel;

class Client
{
    protected $_kernel;

    public function __construct(Kernel $kernel)
    {
        $this->_kernel = $kernel;
    }

    /**
     * 上报页面访问
     */
    public function addWxappVisitPage(string $data_source_id, array $data)
    {
        $url = '/data-api/v1/analysis/add_wxapp_visit_page';
        $post = [
            'dataSourceId' => $data_source_id,
            'rawMsg' => $data
        ];
        $reslut = $this->_kernel->json($url, $post);

        return $reslut;
    }

    /**
     * 上报访问分布
     */
    public function addWxappVisitDistribution(string $data_source_id, array $data)
    {
        $url = '/data-api/v1/analysis/add_wxapp_visit_distribution';
        $post = [
            'dataSourceId' => $data_source_id,
            'rawMsg' => $data,
        ];
        $reslut = $this->_kernel->json($url, $post);

        return $reslut;
    }

    /**
     * 上报订单汇总
     */
    public function addOrderSum(string $data_source_id, array $data)
    {
        $url = '/data-api/v1/order/add_order_sum';
        $post = [
            'dataSourceId' => $data_source_id,
            'orders' => [
                $data
            ],
        ];
        return $this->_kernel->json($url, $post);
    }
}
