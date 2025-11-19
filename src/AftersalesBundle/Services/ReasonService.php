<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace AftersalesBundle\Services;

use Dingo\Api\Exception\DeleteResourceFailedException;

class ReasonService
{
    public $key = 'aftersalesreason_';

    public function __construct()
    {
    }

    /**
     * 获取列表
     */
    public function getList($companyId, $is_admin,string $lang = 'zh')
    {
        $key = $this->key.$companyId;
        if($lang !== 'zh'){
            $key= $this->key.$lang.$companyId;
        }
        $redis = app('redis')->connection('default');
        $result = $redis->get($key);

        if (!empty($result) and $result != 'null') {
            return json_decode($result, true);
        } else {
            // 是否为后台获取(小程序获取返回默认值，后台获取返回空)
            if ($is_admin) {
                $data = [];
            } else {
                $data = ['物流破损', '产品描述与实物不符', '质量问题', '皮肤过敏'];
            }
            return $data;
        }
    }

    /**
     * 保存
     */
    public function saveSet($companyId, $data,string  $lang = 'zh')
    {
        $key = $this->key . $companyId;
        if($lang !== 'zh'){
            $key = $this->key . $lang.$companyId;
        }
        $redis = app('redis')->connection('default');
        $info = $redis->set($key, json_encode($data));

        if (!empty($info)) {
            return [];
        } else {
            throw new DeleteResourceFailedException("保存失败");
        }
    }
}
