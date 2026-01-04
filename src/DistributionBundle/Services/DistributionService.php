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

namespace DistributionBundle\Services;

use Dingo\Api\Exception\ResourceException;
use GoodsBundle\Services\ItemsService;

class DistributionService
{
    // 商品按照默认配置分润比例
    public const PROFIT_ITEM_DEFAULT = 0;

    public const DISTRIBUTION_RATIO = 100;
    /**
     * 获取分润配置
     * @param $companyId
     * @return array|mixed
     */
    public function getDistributionConfig($companyId = null)
    {
        $info = [
            'company_id' => $companyId,
            'distributor' => [
                'show' => 0,
                'distributor' => 0,
                'seller' => 0,
                'popularize_seller' => 0,
                'distributor_seller' => 0,
                'plan_limit_time' => 0,
            ],
        ];

        $key = $this->getCompanyCacheKey($companyId);
        $redis = app('redis')->connection('default');
        $result = $redis->get($key);
        if ($result) {
            $result = json_decode($result, true);
            $info = array_merge_deep($info, $result);
        }
        return $info;
    }

    /**
     * 保存分润配置
     * @param $companyId
     * @param array $params
     * @return array|mixed
     */
    public function setDistributionConfig($companyId, array $params)
    {
        $rules = [
            'distributor.show' => ['required_with:0,1', trans('DistributionBundle/Services/DistributionService.distributor_show_config')],
            'distributor.distributor' => ['required', trans('DistributionBundle/Services/DistributionService.distributor_config_required')],
            'distributor.seller' => ['required', trans('DistributionBundle/Services/DistributionService.seller_config_required')],
            'distributor.popularize_seller' => ['required', trans('DistributionBundle/Services/DistributionService.popularize_seller_config_required')],
            'distributor.distributor_seller' => ['required', trans('DistributionBundle/Services/DistributionService.distributor_seller_config_required')],
            'distributor.plan_limit_time' => ['required', trans('DistributionBundle/Services/DistributionService.plan_limit_time_required')],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }
        $redis = app('redis')->connection('default');
        $data = [
            'company_id' => $companyId,
            'distributor' => $params['distributor'],
        ];
        $key = $this->getCompanyCacheKey($companyId);
        $redis->set($key, json_encode($data));

        $itemsService = new ItemsService();
        $itemsService->updateProfitBy(['company_id' => $companyId, 'profit_type' => self::PROFIT_ITEM_DEFAULT], self::PROFIT_ITEM_DEFAULT, bcdiv($params['distributor']['popularize_seller'], 100, 4));
        $result = $this->getDistributionConfig($companyId);
        return $result;
    }

    public function getCompanyCacheKey($companyId)
    {
        return 'distribution:config:' . sha1($companyId);
    }

    public function getInRuleCacheKey($companyId)
    {
        return 'distribution:config:inRule:' . sha1($companyId);
    }

    // 保存进店规则
    public function setDistributionConfigInRule($companyId, array $params)
    {
        $key = $this->getInRuleCacheKey($companyId);
        $redis = app('redis')->connection('default');
        $redis->set($key, json_encode($params));

        return $params;
    }
    
    public function getDistributionConfigInRule($companyId)
    {
        $key = $this->getInRuleCacheKey($companyId);
        $redis = app('redis')->connection('default');
        $result = $redis->get($key);
        $result = json_decode($result, true);
        // 加入初始值
        if (!$result) {
            $result = [
                'distributor_code' => [
                    'status' => true, // 店铺码进店
                    'sort' => 1,
                ],
                'shop_assistant' => [
                    'status' => false, // 导购物料进店
                    'express_time' => $params['shop_assistant']['express_time'] ?? 0, // 导购物料进店
                    'sort' => 2,
                ], 
                'shop_white' => [
                    'status' => false, // 进入白名会员店,
                    'sort' => 3,
                ],
                'shop_assistant_pro' => [
                    'status' => true, // 专属导购所属店
                    'sort' => 4,
                ],
                'radio_type' => 1, // 兜底策略 1 默认点 2介绍面
                'default_shop' => 0, // 默认店
                'intro_page' => '', // 2 介绍页面
            ];
            $redis->set($key, json_encode($result));
        }

        return $result;
    }
    
}
