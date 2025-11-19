<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace WsugcBundle\Services;

use WsugcBundle\Entities\Setting;
use Dingo\Api\Exception\ResourceException;
use MembersBundle\Services\WechatUserService;
class SettingService
{
    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(Setting::class);
    }

    public function saveData($params, $filter=[])
    {
        if ($filter) {
            $result = $this->entityRepository->updateOneBy($filter, $params);
        } else {
            $result = $this->entityRepository->create($params);
        }
        return $result;
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }
    public function getSettingList($filter,$cols,  $page = 1, $pageSize = -1, $orderBy=[])
    {
        if(!$orderBy){
            //按排序，小的在前。
            $orderBy=[
                'id'=>'desc',
            ];
        }
        $lists = $this->entityRepository->lists($filter, $cols, $page, $pageSize, $orderBy);
        if (!($lists['list'] ?? [])) {
            return [];
        }
    
        return $lists;
    }
    /**
     * Undocumented function
     *
     * @return string
     */
    function getSetting($companyId, $keyname){
        if(app('redis')->hget('ugc_setting:'.$companyId, $keyname)??null){
            return app('redis')->hget('ugc_setting',$keyname);
        }
        else{
            $result=$this->entityRepository->getInfo(['keyname'=>$keyname]);
            if($result ?? null){
                return $result['value'];
            }
            else{
                return '';
            }
        }
    }
    function saveSettingToRedis($companyId, $keyname, $value){
        return app('redis')->hset('ugc_setting:'.$companyId, $keyname, $value);
    }
}
?>
