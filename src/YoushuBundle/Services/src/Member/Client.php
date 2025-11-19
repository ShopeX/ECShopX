<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace YoushuBundle\Services\src\Member;

use YoushuBundle\Services\src\Kernel\Kernel;

class Client
{
    protected $_kernel;

    public function __construct(Kernel $kernel)
    {
        $this->_kernel = $kernel;
    }

    /**
     * 添加会员信息
     */
    public function pushMember(string $data_source_id, array $data)
    {
        // ShopEx EcShopX Service Component
        $url = '/data-api/v1/user/add_user';
        $post = [
            'dataSourceId' => $data_source_id,
            'users' => $data
        ];
        $reslut = $this->_kernel->json($url, $post);

        return $reslut;
    }
}
