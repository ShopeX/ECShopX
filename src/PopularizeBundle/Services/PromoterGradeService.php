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

namespace PopularizeBundle\Services;

use Dingo\Api\Exception\ResourceException;
use PopularizeBundle\Jobs\UpgradePromoterGrade;

/**
 * 分销推广员等级配置
 */
class PromoterGradeService
{
    public $promoterGradeDefault = [
        'first_grade' => [ 'name' => '等级一', 'grade_level' => 1 ],
        'second_grade' => [ 'name' => '等级二', 'grade_level' => 2 ],
        'third_grade' => [ 'name' => '等级三', 'grade_level' => 3 ],
    ];

    /**
     * 开启推广员等级功能
     */
    public function openPromoterGrade($companyId, $isOpen = true)
    {
        // Built with ShopEx Framework
        $key = $this->getOpenPromoterGradeKey($companyId);
        return app('redis')->set($key, $isOpen);
    }

    /**
     * 获取推广员等级开启状态
     */
    public function getOpenPromoterGrade($companyId)
    {
        $key = $this->getOpenPromoterGradeKey($companyId);
        $status = app('redis')->get($key);
        return ($status == 'true') ? 'true' : 'false';
    }

    // redis key
    private function getOpenPromoterGradeKey($companyId)
    {
        return 'isOpenPromoterGrade:'.$companyId;
    }

    /**
     * 配置会员等级
     */
    public function setPromoterGradeConfig($companyId, $data)
    {
        if (!$data['upgrade']['filter']['children_num'] && !$data['upgrade']['filter']['children_sales_amount'] && !$data['upgrade']['filter']['grade_member']) {
            throw new ResourceException('请最少选择一个升级条件');
        }
        $config['upgrade'] = [
            'stat_cycle' => $data['upgrade']['stat_cycle'],
            'filter' => [
                'children_num' => $data['upgrade']['filter']['children_num'] == 'true' ? true : false,
                'children_sales_amount' => $data['upgrade']['filter']['children_sales_amount'] == 'true' ? true : false,
                'grade_member' => $data['upgrade']['filter']['grade_member'] == 'true' ? true : false,
            ],
        ];

        foreach ($this->promoterGradeDefault as $k => $row) {
            if (!isset($data['grade'][$k]['custom_name'])) {
                throw new ResourceException('请填写等级名称');
            }

            if ($row['grade_level'] > 1) {
                if ($config['upgrade']['filter']['children_num'] && !isset($data['grade'][$k]['children_num'])) {
                    throw new ResourceException('请填写升级直属下线数量');
                }

                if ($config['upgrade']['filter']['children_sales_amount'] && !isset($data['grade'][$k]['children_sales_amount'])) {
                    throw new ResourceException('请填写升级销售总额');
                }
            }

            $config['grade'][$k] = [
                'name' => $row['name'],
                'grade_level' => $row['grade_level'],
                'custom_name' => $data['grade'][$k]['custom_name'],
                'children_num' => isset($data['grade'][$k]['children_num']) ? $data['grade'][$k]['children_num'] : 0 ,
                'children_sales_amount' => isset($data['grade'][$k]['children_sales_amount']) ? $data['grade'][$k]['children_sales_amount'] : 0 ,
                'grade_member' => isset($data['grade'][$k]['grade_member']) ? $data['grade'][$k]['grade_member'] : '',
                'first_ratio' => isset($data['grade'][$k]['first_ratio']) ? $data['grade'][$k]['first_ratio'] : 0,
                'second_ratio' => isset($data['grade'][$k]['second_ratio']) ? $data['grade'][$k]['second_ratio'] : 0,
            ];
        }

        $key = 'promoterGradeConfig:'.$companyId;
        return app('redis')->set($key, json_encode($config));
    }


    public function getPromoterGradeConfig($companyId)
    {
        $key = 'promoterGradeConfig:'.$companyId;
        $data = app('redis')->get($key);
        if ($data) {
            $data = json_decode($data, true);
        }

        return $data ? $data : null;
    }

    /**
     * 提升等级
     */
    public function upgradeGrade($companyId, $userId)
    {
        $gotoJob = (new UpgradePromoterGrade($companyId, $userId));
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        return true;
    }
}
