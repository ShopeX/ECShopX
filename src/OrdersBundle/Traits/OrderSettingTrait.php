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

use Dingo\Api\Exception\ResourceException;
use OrdersBundle\Entities\NormalOrders;
use SystemLinkBundle\Services\WdtErpSettingService;
use SystemLinkBundle\Services\JushuitanSettingService;

trait OrderSettingTrait
{
    public $settingType = [
        'order_finish_time' => 7, //默认7天
        'order_cancel_time' => 15, //默认15分钟
        'notpay_order_wxapp_notice' => 0,  //默认0分钟，不发送通知
        'latest_aftersale_time' => 0, //默认确认收货后不可申请售后
        'aftersale_close_time' => 7, //申请售后未处理7天关闭
        'auto_refuse_time' => 0, //售后驳回
        'auto_aftersales' => false, //未发货售后自动同意
        'offline_aftersales' => false,
        'is_refund_freight' => 0,
    ];

    public function getOrdersSetting($companyId, $type = null)
    {
        $key = $this->__key();
        $setting = app('redis')->hget($key, $companyId);
        $result = $setting ? json_decode($setting, true) : [];

        foreach ($this->settingType as $k => $v) {
            if (!isset($result[$k])) {
                $result[$k] = $v;
            }
        }

        if ($type) {
            return $result[$type];
        }
        return $result;
    }

    public function setOrdersSetting($companyId, $setting)
    {
        $key = $this->__key();
        if (!$setting) {
            $setting = $this->settingType;
        } else {
            $setting = $this->__commonParams($setting);
            $this->__checkParams($companyId, $setting);
        }
        $setting = json_encode($setting);
        app('redis')->hset($key, $companyId, $setting);
        return $this->getOrdersSetting($companyId);
    }

    private function __commonParams($setting)
    {
        $setting['order_cancel_time'] = intval($setting['order_cancel_time']);
        $setting['latest_aftersale_time'] = intval($setting['latest_aftersale_time']);
        return $setting;
    }


    private function __checkParams($companyId, $setting)
    {
        if ($setting['order_cancel_time'] < 5) {
            throw new ResourceException('订单自动取消时间需大于等于5分钟');
        }
        // 检查是否存在未处理的售前退款-聚水潭
        if ($setting['auto_aftersales'] == 'true') {
            $this->__checkAutoAftersalesJst($companyId);
        }
        // 检查是否存在未处理的售前退款-旺店通
        if ($setting['auto_aftersales'] == 'true') {
            $this->__checkAutoAftersalesWdt($companyId);
        }
        return true;
    }

    private function __checkAutoAftersalesJst($companyId)
    {
        $service = new JushuitanSettingService();
        $setting = $service->getJushuitanSetting($companyId);
        if (!isset($setting) || $setting['is_open']==false) {
            return true;
        }
        $normalOrdersRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
        $count = $normalOrdersRepository->count(['company_id' => $companyId, 'cancel_status' => 'WAIT_PROCESS', 'order_status' => 'PAYED']);
        if ($count > 0) {
            throw new ResourceException('有未审核的取消订单申请，不能开启自动审批同意');
        }
        return true;
    }

    private function __checkAutoAftersalesWdt($companyId)
    {
        $wtdService = new WdtErpSettingService();
        $wdtSetting = $wtdService->getWdtErpSetting($companyId);
        if (!isset($wdtSetting) || $wdtSetting['is_open']==false) {
            return true;
        }

        $normalOrdersRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
        $count = $normalOrdersRepository->count(['company_id' => $companyId, 'cancel_status' => 'WAIT_PROCESS', 'order_status' => 'PAYED']);
        if ($count > 0) {
            throw new ResourceException('有未审核的取消订单申请，不能开启自动审批同意');
        }
        return true;
    }

    // public function getAllSetting($type = null)
    // {
    //     $allSetting = app('redis')->hgetall($key);
    //     foreach ($allSetting as $companyId => $setting) {
    //         $setting = json_decode($setting, true);
    //         if ($type) {
    //             $result[$companyId] = $setting[$type];
    //         }
    //     }
    //     return $result ?? [];
    // }

    private function __key()
    {
        return 'order_validity_setting';
    }
}
