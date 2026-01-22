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

use DistributionBundle\Services\DistributorItemsService;
use DistributionBundle\Services\DistributorService;
use GoodsBundle\Services\ItemStoreService;
use Illuminate\Support\Arr;
use KaquanBundle\Entities\RelMemberTags;
use KaquanBundle\Entities\SalespersonGiveCoupons;
use KaquanBundle\Entities\UserDiscount;
use KaquanBundle\Entities\UserDiscountLogs;
use KaquanBundle\Interfaces\UserDiscountInterface;
use KaquanBundle\Repositories\UserDiscountRepository;
use KaquanBundle\Services\DiscountCardService as CardService;
use CompanysBundle\Services\ShopsService;
use CompanysBundle\Services\Shops\WxShopsService;
use MembersBundle\Services\MemberService;
use MembersBundle\Services\MemberTagsService;
use MembersBundle\Services\WechatUserService;
use OrdersBundle\Services\Orders\ExcardNormalOrderService;
use OrdersBundle\Services\OrderService;
use WechatBundle\Services\WeappService;
use Dingo\Api\Exception\ResourceException;
use KaquanBundle\Entities\DiscountCards;
use MembersBundle\Entities\MembersInfo;
use PromotionsBundle\Services\PromotionActivity;
use SalespersonBundle\Services\SalespersonCouponStatisticsService;
use SalespersonBundle\Services\SalespersonRelCouponService;
use SalespersonBundle\Services\SalespersonTaskRecordService;
use Exception;
use CompanysBundle\Ego\CompanysActivationEgo;
use ThirdPartyBundle\Services\DmCrm\DiscountCardService as DmDiscountCardService;

class UserDiscountService implements UserDiscountInterface
{
    /** @var UserDiscountRepository */
    private $userDiscountRepository;
    private $userDiscountLogsRepository;
    private $salespersonGiveCouponsRepository;
    private $discountCardsRepository;
    private $memberInfoRepository;

    public function __construct()
    {
        $this->userDiscountRepository = app('registry')->getManager('default')->getRepository(UserDiscount::class);
        $this->userDiscountLogsRepository = app('registry')->getManager('default')->getRepository(UserDiscountLogs::class);
        $this->salespersonGiveCouponsRepository = app('registry')->getManager('default')->getRepository(SalespersonGiveCoupons::class);
        $this->discountCardsRepository = app('registry')->getManager('default')->getRepository(DiscountCards::class);
        $this->memberInfoRepository = app('registry')->getManager('default')->getRepository(MembersInfo::class);
    }

    /**
     * 检测列表卡券是否能领取
     *
     * @param int $companyId
     * @param int $userId
     * @param array $cardList
     * @param string $from
     * @return array
     */
    public function checkCardList(int $companyId, int $userId, array $cardList, string $from): array
    {
        $cardIdList = array_column($cardList, 'card_id');

        $where = [
            'card_id' => $cardIdList,
            'company_id' => $companyId
        ];
        $discountNum = $this->userDiscountRepository->getTotalNumGroupBy($where);
        $discountNumIndex = array_column($discountNum, null, 'card_id');

        $where['user_id'] = $userId;
        $discountUserNum = $this->userDiscountRepository->getTotalNumGroupBy($where);
        $discountUserNumIndex = array_column($discountUserNum, null, 'card_id');

        // 会员信息
        $memberInfo = (new MemberService())->getMemberInfo(['user_id' => $userId], true);
        // Vip会员信息
        $vipGrade = (new VipGradeOrderService())->userVipGradeGet($companyId, $userId);

        $checkResult = [];
        $nowTime = time();
        foreach ($cardList as $item) {
            $i = 0;
            while (is_numeric($item['give_num']) && $i < $item['give_num']) {
                // end_date 验证
                if (strpos($item['end_date'], "-") !== false) {
                    $item['end_date'] = strtotime($item['end_date']);
                }
                if ($item['end_date'] && $item['end_date'] <= $nowTime) {
                    $checkResult[] = [
                        'card_info' => $item,
                        'message' => trans('KaquanBundle.receive_coupon_expired'),
                        'success' => false
                    ];
                    $i++;
                    app('log')->debug("时间验证错误:" . $item['end_date'] . " 现在时间:" . $nowTime);
                    continue;
                }

                // 已经消耗的卡券数量
                $usedCardNumber = $discountNumIndex[$item['card_id']]['total_num'] ?? 0;

                if ($item['quantity'] <= $usedCardNumber) {
                    $checkResult[] = [
                        'card_info' => $item,
                        'message' => trans('KaquanBundle.receive_coupon_out_of_stock'),
                        'success' => false
                    ];
                    app('log')->debug("总库存检测不通过");

                    $i++;
                    continue;
                } else {
                    if (isset($discountNumIndex[$item['card_id']]['total_num'])) {
                        $discountNumIndex[$item['card_id']]['total_num']++;
                    } else {
                        $discountNumIndex[$item['card_id']]['total_num'] = 1;
                    }
                }

                if ($from == 'template' && $item['receive'] != '1' && $item['receive'] != 1) {
                    $checkResult[] = [
                        'card_info' => $item,
                        'message' => trans('KaquanBundle.coupon_not_frontend_receivable'),
                        'success' => false
                    ];
                    $i++;
                    continue;
                }

                if ($item['kq_status'] != DiscountNewGiftCardService::STATUS_NORMAL) {
                    $checkResult[] = [
                        'card_info' => $item,
                        'message' => trans('KaquanBundle.coupon_status_abnormal'),
                        'success' => false
                    ];
                    $i++;
                    continue;
                }


                if ($item['grade_ids']) {
                    if (is_string($item['grade_ids'])) {
                        $item['grade_ids'] = explode(',', trim($item['grade_ids'], ','));
                    }
                    if (!in_array($memberInfo['grade_id'], $item['grade_ids'])) {
                        $checkResult[] = [
                            'card_info' => $item,
                            'message' => trans('KaquanBundle.member_grade_not_match'),
                            'success' => false
                        ];
                        $i++;
                        continue;
                    }
                }

                if ($item['vip_grade_ids']) {
                    if (!isset($vipGrade['is_open']) || !$vipGrade['is_open']) {
                        $checkResult[] = [
                            'card_info' => $item,
                            'message' => trans('KaquanBundle.vip_grade_not_match_not_open'),
                            'success' => false
                        ];
                        $i++;
                        continue;
                    }

                    if (is_string($item['vip_grade_ids'])) {
                        $item['vip_grade_ids'] = explode(',', trim($item['vip_grade_ids'], ','));
                    }

                    if (!in_array($vipGrade['vip_grade_id'], $item['vip_grade_ids'])) {
                        $checkResult[] = [
                            'card_info' => $item,
                            'message' => trans('KaquanBundle.vip_grade_not_match'),
                            'success' => false
                        ];
                        $i++;
                        continue;
                    }
                }

                $userGetNum = $discountUserNumIndex[$item['card_id']]['total_num'] ?? 0;

                if ($userGetNum >= $item['get_limit']) {
                    $checkResult[] = [
                        'card_info' => $item,
                        'message' => trans('KaquanBundle.user_exceed_coupon_limit'),
                        'success' => false
                    ];
                    $i++;
                    app('log')->debug("用户库存检测不通过 card_id:" . $item['card_id'] . "用户已发数量：" . $userGetNum . '卡券限制数量:' . $item['get_limit']);
                    continue;
                } else {
                    if (isset($discountUserNumIndex[$item['card_id']]['total_num'])) {
                        $discountUserNumIndex[$item['card_id']]['total_num']++;
                    } else {
                        $discountUserNumIndex[$item['card_id']]['total_num'] = 1;
                    }
                }

                $checkResult[] = [
                    'card_info' => $item,
                    'message' => '',
                    'success' => true
                ];
                $i++;
            }
        }

        return $checkResult;
    }

    /**
     * 批量发送优惠券
     *
     * @param int $companyId
     * @param int $userId
     * @param array $cardList
     * @param string $from
     * @param int $salespersonId
     * @return array
     * @throws Exception
     */
    public function userGetCardList(int $companyId, int $userId, array $cardList, string $from, int $salespersonId): array
    {
        $conversionSourceFrom = [
            'vip_grade' => '会员等级领优惠券包',
            'grade' => '等级领优惠券包',
            'template' => '模版领优惠券包'
        ];
        $sourceFromZh = $conversionSourceFrom[$from] ?? '其它';

        $cardList = $this->checkCardList($companyId, $userId, $cardList, $from);

        $result = [];
        $cards = [];
        foreach ($cardList as $item) {
            if ($item['success']) {
                $cards[] = $item['card_info'];
            } else {
                $cardInfoTemp = $item['card_info'];

                $result[] = [
                    'id' => $cardInfoTemp['id'],
                    'receive_id' => $cardInfoTemp['receive_id'],
                    'success' => $item['success'],
                    'message' => $item['message'],
                    'card_id' => $cardInfoTemp['card_id'],
                    'user_id' => $userId,
                    'company_id' => $companyId,
                ];
            }
        }

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();

        try {
            foreach ($cards as $cardInfo) {
                $tempCardInfo = $this->_handlerCardInfo($cardInfo);
                $postData = [
                    'status' => 1,
                    'source_type' => $sourceFromZh,
                    'company_id' => $companyId,
                    'card_id' => $tempCardInfo['card_id'],
                    'user_id' => $userId,
                    'code' => $this->getCode($companyId, $tempCardInfo['card_id'], $userId),
                    'salesperson_id' => $salespersonId,
                ];
                $this->userDiscountRepository->userGetCard($postData, $tempCardInfo);
                $salespersonTaskRecordService = new SalespersonTaskRecordService();
                $params = [
                    'company_id' => $companyId,
                    'salesperson_id' => $salespersonId,
                    'user_id' => $userId,
                    'type' => 'coupons_user',
                    'id' => $tempCardInfo['card_id'],
                ];
                $salespersonTaskRecordService->completeGetCoupon($params);
                $result[] = [
                    'id' => $cardInfo['id'],
                    'receive_id' => $cardInfo['receive_id'],
                    'success' => true,
                    'message' => '',
                    'card_id' => $cardInfo['card_id'],
                    'user_id' => $userId,
                    'company_id' => $companyId,
                ];
            }

            $conn->commit();
        } catch (Exception $exception) {
            $conn->rollback();
            throw $exception;
        }
        return $result;
    }

