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

use CompanysBundle\Entities\DistributorWorkWechatRel;
use Dingo\Api\Exception\ResourceException;
use EasyWeChat\Factory;

/**
 * 店务企业微信自建应用
 * Class DistributorWorkWechatService
 * @package WorkWechatBundle\Services
 */
class DistributorWorkWechatService
{
    /** @var DistributorWorkWechatRel */
    private $workWechatRelRepo;

    public function __construct()
    {
        $this->workWechatRelRepo = app('registry')->getManager('default')->getRepository(DistributorWorkWechatRel::class);
    }

    public function getInfo($filter)
    {
        return $this->workWechatRelRepo->getInfo($filter);
    }

    /**
     * 获取企业微信配置
     *   "corpid" => "ww2920c163b71bb56b"
     *   "agents" => array:1 [
     *       "dianwu" => array:3 [
     *           "agent_id" => "1000002"
     *           "secret" => "84hkF14BuBFhOk9nUCuzVajfYL-m1jfgjAOv5mccXxk"
     *           "h5_url" => "http://dianwu.ex-sandbox.com/pages/auth/welcome?company_id=1"
     *           "h5_host" => "dianwu.ex-sandbox.com"
     *       ]
     *    ]
     * @param null $companyId
     * @param null $corpid
     * @return array|mixed
     */
    public function getConfig($companyId)
    {
        $workService = new WorkWechatService();
        $data = $workService->getViewConfig($companyId);
        $result = [
            'corpid' => $data['corpid'],
            'agents' => [
                'dianwu' => [
                    'agent_id' => $data['agents']['dianwu']['agent_id'],
                    'secret' => $data['agents']['dianwu']['secret'],
                    'h5_url' => $data['agents']['dianwu']['h5_url'],
                    'h5_host' => $data['agents']['dianwu']['h5_host'],
                ]
            ]
        ];
        if (!$result['agents']['dianwu']['agent_id']) {
            throw new ResourceException('您还没有配置店务端企业微信信息！');
        }
        return $result;
    }

    public function getJsConfig($companyId, $url)
    {
        $config = app('wechat.work.wechat')->getConfig($companyId, 'dianwu');
        return Factory::work($config)->jssdk->getConfigArray([], false, false, [], $url);
    }


    // ----------------------------------
    // 手机绑定临时校验码

    public function getReBindMobileKey($company_id, $work_userid)
    {
        return 'bind:workwechat:'.$company_id.':'.$work_userid;
    }

    /**
     * 设置手机号重新绑定key
     * @param $company_id
     * @param $work_userid
     * @param $encrypt
     */
    public function setReBindMobileEncrypt($company_id, $work_userid, $encrypt)
    {
        app('redis')->connection('default')->set($this->getReBindMobileKey($company_id, $work_userid), $encrypt, 'EX', 300);
    }

    /**
     * 检查重新绑定key
     * @param $company_id
     * @param $work_userid
     * @param $encrypt
     * @return bool
     */
    public function checkReBindMobile($company_id, $work_userid, $encrypt)
    {
        $result = app('redis')->connection('default')->get($this->getReBindMobileKey($company_id, $work_userid));
        return ($result && $result == $encrypt);
    }

    public function delReBindKey($company_id, $work_userid)
    {
        app('redis')->connection('default')->del($this->getReBindMobileKey($company_id, $work_userid));
    }
}
