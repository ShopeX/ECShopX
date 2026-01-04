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

namespace OpenapiBundle\Services\Kaquan;

use Dingo\Api\Exception\ResourceException;
use OpenapiBundle\Services\BaseService;
use OpenapiBundle\Constants\ErrorCode;
use OpenapiBundle\Exceptions\ErrorException;

use KaquanBundle\Services\KaquanService;
use KaquanBundle\Services\DiscountCardService as CardService;
use KaquanBundle\Services\UserDiscountService;
use MembersBundle\Services\MemberService;

class DiscountCardService extends BaseService
{

    public function getEntityClass(): string
    {
        return KaquanService::class;
    }

    /**
     * @param array $filter
     * @param int $page
     * @param int $pageSize
     * @param array $orderBy
     * @param string $cols
     * @param bool $needCountSql
     * @param bool $noHaving true表示不需要对数据做聚合处理，false表示需要对数据做聚合处理。 聚合筛选附近门店
     * @return array
     */
    public function list(array $filter, int $page = 1, int $pageSize = 10, array $orderBy = [], string $cols = "*", bool $needCountSql = true, bool $noHaving = false): array
    {
        //这个条件没想好怎么用criteria来组织, 先用SQL来处理
        $conn = app('registry')->getConnection('default');
        $sql = "select card_id,title,description,discount,reduce_cost,card_type,date_type,begin_date,end_date,fixed_term,least_cost,most_cost,use_bound,apply_scope,quantity from kaquan_discount_cards where company_id = " . $filter['company_id'];
        $sql .= " order by created desc";
        $count = $pageSize;
        $offset = ($page - 1) * $count;
        $sql .= " limit ". $offset . "," . $count;
        $result['list'] = $conn->executeQuery($sql)->fetchAll();
        $total_sql = "select count(*) from kaquan_discount_cards where company_id = " . $filter['company_id'];
        $result['total_count'] = $conn->executeQuery($total_sql)->fetchColumn();
        if ($result['total_count'] == 0) {
            $this->handlerListReturnFormat($result, $page, $pageSize);
            return $result;
        }
        $card_ids = implode(array_column($result['list'], 'card_id'), ',');

        $get_nums = $conn->fetchAll("SELECT card_id, count(*) as num FROM kaquan_user_discount WHERE company_id={$filter['company_id']} AND card_id IN({$card_ids}) GROUP BY card_id");
        if ($get_nums) {
            $get_nums = array_bind_key($get_nums, 'card_id');
        }
        foreach ($result['list'] as $key => $list) {
            $result['list'][$key]['get_num'] = intval($get_nums[$list['card_id']]['num'] ?? 0);
        }
        $this->handlerListReturnFormat($result, $page, $pageSize);
        return $result;
    }

    /**
     * 单张券发放
     * @param  array $params 
     */
    public function userSendDiscountCard($params)
    {
        $userDiscountService = new UserDiscountService();
        try {
            $memberService = new MemberService();
            $mobile = $memberService->getMobileByUserId($params['plat_account'], $params['company_id']);
            if (empty($mobile)) {
                throw new ErrorException(ErrorCode::MEMBER_NOT_FOUND, '会员ID错误，未查询到会员信息');
            }
            $activity_name = $params['activity_name'] ?? '';
            $result = $userDiscountService->userGetCard($params['company_id'], $params['card_id'], $params['plat_account'], $params['source_type'], 0, $activity_name);
        } catch (\Exception $e) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $e->getMessage());
        }
        return $result;
    }

    /**
     * 查询会员已领取的优惠券列表
     * @param  array $filter   查询条件
     * @param  string $page     
     * @param  string $pageSize 
     */
    public function getUserDiscountList($filter, $page, $pageSize)
    {
        $memberService = new MemberService();
        $mobile = $memberService->getMobileByUserId($filter['user_id'], $filter['company_id']);
        if (empty($mobile)) {
            throw new ErrorException(ErrorCode::MEMBER_NOT_FOUND, '商派会员Id错误，未查询到会员信息');
        }
        $userDiscountService = new UserDiscountService();
        $result = $userDiscountService->getNewUserCardList($filter, $page, $pageSize, false, true);
        $this->handlerListReturnFormat($result, $page, $pageSize);
        return $result;
    }

}
