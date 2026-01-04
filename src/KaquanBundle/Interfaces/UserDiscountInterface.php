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

namespace KaquanBundle\Interfaces;

interface UserDiscountInterface
{
    /**
     * 用户领取卡券
     *
     * @param  postdata
     * @return
     */
    public function userGetCard($companyId, $cardId, $userId);

    /**
     * user del card
     *
     * @param $companyId
     * @param  $userid 用户id
     * @param   $id 用户领取的卡券自增编号
     *
     * @return boolean
     */
    public function userDelCard($companyId, $userId, $id = "", $code = "");

    /**
     * [userConsumeCard 用户使用优惠券]
     * @param  int $companyId
     * @param  string $code                优惠券码
     * @param  string $params              核销操作内容
     * @return bool
     */
    public function userConsumeCard($companyId, $code, $params = ['consume_outer_str' => '快捷买单核销']);

    /**
     * [getUserDiscountList 获取用户领取的优惠券列表]
     * @param  [array] $filter   [条件]
     * @param  [int] $page     [起始页码]
     * @param  [int] $pageSize [每页数据量]
     * @param  [array] $orderBy  [排序]
     * @return [array]
     */
    public function getUserDiscountList($filter, $page = 1, $pageSize = 50);

    /**
     * [getUserCardInfo 获取用户领取的优惠券详情]
     * @param  [type]  $filter      [description]
     * @param  boolean $getCardInfo [description]
     * @return [type]               [description]
     */
    public function getUserCardInfo($filter, $getCardInfo = false);


    /**
     * [getUserDiscountCount]
     * @param  [array] $filter
     * @return [int]
     */
    public function getUserDiscountCount($filter);
}
