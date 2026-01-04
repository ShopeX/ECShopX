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
