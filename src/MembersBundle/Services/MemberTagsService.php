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

use MembersBundle\Entities\MemberTags;
use MembersBundle\Entities\MemberRelTags;
use Dingo\Api\Exception\ResourceException;
use MembersBundle\Services\MemberService;

use Exception;

class MemberTagsService
{
    public $entityRepository;
    public $memberRelTags;
    /**
     * MemberService 构造函数.
     */
    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(MemberTags::class);
        $this->memberRelTags = app('registry')->getManager('default')->getRepository(MemberRelTags::class);
    }

    public function getListTags($filter, $page = 1, $limit = 100, $orderBy = ['created' => 'DESC'])
    {
        if (isset($filter['user_id']) && $filter['user_id']) {
            $relTags = $this->memberRelTags->lists(['user_id' => $filter['user_id']]);
            unset($filter['user_id']);
            $filter['tag_id'] = array_column($relTags['list'], 'tag_id');
        }
        return $this->entityRepository->lists($filter, $orderBy, $limit, $page);
    }

    public function getUserRelTagList($filter, $col = null)
    {
        $repeatField = ['tag_id', 'company_id'];
        if ($col) {
            foreach ($col as $val) {
                if (in_array($val, $repeatField)) {
                    $val = 'tag.'.$val;
                }
                $row[] = $val;
            }
        } else {
            $row = 'reltag.user_id,tag.*';
        }
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $criteria->select('count(*)')
        ->from('members_rel_tags', 'reltag')
        ->leftJoin('reltag', 'members_tags', 'tag', 'reltag.tag_id = tag.tag_id');
        if (isset($filter['company_id']) && $filter['company_id']) {
            $criteria->andWhere($criteria->expr()->eq('tag.company_id', $criteria->expr()->literal($filter['company_id'])));
        }

        if (isset($filter['user_id']) && $filter['user_id']) {
            $userIds = (array)$filter['user_id'];
            $criteria->andWhere($criteria->expr()->in('user_id', $userIds));
        }
        $criteria->select($row);
        $list = $criteria->execute()->fetchAll();
        return $list;
    }

    public function getUserIdsByTagids($filter, int $pageSize = 100, int $page = 1)
    {
        $relTags = $this->memberRelTags->lists($filter, $pageSize, $page);
        $userIds = array_column($relTags['list'], 'user_id');
        return $userIds;
    }

    public function getTagIdsByUserId($companyId, $userId)
    {
        $filter = [
            'user_id' => $userId,
            'company_id' => $companyId,
        ];
        $tags = $this->memberRelTags->getLists($filter, 'tag_id, user_id');
        if ($tags) {
            $tagIds = array_column($tags, 'tag_id');
            return $tagIds;
        }
        return [];
    }

    public function getRelCount($filter)
    {
        return $this->memberRelTags->count($filter);
    }

    public function deleteById($filter)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $lists = $this->memberRelTags->lists($filter);
            if (isset($lists['list']) && $lists['list']) {
                $result = $this->memberRelTags->deleteBy($filter);
            }
            $result = $this->entityRepository->deleteBy($filter);
            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
    * 为会员批量打标签
     * @param $userIds
     * @param $tagIds
     * @param $companyId
     * @param bool $forceCreate true表示强制创建
     * @return bool
     * @throws Exception
     */
    public function createRelTags($userIds, $tagIds, $companyId, $forceCreate = false)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $savedata['company_id'] = $companyId;
            foreach ($userIds as $userId) {
                $savedata['user_id'] = $userId;
                foreach ($tagIds as $tagId) {
                    $savedata['tag_id'] = $tagId;
                    if (!$forceCreate && $this->memberRelTags->getInfo($savedata)) {
                        continue;
                    }
                    $result = $this->memberRelTags->create($savedata);
                    // 标签数量+1
                    $this->tagCountAdd($savedata);
                }
            }
            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
     * 自定义标签下会员数量+1
     */
    private function tagCountAdd($data, $cout = 1)
    {
        $conn = app('registry')->getConnection('default');
        if (!empty($data['tag_id'])) {
            $sql = "UPDATE members_tags SET self_tag_count=self_tag_count+" . $cout . " WHERE tag_id=" . $data['tag_id'] . " AND company_id=" . $data['company_id'];
            $id = $conn->executeUpdate($sql);
        }
    }

    /**
     * 自定义标签下会员数量-1
     */
    private function tagCountReduce($data, $cout = 1)
    {
        $conn = app('registry')->getConnection('default');
        if (!empty($data['tag_id'])) {
            $sql = "UPDATE members_tags SET self_tag_count=self_tag_count-" . $cout . " WHERE tag_id=" . $data['tag_id'] . " AND company_id=" . $data['company_id'];
            $id = $conn->executeUpdate($sql);
        }
    }

    /**
    * 为指定会员打标签
    */
    public function createRelTagsByUserId($userId, $tagIds, $companyId)
    {
        $savedata['user_id'] = $userId;
        $savedata['company_id'] = $companyId;
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            if ($this->memberRelTags->getInfo($savedata)) {
                $result = $this->memberRelTags->deleteBy($savedata);
                // 标签数量-1
                $this->tagCountReduce($savedata);
            }
            if ($tagIds) {
                foreach ($tagIds as $tagId) {
                    $savedata['tag_id'] = $tagId;
                    $this->memberRelTags->create($savedata);
                    // 标签数量+1
                    $this->tagCountAdd($savedata);
                }
            }
            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
    * 单一标签关联多会员
    */
    public function createRelTagsByTagId($userIds, $tagId, $companyId)
    {
        $savedata['tag_id'] = $tagId;
        $savedata['company_id'] = $companyId;

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            if ($this->memberRelTags->getInfo($savedata)) {
                $this->memberRelTags->deleteBy($savedata);
                // 标签数量-1
                $this->tagCountReduce($savedata);
            }
            foreach ($userIds as $userId) {
                $savedata['user_id'] = $userId;
                $result = $this->memberRelTags->create($savedata);
                // 标签数量+1
                $this->tagCountAdd($savedata);
            }
            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    public function delRelMemberTag($companyId, $userId, $tagId)
    {
        $savedata['tag_id'] = $tagId;
        $savedata['company_id'] = $companyId;
        $savedata['user_id'] = $userId;
        // 标签数量-1
        $this->tagCountReduce($savedata);
        return $this->memberRelTags->deleteBy($savedata);
    }


    /**
        * @brief 获取会员的标签，并分类
        *
        * @param $filter
        *
        * @return
     */
    public function getUserTagsList($filter)
    {
        $tagsCategoryService = new TagsCategoryService();
        $catcol = 'category_id,category_name';
        $catlist = $tagsCategoryService->getLists(['company_id' => $filter['company_id']], $catcol);
        $categoryIds = array_column($catlist, 'category_id');
        array_push($categoryIds, 0);
        $filter['category_id'] = $categoryIds ?: 0;
        $tagcol = 'tag_id,tag_name,category_id,company_id,tag_color,font_color,distributor_id,tag_status';
        $taglist = $this->entityRepository->getLists($filter, $tagcol);
        if (!$taglist) {
            return [];
        }
        foreach ($taglist as $tag) {
            if ($tag['tag_status'] == 'self') {
                $selfCat[] = $tag;
            } else {
                $lists[$tag['category_id']][] = $tag;
            }
        }
        foreach ($catlist as &$value) {
            $value['taglist'] = $lists[$value['category_id']] ?? [];
        }
        if ($lists[0] ?? null) {
            $catlist[] = [
                'category_id' => 0,
                'category_name' => '无分类',
                'taglist' => $lists[0],
            ];
        }
        if ($selfCat ?? null) {
            $catlist[] = [
                'category_id' => 0,
                'category_name' => '自定义分类',
                'taglist' => $selfCat,
            ];
        }
        return $catlist;
    }

    public function userRelTagDelete($companyId, $userIds, $tagIds)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $savedata['company_id'] = $companyId;
            foreach ((array)$userIds as $userId) {
                foreach ((array)$tagIds as $tagId) {
                    $this->memberRelTags->deleteBy(['company_id' => $companyId, 'user_id' => $userId, 'tag_id' => $tagId]);
                    // 标签数量-1
                    $this->tagCountReduce(['company_id' => $companyId, 'user_id' => $userId, 'tag_id' => $tagId]);
                }
            }
            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    //根据标签id集合获取会员id，取交集
    public function getUserIdBy($filter, $page, $pageSize)
    {
        $tagIds = $filter['tag_id'] ?? 0;
        if (!$tagIds) {
            return [];
        }
        $userIds = $filter['user_id'] ?? [];
        $companyId = $filter['company_id'] ?? [];

        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $tagIds = (array)$tagIds;
        array_walk($tagIds, function (&$colVal) use ($criteria) {
            $colVal = $criteria->expr()->literal($colVal);
        });
        $criteria->select('user_id')
            ->from('members_rel_tags')
            ->where($criteria->expr()->in('tag_id', $tagIds));
        if ($companyId) {
            $criteria->andWhere($criteria->expr()->eq('company_id', $companyId));
        }
        if ($userIds) {
            $criteria->andWhere($criteria->expr()->in('user_id', $userIds));
        }
        $criteria->groupBy('user_id')
            ->having('count(user_id) ='.count($tagIds));
        if ($pageSize > 0) {
            $criteria->setFirstResult($pageSize * ($page - 1))
                ->setMaxResults($pageSize);
        }
        $list = $criteria->execute()->fetchAll();
        if (!$list) {
            return [];
        }
        $userIds = array_column($list, 'user_id');
        return $userIds;
    }

    /**
     * 判断标签类型并处理
     *
     * @param array $params 参数数组，包含 company_id, user_id 和 tag_id
     * @return array
     */
    public function checkAndProcessTag($params)
    {
        if (empty($params['user_id']) || empty($params['tag_id'])) {
            return [];
        }

        // 获取标签信息
        $filter['company_id'] = $params['company_id'];
        $filter['tag_id'] = array_unique($params['tag_id']);

        $result = [];
        foreach($filter['tag_id']  as $tagId)
        {
            $result[$tagId]['user_id'] = $params['user_id'];
            $result[$tagId]['tag_id'] = $tagId;
            $result[$tagId]['related'] = false;
        }

        $tagList = $this->entityRepository->getLists($filter);
        if (!$tagList) {
            return array_values($result);
        }

        $localIds = [];
        foreach ($tagList as $tag) {
            $localIds[] = $tag['tag_id'];
        }

        $validTagIds = [];

        // 本地离线标签
        if ($localIds) {
            $userTag = $this->memberRelTags->getLists([
                'user_id' => $params['user_id'],
                'tag_id' => $localIds
            ], 'tag_id');

            // 获取用户已存在的标签ID,这里只有非实时的标签
            $validTagIds = array_column($userTag, 'tag_id');
        }

        foreach ($validTagIds as $tagId) {
            $result[$tagId]['related'] = true;
        }

        return array_values($result);
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }
}
