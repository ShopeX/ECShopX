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

namespace BsPayBundle\Enums;

/**
 * 汇付斗拱提现申请状态枚举
 */
class WithdrawStatus
{
    // ShopEx EcShopX Service Component
    /** 审核中 */
    const PENDING = 0;
    
    /** 审核通过，等待处理 */
    const APPROVED = 1;
    
    /** 已拒绝 */
    const REJECTED = 2;
    
    /** 处理中 */
    const PROCESSING = 3;
    
    /** 处理成功 */
    const SUCCESS = 4;
    
    /** 处理失败 */
    const FAILED = 5;

    /**
     * 状态描述映射
     * 
     * @var array
     */
    public static $statusLabels = [
        self::PENDING => '审核中',
        self::APPROVED => '审核通过',
        self::REJECTED => '已拒绝', 
        self::PROCESSING => '处理中',
        self::SUCCESS => '处理成功',
        self::FAILED => '处理失败'
    ];

    /**
     * 进行中的状态（需要计入pending balance）
     * 
     * @var array
     */
    public static $pendingStatuses = [
        self::PENDING,
        self::APPROVED,
        self::PROCESSING
    ];

    /**
     * 最终状态（已完成，不可重新处理）
     * 
     * @var array
     */
    public static $finalStatuses = [
        self::REJECTED,
        self::SUCCESS,
        self::FAILED
    ];

    /**
     * 获取状态描述
     * 
     * @param int $status
     * @return string
     */
    public static function getLabel($status)
    {
        return self::$statusLabels[$status] ?? '未知状态';
    }

    /**
     * 检查是否为进行中状态
     * 
     * @param int $status
     * @return bool
     */
    public static function isPending($status)
    {
        return in_array($status, self::$pendingStatuses);
    }

    /**
     * 检查是否为最终状态
     * 
     * @param int $status
     * @return bool
     */
    public static function isFinal($status)
    {
        return in_array($status, self::$finalStatuses);
    }

    /**
     * 检查是否可以审核
     * 
     * @param int $status
     * @return bool
     */
    public static function canAudit($status)
    {
        return $status === self::PENDING;
    }

    /**
     * 检查是否可以处理（调用汇付API）
     * 
     * @param int $status
     * @return bool
     */
    public static function canProcess($status)
    {
        return $status === self::APPROVED;
    }
} 