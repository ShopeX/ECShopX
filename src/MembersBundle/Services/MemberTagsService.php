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
use MembersBundle\Entities\MemberTagGroup;
use MembersBundle\Entities\MemberTagGroupRel;
use Dingo\Api\Exception\ResourceException;
use MembersBundle\Services\MemberService;

use Exception;

class MemberTagsService
{
    public $entityRepository;
    public $memberRelTags;
    public $tagGroupRepository;
    public $tagGroupRelRepository;
    /**
     * MemberService 构造函数.
     */
    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(MemberTags::class);
        $this->memberRelTags = app('registry')->getManager('default')->getRepository(MemberRelTags::class);
        $this->tagGroupRepository = app('registry')->getManager('default')->getRepository(MemberTagGroup::class);
        $this->tagGroupRelRepository = app('registry')->getManager('default')->getRepository(MemberTagGroupRel::class);
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
     * @param bool $skipPush true表示跳过推送到导购平台（用于导购同步标签到本地时避免循环）
     * @return bool
     * @throws Exception
     */
    public function createRelTags($userIds, $tagIds, $companyId, $forceCreate = false, $skipPush = false)
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
            
            // 推送标签关系到导购平台（如果不需要跳过推送）
            if (!$skipPush) {
                try {
                    $pushService = new MemberTagRelationPushService();
                    $pushService->pushTagRelations($companyId, $userIds, $tagIds, 'add');
                } catch (\Exception $e) {
                    // 推送失败不影响主流程，只记录日志
                    app('log')->warning('[createRelTags] 推送导购平台失败', [
                        'company_id' => $companyId,
                        'user_ids' => $userIds,
                        'tag_ids' => $tagIds,
                        'error' => $e->getMessage(),
                    ]);
                }
            } else {
                app('log')->info('[createRelTags] 跳过推送到导购平台', [
                    'company_id' => $companyId,
                    'user_ids' => $userIds,
                    'tag_ids' => $tagIds,
                ]);
            }
            
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
     * 删除指定会员的所有标签
     *
     * @param int $userId 会员ID
     * @param int $companyId 公司ID
     * @return bool
     * @throws \Exception
     */
    public function deleteRelTagsByUserId($userId, $companyId)
    {
        $savedata['user_id'] = $userId;
        $savedata['company_id'] = $companyId;
        
        try {
            // 查找该会员的所有标签关系
            $relations = $this->memberRelTags->lists($savedata);
            
            if (isset($relations['list']) && !empty($relations['list'])) {
                foreach ($relations['list'] as $relation) {
                    $tagData = [
                        'tag_id' => $relation['tag_id'],
                        'company_id' => $companyId,
                    ];
                    // 标签数量-1
                    $this->tagCountReduce($tagData);
                }
                
                // 删除所有标签关系
                $this->memberRelTags->deleteBy($savedata);
            }
            
            return true;
        } catch (\Exception $e) {
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
        $result = $this->memberRelTags->deleteBy($savedata);
        
        // 推送标签删除关系到导购平台
        if ($result) {
            try {
                $pushService = new MemberTagRelationPushService();
                $pushService->pushTagRelations($companyId, [$userId], [$tagId], 'remove');
            } catch (\Exception $e) {
                // 推送失败不影响主流程，只记录日志
                app('log')->warning('[delRelMemberTag] 推送导购平台失败', [
                    'company_id' => $companyId,
                    'user_id' => $userId,
                    'tag_id' => $tagId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        return $result;
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

    /**
     * 创建标签组并关联标签。
     * - 首次创建标签组时，如果已有标签则先自动生成一个默认组并关联所有现有标签
     * - 然后再创建本次提交的标签组并绑定传入的标签
     *
     * @param array $params
     * @return array
     * @throws \Exception
     */
    public function createTagGroup(array $params)
    {
        if (empty($params['group_name'])) {
            throw new ResourceException('标签组名称不能为空');
        }
        $companyId = $params['company_id'] ?? 0;
        $distributorId = $params['distributor_id'] ?? 0;
        $tagIds = isset($params['tag_ids']) ? (array)$params['tag_ids'] : [];
        $tagIds = array_values(array_unique(array_filter($tagIds)));

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            // 首次创建时，如果已有标签则自动创建默认分组并关联现有标签
            $existingGroupCount = $this->tagGroupRepository->count([
                'company_id' => $companyId,
                'distributor_id' => $distributorId,
            ]);
            if ($existingGroupCount === 0) {
                $allTags = $this->entityRepository->getLists([
                    'company_id' => $companyId,
                    'distributor_id' => $distributorId,
                ], 'tag_id');
                if ($allTags) {
                    $defaultTagIds = array_column($allTags, 'tag_id');
                    if ($defaultTagIds) {
                        $defaultGroup = $this->tagGroupRepository->create([
                            'group_name' => '默认标签组',
                            'description' => '系统自动创建，包含当前全部标签',
                            'company_id' => $companyId,
                            'distributor_id' => $distributorId,
                        ]);
                        $this->bindTagsToGroup($defaultGroup['group_id'], $defaultTagIds, $companyId, $distributorId);
                    }
                }
            }

            // 创建本次提交的标签组
            $group = $this->tagGroupRepository->create([
                'group_name' => $params['group_name'],
                'description' => $params['description'] ?? '',
                'company_id' => $companyId,
                'distributor_id' => $distributorId,
            ]);

            if ($tagIds) {
                // 只绑定当前企业下存在的标签
                $validTags = $this->entityRepository->getLists([
                    'company_id' => $companyId,
                    'distributor_id' => $distributorId,
                    'tag_id' => $tagIds,
                ], 'tag_id');
                $validTagIds = array_column($validTags, 'tag_id');
                if ($validTagIds) {
                    $this->bindTagsToGroup($group['group_id'], $validTagIds, $companyId, $distributorId);
                }
            }

            // 可选：创建新标签并自动归属到当前标签组
            if (!empty($params['tags']) && is_array($params['tags'])) {
                foreach ($params['tags'] as $tagData) {
                    if (empty($tagData['tag_name'])) {
                        throw new ResourceException('标签名称不能为空');
                    }
                    $tagData['company_id'] = $companyId;
                    $tagData['distributor_id'] = $distributorId;
                    $tagData['group_id'] = $group['group_id'];
                    $this->createTagWithGroup($tagData);
                }
            }

            $conn->commit();
            return $group;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
     * 删除标签组，只有当组内无标签关联时才允许删除
     *
     * @param int $groupId
     * @param int $companyId
     * @param int $distributorId
     * @return bool
     */
    public function deleteTagGroup(int $groupId, int $companyId, int $distributorId = 0)
    {
        $groupInfo = $this->tagGroupRepository->getInfo([
            'group_id' => $groupId,
            'company_id' => $companyId,
            'distributor_id' => $distributorId,
        ]);
        if (!$groupInfo) {
            return true;
        }

        // 获取该组下的所有标签ID
        $tagRelList = $this->tagGroupRelRepository->getLists([
            'group_id' => $groupId,
            'company_id' => $companyId,
            'distributor_id' => $distributorId,
        ], 'tag_id');
        
        $tagIds = array_column($tagRelList, 'tag_id');
        
        // 如果有标签，检查是否有标签绑定了会员
        if (!empty($tagIds)) {
            // 检查这些标签是否有绑定会员
            $memberRelCount = $this->memberRelTags->count([
                'company_id' => $companyId,
                'tag_id' => $tagIds,
            ]);
            
            if ($memberRelCount > 0) {
                throw new ResourceException('该标签组下的标签已绑定会员，无法删除');
            }
        }

        // 使用事务确保数据一致性
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            // 删除所有标签（先删除标签与会员的关联，再删除标签本身）
            if (!empty($tagIds)) {
                foreach ($tagIds as $tagId) {
                    // 删除标签与会员的关联（虽然上面已经检查过没有绑定，但为了安全还是执行一次）
                    $this->memberRelTags->deleteBy([
                        'company_id' => $companyId,
                        'tag_id' => $tagId,
                    ]);
                    
                    // 删除标签与标签组的关联
                    $this->tagGroupRelRepository->deleteBy([
                        'group_id' => $groupId,
                        'company_id' => $companyId,
                        'distributor_id' => $distributorId,
                        'tag_id' => $tagId,
                    ]);
                    
                    // 检查标签是否还被其他标签组使用
                    $otherGroupCount = $this->tagGroupRelRepository->count([
                        'company_id' => $companyId,
                        'distributor_id' => $distributorId,
                        'tag_id' => $tagId,
                    ]);
                    
                    // 如果没有被其他标签组使用，则删除标签本身
                    if ($otherGroupCount === 0) {
                        $this->entityRepository->deleteBy([
                            'tag_id' => $tagId,
                            'company_id' => $companyId,
                            'distributor_id' => $distributorId,
                        ]);
                    }
                }
            }

            // 删除标签组
            $result = $this->tagGroupRepository->deleteById($groupId);
            
            $conn->commit();
            return $result;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
     * 创建标签并可选关联到标签组
     *
     * @param array $params
     * @return array
     * @throws \Exception
     */
    public function createTagWithGroup(array $params)
    {
        $companyId = $params['company_id'] ?? 0;
        $distributorId = $params['distributor_id'] ?? 0;
        $groupId = $params['group_id'] ?? 0;

        if (empty($params['tag_name'])) {
            throw new ResourceException('标签名称不能为空');
        }
        // 同企业同分销商下的标签名唯一性校验
        $exists = $this->entityRepository->getInfo([
            'company_id' => $companyId,
            'distributor_id' => $distributorId,
            'tag_name' => $params['tag_name'],
        ]);
        if ($exists) {
            throw new ResourceException('标签名称不能重复');
        }
        // 默认颜色兜底
        $params['tag_color'] = $params['tag_color'] ?? '#ff1939';
        $params['font_color'] = $params['font_color'] ?? '#ffffff';

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $tag = $this->entityRepository->create($params);
            if ($groupId) {
                $groupInfo = $this->tagGroupRepository->getInfo([
                    'group_id' => $groupId,
                    'company_id' => $companyId,
                    'distributor_id' => $distributorId,
                ]);
                if (!$groupInfo) {
                    throw new ResourceException('标签组不存在或不属于当前公司');
                }
                $this->bindTagsToGroup($groupId, [$tag['tag_id']], $companyId, $distributorId);
            }
            $conn->commit();
            return $tag;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
     * 获取标签组列表，附带组内标签，支持分页
     *
     * @param array $filter 必须包含 company_id / distributor_id，可选 group_name 用于搜索
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function getTagGroupList(array $filter, int $page = 1, int $pageSize = 20)
    {
        $companyId = $filter['company_id'] ?? 0;
        $distributorId = $filter['distributor_id'] ?? 0;
        $groupName = $filter['group_name'] ?? '';
        $orderBy = ['created' => 'DESC'];

        // 如果传了group_name，需要搜索标签组名称或标签名称包含该关键词的标签组
        $targetGroupIds = null;
        if (!empty($groupName)) {
            $targetGroupIds = [];
            
            // 1. 搜索标签组名称包含该关键词的标签组
            $groupFilter = [
                'company_id' => $companyId,
                'distributor_id' => $distributorId,
                'group_name|contains' => $groupName,
            ];
            $matchedGroups = $this->tagGroupRepository->lists($groupFilter, '*', 1, -1, $orderBy);
            if (!empty($matchedGroups['list'])) {
                $targetGroupIds = array_merge($targetGroupIds, array_column($matchedGroups['list'], 'group_id'));
            }
            
            // 2. 搜索标签名称包含该关键词的标签，然后找到这些标签所属的标签组
            $tagFilter = [
                'company_id' => $companyId,
                'distributor_id' => $distributorId,
                'tag_name|contains' => $groupName,
            ];
            $matchedTags = $this->entityRepository->getLists($tagFilter, 'tag_id');
            if (!empty($matchedTags)) {
                $matchedTagIds = array_column($matchedTags, 'tag_id');
                // 查找这些标签所属的标签组
                $tagGroupRels = $this->tagGroupRelRepository->getLists([
                    'company_id' => $companyId,
                    'distributor_id' => $distributorId,
                    'tag_id' => $matchedTagIds,
                ], 'group_id');
                if (!empty($tagGroupRels)) {
                    $groupIdsFromTags = array_unique(array_column($tagGroupRels, 'group_id'));
                    $targetGroupIds = array_merge($targetGroupIds, $groupIdsFromTags);
                }
            }
            
            // 去重
            $targetGroupIds = array_values(array_unique(array_filter($targetGroupIds)));
            
            // 如果没有匹配的标签组，直接返回空结果
            if (empty($targetGroupIds)) {
                return ['total_count' => 0, 'list' => []];
            }
        }

        // 构建查询条件
        $queryFilter = [
            'company_id' => $companyId,
            'distributor_id' => $distributorId,
        ];
        // 如果指定了标签组ID列表，添加过滤条件
        if ($targetGroupIds !== null) {
            $queryFilter['group_id'] = $targetGroupIds;
        }

        $groups = $this->tagGroupRepository->lists($queryFilter, '*', $page, $pageSize, $orderBy);

        if (empty($groups['list'])) {
            return ['total_count' => 0, 'list' => []];
        }

        $groupIds = array_column($groups['list'], 'group_id');
        $relList = $this->tagGroupRelRepository->getLists([
            'company_id' => $companyId,
            'distributor_id' => $distributorId,
            'group_id' => $groupIds,
        ], 'group_id, tag_id');

        $groupTagMap = [];
        $tagIds = [];
        foreach ($relList as $rel) {
            $groupTagMap[$rel['group_id']][] = $rel['tag_id'];
            $tagIds[] = $rel['tag_id'];
        }
        $tagIds = array_values(array_unique(array_filter($tagIds)));

        $tagInfoMap = [];
        if ($tagIds) {
            $tags = $this->entityRepository->getLists([
                'company_id' => $companyId,
                'distributor_id' => $distributorId,
                'tag_id' => $tagIds,
            ]);
            foreach ($tags as $tag) {
                $tagInfoMap[$tag['tag_id']] = $tag;
            }
        }

        foreach ($groups['list'] as &$group) {
            $ids = $groupTagMap[$group['group_id']] ?? [];
            $group['tags'] = [];
            foreach ($ids as $tid) {
                if (isset($tagInfoMap[$tid])) {
                    $group['tags'][] = $tagInfoMap[$tid];
                }
            }
        }

        return $groups;
    }

    /**
     * 编辑标签组：更新组信息、标签、删除指定标签及其会员关联
     *
     * @param array $params 需要包含 group_id, group_name, company_id, distributor_id
     * @return array
     * @throws \Exception
     */
    public function updateTagGroup(array $params)
    {
        $groupId = (int)($params['group_id'] ?? 0);
        if (!$groupId) {
            throw new ResourceException('标签组ID不能为空');
        }
        if (empty($params['group_name'])) {
            throw new ResourceException('标签组名称不能为空');
        }
        $companyId = $params['company_id'] ?? 0;
        $distributorId = $params['distributor_id'] ?? 0;

        $group = $this->tagGroupRepository->getInfo([
            'group_id' => $groupId,
            'company_id' => $companyId,
            'distributor_id' => $distributorId,
        ]);
        if (!$group) {
            throw new ResourceException('标签组不存在');
        }

        $deleteIds = isset($params['deleteids']) ? (array)$params['deleteids'] : [];
        $deleteIds = array_values(array_unique(array_filter($deleteIds, function ($v) {
            return (int)$v > 0;
        })));

        $tags = isset($params['tags']) && is_array($params['tags']) ? $params['tags'] : [];

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            // 更新标签组信息
            $updateData = [
                'group_name' => $params['group_name'],
            ];
            if (isset($params['description'])) {
                $updateData['description'] = $params['description'];
            }
            $this->tagGroupRepository->updateOneBy([
                'group_id' => $groupId,
                'company_id' => $companyId,
                'distributor_id' => $distributorId,
            ], $updateData);

            // 删除指定标签的关联（组关联 + 会员关联），如果标签未被其他标签组使用则删除标签本身
            foreach ($deleteIds as $tagId) {
                $tagId = (int)$tagId;
                if (!$tagId) {
                    continue;
                }
                // 删除标签与会员的关联
                $relCount = $this->memberRelTags->count([
                    'company_id' => $companyId,
                    'tag_id' => $tagId,
                ]);
                if ($relCount > 0) {
                    $this->memberRelTags->deleteBy([
                        'company_id' => $companyId,
                        'tag_id' => $tagId,
                    ]);
                    $this->tagCountReduce([
                        'company_id' => $companyId,
                        'tag_id' => $tagId,
                    ], $relCount);
                }
                // 删除标签与当前标签组的关联
                $this->tagGroupRelRepository->deleteBy([
                    'group_id' => $groupId,
                    'company_id' => $companyId,
                    'distributor_id' => $distributorId,
                    'tag_id' => $tagId,
                ]);
                // 检查标签是否还被其他标签组使用
                $otherGroupCount = $this->tagGroupRelRepository->count([
                    'company_id' => $companyId,
                    'distributor_id' => $distributorId,
                    'tag_id' => $tagId,
                ]);
                // 如果没有被其他标签组使用，则删除标签本身
                if ($otherGroupCount === 0) {
                    $this->entityRepository->deleteBy([
                        'tag_id' => $tagId,
                        'company_id' => $companyId,
                        'distributor_id' => $distributorId,
                    ]);
                }
            }

            // 处理标签（新增或更新并确保关联）
            foreach ($tags as $tag) {
                $tagId = (int)($tag['tag_id'] ?? 0);
                $tagName = $tag['tag_name'] ?? '';
                if (!$tagName) {
                    throw new ResourceException('标签名称不能为空');
                }

                // 新增标签
                if ($tagId === 0) {
                    $tag['company_id'] = $companyId;
                    $tag['distributor_id'] = $distributorId;
                    $tag['group_id'] = $groupId;
                    $this->createTagWithGroup($tag);
                    continue;
                }

                // 校验标签归属
                $tagInfo = $this->entityRepository->getInfo([
                    'tag_id' => $tagId,
                    'company_id' => $companyId,
                    'distributor_id' => $distributorId,
                ]);
                if (!$tagInfo) {
                    throw new ResourceException('标签不存在或不属于当前公司');
                }

                // 重名校验（排除自身）
                $dupList = $this->entityRepository->getLists([
                    'company_id' => $companyId,
                    'distributor_id' => $distributorId,
                    'tag_name' => $tagName,
                ], 'tag_id');
                if ($dupList) {
                    foreach ($dupList as $dup) {
                        if (!empty($dup['tag_id']) && (int)$dup['tag_id'] !== $tagId) {
                            throw new ResourceException('标签名称不能重复');
                        }
                    }
                }

                $updateTagData = [
                    'tag_name' => $tagName,
                ];
                if (isset($tag['tag_color'])) {
                    $updateTagData['tag_color'] = $tag['tag_color'];
                }
                if (isset($tag['font_color'])) {
                    $updateTagData['font_color'] = $tag['font_color'];
                }
                if (isset($tag['description'])) {
                    $updateTagData['description'] = $tag['description'];
                }
                if (isset($tag['category_id'])) {
                    $updateTagData['category_id'] = $tag['category_id'];
                }

                $this->entityRepository->updateBy([
                    'tag_id' => $tagId,
                    'company_id' => $companyId,
                    'distributor_id' => $distributorId,
                ], $updateTagData);

                // 确保关联存在
                $this->bindTagsToGroup($groupId, [$tagId], $companyId, $distributorId);
            }

            $conn->commit();
            return [
                'group_id' => $groupId,
                'group_name' => $params['group_name'],
            ];
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }


    /**
     * 为指定标签组绑定标签（去重）
     */
    private function bindTagsToGroup(int $groupId, array $tagIds, int $companyId, int $distributorId = 0)
    {
        foreach ($tagIds as $tagId) {
            if (!$tagId) {
                continue;
            }
            $exists = $this->tagGroupRelRepository->getInfo([
                'group_id' => $groupId,
                'tag_id' => $tagId,
                'company_id' => $companyId,
                'distributor_id' => $distributorId,
            ]);
            if ($exists) {
                continue;
            }
            $this->tagGroupRelRepository->create([
                'group_id' => $groupId,
                'tag_id' => $tagId,
                'company_id' => $companyId,
                'distributor_id' => $distributorId,
            ]);
        }
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }
}
