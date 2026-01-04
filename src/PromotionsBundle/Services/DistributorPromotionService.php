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

namespace PromotionsBundle\Services;

use PromotionsBundle\Entities\RegisterPromotions;
use PromotionsBundle\Entities\DistributorPromotions;

use DistributionBundle\Services\DistributorService;
use KaquanBundle\Services\UserDiscountService;
use GoodsBundle\Services\ItemsService;
use MembersBundle\Services\MemberTagsService;

class DistributorPromotionService
{
    /**
     * RegisterPromotions Repository类
     */
    public $registerPromotionsRepository = null;
    public $distributorPromotionsRepository = null;
    public $distributorService = null;

    public function __construct()
    {
        $this->registerPromotionsRepository = app('registry')->getManager('default')->getRepository(RegisterPromotions::class);
        $this->distributorPromotionsRepository = app('registry')->getManager('default')->getRepository(DistributorPromotions::class);
        $this->distributorService = new DistributorService();
    }

    /**
     * 获取分销商注册营销列表
     */
    public function getDistributorPromotionList($filter, $page = 1, $pageSize = 100)
    {
        $orderBy['id'] = 'ASC';
        $dataLists = $this->registerPromotionsRepository->lists($filter, $orderBy, $pageSize, $page);
        foreach ($dataLists['list'] as &$value) {
            $distributorData = $this->getDistributorByPromotionId($value['company_id'], $value['id'], 'register');
            $value = array_merge($value, $distributorData);
        }
        return $dataLists;
    }

    /**
     * 获取分销商注册营销详情
     */
    public function getInfo($filter)
    {
        $info = $this->registerPromotionsRepository->getInfo($filter);
        if ($info) {
            $distributorData = $this->getDistributorByPromotionId($info['company_id'], $info['id'], 'register');
            $info = array_merge($info, $distributorData);
        }
        return $info;
    }

    /**
     * 根据注册营销id获取分销商数据
     */
    public function getDistributorByPromotionId($companyId, $promotionId, $promotionType = 'register')
    {
        // ShopEx EcShopX Service Component
        $filter = [
            'company_id' => $companyId,
            'promotion_id' => $promotionId,
            'promotion_type' => $promotionType,
        ];
        $data = $this->distributorPromotionsRepository->lists($filter);
        if (!$data) {
            return $data;
        }
        $distributorIds = array_column($data, 'distributor_id');

        //获取分销商信息
        $filter = [
            'company_id' => $companyId,
            'distributor_id' => $distributorIds,
        ];
        $distridutor = $this->distributorService->lists($filter);
        $result['distributor_id']['ids'] = $distributorIds;
        $list = [];
        foreach ($distridutor['list'] as $value) {
            $list[] = [
                'key' => $value['distributor_id'],
                'label' => $value['name'],
            ];
        }
        $result['distributor_id']['list'] = $list;
        return $result;
    }

    /**
     * 根据分销商id获取 营销活动
     */
    public function getPromotionByDistributorId($companyId, $distributorId, $promotionType = 'register')
    {
        $filter = [
            'company_id' => $companyId,
            'promotion_type' => $promotionType,
            'distributor_id' => $distributorId,
        ];
        $data = $this->distributorPromotionsRepository->getInfo($filter);

        if (!$data) {
            return [];
        }

        $filter = [
            'company_id' => $companyId,
            'id' => $data['promotion_id'],
        ];
        $result = $this->registerPromotionsRepository->getInfo($filter);

        $filter = [
            'company_id' => $companyId,
            'distributor_id' => $distributorId,
        ];
        $distridutor = $this->distributorService->getInfo($filter);
        $result['distributor_id'] = $distributorId;
        $result['distributor_name'] = $distridutor['name'];
        $result['distributor_mobile'] = $distridutor['mobile'];
        return $result;
    }

    /**
     * 小程序端根据是否为分销商展示不同的注册促销活动
     */
    public function getPromotionDataConfig($companyId, $distributorId = null, $promotionType = 'register')
    {
        if (!$distributorId) {
            $filter = [
                'company_id' => $companyId,
                'register_type' => 'general',
            ];
            $promotionData = $this->registerPromotionsRepository->getInfo($filter);
        } else {
            $promotionData = $this->getPromotionByDistributorId($companyId, $distributorId, $promotionType);
        }
        return $promotionData;
    }

