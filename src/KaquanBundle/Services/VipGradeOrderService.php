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

namespace KaquanBundle\Services;

use Dingo\Api\Exception\ResourceException;

use KaquanBundle\Entities\VipGradeOrder;
use KaquanBundle\Entities\VipGrade;
use KaquanBundle\Entities\VipGradeRelUser;
use OrdersBundle\Entities\Trade;

use OrdersBundle\Entities\OrderAssociations;
use OrdersBundle\Traits\GetOrderIdTrait;
use CompanysBundle\Traits\GetDefaultCur;
use PopularizeBundle\Services\PromoterGradeService;
use PromotionsBundle\Jobs\FirePromotionsActivity;

class VipGradeOrderService
{
    use GetOrderIdTrait;
    use GetDefaultCur;

    private $entityRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(VipGradeOrder::class);
    }

    public function createData($params)
    {
        $vipGradeRepository = app('registry')->getManager('default')->getRepository(VipGrade::class);
        $info = $vipGradeRepository->getInfo(['vip_grade_id' => $params['vip_grade_id']]);

        if (!$info) {
            throw new ResourceException(trans('KaquanBundle.no_such_member_card'));
        }

        //已购买高等级付费会员，再购买低等级付费会员时提示
        $vipgrade = $this->userVipGradeGet($params['company_id'], $params['user_id']);
        if (($vipgrade['vip_type'] ?? '') == 'svip' && $info['lv_type'] != 'svip') {
            throw new ResourceException(trans('KaquanBundle.already_purchased_higher_grade'));
        }

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            foreach ($info['price_list'] as $list) {
                if ($params['card_type'] == $list['name']) {
                    $params['card_type'] = $list;
                    $params['price'] = $list['price'] * 100;
                }
            }
            $params['title'] = $info['grade_name'];
            $params['lv_type'] = $info['lv_type'];
            $params['discount'] = $info['privileges']['discount'];
            $params['order_id'] = $this->genId($params['user_id']);
            $params['order_status'] = 'NOTPAY';

            $cur = $this->getCur($params['company_id']);
            $params['fee_type'] = isset($cur['currency']) ? $cur['currency'] : '';
            $params['fee_rate'] = isset($cur['rate']) ? $cur['rate'] : '';
            $params['fee_symbol'] = isset($cur['symbol']) ? $cur['symbol'] : '';

            $result = $this->entityRepository->create($params);

            $orderAssociations = [
                'order_id' => $result['order_id'],
                'title' => $result['title'],
                'company_id' => $result['company_id'],
                'shop_id' => $result['shop_id'],
                'user_id' => $result['user_id'],
                'total_fee' => $result['price'],
                'order_status' => 'NOTPAY',
                'create_time' => time(),
                'order_type' => 'memberCard',
                'total_rebate' => 0,
                'member_discount' => 0,
                'coupon_discount' => 0,
                'order_class' => 'memberCard',
                'distributor_id' => $params['distributor_id'],
                'fee_rate' => $result['fee_rate'],
                'fee_type' => $result['fee_type'],
                'fee_symbol' => $result['fee_symbol'],
                'mobile' => '',
            ];
            $orderAssociationsRepository = app('registry')->getManager('default')->getRepository(OrderAssociations::class);
            $data = $orderAssociationsRepository->create($orderAssociations);
            $conn->commit();
            return array_merge($result, $data);
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    public function receiveMemberCard($params)
    {
        // KEY: U2hvcEV4
        $vipGradeRepository = app('registry')->getManager('default')->getRepository(VipGrade::class);
        $info = $vipGradeRepository->getInfo(['vip_grade_id' => $params['vip_grade_id']]);

        if (!$info) {
            throw new ResourceException(trans('KaquanBundle.no_such_member_card'));
        }

        if ($info['is_disabled'] || $info['is_disabled'] == 'true') {
            throw new ResourceException(trans('KaquanBundle.member_card_disabled'));   
        }

        if ($params['card_type'] == 'custom') {
            $params['card_type'] = [
                'day' => $params['day'],
                'desc' => trans('KaquanBundle.backend_manual_gift_description', ['day' => $params['day']]),
                'name' => 'custom',
                'price' => 0,
            ];
        } else {
            foreach ($info['price_list'] as $list) {
                if ($params['card_type'] == $list['name']) {
                    $params['card_type'] = $list;
                    $params['price'] = intval($list['price']) * 100;
                }
            }
        }
        if (isset($params['source_type']) && $params['source_type'] != 'sale') {
            $params['price'] = 0;
        }

        $params['title'] = $info['grade_name'];
        $params['lv_type'] = $info['lv_type'];
        $params['discount'] = $info['privileges']['discount'];
        $params['order_id'] = $this->genId($params['user_id']);
        $params['order_status'] = 'DONE';
        $result = $this->entityRepository->create($params);

        if (isset($params['source_type']) && $params['source_type'] != 'sale') {
            $filter = [
                'user_id' => $result['user_id'],
                'company_id' => $result['company_id'],
                'order_id' => $result['order_id'],
            ];
            if ($params['source_type'] == 'admin') {
                $data = $this->addMemberVipGrade($filter, false);
            } else {
                $data = $this->addMemberVipGrade($filter, true);
            }
            $date = date('Ymd');
            $redisKey = $this->__key($data['company_id'], $data['vip_type'], $date);
            app('redis')->sadd($redisKey, $data['user_id']);
        }
        return $result;
    }


    public function tradeSuccUpdateOrderStatus($orderData)
    {
        $filter = [
            'user_id' => $orderData['user_id'],
            'company_id' => $orderData['company_id'],
            'order_id' => $orderData['order_id'],
        ];

        $promoterGradeService = new PromoterGradeService();
        $promoterGradeService->upgradeGrade($orderData['company_id'], $orderData['user_id']);

        return $this->orderStatusUpdate($filter, ['order_status' => 'DONE']);
    }

    public function orderStatusUpdate($filter, $updateInfo)
    {
        // KEY: U2hvcEV4
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $result = $this->entityRepository->updateOneBy($filter, $updateInfo);

            $orderAssociationsRepository = app('registry')->getManager('default')->getRepository(OrderAssociations::class);
            $result = $orderAssociationsRepository->update($filter, $updateInfo);

            $data = $this->addMemberVipGrade($filter);

            $date = date('Ymd');
            $redisKey = $this->__key($data['company_id'], $data['vip_type'], $date);
            app('redis')->sadd($redisKey, $data['user_id']);
            $conn->commit();
            return $result;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    private function __key($companyId, $type, $date)
    {
        return "MemberCard:".$companyId.":".$type.":".$date;
    }

    private function addMemberVipGrade($filter, $isVerify = false)
    {
        $info = $this->entityRepository->getInfo($filter);
        if (!$info) {
            throw new ResourceException(trans('KaquanBundle.no_such_member_card'));
        }
        $entityRepository = app('registry')->getManager('default')->getRepository(VipGradeRelUser::class);
        $memberFilter = [
            'user_id' => $info['user_id'],
            'company_id' => $info['company_id'],
        ];
        $memberRelList = $entityRepository->lists($memberFilter);
        $relList = array_column($memberRelList['list'], null, 'vip_type');

        //非购买的会卡,需要验证是否已经有过购买历史，赠送或者领取只能记录一次
        if ($isVerify && isset($relList[$info['lv_type']])) {
            throw new ResourceException(trans('KaquanBundle.already_member_no_repeat'));
        }

        $params['company_id'] = $info['company_id'];
        $params['user_id'] = $info['user_id'];
        $params['vip_type'] = $info['lv_type'];
        $params['vip_grade_id'] = $info['vip_grade_id'];

        if (!$relList) {
            $nowTime = time();
            $params['end_date'] = $nowTime + ($info['card_type']['day'] * 24 * 3600);
            $result = $entityRepository->create($params);
            $this->upgradeGradePromotions($info);
            $this->vipGradeTriggerPackage($info);
            return $result;
        } else {
            $svipRel = isset($relList['svip']) ? $relList['svip'] : [];
            $vipRel = isset($relList['vip']) ? $relList['vip'] : [];
            switch ($info['lv_type']) {
            case "svip":
                if ($svipRel) {
                    $svipFilter = array_merge($memberFilter, ['vip_type' => 'svip']);
                    $svipSurplusDay = $svipRel['end_date'] - time();
                    $nowTime = ($svipSurplusDay < 0) ? time() : $svipRel['end_date'];
                    $svipParams['end_date'] = $nowTime + ($info['card_type']['day'] * 24 * 3600);
                    $result = $entityRepository->updateOneBy($svipFilter, $svipParams);
                } else {
                    $nowTime = time();
                    $params['end_date'] = $nowTime + ($info['card_type']['day'] * 24 * 3600);
                    $result = $entityRepository->create($params);
                    $this->upgradeGradePromotions($info);
                }

                if ($vipRel) {
                    //当续费的会员为svip，并且存在vip的历史时，需要重新计算vip的到期时间（svip结束时间+vip当前剩余的天数）
                    $vipFilter = array_merge($memberFilter, ['vip_type' => 'vip']);
                    $vipSurplusDay = $vipRel['end_date'] - time();
                    $vipParams['end_date'] = ($vipSurplusDay < 0) ? $result['end_date'] : ($vipRel['end_date'] + $info['card_type']['day'] * 24 * 3600);
                    $entityRepository->updateOneBy($vipFilter, $vipParams);
                }

                $this->vipGradeTriggerPackage($info);

                return $result;
            case "vip":
                if ($vipRel) {
                    $vipFilter = array_merge($memberFilter, ['vip_type' => 'vip']);
                    $vipSurplusDay = $vipRel['end_date'] - time();
                    $endDate = ($vipSurplusDay < 0) ? time() : $vipRel['end_date'];
                    $vipParams['end_date'] = $endDate + ($info['card_type']['day'] * 24 * 3600);
                    $result = $entityRepository->updateOneBy($vipFilter, $vipParams);
                } else {
                    $endDate = 0;
                    if ($svipRel) {
                        $svipSurplusDay = $svipRel['end_date'] - time();
                        $endDate = ($svipSurplusDay > 0) ? $svipRel['end_date'] : 0;
                    }
                    $nowTime = $endDate ? $endDate : time();
                    $params['end_date'] = $nowTime + ($info['card_type']['day'] * 24 * 3600);
                    $result = $entityRepository->create($params);
                    $this->upgradeGradePromotions($info);
                }
                $this->vipGradeTriggerPackage($info);
                return $result;
            }
        }
    }

    private function vipGradeTriggerPackage(array $info): bool
    {
        if ($info['source_type'] == 'sale') {
            app('log')->debug('触发优惠券发放，company_id：' . $info['company_id'] . ' user_id:' . $info['user_id'] . ' vip_grade_id:' . $info['vip_grade_id']);
            // 触发优惠券包发放
            (new PackageSetService())->triggerPackage((int)$info['company_id'], (int)$info['user_id'], (int)$info['vip_grade_id'], 'vip_grade', true);
        }
        return true;
    }


    /**
     * 付费会员升级，主动触发营销活动
     */
    public function upgradeGradePromotions($info)
    {
        // 只有购买会员进行赠送
        if ($info['source_type'] == 'sale') {
            // 会员等级提升，触发优惠活动
            $activityMemberInfo['vip_grade_type'] = $info['lv_type'];
            $activityMemberInfo['user_id'] = $info['user_id'];
            $activityMemberInfo['mobile'] = $info['mobile'];
            $activityMemberInfo['grade_name'] = $info['title'];
            $job = (new FirePromotionsActivity($info['company_id'], $activityMemberInfo, 'member_vip_upgrade'));
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);
        }
    }

    public function userVipGradeGet($companyId, $userId, $ifAll = false)
    {
        $defaultVip = 'svip';
        $entityRepository = app('registry')->getManager('default')->getRepository(VipGradeRelUser::class);
        $memberFilter = [
            'user_id' => $userId,
            'company_id' => $companyId,
            'end_date|gt' => time(),
        ];
        $memberInfo = $entityRepository->lists($memberFilter);

        $vipGradeRepository = app('registry')->getManager('default')->getRepository(VipGrade::class);
        $vipGrade = $vipGradeRepository->lists(['company_id' => $companyId]);

        //没有配置付费会员卡信息 并且没有购买记录 告知前端 付费会员卡购买功能未开启，并且指定用户没有购买记录 和 有效的会员卡信息
        if (!$memberInfo['list'] && !$vipGrade) {
            return ['is_open' => false, 'is_vip' => false, 'is_had_vip' => false];
        }

        //付费会员卡功能已开启 并且没有购买记录 告知前端 付费会员卡购买功能已开启，并且指定用户没有购买记录 和 有效的会员卡信息
        if ($vipGrade && !$memberInfo['list']) {
            $gradedata['is_open'] = false;
            foreach ($vipGrade as $value) {
                if (!$gradedata['is_open'] && (!$value['is_disabled'] || $value['is_disabled'] === 'false')) {
                    $gradedata = $value;
                    $gradedata['is_open'] = true;
                    break;
                }
                if ($value['is_default'] && (!$value['is_disabled'] || $value['is_disabled'] === 'false')) {
                    $gradedata = $value;
                    $gradedata['is_open'] = true;
                    break;
                }
            }
            $gradedata['discount'] = $gradedata['privileges']['discount'] ?? 0;
            $gradedata['is_vip'] = false;
            $gradedata['is_had_vip'] = false;
            return $gradedata;
        }

        //付费会员卡已有购买记录，返回给前端当前会员卡的有效期和状态，并且带有是否已经开启购买会员卡功能
        $userVipGrade = array_column($vipGrade, null, 'lv_type');
        $isOpen = false;
        foreach ($vipGrade as $value) {
            if (!$value['is_disabled'] || $value['is_disabled'] === 'false') {
                $isOpen = true;
                break;
            }
        }
        $result = [];
        foreach ($memberInfo['list'] as $value) {
            $value['lv_type'] = $value['vip_type'];
            //是否购买过会员卡
            $value['is_had_vip'] = true;
            //是否有效会员卡
            $value['is_vip'] = true;
            $value['end_time'] = date('Y-m-d', $value['end_date']);
            $value['day'] = 0;
            if (($value['end_date'] - time()) > 0) {
                $value['day'] = ceil(abs($value['end_date'] - time()) / (24 * 3600));
            }
            $value['valid'] = true;
            if (intval($value['day']) <= 0) {
                $value['valid'] = false;
                $value['is_vip'] = false;
            }
            //是否设置了付费会员卡
            // $value['is_open'] = false;
            $gradeData = $userVipGrade[$value['vip_type']] ?? [];
            if ($gradeData) {
                // $value['is_open'] = (!$gradeData['is_disabled'] || $gradeData['is_disabled'] === 'false') ? true : false;
                $value['discount'] = $gradeData['privileges']['discount'] ?? 0;
                $value['grade_name'] = $gradeData['grade_name'];
                $value['guide_title'] = $gradeData['guide_title'];
                $value['background_pic_url'] = $gradeData['background_pic_url'];
            } else {
                $value['valid'] = false;
                // $value['is_open'] = false;
            }
            $value['is_open'] = $isOpen;
            $result[$value['vip_type']] = $value;
        }

        if ($ifAll) {
            return $result;
        }
        if (isset($result[$defaultVip])) {
            return $result[$defaultVip];
        } else {
            return reset($result);
        }
    }

    public function userListVipGradeGet($companyId, $memberList)
    {
        $vipGradeRepository = app('registry')->getManager('default')->getRepository(VipGrade::class);
        $vipGrade = $vipGradeRepository->lists(['company_id' => $companyId]);
        if (!$vipGrade) {
            return $memberList;
        }

        $entityRepository = app('registry')->getManager('default')->getRepository(VipGradeRelUser::class);
        $memberFilter = [
            'user_id' => array_column($memberList, 'user_id'),
            'company_id' => $companyId,
            'end_date|gt' => time(),
        ];
        $vipList = $entityRepository->lists($memberFilter,  ["id" => "DESC"], 1000, 1);
        if (!$vipList['list']) {
            return $memberList;
        }

        $vipRelUser = [];
        foreach ($vipList['list'] as $value) {
            if (isset($vipRelUser[$value['user_id']]) && $vipRelUser[$value['user_id']] == 'svip') {
                continue;
            }
            $vipRelUser[$value['user_id']] = $value['vip_type'];
        }

        foreach ($memberList as $key => $value) {
            if (isset($vipRelUser[$value['user_id']])) {
                $memberList[$key]['vip_grade'] = $vipRelUser[$value['user_id']];
            }
        }

        return $memberList;
    }

    public function getUserIdByVipGrade($filter)
    {
        $entityRepository = app('registry')->getManager('default')->getRepository(VipGradeRelUser::class);
        $list = $entityRepository->lists($filter);
        return $list['list'];
    }

    /**
     * 批量获取会员的付费会员信息
     */
    public function getUserVipGrade($companyId, $userIds)
    {
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $criteria->select('reluser.user_id,reluser.vip_type,reluser.end_date,vipgrade.grade_name,vipgrade.vip_grade_id')
                 ->from('kaquan_vip_rel_user', 'reluser')
                 ->leftJoin('reluser', 'kaquan_vip_grade', 'vipgrade', 'reluser.vip_grade_id = vipgrade.vip_grade_id')
                 ->andWhere($criteria->expr()->in('reluser.user_id', $userIds))
                 ->andWhere($criteria->expr()->eq('reluser.company_id', $companyId));

        $list = $criteria->execute()->fetchAll();
        $result = [];
        if ($list) {
            foreach ($list as $val) {
                $result[$val['user_id']][$val['vip_type']] = $val;
                $result[$val['user_id']][$val['vip_type']]['day'] = $val['end_date'] > time() ? ceil(abs($val['end_date'] - time()) / (24 * 3600)) : 0;
            }
        }
        return $result;
    }

    /**
     * 批量获取会员的付费会员信息
     */
    public function getUserVipGrade2($userIds, $status = false, $row = '')
    {
        if (!$row) {
            $row = 'reluser.user_id,reluser.vip_type,reluser.end_date,vipgrade.grade_name,vipgrade.vip_grade_id';
        }
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $criteria->select($row)
                 ->from('kaquan_vip_rel_user', 'reluser')
                 ->leftJoin('reluser', 'kaquan_vip_grade', 'vipgrade', 'reluser.vip_grade_id = vipgrade.vip_grade_id')
                 ->andWhere($criteria->expr()->in('reluser.user_id', $userIds));
        if ($status) {
            $criteria->andWhere($criteria->expr()->gt('end_date', time()));
        }

        $list = $criteria->execute()->fetchAll();
        $result = [];
        if ($list) {
            foreach ($list as $val) {
                $result[$val['user_id']][$val['vip_type']] = $val;
                $result[$val['user_id']][$val['vip_type']]['day'] = ceil(abs($val['end_date'] - time()) / (24 * 3600));
            }
        }
        return $result;
    }

    public function incrSales($orderId, $companyId)
    {
        return true;
    }

    public function getOrderInfo($companyId, $orderId)
    {
        $result = [
            'orderInfo' => [],
            'tradeInfo' => [],
        ];
        $filter['company_id'] = $companyId;
        $filter['order_id'] = $orderId;
        $orderInfo = $this->entityRepository->getInfo($filter);
        if (!$orderInfo) {
            return $result;
        }
        $orderInfo['order_type'] = 'membercard';
        $orderInfo['item_id'] = $orderInfo['vip_grade_id'];
        $orderInfo['item_num'] = 1;
        $orderInfo['total_fee'] = (int)$orderInfo['price'];
        $orderInfo['freight_fee'] = 0;
        $orderInfo['cost_fee'] = 0;
        $orderInfo['item_fee'] = $orderInfo['price'];
        $orderInfo['create_time'] = $orderInfo['created'];

        $result['orderInfo'] = $orderInfo;

        $tradeRepository = app('registry')->getManager('default')->getRepository(Trade::class);
        $trade = $tradeRepository->getTradeList($filter);
        if ($trade['list']) {
            $result['tradeInfo'] = $trade['list'][0];
            $result['orderInfo']['pay_type'] = $result['tradeInfo']['payType'];
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
        return $this->entityRepository->$method(...$parameters);
    }
}
