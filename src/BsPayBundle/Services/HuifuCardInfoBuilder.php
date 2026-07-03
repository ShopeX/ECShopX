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

namespace BsPayBundle\Services;

/**
 * 斗拱结算卡 card_info 组装（对公传 branch_code，对私传证件字段）
 */
class HuifuCardInfoBuilder
{
    public static function build(array $data): array
    {
        $cardInfo = [
            'card_type' => $data['card_type'],
            'card_name' => $data['card_name'],
            'card_no' => $data['card_no'],
            'prov_id' => $data['prov_id'],
            'area_id' => $data['area_id'],
            'branch_name' => $data['branch_name'] ?? '',
            'mp' => $data['mp'],
        ];

        if ((int) ($data['card_type'] ?? 0) === 0) {
            $cardInfo['branch_code'] = $data['branch_code'] ?? '';
        } else {
            $cardInfo['cert_type'] = '00';
            $cardInfo['cert_no'] = $data['cert_no'] ?? '';
            $cardInfo['cert_validity_type'] = $data['cert_validity_type'] ?? '';
            $cardInfo['cert_begin_date'] = $data['cert_begin_date'] ?? '';
            $cardInfo['cert_end_date'] = $data['cert_end_date'] ?? '';
        }

        return $cardInfo;
    }
}
