<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
