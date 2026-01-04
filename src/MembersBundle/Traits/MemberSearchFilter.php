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

namespace MembersBundle\Traits;

use KaquanBundle\Services\VipGradeOrderService;
use PopularizeBundle\Entities\Promoter as EntitiesPromoter;

trait MemberSearchFilter
{
    public function dataFilter($postdata, $authData)
    {
        //$postdata = array_filter($postdata);
        if (isset($postdata['mobile']) && $postdata['mobile']) {
            $filter['mobile'] = $postdata['mobile'];
        }
        if (isset($postdata['remarks']) && $postdata['remarks']) {
            $filter['remarks|like'] = $postdata['remarks'];
        }
        if (isset($postdata['inviter_id']) && $postdata['inviter_id']) {
            $filter['inviter_id'] = $postdata['inviter_id'];
        }
        if (isset($postdata['user_card_code']) && $postdata['user_card_code']) {
            $filter['user_card_code'] = $postdata['user_card_code'];
        }
        if (isset($postdata['username']) && $postdata['username']) {
            $filter['username'] = $postdata['username'];
        }
        if (isset($postdata['name']) && $postdata['name']) {
            $filter['name'] = $postdata['name'];
        }
        if (isset($postdata['grade_id']) && $postdata['grade_id']) {
            if (is_numeric($postdata['grade_id'])) {
                $filter['grade_id'] = $postdata['grade_id'];
                $postdata['vip_grade'] = 'notvip';
            } else {
                $postdata['vip_grade'] = $postdata['grade_id'];
            }
        }

        if (isset($postdata['time_start_begin']) && $postdata['time_start_begin']) {
            $filter['created|gte'] = $postdata['time_start_begin'];
            $filter['created|lte'] = $postdata['time_start_end'];
        }

        if (isset($postdata['user_id']) && $postdata['user_id']) {
            $userIds = is_array($postdata['user_id']) ? $postdata['user_id'] : [$postdata['user_id']];
        }

        if (isset($postdata['have_consume']) && $postdata['have_consume']) {
            if ($postdata['have_consume'] == 'true') {
                $filter['have_consume'] = true;
            } elseif ($postdata['have_consume'] == 'false') {
                $filter['have_consume'] = false;
            }
        }

        $shopIds = isset($postdata['shop_id']) ? $postdata['shop_id'] : 0;
        $distributorIds = isset($postdata['distributor_id']) ? $postdata['distributor_id'] : 0;

        if (!$shopIds && !$distributorIds && ($authData['operator_type'] ?? '') == 'distributor') {
            $shopIds = isset($authData['shop_ids']) ? $authData['shop_ids'] : [];
            if ($shopIds) {
                $shopIds = array_column($shopIds, 'shop_id');
            }

            $distributorIds = isset($authData['distributor_ids']) ? $authData['distributor_ids'] : [];
            if ($distributorIds) {
                $distributorIds = array_column($distributorIds, 'distributor_id');
            }
        }

        $filter['company_id'] = $authData['company_id'];
        $filter['shop_id'] = $shopIds;
        $filter['distributor_id'] = $distributorIds;
        $postdata['vip_grade'] = (isset($postdata['vip_grade']) && $postdata['vip_grade']) ? $postdata['vip_grade'] : '';
        if ($postdata['vip_grade']) {
            $vipFilter['company_id'] = $authData['company_id'];
            $vipFilter['end_date|gt'] = time();
            if ($postdata['vip_grade'] != 'notvip') {
                $vipFilter['vip_type'] = explode(',', $postdata['vip_grade']);
            }
            $VipGradeOrderService = new VipGradeOrderService();
            $list = $VipGradeOrderService->getUserIdByVipGrade($vipFilter);

            $ids = array_filter(array_unique(array_column($list, 'user_id')));
            if ($ids && isset($userIds) && $userIds) {
                $userIds = array_filter(array_unique(array_intersect($userIds, $ids)));
            } elseif ($ids) {
                $userIds = $ids;
            } else {
                $userIds = [0];
            }
        }

        if (isset($postdata['tag_id']) && $postdata['tag_id']) {
            $filter['tag_id'] = $postdata['tag_id'];
        }

        if ($postdata['vip_grade'] == 'notvip') {
            if (isset($userIds) && $userIds) {
                $filter['user_id|notIn'] = $userIds;
            }
        } else {
            if (isset($userIds) && $userIds) {
                $filter['user_id|in'] = $userIds;
            } elseif (isset($userIds)) {
                return false;
            }
        }
        return $filter;
    }

