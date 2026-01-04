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

class WorkWechatService
{
    /**
     * 获取企业微信配置 - 导购
     * @param $companyId
     * @return array|mixed
     */
    public function getWorkWechatConfig($companyId = null, $corpid = null)
    {
        $key = $corpid ? $this->getCropidCacheKey($corpid) : $this->getCompanyCacheKey($companyId);
        $redis = app('redis')->connection('default');
        $result = $redis->get($key);
        if (!$result) {
            throw new ResourceException('您还没有配置企业微信信息！');
        }
        $result = json_decode($result, true);
        if (isset($result['agents']['dianwu'])) {
            $url = config('common.dis_workwechat_h5_baseuri');
            $result['agents']['dianwu']['h5_url'] = $url.'?company_id='.$companyId;
            $url_info = parse_url($url);
            $host = $url_info['host'] ?? '';
            if (isset($url_info['port'])) {
                $host .= ':'.$url_info['port'];
            }
            $result['agents']['dianwu']['h5_host'] = $host;
        }

        // if (!isset($result['agents']['app']['appid']) || $result['agents']['app']['appid'] == '') {
        //     throw new ResourceException('您还没有配置导购企业微信app信息！');
        // }
        return $result;
    }

    /**
     * 获取展示配置信息
     * @param null $companyId
     * @param null $corpid
     */
    public function getViewConfig($companyId = null, $corpid = null)
    {
        // KEY: U2hvcEV4
        $key = $corpid ? $this->getCropidCacheKey($corpid) : $this->getCompanyCacheKey($companyId);
        $redis = app('redis')->connection('default');
        $result = $redis->get($key);
        if (!$result) {
            $data = [
                'corpid' => '',
                'agents' => [
                    'app' => [
                        'appid' => '',
                        'agent_id' => '',
                        'secret' => '',
                        'token' => '',
                        'aes_key' => '',
                    ],
                    'customer' => [
                        'secret' => '',
                        'token' => '',
                        'aes_key' => '',
                    ],
                    'report' => [
                        'secret' => '',
                        'token' => '',
                        'aes_key' => '',
                    ],
                    'dianwu' => [
                        'agent_id' => '',
                        'secret' => '',
                    ],
                ]
            ];
        } else {
            $data = json_decode($result, true);
            if (!isset($data['agents']['dianwu'])) {
                $data['agents']['dianwu'] = [
                    'agent_id' => '',
                    'secret' => '',
                ];
            }
        }
        $data = $this->attachConfig($data, $companyId);
        return $data;
    }

    /**
     * 通过小程序appid获取company_id
     * @param $appid
     * @return null
     */
    public function getCompanyIdByAppid($appid)
    {
        $redis = app('redis')->connection('default');
        $appidKey = $this->getAppIdCacheKey($appid);
        $companyId = $redis->get($appidKey);
        return $companyId ?: null;
    }

