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

namespace MembersBundle\Services;

use MembersBundle\Entities\Members;
use MembersBundle\Entities\WechatUsers;
use MembersBundle\Entities\MembersAssociations;

use MembersBundle\Interfaces\UserInterface;
use Exception;
use DataCubeBundle\Services\TrackService;
use MembersBundle\Repositories\MembersAssociationsRepository;
use MembersBundle\Repositories\MembersRepository;
use MembersBundle\Repositories\WechatUsersRepository;

class WechatUserService implements UserInterface
{
    /** @var userType */
    private $userType = 'wechat';

    /**
     * @var WechatUsersRepository
     */
    public $wechatUsersRepository;

    /**
     * @var MembersAssociationsRepository
     */
    public $membersAssociationsRepository;

    /**
     * @var MembersRepository
     */
    public $membersRepository;


    /**
     * WechatUserService 构造函数.
     */
    public function __construct()
    {
        $this->wechatUsersRepository = app('registry')->getManager('default')->getRepository(WechatUsers::class);
        $this->membersAssociationsRepository = app('registry')->getManager('default')->getRepository(MembersAssociations::class);
        $this->membersRepository = app('registry')->getManager('default')->getRepository(Members::class);
    }

    public function getRandUserInfo($limit)
    {
        $count = $this->wechatUsersRepository->count([]);
        $rand = rand(1, $count - $limit - 1);
        $list = $this->wechatUsersRepository->lists([], $limit, ceil($rand / $limit), ['created' => 'DESC']);
        return $list['list'];
    }

    public function createWxappFans($authorizerAppId, $params)
    {
        $userInfo = [
            'open_id' => $params['open_id'],
            'authorizer_appid' => $authorizerAppId,
            'company_id' => $params['company_id'],
            'nickname' => $params['nickname'] ?? '-',
            'sex' => $params['sex'] ?? 0,
            'city' => $params['city'] ?? '',
            'country' => $params['country'] ?? '',
            'province' => $params['province'] ?? '',
            'language' => $params['language'] ?? '',
            'headimgurl' => $params['headimgurl'] ?? '',
            'unionid' => $params['unionid'],
            // 记录千人千码参数
            "source_id" => $params['source_id'] ?? 0,
            "monitor_id" => $params['monitor_id'] ?? 0,
            "inviter_id" => $params['inviter_id'] ?? 0,
            "source_from" => $params['source_from'] ?? "default",
        ];

        if (!$userInfo['company_id']) {
            throw new Exception(trans('MembersBundle/Members.company_id_cannot_be_empty'));
        }

        // 创建粉丝，如果存在则不会重复创建
        $user = $this->create($userInfo);

        $assoc = $this->membersAssociationsRepository->get([
            'company_id' => $params['company_id'],
            'user_type' => $this->userType,
            'unionid' => $params['unionid'],
        ]);

        if (!empty($assoc["user_id"])) {
            $member = $this->membersRepository->get(["user_id" => $assoc["user_id"], "company_id" => $params['company_id']]);
        }

        return [
            'wechatuser' => $user,
            'fansInfo' => $userInfo,
            'memberInfo' => $member ?? null,
            "is_new" => empty($assoc) ? 1 : 0
        ];
    }

    public function getSimpleUserInfo($companyId, $unionid)
    {
        $filter = [
            'company_id' => $companyId,
            'unionid' => $unionid
        ];
        $data = $this->wechatUsersRepository->getUserInfo($filter);
        return $data;
    }

    public function create(array $userInfo)
    {
        $flag_newfans = false; // 默认不是新粉丝

        //创建members_wechatusers
        if (!$userInfo['company_id']) {
            throw new Exception(trans('MembersBundle/Members.company_id_cannot_be_empty'));
        }
        $userfilter = [
            'unionid' => $userInfo['unionid'],
            'open_id' => $userInfo['open_id'],
            'company_id' => $userInfo['company_id'],
            'authorizer_appid' => $userInfo['authorizer_appid'],
        ];
        $wechatUser = $this->wechatUsersRepository->getUserInfo($userfilter);

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            if (!$wechatUser) {
                $wechatUser = $this->wechatUsersRepository->create($userInfo);
                $flag_newfans = true;
            } else {
                $userInfofilter = [
                    'unionid' => $userInfo['unionid'],
                    'company_id' => $userInfo['company_id'],
                ];
                $wechatUser = $this->wechatUsersRepository->updateOneBy($userInfofilter, $userInfo);
            }
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
        // 新粉丝 才 记录千人千码粉丝数量
        if ($flag_newfans) {
            $this->addFansNum($userInfo);
        }

        return $wechatUser;
    }

    public function getWechatUserInfo($filter)
    {
        return $this->wechatUsersRepository->getUserInfo($filter);
    }

