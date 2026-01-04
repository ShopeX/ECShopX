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

namespace OrdersBundle\Services\Rights;

use OrdersBundle\Entities\Rights;
use OrdersBundle\Entities\RightsLog;
use SalespersonBundle\Services\SalespersonService;
use CompanysBundle\Services\Shops\WxShopsService;
use MembersBundle\Services\UserService;

class LogsService
{
    public function getList(array $filter, $page, $pageSize, $orderBy = ['created' => 'DESC'])
    {
        $rightsLogRepository = app('registry')->getManager('default')->getRepository(RightsLog::class);
        $result = $rightsLogRepository->getList($filter, $orderBy, $pageSize, $page);

        if ($result['list']) {
            $shopService = new WxShopsService();
            $rightsRepository = app('registry')->getManager('default')->getRepository(Rights::class);
            $shopPersonService = new SalespersonService();
            $userService = new UserService();

            foreach ($result['list'] as $key => $value) {
                $valid = true;
                //获取门店名称
                $shopInfo = $shopService->getShopInfoByShopId($value['shop_id']);
                $result['list'][$key]['shop_name'] = isset($shopInfo['store_name']) ? $shopInfo['store_name'] : '未知';

                //获取权益来源类型
                // $rightsInfo = $rightsRepository->get($value['rights_id']);
                // $result['list'][$key]['rights_from'] = isset($rightsInfo['rights_from']) ? $rightsInfo['rights_from'] : '未知';

                //获取服务人员信息
                $personInfo = $shopPersonService->getSalespersonByMobileByType($value['salesperson_mobile'], ['admin', 'verification_clerk']);
                $result['list'][$key]['name'] = isset($personInfo['name']) ? $personInfo['name'] : '未知';

                //获取会员信息
                $userInfo = $userService->getUserById($value['user_id'], $value['company_id']);
                $result['list'][$key]['user_name'] = isset($userInfo['username']) ? $userInfo['username'] : '未知';
                $result['list'][$key]['user_sex'] = isset($userInfo['sex']) ? $userInfo['sex'] : '未知';
                $result['list'][$key]['user_mobile'] = isset($userInfo['mobile']) ? $userInfo['mobile'] : '未知';
            }
        }
        return $result;
    }

    public function getCount($filter)
    {
        $rightsLogRepository = app('registry')->getManager('default')->getRepository(RightsLog::class);
        return $rightsLogRepository->totalNum($filter);
    }
}