    /**
     * 保存企业微信配置
     * @param $companyId
     * @param array $params
     * @return array|mixed
     */
    public function saveWorkWechatConfig($companyId = null, array $params)
    {
        // KEY: U2hvcEV4
        $rules = [
            'show' => ['required_with:0,1', '是否开启企业微信'],
            'corpid' => ['required', '企业微信corpid必填'],
/*            'agents.app.appid' => ['required', '企业微信小程序appid必填'],
            'agents.app.agent_id' => ['required', '企业微信小程序agent_id必填'],
            'agents.app.secret' => ['required', '企业微信小程序secret必填'],
            'agents.app.token' => ['required', '企业微信小程序Token必填'],
            'agents.app.aes_key' => ['required', '企业微信小程序EncodingAESKey必填'],
            'agents.customer.secret' => ['required', '企业微信客户联系Secret必填'],
            'agents.customer.token' => ['required', '企业微信客户联系Token必填'],
            'agents.customer.aes_key' => ['required', '企业微信客户联系EncodingAESKey必填'],
            'agents.report.secret' => ['required', '企业微信管理工具Secret必填'],
            'agents.report.token' => ['required', '企业微信管理工具Token必填'],
            'agents.report.aes_key' => ['required', '企业微信管理工具EncodingAESKey必填'],
            'agents.dianwu.agent_id' => ['required', '企业微信店务自建应用agent_id必填'],
            'agents.dianwu.secret' => ['required', '企业微信店务自建应用secret必填'],*/
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }
        $key = $this->getCompanyCacheKey($companyId);
        $redis = app('redis')->connection('default');
        $data = [
            'show' => '1',//trim($params['show']),
            'corpid' => trim($params['corpid']),
            'company_id' => $companyId,
            'agents' => [
                'app' => [
                    'appid' => trim($params['agents']['app']['appid'] ?? ''),
                    'agent_id' => trim($params['agents']['app']['agent_id'] ?? ''),
                    'secret' => trim($params['agents']['app']['secret'] ?? ''),
                    'token' => trim($params['agents']['app']['token'] ?? ''),
                    'aes_key' => trim($params['agents']['app']['aes_key'] ?? ''),
                ],
                'customer' => [
                    'secret' => trim($params['agents']['customer']['secret'] ?? ''),
                    'token' => trim($params['agents']['customer']['token'] ?? ''),
                    'aes_key' => trim($params['agents']['customer']['aes_key'] ?? ''),
                ],
                'report' => [
                    'secret' => trim($params['agents']['report']['secret'] ?? ''),
                    'token' => trim($params['agents']['report']['token'] ?? ''),
                    'aes_key' => trim($params['agents']['report']['aes_key'] ?? ''),
                ],
                'dianwu' => [
                    'agent_id' => trim($params['agents']['dianwu']['agent_id'] ?? ''),
                    'secret' => trim($params['agents']['dianwu']['secret'] ?? ''),
                ],
            ]
        ];
        $cropidKey = $this->getCropidCacheKey($data['corpid']);
        $redis->set($key, json_encode($data));
        $redis->set($cropidKey, json_encode($data));

        app('log')->debug('key:' . $key . '读取存入数据:' . $redis->get($key));

        if (isset($data['agents']['app']['appid'])) {
            $appidKey = $this->getAppIdCacheKey($data['agents']['app']['appid']);
            $redis->set($appidKey, $companyId);
        }
        $result = $this->attachConfig($data, $companyId);
        return $result;
    }

    public function getCompanyCacheKey($companyId = null)
    {
        return $companyId ? 'workwechat:config:' . sha1($companyId) : 'workwechat:config';
    }

    public function getCropidCacheKey($cropid)
    {
        return 'workwechat:configcropid:' . $cropid;
    }

    public function getAppIdCacheKey($appid)
    {
        return 'workwechat:configappid:' . $appid;
    }

    public function checkCodeAuth($companyId = null, $corpid = null)
    {
        $config = $this->getWorkWechatConfig($companyId, $corpid);
        return $config['show'] ? true : false;
    }

    public function attachConfig($config, $company_id)
    {
        $result = $config;
        if (isset($result['agents']['customer'])) {
            $result['agents']['customer']['URL'] = '/workwechat/customer/notify/'.$result['corpid'];
            if (!($result['agents']['customer']['token'] ?? '')) {
                // 随机生成token
                $result['agents']['customer']['token'] = str_random(rand(9, 13));
            }
            if (!($result['agents']['customer']['aes_key'] ?? '')) {
                // 随机生成aes_keys
                $result['agents']['customer']['aes_key'] = str_random(43);
            }
        }
        if (isset($result['agents']['dianwu'])) {
            $url = config('common.dis_workwechat_h5_baseuri');
            $result['agents']['dianwu']['h5_url'] = $url.'?company_id='.$company_id;
            $url_info = parse_url($url);
            $host = $url_info['host'] ?? '';
            if (isset($url_info['port'])) {
                $host .= ':'.$url_info['port'];
            }
            $result['agents']['dianwu']['h5_host'] = $host;
            $verify_domain_service = new WorkWechatVerifyDomainService();
            $verify_info = $verify_domain_service->getVerifyInfoByCompanyId($company_id);
            $result['agents']['dianwu']['verify_file_name'] = $verify_info ? ($verify_info['name'].'.txt') : '';
        }
        return $result;
    }
}
