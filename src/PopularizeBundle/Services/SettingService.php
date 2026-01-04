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
use PointBundle\Services\PointMemberRuleService;

/**
 * 分销基础配置
 */
class SettingService
{
    // 支持推广员分佣层级
    public $supportPromoterLevel = [
        'first_level' => [
            'level' => 1,
            'name' => '上级',
        ],
         'second_level' => [
             'level' => 2,
             'name' => '上上级',
         ],
    ];

    // 支持充值分佣层级
    public $rechargePromoterLevel = [
        'first_level' => [
            'level' => 1,
            'name' => '上级',
        ],
         'second_level' => [
             'level' => 2,
             'name' => '上上级',
         ],
    ];

    /**
     * 开启分销
     */
    public function openPopularize($companyId, $isOpen = true)
    {
        // 后续增加开启关闭记录
        $key = $this->getOpenPopularizeKey($companyId);
        return app('redis')->set($key, $isOpen);
    }

    /**
     * 获取当前企业是否开启分销
     */
    public function getOpenPopularize($companyId)
    {
        $key = $this->getOpenPopularizeKey($companyId);
        $status = app('redis')->get($key);
        return ($status == 'true') ? 'true' : 'false';
    }

    private function getOpenPopularizeKey($companyId)
    {
        return 'isOpenPopularize:'.$companyId;
    }


    public function closePointCommission(int $companyId)
    {
        $config = $this->getConfig($companyId);
        if ($config['commission_type'] == 'point') {
            $config['commission_type'] = 'money';
            $this->setConfig($companyId, $config);
        }
        return true;
    }


    /**
     * 保存分销配置
     */
    public function setConfig($companyId, $data)
    {
        $config = $this->getConfig($companyId);
        $config['is_open_wechat'] = isset($data['is_open_wechat']) ? $data['is_open_wechat'] : false;
        $config['limit_rebate'] = $data['limit_rebate'];
        $config['limit_time'] = $data['limit_time'];
        $config['goods'] = $data['goods'];
        $config['isOpenGuide'] = $data['isOpenGuide'];
        $config['isOpenShop'] = $data['isOpenShop'];

        if (isset($data['internalOpenIdentity'])) {
            $config['internalOpenIdentity'] = $data['internalOpenIdentity'];
        }
        if (isset($data['isOpenPromoterInformation'])) {
            $config['isOpenPromoterInformation'] = $data['isOpenPromoterInformation'];
        }
//        $config['guideImg'] = $data['guideImg'];
        if (isset($data['banner_img'])) {
            $config['banner_img'] = $data['banner_img'];
        }

        if (isset($data['shop_img'])) {
            $config['shop_img'] = $data['shop_img'];
        }
        if (isset($data['share_title'])) {
            $config['share_title'] = $data['share_title'];
        }
        if (isset($data['share_des'])) {
            $config['share_des'] = $data['share_des'];
        }
        if (isset($data['applets_share_img'])) {
            $config['applets_share_img'] = $data['applets_share_img'];
        }
        if (isset($data['h5_share_img'])) {
            $config['h5_share_img'] = $data['h5_share_img'];
        }
        $config['qrcode_bg_img'] = $data['qrcode_bg_img'] ?? '';
        $config['promoter_qrcode_bg_img'] = $data['promoter_qrcode_bg_img'] ?? '';

        if (isset($data['custompage_template_id'])) {
            $config['custompage_template_id'] = $data['custompage_template_id'];
        }
        $config['isOpenRecharge'] = $data['isOpenRecharge'];
        $config['commission_type'] = isset($data['commission_type']) && in_array($data['commission_type'], ['money', 'point']) ? $data['commission_type'] : 'money';

        // 设置积分返佣的前提是需打开积分设置
        if ($config['commission_type'] == 'point') {
            $isOpenPoint = (new PointMemberRuleService($companyId))->getIsOpenPoint();
            if (!$isOpenPoint) {
                throw new ResourceException('未打开积分设置不可设置积分返佣');
            }
        }

        if (isset($data['change_promoter']) && $data['change_promoter']) {
            // 成为推广员条件
            $config['change_promoter'] = $data['change_promoter'];
        }

        // 为每个推广员层级设置佣金比例
        if (isset($data['popularize_ratio']) && $data['popularize_ratio']) {
            $config['popularize_ratio']['type'] = $data['popularize_ratio']['type'] ?: 'profit';
            $ratioType = $config['popularize_ratio']['type'];

            $totalRatio = array_sum(array_column($data['popularize_ratio'][$ratioType], 'ratio'));
            if ($ratioType == 'profit' && $totalRatio > 100) {
                //如果以订单利润分佣则不能所有比例不能超过百分之100
                throw new ResourceException('按利润分佣不能总分佣比例不可超过100%');
            }
            if ($ratioType == 'order_money' && $totalRatio > 50) {
                //如果以订单实付金额分佣则不能所有比例不能超过百分之50
                throw new ResourceException('按订单金额分佣不能总分佣比例不可超过50%');
            }

            foreach ($this->supportPromoterLevel as $name => $row) {
                if (isset($data['popularize_ratio'][$ratioType][$name])) {
                    $config['popularize_ratio'][$ratioType][$name] = [
                        'ratio' => $data['popularize_ratio'][$ratioType][$name]['ratio'],
                        'name' => $row['name'],
                    ];
                } else {
                    $config['popularize_ratio'][$ratioType][$name] = [
                        'ratio' => 0,
                        'name' => $row['name'],
                    ];
                }
            }
        }
        // 为每个推广员层级设置佣金比例
        if (isset($data['recharge']) && $data['recharge']) {
            foreach ($this->rechargePromoterLevel as $name => $row) {
                if (isset($data['recharge']['profit'][$name])) {
                    $config['recharge']['profit'][$name] = [
                        'ratio' => $data['recharge']['profit'][$name]['ratio'],
                        'name' => $row['name'],
                    ];
                } else {
                    $config['recharge']['profit'][$name] = [
                        'ratio' => 0,
                        'name' => $row['name'],
                    ];
                }
            }
        }

        $key = 'popularizeConfig:'.$companyId;
        return app('redis')->set($key, json_encode($config));
    }

