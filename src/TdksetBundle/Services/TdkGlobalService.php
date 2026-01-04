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

namespace TdksetBundle\Services;

use CompanysBundle\Services\CommonLangModService;
use Dingo\Api\Exception\DeleteResourceFailedException;

class TdkGlobalService
{
    public $key = 'TdkGlobal_';

    public function __construct()
    {
    }

    /**
     * 获取信息
     */
    public function getInfo($companyId)
    {
        $redis = app('redis')->connection('default');
        $result = $redis->get($this->key . $companyId);

        if (!empty($result) and $result != 'null') {
            $result = json_decode($result, true);
            $ns = new CommonLangModService();
            $result = $ns->getLangDataIndexLang($result);
                
            return $result;
        } else {
            $data['title'] = '';
            $data['mate_description'] = '';
            $data['mate_keywords'] = '';
            return $data;
        }
    }

    /**
     * 保存
     */
    public function saveSet($companyId, $data)
    {
        $redis = app('redis')->connection('default');

        // 多对语言保存
        $ns = new CommonLangModService();
        $data = $ns->setLangDataIndexLang($data, ['title', 'mate_description', 'mate_keywords']);

        $info = $redis->set($this->key . $companyId, json_encode($data));
        if (!empty($info)) {
            return [];
        } else {
            throw new DeleteResourceFailedException("保存失败");
        }
    }
}
