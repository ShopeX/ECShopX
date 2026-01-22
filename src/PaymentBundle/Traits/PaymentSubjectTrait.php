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

namespace PaymentBundle\Traits;

/**
 * 支付收款主体处理Trait
 * 用于处理店铺收款主体逻辑，根据店铺的payment_subject决定使用平台还是店铺配置
 */
trait PaymentSubjectTrait
{
    /**
     * 根据店铺收款主体类型获取实际使用的distributorId
     * 
     * @param int $distributorId 店铺ID
     * @param int $companyId 公司ID
     * @return int 实际使用的distributorId，0表示使用平台配置
     */
    protected function getActualDistributorId($distributorId, $companyId)
    {
        // 如果distributorId为0，直接返回0（使用平台配置）
        if ($distributorId == 0) {
            return 0;
        }
        
        // 查询店铺的收款主体类型
        $distributorRepository = app('registry')->getManager('default')->getRepository(\DistributionBundle\Entities\Distributor::class);
        $distributorInfo = $distributorRepository->getInfo([
            'distributor_id' => $distributorId,
            'company_id' => $companyId
        ]);
        
        if (!$distributorInfo) {
            // 店铺不存在，使用平台配置
            return 0;
        }
        
        // payment_subject: 0=平台, 1=店铺
        // 如果收款主体为平台，使用平台配置（distributor_id=0）
        // 如果收款主体为店铺，使用店铺配置（返回原distributorId）
        return ($distributorInfo['payment_subject'] == 1) ? $distributorId : 0;
    }

    /**
     * 根据店铺收款主体类型获取实际使用的subKey（用于ChinaumsPayService）
     * 
     * @param string $subKey 子商户配置key，格式：distributor_123 或 dealer_456
     * @param int $companyId 公司ID
     * @return string 实际使用的subKey，空字符串表示使用平台配置
     */
    protected function getActualSubKey($subKey, $companyId)
    {
        // 如果subKey为空或者是dealer_开头，直接返回（不处理）
        if (empty($subKey) || strpos($subKey, 'dealer_') === 0) {
            return $subKey;
        }
        
        // 从subKey中提取distributorId（格式：distributor_123）
        if (strpos($subKey, 'distributor_') === 0) {
            $distributorId = (int)str_replace('distributor_', '', $subKey);
            
            if ($distributorId > 0) {
                // 使用getActualDistributorId方法获取实际使用的distributorId
                $actualDistributorId = $this->getActualDistributorId($distributorId, $companyId);
                
                // 如果实际使用的是平台配置，返回空字符串
                if ($actualDistributorId == 0) {
                    return '';
                }
                
                // 如果实际使用的是店铺配置，返回原subKey
                return $subKey;
            }
        }
        
        return $subKey;
    }
}

