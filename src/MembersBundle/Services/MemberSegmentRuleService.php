<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace MembersBundle\Services;

use Dingo\Api\Exception\ResourceException;
use Exception;
use KaquanBundle\Entities\MemberCardGrade;
use GoodsBundle\Services\MultiLang\MagicLangTrait;

class MemberSegmentRuleService
{
    use MagicLangTrait;
    /**
     * 根据规则配置查询匹配的用户ID
     *
     * @param array $ruleConfig 规则配置（层级结构）
     * @param int $companyId 公司ID
     * @param int $distributorId 分销商ID
     * @return array 匹配的用户ID数组
     */
    public function queryMatchedUserIds(array $ruleConfig, int $companyId, int $distributorId = 0): array
    {
        if (empty($ruleConfig)) {
            return [];
        }

        $topTypeResults = [];

        // 遍历每个顶层类型
        foreach ($ruleConfig as $topTypeConfig) {
            $type = $topTypeConfig['type'] ?? '';
            $totalCondition = $topTypeConfig['total_condition'] ?? [];
            $subConditions = $topTypeConfig['sub'] ?? [];

            if (empty($type) || empty($subConditions)) {
                continue;
            }

            // 根据顶层类型调用对应的查询方法
            $userIds = [];
            switch ($type) {
                case 'member':
                    $userIds = $this->queryMemberType($subConditions, $totalCondition, $companyId, $distributorId);
                    break;
                case 'order':
                    $userIds = $this->queryOrderType($subConditions, $totalCondition, $companyId, $distributorId);
                    break;
                default:
                    throw new ResourceException("不支持的规则类型: {$type}");
            }

            $topTypeResults[] = $userIds;
        }

        // 所有顶层类型的结果取交集（AND逻辑）
        if (empty($topTypeResults)) {
            return [];
        }

        $result = $topTypeResults[0];
        for ($i = 1; $i < count($topTypeResults); $i++) {
            $result = array_intersect($result, $topTypeResults[$i]);
        }

        return array_values(array_unique($result));
    }

    /**
     * 查询 member 类型
     *
     * @param array $subConditions 子条件数组
     * @param array $totalCondition 总条件
     * @param int $companyId 公司ID
     * @param int $distributorId 分销商ID
     * @return array 用户ID数组
     */
    private function queryMemberType(array $subConditions, array $totalCondition, int $companyId, int $distributorId): array
    {
        $allUserIds = null; // 初始化为null，表示还没有处理任何条件

        // 遍历子条件，每个子条件的结果取交集（AND逻辑）
        foreach ($subConditions as $subCondition) {
            $subType = $subCondition['type'] ?? '';
            $params = $subCondition['params'] ?? [];

            $userIds = [];
            switch ($subType) {
                case 'birthday':
                    $userIds = $this->queryBirthday($params, $companyId, $distributorId);
                    break;
                case 'grade':
                    $userIds = $this->queryGrade($params, $companyId, $distributorId);
                    break;
                case 'point':
                    $userIds = $this->queryPoint($params, $companyId, $distributorId);
                    break;
                default:
                    continue 2; // 跳过不支持的子类型
            }

            // 第一个条件：直接赋值
            if ($allUserIds === null) {
                $allUserIds = $userIds;
            } else {
                // 后续条件：与之前的结果取交集
                if (empty($userIds)) {
                    // 如果当前条件没有结果，交集为空
                    $allUserIds = [];
                } else {
                    // 取交集
                    $allUserIds = array_intersect($allUserIds, $userIds);
                }
            }
        }

        // 如果没有处理任何条件，返回空数组
        if ($allUserIds === null) {
            return [];
        }

        return array_values(array_unique($allUserIds));
    }

