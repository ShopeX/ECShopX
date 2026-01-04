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
