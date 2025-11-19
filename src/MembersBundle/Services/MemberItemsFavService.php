<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
