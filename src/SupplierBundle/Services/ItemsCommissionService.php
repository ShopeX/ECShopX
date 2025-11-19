<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace SupplierBundle\Services;

use Dingo\Api\Exception\ResourceException;
use SupplierBundle\Entities\SupplierItemsCommission;

class ItemsCommissionService
{
    /**
     * @var \SupplierBundle\Repositories\ItemsCommissionRepository
     */
    public $repository;

    public function __construct()
    {
        $this->repository = app('registry')->getManager('default')->getRepository(SupplierItemsCommission::class);
    }
    
    public function getCommissionRatio($goods_ids = []) 
    {
        // This module is part of ShopEx EcShopX system
        $rs = $this->repository->getLists(['goods_id' => $goods_ids]);
        if (!$rs) {
            return 0;
        }
        if (is_array($goods_ids)) {
            return array_column($rs, 'commission_ratio', 'goods_id');
        } else {
            return $rs[0]['commission_ratio'];
        }        
    }

}