    /**
     * 查询 order 类型
     *
     * @param array $subConditions 子条件数组
     * @param array $totalCondition 总条件（订单时间范围）
     * @param int $companyId 公司ID
     * @param int $distributorId 分销商ID
     * @return array 用户ID数组
     */
    private function queryOrderType(array $subConditions, array $totalCondition, int $companyId, int $distributorId): array
    {
        // 提取总条件中的时间范围
        $timeRange = [];
        if (!empty($totalCondition) && isset($totalCondition[0])) {
            $totalConditionItem = $totalCondition[0];
            if (isset($totalConditionItem['condition_type']) && $totalConditionItem['condition_type'] === 'timeRange') {
                $timeRange = $totalConditionItem['params'] ?? [];
            }
        }

        if (empty($timeRange) || count($timeRange) < 2) {
            throw new ResourceException('订单类型必须设置时间范围总条件');
        }

        $startTime = (int)$timeRange[0];
        $endTime = (int)$timeRange[1];

        $allUserIds = null; // 初始化为null，表示还没有处理任何条件

        // 遍历子条件，每个子条件的结果取交集（AND逻辑）
        foreach ($subConditions as $subCondition) {
            $subType = $subCondition['type'] ?? '';
            $params = $subCondition['params'] ?? [];

            $userIds = [];
            switch ($subType) {
                case 'perOrder':
                    $userIds = $this->queryPerOrder($params, $startTime, $endTime, $companyId, $distributorId);
                    break;
                case 'sumaryOrder':
                    $userIds = $this->querySumaryOrder($params, $startTime, $endTime, $companyId, $distributorId);
                    break;
                case 'orderItem':
                    $userIds = $this->queryOrderItem($params, $startTime, $endTime, $companyId, $distributorId);
                    break;
                case 'hasOrder':
                    $userIds = $this->queryHasOrder($params, $startTime, $endTime, $companyId, $distributorId);
                    break;
                default:
                    continue 2; // 跳过不支持的子类型
            }

            // 第一个条件：直接赋值
            if ($allUserIds === null) {
                $allUserIds = $userIds;
            } else {
                // 后续条件：与之前的结果取交集
                if (empty($userIds)) {
                    // 如果当前条件没有结果，交集为空
                    $allUserIds = [];
                } else {
                    // 取交集
                    $allUserIds = array_intersect($allUserIds, $userIds);
                }
            }
        }

        // 如果没有处理任何条件，返回空数组
        if ($allUserIds === null) {
            return [];
        }

        return array_values(array_unique($allUserIds));
    }

    /**
     * 查询生日条件
     * params: [start_timestamp, end_timestamp]
     *
     * @param array $params 参数：[开始时间戳, 结束时间戳]
     * @param int $companyId 公司ID
     * @param int $distributorId 分销商ID
     * @return array 用户ID数组
     */
    private function queryBirthday(array $params, int $companyId, int $distributorId): array
    {
        if (count($params) < 2) {
            return [];
        }

        $startTimestamp = (int)$params[0];
        $endTimestamp = (int)$params[1];

        // 将时间戳转换为日期格式（仅取月日）
        $startDate = date('m-d', $startTimestamp);
        $endDate = date('m-d', $endTimestamp);

        $conn = app('registry')->getConnection('default');

        $sql = "
            SELECT DISTINCT mi.user_id
            FROM members_info mi
            INNER JOIN members m ON mi.user_id = m.user_id
            WHERE mi.company_id = :company_id
              AND m.company_id = :company_id2
        ";

        $bindParams = [
            'company_id' => $companyId,
            'company_id2' => $companyId,
        ];

        // 如果有分销商ID，需要关联分销商表
        if ($distributorId > 0) {
            $sql .= " AND EXISTS (
                SELECT 1 FROM distribution_distributor_user ddu
                WHERE ddu.user_id = m.user_id AND ddu.distributor_id = :distributor_id
            )";
            $bindParams['distributor_id'] = $distributorId;
        }

