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

namespace OpenapiBundle\Services\Order;

use Carbon\Carbon;
use OpenapiBundle\Services\BaseService;
use OrdersBundle\Entities\NormalOrders;

class OrdersNormalOrdersService extends BaseService
{
    /**
     * 订单类型
     */
    public const ORDER_TYPE_NORMAL = "normal";
    public const ORDER_TYPE_MAP = [
        self::ORDER_TYPE_NORMAL => "普通实体订单"
    ];

    /**
     * 订单类型
     */
    public const TYPE_NORMAL = 0;
    public const TYPE_CROSS_BORDER = 1;
    public const TYPE_MAP = [
        self::TYPE_NORMAL => "普通订单",
        self::TYPE_CROSS_BORDER => "跨境订单"
    ];

    /**
     * 订单状态
     */
    public const ORDER_STATUS_CANCEL = "CANCEL"; // 已取消
    public const ORDER_STATUS_DONE = "DONE"; // 订单完成
    public const ORDER_STATUS_NOTPAY = "NOTPAY"; // 未支付
    public const ORDER_STATUS_PART_PAYMENT = "PART_PAYMENT"; // 部分付款
    public const ORDER_STATUS_PAYED = "PAYED"; // 已支付
    public const ORDER_STATUS_REFUND_SUCCESS = "REFUND_SUCCESS"; // 退款成功
    public const ORDER_STATUS_WAIT_BUYER_CONFIRM = "WAIT_BUYER_CONFIRM"; // 等待用户收货
    public const ORDER_STATUS_WAIT_GROUPS_SUCCESS = "WAIT_GROUPS_SUCCESS"; // 等待拼团成功
    public const ORDER_STATUS_MAP = [
        self::ORDER_STATUS_CANCEL => "已取消",
        self::ORDER_STATUS_DONE => "订单完成",
        self::ORDER_STATUS_NOTPAY => "未支付",
        self::ORDER_STATUS_PART_PAYMENT => "部分付款",
        self::ORDER_STATUS_PAYED => "已支付",
        self::ORDER_STATUS_REFUND_SUCCESS => "退款成功",
        self::ORDER_STATUS_WAIT_BUYER_CONFIRM => "等待用户收货",
        self::ORDER_STATUS_WAIT_GROUPS_SUCCESS => "等待拼团成功",
    ];

    /**
     * 店铺自提状态
     */
    public const ZITI_STATUS_APPROVE = "APPROVE"; // 审核通过,药品自提需要审核
    public const ZITI_STATUS_DONE = "DONE"; // 自提完成
    public const ZITI_STATUS_NOTZITI = "NOTZITI"; // 自提完成
    public const ZITI_STATUS_PENDING = "PENDING"; // 等待自提
    public const ZITI_STATUS_MAP = [
        self::ZITI_STATUS_APPROVE => "审核通过",
        self::ZITI_STATUS_DONE => "自提完成",
        self::ZITI_STATUS_NOTZITI => "自提完成",
        self::ZITI_STATUS_PENDING => "等待自提",
    ];

    /**
     * 取消订单状态
     */
    public const CANCEL_STATUS_NO_APPLY_CANCEL = "NO_APPLY_CANCEL"; // 未申请
    public const CANCEL_STATUS_WAIT_PROCESS = "WAIT_PROCESS"; // 等待审核
    public const CANCEL_STATUS_REFUND_PROCESS = "REFUND_PROCESS"; // 退款处理
    public const CANCEL_STATUS_SUCCESS = "SUCCESS"; // 取消成功
    public const CANCEL_STATUS_FAILS = "FAILS"; // 取消失败
    public const CANCEL_STATUS_MAP = [
        self::CANCEL_STATUS_NO_APPLY_CANCEL => "未申请",
        self::CANCEL_STATUS_WAIT_PROCESS => "等待审核",
        self::CANCEL_STATUS_REFUND_PROCESS => "退款处理",
        self::CANCEL_STATUS_SUCCESS => "取消成功",
        self::CANCEL_STATUS_FAILS => "取消失败",
    ];