    /**
     * 获取配置信息
     */
    public function getConfig($companyId)
    {
        $key = 'popularizeConfig:'.$companyId;
        $data = app('redis')->get($key);
        if ($data) {
            $data = json_decode($data, true);
            foreach ($this->supportPromoterLevel as $name => $row) {
                if (!isset($data['popularize_ratio']['profit'][$name])) {
                    $data['popularize_ratio']['profit'][$name] = [
                        'ratio' => 0,
                        'name' => $row['name'],
                    ];
                    $data['popularize_ratio']['order_money'][$name] = [
                        'ratio' => 0,
                        'name' => $row['name'],
                    ];
                }
            }

            foreach ($this->rechargePromoterLevel as $name => $row) {
                if (!isset($data['recharge']['profit'][$name])) {
                    $data['recharge']['profit'][$name] = [
                        'ratio' => 0,
                        'name' => $row['name'],
                    ];
                }
            }

            $data['commission_type'] = $data['commission_type'] ?? 'money';
            $data['goods'] = $data['goods'] ?? 'all';
        }

        $data = $data ? $data : $this->getDefaultConfig();
        $data['isOpenPopularize'] = $this->getOpenPopularize($companyId);
/**
$data['popularize_ratio']['profit']['first_level']['name'] = '业绩';
unset($data['popularize_ratio']['profit']['second_level']);
$data['popularize_ratio']['order_money']['first_level']['name'] = '业绩';
unset($data['popularize_ratio']['order_money']['second_level']);
**/


//var_dump($data['popularize_ratio']['profit']);
        return $data;
    }

    /**
     * 获取默认配置
     */
    public function getDefaultConfig()
    {
        $config['limit_rebate'] = 1;
        $config['limit_time'] = 0;
        $config['isOpenGuide'] = false;
        $config['isOpenShop'] = false;
        $config['isOpenRecharge'] = false;
        $config['goods'] = 'all';
//        $config['guideImg'] = '';
        $config['banner_img'] = '';
        $config['qrcode_bg_img'] = '';
        $config['custompage_template_id'] = 0;
        $config['commission_type'] = 'money';

        // 默认无门槛
        $config['change_promoter']['type'] = 'no_threshold';
        $config['change_promoter']['filter'] = array(
            'no_threshold' => 0,
            'vip_grade' => 'vip',
            'consume_money' => 0,
            'order_num' => 0,
        );

        // 利润分佣
        $config['popularize_ratio']['type'] = 'profit';
        foreach ($this->supportPromoterLevel as $name => $row) {
            $config['popularize_ratio']['profit'][$name] = [
                'ratio' => 0,
                'name' => $row['name'],
            ];
            $config['popularize_ratio']['order_money'][$name] = [
                'ratio' => 0,
                'name' => $row['name'],
            ];
        }
        foreach ($this->rechargePromoterLevel as $name => $row) {
            $config['recharge']['profit'][$name] = [
                'ratio' => 0,
                'name' => $row['name'],
            ];
        }
        return $config;
    }
}