    public function filterProcess($postdata, $authdata)
    {
        $userIds = [];
        if (isset($postdata['vip_grade']) && $postdata['vip_grade']) {
            $vipFilter['company_id'] = $authdata['company_id'];
            if ($postdata['vip_grade'] != 'notvip') {
                $vipFilter['vip_type'] = $postdata['vip_grade'];
            }
            $VipGradeOrderService = new VipGradeOrderService();
            $list = $VipGradeOrderService->getUserIdByVipGrade($vipFilter);
            $ids = array_filter(array_unique(array_column($list, 'user_id')));
            if ($ids && isset($userIds) && $userIds) {
                $userIds = array_filter(array_unique(array_intersect($userIds, $ids)));
            } elseif ($ids) {
                $userIds = $ids;
            }
        }
        unset($postdata['vip_grade']);

        if (isset($postdata['tag_id']) && $postdata['tag_id']) {
        }
        unset($postdata['tag_id']);

        if (isset($postdata['inviter_mobile']) && $postdata['inviter_mobile']) {
        }
        unset($postdata['inviter_mobile']);

        if (isset($postdata['salesman_mobile']) && $postdata['salesman_mobile']) {
        }
        unset($postdata['salesman_mobile']);

        if (isset($postdata['shop_id']) && $postdata['shop_id']) {
        }
        unset($postdata['shop_id']);

        if (isset($postdata['distributor_id']) && $postdata['distributor_id']) {
        }
        unset($postdata['distributor_id']);
    }

    /**
     * 根据手机号，获取推广员下级推广员、下级会员、下级推广员的下级会员的user_id
     * @param  string $companyId      企业ID
     * @param  string $promoterMobile 来源推广员会员ID
     */
    public function getPromoterUserIds($companyId, $pUserId)
    {
        $result = [-1];
        // 查询下级推广员
        $promoterRepository = app('registry')->getManager('default')->getRepository(EntitiesPromoter::class);
        $promoterInfo = $promoterRepository->getInfo(['company_id' => $companyId, 'user_id' => $pUserId, 'is_promoter' => 1]);
        if (empty($promoterInfo)) {
            return $result;
        }

        $pid = intval($promoterInfo['pid']);
        if ($pid > 0) {
            // 非顶级，只查询下级C
            $childLists = $promoterRepository->lists([
                'company_id' => $companyId,
                'pid' => $promoterInfo['promoter_id'],
                'is_promoter' => 0,
                'disabled' => 0,
            ], 1, -1);
            if ($childLists['total_count'] == 0) {
                return $result;
            }
            $result = array_column($childLists['list'], 'user_id');
            return $result;
        } else {
            // 顶级，查询下级推广员、下级C、下级推广员的下级C
            $childLists = $promoterRepository->lists([
                'company_id' => $companyId,
                'pid' => $promoterInfo['promoter_id'],
                'disabled' => 0,
            ], 1, -1);
            if ($childLists['total_count'] == 0) {
                return $result;
            }
            $userIds = [];
            $secondPid = [];
            foreach ($childLists['list'] as $value) {
                $value['is_promoter'] and $secondPid[] = $value['promoter_id'];
                $userIds[] = $value['user_id'];
            }
            if ($secondPid) {
                $secondLists = $promoterRepository->lists([
                    'company_id' => $companyId,
                    'pid' => $secondPid,
                    'is_promoter' => 0,
                    'disabled' => 0,
                ], 1, -1);
                $userIds = array_merge($userIds, array_column($secondLists['list'], 'user_id'));
            }
            $result = empty($userIds) ? [-1] : $userIds;
        }
        return $result;
    }
}
