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

namespace CompanysBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use CompanysBundle\Entities\WxShops;
use Doctrine\Common\Collections\Criteria;
use Dingo\Api\Exception\DeleteResourceFailedException;
use Dingo\Api\Exception\ResourceException;
use Dingo\Api\Exception\UpdateResourceFailedException;

class WxShopsRepository extends EntityRepository
{
    /**
     * 当前表名称
     */
    public $table = 'wxshops';

    public function getAllShops()
    {
        return app('registry')->getConnection('default')->fetchAssoc("select * from wxshops");
    }

    /**
     * 创建微信门店
     */
    public function create($params)
    {
        $shopList = $this->findOneBy(['company_id' => $params['company_id']]);
        $wxshops = new WxShops();

        if (isset($params['map_poi_id']) && $params['map_poi_id']) {
            $wxshops->setMapPoiId($params['map_poi_id']);
        }
        if (isset($params['poi_id']) && $params['poi_id']) {
            $wxshops->setPoiId($params['poi_id']);
        }
        if (isset($params['lng']) && $params['lng']) {
            $wxshops->setLng($params['lng']);
        }
        if (isset($params['lat']) && $params['lat']) {
            $wxshops->setLat($params['lat']);
        }
        if (isset($params['category']) && $params['category']) {
            $wxshops->setCategory($params['category']);
        }
        if (isset($params['credential']) && $params['credential']) {
            $wxshops->setCredential($params['credential']);
        }
        if (isset($params['company_name']) && $params['company_name']) {
            $wxshops->setCompanyName($params['company_name']);
        }

        if (isset($params['distributor_id'])) {
            $wxshops->setDistributorId($params['distributor_id']);
            if ($params['distributor_id']) {
                // 如果是店铺门店默认有效期
                $wxshops->setExpiredAt(4701945600);
            }
        }

        $wxshops->setAddress($params['address']);
        $wxshops->setPicList($params['pic_list']);
        $wxshops->setContractPhone($params['contract_phone']);
        $wxshops->setHour($params['hour']);
        $wxshops->setStoreName($params['store_name']);
        $wxshops->setAddType($params['add_type']);
        $wxshops->setCompanyId($params['company_id']);

        if (isset($params['status']) && $params['status']) {
            $wxshops->setStatus($params['status']);
        }
        if (isset($params['qualification_list']) && $params['qualification_list']) {
            $wxshops->setQualificationList($params['qualification_list']);
        }
        if (!$shopList) {
            $isDefault = true;
            $wxshops->setIsDefault($isDefault);
        }
        if (isset($params['card_id']) && $params['card_id']) {
            $wxshops->setCardId($params['card_id']);
        }
        if (isset($params['audit_id']) && $params['audit_id']) {
            $wxshops->setAuditId($params['audit_id']);
        }
        if (isset($params['is_domestic']) && $params['is_domestic']) {
            $wxshops->setIsDomestic($params['is_domestic']);
        }
        if (isset($params['country']) && $params['country']) {
            $wxshops->setCountry($params['country']);
        }
        if (isset($params['city']) && $params['city']) {
            $wxshops->setCity($params['city']);
        }

        if (!isset($params['is_direct_store']) || !$params['is_direct_store']) {
            $params['is_direct_store'] = 1;
        }
        $wxshops->setIsDirectStore($params['is_direct_store']);

        if (!isset($params['is_open'])) {
            $params['is_open'] = true;
        }
        $wxshops->setIsOpen($params['is_open']);

        $em = $this->getEntityManager();
        $em->persist($wxshops);
        $em->flush();
        $result = [
            'wx_shop_id' => $wxshops->getWxShopId(),
            'map_poi_id' => $wxshops->getMapPoiId(),
            'store_name' => $wxshops->getStoreName(),
            'poi_id' => $wxshops->getPoiId(),
            'lng' => $wxshops->getLng(),
            'lat' => $wxshops->getLat(),
            'address' => $wxshops->getAddress(),
            'category' => $wxshops->getCategory(),
            'pic_list' => $wxshops->getPicList(),
            'contract_phone' => $wxshops->getContractPhone(),
            'hour' => $wxshops->getHour(),
            'add_type' => $wxshops->getAddType(),
            'credential' => $wxshops->getCredential(),
            'company_name' => $wxshops->getCompanyName(),
            'qualification_list' => $wxshops->getQualificationList(),
            'card_id' => $wxshops->getCardId(),
            'status' => $wxshops->getStatus(),
            'company_id' => $wxshops->getCompanyId(),
            'distributor_id' => $wxshops->getDistributorId(),
            'is_domestic' => $wxshops->getIsDomestic() ?: 1,
            'country' => $wxshops->getCountry(),
            'city' => $wxshops->getCity(),
            'is_direct_store' => $wxshops->getIsDirectStore() ?: 1,
            'is_open' => $wxshops->getIsOpen() ?: 1,
            'created' => $wxshops->getCreated(),
            'updated' => $wxshops->getUpdated(),
        ];

        return $result;
    }

