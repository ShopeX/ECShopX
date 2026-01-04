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

namespace OrdersBundle\Traits;

use OrdersBundle\Entities\NormalOrdersRelDada;
use MembersBundle\Services\MemberService;

trait GetOrderIdTrait
{
    public function genId($identifier)
    {
        // 压测默认，订单号生成方式修改;
        if (env('TEST_MODE', false) == true) {
            return date('ymdhis').substr(implode(null, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 7);
        }
        $time = time();
        $startTime = 1325347200;//2012-01-01 做为初始年
        //当前时间相距初始年的天数，4位可使用20年
        $day = floor(($time - $startTime) / 86400);

        //确定每90秒的的订单生成 一天总共有960个90秒，控制在三位
        $minute = floor(($time - strtotime(date('Y-m-d'))) / 90);

        //防止通过订单号计算出商城生成的订单数量，导致泄漏关键数据
        $redisId = app('redis')->hincrby(date('Ymd'), $minute, rand(1, 9));

        //设置过期时间
        app('redis')->expire(date('Ymd'), 86400);

        $id = $day . str_pad($minute, 3, '0', STR_PAD_LEFT) . str_pad($redisId, 5, '0', STR_PAD_LEFT) . str_pad($identifier % 10000, 4, '0', STR_PAD_LEFT);//16位

        return $id;
    }

    /**
     * 根据达达的状态，获取订单号，将订单号作为筛选条件
     * @param  array $filter 筛选条件
     * @return array filter  处理后的筛选条件
     */
    public function getOrderIdByDadaStatus($filter)
    {
        if (isset($filter['order_status']) && $filter['order_status'] && is_string($filter['order_status'])) {
            $order_status = explode('_', $filter['order_status']);
            if ($order_status[0] == 'DADA') {
                $normalOrdersRelDadaRepository = app('registry')->getManager('default')->getRepository(NormalOrdersRelDada::class);
                $dada_filter = [
                    'company_id' => $filter['company_id'],
                    'dada_status' => $order_status[1],
                ];
                $relDaDaList = $normalOrdersRelDadaRepository->getLists($dada_filter, 'order_id');
                $filter['order_id'] = array_column($relDaDaList, 'order_id');
                unset($filter['order_status']);
            }
        }
        return $filter;
    }

    /**
     * 根据推广员身份名称、推广员手机号，将订单号作为筛选条件
     * @param  array $filter 筛选条件
     * @return array filter  处理后的筛选条件
     */
    public function getOrderIdByPromoter($filter)
    {
        $conn = app("registry")->getConnection("default");
        $identityOrderId = $promoterOrderId = [];
        $is_promoter_identity = $is_promoter_mobile = false;
        if (isset($filter['promoter_identity']) && $filter['promoter_identity']) {
            $sql = "select promoter.user_id from popularize_promoter promoter left join popularize_promoter_identity identity on promoter.identity_id=identity.id where promoter.company_id=".$filter['company_id']." and identity.name='".$filter['promoter_identity']."'";
            $lists = $conn->executeQuery($sql)->fetchAll();
            $userIds = array_column($lists, 'user_id');
            $userIds = array_filter($userIds, function($value) {
                return $value !== null && $value !== false && $value !== "" && $value !== 0;
            });
            if ($userIds) {
                $sql = "select id,order_id,user_id,buy_user_id from popularize_brokerage where company_id=".$filter['company_id']." and brokerage_type='first_level' and user_id in (".implode($userIds, ',').")";
                $lists = $conn->executeQuery($sql)->fetchAll();
                $identityOrderId = array_column($lists, 'order_id');
                $identityOrderId = array_filter($identityOrderId, function($value) {
                    return $value !== null && $value !== false && $value !== "" && $value !== 0;
                });
            }
            unset($filter['promoter_identity']);
            $is_promoter_identity = true;
        }
        if (isset($filter['promoter_mobile']) && $filter['promoter_mobile']) {
            $memberService = new MemberService();
            $userId = $memberService->getUserIdByMobile($filter['promoter_mobile'], $filter['company_id']);
            if ($userId) {
                $sql = "select brokerage.order_id from popularize_promoter promoter left join popularize_brokerage brokerage on promoter.user_id=brokerage.user_id where promoter.company_id=".$filter['company_id']." and promoter.user_id=".$userId;
                $lists = $conn->executeQuery($sql)->fetchAll();
                $promoterOrderId = array_column($lists, 'order_id');
                $promoterOrderId = array_filter($promoterOrderId, function($value) {
                    return $value !== null && $value !== false && $value !== "" && $value !== 0;
                });

            }
            unset($filter['promoter_mobile']);
            $is_promoter_mobile = true;
        }
        if (!$is_promoter_identity && !$is_promoter_mobile) {
            return $filter;
        }
        if ($is_promoter_identity && $is_promoter_mobile && (empty($identityOrderId) || empty($promoterOrderId))) {
            $filter['order_id'] = -1;
        } elseif($is_promoter_identity && $is_promoter_mobile && !empty($identityOrderId) && !empty($promoterOrderId)) {
            $filter['order_id'] = array_intersect($identityOrderId, $promoterOrderId);
        } elseif ($is_promoter_identity) {
            $filter['order_id'] = empty($identityOrderId) ? -1 : $identityOrderId;
        } elseif ($is_promoter_mobile) {
            $filter['order_id'] = empty($promoterOrderId) ? -1 : $promoterOrderId;
        }
        if (isset($filter['order_id']) && $filter['order_id'] != -1) {
            $filter['order_id'] = array_values($filter['order_id']);
        }
        return $filter;
    }

    /**
     * 获取订单相关的推广员信息
     * @param  array $orderLists 订单列表
     */
    public function getOrderPromoter($companyId, $orderLists)
    {
        if (empty($orderLists)) {
            return [];
        }
        $conn = app("registry")->getConnection("default");
        // promoter_name:推广员
        // promoter_mobile:推广员手机号
        // order_total_rebate:订单总佣金
        // order_first_rebate:销售佣金（一级）
        // order_second_rebate:推荐人佣金(二级)
        // promoter_identity:推广员身份
        // p_promoter_name:上级推广员
        // p_promoter_mobile:上级推广员手机号
        // promoter_is_close:是否结算
        $orderIds = array_column($orderLists, 'order_id');
        $sql = "select order_id,user_id,is_close from popularize_brokerage where brokerage_type='first_level' and source='order' and order_id in (".implode($orderIds, ',').")";
        $userLists = $conn->executeQuery($sql)->fetchAll();
        $userIds = array_column($userLists, 'user_id');
        $userIds = array_filter($userIds, function($value) {
            return $value !== null && $value !== false && $value !== "" && $value !== 0;
        });
        if (empty($userIds)) {
            return [];
        }
        $result = [];
        // 查询推广员
        $sql = "select promoter.user_id,promoter.promoter_name,promoter.pname p_promoter_name,promoter.pmobile p_promoter_mobile,identity.name promoter_identity from popularize_promoter promoter left join popularize_promoter_identity identity on promoter.identity_id=identity.id where promoter.user_id in (".implode($userIds, ',').")";
        $promoterLists = $conn->executeQuery($sql)->fetchAll();
        $promoterLists = array_column($promoterLists, null, 'user_id');
        
        // 查询推广员手机号
        $memberService = new MemberService();
        $mobiles = $memberService->getMobileByUserIds($companyId, $userIds);
        foreach ($userLists as $key => $value) {
            $promoterData[$value['order_id']] = $promoterLists[$value['user_id']] ?? [];
            $promoterData[$value['order_id']]['promoter_mobile'] = $mobiles[$value['user_id']] ?? '';
            $promoterData[$value['order_id']]['promoter_is_close'] = $value['is_close'];
        }
        // 查询订单分佣金额
        $where = "company_id=".$companyId." and order_id in (".implode($orderIds, ',').") and source='order'";
        $sql = "select order_id,sum(rebate) order_total_rebate from popularize_brokerage where ".$where." group by order_id";
        $orderRebate = $conn->executeQuery($sql)->fetchAll();
        $orderRebate = array_column($orderRebate, null, 'order_id');
        // 查询订单分佣金额
        $sql = "select order_id,sum(rebate) as rebate from popularize_brokerage where ".$where." and brokerage_type='first_level' group by order_id";
        $firstRebate = $conn->executeQuery($sql)->fetchAll();
        $firstRebate = array_column($firstRebate, null, 'order_id');
        $sql = "select order_id,sum(rebate) as rebate from popularize_brokerage where ".$where." and brokerage_type='second_level' group by order_id";
        $secondRebate = $conn->executeQuery($sql)->fetchAll();
        $secondRebate = array_column($secondRebate, null, 'order_id');
        foreach ($orderIds as $order_id) {
            if (isset($promoterData[$order_id])) {
                $result[$order_id] = $promoterData[$order_id];
            }
            isset($orderRebate[$order_id]) and $result[$order_id]['order_total_rebate'] = $orderRebate[$order_id]['order_total_rebate'] ?? '';
            if (isset($firstRebate[$order_id])) {
                $result[$order_id]['order_first_rebate'] = $firstRebate[$order_id]['rebate'] ?? '';
            }
            if (isset($secondRebate[$order_id])) {
                $result[$order_id]['order_second_rebate'] = $secondRebate[$order_id]['rebate'] ?? '';
            }
        }
        return $result;
    }
}
