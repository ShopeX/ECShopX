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

use Dingo\Api\Exception\ResourceException;

use PromotionsBundle\Entities\SpecificCrowdDiscount;
use PromotionsBundle\Entities\SpecificCrowdDiscountRelUser;

use MembersBundle\Services\MemberTagsService;

class SpecificCrowdDiscountService
{
    public $entityRepository;
    public $entityRepositoryRelUser;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(SpecificCrowdDiscount::class);
        $this->entityRepositoryRelUser = app('registry')->getManager('default')->getRepository(SpecificCrowdDiscountRelUser::class);
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

    public function getValideUserOrientation($companyId, $userId)
    {
        //获取会员的标签ids
        $memberTagsService = new MemberTagsService();
        $userTagIds = $memberTagsService->getTagIdsByUserId($companyId, $userId);
        if (!$userTagIds) {
            return [];
        }

        //获取会员标签所参与的测定向促销
        $filter = [
            'company_id' => $companyId,
            'specific_id' => (array)$userTagIds,
            'status' => 2,
        ];
        $col = 'id,cycle_type,specific_id,start_time,end_time,status,discount,limit_total_money';
        $orderBy = ['start_time' => 'DESC', 'id' => 'DESC'];
        $activityData = $this->entityRepository->getLists($filter, $col, 1, 1, $orderBy);
        if (!$activityData) {
            return [];
        }
        $activityData = reset($activityData);
        if ($activityData['cycle_type'] == 1) {
            return $activityData;
        }
        if ($activityData['start_time'] <= time() && time() < $activityData['end_time']) {
            return $activityData;
        }
        return [];
    }

    public function getUserOrientationDiscount($companyId, $userId, $orderData)
    {
        if (in_array($orderData['order_class'], ['pointsmall', 'community'])) {
            return $orderData;
        }

        $activityData = $this->getValideUserOrientation($companyId, $userId);
        if (!$activityData) {
            return $orderData;
        }
        if ($activityData['status'] != 2) {
            return $orderData;
        }
        $activityId = $activityData['id'];
        $userTotalDiscount = app('redis')->hget($this->_key($companyId, $activityId), 'discount_'.$userId.'_'.$activityId);
        if ($activityData['limit_total_money'] <= $userTotalDiscount) {
            return $orderData;
        }
        if ($activityData['cycle_type'] == 2 && $activityData['end_time'] <= time()) {
            return $orderData;
        }
        $orderData = $this->_getOrderDataDiscount($companyId, $userId, $orderData, $activityData);
        return $orderData;
    }