    /**
     * 更新微信门店
     */
    public function update($wx_shop_id, $params)
    {
        $wxshops = $this->find($wx_shop_id);

        if (isset($params['map_poi_id']) && $params['map_poi_id']) {
            $wxshops->setMapPoiId($params['map_poi_id']);
        }
        if (isset($params['store_name']) && $params['store_name']) {
            $wxshops->setStoreName($params['store_name']);
        }
        if (isset($params['poi_id']) && $params['poi_id']) {
            $wxshops->setPoiId($params['poi_id']);
        }
        if (isset($params['lng']) && $params['lng']) {
            $wxshops->setLng($params['lng']);
        }
        if (isset($params['lat']) && $params['lat']) {
            $wxshops->setLat($params['lat']);
        }
        if (isset($params['address']) && $params['address']) {
            $wxshops->setAddress($params['address']);
        }
        if (isset($params['category']) && $params['category']) {
            $wxshops->setCategory($params['category']);
        }
        if (isset($params['pic_list']) && $params['pic_list']) {
            $wxshops->setPicList($params['pic_list']);
        }
        if (isset($params['contract_phone']) && $params['contract_phone']) {
            $wxshops->setContractPhone($params['contract_phone']);
        }
        if (isset($params['hour']) && $params['hour']) {
            $wxshops->setHour($params['hour']);
        }
        if (isset($params['add_type']) && $params['add_type']) {
            $wxshops->setAddType($params['add_type']);
        }
        if (isset($params['credential']) && $params['credential']) {
            $wxshops->setCredential($params['credential']);
        }
        if (isset($params['company_name']) && $params['company_name']) {
            $wxshops->setCompanyName($params['company_name']);
        }
        if (isset($params['qualification_list']) && $params['qualification_list']) {
            $wxshops->setQualificationList($params['qualification_list']);
        }
        if (isset($params['card_id']) && $params['card_id']) {
            $wxshops->setCardId($params['card_id']);
        }
        if (isset($params['status']) && $params['status']) {
            $wxshops->setStatus($params['status']);
        }
        if (isset($params['company_id']) && $params['company_id']) {
            $wxshops->setCompanyId($params['company_id']);
        }
        if (isset($params['errmsg']) && $params['errmsg']) {
            $wxshops->setErrmsg($params['errmsg']);
        }
        if (isset($params['audit_id']) && $params['audit_id']) {
            $wxshops->setAuditId($params['audit_id']);
        }
        if (isset($params['resource_id']) && $params['resource_id']) {
            $wxshops->setResourceId($params['resource_id']);
        }
        if (isset($params['expired_at']) && $params['expired_at']) {
            $wxshops->setExpiredAt($params['expired_at']);
        }

        if (isset($params['country']) && $params['country']) {
            $wxshops->setCountry($params['country']);
        }
        if (isset($params['city']) && $params['city']) {
            $wxshops->setCity($params['city']);
        }

        if (!isset($params['is_direct_store']) || !$params['is_direct_store']) {
            $params['is_direct_store'] = 1;
        }
        $wxshops->setIsDirectStore($params['is_direct_store']);

        if (!isset($params['is_open'])) {
            $params['is_open'] = true;
        }
        $wxshops->setIsOpen($params['is_open']);

        $em = $this->getEntityManager();
        $em->persist($wxshops);
        $em->flush();

        $result = [
            'wx_shop_id' => $wxshops->getWxShopId(),
            'map_poi_id' => $wxshops->getMapPoiId(),
            'store_name' => $wxshops->getStoreName(),
            'poi_id' => $wxshops->getPoiId(),
            'lng' => $wxshops->getLng(),
            'lat' => $wxshops->getLat(),
            'address' => $wxshops->getAddress(),
            'category' => $wxshops->getCategory(),
            'pic_list' => $wxshops->getPicList(),
            'contract_phone' => $wxshops->getContractPhone(),
            'hour' => $wxshops->getHour(),
            'add_type' => $wxshops->getAddType(),
            'credential' => $wxshops->getCredential(),
            'company_name' => $wxshops->getCompanyName(),
            'qualification_list' => $wxshops->getQualificationList(),
            'card_id' => $wxshops->getCardId(),
            'status' => $wxshops->getStatus(),
            'company_id' => $wxshops->getCompanyId(),
            'created' => $wxshops->getCreated(),
            'updated' => $wxshops->getUpdated(),
            'expired_at' => $wxshops->getExpiredAt(),
            'resource_id' => $wxshops->getResourceId(),
            'is_domestic' => $wxshops->getIsDomestic() ?: 1,
            'distributor_id' => $wxshops->getDistributorId(),
            'is_open' => $wxshops->getIsOpen() ?: true,
            'country' => $wxshops->getCountry(),
            'city' => $wxshops->getCity(),
            'is_direct_store' => $wxshops->getIsDirectStore() ?: 1,
        ];

        return $result;
    }