        // 生日范围查询（仅比较月日）
        // birthday 字段格式可能是 "2020-09-10" 或 "09-10"
        if ($startDate <= $endDate) {
            // 正常范围，如 01-01 到 12-31
            $sql .= " AND (
                CASE 
                    WHEN mi.birthday LIKE '%-%-%' THEN DATE_FORMAT(STR_TO_DATE(mi.birthday, '%Y-%m-%d'), '%m-%d')
                    ELSE mi.birthday
                END BETWEEN :start_date AND :end_date
            )";
        } else {
            // 跨年范围，如 12-01 到 01-31
            $sql .= " AND (
                CASE 
                    WHEN mi.birthday LIKE '%-%-%' THEN DATE_FORMAT(STR_TO_DATE(mi.birthday, '%Y-%m-%d'), '%m-%d')
                    ELSE mi.birthday
                END >= :start_date
                OR CASE 
                    WHEN mi.birthday LIKE '%-%-%' THEN DATE_FORMAT(STR_TO_DATE(mi.birthday, '%Y-%m-%d'), '%m-%d')
                    ELSE mi.birthday
                END <= :end_date
            )";
        }

        $bindParams['start_date'] = $startDate;
        $bindParams['end_date'] = $endDate;

        $stmt = $conn->prepare($sql);
        foreach ($bindParams as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $result = $stmt->executeQuery()->fetchAllAssociative();
        return array_column($result, 'user_id');
    }

    /**
     * 查询会员等级条件
     * params: [grade_id1, grade_id2, ...]
     *
     * @param array $params 参数：等级ID数组
     * @param int $companyId 公司ID
     * @param int $distributorId 分销商ID
     * @return array 用户ID数组
     */
    private function queryGrade(array $params, int $companyId, int $distributorId): array
    {
        if (empty($params) || !is_array($params)) {
            return [];
        }

        $gradeIds = array_map('intval', $params);

        $conn = app('registry')->getConnection('default');

        $sql = "
            SELECT DISTINCT m.user_id
            FROM members m
            WHERE m.company_id = :company_id
              AND m.grade_id IN (:grade_ids)
        ";

        $bindParams = [
            'company_id' => $companyId,
        ];

        // 如果有分销商ID，需要关联分销商表
        if ($distributorId > 0) {
            $sql .= " AND EXISTS (
                SELECT 1 FROM distribution_distributor_user ddu
                WHERE ddu.user_id = m.user_id AND ddu.distributor_id = :distributor_id
            )";
            $bindParams['distributor_id'] = $distributorId;
        }

        $placeholders = [];
        foreach ($gradeIds as $index => $gradeId) {
            $key = 'grade_id_' . $index;
            $placeholders[] = ':' . $key;
            $bindParams[$key] = $gradeId;
        }
        $sql = str_replace(':grade_ids', implode(', ', $placeholders), $sql);

        $stmt = $conn->prepare($sql);
        foreach ($bindParams as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $result = $stmt->executeQuery()->fetchAllAssociative();
        return array_column($result, 'user_id');
    }

    /**
     * 查询积分条件
     * params: [min_point, max_point]
     *
     * @param array $params 参数：[最小积分, 最大积分]
     * @param int $companyId 公司ID
     * @param int $distributorId 分销商ID
     * @return array 用户ID数组
     */
    private function queryPoint(array $params, int $companyId, int $distributorId): array
    {
        if (count($params) < 2) {
            return [];
        }

        $minPoint = (int)$params[0];
        $maxPoint = (int)$params[1];

        $conn = app('registry')->getConnection('default');

        // 直接拼接SQL，方便调试
        $companyId = (int)$companyId;
        $minPoint = (int)$minPoint;
        $maxPoint = (int)$maxPoint;

        $sql = "
            SELECT DISTINCT pm.user_id
            FROM point_member pm
            INNER JOIN members m ON pm.user_id = m.user_id AND pm.company_id = m.company_id
            WHERE pm.company_id = {$companyId}
              AND pm.point >= {$minPoint}
              AND pm.point <= {$maxPoint}
        ";

        if ($distributorId > 0) {
            $distributorId = (int)$distributorId;
            $sql .= " AND EXISTS (
                SELECT 1 FROM distribution_distributor_user ddu
                WHERE ddu.user_id = m.user_id AND ddu.distributor_id = {$distributorId}
            )";
        }

        // 记录完整SQL，方便调试和直接执行
        app('log')->debug('queryPoint SQL (原生拼接)', [
            'sql' => $sql,
        ]);

        $result = $conn->fetchAllAssociative($sql);
        return array_column($result, 'user_id');
    }

    /**
     * 查询单笔金额条件
     * params: [min_amount, max_amount] (单位：分)
     *
     * @param array $params 参数：[最小金额, 最大金额]（单位：分）
     * @param int $startTime 开始时间戳
     * @param int $endTime 结束时间戳
     * @param int $companyId 公司ID
     * @param int $distributorId 分销商ID
     * @return array 用户ID数组
     */
    private function queryPerOrder(array $params, int $startTime, int $endTime, int $companyId, int $distributorId): array
    {
        if (count($params) < 2) {
            return [];
        }

        $minAmount = (int)$params[0] * 100; // 单位：分
        $maxAmount = (int)$params[1] * 100; // 单位：分

        $conn = app('registry')->getConnection('default');

        // 直接拼接SQL，方便调试
        $companyId = (int)$companyId;
        $startTime = (int)$startTime;
        $endTime = (int)$endTime;
        $minAmount = (int)$minAmount;
        $maxAmount = (int)$maxAmount;

        $sql = "
            SELECT DISTINCT o.user_id
            FROM orders_normal_orders o
            WHERE o.company_id = {$companyId}
              AND o.create_time >= {$startTime}
              AND o.create_time <= {$endTime}
              AND o.total_fee >= {$minAmount}
              AND o.total_fee <= {$maxAmount}
              AND o.pay_status = 'PAYED'
        ";

        if ($distributorId > 0) {
            $distributorId = (int)$distributorId;
            $sql .= " AND o.distributor_id = {$distributorId}";
        }

        // 记录完整SQL，方便调试和直接执行
        app('log')->debug('queryPerOrder SQL (原生拼接)', [
            'sql' => $sql,
            
        ]);

        $result = $conn->fetchAllAssociative($sql);
        return array_column($result, 'user_id');
    }

    /**
     * 查询累计金额条件
     * params: [min_amount, max_amount] (单位：分)
     *
     * @param array $params 参数：[最小金额, 最大金额]（单位：分）
     * @param int $startTime 开始时间戳
     * @param int $endTime 结束时间戳
     * @param int $companyId 公司ID
     * @param int $distributorId 分销商ID
     * @return array 用户ID数组
     */
    private function querySumaryOrder(array $params, int $startTime, int $endTime, int $companyId, int $distributorId): array
    {
        if (count($params) < 2) {
            return [];
        }

        $minAmount = (int)$params[0] * 100; // 单位：分
        $maxAmount = (int)$params[1] * 100; // 单位：分

        $conn = app('registry')->getConnection('default');

        $sql = "
            SELECT o.user_id, SUM(CAST(o.total_fee AS UNSIGNED)) as total_amount
            FROM orders_normal_orders o
            WHERE o.company_id = :company_id
              AND o.create_time >= :start_time
              AND o.create_time <= :end_time
              AND o.pay_status = 'PAYED'
        ";

        $bindParams = [
            'company_id' => $companyId,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ];

        if ($distributorId > 0) {
            $sql .= " AND o.distributor_id = :distributor_id";
            $bindParams['distributor_id'] = $distributorId;
        }

        $sql .= " GROUP BY o.user_id
                  HAVING total_amount >= :min_amount AND total_amount <= :max_amount";

        $bindParams['min_amount'] = $minAmount;
        $bindParams['max_amount'] = $maxAmount;

        $stmt = $conn->prepare($sql);
        foreach ($bindParams as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $result = $stmt->executeQuery()->fetchAllAssociative();
        return array_column($result, 'user_id');
    }

    /**
     * 查询下单商品条件
     * params: [item_id1, item_id2, ...]
     *
     * @param array $params 参数：商品ID数组
     * @param int $startTime 开始时间戳
     * @param int $endTime 结束时间戳
     * @param int $companyId 公司ID
     * @param int $distributorId 分销商ID
     * @return array 用户ID数组
     */
    private function queryOrderItem(array $params, int $startTime, int $endTime, int $companyId, int $distributorId): array
    {
        if (empty($params) || !is_array($params)) {
            return [];
        }

        $itemIds = array_map('intval', $params);

        $conn = app('registry')->getConnection('default');

        $sql = "
            SELECT DISTINCT o.user_id
            FROM orders_normal_orders o
            INNER JOIN orders_normal_orders_items oi ON o.order_id = oi.order_id
            WHERE o.company_id = :company_id
              AND o.create_time >= :start_time
              AND o.create_time <= :end_time
              AND o.pay_status = 'PAYED'
        ";

        $bindParams = [
            'company_id' => $companyId,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ];

        if ($distributorId > 0) {
            $sql .= " AND o.distributor_id = :distributor_id";
            $bindParams['distributor_id'] = $distributorId;
        }

        $placeholders = [];
        foreach ($itemIds as $index => $itemId) {
            $key = 'item_id_' . $index;
            $placeholders[] = ':' . $key;
            $bindParams[$key] = $itemId;
        }
        $sql .= " AND oi.item_id IN (" . implode(', ', $placeholders) . ")";

        $stmt = $conn->prepare($sql);
        foreach ($bindParams as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $result = $stmt->executeQuery()->fetchAllAssociative();
        return array_column($result, 'user_id');
    }

    /**
     * 查询是否有下单条件
     * params: [1] 表示有下单，[0] 表示没下单
     *
     * @param array $params 参数：[1] 有下单，[0] 没下单
     * @param int $startTime 开始时间戳
     * @param int $endTime 结束时间戳
     * @param int $companyId 公司ID
     * @param int $distributorId 分销商ID
     * @return array 用户ID数组
     */
    private function queryHasOrder(array $params, int $startTime, int $endTime, int $companyId, int $distributorId): array
    {
        if (empty($params) || !isset($params[0])) {
            return [];
        }

        $hasOrder = (int)$params[0]; // 1=有下单，0=没下单

        $conn = app('registry')->getConnection('default');

        if ($hasOrder == 1) {
            // 有下单
            $sql = "
                SELECT DISTINCT o.user_id
                FROM orders_normal_orders o
                WHERE o.company_id = :company_id
                  AND o.create_time >= :start_time
                  AND o.create_time <= :end_time
                  AND o.pay_status = 'PAYED'
            ";

            $bindParams = [
                'company_id' => $companyId,
                'start_time' => $startTime,
                'end_time' => $endTime,
            ];

            if ($distributorId > 0) {
                $sql .= " AND o.distributor_id = :distributor_id";
                $bindParams['distributor_id'] = $distributorId;
            }
        } else {
            // 没下单：所有会员减去有下单的会员
            $sql = "
                SELECT DISTINCT m.user_id
                FROM members m
                WHERE m.company_id = :company_id
                  AND NOT EXISTS (
                      SELECT 1 FROM orders_normal_orders o
                      WHERE o.user_id = m.user_id
                        AND o.company_id = :company_id2
                        AND o.create_time >= :start_time
                        AND o.create_time <= :end_time
                        AND o.pay_status = 'PAYED'
            ";

            $bindParams = [
                'company_id' => $companyId,
                'company_id2' => $companyId,
                'start_time' => $startTime,
                'end_time' => $endTime,
            ];

            if ($distributorId > 0) {
                $sql .= " AND o.distributor_id = :distributor_id";
                $bindParams['distributor_id'] = $distributorId;
            }

            $sql .= " )";

            if ($distributorId > 0) {
                $sql .= " AND EXISTS (
                    SELECT 1 FROM distribution_distributor_user ddu
                    WHERE ddu.user_id = m.user_id AND ddu.distributor_id = :distributor_id2
                )";
                $bindParams['distributor_id2'] = $distributorId;
            }
        }

        $stmt = $conn->prepare($sql);
        foreach ($bindParams as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $result = $stmt->executeQuery()->fetchAllAssociative();
        return array_column($result, 'user_id');
    }

    /**
     * 获取规则结构配置（给前端使用）
     * 
     * @param int $companyId 公司ID
     * @return array 规则结构配置
     */
    public function getRuleStructure(int $companyId): array
    {
        // 查询会员等级列表
        $gradeList = $this->getGradeList($companyId);
        // 格式化为 {grade_id: 1, grade_name: "xxx"} 格式的数组
        $gradeMapValue = array_map(function($grade) {
            return [
                'id' => $grade['grade_id'],
                'name' => $grade['grade_name']
            ];
        }, $gradeList);

        return [
            [
                'type' => 'member',
                'total_condition' => [],
                'sub' => [
                    [
                        'type' => 'birthday',
                        'lebel' => '生日',
                        'condition_type' => 'timeRange'
                    ],
                    [
                        'type' => 'grade',
                        'lebel' => '会员等级',
                        'condition_type' => 'mapping',
                        'map_value' => $gradeMapValue
                    ],
                    [
                        'type' => 'point',
                        'lebel' => '会员积分',
                        'condition_type' => 'number_rang',
                        'unit' => '积分'
                    ]
                ]
            ],
            [
                'type' => 'order',
                'total_condition' => [
                    [
                        'condition_type' => 'timeRange',
                        'lebel' => '订单时间范围',
                        'unit' => ''
                    ]
                ],
                'sub' => [
                    [
                        'type' => 'perOrder',
                        'lebel' => '单笔金额',
                        'condition_type' => 'number_rang',
                        'unit' => '元'
                    ],
                    [
                        'type' => 'sumaryOrder',
                        'lebel' => '累计金额',
                        'condition_type' => 'number_rang',
                        'map_value' => '元'
                    ],
                    [
                        'type' => 'orderItem',
                        'lebel' => '下单商品',
                        'condition_type' => 'number_items',
                        'map_value' => ''
                    ],
                    [
                        'type' => 'hasOrder',
                        'lebel' => '商城有下单',
                        'condition_type' => 'radio',
                        'map_value'=>[
                            ['id'=>0,'name'=>'否','is_default'=>1],
                            ['id'=>1,'name'=>'是','is_default'=>0]
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * 获取会员等级列表
     * 
     * @param int $companyId 公司ID
     * @return array 会员等级列表，包含 grade_id 和 grade_name
     */
    private function getGradeList(int $companyId): array
    {
        $memberCardGradeRepository = app('registry')->getManager('default')->getRepository(MemberCardGrade::class);
        
        // 使用 Repository 的方法获取等级列表（会自动处理多语言）
        $gradeList = $memberCardGradeRepository->getListByCompanyId($companyId);
        
        // 提取 grade_id 和 grade_name
        $result = [];
        foreach ($gradeList as $grade) {
            $result[] = [
                'grade_id' => $grade['grade_id'] ?? $grade['gradeId'] ?? 0,
                'grade_name' => $grade['grade_name'] ?? $grade['gradeName'] ?? ''
            ];
        }
        
        return $result;
    }

    /**
     * 获取人群圈选规则列表
     * 
     * @param array $params 查询参数
     * @return array 返回包含分页信息的规则列表
     */
    public function getSegmentRuleList(array $params): array
    {
        $companyId = $params['company_id'] ?? 0;
        $operatorType = $params['operator_type'] ?? '';
        $distributorId = $params['distributor_id'] ?? 0;
        
        // 获取分页参数
        $page = (int)($params['page'] ?? 1);
        $pageSize = (int)($params['page_size'] ?? 20);
        
        // 验证分页参数
        if ($page < 1) {
            $page = 1;
        }
        if ($pageSize < 1 || $pageSize > 100) {
            $pageSize = 20;
        }
        
        // 构建查询条件
        $filter = [
            'company_id' => $companyId,
        ];

        // 如果是分销商，只能查看自己的规则
        if ($operatorType == 'distributor') {
            if ($distributorId) {
                $filter['distributor_id'] = $distributorId;
            }
        } else {
            // 如果传了 distributor_id，按 distributor_id 过滤
            $requestDistributorId = $params['request_distributor_id'] ?? null;
            if ($requestDistributorId !== null && $requestDistributorId !== '') {
                $filter['distributor_id'] = (int)$requestDistributorId;
            }
        }

        // 状态过滤
        if (isset($params['status']) && $params['status'] !== null && $params['status'] !== '') {
            $filter['status'] = (int)$params['status'];
        }

        // 规则名称模糊搜索（支持 rule_name 或 tag_name 参数）
        if (!empty($params['tag_name'])) {
            // 如果传了 tag_name，用它来搜索 rule_name
            $filter['rule_name|contains'] = $params['tag_name'];
        } elseif (!empty($params['rule_name'])) {
            // 如果传了 rule_name，用它来搜索 rule_name
            $filter['rule_name|contains'] = $params['rule_name'];
        }

        // 创建时间范围查询
        if (!empty($params['created_start']) || !empty($params['created_end'])) {
            if (!empty($params['created_start'])) {
                // 将时间字符串转换为时间戳
                $createdStart = is_numeric($params['created_start']) ? (int)$params['created_start'] : strtotime($params['created_start']);
                if ($createdStart) {
                    $filter['created|gte'] = $createdStart;
                }
            }
            if (!empty($params['created_end'])) {
                // 将时间字符串转换为时间戳
                $createdEnd = is_numeric($params['created_end']) ? (int)$params['created_end'] : strtotime($params['created_end']);
                if ($createdEnd) {
                    // 如果是日期格式，设置为当天的23:59:59
                    if (!is_numeric($params['created_end'])) {
                        $createdEnd = strtotime(date('Y-m-d', $createdEnd) . ' 23:59:59');
                    }
                    $filter['created|lte'] = $createdEnd;
                }
            }
        }

        // 排序：按创建时间倒序
        $orderBy = ['created' => 'DESC'];

        // 获取 Repository
        $segmentRuleRepository = app('registry')->getManager('default')->getRepository(\MembersBundle\Entities\MemberSegmentRule::class);
        
        // 查询列表
        $result = $segmentRuleRepository->lists($filter, '*', $page, $pageSize, $orderBy);

        // 格式化返回数据
        $list = [];
        foreach ($result['list'] as $item) {
            $list[] = [
                'rule_id' => $item['rule_id'],
                'rule_name' => $item['rule_name'],
                'description' => $item['description'] ?? '',
                'status' => $item['status'],
                'distributor_id' => $item['distributor_id'],
                'created' => date('Y-m-d H:i:s', $item['created']),
                'updated' => $item['updated'] ? date('Y-m-d H:i:s', $item['updated']) : '',
            ];
        }

        return [
            'total_count' => $result['total_count'],
            'page' => $page,
            'page_size' => $pageSize,
            'list' => $list,
        ];
    }
}