    private function _getOrderDataDiscount($companyId, $userId, $orderData, $activityData)
    {
        if ($orderData['total_fee'] <= 0) {
            return $orderData;
        }
        $discount = bcdiv($activityData['discount'], 100, 2);
        $totalFee = (intval(($orderData['freight_fee'] ?? 0)) > 0) ? (bcsub($orderData['total_fee'], $orderData['freight_fee'])) : $orderData['total_fee'] ;
        if (isset($orderData['items'])) {
            $itemTotalFeeArr = array_column($orderData['items'], 'total_fee');
            array_multisort($itemTotalFeeArr, SORT_ASC, $orderData['items']);
            $itemTotalFee = array_sum(array_column($orderData['items'], 'total_fee'));
            $totalDiscountFee = bcmul($totalFee, bcsub(1, $discount, 2));
            //计算获得的优惠金额大于限额 ，取最小的限额
            if ($totalDiscountFee > $activityData['limit_total_money']) {
                $totalDiscountFee = $activityData['limit_total_money'];
            }
            $activityId = $activityData['id'];

            $userTotalDiscount = app('redis')->hget($this->_key($companyId, $activityId), 'discount_'.$userId.'_'.$activityId);
            $userTotalDiscount = $userTotalDiscount ?: 0;
            $newUserTotalDiscount = bcadd($userTotalDiscount, $totalDiscountFee);
            //如果总优惠金额大于限额，用限额减去原总优惠金额 即为本次可优惠金额
            if ($newUserTotalDiscount > $activityData['limit_total_money']) {
                $totalDiscountFee = bcsub($activityData['limit_total_money'], $userTotalDiscount);
                //$newUserTotalDiscount = $activityData['limit_total_money'];
            }
            $itemDiscountFeeArr = [];
            $orderItemDiscountFee = [];
            $orderItemCount = count($orderData['items']);
            $discountInfo = [
                'id' => $activityData['id'],
                'type' => 'member_tag_targeted_promotion',
                'rule' => '专属优惠'.$activityData['discount'].'%',
            ];
            $tdf = 0;
            foreach ($orderData['items'] as $key => $item) {
                $percent = round(bcdiv($item['total_fee'], $itemTotalFee, 5), 4);
                if ($key == $orderItemCount - 1) {
                    $discountFee = $totalDiscountFee - $tdf;
                } else {
                    $discountFee = ($orderItemCount == 1) ? $totalDiscountFee : round(bcmul($totalDiscountFee, $percent, 2));
                    $tdf += $discountFee;
                }
                $orderData['items'][$key]['discount_fee'] += $discountFee;
                $orderData['items'][$key]['coupon_discount'] = $discountFee;
                $orderData['items'][$key]['total_fee'] -= $discountFee;
                $discountInfo['info'] = '专属优惠';
                $discountInfo['discount_fee'] = $discountFee;
                if (isset($orderData['items'][$key]['discount_info'])) {
                    array_push($orderData['items'][$key]['discount_info'], $discountInfo);
                } else {
                    $orderData['items'][$key]['discount_info'][] = $discountInfo;
                }

                $orderData['items_promotion'][] = [
                    'company_id' => $item['company_id'],
                    'user_id' => $userId,
                    'shop_id' => $item['distributor_id'] ?? 0,
                    'item_id' => $item['item_id'],
                    'item_name' => $item['item_name'],
                    'item_type' => 'normal',
                    'order_type' => 'normal',
                    'activity_id' => $activityData['id'],
                    'activity_type' => 'member_tag_targeted_promotion',
                    'activity_name' => '定向促销',
                    'activity_tag' => '定向促销',
                    'activity_desc' => $discountInfo,
                    'activity_rule' => '指定会员优惠.'.$activityData['discount'],
                ];
            }
            $orderData['discount_fee'] += $totalDiscountFee;
            $orderData['total_fee'] -= $totalDiscountFee;
            $discountInfo['info'] = '专属优惠';
            $discountInfo['discount_fee'] = $totalDiscountFee;
            if (isset($orderData['discount_info'])) {
                array_push($orderData['discount_info'], $discountInfo);
            } else {
                $orderData['discount_info'][] = $discountInfo;
            }
        }
        return $orderData;
    }

    private function _key($companyId, $id)
    {
        $activityData = $this->entityRepository->getInfo(['company_id' => $companyId, 'id' => $id]);
        $cycleType = $activityData['cycle_type'];
        if ($cycleType == 1) {
            $nowMonth = date('n');
            $userTotalDiscountKey = 'userTotalDiscount:'.$companyId."_".$nowMonth;
        } elseif ($cycleType == 2) {
            $userTotalDiscountKey = 'userTotalDiscount:'.$companyId;
        }
        return $userTotalDiscountKey;
    }

    public function setUserTotalDiscount($companyId, $userId, $orderData, $type = 'plus')
    {
        if (!($orderData['discount_info'] ?? [])) {
            return true;
        }
        foreach ($orderData['discount_info'] as $value) {
            if ($value['type'] != 'member_tag_targeted_promotion') {
                continue;
            }
            $logdata = [
                'company_id' => $orderData['company_id'],
                'user_id' => $orderData['user_id'],
                'order_id' => $orderData['order_id'],
                'discount_fee' => $value['discount_fee'],
                'activity_id' => $value['id'],
                'action_type' => $type,
            ];
            $this->createLog($logdata);
            if ($type == 'plus') {
                app('redis')->hincrby($this->_key($companyId, $value['id']), 'discount_'.$userId.'_'.$value['id'], $value['discount_fee']);
            } elseif ($type == 'less') {
                app('redis')->hincrby($this->_key($companyId, $value['id']), 'discount_'.$userId.'_'.$value['id'], -$value['discount_fee']);
            }
        }
        return true;
    }

