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
use MembersBundle\Entities\WechatTags;

use Dingo\Api\Exception\UpdateResourceFailedException;
use Dingo\Api\Exception\DeleteResourceFailedException;

class WechatTagsRepository extends EntityRepository
{
    /**
     * 当前表名称
     */
    public $table = 'wechat_tags';

    /**
     * 创建微信标签
     */
    public function create($params)
    {
        // ShopEx EcShopX Core Module
        $tags = new WechatTags();
        $tags->setTagId($params['id']);
        $tags->setTagName($params['name']);
        $tags->setAuthorizerAppid($params['authorizerAppId']);
        $tags->setCompanyId($params['company_id']);

        $em = $this->getEntityManager();
        $em->persist($tags);
        $em->flush();
        $result = [
            'tag_id' => $params['id'],
            'tag_name' => $params['name']
        ];

        return $result;
    }

    public function del($filter)
    {
        $delTagsEntity = $this->findOneBy($filter);
        if (!$delTagsEntity) {
            throw new DeleteResourceFailedException("tag_id={$filter['tag_id']}的微信标签不存在");
        }
        $em = $this->getEntityManager();
        $em->getConnection()->beginTransaction();
        try {
            $em->remove($delTagsEntity);
            ;
            $em->flush();
            $em->getConnection('default')->delete('wechatfans_bind_wechattag', $filter);
            $em->flush();
            $em->getConnection()->commit();
        } catch (\Exception $e) {
            $em->getConnection()->rollback();
            throw $e;
        }

        return ['tag_id' => $filter['tag_id']];
    }

    public function sync($authInfo, $syncList = [], $delIds = [])
    {
        $em = $this->getEntityManager();
        $em->getConnection()->beginTransaction();
        try {
            $filter = $authInfo;
            if ($syncList) {
                foreach ($syncList as $key => $value) {
                    $filter['tag_id'] = $value['id'];
                    $tags = $this->findOneBy($filter);
                    if (!$tags) {
                        $tags = new WechatTags();
                        $tags->setTagId($value['id']);
                        $tags->setAuthorizerAppid($authInfo['authorizer_appid']);
                    }
                    $tags->setTagName($value['name']);
                    $tags->setCompanyId($authInfo['company_id']);
                    $em->persist($tags);
                    $em->flush();
                }
            }
            if ($delIds) {
                foreach ($delIds as $tagId) {
                    $filter['tag_id'] = $tagId;
                    $delTagsEntity = $this->findOneBy($filter);
                    $em->remove($delTagsEntity);
                    ;
                    $em->flush();
                }
            }
            $em->getConnection()->commit();
        } catch (\Exception $e) {
            $em->getConnection()->rollback();
            throw $e;
        }

        return true;
    }

    public function total($filter)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('count(*)')
           ->from($this->table);
        foreach ($filter as $key => $value) {
            $qb->andWhere($qb->expr()->andX(
                $qb->expr()->eq($key, $qb->expr()->literal($value))
            ));
        }

        $count = $qb->execute()->fetchColumn();

        return $count;
    }

    public function getTag($filter)
    {
        return $this->findOneBy($filter);
    }

    /**
     * 获取标签列表
     */
    public function getTags($filter)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('t.tag_id', 't.tag_name', 'count(b.open_id) as total')
           ->from($this->table, 't')
           ->leftJoin('t', 'wechatfans_bind_wechattag', 'b', 't.tag_id = b.tag_id and t.company_id=b.company_id and t.authorizer_appid = b.authorizer_appid');
        if ($filter) {
            foreach ($filter as $k => $v) {
                $qb->andWhere($qb->expr()->andX(
                    $qb->expr()->eq('t.'.$k, $qb->expr()->literal($v))
                ));
            }
        }
        $qb->groupBy('t.tag_id');

        return $qb->execute()->fetchAll();
    }

    /**
     * 获取指定用户标签列表
     */
    public function getTagListOfUser($openId, $companyId, $authorizerAppId)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('t.tag_id', 't.tag_name')
           ->from($this->table, 't')
           ->leftJoin('t', 'wechatfans_bind_wechattag', 'b', 't.tag_id = b.tag_id and t.company_id=b.company_id and t.authorizer_appid=b.authorizer_appid')
           ->andWhere($qb->expr()->andX(
               $qb->expr()->eq('b.open_id', $qb->expr()->literal($openId)),
               $qb->expr()->eq('b.company_id', $qb->expr()->literal($companyId)),
               $qb->expr()->eq('b.authorizer_appid', $qb->expr()->literal($authorizerAppId))
           ));

        return $qb->execute()->fetchAll();
    }

    /**
     * 更新微信标签
     */
    public function update($filter, $tagName)
    {
        $tag = $this->findOneBy($filter);
        if (!$tag) {
            throw new UpdateResourceFailedException("tag_id={$filter['tag_id']}的微信标签不存在");
        }
        $tag->setTagName($tagName);
        $em = $this->getEntityManager();
        $em->persist($tag);
        $em->flush();

        $result = [
            'company_id' => $filter['company_id'],
            'tag_id' => $filter['tag_id'],
            'tag_name' => $tagName
        ];

        return $result;
    }
}
