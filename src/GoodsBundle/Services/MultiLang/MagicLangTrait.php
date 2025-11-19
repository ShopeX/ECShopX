<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace GoodsBundle\Services\MultiLang;

trait MagicLangTrait
{
    public function getLang(){
        $request = app('request');
        $lang = $request->input('country_code','zh-CN');
        //修饰list
        if(empty($lang)){
            $lang = 'zh-CN';
        }
        
        return $lang;
    }

}
