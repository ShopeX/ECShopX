<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace SelfserviceBundle\Services;

use DistributionBundle\Services\DistributorService;
use SelfserviceBundle\Entities\RegistrationActivityRelShop;

class RegistrationActivityRelShopService
{
    /** @var \SelfserviceBundle\Repositories\RegistrationActivityRelShopRepository */
    public $entityRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(RegistrationActivityRelShop::class);
    }

    public function getRelShops($activity_id)
    {
        //保存店铺 distributor_ids 关联数据 RegistrationActivityRelShop        
        $rsRelShops = $this->entityRepository->getLists(['activity_id' => $activity_id]);
        if (!$rsRelShops or !$rsRelShops[0]['distributor_id']) {
            return [];
        }

        //获取绑定的店铺名称
        $distributor_ids = array_column($rsRelShops, 'distributor_id');
        $filter = ['distributor_id' => $distributor_ids];
        $distributorService = new DistributorService();
        return $distributorService->entityRepository->getLists($filter, 'distributor_id,name,address');
    }
    
    public function saveRelShops($activity_id, $distributor_ids)
    {
        //保存店铺 distributor_ids 关联数据 RegistrationActivityRelShop        
        $rs = $this->entityRepository->getLists(['activity_id' => $activity_id]);
        if ($distributor_ids) {
            $old_distributor_ids = array_column($rs, 'distributor_id');

            //删除不存在的数据
            $del_distributor_ids = array_diff($old_distributor_ids, $distributor_ids);
            if ($del_distributor_ids) {
                $_filter = [
                    'activity_id' => $activity_id,
                    'distributor_id' => $del_distributor_ids,
                ];
                $this->entityRepository->deleteBy($_filter);
            }

            //写入新的关联店铺
            $new_distributor_ids = array_diff($distributor_ids, $old_distributor_ids);
            if ($new_distributor_ids) {
                foreach ($new_distributor_ids as $distributor_id) {
                    $insertData = [
                        'activity_id' => $activity_id,
                        'distributor_id' => $distributor_id,
                    ];
                    $this->entityRepository->create($insertData);
                }
            }
        } else {
            $insertData = [
                'activity_id' => $activity_id,
                'distributor_id' => 0,//适用全部店铺，写入0
            ];
            foreach ($rs as $v) {
                if ($v['distributor_id']) {
                    $this->entityRepository->deleteById($v['id']);
                } else {
                    $insertData = [];
                }
            }
            if ($insertData) {
                $this->entityRepository->create($insertData);
            }
        }
    }
}
