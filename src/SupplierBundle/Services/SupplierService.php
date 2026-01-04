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

namespace SupplierBundle\Services;

use CompanysBundle\Services\SettingService;
use Dingo\Api\Exception\ResourceException;
use SupplierBundle\Entities\Supplier;
use WechatBundle\Jobs\SendTemplateMessageJob;

class SupplierService
{
    /**
     * @var \SupplierBundle\Repositories\SupplierRepository
     */
    public $repository;

    public function __construct()
    {
        $this->repository = app('registry')->getManager('default')->getRepository(Supplier::class);
    }

    //供应商审核状态
    public function getCheckStatus(&$operator = [])
    {
        if ($operator['logintype'] != 'supplier') {
            return true;
        }
        $rs = $this->repository->getInfo(['operator_id' => $operator['operator_id']]);
        $operator['supplier_check_status'] = $rs['is_check'] ?? 0;
        return true;
    }

    public function getIdByOperatorId($operator_id)
    {
        $rs  = $this->repository->getInfo(['operator_id' => $operator_id]);
        return $rs['id'] ?? 0;
    }

    /**
     * 处理供应商的菜单。
     * 如果供应商没有填写入驻资料，不显示其它菜单
     */
    public function setMenu($operator_id, $operator_type, &$menu = [])
    {
        if ($operator_type != 'supplier') {
            return true;
        }
        $operator = [
            'logintype' => 'supplier',
            'operator_id' => $operator_id,
        ];
        $this->getCheckStatus($operator);
        if ($operator['supplier_check_status'] != 1) {
            foreach ($menu as $key => $value) {
                if ($value['url'] != '/supplier/setting') {
                    unset($menu[$key]);
                    continue;
                }
                if (isset($value['children'])) {
                    foreach ($value['children'] as $child => $childMenu) {
                        if (!strstr($childMenu['url'], 'supplier_register')) {
                            unset($menu[$key]['children'][$child]);
                        }
                    }
                }
            }
        }
        return true;
    }

    /**
     * 发送商品待审核消息
     */
    public function sendGoodsAuditMessage($companyId, $goodsName)
    {
        $settingService = new SettingService();
        $wx_openid = $settingService->getWxOpenid($companyId);
        if (!$wx_openid) {
            return true;
        }
        $tousers = explode("\n", $wx_openid);

        $template_id = 'ceNoFEz8Ui2hwEGkHORovmAkiTUvVWBkq2OpmeNR_bw';

        //防止频繁发送
        $key = 'wxa_msg:' . $template_id;
        $redis = app('redis');
        if ($redis->get($key)) {
            return true;
        }
        $redis->set($key, 1, 'EX', 10);//10s内只发送一次

        // $tousers = ['obs5C6PehRYViUvYmyXiqu6VRSeU'];
        foreach ($tousers as $touser) {
            $msg_data = [
                'thing1' => $goodsName,
                'time2' => date('Y-m-d H:i:s'),
            ];
            $jobParams = [
                'company_id' => $companyId,
                'template_id' => $template_id,
                'touser' => $touser,
                'msg_data' => $msg_data,
            ];
            $gotoJob = (new SendTemplateMessageJob($jobParams))->onQueue('slow');
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        }
    }

    public function __call($method, $parameters)
    {
        return $this->repository->$method(...$parameters);
    }

}