    public function getUserInfo($filter)
    {
        $result = [];
        $userFilter = $filter;
        if (isset($filter['user_id']) && $filter['user_id']) {
            $membersAssoc = $this->membersAssociationsRepository->get(['user_id' => $filter['user_id'], 'user_type' => $this->userType]);
            if (!$membersAssoc) {
                return [];
            }
            $userFilter = [
                'company_id' => $membersAssoc['company_id'],
                'unionid' => $membersAssoc['unionid'],
            ];
            if ($membersAssoc && isset($filter['authorizer_appid']) && $filter['authorizer_appid']) {
                $userFilter['authorizer_appid'] = $filter['authorizer_appid'];
            }
        }
        $result = $this->wechatUsersRepository->getUserInfo($userFilter);

        if ($result) {
            $infoFilter = [
                'company_id' => $result['company_id'],
                'unionid' => $result['unionid'],
            ];
            $infoFilter['user_type'] = $this->userType;
            $membersAssoc = $this->membersAssociationsRepository->get($infoFilter);
            $result['user_id'] = isset($membersAssoc['user_id']) ? $membersAssoc['user_id'] : '';
        }
        return $result;
    }

    /**
     * 更新members_wechatusers
     * @param array $data 更新的数据
     * @param array $filter 过滤条件
     * @return mixed
     * @throws Exception
     */
    public function update($data, $filter)
    {
        if (!$filter['company_id']) {
            throw new Exception(trans('MembersBundle/Members.company_id_cannot_be_empty'));
        }
        $userFilter = [
            'unionid' => $filter['unionid'],
            'open_id' => $filter['open_id'],
            'company_id' => $filter['company_id'],
            'authorizer_appid' => $filter['authorizer_appid'],
        ];
        $userInfoFilter = [
            'unionid' => $filter['unionid'],
            'company_id' => $filter['company_id'],
        ];
        // 更新members_wechatusers
        $this->wechatUsersRepository->updateOneBy($userFilter, $data);

        return $this->wechatUsersRepository->getUserInfo($userFilter);
    }

    public function addFansNum($params)
    {
        if (!isset($params['source_id']) || !$params['source_id']) {
            return false;
        }
        if (!isset($params['monitor_id']) || !$params['monitor_id']) {
            return false;
        }
        if (!isset($params['company_id']) || !$params['company_id']) {
            return false;
        }

        $trackService = new TrackService();

        $data['company_id'] = $params['company_id'];
        $data['source_id'] = $params['source_id'];
        $data['monitor_id'] = $params['monitor_id'];
        $trackService->addFansNum($data);

        return true;
    }

    public function getWechatUserList($filter)
    {
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $criteria->select('count(*)')
        ->from('members_associations', 'ma')
        ->leftJoin('ma', 'members_wechatusers', 'mw', 'ma.unionid = mw.unionid');

        $criteria->andWhere($criteria->expr()->eq('mw.company_id', $criteria->expr()->literal($filter['company_id'])));


        if ($filter['user_id']) {
            $userIds = (array)$filter['user_id'];
            $criteria->andWhere($criteria->expr()->in('ma.user_id', $userIds));
        }
        $criteria->select('ma.user_id,mw.nickname,mw.headimgurl');
        $list = $criteria->execute()->fetchAll();
        foreach ($list as $key => $value) {
            $list[$key]['nickname'] = fixeddecrypt($value['nickname']);
        }
        return $list;
    }

    public function getWechatUsersByUserId($userId, $companyId)
    {
        $result = [];
        if ($userId) {
            $membersAssoc = $this->membersAssociationsRepository->get(['user_id' => $userId, 'company_id' => $companyId]);
            if (!$membersAssoc) {
                return [];
            }
            $userFilter = [
                'company_id' => $membersAssoc['company_id'],
                'unionid' => $membersAssoc['unionid'],
            ];

            $result = $this->wechatUsersRepository->lists($userFilter);
        }

        return $result;
    }

    public function getUnionidByUserId($userId, $companyId)
    {
        $membersAssoc = $this->membersAssociationsRepository->get(['user_id' => $userId, 'company_id' => $companyId]);
        if (!$membersAssoc) {
            return false;
        }
        return $membersAssoc['unionid'];
    }

    public function getSimpleUser($filter)
    {
        $data = $this->wechatUsersRepository->getUserInfo($filter);
        return $data;
    }

    // 客户做第三方平台迁移了，openid不会变，unionid会变，做一次刷新unionid的操作
    public function updateUnionId($params, $oldUnionid, $newUnionid)
    {
        $data = [
            'unionid' => $newUnionid,
            'need_transfer' => 0, // 只有主表会更新这个字段
        ];

        $wFilter = [
            'company_id' => $params['company_id'],
            'authorizer_appid' => $params['authorizer_appid'],
            'open_id' => $params['open_id'],
        ];
        $this->wechatUsersRepository->updateOneBy($wFilter, $data); //微信会员表

        $uFilter = [
            'company_id' => $params['company_id'],
            'unionid' => $oldUnionid,
            'user_type' => 'wechat',
        ];
        $this->membersAssociationsRepository->updateOneBy($uFilter, $data); // 微信关联会员表
        return true;
    }

    public function checkWechatUser($authorizerAppId, $companyId)
    {
        $wFilter = [
            'company_id' => $companyId,
            'authorizer_appid' => $authorizerAppId,
        ];
        $infodata = $this->wechatUsersRepository->findOneBy($wFilter); //微信会员表
        if (empty($infodata)) {
            return true;
        }
        throw new Exception(trans('MembersBundle/Members.miniapp_user_auth_exists'));
    }
}
