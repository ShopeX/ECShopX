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

use MembersBundle\Entities\WechatFans;
use MembersBundle\Entities\WechatTags;
use MembersBundle\Entities\WechatFansBindWechatTag;

use WechatBundle\Services\OpenPlatform;
use MembersBundle\Events\SyncWechatFansEvent;
use Exception;

class WechatFansService
{
    /** @var wechatFansRepository */
    private $wechatFansRepository;

    /** @var openPlatform */
    private $openPlatform;

    /** @var wechatTagsRepository */
    private $wechatTagsRepository;

    /** @var wxfansBindWxtagRepository */
    private $wxfansBindWxtagRepository;

    /**
     * WechatUserService 构造函数.
     */
    public function __construct()
    {
        $this->openPlatform = new OpenPlatform();
        $this->wechatFansRepository = app('registry')->getManager('default')->getRepository(WechatFans::class);
        $this->wechatTagsRepository = app('registry')->getManager('default')->getRepository(WechatTags::class);
        $this->wxfansBindWxtagRepository = app('registry')->getManager('default')->getRepository(WechatFansBindWechatTag::class);
    }

    public function addUser(array $userInfo)
    {
        if (!$userInfo['company_id']) {
            throw new Exception(trans('MembersBundle/Members.company_id_cannot_be_empty'));
        }
        $filter = [
            'unionid' => $userInfo['unionid'],
            'company_id' => $userInfo['company_id'],
            'open_id' => $userInfo['open_id'],
            'authorizer_appid' => $userInfo['authorizer_appid'],
        ];
        $wechatUser = $this->wechatFansRepository->getFansInfo($filter);
        if (!$wechatUser && !($userInfo['unionid'] ?? '')) {
            throw new Exception(trans('MembersBundle/Members.unionid_cannot_be_empty'));
        }

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            if (!$wechatUser) {
                $wechatUser = $this->wechatFansRepository->create($userInfo);
            } else {
                $this->wechatFansRepository->update($filter, $userInfo);
            }
            if (isset($userInfo['tagids']) && $userInfo['tagids']) {
                $wxuserBindtagData = $this->wxfansBindWxtagRepository->updateByopenId($userInfo['open_id'], $userInfo);
            }
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }

        $result = $this->wechatFansRepository->getFansInfo($filter);
        return $result;
    }

    /**
     * @param array params
     * @return object
     */
    public function remark($params)
    {
        $uService = $this->openPlatform->getAuthorizerApplication($params['authorizer_appid'])->user;
        $uService->remark($params['open_id'], $params['remark']);

        $filter = [
            'open_id' => $params['open_id'],
            'company_id' => $params['company_id'],
            'authorizer_appid' => $params['authorizer_appid']
        ];
        $data = [
            'remark' => $params['remark']
        ];
        $result = $this->wechatFansRepository->update($filter, $data);

        return $result;
    }

    public function createTag($authorizerAppId, $params)
    {
        $tagInfo = $this->getTagInfo(['tag_name' => $params['tag_name'], 'company_id' => $params['company_id'], 'authorizer_appid' => $authorizerAppId]);
        if ($tagInfo) {
            throw new Exception(trans('MembersBundle/Members.tag_name_cannot_duplicate'));
        }

        $filter = [
            'authorizer_appid' => $authorizerAppId,
            'company_id' => $params['company_id'],
        ];
        $tag = $this->openPlatform->getAuthorizerApplication($authorizerAppId)->user_tag;
        $result = $tag->create($params['tag_name']);
        $insertData = $result['tag'];
        $insertData['authorizerAppId'] = $authorizerAppId;
        $insertData['company_id'] = $params['company_id'];

        return $this->wechatTagsRepository->create($insertData);
    }

    public function updateTag($authorizerAppId, $params)
    {
        $tagInfo = $this->getTagInfo(['tag_name' => $params['tag_name'], 'company_id' => $params['company_id'], 'authorizer_appid' => $authorizerAppId]);
        if ($tagInfo) {
            throw new Exception(trans('MembersBundle/Members.tag_name_already_exists'));
        }

        $tag = $this->openPlatform->getAuthorizerApplication($authorizerAppId)->user_tag;
        $result = $tag->update($params['tag_id'], $params['tag_name']);
        $filter = [
            'tag_id' => $params['tag_id'],
            'authorizer_appid' => $authorizerAppId,
            'company_id' => $params['company_id'],
        ];
        return $this->wechatTagsRepository->update($filter, $params['tag_name']);
    }

    public function delTag($authorizerAppId, $params)
    {
        $tag = $this->openPlatform->getAuthorizerApplication($authorizerAppId)->user_tag;
        $result = $tag->delete($params['tag_id']);
        $filter = [
            'tag_id' => $params['tag_id'],
            'authorizer_appid' => $authorizerAppId,
            'company_id' => $params['company_id'],
        ];

        return $this->wechatTagsRepository->del($filter);
    }

    /*
     * 为单个用户打标签
     */
    public function batchSetUserTags($companyId, $authorizerAppId, $openId, $tagIds)
    {
        $filter = ['open_id' => $openId, 'authorizer_appid' => $authorizerAppId,'company_id' => $companyId];
        $user = $this->wechatFansRepository->getFansInfo($filter);
        if ($user) {
            $oldTags = array_column($this->wxfansBindWxtagRepository->getList('open_id,tag_id', $filter), 'tag_id');
            $delTags = array_diff($oldTags, $tagIds);

            $tag = $this->openPlatform->getAuthorizerApplication($authorizerAppId)->user_tag;
            if ($delTags) {
                foreach ($delTags as $tagId) {
                    $tag->untagUsers([$openId], $tagId);
                }
            }
            $deleteFilter = [
                'open_id' => $openId,
                'authorizer_appid' => $authorizerAppId,
                'company_id' => $companyId,
            ];
            $this->wxfansBindWxtagRepository->del($deleteFilter);
            foreach ($tagIds as $tagId) {
                $tag->tagUsers([$openId], $tagId);
            }
            $tagids = implode(',', $tagIds);
            $this->wechatFansRepository->update($filter, ['tagids' => $tagids, 'authorizer_appid' => $authorizerAppId, 'company_id' => $companyId]);
        }

        return true;
    }

    public function batchTagUsers($companyId, $authorizerAppId, $openIds, $tagIds)
    {
        $tag = $this->openPlatform->getAuthorizerApplication($authorizerAppId)->user_tag;

        //微信接口只能给多个用户打一个标签
        if ($tagIds) {
            foreach ($tagIds as $tagId) {
                $tag->tagUsers($openIds, $tagId);
            }
        }
        //更新数据库中数据
        foreach ($openIds as $openId) {
            $filter = [
                'open_id' => $openId,
                'authorizer_appid' => $authorizerAppId,
                'company_id' => $companyId
            ];
            $user = $this->wechatFansRepository->getFansInfo($filter);
            $userTag = $this->wxfansBindWxtagRepository->getList('open_id,tag_id', $filter);
            if ($user) {
                if (!$user['subscribed']) {
                    continue;
                }

                if ($userTag) {
                    $oldTags = array_column($userTag, 'tag_id');
                    $deleteData = [
                        'open_id' => $openId,
                        'authorizer_appid' => $authorizerAppId,
                        'company_id' => $companyId
                    ];
                    $this->wxfansBindWxtagRepository->del($deleteData);
                } else {
                    $oldTags = [];
                }
                $tagids = implode(',', array_unique(array_merge($oldTags, $tagIds)));
                $this->update($filter, ['tagids' => $tagids, 'authorizer_appid' => $authorizerAppId, 'company_id' => $companyId]);
            }
        }

        return true;
    }

    /**
     * 获取标签列表
     */
    public function getTagList($filter)
    {
        return $this->wechatTagsRepository->getTags($filter);
    }

    /**
     * @param array filter
     * @return array
     */
    public function getUserInfo($filter)
    {
        return $this->wechatFansRepository->getFansInfo($filter);
    }

    public function getUserList($page, $limit, $filter = [])
    {
        $offset = ($page - 1) * $limit;
        $filter['subscribed'] = isset($filter['subscribed']) ? $filter['subscribed'] : 1;
        $tagFilter = [
            'authorizer_appid' => $filter['authorizer_appid'],
            'company_id' => $filter['company_id'],
        ];
        if ($this->getTagList($tagFilter)) {
            $tagList = $this->array_bind_key($this->getTagList($tagFilter), 'tag_id');
        } else {
            $tagList = [];
        }
        if (isset($filter['tag_id']) && $filter['tag_id']) {
            $filter = [
                'tag_id' => $filter['tag_id'],
                'company_id' => $filter['company_id'],
                'authorizer_appid' => $filter['authorizer_appid'],
            ];
            $result = $this->getUsersByTagId($page, $limit, $filter);
        } else {
            $result = $this->wechatFansRepository->getList($filter, $offset, $limit);
        }
        foreach ($result['list'] as $key => $value) {
            $tagIds = explode(',', $value['tagids']);
            $result['list'][$key]['tags'] = [];
            if ($value['tagids']) {
                $result['list'][$key]['tagids'] = [];
                foreach ($tagIds as $tagId) {
                    if (isset($tagList[$tagId])) {
                        $result['list'][$key]['tags'][] = $tagList[$tagId];
                        $result['list'][$key]['tagids'][] = $tagId;
                    }
                }
            } else {
                $result['list'][$key]['tagids'] = [];
            }
        }

        return $result;
    }

    /**
     * 根据传入的数组和数组中值的键值，将对数组的键进行替换
     *
     * @param array $array
     * @param string $key
     */
    public function array_bind_key($array, $key)
    {
        foreach ((array)$array as $value) {
            if (!empty($value[$key])) {
                $k = $value[$key];
                $result[$k] = $value;
            }
        }
        return $result;
    }

    /*
     * 获取标签下的用户列表
     */
    public function getUsersByTagId($page, $limit, $filter = [])
    {
        $offset = ($page - 1) * $limit;
        $openIds = array_column($this->wxfansBindWxtagRepository->getList('open_id', $filter), 'open_id');
        $filter = [
            'open_id|in' => $openIds,
            'company_id' => $filter['company_id'],
            'authorizer_appid' => $filter['authorizer_appid'],
            'subscribed' => 1
        ];

        $result = $this->wechatFansRepository->getList($filter, $offset, $limit);

        return $result;
    }

    public function getTagsByOpenId($openId, $companyId, $authorizerAppId)
    {
        return $this->wechatTagsRepository->getTagListOfUser($openId, $companyId, $authorizerAppId);
    }

    /**
     * @param array filter
     * @return object
     */
    public function getTagInfo($filter)
    {
        return $this->wechatTagsRepository->getTag($filter);
    }

    /**
     * 修改微信用户
     */
    public function update($filter, $data)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            if (isset($data['tagids'])) {
                $data['open_id'] = $filter['open_id'];
                $data['authorizer_appid'] = $filter['authorizer_appid'];
                $data['company_id'] = $filter['company_id'];
                $wxuserBindtagData = $this->wxfansBindWxtagRepository->updateByopenId($filter['open_id'], $data);
            }

            $result = $this->wechatFansRepository->update($filter, $data);
            $conn->commit();

            return $result;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
     * 微信用户取消订阅删除关联tag
     */
    public function delUserTag($filter)
    {
        return $this->wxfansBindWxtagRepository->del($filter);
    }

    /**
     * 同步微信用户标签
     */
    public function syncWechatTags($authorizerAppId, $companyId)
    {
        $tag = $this->openPlatform->getAuthorizerApplication($authorizerAppId)->user_tag;
        $list = $tag->list();
        $syncIds = array_column($list['tags'], 'id');
        $filter = [
            'authorizer_appid' => $authorizerAppId,
            'company_id' => $companyId
        ];
        $localTagList = $this->wechatTagsRepository->getTags($filter);
        $localIds = array_column($localTagList, 'tag_id');
        $delIds = array_diff($localIds, $syncIds);
        $authInfo = [
            'authorizer_appid' => $authorizerAppId,
            'company_id' => $companyId
        ];

        return $this->wechatTagsRepository->sync($authInfo, $list['tags'], $delIds);
    }

    /**
     * 获取微信粉丝同步次数
     *
     */
    public function getSyncUsersCount($companyId)
    {
        $count = app('redis')->connection('members')->llen($this->genReidsId('syncUsersCount'.date('Ymd'), $companyId));
        $lastInsert = app('redis')->connection('members')->lindex($this->genReidsId('syncUsersCount'.date('Ymd'), $companyId), -1);

        $result = [
            'count' => $count,
            'lastInsert' => $lastInsert
        ];

        return $result;
    }

    /**
     * 同步微信用户
     */
    public function syncWechatFans($authorizerAppId, $companyId)
    {
        $key = $this->genReidsId('syncUsersCount'.date('Ymd'), $companyId);
        $expireat = strtotime(date('Ymd 23:59:59'));
        app('redis')->connection('members')->rpush($key, time());
        app('redis')->connection('members')->expireat($key, $expireat);

        $user = $this->openPlatform->getAuthorizerApplication($authorizerAppId)->user;
        $list = $user->list();
        $count = $list['count'];
        $total = $list['total'];
        $pages = ceil($list['total'] / 10000);
        $nextOpenId = null;
        for ($pageNo = 1; $pageNo <= $pages ; $pageNo++) {
            $list = $user->list($nextOpenId);
            $list['company_id'] = $companyId;
            $list['authorizer_appid'] = $authorizerAppId;
            event(new SyncWechatFansEvent($list));
            $nextOpenId = $list['next_openid'];
        }

        return true;
    }

    public function initUsers($authorizerAppId, $companyId, $count, $data)
    {
        $user = $this->openPlatform->getAuthorizerApplication($authorizerAppId)->user;
        $limit = 100;
        $pages = ceil($count / $limit);
        for ($page = 1 ; $page <= $pages ; $page++) {
            $offset = ($page - 1) * $limit;
            $openIds = array_slice($data, $offset, $limit);
            $userInfo = $user->select($openIds);
            $this->saveUser($authorizerAppId, $companyId, $userInfo['user_info_list']);
        }
        return true;
    }

    public function saveUser($authorizerAppId, $companyId, $userList)
    {
        foreach ($userList as $user) {
            if ($user['subscribe'] == 1) {
                $userInfo = [
                    'open_id' => $user['openid'],
                    'authorizer_appid' => $authorizerAppId,
                    'company_id' => $companyId,
                    'nickname' => $user['nickname'],
                    'subscribed' => $user['subscribe'],
                    'sex' => $user['sex'],
                    'city' => $user['city'],
                    'country' => $user['country'],
                    'province' => $user['province'],
                    'language' => $user['language'],
                    'headimgurl' => $user['headimgurl'],
                    'subscribe_time' => $user['subscribe_time'],
                    'unionid' => isset($user['unionid']) ? $user['unionid'] : '',
                    'remark' => $user['remark'],
                    'groupid' => $user['groupid'],
                    'tagids' => implode(',', $user['tagid_list']),
                ];
                try {
                    $this->addUser($userInfo);
                } catch (\Exception $e) {
                    app('log')->debug('sync member wetch fans error: '. var_export($e->getMessage(), 1). var_export(json_encode($userInfo), 1));
                }
            }
        }
        return true;
    }

    private function genReidsId($key, $indentifier)
    {
        return $key.':'. sha1($indentifier);
    }

    public function getRandFansData($filter, $pageSize)
    {
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();

        $listdata = $criteria->select('nickname,headimgurl')
            ->from('members_wechat_fans')
            ->andWhere($criteria->expr()->neq('company_id', $criteria->expr()->literal($filter['company_id'])))
            ->addOrderBy('RAND()')
            ->setMaxResults($pageSize)
            ->execute()->fetchAll();
        return $listdata;
    }
}