    /**
     * 执行营销
     */
    public function executionMarketing($filter, $userId, $mobile)
    {
        $companyId = $filter['company_id'];
        $promotionData = [];
        if (isset($filter['distributor_id']) && $distributorId = $filter['distributor_id']) {
            $promotionData = $this->getPromotionByDistributorId($companyId, $distributorId, 'register');
        }
        if (!$promotionData) {
            $filter = [
                'company_id' => $companyId,
                'register_type' => 'general',
            ];
            $promotionData = $this->getInfo($filter);
        }

        if ($promotionData && $promotionData['is_open'] == 'true') {
            //如果有设置过促销方案，则执行促销方案
            if (!empty($promotionData['promotions_value'])) {
                if (isset($promotionData['promotions_value']['items']) && !empty($promotionData['promotions_value']['items'])) {
                    //商品促销
                    //todo：改为异步队列执行
                    $this->itemsPromotions($promotionData['promotions_value']['items'], $userId, $companyId, $mobile);
                }

                if (isset($promotionData['promotions_value']['coupons']) && !empty($promotionData['promotions_value']['coupons'])) {
                    //todo：改为异步队列执行
                    $memberInfo = [
                        'user_id' => $userId,
                        'mobile' => $mobile
                    ];
                    $this->actionItemsCoupons($companyId, $promotionData['promotions_value']['coupons'], $memberInfo, '注册送优惠券');
                }

                if (isset($promotionData['promotions_value']['staff_coupons']) && !empty($promotionData['promotions_value']['staff_coupons'])) {
                    //todo：改为异步队列执行
                    $memberInfo = [
                        'user_id' => $userId,
                        'mobile' => $mobile
                    ];
                    $this->_staffCouponAction($companyId, $promotionData['promotions_value']['staff_coupons'], $memberInfo);
                }
            }
        }
        return true;
    }

    private function _staffCouponAction($companyId, $coupons, $memberInfo)
    {
        //获取会员tag
        $memberTagsService = new MemberTagsService();
        $mf = [
            'company_id' => $companyId,
            'user_id' => $memberInfo['user_id'],
        ];
        $col = ['tag_id', 'source', 'user_id'];
        $tagList = $memberTagsService->getUserRelTagList($mf, $col);
        $tagSource = array_column($tagList, 'source');
        if ($tagSource && in_array('staff', $tagSource)) {
            $this->actionItemsCoupons($companyId, $coupons, $memberInfo, '员工优惠券赠送');
        }
        return true;
    }

    /**
     * 商品权益促销
     */
    public function itemsPromotions($itemIds, $userId, $companyId, $mobile)
    {
        $itemsService = new ItemsService();
        foreach ($itemIds as $itemId) {
            try {
                $itemsService->addRightsByItemId($itemId, $userId, $companyId, $mobile);
            } catch (\Exception $e) {
                app('log')->debug($e->getMessage());
            }
        }
        return true;
    }

    /**
     * 赠送优惠券
     */
    public function actionItemsCoupons($companyId, $coupons, $memberInfo, $sourceFrom)
    {
        $userDiscountService = new UserDiscountService();
        $sendCoupons = false;
        foreach ($coupons as $couponRow) {
            for ($i = 1; $i <= $couponRow['count']; $i++) {
                try {
                    $userDiscountService->userGetCard($companyId, $couponRow['card_id'], $memberInfo['user_id'], $sourceFrom, 0, '', '', $memberInfo['mobile']);
                    $sendCoupons = true;
                } catch (\Exception $e) {
                    app('log')->debug($sourceFrom. '=>' .$e->getMessage());
                }
            }
        }

        return $sendCoupons;
    }

    public function deleteData($filter)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();

        try {
            $this->registerPromotionsRepository->deleteBy($filter);
            $filter = [
                'company_id' => $filter['company_id'],
                'promotion_id' => $filter['id'],
                'promotion_type' => 'register'
            ];
            $this->distributorPromotionsRepository->deleteBy($filter);
            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->distributorPromotionsRepository->$method(...$parameters);
    }
}
