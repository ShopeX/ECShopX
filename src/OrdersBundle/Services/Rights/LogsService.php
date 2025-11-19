<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
