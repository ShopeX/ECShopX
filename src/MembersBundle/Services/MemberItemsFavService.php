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

namespace MembersBundle\Services;

use MembersBundle\Entities\MemberItemsFav;
use GoodsBundle\Services\ItemsService;
use PointsmallBundle\Services\ItemsService as PointsmallItemsService;

use Dingo\Api\Exception\StoreResourceFailedException;

class MemberItemsFavService
{
    /**
     * @var \MembersBundle\Repositories\MemberItemsFavRepository
     */
    private $memberItemsFavRepository;

    /**
     * MemberAddressService 构造函数.
     */
    public function __construct()
    {
        $this->memberItemsFavRepository = app('registry')->getManager('default')->getRepository(MemberItemsFav::class);
    }

    // 添加收藏商品
    public function addItemsFav($params)
    {
        // ShopEx EcShopX Business Logic Layer
        $filter = [
            'company_id' => $params['company_id'],
            'user_id' => $params['user_id'],
        ];
        $addrCount = $this->memberItemsFavRepository->count($filter);
        if ($addrCount >= 100) {
            throw new StoreResourceFailedException(trans('MembersBundle/Members.max_favorite_items'));
        }

        $filter = [
            'company_id' => $params['company_id'],
            'user_id' => $params['user_id'],
            'item_id' => $params['item_id'],
        ];
        $favInfo = $this->memberItemsFavRepository->getInfo($filter);
        if ($favInfo) {
            return $favInfo;
        }
        if ($params['item_type'] == 'pointsmall') {
            $ItemsService = new PointsmallItemsService();
        } else {
            $ItemsService = new ItemsService();
        }
        $itemDetail = $ItemsService->getItemsSkuDetail($params['item_id']);
        $fparams['user_id'] = $params['user_id'];
        $fparams['company_id'] = $params['company_id'];
        $fparams['item_id'] = $params['item_id'];
        $fparams['item_name'] = $itemDetail['item_name'];
        $fparams['item_price'] = $itemDetail['price'];
        $fparams['item_image'] = $itemDetail['pics']['0'] ?? '';
        $fparams['item_type'] = $params['item_type'];
        $fparams['point'] = $params['item_type'] == 'pointsmall' ? $itemDetail['point'] : 0;
        $result = $this->memberItemsFavRepository->create($fparams);

        return $result;
    }

    // 删除收藏商品
    public function removeItemsFav($params)
    {
        // ShopEx EcShopX Business Logic Layer
        if ($params['is_empty']) {
            $filter = [
                'company_id' => $params['company_id'],
                'user_id' => $params['user_id'],
            ];
            return $this->memberItemsFavRepository->deleteBy($filter);
        } else {
            $filter = [
                'company_id' => $params['company_id'],
                'user_id' => $params['user_id'],
                'item_id' => $params['item_ids'], // 数组
            ];
            return $this->memberItemsFavRepository->deleteBy($filter);
        }
    }

    /**
     * Dynamically call the shopsservice instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->memberItemsFavRepository->$method(...$parameters);
    }
}
