<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace MembersBundle\Services;

use MembersBundle\Entities\MemberDistributionFav;
use DistributionBundle\Services\DistributorService;
use DistributionBundle\Entities\Distributor;
use Dingo\Api\Exception\StoreResourceFailedException;

class MemberDistributionFavService
{
    private $memberDistributionFavRepository;
    private $distributorRepository;

    /**
     * MemberDistributionFavService 构造函数.
     */
    public function __construct()
    {
        $this->memberDistributionFavRepository = app('registry')->getManager('default')->getRepository(MemberDistributionFav::class);
        $this->distributorRepository = app('registry')->getManager('default')->getRepository(Distributor::class);
    }

    // 添加收藏店铺
    public function addDistributionFav($params)
    {
        $filter = [
            'company_id' => $params['company_id'],
            'user_id' => $params['user_id'],
            'distributor_id' => $params['distributor_id'],
        ];

        $distributorInfo = $this->distributorRepository->getInfoById($filter['distributor_id']);
        if (!$distributorInfo) {
            throw new StoreResourceFailedException(trans('MembersBundle/Members.shop_info_error'));
        }

        $favInfo = $this->memberDistributionFavRepository->getInfo($filter);

        if ($favInfo) {
            return $favInfo;
        }

        $result = $this->memberDistributionFavRepository->create($filter);

        return $result;
    }

    // 删除收藏
    public function removeDistributionFav($params)
    {
        if (!$params['company_id'] || !$params['user_id'] || !$params['distributor_id']) {
            throw new StoreResourceFailedException(trans('MembersBundle/Members.param_error_distribution'));
        }
        $filter = [
            'company_id' => $params['company_id'],
            'user_id' => $params['user_id'],
            'distributor_id' => $params['distributor_id'], // 数组
        ];
        return $this->memberDistributionFavRepository->deleteBy($filter);
    }


    // 获取店铺收藏列表
    public function getDistributionFavList($params)
    {
        if (!isset($params['user_id']) || !isset($params['company_id'])) {
            throw new StoreResourceFailedException(trans('MembersBundle/Members.get_user_info_failed_distribution'));
        }

        $page = isset($params['page']) ? $params['page'] : 1;

        $pageSize = isset($params['pageSize']) ? $params['pageSize'] : 100;

        $orderBy = ['fav_id' => 'DESC'];

        $filter = ['user_id' => $params['user_id'], 'company_id' => $params['company_id']];

        $result = $this->memberDistributionFavRepository->lists($filter, $page, $pageSize, $orderBy);

        if (!$result['list']) {
            return [];
        }
        $distributorIds = array_column($result['list'], 'distributor_id');

        $result = [];
        if (count($distributorIds) > 0) {
            $distributorService = new DistributorService();

            $result = $distributorService->lists(['distributor_id' => $distributorIds,'company_id' => $params['company_id'], 'is_valid' => 'true']);
            if ($result['total_count'] > 0) {
                foreach ($result['list'] as &$value) {
                    $value['fav_num'] = $this->memberDistributionFavRepository->count(['distributor_id' => $value['distributor_id'],'company_id' => $params['company_id']]);
                }
            }
        }

        return $result;
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
        return $this->memberDistributionFavRepository->$method(...$parameters);
    }
}
