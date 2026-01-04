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

namespace WorkWechatBundle\Services;

use Dingo\Api\Exception\ResourceException;

class WechatManagerService
{
    private $config;

    /**
     * 加载企业微信主要配置
     *
     * @param null $companyId
     * @param null $corpid
     * @return array
     */
    public function loadConfig($companyId = null, $app = 'salesperson'): array
    {
        // ShopEx EcShopX Core Module
        $workWechatService = new WorkWechatService();
        $result = $workWechatService->getWorkWechatConfig($companyId);

        if ($app == 'dianwu') {
            $rules = [
                'corpid'              => ['required', '未设置企业微信corpid'],
                'agents.dianwu'              => ['required', '未设置店务助手自建应用'],
                'agents.dianwu.agent_id' => ['required', '店务助手自建应用AgentID必填'],
                'agents.dianwu.secret'   => ['required', '店务助手自建应用Secret必填']
            ];
            $error = validator_params($result, $rules);
            if ($error) {
                throw new ResourceException($error);
            }

            $this->config = [
                'corp_id'       => $result['corpid'],
                'agent_id'      => $result['agents']['dianwu']['agent_id'], // 如果有 agend_id 则填写
                'secret'        => $result['agents']['dianwu']['secret'],
                'h5_host'       => $result['agents']['dianwu']['h5_host'],
                'h5_url'        => $result['agents']['dianwu']['h5_url'],
                // 指定 API 调用返回结果的类型：array(default)/collection/object/raw/自定义类名
                'response_type' => 'array',
                'log'           => [
                    'level' => 'debug',
                    'file'  => storage_path("logs/workwechat.log"),
                ],
            ];
        } else {
            $rules = [
                'corpid'              => ['required', '未设置企业微信corpid'],
                'agents'              => ['required', '未设置导购小程序'],
                'agents.app'          => ['required', '未设置导购小程序'],
                'agents.app.agent_id' => ['required', '导购小程序AgentID必填'],
                'agents.app.secret'   => ['required', '导购小程序Secret必填']
            ];
            $error = validator_params($result, $rules);
            if ($error) {
                throw new ResourceException($error);
            }
 
            $this->config = [
                'corp_id'       => $result['corpid'],
                'appid'         => $result['agents']['app']['appid'],
                'agent_id'      => $result['agents']['app']['agent_id'], // 如果有 agend_id 则填写
                'secret'        => $result['agents']['app']['secret'],
                // 指定 API 调用返回结果的类型：array(default)/collection/object/raw/自定义类名
                'response_type' => 'array',
                'log'           => [
                    'level' => 'debug',
                    'file'  => storage_path("logs/workwechat.log"),
                ],
            ];
        }

        return $this->config;
    }


    /**
     * 获取企业微信配置
     *
     * @param null $companyId
     * @param null $corpid/Users/lujunyi/Sites/yuanyuanke/ecshopx/ecshopx-api/src/WorkWechatBundle/Services/WechatManagerService.php
     * @return mixed
     */
    public function getConfig($companyId = null, $app = null)
    {
        if (empty($this->config)) {
            $this->loadConfig($companyId, $app);
        }
        return $this->config;
    }
}