    /**
     * 支付状态
     */
    public const PAY_STATUS_NOTPAY = "NOTPAY"; // 未支付
    public const PAY_STATUS_PAYED = "PAYED"; // 已支付
    public const PAY_STATUS_ADVANCE_PAY = "ADVANCE_PAY"; // 预付款完成
    public const PAY_STATUS_TAIL_PAY = "TAIL_PAY"; // 支付尾款中
    public const PAY_STATUS_MAP = [
        self::PAY_STATUS_NOTPAY => "未支付",
        self::PAY_STATUS_PAYED => "已支付",
        self::PAY_STATUS_ADVANCE_PAY => "预付款完成",
        self::PAY_STATUS_TAIL_PAY => "支付尾款中",
    ];

    /**
     * 跨境订单审核状态
     */
    public const AUDIT_STATUS_APPROVED = "approved"; // 成功
    public const AUDIT_STATUS_PROCESSING = "processing"; // 审核中
    public const AUDIT_STATUS_REJECTED = "rejected"; // 审核拒绝
    public const AUDIT_STATUS_MAP = [
        self::AUDIT_STATUS_APPROVED => "成功",
        self::AUDIT_STATUS_PROCESSING => "审核中",
        self::AUDIT_STATUS_REJECTED => "审核拒绝",
    ];

    /**
     * 订单种类。
     */
    public const ORDER_CLASS_BARGAIN = "bargain"; // 助力订单
    public const ORDER_CLASS_COMMUNITY = "community"; // 社区活动订单
    public const ORDER_CLASS_CROSSBORDER = "crossborder"; // 跨境订单
    public const ORDER_CLASS_GROUPS = "groups"; // 拼团订单
    public const ORDER_CLASS_NORMAL = "normal"; // 普通订单
    public const ORDER_CLASS_POINTSMALL = "pointsmall"; // 积分商城
    public const ORDER_CLASS_SECKILL = "seckill"; // 秒杀订单
    public const ORDER_CLASS_SHOPGUIDE = "shopguide"; // 导购订单
    public const ORDER_CLASS_MAP = [
        self::ORDER_CLASS_BARGAIN => "助力订单",
        self::ORDER_CLASS_COMMUNITY => "社区活动订单",
        self::ORDER_CLASS_CROSSBORDER => "跨境订单",
        self::ORDER_CLASS_GROUPS => "拼团订单",
        self::ORDER_CLASS_NORMAL => "普通订单",
        self::ORDER_CLASS_POINTSMALL => "积分商城",
        self::ORDER_CLASS_SECKILL => "秒杀订单",
        self::ORDER_CLASS_SHOPGUIDE => "导购订单",
    ];

    public function getEntityClass(): string
    {
        return NormalOrders::class;
    }

    public function list(array $filter, int $page = 1, int $pageSize = 10, array $orderBy = [], string $cols = "*", bool $needCountSql = true): array
    {
        // 计算偏移量
        if ($page < 0) {
            $pageSize = 0;
            $offset = 0;
        } else {
            $offset = ($page - 1) * $pageSize;
        }
        // 获取分页内容
        $list = $this->getRepository()->getList($filter, $offset, $pageSize, $orderBy, $cols);
        // 判断是否要统计总数量
        $count = $needCountSql ? (int)$this->getRepository()->count($filter) : 0;
        $result = ["list" => $list, "total_count" => $count];
        $this->handlerListReturnFormat($result, $page, $pageSize);
        return $result;
    }

    protected function handleData(array &$data)
    {
        if (isset($data["create_time"])) {
            $data["create_time_desc"] = Carbon::createFromTimestamp($data["create_time"])->toDateTimeString();
        }
        foreach ([
                     "order_class" => self::ORDER_CLASS_MAP,
                     "order_type" => self::ORDER_TYPE_MAP,
                     "type" => self::TYPE_MAP,
                     "order_status" => self::ORDER_STATUS_MAP,
                     "ziti_status" => self::ZITI_STATUS_MAP,
                     "cancel_status" => self::CANCEL_STATUS_MAP,
                     "pay_status" => self::PAY_STATUS_MAP,
                     "audit_status" => self::AUDIT_STATUS_MAP
                 ] as $field => $map) {
            if (!isset($data[$field])) {
                continue;
            }
            $data[$field] = (string)$field;
            //$data[sprintf("%s_desc", $field)] = $map[$data[$field]] ?? "";
        }
    }
}
