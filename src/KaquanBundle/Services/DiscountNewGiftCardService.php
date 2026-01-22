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

namespace KaquanBundle\Services;

use Dingo\Api\Exception\ResourceException;
use KaquanBundle\Entities\DiscountCards;
use KaquanBundle\Entities\RelItems;
use KaquanBundle\Entities\RelMemberTags;
use KaquanBundle\Entities\UserDiscount;
use KaquanBundle\Events\CouponAddEvent;
use KaquanBundle\Events\CouponEditEvent;
use KaquanBundle\Interfaces\KaquanInterface;
use KaquanBundle\Repositories\DiscountCardsRepository;
use KaquanBundle\Repositories\DiscountRelItemsRepository;
use KaquanBundle\Repositories\DiscountRelMemberTagsRepository;

class DiscountNewGiftCardService implements KaquanInterface
{
    /** @var DiscountCardsRepository */
    public $discountCardRepository;
    /** @var DiscountRelItemsRepository */
    public $relItemsRepository;
    /** @var DiscountRelMemberTagsRepository */
    public $relMemberTagRepository;

    public const DATE_TYPE_LONG = 'DATE_TYPE_LONG';
    public const DATE_TYPE_SHORT = 'DATE_TYPE_SHORT';

    public const USE_BOUND_ALL_ITEMS = 0;
    public const USE_BOUND_ASSIGN_ITEMS = 1;

    public const STATUS_NORMAL = 0;
    public const STATUS_STOP = 1;
    public const STATUS_CLOSE = 2;
    public const STATUS_INIT = 10;

    public function __construct()
    {
        $this->discountCardRepository = app('registry')->getManager('default')->getRepository(DiscountCards::class);
        $this->relItemsRepository = app('registry')->getManager('default')->getRepository(RelItems::class);
        $this->relMemberTagRepository = app('registry')->getManager('default')->getRepository(RelMemberTags::class);
    }

    public function createKaquan(array $dataInfo, $appId = '')
    {
        $this->__setParams($dataInfo, true);
        // ..
        if (isset($dataInfo['distributor_id'])) {
            $dataInfo['use_all_shops'] = 'false';
        } else {
            $dataInfo['use_all_shops'] = 'true';
        }
        $dataInfo['kq_status'] = self::STATUS_INIT;
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $result = $this->discountCardRepository->create($dataInfo);

            if (isset($dataInfo['items'])) {
                $relItemInsertData = $this->parseRelItemInsertData($dataInfo['items'], $result['card_id'], $result['company_id']);
                // 每100个分批导入
                foreach (array_chunk($relItemInsertData, 100) as $insertData) {
                    $this->relItemsRepository->createQuick($insertData);
                }
            }
            $companyId = $result['company_id'];
            $cardId = $result['card_id'];
            if (isset($dataInfo['user_tag_ids'])) {
                $relTagData = array_map(function ($tagId) use ($companyId, $cardId) {
                    return [
                        'company_id' => $companyId,
                        'card_id' => $cardId,
                        'tag_id' => $tagId,
                    ];
                }, $dataInfo['user_tag_ids']);
                $this->relMemberTagRepository->createQuick($relTagData);
            }
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }

        $eventData = [
            'card_id' => $result['card_id'],
            'company_id' => $result['company_id']
        ];
        event(new CouponAddEvent($eventData));
        return $result;
    }

    public function deleteKaquan($filter, $appId = '')
    {
    }

    /**
     * @param $filter
     * @return array|void
     */
    public function getKaquanDetail($filter)
    {
    }

    public function getKaquanList($offset, $count, $filter = [])
    {
        // fe10e2f6 module
    }

    public function updateKaquan($dataInfo, $appId = '')
    {
        $this->__setParams($dataInfo);
        $filter['card_id'] = $dataInfo['card_id'];
        $filter['company_id'] = $dataInfo['company_id'];

        $detail = $this->discountCardRepository->getInfo($filter);
        if (!$detail) {
            throw new ResourceException(trans('KaquanBundle.coupon_invalid'));
        }
        if ($detail['card_type'] != 'new_gift') {
            throw new ResourceException(trans('KaquanBundle.card_type_error'));
        }

        if (isset($dataInfo['kq_status'])) {
            if ($detail['kq_status'] == self::STATUS_CLOSE && $dataInfo['kq_status'] != self::STATUS_CLOSE) {
                throw new ResourceException(trans('KaquanBundle.paused_card_cannot_reactivate'));
            }
        }

        if (isset($dataInfo['quantity'])) {
            $dataInfo['quantity'] = abs($dataInfo['quantity']);
            $cardRelatedRepository = app('registry')->getManager('default')->getRepository(UserDiscount::class);
            $receiveNum = $cardRelatedRepository->getTotalNum($filter);
            $usableNum = $detail['quantity'] - $receiveNum;
            // lock ?
            if ($dataInfo['quantity'] < $usableNum) {
                throw new ResourceException(trans('KaquanBundle.reduce_quantity_cannot_less_than_remaining', ['usableNum' => $usableNum]));
            }
        }

        if ($dataInfo['grade_ids'] != [] || $dataInfo['vip_grade_ids'] != []) {
            if ($detail['grade_ids'] == [] && $detail['vip_grade_ids'] == []) {
                // 所有会员通用, 不允许再修改范围
                if (($dataInfo['grade_ids'] ?? false) || ($dataInfo['vip_grade_ids'] ?? false)) {
                    throw new ResourceException(trans('KaquanBundle.members_only_expand_not_reduce_scope'));
                }
            }
            if (isset($dataInfo['grade_ids'])) {
                foreach ($detail['grade_ids'] as $gid) {
                    if (!in_array($gid, $dataInfo['grade_ids'])) {
                        throw new ResourceException(trans('KaquanBundle.members_only_expand_not_reduce_scope'));
                    }
                }
            }
            if (isset($dataInfo['vip_grade_ids'])) {
                foreach ($detail['vip_grade_ids'] as $vgid) {
                    if (!in_array($vgid, $dataInfo['vip_grade_ids'])) {
                        throw new ResourceException(trans('KaquanBundle.members_only_expand_not_reduce_scope'));
                    }
                }
            }
        }

        if ($detail['date_type'] == DiscountNewGiftCardService::DATE_TYPE_LONG) {
            $isActive = $detail['send_begin_time'] + 3600 * 24 * $detail['begin_date'] < time();
            $hour = $detail['fixed_term'] * 24;
        } else {
            $isActive = $detail['begin_time'] < time();
            $hour = ($detail['end_date'] - $detail['begin_date']) / 3600;
        }

        if ($detail['kq_status'] != self::STATUS_INIT && $isActive) {
            //throw new ResourceException('活动生效后无法修改店铺商品');
            unset($dataInfo['items']);
            unset($dataInfo['distributor_id']);
        }

        if (isset($dataInfo['lock_time']) && $dataInfo['lock_time'] > $hour) {
            throw new ResourceException(trans('KaquanBundle.lock_time_cannot_exceed_usage_time'));
        }

        // 如果添加卡券后没有手动设置店铺和商品限制，卡券状态一直处于初始化
        if ($detail['kq_status'] == self::STATUS_INIT) {
            if (!isset($dataInfo['items']) || !isset($dataInfo['distributor_id'])) {
                $dataInfo['kq_status'] = self::STATUS_INIT;
            } elseif (isset($dataInfo['kq_status']) && $dataInfo['kq_status'] == self::STATUS_INIT) {
                // 前端随便传其他参数都应该转换成正常状态
                $dataInfo['kq_status'] = self::STATUS_NORMAL;
            }
        }

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $result = $this->discountCardRepository->update($dataInfo, $filter);
            if (isset($dataInfo['items'])) {
                $this->relItemsRepository->deleteQuick($filter);
                $relItemInsertData = $this->parseRelItemInsertData($dataInfo['items'], $result['card_id'], $result['company_id']);
                // 每100个分批导入
                foreach (array_chunk($relItemInsertData, 100) as $insertData) {
                    $this->relItemsRepository->createQuick($insertData);
                }
            }
            $this->relMemberTagRepository->deleteQuick($filter);
            // todo Duplicated code fragment --
            $companyId = $result['company_id'];
            $cardId = $result['card_id'];
            if (isset($dataInfo['user_tag_ids'])) {
                $relTagData = array_map(function ($tagId) use ($companyId, $cardId) {
                    return [
                        'company_id' => $companyId,
                        'card_id' => $cardId,
                        'tag_id' => $tagId,
                    ];
                }, $dataInfo['user_tag_ids']);
                $this->relMemberTagRepository->createQuick($relTagData);
            }
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }

        $eventData = [
            'card_id' => $result['card_id'],
            'company_id' => $result['company_id']
        ];
        event(new CouponEditEvent($eventData));

        return $result;
    }

    public function parseRelItemInsertData(&$items, $cardId, $companyId)
    {
        return array_map(function ($item) use ($cardId, $companyId) {
            $result = [
                'item_id' => intval($item['id']),
                'card_id' => intval($cardId),
                'item_type' => 'normal',
                'company_id' => intval($companyId),
            ];
            if (isset($item['limit'])) {
                $result['use_limit'] = intval($item['limit']);
            }
            return $result;
        }, $items);
    }

    public function __setParams(&$params, $is_create = false)
    {
        if (isset($params['date_type'])) {
            switch ($params['date_type']) {
                case self::DATE_TYPE_LONG:
                    $params['begin_date'] = $params['begin_time'];
                    $params['fixed_term'] = $params['days'];
                    $params['end_date'] = 0;
                    $hour = $params['fixed_term'] * 24;
                    break;
                case self::DATE_TYPE_SHORT:
                    $params['begin_date'] = $params['begin_time'];
                    $params['end_date'] = $params['end_time'];
                    $params['fixed_term'] = 0;
                    $hour = ($params['end_date'] - $params['begin_date']) / 3600;
                    break;
            }
        }

        if ($is_create && isset($params['lock_time']) && $params['lock_time'] > $hour) {
            throw new ResourceException(trans('KaquanBundle.lock_time_cannot_exceed_usage_time'));
        }

        // 处理 receive 字段，统一转换为整数 0 或 1
        if (isset($params['receive'])) {
            $params['receive'] = $params['receive'] == true ? 1 : 0;
        } elseif ($is_create) {
            $params['receive'] = 0;
        }

        // 照着以前的逻辑转义这些参数
        unset($params['days'], $params['begin_time'], $params['end_time']);

        if (isset($params['distributor_ids']) && !empty($params['distributor_ids'])) {
            $distributor_ids = json_decode($params['distributor_ids'], true);
            $distributor_ids = array_map(function ($id) {
                return intval($id);
            }, $distributor_ids);
            $params['distributor_id'] = $distributor_ids;
            unset($params['distributor_ids']);
            // // ..
            if (count($distributor_ids) == 0) {
                $params['use_all_shops'] = 'true';
            } else {
                $params['use_all_shops'] = 'false';
            }
        }

        if (isset($params['user_tag_ids']) && !empty($params['user_tag_ids'])) {
            $tags = json_decode($params['user_tag_ids'], true);
            $params['user_tag_ids'] = $tags;
        }

        if (isset($params['grade_ids']) && !empty($params['grade_ids']) ) {
            $ids = json_decode($params['grade_ids'], true);
            $params['grade_ids'] = $ids;
        }

        if (isset($params['vip_grade_ids']) && !empty($params['grade_ids'])) {
            $ids = json_decode($params['vip_grade_ids'], true);
            $params['vip_grade_ids'] = $ids;
        }

        if (isset($params['items']) && !empty($params['items'])) {
            $items = json_decode($params['items'], true);
            if ($items) {
                $params['items'] = $items;
                $params['use_bound'] = self::USE_BOUND_ASSIGN_ITEMS;
            } else {
                $params['items'] = [['id' => 0]];
                $params['use_bound'] = self::USE_BOUND_ALL_ITEMS;
            }
        } elseif ($is_create) {
            $params['items'] = [['id' => 0]];
            $params['use_bound'] = self::USE_BOUND_ALL_ITEMS;
        }
        if (isset($params['get_limit']) && $params['get_limit'] <= 0) {
            $params['get_limit'] = 1;
        }

        if (isset($params['use_all_items']) && $params['use_all_items'] == 'forbid') {
            $params['use_all_items'] = 'false';
        }
    }

    // 基础函数，数组转位运算后的值
    public static function toBitInt(array $ids): int
    {
        $bit = 0;
        foreach ($ids as $id) {
            $bit |= (1 << $id - 1);
        }
        return $bit;
    }

    public static function toBitArr(int $num): array
    {
        $arr = [];
        $bin = strrev(decbin($num));
        for ($i = strlen($bin) - 1; $i >= 0; $i--) {
            if ($bin[$i] == 1) {
                $arr[] = $i + 1;
            }
        }
        return $arr;
    }
}
