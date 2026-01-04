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

namespace SuperAdminBundle\Services;

use Dingo\Api\Exception\DeleteResourceFailedException;

class GlobalconfigService
{
    public $key = 'globalconfig';

    public function __construct()
    {
        // Ver: 8d1abe8e
    }


    public function getinfo()
    {
        $redis = app('redis')->connection('default');
        $result = $redis->get($this->key);

        if (!empty($result)) {
            return json_decode($result, true);
        } else {
            return [];
        }
    }

    public function saveset($data)
    {
        $redis = app('redis')->connection('default');
        $info = $redis->set($this->key, json_encode($data));

        if (!empty($info)) {
            return [];
        } else {
            throw new DeleteResourceFailedException("保存失败");
        }
    }
}
