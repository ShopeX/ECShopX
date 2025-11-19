<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace MembersBundle\Services;

use MembersBundle\Entities\MemberOperateLog;

class MemberOperateLogService
{
    private $entityRepository;

    /**
     * 操作类型
     */
    public const OPERATE_TYPE_INFO = "info"; // 修改会员信息
    public const OPERATE_TYPE_MOBILE = "mobile"; // 修改手机号
    public const OPERATE_TYPE_GRADE_ID = "grade_id"; // 修改会员等级
    public const OPERATE_TYPE_MAP = [
        self::OPERATE_TYPE_INFO => "修改会员信息",
        self::OPERATE_TYPE_MOBILE => "修改手机号",
        self::OPERATE_TYPE_GRADE_ID => "修改会员等级",
    ];

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(MemberOperateLog::class);
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }
}