    /**
     * [userGetCard 用户领取优惠券]
     * @param  int $companyId
     * @param  int $cardId
     * @param  int $userId
     * @param  string $sourceFrom
     * @return array
     */
    public function userGetCard($companyId, $cardId, $userId, $sourceFrom = "商城本地领取", $salespersonId = 0, $activityName = '' , $dmCardCode = '', $mobile = '')
    {
        if (!$companyId) {
            throw new ResourceException(trans('KaquanBundle.coupon_record_add_failed'));
        }
        $postdata['status'] = 1;
        // 校验优惠券是否能领取
        $cardInfo = $this->__getCardInfo($cardId, $companyId, $userId);
        app('log')->info('registerPromotion::userGetCard::cardInfo='.json_encode($cardInfo));
        $dmDiscountCardService = new DmDiscountCardService($companyId);
        // 去达摩发放卡券
        if (!in_array($sourceFrom, ['达摩CRM批量投放事件', '达摩CRM领取事件']) && $dmDiscountCardService->isOpen && $cardInfo['source_type'] == 'dmcrm') {
            if (!$mobile) {
                $memberService = new MemberService();
                $mobile = $memberService->getMobileByUserId($userId, $companyId);
            }
            app('log')->info('registerPromotion::userGetCard::mobile='.$mobile);
            $dmResult = $dmDiscountCardService->sendCoupon($cardInfo['dm_card_id'], $mobile);
            $postdata['dm_card_code'] = $dmResult['couponCode'];
        }
        if ($dmCardCode) {
            $postdata['dm_card_code'] = $dmCardCode;
            $postdata['code'] = $dmCardCode;
        } else {
            $postdata['code'] = $this->getCode($companyId, $cardId, $userId);
        }

        // 如果设置了  前台不可直接领取 则提示
        // if ($sourceFrom == "商城本地领取" && $cardInfo['receive'] != 'true') {
        //     throw new ResourceException('不可领取！本地领取方式不可用');
        // }

        if ($cardInfo['kq_status'] != DiscountNewGiftCardService::STATUS_NORMAL) {
            throw new ResourceException(trans('KaquanBundle.not_receivable_status_abnormal'));
        }

        if ($sourceFrom == '商城本地领取') {
            $grade_receive = true;
            $memberService = new MemberService();
            $memberInfo = $memberService->getMemberInfo(['user_id' => $userId], true);
            $grade_id = $memberInfo['grade_id'];
            if ($cardInfo['grade_ids'] && !in_array($grade_id, $cardInfo['grade_ids'])) {
                $grade_receive = false;
            }
            $vipgrade_receive = true;
            if (!$grade_receive && $cardInfo['vip_grade_ids']) {
                $vipGradeService = new VipGradeOrderService();
                $vipgrade = $vipGradeService->userVipGradeGet($companyId, $userId);
                if (!($vipgrade['is_open'] ?? false)) {
                    $vipgrade_receive = false;
                }
                if (!in_array($vipgrade['vip_grade_id'], $cardInfo['vip_grade_ids'])) {
                    $vipgrade_receive = false;
                }
            }
            if (!$grade_receive && !$vipgrade_receive) {
                throw new ResourceException(trans('KaquanBundle.not_receivable'));
            }

            // 判断用户标签...
            /*        $memberTagsService = new MemberTagsService();
                    $filter = ['company_id' => $companyId, 'card_id' => $cardId];
                    $DiscountRelMemberTagRepository = app('registry')->getManager('default')->getRepository(RelMemberTags::class);
                    $relMemberTags = $DiscountRelMemberTagRepository->lists($filter);
                    $discountRelMemberTagIds = array_column($relMemberTags, 'tag_id');
                    if ($discountRelMemberTagIds) {
                        $count = $memberTagsService->getRelCount([
                            'user_id' => app('auth')->user()->get('user_id'),
                            'company_id' =>$companyId,
                            'tag_id' => $discountRelMemberTagIds,
                        ]);
                        if ($count <= 0) {
                            throw new ResourceException('不可领取！');
                        }
                    }*/
        }

        $filter['card_id'] = $cardId;
        $filter['user_id'] = $userId;
        $userGetNum = $this->getUserGetNum($userId, $cardId, $companyId);

        if (($sourceFrom == "商城本地领取" || $sourceFrom == "大转盘中奖领取") && $userGetNum >= $cardInfo['get_limit']) {
            throw new ResourceException(trans('KaquanBundle.already_received_check_center'));
        }
        $postdata['source_type'] = $sourceFrom;
        $postdata['company_id'] = $companyId;
        $postdata['card_id'] = $cardId;
        $postdata['user_id'] = $userId;
        $postdata['code'] = $this->getCode($companyId, $cardId, $userId);
        $postdata['salesperson_id'] = $salespersonId;
        $postdata['activity_name'] = $activityName;
        $status = $this->userDiscountRepository->userGetCard($postdata, $cardInfo);
        $salespersonTaskRecordService = new SalespersonTaskRecordService();
        $params = [
            'company_id' => $companyId,
            'salesperson_id' => $salespersonId,
            'user_id' => $userId,
            'type' => 'coupons_user',
            'id' => $cardId,
        ];
        $salespersonTaskRecordService->completeGetCoupon($params);


        $cardInfo['status'] = $postdata['status'];
        $result = $this->__getCardData($cardId, $companyId, $userId);
        $result['code'] = $postdata['code'];
        $result['dm_card_code'] = $postdata['dm_card_code'] ?? '';
        $this->sendWxaTemplateMsg($companyId, $userId, $cardInfo, 'get');
        return $result;
    }

    public function sendWxaTemplateMsg($companyId, $userId, $data, $source = 'get')
    {
        $wxaappid = app('wxaTemplateMsg')->getWxaAppId($companyId);
        if (!$wxaappid) {
            return true;
        }
        $openid = app('wxaTemplateMsg')->getOpenIdBy($userId, $wxaappid);
        if (!$openid) {
            return true;
        }

        if ($data['card_type'] != 'gift') {
            $remarks = '满'. ($data['least_cost'] ? $data['least_cost'] / 100 : 0.01) .'元可用';
        } else {
            $remarks = '无限制';
        }

        if ($data['card_type'] == 'cash') {
            $amount = ($data['reduce_cost'] / 100). "元";
        } elseif ($data['card_type'] == 'discount') {
            $amount = ((100 - $data['discount']) / 10). "折";
        } elseif ($data['card_type'] == 'gift' || $data['card_type'] == 'new_gift') {
            $amount = $data['gift'];
        }

        if (isset($data['status']) && $data['status'] == 2) {
            $status = '已使用';
        } else {
            $status = '已到账';
        }

        $wxaTemplateMsgData = [
            'title' => $data['title'],
            'used_action' => '至小程序商城购物可使用',
            'amount' => $amount,
            'status' => $status,
            'active_date' => date('Y-m-d', $data['begin_date']).' - '. date('Y-m-d', $data['end_date']),
            'activedate' => date('Y.m.d H:i:s', $data['end_date']),
            'remarks' => $remarks,
        ];
        if ($source == 'get') {
            $sendData['scenes_name'] = 'userGetCardSucc';
        } else {
            $sendData['scenes_name'] = 'cardUsedSucc';
        }
        $sendData['company_id'] = $companyId;
        $sendData['appid'] = $wxaappid;
        $sendData['openid'] = $openid;
        $sendData['data'] = $wxaTemplateMsgData;
        app('wxaTemplateMsg')->send($sendData);
    }

    /**
     * [userDelCard 用户删除领取到的优惠券]
     * @param  int $companyId
     * @param  int $userId
     * @param  string $id        用户领取优惠券时的自增id
     * @param  string $code      优惠券码
     * @return bool
     */
    public function userDelCard($companyId, $userId, $id = "", $code = "")
    {
        $filter['user_id'] = $userId;
        $filter['company_id'] = $companyId;
        if ($id) {
            $filter['id'] = $id;
        }
        if ($code) {
            $filter['code'] = $code;
        }
        return $this->userDiscountRepository->userDelCard($filter);
    }