    /**
     * 删除微信门店
     */
    public function delete($wx_shop_id)
    {
        $delShopsEntity = $this->find($wx_shop_id);
        if (!$delShopsEntity) {
            throw new DeleteResourceFailedException("wx_shop_id={$wx_shop_id}的微信店铺不存在");
        }
        $em = $this->getEntityManager();
        $em->remove($delShopsEntity);
        $em->flush();
        return true;
    }

    public function openOrClose($wx_shop_id, $status = 1)
    {
        if ($status === 'false' || $status === false) {
            $status = false;
        } elseif ($status === 'true' || $status === true) {
            $status = true;
        }
        $shopsEntity = $this->find($wx_shop_id);
        if (!$shopsEntity) {
            throw new DeleteResourceFailedException("wx_shop_id={$wx_shop_id}的门店不存在");
        }

        $shopsEntity->setIsOpen($status);

        $em = $this->getEntityManager();
        $em->persist($shopsEntity);
        $em->flush();
        return true;
    }

    /**
     * 获取微信门店详细信息
     */
    public function get($wx_shop_id)
    {
        $wxShopsInfo = $this->find($wx_shop_id);
        if (!$wxShopsInfo) {
            return [];
            //throw new ResourceException("wx_shop_id={$wx_shop_id}的微信店铺不存在");
        }
        $result = [
            'wx_shop_id' => $wxShopsInfo->getWxShopId(),
            'map_poi_id' => $wxShopsInfo->getMapPoiId(),
            'store_name' => $wxShopsInfo->getStoreName(),
            'poi_id' => $wxShopsInfo->getPoiId(),
            'lng' => $wxShopsInfo->getLng(),
            'lat' => $wxShopsInfo->getLat(),
            'address' => $wxShopsInfo->getAddress(),
            'category' => $wxShopsInfo->getCategory(),
            'pic_list' => json_decode($wxShopsInfo->getPicList(), 1),
            'contract_phone' => $wxShopsInfo->getContractPhone(),
            'distributor_id' => $wxShopsInfo->getDistributorId(),
            'hour' => $wxShopsInfo->getHour(),
            'add_type' => $wxShopsInfo->getAddType(),
            'credential' => $wxShopsInfo->getCredential(),
            'company_name' => $wxShopsInfo->getCompanyName(),
            'qualification_list' => $wxShopsInfo->getQualificationList(),
            'card_id' => $wxShopsInfo->getCardId(),
            'status' => $wxShopsInfo->getStatus(),
            'errmsg' => $wxShopsInfo->getErrmsg(),
            'company_id' => $wxShopsInfo->getCompanyId(),
            'created' => $wxShopsInfo->getCreated(),
            'updated' => $wxShopsInfo->getUpdated(),
            'resource_id' => $wxShopsInfo->getResourceId(),
            'is_domestic' => $wxShopsInfo->getIsDomestic() ?: 1,
            'country' => $wxShopsInfo->getCountry(),
            'city' => $wxShopsInfo->getCity(),
            'is_direct_store' => $wxShopsInfo->getIsDirectStore() ?: 1,
            'is_open' => $wxShopsInfo->getIsOpen() ?: true,
            'expired_at' => $wxShopsInfo->getExpiredAt(),
        ];
        $latlng = $result['lat'] . ',' . $result['lng'];
        $result['qqmapimg'] = 'http://apis.map.qq.com/ws/staticmap/v2/?'
                            . 'key=' . config('common.qqmap_key')
                            . '&size=500x249'
                            . '&zoom=16'
                            . '&center=' . $latlng
                            . '&markers=color:blue|label:A|' . $latlng;

        return $result;
    }

