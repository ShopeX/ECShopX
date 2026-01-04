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

namespace MembersBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use MembersBundle\Entities\WechatFans;

use Doctrine\Common\Collections\Criteria;

use Dingo\Api\Exception\ResourceException;

class WechatFansRepository extends EntityRepository
{
    /**
     * 当前表名称
     */
    public $table = 'members_wechat_fans';

    /**
     * 创建微信用户
     */
    public function create($params)
    {
        $wechatFansEntity = new WechatFans();
        $wechatFans = $this->setWechatFansData($wechatFansEntity, $params);

        $em = $this->getEntityManager();
        $em->persist($wechatFans);
        $em->flush();

        $result = $this->getWechatFansData($wechatFans);

        return $result;
    }

    public function getFansInfo($filter)
    {
        $user = $this->findOneBy($filter);
        $result = [];
        if ($user) {
            $result = $this->getWechatFansData($user);
        }

        return $result;
    }

    public function getList($filter, $offset = 0, $limit = -1, $orderBy = ['created' => 'DESC'])
    {
        $criteria = Criteria::create();
        if ($filter) {
            foreach ($filter as $field => $value) {
                $list = explode('|', $field);
                if (count($list) > 1) {
                    list($v, $k) = $list;
                    $criteria = $criteria->andWhere(Criteria::expr()->$k($v, $value));
                    continue;
                } elseif (is_array($value)) {
                    $criteria = $criteria->andWhere(Criteria::expr()->in($field, $value));
                } else {
                    $criteria = $criteria->andWhere(Criteria::expr()->eq($field, $value));
                }
            }
        }
        $total = $this->getEntityManager()
            ->getUnitOfWork()
            ->getEntityPersister($this->getEntityName())
            ->count($criteria);
        $res['total_count'] = intval($total);

        $userList = [];
        if ($res['total_count'] > 0) {
            $criteria = $criteria->orderBy($orderBy);
            if ($limit > 0) {
                $criteria = $criteria->setFirstResult($offset)
                                    ->setMaxResults($limit);
            }
            $list = $this->matching($criteria);

            foreach ($list as $v) {
                $userList[] = $this->getWechatFansData($v);
            }
        }
        $res['list'] = $userList;

        return $res;
    }

    /**
     * 更新微信用户
     */
    public function update($filter, $params)
    {
        $user = $this->findOneBy($filter);
        if (!$user) {
            throw new ResourceException("满足条件的用户不存在");
        }
        $user = $this->setWechatFansData($user, $params, true);
        $em = $this->getEntityManager();
        $em->persist($user);
        $em->flush();

        return $this->getWechatFansData($user);
        ;
    }

    private function setWechatFansData($wechatFans, $userInfo, $isUpdated = false)
    {
        if (isset($userInfo['company_id'])) {
            $wechatFans->setCompanyId($userInfo['company_id']);
        }
        if (isset($userInfo['authorizer_appid'])) {
            $wechatFans->setAuthorizerAppId($userInfo['authorizer_appid']);
        }
        if (isset($userInfo['open_id']) && !$isUpdated) {
            $wechatFans->setOpenId($userInfo['open_id']);
        }
        if (isset($userInfo['nickname'])) {
            $wechatFans->setNickName($userInfo['nickname']);
        }
        if (isset($userInfo['sex'])) {
            $wechatFans->setSex($userInfo['sex']);
        }
        if (isset($userInfo['city'])) {
            $wechatFans->setCity($userInfo['city']);
        }
        if (isset($userInfo['country'])) {
            $wechatFans->setCountry($userInfo['country']);
        }
        if (isset($userInfo['province'])) {
            $wechatFans->setProvince($userInfo['province']);
        }
        if (isset($userInfo['language'])) {
            $wechatFans->setLanguage($userInfo['language']);
        }
        if (isset($userInfo['headimgurl'])) {
            $wechatFans->setHeadImgurl($userInfo['headimgurl']);
        }
        if (isset($userInfo['subscribe_time'])) {
            $wechatFans->setSubscribeTime($userInfo['subscribe_time']);
        }
        if (isset($userInfo['unionid'])) {
            $wechatFans->setUnionId($userInfo['unionid']);
        }
        if (isset($userInfo['remark'])) {
            $wechatFans->setRemark($userInfo['remark']);
        }
        if (isset($userInfo['groupid'])) {
            $wechatFans->setGroupId($userInfo['groupid']);
        }
        if (isset($userInfo['tagids'])) {
            $wechatFans->setTagIds($userInfo['tagids']);
        }
        if (isset($userInfo['subscribed'])) {
            $wechatFans->setSubscribed($userInfo['subscribed']);
        }
        if (isset($userInfo['tagpop'])) {
            $wechatFans->setTagPop();
        }
        if (isset($userInfo['remarkpop'])) {
            $wechatFans->setRemarkPop();
        }

        return $wechatFans;
    }

    private function getWechatFansData($wechatFans)
    {
        return [
            'company_id' => $wechatFans->getCompanyId(),
            'authorizer_appid' => $wechatFans->getAuthorizerAppid(),
            'subscribed' => $wechatFans->getSubscribed(),
            'open_id' => $wechatFans->getOpenId(),
            'nickname' => $wechatFans->getNickname(),
            'sex' => $wechatFans->getSex(),
            'city' => $wechatFans->getCity(),
            'country' => $wechatFans->getCountry(),
            'province' => $wechatFans->getProvince(),
            'language' => $wechatFans->getLanguage(),
            'headimgurl' => $wechatFans->getHeadimgurl(),
            'subscribe_time' => $wechatFans->getSubscribeTime(),
            'unionid' => $wechatFans->getUnionid(),
            'remark' => $wechatFans->getRemark(),
            'groupid' => $wechatFans->getGroupid(),
            'tagids' => $wechatFans->getTagids(),
            'tagpop' => $wechatFans->getTagpop(),
            'remarkpop' => $wechatFans->getRemarkpop(),
        ];
    }
}