    /**
     * [userConsumeCard 用户使用优惠券]
     * @param  int $companyId
     * @param  string $code                优惠券码
     * @param  string $params              核销操作内容
     * @return bool
     */
    public function userConsumeCard($companyId, $code, $params = ['consume_outer_str' => '快捷买单核销'])
    {
        $filter['company_id'] = $companyId;
        $filter['code'] = $code;
        if (isset($params['user_id'])) {
            $filter['user_id'] = $params['user_id'];
        }

        //获取卡券明细
        $nowTime = time();
        $cardData = $this->getUserCardInfo($filter, true);
        $userCardInfo = $cardData['detail'];
        if (!in_array($userCardInfo['status'], [1, 4])) {
            throw new ResourceException(trans('KaquanBundle.coupon_use_failed_used_invalid'));
        }
        if ($userCardInfo['begin_date'] > $nowTime) {
            throw new ResourceException(trans('KaquanBundle.coupon_use_failed_not_time'));
        }
        if ($userCardInfo['end_date'] <= $nowTime) {
            throw new ResourceException(trans('KaquanBundle.coupon_use_failed_expired'));
        }

        if (isset($params['item_id']) && $params['item_id']) {
            if (!in_array($params['item_id'], $userCardInfo['rel_item_ids'])) {
                throw new ResourceException(trans('KaquanBundle.coupon_use_failed_not_applicable_product'));
            }
        }
        $cardInfo = $cardData['card_info'];
        if ($cardInfo['use_scenes'] == "SELF" && isset($params['verify_code']) && $params['verify_code']) {
            if ($cardInfo['self_consume_code'] != $params['verify_code']) {
                throw new ResourceException(trans('KaquanBundle.coupon_use_failed_wrong_verify_code'));
            }
        }
        $salespersonCouponStatisticsService = new SalespersonCouponStatisticsService();
        $couponParams = [
            'company_id' => $cardData['detail']['company_id'],
            'salesperson_id' => $cardData['detail']['salesperson_id'],
            'coupon_id' => $cardData['detail']['id'],
        ];
        $salespersonCouponStatisticsService->completeCouponPay($couponParams);
        //获取核销的门店信息
        if (isset($params['shop_id']) && $params['shop_id']) {
            $shopList = $cardData['shop_list']['list'];
            foreach ($shopList as $value) {
                if ($value['wxShopId'] == $params['shop_id']) {
                    $params['location_name'] = $value['companyName']."(".$value['storeName'].")";
                    $params['location_id'] = $value['wxShopId'];
                    unset($params['shop_id']);
                    break;
                }
            }
        }

        $postdata = $params;
        $postdata['consume_source'] = $cardInfo['use_scenes'];
        $filter['code'] = $code;
        if (!isset($params['status'])) {
            $postdata['status'] = 2;
        }

        $result = $this->userDiscountRepository->userConsumeCardUpdate($postdata, $filter);
        // 记录使用日志
        if (isset($result['status']) && $result['status'] == true) {
            $memberService = new MemberService();
            $memberInfo = [];
            if ($cardData['detail']['user_id']) {
                $memberInfo = $memberService->getMemberInfo(['user_id' => $cardData['detail']['user_id'], 'company_id' => $companyId]);
            }
            $logData = [
                'user_id' => $cardData['detail']['user_id'],
                'company_id' => $cardData['detail']['company_id'],
                'mobile' => isset($memberInfo['mobile']) ? $memberInfo['mobile'] : '',
                'username' => isset($memberInfo['username']) ? $memberInfo['username'] : '',
                'card_id' => $cardData['detail']['card_id'],
                'code' => $cardData['detail']['code'],
                'title' => $cardData['detail']['title'],
                'card_type' => $cardData['detail']['card_type'],
                'shop_name' => isset($postdata['location_name']) ? $postdata['location_name'] : trans('KaquanBundle.shop_name_default'),
                'used_time' => time(),
                'used_status' => 'consume',
                'used_order' => $params['trans_id'] ?? '',
            ];
            $this->userDiscountLogsRepository->create($logData);
        }
        $userCardInfo['status'] = $postdata['status'];
        $this->sendWxaTemplateMsg($companyId, $userCardInfo['user_id'], $userCardInfo, 'used');
        return $result;
    }

    /**
     * [getUserDiscountList 获取]
     * @param  [type]  $filter   [description]
     * @param  integer $page     [description]
     * @param  integer $pageSize [description]
     * @return [type]            [description]
     */
    public function getUserDiscountList($filter, $page = 1, $pageSize = 50)
    {
        $pageSize = ($pageSize > 50) ? 50 : $pageSize;
        $pageSize = ($pageSize <= 0) ? 20 : $pageSize;
        $page = ($page - 1) * $pageSize;
        $cardLists = [];
        $cardTotal = $this->userDiscountRepository->getTotalNum($filter);
        if ($cardTotal) {
            $cardLists = $this->userDiscountRepository->getUserCardList($filter, $page, $pageSize);
            $cardIds = array_column($cardLists, 'card_id');
            $distributorIds = [];
            $cardService = new CardService();
            $cardDescriptionTempList = $cardService->getList(['card_id', 'description'], ['card_id' => $cardIds], 0, $pageSize);
            $cardDescriptionList = array_column($cardDescriptionTempList, null, 'card_id');
            foreach ($cardLists as &$detail) {
                if (isset($detail['rel_shops_ids']) && $detail['rel_shops_ids'] && $detail['rel_shops_ids'] !== 'all') {
                    $detail['rel_shops_ids'] = array_filter(explode(',', $detail['rel_shops_ids']));
                } else {
                    $detail['rel_shops_ids'] = 'all';
                }

                if (isset($detail['rel_distributor_ids']) && $detail['rel_distributor_ids'] && $detail['rel_distributor_ids'] !== 'all') {
                    $detail['rel_distributor_ids'] = array_filter(explode(',', $detail['rel_distributor_ids']));
                    $distributorIds = array_merge($distributorIds, $detail['rel_distributor_ids']);
                } else {
                    $detail['rel_distributor_ids'] = 'all';
                }

                if (isset($detail['rel_item_ids']) && $detail['rel_item_ids'] && $detail['rel_item_ids'] !== 'all') {
                    $detail['rel_item_ids'] = array_filter(explode(',', $detail['rel_item_ids']));
                    $detail['use_all_items'] = false;
                } else {
                    $detail['rel_item_ids'] = 'all';
                    $detail['use_all_items'] = true;
                }
                $detail['use_condition'] = unserialize($detail['use_condition']);
                $detail['description'] = isset($cardDescriptionList[$detail['card_id']]) ? $cardDescriptionList[$detail['card_id']]['description'] : '';
            }
            unset($detail);

            $distributorList = [];
            if ($distributorIds) {
                $distributorService = new DistributorService();
                $distributorTempList = $distributorService->lists(['distributor_id' => $distributorIds], ['created' => 'desc'], -1);
                $distributorList = array_column($distributorTempList['list'], null, 'distributor_id');
            }
            $detail['distributor_info'] = [];
            foreach ($cardLists as &$detail) {
                if (is_array($detail['rel_distributor_ids'])) {
                    foreach ($detail['rel_distributor_ids'] as $v) {
                        if (isset($distributorList[$v])) {
                            $detail['distributor_info'][] = $distributorList[$v];
                        }
                    }
                }
            }
            unset($detail);
            $detail['use_all_distributor'] = $detail['distributor_info'] ?? [] ? false : true;
        }
        $result['list'] = $cardLists;
        $result['count'] = $cardTotal;
        return $result;
    }

    /**
     * [getDiscountUserList 获取用户卡券领取情况]
     * @param  [type]  $filter   查询条件
     * @param  integer $page     页数
     * @param  integer $pageSize 分页条数
     * @return [type]            [description]
     */
    public function getDiscountUserList($filter, $page = 1, $pageSize = 50)
    {
        $pageSize = ($pageSize > 50) ? 50 : $pageSize;
        $pageSize = ($pageSize <= 0) ? 20 : $pageSize;
        $page = ($page - 1) * $pageSize;
        $cardLists = [];
        if (isset($filter['mobile']) && $filter['mobile']) {
            $filter['user_id'] = (new MemberService())->getUserIdByMobile($filter['mobile'], $filter['company_id']);
            unset($filter['mobile']);
        }
        if (isset($filter['status']) && $filter['status']) {
            $filter = $this->myUserCardStatusFilter($filter, $filter['status']);
        }
        $cardTotal = $this->userDiscountRepository->getTotalNum($filter);
        $cardUserInfo = [];
        $uids = [];
        if ($cardTotal) {
            $cardLists = $this->userDiscountRepository->getCardUserList($filter, $page, $pageSize);
            foreach ($cardLists as &$detail) {
                $detail['begin_date_str'] = date('Y-m-d H:i:s', $detail['begin_date']);
                $detail['end_date_str'] = date('Y-m-d H:i:s', $detail['end_date']);
                $uids[] = $detail['user_id'];
                if (isset($detail['rel_shops_ids']) && $detail['rel_shops_ids'] && $detail['rel_shops_ids'] !== 'all') {
                    $detail['rel_shops_ids'] = array_filter(explode(',', $detail['rel_shops_ids']));
                } else {
                    $detail['rel_shops_ids'] = 'all';
                }

                if (isset($detail['rel_item_ids']) && $detail['rel_item_ids'] && $detail['rel_item_ids'] !== 'all') {
                    $detail['rel_item_ids'] = array_filter(explode(',', $detail['rel_item_ids']));
                } else {
                    $detail['rel_item_ids'] = 'all';
                }
                $detail['use_condition'] = unserialize($detail['use_condition']);
                $detail['status_msg'] = $this->__status($detail['status']);
            }
            $userInfo = (new MemberService())->getList(1, $pageSize, ['user_id|in' => array_unique($uids)]);
            foreach ($userInfo['list'] as $v) {
                $cardUserInfo[$v['user_id']] = $v;
            }
        }

        foreach ($cardLists as &$v) {
            $v['username'] = isset($cardUserInfo[$v['user_id']]) ? $cardUserInfo[$v['user_id']]['username'] : trans('KaquanBundle.none');
            $v['mobile'] = isset($cardUserInfo[$v['user_id']]) ? $cardUserInfo[$v['user_id']]['mobile'] : trans('KaquanBundle.none');
        }
        $result['list'] = $cardLists;
        $result['count'] = $cardTotal;
        return $result;
    }