    /**
     * 获取微信门店详细信息
     */
    public function getDetailByPoiId($poi_id)
    {
        $wxShopsInfo = $this->findOneBy(['poi_id' => $poi_id]);
        if (!$wxShopsInfo) {
            return [];
        }
        $result = [
            'wx_shop_id' => $wxShopsInfo->getWxShopId(),
            'map_poi_id' => $wxShopsInfo->getMapPoiId(),
            'store_name' => $wxShopsInfo->getStoreName(),
            'poi_id' => $wxShopsInfo->getPoiId(),
            'lng' => $wxShopsInfo->getLng(),
            'lat' => $wxShopsInfo->getLat(),
            'address' => $wxShopsInfo->getAddress(),
            'category' => $wxShopsInfo->getCategory(),
            'pic_list' => json_decode($wxShopsInfo->getPicList(), 1),
            'contract_phone' => $wxShopsInfo->getContractPhone(),
            'hour' => $wxShopsInfo->getHour(),
            'distributor_id' => $wxShopsInfo->getDistributorId(),
            'add_type' => $wxShopsInfo->getAddType(),
            'credential' => $wxShopsInfo->getCredential(),
            'company_name' => $wxShopsInfo->getCompanyName(),
            'qualification_list' => $wxShopsInfo->getQualificationList(),
            'card_id' => $wxShopsInfo->getCardId(),
            'status' => $wxShopsInfo->getStatus(),
            'errmsg' => $wxShopsInfo->getErrmsg(),
            'company_id' => $wxShopsInfo->getCompanyId(),
            'is_domestic' => $wxShopsInfo->getIsDomestic() ?: 1,
            'country' => $wxShopsInfo->getCountry(),
            'city' => $wxShopsInfo->getCity(),
            'is_direct_store' => $wxShopsInfo->getIsDirectStore() ?: 1,
            'is_open' => $wxShopsInfo->getIsOpen() ?: true,
            'created' => $wxShopsInfo->getCreated(),
            'updated' => $wxShopsInfo->getUpdated(),
        ];

        return $result;
    }

