<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace MembersBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use Dingo\Api\Exception\StoreResourceFailedException;
use Dingo\Api\Exception\ResourceException;
use App\Http\Controllers\Controller as Controller;
use MembersBundle\Services\MemberTagsService;
use MembersBundle\Services\MemberSegmentRuleService;
use MembersBundle\Entities\MemberSegmentRule;

class MemberTagAction extends Controller
{
    public $memberTagService;
    public $limit;
    public $segmentRuleRepository;
    public $segmentRuleService;

    public function __construct()
    {
        $this->memberTagService = new MemberTagsService();
        $this->limit = 20;
        $this->segmentRuleRepository = app('registry')->getManager('default')->getRepository(MemberSegmentRule::class);
        $this->segmentRuleService = new MemberSegmentRuleService();
    }

    /**
     * 创建人群规则
     * 路由: POST /member/segment-rule
     * 路由名称: member.segment.rule.create
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createSegmentRule(Request $request)
    {
        $params = $request->all();
        if(!empty($params['condition'])){
            if(!is_array($params['condition'])){
                $params['condition'] = json_decode($params['condition'], true);
            }

        }
        // 参数验证
        $rules = [
            'rule_name' => ['required|string|max:100', '规则名称必填且不能超过100个字符'],
            'description' => ['string|max:255', '人群说明不能超过255个字符'],
            'condition' => ['required|array', '规则配置必填且必须是数组'],
            'tag_ids' => ['required|array', '标签ID数组必填且必须是数组'],
        ];
        
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        // 验证 condition 结构
        if (empty($params['condition']) || !is_array($params['condition'])) {
            throw new ResourceException('规则配置不能为空');
        }

        // 获取当前用户信息
        $user = app('auth')->user();
        $data = [
            'company_id' => $user->get('company_id'),
            'distributor_id' => 0,
            'rule_name' => $params['rule_name'],
            'description' => $params['description'] ?? '',
            'rule_config' => $params['condition'], // 前端传的 condition 就是 rule_config
            'tag_ids' => $params['tag_ids'],
            'status' => isset($params['status']) ? (int)$params['status'] : 1,
        ];

        // 如果是分销商，设置分销商ID
        $operatorType = $user->get('operator_type');
        if ($operatorType == 'distributor') {
            $data['distributor_id'] = $user->get('distributor_id') ?: 0;
        } elseif (isset($params['distributor_id'])) {
            $data['distributor_id'] = (int)$params['distributor_id'];
        }

        // 创建规则
        try {
            $result = $this->segmentRuleRepository->create($data);
            
            // 获取匹配的用户ID并打标签
            $matchedUserIds = [];
            $taggedCount = 0;
            
            try {
                // 查询匹配的用户ID
                $matchedUserIds = $this->segmentRuleService->queryMatchedUserIds(
                    $params['condition'],
                    $data['company_id'],
                    $data['distributor_id']
                );
                
                // 如果有匹配的用户且有标签，则打标签
                if (!empty($matchedUserIds) && !empty($params['tag_ids'])) {
                    $this->memberTagService->createRelTags(
                        $matchedUserIds,
                        $params['tag_ids'],
                        $data['company_id']
                    );
                    $taggedCount = count($matchedUserIds);
                    
                    app('log')->info('[createSegmentRule] 圈选规则创建并打标签成功', [
                        'rule_id' => $result['rule_id'],
                        'rule_name' => $result['rule_name'],
                        'matched_count' => count($matchedUserIds),
                        'tagged_count' => $taggedCount,
                        'tag_ids' => $params['tag_ids'],
                    ]);
                }
            } catch (\Exception $e) {
                // 打标签失败不影响规则创建，只记录日志
                app('log')->error('[createSegmentRule] 打标签失败', [
                    'rule_id' => $result['rule_id'],
                    'error' => $e->getMessage(),
                ]);
            }
            
            return $this->response->array([
                'rule_id' => $result['rule_id'],
                'rule_name' => $result['rule_name'],
                'description' => $result['description'] ?? '',
                'matched_count' => count($matchedUserIds),
                'tagged_count' => $taggedCount,
                'status' => 'success'
            ]);
        } catch (\Exception $e) {
            throw new ResourceException('创建规则失败：' . $e->getMessage());
        }
    }

    /**
     * 获取人群规则列表
     * 路由: GET /member/segment-rule
     * 路由名称: member.segment.rule.list
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSegmentRuleList(Request $request)
    {
        // 获取当前用户信息
        $user = app('auth')->user();
        $companyId = $user->get('company_id');
        $operatorType = $user->get('operator_type');
        $distributorId = $user->get('distributor_id');
        
        // 准备参数
        $params = [
            'company_id' => $companyId,
            'operator_type' => $operatorType,
            'distributor_id' => $distributorId,
            'page' => $request->get('page', 1),
            'page_size' => $request->get('page_size', 20),
            'status' => $request->get('status'),
            'rule_name' => $request->get('rule_name'),
            'tag_name' => $request->get('tag_name'),
            'created_start' => $request->get('created_start'),
            'created_end' => $request->get('created_end'),
            'request_distributor_id' => $request->get('distributor_id'),
        ];

        try {
            // 调用 Service 获取列表
            $result = $this->segmentRuleService->getSegmentRuleList($params);
            
            return $this->response->array($result);
        } catch (\Exception $e) {
            throw new ResourceException('获取规则列表失败：' . $e->getMessage());
        }
    }

    /**
     * 编辑人群规则
     * 路由: PUT /member/segment-rule/{rule_id}
     * 路由名称: member.segment.rule.update
     *
     * @param Request $request
     * @param int $rule_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateSegmentRule(Request $request, $rule_id)
    {
        $params = $request->all();
        
        // 参数验证
        $rules = [
            'rule_name' => ['string|max:100', '规则名称不能超过100个字符'],
            'description' => ['string|max:255', '人群说明不能超过255个字符'],
            'condition' => ['array', '规则配置必须是数组'],
            'tag_ids' => ['array', '标签ID数组必须是数组'],
        ];
        
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        // 获取当前用户信息
        $user = app('auth')->user();
        $companyId = $user->get('company_id');
        $operatorType = $user->get('operator_type');
        
        // 构建查询条件
        $filter = [
            'rule_id' => (int)$rule_id,
            'company_id' => $companyId,
        ];

        // 如果是分销商，只能编辑自己的规则
        if ($operatorType == 'distributor') {
            $distributorId = $user->get('distributor_id');
            if ($distributorId) {
                $filter['distributor_id'] = $distributorId;
            }
        } else {
            $requestDistributorId = $request->get('distributor_id');
            if ($requestDistributorId !== null) {
                $filter['distributor_id'] = (int)$requestDistributorId;
            }
        }

        // 验证规则是否存在
        $rule = $this->segmentRuleRepository->getOneBy($filter);
        if (!$rule) {
            throw new ResourceException('规则不存在或无权限编辑');
        }

        // 准备更新数据
        $updateData = [];
        
        if (isset($params['rule_name'])) {
            $updateData['rule_name'] = $params['rule_name'];
        }
        
        if (isset($params['description'])) {
            $updateData['description'] = $params['description'];
        }
        
        if (isset($params['condition'])) {
            if (empty($params['condition']) || !is_array($params['condition'])) {
                throw new ResourceException('规则配置不能为空');
            }
            $updateData['rule_config'] = $params['condition'];
        }
        
        if (isset($params['tag_ids'])) {
            $updateData['tag_ids'] = $params['tag_ids'];
        }
        
        if (isset($params['status'])) {
            $updateData['status'] = (int)$params['status'];
        }

        if (empty($updateData)) {
            throw new ResourceException('没有需要更新的数据');
        }

        // 更新规则
        try {
            $result = $this->segmentRuleRepository->updateOneBy($filter, $updateData);
            return $this->response->array([
                'rule_id' => $result['rule_id'],
                'rule_name' => $result['rule_name'],
                'description' => $result['description'] ?? '',
                'status' => 'success'
            ]);
        } catch (\Exception $e) {
            throw new ResourceException('更新规则失败：' . $e->getMessage());
        }
    }

    /**
     * 查看人群规则详情
     * 路由: GET /member/segment-rule/{rule_id}
     * 路由名称: member.segment.rule.get
     *
     * @param Request $request
     * @param int $rule_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSegmentRule(Request $request, $rule_id)
    {
        // 获取当前用户信息
        $user = app('auth')->user();
        $companyId = $user->get('company_id');
        $operatorType = $user->get('operator_type');
        
        // 构建查询条件
        $filter = [
            'rule_id' => (int)$rule_id,
            'company_id' => $companyId,
        ];

        // 如果是分销商，只能查看自己的规则
        if ($operatorType == 'distributor') {
            $distributorId = $user->get('distributor_id');
            if ($distributorId) {
                $filter['distributor_id'] = $distributorId;
            }
        } else {
            $requestDistributorId = $request->get('distributor_id');
            if ($requestDistributorId !== null) {
                $filter['distributor_id'] = (int)$requestDistributorId;
            }
        }

        // 查询规则
        $rule = $this->segmentRuleRepository->getOneBy($filter);
        
        if (!$rule) {
            throw new ResourceException('规则不存在或无权限查看');
        }

        // 查询关联的标签信息
        $tagList = [];
        $tagIds = $rule['tag_ids'] ?? [];
        if (!empty($tagIds) && is_array($tagIds)) {
            // 使用查询构建器直接查询，避免空数组导致的 SQL 语法错误
            $conn = app('registry')->getConnection('default');
            $qb = $conn->createQueryBuilder();
            
            // 将数组值转换为 literal（项目标准方式）
            array_walk($tagIds, function (&$colVal) use ($qb) {
                $colVal = $qb->expr()->literal($colVal);
            });
            
            $qb->select('tag_id', 'tag_name')
                ->from('members_tags')
                ->where($qb->expr()->eq('company_id', $companyId))
                ->andWhere($qb->expr()->in('tag_id', $tagIds));
            
            $tags = $qb->execute()->fetchAll();
            
            foreach ($tags as $tag) {
                $tagList[] = [
                    'tag_id' => $tag['tag_id'],
                    'tag_name' => $tag['tag_name'],
                ];
            }
        }

        // 格式化返回数据
        return $this->response->array([
            'rule_id' => $rule['rule_id'],
            'rule_name' => $rule['rule_name'],
            'description' => $rule['description'] ?? '',
            'condition' => $rule['rule_config'], // 返回时使用 condition 字段名
            'tag_ids' => $rule['tag_ids'],
            'tags' => $tagList, // 标签详细信息
            'status' => $rule['status'],
            'created' => $rule['created'],
            'updated' => $rule['updated'],
        ]);
    }

    /**
     * 预览人群规则（查询匹配的用户ID）
     * 路由: POST /member/segment-rule/preview
     * 路由名称: member.segment.rule.preview
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function previewSegmentRule(Request $request)
    {
        $params = $request->all();
        
        // 参数验证
        $rules = [
            'condition' => ['required|array', '规则配置必填且必须是数组'],
        ];
        
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        // 验证 condition 结构
        if (empty($params['condition']) || !is_array($params['condition'])) {
            throw new ResourceException('规则配置不能为空');
        }

        // 获取当前用户信息
        $user = app('auth')->user();
        $companyId = $user->get('company_id');
        $operatorType = $user->get('operator_type');
        
        $distributorId = 0;
        if ($operatorType == 'distributor') {
            $distributorId = $user->get('distributor_id') ?: 0;
        } elseif (isset($params['distributor_id'])) {
            $distributorId = (int)$params['distributor_id'];
        }

        try {
            // 调用 Service 查询匹配的用户ID
            $userIds = $this->segmentRuleService->queryMatchedUserIds(
                $params['condition'],
                $companyId,
                $distributorId
            );

            return $this->response->array([
                'matched_count' => count($userIds),
                'user_ids' => $userIds,
            ]);
        } catch (\Exception $e) {
            throw new ResourceException('查询失败：' . $e->getMessage());
        }
    }

    /**
     * 获取规则结构配置（给前端使用）
     * 路由: GET /member/segment-rule/structure
     * 路由名称: member.segment.rule.structure
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRuleStructure(Request $request)
    {
        // 获取当前用户信息
        $user = app('auth')->user();
        $companyId = $user->get('company_id');

        try {
            // 调用 Service 获取规则结构
            $structure = $this->segmentRuleService->getRuleStructure($companyId);

            return $this->response->array($structure);
        } catch (\Exception $e) {
            throw new ResourceException('获取规则结构失败：' . $e->getMessage());
        }
    }
}