    /**
     * [getDiscountUserLogsList 获取用户卡券使用情况]
     * @param  [type]  $filter   查询条件
     * @param  integer $page     页数
     * @param  integer $pageSize 分页条数
     * @return [type]            [description]
     */
    public function getDiscountUserLogsList($filter, $page = 1, $pageSize = 50)
    {
        if (isset($filter['mobile']) && $filter['mobile']) {
            $filter['user_id'] = (new MemberService())->getUserIdByMobile($filter['mobile'], $filter['company_id']);
            unset($filter['mobile']);
        }
        $result = $this->userDiscountLogsRepository->lists($filter, ["used_time" => "DESC"], $pageSize, $page);

        foreach ($result['list'] as &$v) {
            $v['used_time'] = date('Y-m-d H:i:s', $v['used_time']);
        }
        return $result;
    }

    private function __status($status)
    {
        $allStatus = [
            '1' => trans('KaquanBundle.unchecked'),
            '2' => trans('KaquanBundle.checked'),
            '3' => trans('KaquanBundle.transferred'),
            '5' => trans('KaquanBundle.expired_status'),
        ];
        return $allStatus[$status] ?? '';
    }

    /**
     * [getUserDiscountCount 获取会员优惠券总数量]
     * @param  [type] $filter [description]
     * @return [type]         [description]
     */
    public function getUserDiscountCount($filter)
    {
        $cardTotal = $this->userDiscountRepository->getTotalNum($filter);
        return intval($cardTotal);
    }

    /**
     * [getUserCardInfo 获取优惠券的详细信息]
     * @param  [type]  $filter      [description]
     * @param  boolean $getCardInfo 是否要获取卡券明细
     * @return [type]               [description]
     */
    public function getUserCardInfo($filter, $getCardInfo = false)
    {
        $orderItemIds = [];
        $defaultItemIds = [];
        if (isset($filter['order_item_ids'])) {
            $orderItemIds = $filter['order_item_ids'];
            $defaultItemIds = $filter['default_item_ids'];
            unset($filter['order_item_ids'], $filter['default_item_ids']);
        }

        $principal_name = '';
        if ($filter['wxapp_appid'] ?? 0) {
            $WeappService = new WeappService();
            $wxapp_appid = $filter['wxapp_appid'];
            $data = $WeappService->getWxaDetail($filter['company_id'], $wxapp_appid);
            $principal_name = $data['principal_name'];
        }
        unset($filter['wxapp_appid']);
        $result['detail'] = [];
        $result['shop_list'] = [];

        $datalist = $this->userDiscountRepository->getUserCardList($filter);
        $detail = reset($datalist);
        if ($detail) {
            if ($detail['rel_shops_ids'] !== 'all') {
                if (isset($detail['rel_shops_ids']) && $detail['rel_shops_ids']) {
                    $detail['rel_shops_ids'] = array_filter(explode(',', $detail['rel_shops_ids']));
                }
                if (isset($detail['rel_distributor_ids']) && $detail['rel_distributor_ids']) {
                    $detail['rel_distributor_ids'] = array_filter(explode(',', $detail['rel_distributor_ids']));
                }
            } else {
                $detail['rel_shops_ids'] = 'all';
            }

            if (isset($detail['rel_item_ids']) && $detail['rel_item_ids'] && $detail['rel_item_ids'] !== 'all') {
                $detail['rel_item_ids'] = array_filter(explode(',', $detail['rel_item_ids']));
            } else {
                $detail['rel_item_ids'] = 'all';
            }

            $detail['use_condition'] = unserialize($detail['use_condition']);
            $nowTime = time();
            $detail['is_valid'] = true;
            if ($detail['status'] != 1) {
                $detail['is_valid'] = false;
            }
            if ($detail['begin_date'] > $nowTime) {
                $detail['is_valid'] = false;
            }
            if ($detail['end_date'] <= $nowTime) {
                $detail['is_valid'] = false;
            }
            $result['detail'] = $detail;

            //获取该卡券支持的门店详情列表
            $poiFilter['company_id'] = $detail['company_id'];
            if (is_array($detail['rel_shops_ids'])) {
                $poiFilter['wx_shop_id'] = $detail['rel_shops_ids'];
            }
            $poiFilter['expired_at|gt'] = time();
            $shopsService = new ShopsService(new WxShopsService());
            $poiList = $shopsService->getShopsList($poiFilter, 1, 50);
            if ($principal_name) {
                foreach ($poiList['list'] as &$value) {
                    $value['companyName'] = $principal_name;
                }
            }
            $result['shop_list'] = $poiList;
        }

        //获取卡券详细信息
        $result['card_info'] = [];
        $result['card_code'] = [];
        if ($getCardInfo && $detail) {
            $cardFilter['card_id'] = $detail['card_id'];
            $cardFilter['company_id'] = $detail['company_id'];
            $discountCardService = new KaquanService(new CardService());
            $cardInfo = $discountCardService->getKaquanDetail($cardFilter);

            //扫码核销生成二维码
            if ($cardInfo['use_scenes'] == "SWEEP") {
                $dns1d = app('DNS1D')->getBarcodePNG('CQ_'.$detail['code'], "C93", 1, 70);
                $dns2d = app('DNS2D')->getBarcodePNG('CQ_'.$detail['code'], "QRCODE", 120, 120);

                $result['card_code'] = [
                    'barcode_url' => 'data:image/jpg;base64,' . $dns1d,
                    'qrcode_url' => 'data:image/jpg;base64,' . $dns2d,
                    'code' => $detail['code'],
                ];
            }
            $result['card_info'] = $cardInfo;
        }
        return $result;
    }

    /**
     * [getCode 领取优惠券时 生成code码]
     * @param  [type] $companyId [description]
     * @param  [type] $cardId    [description]
     * @param  [type] $userId    [description]
     * @return [type]            [description]
     */
    private function getCode($companyId, $cardId, $userId)
    {
        $code = $this->genId(12);
        $filter['company_id'] = $companyId;
        $filter['code'] = $code;
        $userCode = $this->userDiscountRepository->get($filter);
        if (!$userCode) {
            return $code;
        } else {
            return $this->getCode($companyId, $cardId, $userId);
        }
    }

    /**
     * [genId 生成指定长度的字符串编码]
     * @param  integer $length [description]
     * @param  string  $prefix [description]
     * @param  string  $suffix [description]
     * @return [type]          [description]
     */
    private function genId($length = 8, $prefix = '', $suffix = '')
    {
        // $uppercase    = ['Q', 'W', 'E', 'R', 'T', 'Y', 'U', 'I', 'O', 'P', 'A', 'S', 'D', 'F', 'G', 'H', 'J', 'K', 'L', 'Z', 'X', 'C', 'V', 'B', 'N', 'M'];
        $numbers = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
        $characters = [];
        $coupon = '';
        // $characters = array_merge($numbers, $uppercase);
        $characters = $numbers;

        for ($i = 0; $i < $length; $i++) {
            $coupon .= $characters[mt_rand(0, count($characters) - 1)];
        }
        return $prefix . $coupon . $suffix;
    }

    private function __getCardData($cardId, $companyId, $userId)
    {
        $result = [];
        $discountCardService = new KaquanService(new CardService());
        $filter['card_id'] = $cardId;
        $filter['company_id'] = $companyId;
        $cardInfo = $discountCardService->getKaquanDetail($filter);
        //指定优惠券总领取量,优惠券被领取的总数量
        $getNum = $this->getCardGetNum($cardId, $companyId);
        if ($getNum) {
            $result['total_lastget_num'] = $cardInfo['quantity'] - $getNum;
        }
        //指定优惠券个人总领取量,个人领取的指定优惠券总数
        $userGetNum = $this->getUserGetNum($userId, $cardId, $companyId);
        if ($userGetNum) {
            $result['lastget_num'] = $cardInfo['get_limit'] - $userGetNum;
        }
        return $result;
    }

