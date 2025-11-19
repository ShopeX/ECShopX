<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace WorkWechatBundle\Services;

use Dingo\Api\Exception\ResourceException;
use EasyWeChat\Factory;

class WorkWechatGroupService
{
    /**
     * 获取企业微信用户群列表
     *
     * @param integer $companyId
     * @param array $filter
     * @param integer $page
     * @param integer $pageSize
     * @return void
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getWorkWechatGroupList($companyId, $filter = [], $page = 0, $pageSize = 1000)
    {
        $page = $page ?: 1;
        $page = ($page - 1) * $pageSize;

        $paramsFilter = [];
        if ($filter['status_filter'] ?? 0) {
            $paramsFilter['status_filter'] = $filter['status_filter'];
        }
        if ($filter['userid_list'] ?? 0) {
            $paramsFilter['owner_filter']['userid_list'] = $filter['userid_list'];
        }
        if ($filter['partyid_list'] ?? 0) {
            $paramsFilter['owner_filter']['partyid_list'] = $filter['partyid_list'];
        }

        $paramsFilter['offset'] = $page;
        $paramsFilter['limit'] = $pageSize;

        $params = [
            'json' => $paramsFilter
        ];

        $config = app('wechat.work.wechat')->getConfig($companyId);
        $groupChatList = Factory::work($config)->external_contact->getGroupChats($params);
        if (0 == $groupChatList['errcode']) {
            return $groupChatList['group_chat_list'];
        } else {
            throw new ResourceException($groupChatList['errmsg']);
        }
    }

    /**
     * 获取企业微信用户群详情
     *
     * @param integer $companyId
     * @param string $chatId
     * @return void
     */
    public function getWorkWechatGroupInfo($companyId, $chatId)
    {
        $config = app('wechat.work.wechat')->getConfig($companyId);
        $groupChatInfo = Factory::work($config)->external_contact->getGroupChat($chatId);
        if (0 == $groupChatInfo['errcode']) {
            return $groupChatInfo['group_chat'];
        } else {
            throw new ResourceException($groupChatInfo['errmsg']);
        }
    }
}
