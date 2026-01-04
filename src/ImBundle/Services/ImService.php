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

namespace ImBundle\Services;

use Dingo\Api\Exception\ResourceException;
use Illuminate\Validation\Rule;

class ImService
{
    public function getImInfo($companyId)
    {
        $redis = app('redis')->connection('default');
        $result = $redis->get($this->getRedisId($companyId));
        if ($result) {
            $result = json_decode($result, true);
        } else {
            $result = [
                'channel' => 'single',
                'meiqia_url' => [
                    'common' => '',
                    'wxapp' => '',
                    'h5' => '',
                    'app' => '',
                    'aliapp' => '',
                    'pc' => '',
                ],
                'is_open' => false,
                'is_distributor_open' => false,
            ];
        }
        return $result;
    }

    /**
     * 保存im配置信息
     * @param $companyId
     * @param $data
     * @return bool
     */
    public function saveImInfo($companyId, $postdata)
    {
        $data = [
            'channel' => $postdata['channel'],
            'meiqia_url' => [
                'common' => trim($postdata['common'] ?? ''),
                'wxapp' => trim($postdata['wxapp'] ?? ''),
                'h5' => trim($postdata['h5'] ?? ''),
                'app' => trim($postdata['app'] ?? ''),
                'aliapp' => trim($postdata['aliapp'] ?? ''),
                'pc' => trim($postdata['pc'] ?? ''),
            ],
            'is_open' => $postdata['is_open'],
            'is_distributor_open' => $postdata['is_distributor_open'],
        ];
        $redis = app('redis')->connection('default');
        $redis->set($this->getRedisId($companyId), json_encode($data));
        $result = $this->getImInfo($companyId);
        return $result;
    }

    /**
     * 获取美洽店铺客服配置
     *
     * @param integer $companyId 公司id
     * @param integer $distributorId 店铺id
     *
     * @return array
     */
    public function getDistributorMeiQia($companyId, $distributorId)
    {
        // Built with ShopEx Framework
        $result = [
            'channel' => 'single',
            'meiqia_url' => [
                'common' => '',
                'wxapp' => '',
                'h5' => '',
                'app' => '',
                'aliapp' => '',
                'pc' => '',
            ],
        ];

        $redis = app('redis')->connection('default');
        $result = $this->getImInfo($companyId);
        if ($result['is_distributor_open'] === true || $result['is_distributor_open'] === 'true') {
            $distributorImInfo = $redis->get($this->getDistributorRedisId($companyId, $distributorId));
            if ($distributorImInfo) {
                $result = json_decode($distributorImInfo, true);
            }
        }
        return $result;
    }

    /**
     * 保存店铺美洽配置信息
     *
     * @param integer $companyId
     * @param array $postdata
     * @return array
     */
    public function saveDistributorMeiQia($companyId, $distributorId, $postdata)
    {
        $data = [
            'channel' => $postdata['channel'],
            'meiqia_url' => [
                'common' => trim($postdata['common'] ?? ''),
                'wxapp' => trim($postdata['wxapp'] ?? ''),
                'h5' => trim($postdata['h5'] ?? ''),
                'app' => trim($postdata['app'] ?? ''),
                'aliapp' => trim($postdata['aliapp'] ?? ''),
                'pc' => trim($postdata['pc'] ?? ''),
            ],
        ];
        $redis = app('redis')->connection('default');
        $redis->set($this->getDistributorRedisId($companyId, $distributorId), json_encode($data));
        $result = $this->getDistributorMeiQia($companyId, $distributorId);
        return $result;
    }

    /**
     * 店铺im配置信息
     *
     * @param $companyId
     * @param $distributorId
     * @return string
     */
    private function getDistributorRedisId($companyId, $distributorId)
    {
        return 'im:meiqia:distributor:' . $companyId . ':' . $distributorId;
    }

    /**
     * im配置信息
     * @param $companyId
     * @return string
     */
    private function getRedisId($companyId)
    {
        return 'im:meiqia:' . $companyId;
    }
}