    /**
     * [__getCardInfo 领取优惠券时 检测领取权限]
     * @param  [type] $cardId    [description]
     * @param  [type] $companyId [description]
     * @return [type]            [description]
     */
    private function __getCardInfo($cardId, $companyId, $userId)
    {
        $discountCardService = new KaquanService(new CardService());
        $filter['card_id'] = $cardId;
        $filter['company_id'] = $companyId;
        $cardInfo = $discountCardService->getKaquanDetail($filter);
        if (!$cardInfo) {
            throw new ResourceException(trans('KaquanBundle.receive_coupon_not_exist'));
        }
        if ($cardInfo['end_date'] && $cardInfo['end_date'] <= time()) {
            throw new ResourceException(trans('KaquanBundle.receive_coupon_failed_expired'));
        }

        $getNum = $this->getCardGetNum($cardId, $companyId);

        if ($cardInfo['quantity'] <= $getNum) {
            throw new ResourceException(trans('KaquanBundle.receive_coupon_failed_insufficient_stock'));
        }

        return $this->_handlerCardInfo($cardInfo);
    }

    /**
     * 处理单个卡券数据
     *
     * @param array $cardInfo
     * @return array
     */
    private function _handlerCardInfo(array $cardInfo): array
    {
        $cardInfo['discount'] = $cardInfo['discount'] ?: 0;
        $cardInfo['least_cost'] = $cardInfo['least_cost'] ?: 0;
        $cardInfo['reduce_cost'] = $cardInfo['reduce_cost'] ?: 0;
        $cardInfo['most_cost'] = $cardInfo['most_cost'] ?: 99999900;

        $cardInfo['use_condition'] = [
            'accept_category' => $cardInfo['accept_category'],
            'reject_category' => $cardInfo['reject_category'],
            'least_cost' => $cardInfo['least_cost'],
            'object_use_for' => $cardInfo['object_use_for'],
            'can_use_with_other_discount' => $cardInfo['can_use_with_other_discount'],
        ];

        if (isset($cardInfo['distributor_id']) && $cardInfo['distributor_id']) {
            $distributorIds = explode(',', $cardInfo['distributor_id']);
            $cardInfo['rel_distributor_ids'] = [];
            if (count($distributorIds) > 0) {
                foreach ($distributorIds as $value) {
                    if (is_numeric($value)) {
                        $cardInfo['rel_distributor_ids'][] = $value;
                    }
                }
            }
        }

        if (is_string($cardInfo['rel_shops_ids']) && $cardInfo['rel_shops_ids']) {
            $relShopsIds = explode(',', $cardInfo['rel_shops_ids']);
            if (array_filter($relShopsIds)) {
                unset($cardInfo['rel_shops_ids']);
                $cardInfo['rel_shops_ids'] = array_filter($relShopsIds);
            } else {
                $cardInfo['rel_shops_ids'] = [];
            }
        }

        if ($cardInfo['use_all_shops'] == "true") {
            $cardInfo['rel_shops_ids'] = 'all';
            $cardInfo['distributor_id'] = 'all';
        } else {
            $shopIds = $cardInfo['rel_shops_ids'] ?? [];
            $cardInfo['rel_shops_ids'] = !empty($shopIds) ? ',' . implode(',', $shopIds) . ',' : 'all';

            $distributorIds = $cardInfo['rel_distributor_ids'] ?? [];
            $cardInfo['distributor_id'] = count($distributorIds) > 0 ? ',' . implode(',', $distributorIds) . ',' : 'all';
        }

        switch ($cardInfo['use_bound']) {
            case '0':
                $cardInfo['rel_item_ids'] = 'all';
                break;
            case '1'://指定商品
                $cardInfo['rel_item_ids'] = ',' . implode(',', $cardInfo['rel_item_ids'] ?? []) . ',';
                break;
            case '2'://指定主类目
                $cardInfo['rel_item_ids'] = ',' . implode(',', $cardInfo['item_category'] ?? []) . ',';
                break;
            case '3'://指定标签
                $cardInfo['rel_item_ids'] = ',' . implode(',', $cardInfo['tag_ids'] ?? []) . ',';
                break;
            case '4'://指定品牌
                $cardInfo['rel_item_ids'] = ',' . implode(',', $cardInfo['brand_ids'] ?? []) . ',';
                break;
            case '5':// 指定商品不可选
                $cardInfo['rel_item_ids'] = ',' . implode(',', $cardInfo['rel_item_ids'] ?? []) . ',';
                break;
        }

        if ($cardInfo['card_type'] == 'new_gift') {
            // 兑换卡券不冗余 distributor_ids 和 item_ids
            $cardInfo['distributor_id'] = '';
            $cardInfo['rel_item_ids'] = '';
        }

        app('log')->info('DATE_TYPE:' . $cardInfo['date_type'] . 'begin_date:' . $cardInfo['begin_date'] . 'end_date:' . $cardInfo['end_date'] . 'fixed_term:' . $cardInfo['fixed_term']);
        if ($cardInfo['date_type'] == "DATE_TYPE_FIX_TERM" || $cardInfo['date_type'] == DiscountNewGiftCardService::DATE_TYPE_LONG) {
            if ($cardInfo['begin_date'] < 10000) {
                $cardInfo['begin_date'] = strtotime(date('Y-m-d 00:00:00', time() + 3600 * 24 * $cardInfo['begin_date']));
                if (intval($cardInfo['end_date']) <= 0) {
                    $cardInfo['end_date'] = strtotime(date('Y-m-d 23:59:59', $cardInfo['begin_date'] + 3600 * 24 * $cardInfo['fixed_term']));
                }
            }
        }

        return $cardInfo;
    }

    public function getCardGetNum($cardId, $companyId)
    {
        $filter['card_id'] = $cardId;
        $filter['company_id'] = $companyId;
        $userGetNum = $this->userDiscountRepository->getTotalNum($filter);
        return $userGetNum ?: 0;
    }

    public function getUserGetNum($userId, $cardId, $companyId)
    {
        $filter['user_id'] = $userId;
        $filter['card_id'] = $cardId;
        $filter['company_id'] = $companyId;
        $userGetNum = $this->userDiscountRepository->getTotalNum($filter);
        return $userGetNum ?: 0;
    }

    public function getCardUsedNum($cardId, $companyId)
    {
        $filter['card_id'] = $cardId;
        $filter['status'] = 2;
        $filter['company_id'] = $companyId;
        $userGetNum = $this->userDiscountRepository->getTotalNum($filter);
        return $userGetNum ?: 0;
    }

    // 获取兑换券使用上限
    public function getCardExchangeItemNum($cardId, $itemId, $companyId)
    {
        $filter['card_id'] = $cardId;
        $filter['company_id'] = $companyId;
        $filter['rel_item_ids'] = strval($itemId);
        $userGetNum = $this->userDiscountRepository->getTotalNum($filter);
        return $userGetNum ?: 0;
    }

    /**
     * [__getCardInfo 取消订单时，恢复优惠券]
     * @param  [type] $code    [优惠券唯一code码]
     * @param  [type] $userId  [会员编号]
     * @return [type]            [description]
     */
    public function callbackUserCard($companyId, $code, $userId, $mobile = '', $dmCardCode = '')
    {
        $filter['company_id'] = $companyId;
        $filter['code'] = $code;
        $filter['user_id'] = $userId;
        $filter['status'] = 2;
        $detail = $this->userDiscountRepository->get($filter);
        if (!$detail) {
            throw new ResourceException('恢复失败，无此优惠券 或 未被使用');
        }
        // 达摩CRM卡券解除占用
        $dmDiscountCardService = new DmDiscountCardService($companyId);
        if ($dmDiscountCardService->isOpen && $dmCardCode) {
            $dmDiscountCardService->occupyCoupon($mobile, $dmCardCode, 0);
        }
        $postdata['status'] = 1;
        $result = $this->userDiscountRepository->userConsumeCardUpdate($postdata, $filter);

        // 优惠券回退日志
        if (isset($result['status']) && $result['status'] == true) {
            $memberService = new MemberService();
            $memberInfo = [];
            $memberInfo = $memberService->getMemberInfo(['user_id' => $userId, 'company_id' => $companyId]);
            $logData = [
                'user_id' => $detail->getUserId(),
                'company_id' => $detail->getCompanyId(),
                'mobile' => isset($memberInfo['mobile']) ? $memberInfo['mobile'] : '',
                'username' => isset($memberInfo['username']) ? $memberInfo['username'] : '',
                'card_id' => $detail->getCardId(),
                'code' => $detail->getCode(),
                'title' => $detail->getTitle(),
                'card_type' => $detail->getCardType(),
                'shop_name' => isset($postdata['location_name']) ? $postdata['location_name'] : trans('KaquanBundle.shop_name_default'),
                'used_time' => time(),
                'used_status' => 'callback',
                'used_order' => $detail->getTransId(),
            ];
            $this->userDiscountLogsRepository->create($logData);
        }
        return $result;
    }

    public function countUserCard($filter)
    {
        return (int)$this->getNewUserCardList($filter, 0, -1, true);
    }

