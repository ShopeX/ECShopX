<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace MembersBundle\Services;

use ThirdPartyBundle\Services\MarketingCenter\Request;
use MembersBundle\Entities\MemberTags;
use MembersBundle\Jobs\PushMemberTagRelationJob;

class MemberTagRelationPushService
{
    /**
     * 推送会员标签关系到导购平台
     *
     * @param int $companyId 公司ID
     * @param array $userIds 会员ID列表
     * @param array $tagIds 标签ID列表
     * @param string $action 操作类型：add/remove
     * @return array 推送结果
     */
    public function pushTagRelations(int $companyId, array $userIds, array $tagIds, string $action = 'add'): array
    {
        try {
            // 1. 获取会员信息
            $members = $this->getMemberInfo($companyId, $userIds);
            
            if (empty($members)) {
                app('log')->warning('[MemberTagRelationPush] 未找到会员信息', [
                    'company_id' => $companyId,
                    'user_ids' => $userIds,
                ]);
                return ['success' => true, 'message' => '未找到会员信息'];
            }
            
            // 2. 获取标签信息
            $tags = $this->getTagInfo($companyId, $tagIds);
            
            if (empty($tags)) {
                app('log')->warning('[MemberTagRelationPush] 未找到标签信息', [
                    'company_id' => $companyId,
                    'tag_ids' => $tagIds,
                ]);
                return ['success' => true, 'message' => '未找到标签信息'];
            }
            
            // 3. 构建推送数据
            $relations = [];
            
            foreach ($members as $member) {
                foreach ($tags as $tag) {
                    $relations[] = [
                        'user_id' => (string)$member['user_id'],
                        'mobile' => $member['mobile'] ?? '',
                        'tag_id' => (string)$tag['tag_id'],
                        'tag_name' => $tag['tag_name'],
                        'tag_type' => $this->getTagType($tag['source']),
                        'wechat_tag_id' => $tag['wechat_tag_id'] ?? null,
                    ];
                }
            }
            
            
            if (empty($relations)) {
                app('log')->warning('[MemberTagRelationPush] 没有需要推送的数据', [
                    'company_id' => $companyId,
                    'user_ids' => $userIds,
                    'tag_ids' => $tagIds,
                ]);
                return ['success' => true, 'message' => '没有需要推送的数据'];
            }
            
            // 4. 判断推送方式：≤50条同步，>50条异步
            $totalCount = count($relations);
            if ($totalCount <= 50) {
                // 同步推送
                return $this->syncPush($companyId, $action, $relations);
            } else {
                // 异步推送
                return $this->asyncPush($companyId, $action, $relations);
            }
            
        } catch (\Exception $e) {
            app('log')->error('[MemberTagRelationPush] 推送失败', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * 同步推送
     *
     * @param int $companyId 公司ID
     * @param string $action 操作类型
     * @param array $relations 标签关系数据
     * @return array
     */
    private function syncPush(int $companyId, string $action, array $relations): array
    {
        $params = [
            'company_id' => (string)$companyId,
            'action' => $action,
            'timestamp' => date('Y-m-d H:i:s'),
            'relations' => $relations,
        ];
        
        app('log')->info('[MemberTagRelationPush] 同步推送', [
            'company_id' => $companyId,
            'action' => $action,
            'count' => count($relations),
        ]);
        
        $request = new Request();
        $result = $request->call($companyId, 'members.tag.relation.sync', [$params]);
        
        app('log')->info('[MemberTagRelationPush] 同步推送完成', [
            'company_id' => $companyId,
            'result' => $result,
        ]);
        
        return [
            'success' => true,
            'mode' => 'sync',
            'result' => $result,
        ];
    }
    
    /**
     * 异步推送
     *
     * @param int $companyId 公司ID
     * @param string $action 操作类型
     * @param array $relations 标签关系数据
     * @return array
     */
    private function asyncPush(int $companyId, string $action, array $relations): array
    {
        app('log')->info('[MemberTagRelationPush] 异步推送', [
            'company_id' => $companyId,
            'action' => $action,
            'count' => count($relations),
        ]);
        
        // 推入队列
        $job = new PushMemberTagRelationJob(
            $companyId,
            $action,
            $relations
        );
        $job->onQueue('marketing');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);
        
        return [
            'success' => true,
            'mode' => 'async',
            'message' => '已加入推送队列',
        ];
    }
    
    /**
     * 获取会员信息
     *
     * @param int $companyId 公司ID
     * @param array $userIds 会员ID列表
     * @return array
     */
    private function getMemberInfo(int $companyId, array $userIds): array
    {
        $em = app('registry')->getManager('default');
        $qb = $em->createQueryBuilder();
        
        // 转换为 literal 防止SQL注入
        array_walk($userIds, function (&$colVal) use ($qb) {
            $colVal = $qb->expr()->literal($colVal);
        });
        
        $qb->select('m.user_id', 'm.mobile')
            ->from('MembersBundle\Entities\Members', 'm')
            ->where('m.company_id = :company_id')
            ->andWhere($qb->expr()->in('m.user_id', $userIds))
            ->setParameter('company_id', $companyId);
        
        return $qb->getQuery()->getResult();
    }
    
    /**
     * 获取标签信息（过滤掉企业微信标签）
     *
     * @param int $companyId 公司ID
     * @param array $tagIds 标签ID列表
     * @return array
     */
    private function getTagInfo(int $companyId, array $tagIds): array
    {
        $em = app('registry')->getManager('default');
        $qb = $em->createQueryBuilder();
        
        // 转换为 literal 防止SQL注入
        array_walk($tagIds, function (&$colVal) use ($qb) {
            $colVal = $qb->expr()->literal($colVal);
        });
        
        $qb->select('t.tag_id', 't.tag_name', 't.source', 't.wechat_tag_id')
            ->from(MemberTags::class, 't')
            ->where('t.company_id = :company_id')
            ->andWhere($qb->expr()->in('t.tag_id', $tagIds))
            ->setParameter('company_id', $companyId);
        
        
        return $qb->getQuery()->getResult();
    }
    
    /**
     * 转换标签类型
     *
     * @param string $source 标签来源
     * @return string 标签类型
     */
    private function getTagType(string $source): string
    {
        $typeMap = [
            'self' => 'self',      // 自定义标签
            'wechat' => 'wechat',  // 企业微信标签
            'staff' => 'staff',    // 员工标签
        ];
        
        return $typeMap[$source] ?? 'self';
    }
}

