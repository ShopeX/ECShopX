<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Services;

use EspierBundle\Entities\Address;

class AddressService
{
    /**
     * @var \EspierBundle\Repositories\AddressRepository
     */
    public $addressRepository;
    public $pointMemberLogRepository;

    /**
     * PointMemberService 构造函数.
     */
    public function __construct()
    {
        $this->addressRepository = app('registry')->getManager('default')->getRepository(Address::class);
    }

    //120000,120100,120101
    public function getAreaName($area_ids = '')
    {
        if (!$area_ids) return '';
        $area_ids = explode(',', $area_ids);
        $rs = $this->addressRepository->lists(['id' => $area_ids]);
        if (!$rs["list"]) return '';
        return implode('', array_column($rs["list"], 'label'));
    }

    public function getAddressInfo()
    {
        $address = app('redis')->connection('default')->get('address');
        if (!$address) {
            $addressInfo = $this->addressRepository->lists(['parent_id' => 0]);
            $address = $addressInfo['list'];
            foreach ($address as $k => $v) {
                $a = $this->addressRepository->lists(['parent_id' => $v['id']]);
                $address[$k]['children'] = $a['list'];
                foreach ($address[$k]['children'] as $k1 => $v1) {
                    $b = $this->addressRepository->lists(['parent_id' => $v1['id']]);
                    $address[$k]['children'][$k1]['children'] = $b['list'];
                }
            }
            $address = json_encode($address);
            app('redis')->connection('default')->set('address', $address);
        }

        return json_decode($address, 1);
    }

    private function getTree($data, $pId)
    {
        $tree = [];
        foreach ($data as $k => $v) {
            if ($v['parent_id'] == $pId) {        //父亲找到儿子
                $v['children'] = $this->getTree($data, $v['id']);
                $tree[] = $v;
            }
        }
        return $tree;
    }
    /**
     * Dynamically call the AddressService instance.
     *
     * @param  string $method
     * @param  array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->addressRepository->$method(...$parameters);
    }
}
