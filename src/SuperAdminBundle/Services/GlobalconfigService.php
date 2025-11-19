<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
