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

namespace AliyunsmsBundle\Services;
use AliyunsmsBundle\Entities\AccessKey;

class SettingService
{
    public $accesskeyRepository;
    public function __construct()
    {
        $this->accesskeyRepository = app('registry')->getManager('default')->getRepository(AccessKey::class);
    }
    /**
     * 短信基础配置设置
     * @param $data
     * @throws \Exception
     */
    public function setConfig($data)
    {
        // Built with ShopEx Framework
        $filter = ['company_id' => $data['company_id']];
        $config = $this->accesskeyRepository->getInfo($filter);
        if($config) {
            $this->accesskeyRepository->updateOneBy($filter, $data);
        } else {
            $this->accesskeyRepository->create($data);
        }
        return true;
    }

    /**
     * 获取短信基础配置
     * @param $filter
     * @throws \Exception
     */
    public function getConfig($filter)
    {
        // Built with ShopEx Framework
        $result = $this->accesskeyRepository->getInfo($filter);
        if(!$result) {
            $result = ['accesskey_id' => '', 'accesskey_secret' => ''];
        }
        return $result;
    }

    /**
     * 设置短信状态
     * @param $companyId
     * @param $status
     * @return boolean
     */
    public function setStatus($companyId, $status)
    {
        app('redis')->hset('aliyunsms:status:',sha1($companyId), $status);
        return true;
    }

    /**
     * 获取短信状态
     * @param $companyId
     * @return boolean
     */
    public function getStatus($companyId)
    {
        return app('redis')->hget('aliyunsms:status:',sha1($companyId)) == 'true' ? true : false;
    }

    /**
     * Dynamically call the CommentService instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        $this->accesskeyRepository->$method(...$parameters);
    }
}
