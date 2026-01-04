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

namespace KaquanBundle\Traits;

use Dingo\Api\Exception\ResourceException;

trait CardPackageCheckTrait
{
    /**
     * 检查卡券包创建参数内容
     *
     * @param int $companyId
     * @param array $packageContent
     * @return array
     */
    private function checkCreatePackage(int $companyId, array $packageContent): array
    {
        if (empty($packageContent)) {
            throw new ResourceException('请选择优惠券');
        }

        if (count($packageContent) > 20) {
            throw new ResourceException('请选择小于20张优惠券');
        }

        $cardIdSet = array_column($packageContent, 'card_id');

        $where = [
            'card_id' => $cardIdSet,
            'company_id' => $companyId
        ];
        // 通过ID 查询卡券是否存在以及数量是否匹配规则
        $fields = 'title,get_limit,card_id,company_id';

        $discountCards = $this->discountCards->getList($fields, $where, -1);
        $discountCardsIndex = array_column($discountCards, null, 'card_id');

        $packageVoucher = [];
        foreach ($packageContent as $item) {
            if (!isset($discountCardsIndex[$item['card_id']])) {
                throw new ResourceException('优惠券信息不存在');
            }

            if ($discountCardsIndex[$item['card_id']]['get_limit'] < $item['give_num']) {
                throw new ResourceException($discountCardsIndex[$item['card_id']]['title'].'发送数量不能大于领券限制');
            }

            $packageVoucher[] = [
                'company_id' => $companyId,
                'card_id' => $item['card_id'],
                'give_num' => $item['give_num'],
            ];
        }


        return $packageVoucher;
    }
}
