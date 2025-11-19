<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace DistributionBundle\Services;

use Dingo\Api\Exception\ResourceException;
use DistributionBundle\Entities\Advertisement;

class AdvertisementService
{
    /** @var resourcesRepository */
    private $entityRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(Advertisement::class);
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
        return $this->entityRepository->$method(...$parameters);
    }

    //排序/发布/撤回
    public function updateStatusOrSort($companyId, $params)
    {
        foreach ($params as $value) {
            $inputdata = [];
            if (!isset($value['id'])) {
                throw new ResourceException(trans('DistributionBundle/Services/AdvertisementService.params_error'));
            }
            $filter['id'] = $value['id'];
            $info = $this->entityRepository->getInfoById($filter['id']);
            $distributor_id = $info['distributor_id'];
            if (isset($value['release_status'])) {
                $inputdata['release_status'] = (!$value['release_status'] || $value['release_status'] === 'false') ? false : true;
                if ($inputdata['release_status']) {
                    $total = $this->count(['company_id' => $companyId, 'distributor_id' => $distributor_id,'release_status' => true]);
                    if ($total >= 3) {
                        throw new ResourceException(trans('DistributionBundle/Services/AdvertisementService.published_ads_limit'));
                    }
                }
                $inputdata['release_time'] = (!$value['release_status'] || $value['release_status'] === 'false') ? 0 : time();
            }
            if (isset($value['sort'])) {
                $inputdata['sort'] = $value['sort'];
            }
            if (!$inputdata) {
                throw new ResourceException(trans('DistributionBundle/Services/AdvertisementService.params_error'));
            }
            $result[] = $this->entityRepository->updateOneBy($filter, $inputdata);
        }
        return true;
    }

    //启动页广告
    public function getStartAds($filter)
    {
        $filter['release_status'] = true; //已发布
        $total = $this->entityRepository->count($filter);
        if ($total == 0) {
            $filter['distributor_id'] = 0;
        }
        $result = $this->entityRepository->lists($filter);
        $count = $result['total_count'];
        if (!$count) {
            return $result;
        }
        $frontendShowTotal = 3; //前端展示数量
        while ($count++ < $frontendShowTotal) {
            $result['list'][] = end($result['list']);
        }
        $result['total_count'] = $frontendShowTotal;
        $result['thumb_img'] = array_column($result['list'], 'thumb_img');
        array_walk($result['list'], function (&$val) {
            $val['media'] = ['url' => $val['media_url'], 'type' => $val['media_type']];
        });
        $result['media'] = array_column($result['list'], 'media');
        return $result;
    }
}
