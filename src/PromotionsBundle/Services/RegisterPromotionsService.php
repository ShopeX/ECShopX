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

use GoodsBundle\Services\ItemsService;
use PromotionsBundle\Entities\RegisterPromotions;
use PromotionsBundle\Entities\DistributorPromotions;

use KaquanBundle\Services\UserDiscountService;
use KaquanBundle\Services\VipGradeOrderService;

use Dingo\Api\Exception\StoreResourceFailedException;

class RegisterPromotionsService
{
    /**
     * RegisterPromotions Repository类
     */
    public $registerPromotionsRepository = null;

    public $distributorPromotionsRepository = null;

    public function __construct()
    {
        $this->registerPromotionsRepository = app('registry')->getManager('default')->getRepository(RegisterPromotions::class);
        $this->distributorPromotionsRepository = app('registry')->getManager('default')->getRepository(DistributorPromotions::class);
    }

    /**
     * 保存注册引导配置
     */
    public function saveRegisterPromotionsConfig($companyId, $data)
    {
        if (!$data['ad_title']) {
            throw new StoreResourceFailedException(trans('PromotionsBundle.register_title_required'));
        }

        if ($data['is_open'] && !$data['ad_pic']) {
            throw new StoreResourceFailedException(trans('PromotionsBundle.please_select_ad_image'));
        }

        // 验证注册标题长度
        // 验证赠送的参数和组织数据结构
        $registerType = (isset($data['register_type']) && $data['register_type']) ? $data['register_type'] : 'general';
        $params['company_id'] = $companyId;
        $params['ad_title'] = $data['ad_title'];
        $params['is_open'] = $data['is_open'];
        $params['ad_pic'] = $data['ad_pic'];
        $params['register_type'] = $registerType;
        $params['register_jump_path'] = $data['register_jump_path'] ?? [];
        $params['promotions_value'] = $data['promotions_value'];

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            if (isset($data['id']) && $data['id']) {
                $filter['id'] = $data['id'];
                $filter['company_id'] = $companyId;
                $result = $this->registerPromotionsRepository->updateOneBy($filter, $params);
            } else {
                $result = $this->registerPromotionsRepository->create($params);
            }

            if ($params['register_type'] == 'distributor') {
                if (!isset($data['distributor_id']) || !$data['distributor_id']) {
                    throw new StoreResourceFailedException(trans('PromotionsBundle.please_select_distributor'));
                }
                $filter = [
                    'company_id' => $companyId,
                    'promotion_id' => $result['id'],
                ];
                $this->distributorPromotionsRepository->deleteBy($filter);

                foreach ($data['distributor_id'] as $id) {
                    $param = [
                        'company_id' => $companyId,
                        'distributor_id' => $id,
                        'promotion_id' => $result['id'],
                        'promotion_type' => 'register'
                    ];
                    $this->distributorPromotionsRepository->create($param);
                }
            }
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
        return $result;
    }

    /**
     * 获取指定企业的注册引导参数
     */
    public function getRegisterPromotionsConfig($companyId, $registerType = 'general')
    {
        $filter['company_id'] = $companyId;
        if ($registerType == 'all') {
            $info = [];
            $lists = $this->registerPromotionsRepository->lists($filter);
            if ($lists['total_count'] > 0) {
                $tmpList = [];
                foreach ($lists['list'] as $row) {
                    if (in_array($row['register_type'], ['general', 'membercard'])) {
                        $tmpList[$row['register_type']] = $row;
                    }
                }
                $info = $tmpList;

                if (!isset($info['general'])) {
                    $info['general'] = [
                        'ad_pic' => '',
                        'ad_title' => '',
                        'company_id' => '',
                        'id' => '',
                        'is_open' => "false",
                        'promotions_value' => [],
                        'register_type' => '',
                    ];
                }

                if (!isset($info['membercard'])) {
                    $info['membercard'] = [
                        'ad_pic' => '',
                        'ad_title' => '',
                        'company_id' => '',
                        'id' => '',
                        'is_open' => "false",
                        'promotions_value' => [],
                        'register_type' => '',
                    ];
                }
            }
        } else {
            $filter['register_type'] = $registerType ?: 'general';
            $info = $this->registerPromotionsRepository->getInfo($filter);
        }
        return $info;
    }

    /**
     * 根据企业ID执行促销方案
     */
    public function actionPromotionByCompanyId($companyId, $userId, $mobile, $registerType = 'general')
    {
        $data = $this->registerPromotionsRepository->getInfo(['company_id' => $companyId, 'register_type' => $registerType]);
        if ($data && $data['is_open'] == 'true') {
            //如果有设置过促销方案，则执行促销方案
            if (!empty($data['promotions_value'])) {
                if (isset($data['promotions_value']['items']) && !empty($data['promotions_value']['items'])) {
                    //商品促销
                    //todo：改为异步队列执行
                    $this->itemsPromotions($data['promotions_value']['items'], $userId, $companyId, $mobile);
                }

                if (isset($data['promotions_value']['coupons']) && !empty($data['promotions_value']['coupons'])) {
                    //todo：改为异步队列执行
                    $memberInfo = [
                        'user_id' => $userId,
                        'mobile' => $mobile
                    ];
                    $this->actionItemsCoupons($companyId, $data['promotions_value']['coupons'], $memberInfo, '注册送优惠券');
                }

                if (isset($data['promotions_value']['membercard']) && !empty($data['promotions_value']['membercard'])) {
                    $memberCard = $data['promotions_value']['membercard'];
                    if (isset($memberCard['vip_grade_id']) && $memberCard['vip_grade_id']) {
                        $params = [
                            'user_id' => $userId,
                            'company_id' => $companyId,
                            'mobile' => $mobile,
                            'vip_grade_id' => $memberCard['vip_grade_id'],
                            'card_type' => $memberCard['card_type'],
                            'source_type' => 'receive',
                        ];
                        $vipGradeOrderService = new VipGradeOrderService();
                        return $vipGradeOrderService->receiveMemberCard($params);
                    }
                }
            }
        }
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
                    $userDiscountService->userGetCard($companyId, $couponRow['card_id'], $memberInfo['user_id'], $sourceFrom);
                    $sendCoupons = true;
                } catch (\Exception $e) {
                    app('log')->debug($sourceFrom . '=>' . $e->getMessage());
                }
            }
        }

        return $sendCoupons;
    }

    public function getRegisterPointConfig($companyId, $type)
    {
        $key = 'registerPoint:' . $companyId . ':' . $type;
        $result = app('redis')->get($key);

        if (!$result) {
            $result = [
                'is_open' => false,
                'point' => 0,
                'type' => 'point'
            ];
        } else {
            $result = json_decode($result, 1);
        }

        return $result;
    }

    public function saveRegisterPointConfig($companyId, $type, $config)
    {
        $key = 'registerPoint:' . $companyId . ':' . $type;
        return app('redis')->set($key, json_encode($config));
    }
}
