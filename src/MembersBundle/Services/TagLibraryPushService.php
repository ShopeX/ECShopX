<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace MembersBundle\Services;

use MembersBundle\Entities\MemberTags;
use MembersBundle\Entities\MemberTagGroup;
use MembersBundle\Entities\MemberTagGroupRel;
use Exception;

class TagLibraryPushService
{
    private $tagRepository;
    private $tagGroupRepository;
    private $tagGroupRelRepository;
    private $em;

    /**
     * TagLibraryPushService 构造函数.
     */
    public function __construct()
    {
        $this->em = app('registry')->getManager('default');
        $this->tagRepository = $this->em->getRepository(MemberTags::class);
        $this->tagGroupRepository = $this->em->getRepository(MemberTagGroup::class);
        $this->tagGroupRelRepository = $this->em->getRepository(MemberTagGroupRel::class);
    }

    /**
     * 推送标签库数据到云店
     *
     * @param int $companyId 公司ID
     * @param array $tagLibrary 标签库数据
     * @return array 返回统计信息
     * @throws Exception
     */
    public function pushTagLibrary(int $companyId, array $tagLibrary): array
    {
        // 统计信息
        $stats = [
            'success_count' => 0,
            'fail_count' => 0,
            'created_groups' => 0,
            'updated_groups' => 0,
            'created_tags' => 0,
            'updated_tags' => 0,
            'deleted_groups' => 0,
            'deleted_tags' => 0,
        ];

        try {
            $this->em->beginTransaction();

            // 收集本次推送的所有 wechat_group_id 和 wechat_tag_id
            $pushedWechatGroupIds = [];
            $pushedWechatTagIds = [];

            foreach ($tagLibrary as $groupData) {
                try {
                    if (!empty($groupData['wechat_group_id'])) {
                        $pushedWechatGroupIds[] = $groupData['wechat_group_id'];
                    }
                    
                    // 收集标签的 wechat_tag_id
                    if (!empty($groupData['taglist'])) {
                        foreach ($groupData['taglist'] as $tagData) {
                            if (!empty($tagData['wechat_tag_id'])) {
                                $pushedWechatTagIds[] = $tagData['wechat_tag_id'];
                            }
                        }
                    }

                    $this->processTagGroup($companyId, $groupData, $stats);
                } catch (Exception $e) {
                    app('log')->error('[TagLibraryPushService] 处理标签组失败', [
                        'group_data' => $groupData,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    $stats['fail_count']++;
                }
            }

            // 删除本地多余的企微标签组（本地有但导购没传的）
            if (!empty($pushedWechatGroupIds)) {
                $deletedGroups = $this->deleteObsoleteGroups($companyId, $pushedWechatGroupIds);
                $stats['deleted_groups'] = $deletedGroups;
            }

            // 删除本地多余的企微标签（本地有但导购没传的）
            if (!empty($pushedWechatTagIds)) {
                $deletedTags = $this->deleteObsoleteTags($companyId, $pushedWechatTagIds);
                $stats['deleted_tags'] = $deletedTags;
            }

            $this->em->flush();
            $this->em->commit();

            app('log')->info('[TagLibraryPushService] 标签库推送成功', [
                'company_id' => $companyId,
                'stats' => $stats,
            ]);

            return $stats;

        } catch (Exception $e) {
            $this->em->rollback();
            app('log')->error('[TagLibraryPushService] 标签库推送失败', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * 处理单个标签组
     *
     * @param int $companyId 公司ID
     * @param array $groupData 标签组数据
     * @param array &$stats 统计信息（引用传递）
     * @throws Exception
     */
    private function processTagGroup(int $companyId, array $groupData, array &$stats): void
    {
        // 验证标签组数据
        if (empty($groupData['group_name'])) {
            app('log')->warning('[TagLibraryPushService] 标签组名称为空，跳过', ['data' => $groupData]);
            $stats['fail_count']++;
            return;
        }

        $groupName = $groupData['group_name'];
        $description = $groupData['description'] ?? '';
        $wechatGroupId = $groupData['wechat_group_id'] ?? null;
        $taglist = $groupData['taglist'] ?? [];

        // 查找或创建标签组
        $groupId = $this->findOrCreateTagGroup($companyId, $groupName, $description, $wechatGroupId, $stats);

        // 处理标签列表
        $processedTagIds = [];
        foreach ($taglist as $tagData) {
            $tagId = $this->processTag($companyId, $tagData, $stats);
            if ($tagId) {
                $processedTagIds[] = $tagId;
                // 创建或更新标签组与标签的关联关系
                $this->createTagGroupRelation($companyId, $groupId, $tagId);
            }
        }

        // 清理该标签组下已经不存在的标签关联
        if (!empty($processedTagIds)) {
            $this->cleanOldRelations($companyId, $groupId, $processedTagIds);
        }

        $stats['success_count']++;
    }

    /**
     * 查找或创建标签组
     *
     * @param int $companyId 公司ID
     * @param string $groupName 标签组名称
     * @param string $description 描述
     * @param string|null $wechatGroupId 企微标签组ID
     * @param array &$stats 统计信息
     * @return int 标签组ID
     * @throws Exception
     */
    private function findOrCreateTagGroup(int $companyId, string $groupName, string $description, ?string $wechatGroupId, array &$stats): int
    {
        $existingGroup = null;

        // 优先根据 wechat_group_id 查找
        if (!empty($wechatGroupId)) {
            $qb = $this->em->createQueryBuilder();
            $qb->select('g')
                ->from(MemberTagGroup::class, 'g')
                ->where('g.company_id = :company_id')
                ->andWhere('g.wechat_group_id = :wechat_group_id')
                ->setParameter('company_id', $companyId)
                ->setParameter('wechat_group_id', $wechatGroupId);
            $existingGroup = $qb->getQuery()->getOneOrNullResult();
        }

        if ($existingGroup) {
            // 更新现有标签组（ID重复，名字可能变了）
            $existingGroup->setGroupName($groupName);
            $existingGroup->setDescription($description);
            $stats['updated_groups']++;
            return $existingGroup->getGroupId();
        } else {
            // 创建新标签组
            $newGroup = new MemberTagGroup();
            $newGroup->setCompanyId($companyId);
            $newGroup->setGroupName($groupName);
            $newGroup->setDescription($description);
            if (!empty($wechatGroupId)) {
                $newGroup->setWechatGroupId($wechatGroupId);
            }
            $this->em->persist($newGroup);
            $this->em->flush(); // 立即刷新以获取 group_id
            $stats['created_groups']++;
            return $newGroup->getGroupId();
        }
    }

    /**
     * 处理单个标签
     *
     * @param int $companyId 公司ID
     * @param array $tagData 标签数据
     * @param array &$stats 统计信息
     * @return int|null 标签ID，失败返回null
     */
    private function processTag(int $companyId, array $tagData, array &$stats): ?int
    {
        if (empty($tagData['tag_name'])) {
            app('log')->warning('[TagLibraryPushService] 标签名称为空，跳过', ['data' => $tagData]);
            return null;
        }

        $tagName = $tagData['tag_name'];
        $tagDescription = $tagData['description'] ?? '';
        $wechatTagId = $tagData['wechat_tag_id'] ?? null;
        $tagColor = $tagData['tag_color'] ?? '#3e7bff';
        $fontColor = $tagData['font_color'] ?? '#ffffff';
        $tagIcon = $tagData['tag_icon'] ?? '';
        $tagStatus = $tagData['tag_status'] ?? 'online';
        $source = $tagData['source'] ?? 'wechat';

        // 查找或创建标签
        return $this->findOrCreateTag(
            $companyId,
            $tagName,
            $tagDescription,
            $wechatTagId,
            $tagColor,
            $fontColor,
            $tagIcon,
            $tagStatus,
            $source,
            $stats
        );
    }

    /**
     * 查找或创建标签
     *
     * @param int $companyId 公司ID
     * @param string $tagName 标签名称
     * @param string $tagDescription 标签描述
     * @param string|null $wechatTagId 企微标签ID
     * @param string $tagColor 标签颜色
     * @param string $fontColor 字体颜色
     * @param string $tagIcon 标签图标
     * @param string $tagStatus 标签状态
     * @param string $source 来源
     * @param array &$stats 统计信息
     * @return int 标签ID
     * @throws Exception
     */
    private function findOrCreateTag(
        int $companyId,
        string $tagName,
        string $tagDescription,
        ?string $wechatTagId,
        string $tagColor,
        string $fontColor,
        string $tagIcon,
        string $tagStatus,
        string $source,
        array &$stats
    ): int {
        $existingTag = null;

        // 优先根据 wechat_tag_id 查找
        if (!empty($wechatTagId)) {
            $qb = $this->em->createQueryBuilder();
            $qb->select('t')
                ->from(MemberTags::class, 't')
                ->where('t.company_id = :company_id')
                ->andWhere('t.wechat_tag_id = :wechat_tag_id')
                ->setParameter('company_id', $companyId)
                ->setParameter('wechat_tag_id', $wechatTagId);
            $existingTag = $qb->getQuery()->getOneOrNullResult();
        }

        if ($existingTag) {
            // 更新现有标签（ID重复，名字可能变了）
            $existingTag->setTagName($tagName);
            $existingTag->setDescription($tagDescription);
            $existingTag->setTagColor($tagColor);
            $existingTag->setFontColor($fontColor);
            $existingTag->setTagIcon($tagIcon);
            $existingTag->setTagStatus($tagStatus);
            $existingTag->setSource($source);
            $stats['updated_tags']++;
            return $existingTag->getTagId();
        } else {
            // 创建新标签
            $newTag = new MemberTags();
            $newTag->setCompanyId($companyId);
            $newTag->setTagName($tagName);
            $newTag->setDescription($tagDescription);
            $newTag->setTagColor($tagColor);
            $newTag->setFontColor($fontColor);
            $newTag->setTagIcon($tagIcon);
            $newTag->setTagStatus($tagStatus);
            $newTag->setSource($source);
            if (!empty($wechatTagId)) {
                $newTag->setWechatTagId($wechatTagId);
            }
            $this->em->persist($newTag);
            $this->em->flush(); // 立即刷新以获取 tag_id
            $stats['created_tags']++;
            return $newTag->getTagId();
        }
    }

    /**
     * 创建标签组与标签的关联关系
     *
     * @param int $companyId 公司ID
     * @param int $groupId 标签组ID
     * @param int $tagId 标签ID
     */
    private function createTagGroupRelation(int $companyId, int $groupId, int $tagId): void
    {
        // 查找是否已存在关联
        $qb = $this->em->createQueryBuilder();
        $qb->select('r')
            ->from(MemberTagGroupRel::class, 'r')
            ->where('r.company_id = :company_id')
            ->andWhere('r.group_id = :group_id')
            ->andWhere('r.tag_id = :tag_id')
            ->setParameter('company_id', $companyId)
            ->setParameter('group_id', $groupId)
            ->setParameter('tag_id', $tagId);
        $existingRel = $qb->getQuery()->getOneOrNullResult();

        if (!$existingRel) {
            $newRel = new MemberTagGroupRel();
            $newRel->setCompanyId($companyId);
            $newRel->setGroupId($groupId);
            $newRel->setTagId($tagId);
            $this->em->persist($newRel);
        }
    }

    /**
     * 清理标签组下不在本次推送中的标签关联
     *
     * @param int $companyId 公司ID
     * @param int $groupId 标签组ID
     * @param array $processedTagIds 本次推送的标签ID列表
     */
    private function cleanOldRelations(int $companyId, int $groupId, array $processedTagIds): void
    {
        $qb = $this->em->createQueryBuilder();
        $qb->delete(MemberTagGroupRel::class, 'r')
            ->where('r.company_id = :company_id')
            ->andWhere('r.group_id = :group_id')
            ->andWhere($qb->expr()->notIn('r.tag_id', ':tag_ids'))
            ->setParameter('company_id', $companyId)
            ->setParameter('group_id', $groupId)
            ->setParameter('tag_ids', $processedTagIds);
        $qb->getQuery()->execute();
    }

    /**
     * 删除本地多余的企微标签组（本地有但导购没传的）
     *
     * @param int $companyId 公司ID
     * @param array $pushedWechatGroupIds 本次推送的所有 wechat_group_id
     * @return int 删除的标签组数量
     */
    private function deleteObsoleteGroups(int $companyId, array $pushedWechatGroupIds): int
    {
        // 查找所有有 wechat_group_id 但不在本次推送列表中的标签组
        $qb = $this->em->createQueryBuilder();
        $qb->select('g')
            ->from(MemberTagGroup::class, 'g')
            ->where('g.company_id = :company_id')
            ->andWhere('g.wechat_group_id IS NOT NULL')
            ->andWhere($qb->expr()->notIn('g.wechat_group_id', ':wechat_group_ids'))
            ->setParameter('company_id', $companyId)
            ->setParameter('wechat_group_ids', $pushedWechatGroupIds);

        $obsoleteGroups = $qb->getQuery()->getResult();
        $deletedCount = 0;

        foreach ($obsoleteGroups as $group) {
            // 先删除关联关系
            $relQb = $this->em->createQueryBuilder();
            $relQb->delete(MemberTagGroupRel::class, 'r')
                ->where('r.company_id = :company_id')
                ->andWhere('r.group_id = :group_id')
                ->setParameter('company_id', $companyId)
                ->setParameter('group_id', $group->getGroupId());
            $relQb->getQuery()->execute();

            // 删除标签组
            $this->em->remove($group);
            $deletedCount++;

            app('log')->info('[TagLibraryPushService] 删除多余标签组', [
                'company_id' => $companyId,
                'group_id' => $group->getGroupId(),
                'group_name' => $group->getGroupName(),
                'wechat_group_id' => $group->getWechatGroupId(),
            ]);
        }

        return $deletedCount;
    }

    /**
     * 删除本地多余的企微标签（本地有但导购没传的）
     *
     * @param int $companyId 公司ID
     * @param array $pushedWechatTagIds 本次推送的所有 wechat_tag_id
     * @return int 删除的标签数量
     */
    private function deleteObsoleteTags(int $companyId, array $pushedWechatTagIds): int
    {
        // 查找所有有 wechat_tag_id 但不在本次推送列表中的标签
        $qb = $this->em->createQueryBuilder();
        $qb->select('t')
            ->from(MemberTags::class, 't')
            ->where('t.company_id = :company_id')
            ->andWhere('t.wechat_tag_id IS NOT NULL')
            ->andWhere($qb->expr()->notIn('t.wechat_tag_id', ':wechat_tag_ids'))
            ->setParameter('company_id', $companyId)
            ->setParameter('wechat_tag_ids', $pushedWechatTagIds);

        $obsoleteTags = $qb->getQuery()->getResult();
        $deletedCount = 0;

        foreach ($obsoleteTags as $tag) {
            // 先删除标签组关联关系
            $relQb = $this->em->createQueryBuilder();
            $relQb->delete(MemberTagGroupRel::class, 'r')
                ->where('r.company_id = :company_id')
                ->andWhere('r.tag_id = :tag_id')
                ->setParameter('company_id', $companyId)
                ->setParameter('tag_id', $tag->getTagId());
            $relQb->getQuery()->execute();

            // 删除会员标签关联（如果有的话）
            $memberRelQb = $this->em->createQueryBuilder();
            $memberRelQb->delete('MembersBundle\Entities\MemberRelTags', 'mrt')
                ->where('mrt.tag_id = :tag_id')
                ->setParameter('tag_id', $tag->getTagId());
            $memberRelQb->getQuery()->execute();

            // 删除标签
            $this->em->remove($tag);
            $deletedCount++;

            app('log')->info('[TagLibraryPushService] 删除多余标签', [
                'company_id' => $companyId,
                'tag_id' => $tag->getTagId(),
                'tag_name' => $tag->getTagName(),
                'wechat_tag_id' => $tag->getWechatTagId(),
            ]);
        }

        return $deletedCount;
    }
}