    public function getNewUserCardList($filter, $page = 0, $pageSize = -1, $onlyCount = false, $filterDistributor = false)
    {
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $criteria->from('kaquan_user_discount', 'kudc');
        $criteria->leftjoin('kudc', 'kaquan_discount_cards', 'kdc', 'kudc.card_id = kdc.card_id');
        if ($filter['item_id'] ?? []) {
            $cardService = new CardService();
            $filterUserCardIds = $cardService->getUserCardIdsByGoods($filter);
            if ($filterUserCardIds) {
                $criteria->andWhere($criteria->expr()->in('kudc.id', $filterUserCardIds));
            } else {
                return ['total_count' => 0, 'list' => []]; //没有符合条件的卡券
            }
        }

        // 店铺和平台的优惠券相互独立
        // 获取优惠券的适用范围
        $scopeType = $filter["scope_type"] ?? null;
        unset($filter["scope_type"]);
        if ($scopeType != "all") {
            // 获取店铺id
            $distributor_id = $filter['distributor_id'] ?? 0;
            if ($distributor_id) {
                $distributorId = '%,'.$distributor_id.',%';
                $company = (new CompanysActivationEgo())->check($filter['company_id']);
                if ($company['product_model'] == 'platform') {
                    $criteria->andWhere($criteria->expr()->like('rel_distributor_ids', $criteria->expr()->literal($distributorId)));
                } else {
                    $criteria->andWhere(
                        $criteria->expr()->orX(
                            $criteria->expr()->like('rel_distributor_ids', $criteria->expr()->literal('all')),
                            $criteria->expr()->like('rel_distributor_ids', $criteria->expr()->literal($distributorId))
                        )
                    );
                }
            } else {
                $criteria->andWhere($criteria->expr()->eq('rel_distributor_ids', $criteria->expr()->literal('all')));
            }
        }

        $askey = ['company_id', 'card_id', 'begin_date' , 'end_date', 'card_type', 'use_platform', 'least_cost'];
        // 需要替换的key
        $kaquanDiscountCardTableKey = [
            "discount_card_source_type" => sprintf("%s.source_type", "kdc"),
            "discount_card_source_id" => sprintf("%s.source_id", "kdc")
        ];
        if (isset($filter['or'])) {
            $orfilter = $filter['or'];
            $orX = [];
            foreach ($orfilter as $key => $filterValue) {
                if (is_array($filterValue)) {
                    array_walk($filterValue, function (&$value) use ($criteria) {
                        $value = $criteria->expr()->literal($value);
                    });
                } elseif (!is_numeric($filterValue)) {
                    $filterValue = $criteria->expr()->literal($filterValue);
                }
                $list = explode('|', $key);
                if (count($list) > 1) {
                    list($v, $k) = $list;
                    $v = in_array($v, $askey) ? "kudc.".$v : $v;
                    $orX[] = $criteria->expr()->$k($v, $filterValue);
                } elseif (is_array($filterValue)) {
                    $orX[] = $criteria->expr()->in($key, $filterValue);
                } else {
                    $orX[] = $criteria->expr()->eq($key, $filterValue);
                }
            }
            if ($orX) {
                $criteria->andWhere($criteria->expr()->orX(...$orX));
            }
        }

        unset($filter['item_id'], $filter['distributor_id'], $filter['or'], $filter['category_id']);
        if ($filter) {
            foreach ($filter as $field => $value) {
                if (in_array($field, $askey)) {
                    $field = "kudc.".$field;
                } elseif (isset($kaquanDiscountCardTableKey[$field])) {
                    $field = $kaquanDiscountCardTableKey[$field];
                }

                $list = explode('|', $field);
                if (count($list) > 1) {
                    list($v, $k) = $list;
                    if (in_array($v, $askey)) {
                        $v = "kudc.".$v;
                    } elseif (isset($kaquanDiscountCardTableKey[$v])) {
                        $v = $kaquanDiscountCardTableKey[$v];
                    }
                    if ($k == 'contains') {
                        $k = 'like';
                    }
                    if ($k == 'like') {
                        $value = '%' . $value . '%';
                    }
                    $criteria = $criteria->andWhere($criteria->expr()->$k($v, $criteria->expr()->literal($value)));
                } elseif (is_array($value)) {
                    array_walk($value, function (&$colVal) use ($criteria) {
                        $colVal = $criteria->expr()->literal($colVal);
                    });
                    $criteria = $criteria->andWhere($criteria->expr()->in($field, $value));
                } else {
                    $criteria = $criteria->andWhere($criteria->expr()->eq($field, $criteria->expr()->literal($value)));
                }
            }
        }
        $count = $criteria->select('count(DISTINCT kudc.id) as count')->execute()->fetch();
        if ($onlyCount) {
            return $count['count'];
        }
        if ($count['count'] <= 0) {
            return ['total_count' => 0, 'list' => []];
        }

        if ($pageSize > 0) {
            $criteria->setFirstResult(($page - 1) * $pageSize)->setMaxResults($pageSize);
        }

        $criteria->orderby('kudc.end_date', 'ASC');
        $criteria->orderby('kudc.id', 'DESC');
        $res['list'] = $criteria->select('DISTINCT kudc.*,kdc.description,kdc.source_type,kdc.source_id')->execute()->fetchAll();
        $res['total_count'] = $count['count'];
        return $res;
    }

    /**
     * 导购员发放优惠券给会员
     * @param $salespersonInfo
     * @param $userIds
     * @param $couponIds
     * @return bool
     */
    public function giveUserCoupons($salespersonInfo, $userIds, $couponIds)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $redisConn = app('redis')->connection('default');
            $couponsSetting = $redisConn->hgetall('coupongrantset'.$salespersonInfo['company_id']);
            $salespersonRelCouponService = new SalespersonRelCouponService();
            $couponCount = $salespersonRelCouponService->count(['company_id' => $salespersonInfo['company_id'], 'coupon_id' => $couponIds]);
            $permissionGrantCoupons = $couponsSetting['coupons'] ?? '';
            $permissionGrantCoupons = explode(',', $permissionGrantCoupons);
            if (count($couponIds) != $couponCount && array_diff($couponIds, $permissionGrantCoupons ?: [])) {
                throw new ResourceException('包含不可发放的优惠券');
            }
            //当前导购员限制周期内已发放的优惠券数量
            if ($couponsSetting['limit_cycle'] === 'week') {
                $start = mktime(0, 0, 0, date('m'), date('d') - date('w') + 1, date('Y'));
                $end = mktime(23, 59, 59, date('m'), date('d') - date('w') + 7, date('Y'));
            } else {
                $start = mktime(0, 0, 0, date('m'), 1, date('Y'));
                $end = mktime(23, 59, 59, date('m'), date('t'), date('Y'));
            }
            $filter = [];
            $filter['give_time|lte'] = $end;
            $filter['give_time|gte'] = $start;
            $filter['salesperson_id'] = $salespersonInfo['salesperson_id'];
            $filter['user_id'] = $userIds;
            $usersGiven = $this->salespersonGiveCouponsRepository->getCountByUserId($filter);
            $giveSum = count($couponIds); //将要发送的优惠券总数
            $givenSum = 0; //已发送的优惠券总数
            foreach ($usersGiven as $value) {
                if ($value['count'] + $giveSum > $couponsSetting['grant_per_user_total']) {
                    throw new ResourceException('超出发放给单个用户优惠券上限');
                }
                $givenSum += $value['count'] + $giveSum;
            }
            if ($givenSum > $couponsSetting['grant_total']) {
                throw new ResourceException('超出可发放优惠券限制');
            }

            //优惠券是否有效及剩余量是否足够
            $filter = [];
            $filter['card_id'] = $couponIds;
            $filter['company_id'] = $salespersonInfo['company_id'];
            $filter['date_type'] = 'DATE_TYPE_FIX_TERM';
            $filter['end_date'] = time();
            $couponsEffective = $this->discountCardsRepository->effectiveFilterLists($filter, [], 10000);
            $tmpArr = [];
            foreach ($couponsEffective['list'] as &$value) {
                $value['get_num'] = $this->getCardGetNum($value['card_id'], $filter['company_id']);
                $tmpArr[] = $value['card_id'];
            }
            $couponsEffectiveArr = array_column($couponsEffective['list'], null, 'card_id');
            unset($value);
            foreach ($couponIds as $value) {
                if (!in_array($value, $tmpArr)) {
                    throw new ResourceException('优惠券不存在或已失效');
                }
                if ($couponsEffectiveArr[$value]['get_num'] + count($userIds) > $couponsEffectiveArr[$value]['quantity']) {
                    throw new ResourceException('优惠券数量不足');
                }
            }

            $now = time();
            $filter = [
                'user_id' => $userIds
            ];
            $usersInfo = $this->memberInfoRepository->lists($filter, [], 1000, 1);
            $logData = [];
            foreach ($couponsEffective['list'] as $value) {
                if (in_array($value['card_id'], $couponIds)) {
                    foreach ($usersInfo['list'] as $user) {
                        //保存发放记录
                        $data = [
                            'company_id' => $salespersonInfo['company_id'],
                            'salesperson_id' => $salespersonInfo['salesperson_id'],
                            'salesperson_name' => $salespersonInfo['salesperson_name'],
                            'user_id' => $user['user_id'],
                            'user_name' => $user['username'],
                            'coupons_id' => $value['card_id'],
                            'coupons_name' => $value['title'],
                            'number' => 1,
                            'give_time' => $now,
                            'status' => 0,
                            'updated' => $now,
                        ];
                        $logResult = $this->salespersonGiveCouponsRepository->create($data);
                        if (!$logResult) {
                            throw new ResourceException('发券失败');
                        }
                        $sendData[] = [
                            'id' => $logResult['id'],
                            'user_id' => $logResult['user_id'],
                            'coupon_id' => $logResult['coupons_id'],
                        ];
                    }
                }
            }
            $conn->commit();

