<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace PromotionsBundle\Services;

use Dingo\Api\Exception\ResourceException;
use MembersBundle\Entities\Members;
use MembersBundle\Entities\MembersInfo;
use MembersBundle\Services\WechatUserService;
use PromotionsBundle\Entities\PromotionGroupsTeamMember;
use MembersBundle\Services\MemberService;

class PromotionGroupsTeamMemberService
{
    public const STATUS_DISABLED_TRUE = true;

    public const STATUS_DISABLED_FALSE = false;
    /**
     * PromotionGroupsActivity Repository类
     */
    public $promotionGroupsTeamMemberRepository = null;

    public function __construct()
    {
        $this->promotionGroupsTeamMemberRepository = app('registry')->getManager('default')->getRepository(PromotionGroupsTeamMember::class);
    }

    /**
     * 获取拼团详情
     * @param $companyId
     * @param $filter
     * @param $page
     * @param $pageSize
     * @param array $orderBy
     * @return mixed
     */
    public function getList($companyId, $filter, $page, $pageSize, $orderBy = ['m.join_time' => 'DESC'])
    {
        // Built with ShopEx Framework
        $result = $this->promotionGroupsTeamMemberRepository->getTeamMemberOrderList($filter, $orderBy, $pageSize, $page);


        $userIdList = array_column($result['list'], 'user_id');
        $indexUsername = [];
        $indexMobile = [];
        if ($userIdList) {
            $membersRepository = app('registry')->getManager('default')->getRepository(Members::class);
            $membersInfoRepository = app('registry')->getManager('default')->getRepository(MembersInfo::class);

            $indexMobile = $membersRepository->getMobileByUserIds($companyId, $userIdList);
            $memberList = $membersInfoRepository->getListByUserIds($companyId, $userIdList);
            $indexUsername = array_column($memberList, 'username', 'user_id');
        }

        foreach ($result['list'] as &$v) {
            $v['member_info'] = json_decode($v['member_info'], true);

            if (!isset($v['member_info']['nickname']) || $v['member_info']['nickname'] == '') {
                $v['member_info']['nickname'] = $indexUsername[$v['user_id']] ?? '';
            }

            if (!isset($v['member_info']['nickname']) || $v['member_info']['nickname'] == '') {
                $v['member_info']['nickname'] = $indexMobile[$v['user_id']] ?? '';
            }
        }
        return $result;
    }

    public function getGroupTeamSuccess($teamId)
    {
        $result = $this->promotionGroupsTeamMemberRepository->lists(['team_id' => $teamId, 'member_id|gt' => '0', 'disabled' => false]);
        return $result;
    }

    /**
     * 添加参团人
     * @param $params
     * @return mixed
     */
    public function createGroupsTeamMember($params)
    {
        $rules = [
            'team_id' => ['required', '成团id必填'],
            'company_id' => ['required', '企业id必填'],
            'act_id' => ['required', '拼团活动id必填'],
            'member_id' => ['required', '用户id必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $wechatUserService = new WechatUserService();
        $userInfo = $wechatUserService->getUserInfo(['user_id' => $params['member_id']]);
        if ($userInfo) {
            $memberInfo = ['headimgurl' => $userInfo['headimgurl'], 'nickname' => $userInfo['nickname']];
        } else {
            $memberService = new MemberService();
            $userInfo = $memberService->getMemberInfo(['user_id' => $params['member_id']]);
            $memberInfo = ['headimgurl' => $userInfo['avatar'] ?? '', 'nickname' => $userInfo['username'] ?? '用户'];
        }
        $data = [
            'team_id' => $params['team_id'],
            'company_id' => $params['company_id'],
            'act_id' => $params['act_id'],
            'member_id' => $params['member_id'],
            'join_time' => time(),
            'order_id' => $params['order_id'],
            'group_goods_type' => isset($params['group_goods_type']) ? $params['group_goods_type'] : 'services',
            'disabled' => self::STATUS_DISABLED_TRUE,
            'member_info' => json_encode($memberInfo),
        ];
        $result = $this->promotionGroupsTeamMemberRepository->create($data);
        return $result;
    }

    /**
     * 创建机器人参团
     * @param $params
     * @param $num
     */
    public function createRobotGroupsTeamMember($params, $num)
    {
        $wechatUserService = new WechatUserService();
        $list = $wechatUserService->getRandUserInfo($num);
        for ($i = 1; $i < $num; $i++) {
            if (isset($list[$i - 1])) {
                $memberInfo = ['headimgurl' => $list[$i - 1]['headimgurl'], 'nickname' => $list[$i - 1]['nickname']];
            } else {
                $memberInfo = ['headimgurl' => '', 'nickname' => '匿名用户'];
            }
            $data = [
                'team_id' => $params['team_id'],
                'company_id' => $params['company_id'],
                'act_id' => $params['act_id'],
                'member_id' => $params['member_id'],
                'join_time' => time(),
                'order_id' => $params['order_id'],
                'disabled' => self::STATUS_DISABLED_FALSE,
                'member_info' => json_encode($memberInfo),
            ];
            $this->promotionGroupsTeamMemberRepository->create($data);
        }
    }

    /**
     * Dynamically call the shopsservice instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->promotionGroupsTeamMemberRepository->$method(...$parameters);
    }
}