    /**
     * 获取微信门店详细信息
     */
    public function getDetailByAuditId($audit_id)
    {
        $wxShopsInfo = $this->findOneBy(['audit_id' => $audit_id]);
        if (!$wxShopsInfo) {
            return [];
        }
        $result = [
            'wx_shop_id' => $wxShopsInfo->getWxShopId(),
            'map_poi_id' => $wxShopsInfo->getMapPoiId(),
            'store_name' => $wxShopsInfo->getStoreName(),
            'poi_id' => $wxShopsInfo->getPoiId(),
            'lng' => $wxShopsInfo->getLng(),
            'lat' => $wxShopsInfo->getLat(),
            'address' => $wxShopsInfo->getAddress(),
            'category' => $wxShopsInfo->getCategory(),
            'pic_list' => json_decode($wxShopsInfo->getPicList(), 1),
            'contract_phone' => $wxShopsInfo->getContractPhone(),
            'hour' => $wxShopsInfo->getHour(),
            'distributor_id' => $wxShopsInfo->getDistributorId(),
            'add_type' => $wxShopsInfo->getAddType(),
            'credential' => $wxShopsInfo->getCredential(),
            'company_name' => $wxShopsInfo->getCompanyName(),
            'qualification_list' => $wxShopsInfo->getQualificationList(),
            'card_id' => $wxShopsInfo->getCardId(),
            'status' => $wxShopsInfo->getStatus(),
            'errmsg' => $wxShopsInfo->getErrmsg(),
            'company_id' => $wxShopsInfo->getCompanyId(),
            'audit_id' => $wxShopsInfo->getAuditId(),
            'is_domestic' => $wxShopsInfo->getIsDomestic() ?: 1,
            'country' => $wxShopsInfo->getCountry(),
            'city' => $wxShopsInfo->getCity(),
            'is_direct_store' => $wxShopsInfo->getIsDirectStore() ?: 1,
            'is_open' => $wxShopsInfo->getIsOpen() ?: true,
            'created' => $wxShopsInfo->getCreated(),
            'updated' => $wxShopsInfo->getUpdated(),
        ];

        return $result;
    }

    /**
     * 获取微信门店列表
     */
    public function list($filter, $orderBy = ['wx_shop_id' => 'DESC'], $pageSize = 100000, $page = 1)
    {
        $criteria = Criteria::create();
        if ($filter) {
            if (isset($filter['address|contains'])) {
                foreach ((array)$filter['address|contains'] as $val) {
                    $criteria = $criteria->andWhere(Criteria::expr()->contains('address', $val));
                }
            }
            unset($filter['address|contains']);
            foreach ($filter as $field => $value) {
                $list = explode('|', $field);
                if (count($list) > 1) {
                    list($v, $k) = $list;
                    $criteria = $criteria->andWhere(Criteria::expr()->$k($v, $value));
                    continue;
                } elseif (is_array($value)) {
                    $criteria = $criteria->andWhere(Criteria::expr()->in($field, $value));
                } else {
                    $criteria = $criteria->andWhere(Criteria::expr()->eq($field, $value));
                }
            }
        }

        $total = $this->getEntityManager()
            ->getUnitOfWork()
            ->getEntityPersister($this->getEntityName())
            ->count($criteria);
        $res['total_count'] = intval($total);

        $newWxShopsInfo = [];
        if ($res['total_count']) {
            $criteria = $criteria->orderBy($orderBy)
                ->setFirstResult($pageSize * ($page - 1))
                ->setMaxResults($pageSize);
            $list = $this->matching($criteria);
            foreach ($list as $v) {
                $newWxShopsInfo[] = normalize($v);
            }
        }
        $res['list'] = $newWxShopsInfo;
        return $res;
    }

    public function setDefaultWxShops($companyId, $wx_shop_id)
    {
        $shopEntity = $this->find($wx_shop_id);
        if (!$shopEntity) {
            throw new UpdateResourceFailedException('wx_shop_id={$wx_shop_id}的微信店铺不存在');
        }
        $em = $this->getEntityManager();
        $em->getConnection()->beginTransaction();
        try {
            $em->getConnection('default')->update('wxshops', ['is_default' => 0], ['company_id' => $companyId]);
            $isDefault = true;
            $shopEntity->setIsDefault($isDefault);
            $em->persist($shopEntity);
            $em->flush();
            $em->getConnection()->commit();
        } catch (\Exception $e) {
            $em->getConnection()->rollback();
            throw $e;
        }

        return true;
    }
}