            $promotionActivity = new PromotionActivity();
            $result = $promotionActivity->giveUserCouponsToJob($salespersonInfo['company_id'], $salespersonInfo['salesperson_id'], $sendData);
        } catch (\Exception $exception) {
            $conn->rollback();
            throw $exception;
        }

        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 发放失败优惠券重试发送
     *
     * @param int $id
     * @param int $salespersonInfo
     * @return void
     */
    public function tryGiveUserCoupons($id, $salespersonInfo)
    {
        $result = $this->salespersonGiveCouponsRepository->getInfo(['id' => $id]);
        if (1 == $result['status']) {
            throw new ResourceException('优惠券已发送成功,请勿重新发送');
        }
        $logData[] = $result;
        $promotionActivity = new PromotionActivity();
        $result = $promotionActivity->giveUserCouponsToJob($salespersonInfo, $logData);
        return true;
    }

    /**
     * 获取导购员限制周期内已发放的优惠券数量
     * @param $filter array 限制条件
     */
    public function getSalespersonGivenNum($salespersonInfo, $limit_type)
    {
        //当前导购员限制周期内已发放的优惠券数量
        if ($limit_type === 'week') {
            $start = mktime(0, 0, 0, date('m'), date('d') - date('w') + 1, date('Y'));
            $end = mktime(23, 59, 59, date('m'), date('d') - date('w') + 7, date('Y'));
        } elseif ($limit_type === 'month') {
            $start = mktime(0, 0, 0, date('m'), 1, date('Y'));
            $end = mktime(23, 59, 59, date('m'), date('t'), date('Y'));
        } else {
            return 0;
        }
        $filter = [
            'give_time|lte' => $end,
            'give_time|gte' => $start,
            'status' => 1,
            'salesperson_id' => $salespersonInfo['salesperson_id'],
        ];
        $count = $this->salespersonGiveCouponsRepository->count($filter);

        return $count;
    }

    /**
     * 导购员获取已发放优惠券统计
     * @param $filter
     */
    public function getCouponsRecord($filter)
    {
        $couponList = $this->salespersonGiveCouponsRepository->getCountGroupByCouponsId($filter);
        $couponIds = array_column($couponList, 'coupons_id');
        if ($couponIds) {
            $filter = [
                'card_id' => $couponIds
            ];
            $discountCardsListTemp = $this->discountCardsRepository->getList('*', $filter);
            $discountCardsList = array_column($discountCardsListTemp, null, 'card_id');
            foreach ($couponList as &$coupon) {
                $coupon = array_merge($coupon, $discountCardsList[$coupon['coupons_id']] ?? []);
            }
        }
        return $couponList;
    }

    /**
    * 导购员获取已发放优惠券统计
    * @param $filter
    */
    public function getCouponsRecordV2($filter)
    {
        $salespersonCouponStatisticsService = new SalespersonCouponStatisticsService();
        $couponListTemp = $salespersonCouponStatisticsService->getCountGroupByCouponsId($filter);

        $couponList = array_column($couponListTemp, null, 'coupon_id');
        $couponIds = array_column($couponList, 'coupon_id');
        $filter = [
           'card_id' => $couponIds
       ];
        $discountCardsList = $this->discountCardsRepository->getList('*', $filter);
        foreach ($discountCardsList as &$coupon) {
            $coupon = array_merge($coupon, $couponList[$coupon['card_id']] ?? []);
        }
        return $discountCardsList ?: [];
    }


    /**
     * 获取导购发放优惠券列表
     *
     * @param array $params
     * @param int $page
     * @param int $pageSize
     * @return void
     */
    public function getSalespersonSendCouponsList($params, $page, $pageSize)
    {
        $filter['company_id'] = $params['company_id'];
        $filter['salesperson_id'] = $params['salesperson_id'];
        if (isset($params['status'])) {
            $filter['status'] = $params['status'];
        }
        $result = $this->salespersonGiveCouponsRepository->lists($filter, '*', $page, $pageSize);
        if ($result['list'] ?? []) {
            $userIds = array_column($result['list'], 'user_id');
            //获取微信信息
            $wecFilter = [
                'user_id' => $userIds,
                'company_id' => $params['company_id'],
            ];
            $wechatUserService = new WechatUserService();
            $wechatUsers = $wechatUserService->getWechatUserList($wecFilter);
            $wechatUser = array_column($wechatUsers, null, 'user_id');
            foreach ($result['list'] as &$v) {
                $v['username'] = $v['username'] ?? $wechatUser[$v['user_id']]['nickname'];
            }
        }
        return $result;
    }

    /**
     * 我的优惠券列表-转换条件
     * @param string $filter
     * @return string
     */
    public function myUserCardStatusFilter($filter, $status = '1')
    {
        // 状态 1:可使用、未到使用期优惠券 2:已使用 3:已过期、作废
        switch ($status) {
            case '1':
                // 4：锁定 10: 已使用
                $filter['status'] = [1, 4, 10];
                $filter['end_date|gt'] = time();
                break;
            case '2':
                $filter['status'] = 2;
                break;
            case '3':
                $filter['status'] = [1, 5, 6];
                $filter['end_date|lt'] = time();
                break;
            default:
                # code...
                break;
        }
        return $filter;
    }

    private function crcCode10($code)
    {
        return sprintf("%010d", crc32($code));
    }

    public function exchangeCardInfo($companyId, $userCardId, $userId)
    {
        $filter = [
            'id' => $userCardId,
            'company_id' => $companyId,
            'user_id' => $userId
        ];

        /** @var UserDiscount $userCard */
        $userCard = $this->userDiscountRepository->get($filter);
        if (!$userCard) {
            throw new ResourceException('兑换券不存在');
        }
        if ($userCard->getCardType() != 'new_gift') {
            throw new ResourceException('兑换券类型错误');
        }
        if ($userCard->getStatus() != 10) {
            throw new ResourceException('兑换券状态错误');
        }

        /*        $discountCardService = new KaquanService(new CardService);
                $cardInfo = $discountCardService->getCardInfoById($companyId, $userCard->getCardId());*/

        $distributorServer = new DistributorService();
        $distributorInfo = $distributorServer->getInfoSimple([
            'distributor_id' => $userCard->getRelDistributorIds(),
            'company_id' => $companyId,
        ]);

        $verifyCode = $this->crcCode10($userCard->getCode().$userCard->getRelDistributorIds().$userCard->getRelItemIds());
        $code = 'excode:'.$userCardId.'-'.$verifyCode;

        $dns1d = app('DNS1D')->getBarcodePNG($code, "C93", 1, 70);
        $dns2d = app('DNS2D')->getBarcodePNG($code, "QRCODE", 120, 120);

        return [
            'distributor_info' => $distributorInfo,
            'code' => $verifyCode,
            'barcode_url' => 'data:image/jpg;base64,' . $dns1d,
            'qrcode_url' => 'data:image/jpg;base64,' . $dns2d
        ];
    }

    /**
     * 定时任务取消兑换券锁定商品
     */
    public function scheduleCancelExCard()
    {
        // 最大处理1000条
        for ($i = 0; $i < 5; $i++) {
            $userCardIds = $this->userDiscountRepository->getExpiredCardIds(200);
            foreach ($userCardIds as $card) {
                $id = intval($card['id']);
                $this->userCardTransaction(function () use ($id) {
                    /** @var UserDiscount $userCard */
                    $userCard = $this->userDiscountRepository->get(['id' => $id]);
                    if ($userCard->getStatus() != 10) {
                        return;
                    }
                    // 解锁库存
                    $this->decrbyItemStore($userCard->getCompanyId(), $userCard->getRelItemIds(), $userCard->getRelDistributorIds(), -1);
                    $this->userDiscountRepository->updateUserCard([
                        'rel_item_ids' => '',
                        'rel_distributor_ids' => '',
                        'status' => 1,
                    ], ['id' => $id]);
                });
            }
            if (count($userCardIds) < 200) {
                break;
            }
        }
    }

    /**
     * 店务端核销优惠券
     * @param $companyId
     * @param $userCardId
     * @param $code
     * @param $user
     */
    public function consumeExCard($companyId, $userCardId, $code, $user, $did)
    {
        $filter = [
            'id' => $userCardId,
            'company_id' => $companyId,
        ];
        $orderInfo = $this->userCardTransaction(function () use ($filter, $companyId, $user, $did, $code) {
            /** @var UserDiscount $userCard */
            $userCard = $this->userDiscountRepository->get($filter);
            if (!$userCard) {
                throw new ResourceException('兑换券不存在');
            }
            if ($userCard->getStatus() != 10) {
                throw new ResourceException('兑换券状态错误');
            }
            if ($userCard->getCardType() != 'new_gift') {
                throw new ResourceException('兑换券类型错误');
            }
            $verifyCode = $this->crcCode10($userCard->getCode().$userCard->getRelDistributorIds().$userCard->getRelItemIds());
            if ($verifyCode != $code) {
                throw new ResourceException('兑换码错误');
            }
            // 后续判断: 如果优惠券信息被修改?
            $nowTime = time();
            if ($userCard->getBeginDate() > $nowTime) {
                throw new ResourceException('兑换券核销失败，该兑换券未到使用日期');
            }
            if ($userCard->getEndDate() <= $nowTime) {
                throw new ResourceException('兑换券核销失败，该兑换券已过期');
            }
            $itemId = $userCard->getRelItemIds();
            $distributorId = $userCard->getRelDistributorIds();
            // 判断当前用户是否有核销权限
            /*            $distributor_ids = array_column($user->get('distributor_ids'), 'distributor_id');
                        if ($user->get('operator_type') != 'admin' && !in_array($distributorId, $distributor_ids)) {
                            throw new ResourceException('兑换券核销失败，操作员没有店铺权限');
                        }*/
            // 判断当前店铺是否正确
            if ($did != $distributorId) {
                throw new ResourceException('兑换券核销失败，店铺错误');
            }

            // 解锁库存
            $this->decrbyItemStore($companyId, $itemId, $distributorId, -1);
            // 创建订单
            $memberService = new MemberService();
            $memberInfo = [];
            if ($userCard->getUserId()) {
                $memberInfo = $memberService->getMemberInfo(['user_id' => $userCard->getUserId(), 'company_id' => $companyId]);
            }
            $orderService = new OrderService(new ExcardNormalOrderService());
            $orderParams = [
                'items' => [[
                    'item_id' => $itemId,
                    'num' => 1,
                ]],
                'company_id' => $companyId,
                'user_id' => $userCard->getUserId(),
                'mobile' => $memberInfo['mobile'] ?? '',
                'receipt_type' => '',
                'pay_type' => '',
                'order_type' => 'normal',
                'user_card_id' => $userCard->getId(),
                'distributor_id' => $distributorId,
            ];
            $orderData = $orderService->create($orderParams);
            // 更新订单为已完成
            $updateInfo = [
                'ziti_status' => 'DONE',
                'order_status' => 'DONE',
                'delivery_status' => 'DONE',
                'cancel_status' => 'NO_APPLY_CANCEL',
                'delivery_time' => time(),
                'end_time' => time(),
            ];
            $orderService->update([
                'company_id' => $orderData['company_id'],
                'order_id' => $orderData['order_id'],
            ], $updateInfo);
            // 更新优惠券状态为已完成
            $this->userDiscountRepository->updateUserCard([
                'status' => 2,
            ], $filter);
            // 创建核销日志
            $distributorService = new DistributorService();
            $distributorInfo = $distributorService->getInfoSimple(['company_id' => $companyId, "distributor_id" => $distributorId]);
            $logData = [
                'user_id' => $userCard->getUserId(),
                'company_id' => $companyId,
                'mobile' => $memberInfo['mobile'] ?? '',
                'username' => $memberInfo['username'] ?? '',
                'card_id' => $userCard->getCardId(),
                'code' => $userCard->getCode(),
                'title' => $userCard->getTitle(),
                'card_type' => $userCard->getCardType(),
                'shop_name' => $distributorInfo ? $distributorInfo['name'] : '微商城',
                'used_time' => time(),
                'used_status' => 'consume',
                'used_order' => $orderData['order_id'],
            ];
            $this->userDiscountLogsRepository->create($logData);
            return $orderData;
        });
        return $orderInfo;
    }

    /**
     * 兑换券使用
     * @return bool
     */
    public function exchangeCard($companyId, $userCardId, $itemId, $distributorId, $userId)
    {
        $filter = [
            'id' => $userCardId,
            'company_id' => $companyId,
            'user_id' => $userId
        ];
        $this->userCardTransaction(function () use ($filter, $itemId, $distributorId, $companyId) {
            /** @var UserDiscount $userCard */
            $userCard = $this->userDiscountRepository->get($filter);

            if (!$userCard) {
                throw new ResourceException('兑换券不存在');
            }

            if (!in_array($userCard->getStatus(), [1, 10])) {
                throw new ResourceException('兑换券使用失败，该兑换券已被使用或无效');
            }

            if ($userCard->getCardType() != 'new_gift') {
                throw new ResourceException('兑换券类型错误');
            }

            $nowTime = time();
            if ($userCard->getBeginDate() > $nowTime) {
                throw new ResourceException('兑换券使用失败，该兑换券未到使用日期');
            }
            if ($userCard->getEndDate() <= $nowTime) {
                throw new ResourceException('兑换券使用失败，该兑换券已过期');
            }

            if ($userCard->getRelItemIds() == $itemId && $userCard->getRelDistributorIds() == $distributorId) {
                return true;
            }

            if ($distributorId > 0) {
                $distributorServer = new DistributorService();
                $distributorInfo = $distributorServer->getInfoSimple([
                    'distributor_id' => $distributorId,
                    'company_id' => $companyId,
                ]);
                if (!$distributorInfo) {
                    throw new ResourceException('兑换券使用失败，店铺不存在');
                }
            }

            $discountCardService = new KaquanService(new CardService());
            $cardInfo = $discountCardService->getCardInfoById($companyId, $userCard->getCardId(), [$itemId]);

            $rel_distributor_ids = trim($cardInfo['distributor_id'], ',');
            if ($rel_distributor_ids && !in_array($distributorId, explode(',', $rel_distributor_ids))) {
                throw new ResourceException('兑换券使用失败，不适用的门店');
            }

            $limit = 0;
            if ($cardInfo['use_bound'] != '0') {
                // 有兑换商品限制
                $item = Arr::first($cardInfo['rel_items'], function ($item) use ($itemId) {
                    return $item['item_id'] == $itemId;
                });
                if (!$item) {
                    throw new ResourceException('兑换券使用失败，不适用的商品');
                }
                $limit = $item['use_limit'];
            }

            $userCardId = $userCard->getCardId();

            $lockTime = $cardInfo['lock_time'];
            // item_id 锁，避免商品兑换上限判断错误
            $lockResult = $this->userCardItemLock($companyId, $itemId, function () use ($userCardId, $itemId, $distributorId, $companyId, $limit, $userCard, $lockTime, $filter) {
                if ($limit && $limit <= $this->getCardExchangeItemNum($userCardId, $itemId, $companyId)) {
                    throw new ResourceException('兑换券使用失败，该商品兑换已达到上限');
                }
                // 扣减当前商品库存
                $result = $this->decrbyItemStore($companyId, $itemId, $distributorId);
                if (!$result) {
                    throw new ResourceException('兑换券使用失败，该商品库存已用尽');
                }
                if ($userCard->getStatus() == 10) {
                    // 恢复原商品库存
                    $this->decrbyItemStore($companyId, $userCard->getRelItemIds(), $userCard->getRelDistributorIds(), -1);
                }
                $now = time();
                $expiredTime = $now + $lockTime * 60 * 60;
                if ($userCard->getEndDate() < $expiredTime) {
                    $expiredTime = $userCard->getEndDate();
                }
                $this->userDiscountRepository->updateUserCard([
                    'rel_item_ids' => $itemId,
                    'rel_distributor_ids' => $distributorId,
                    'status' => 10,
                    'used_time' => $now,
                    'expired_time' => $expiredTime,
                ], $filter);
            });
            if (!$lockResult) {
                throw new ResourceException('兑换券使用失败，请稍后重试..');
            }
        });
        return true;
    }

    // 扣减商品库存
    public function decrbyItemStore($companyId, $itemId, $distributorId, $num = 1)
    {
        $distributorItemsService = new DistributorItemsService();
        $distributorItem = $distributorItemsService->getValidDistributorItemSkuInfo($companyId, $itemId, $distributorId);
        $isTotalStore = $distributorItem['is_total_store'] ?? true;
        $itemStoreService = new ItemStoreService();
        return $itemStoreService->minusItemStore($itemId, $num, $distributorId, $isTotalStore);
    }

    /**
     * 商品id锁定
     * @param int $companyId
     * @param int $itemId
     * @param \Closure $func 锁内执行
     * @return bool
     */
    public function userCardItemLock(int $companyId, int $itemId, \Closure $func)
    {
        $key = "lock:discount:item:{$companyId}:{$itemId}";
        return $this->redisLock($key, $func, 6);
    }

    /**
     * 用户卡券执行
     * @param int $companyId
     * @param int $userCardId
     * @param \Closure $func 事务内执行
     * @return bool
     */
    public function userCardTransaction(\Closure $func)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $re = $func();
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            app('log')->debug($e->getMessage());
            throw $e;
        }
        return $re;
    }

    /**
     * redis 分布式锁
     * @param string $key 锁定的key
     * @param \Closure $func 执行逻辑
     * @param int $expire 过期时间(秒)
     * @param int $times 重试次数
     * @param int $sleep 重试间隔(毫秒)
     * @return bool|mixed
     */
    public function redisLock(string $key, \Closure $func, int $expire = 5, int $times = 3, int $sleep = 500)
    {
        for ($i = 0; $i < $times; $i++) {
            $redis = app('redis')->connection('default');
            if ($redis->set($key, 1, 'NX', 'EX', $expire)) {
                try {
                    $re = $func();
                } finally {
                    $redis->del($key);
                }
                return $re ?: true;
            } else {
                usleep($sleep * 1000);
            }
        }
        return false;
    }
}