    private function createLog($logdata)
    {
        $filter = [
            'company_id' => $logdata['company_id'],
            'id' => $logdata['activity_id'],
        ];
        $info = $this->entityRepository->getInfo($filter);
        if ($info) {
            //自然月周期 存储月份，指定周期 存储月和天
            $logdata['activity_month'] = ($info['cycle_type'] == 1) ? date('n') : date('n.d') ;
            $logdata['specific_id'] = $info['specific_id'];
            $memberTagsService = new MemberTagsService();
            $filter = ['company_id' => $logdata['company_id'], 'tag_id' => $info['specific_id']];
            $tag = $memberTagsService->getInfo($filter);
            $logdata['specific_name'] = $tag['tag_name'];
        }
        $this->entityRepositoryRelUser->create($logdata);
    }


    /**
     * @brief 每月一号凌晨更新自然月的周期开始和结束时间
     *
     * @return
     */
    public function scheduleExpiredPromotionMonth()
    {
        $pageSize = 20;
        $time = strtotime(date('Y-m-d 00:00:00', time()));
        $filter = [
            'end_time|lte' => $time,
            'cycle_type' => 1,
            'status' => 2,
        ];
        $totalCount = $this->entityRepository->count($filter);
        $totalPage = ceil($totalCount / $pageSize);

        $beginDate = date('Y-m-01 00:00:00', strtotime(date("Y-m-d")));
        $endDate = date('Y-m-d 23:59:59', strtotime("$beginDate +1 month -1 day"));
        $startTime = strtotime($beginDate);
        $endTime = strtotime($endDate);

        for ($i = 1; $i <= $totalPage; $i++) {
            $result = $this->entityRepository->getLists($filter, $cols = 'id,status,end_time', $i, $pageSize);
            foreach ($result as $value) {
                if ($value['end_time'] < $startTime) {
                    $updateData['start_time'] = $startTime;
                    $updateData['end_time'] = $endTime;
                    $this->entityRepository->updateBy(['id' => $value['id']], $updateData);
                }
            }
        }
        return true;
    }

    /**
     * @brief 过期非自然月的定向促销
     *
     * @return
     */
    public function scheduleExpiredPromotion()
    {
        $pageSize = 20;
        $time = strtotime(date('Y-m-d 00:00:00', time()));
        $filter = [
            'end_time|lte' => $time,
            'cycle_type' => 2,
            'status' => 2,
        ];
        $totalCount = $this->entityRepository->count($filter);
        $totalPage = ceil($totalCount / $pageSize);
        for ($i = 1; $i <= $totalPage; $i++) {
            $result = $this->entityRepository->getLists($filter, $cols = 'id,status,end_time', $i, $pageSize);
            $ids = array_column($result, 'id');
            $data['status'] = 4;
            $this->entityRepository->updateBy(['id' => $ids], $data);
        }
        return true;
    }

    public function getDiscountLogList($filter, $page = 1, $pageSize = -1, $orderBy = [])
    {
        $lists = $this->entityRepositoryRelUser->lists($filter, '*', $page, $pageSize, $orderBy);
        return $lists;
    }

    public function __checkPostData($data)
    {
        switch ($data['specific_type']) {
            case 'member_tag':
                $memberTagsService = new MemberTagsService();
                $filter = ['company_id' => $data['company_id'], 'tag_id' => $data['specific_id']];
                $tag = $memberTagsService->getInfo($filter);
                if (!$tag) {
                    throw new ResourceException('请选择正确的针对人群');
                }
                break;
        }
        $data['discount'] = intval($data['discount']);
        if ($data['discount'] < 1 || $data['discount'] > 100) {
            throw new ResourceException(trans('PromotionsBundle.discount_percentage_range_error'));
        }
        if (!is_numeric($data['limit_total_money'])) {
            throw new ResourceException('周期内优惠限额必须为数字');
        }
        return $data;
    }
}
