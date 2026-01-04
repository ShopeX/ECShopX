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
use Exception;
use KaquanBundle\Entities\CardPackageReceive;
use KaquanBundle\Entities\CardPackageReceiveDetails;
use KaquanBundle\Entities\CardPackageReceiveRecord;
use KaquanBundle\Jobs\ReceivesCardPackage;
use MembersBundle\Services\MemberService;

class PackageReceivesService
{
    public const RECEIVES_STATUS = [
        'in_progress' => 1,
        'success' => 2,
        'fail' => 3,
    ];

    // 优惠券
    private $cardPackageReceiveDetailsRepository;
    private $cardPackageReceiveRepository;
    private $cardPackageReceiveRecordRepository;

    public function __construct()
    {
        $this->cardPackageReceiveDetailsRepository = app('registry')->getManager('default')->getRepository(CardPackageReceiveDetails::class);
        $this->cardPackageReceiveRepository = app('registry')->getManager('default')->getRepository(CardPackageReceive::class);
        $this->cardPackageReceiveRecordRepository = app('registry')->getManager('default')->getRepository(CardPackageReceiveRecord::class);
    }

    /**
     * 用户领取卡券包
     *
     * @param int $companyId
     * @param int $packageId
     * @param int $userId
     * @param string $from
     * @param int $salespersonId
     * @return bool
     * @throws Exception
     */
    public function receivesPackage(int $companyId, int $packageId, int $userId, string $from, int $salespersonId = 0): bool
    {
        $packageDetails = (new PackageQueryService())->getDetails($companyId, $packageId);
        $cards = $packageDetails['discount_cards'];

        // 检测卡卷包他已领过几次
        $filter = [
            'package_id' => $packageId,
            'company_id' => $companyId,
            'user_id' => $userId,
        ];

        $count = $this->cardPackageReceiveRepository->count($filter);
        if ($count >= $packageDetails['limit_count']) {
            throw new ResourceException(trans('KaquanBundle.exceed_limit_count'));
        }

        $checkCardsResult = (new UserDiscountService())->checkCardList($companyId, $userId, $cards, $from);

        $nowTime = time();
        $cardPackageReceive = [
            'package_id' => $packageId,
            'company_id' => $companyId,
            'user_id' => $userId,
            'receive_type' => $from,
            'receive_status' => self::RECEIVES_STATUS['in_progress'],
            'front_show' => 0,
            'receive_time' => $nowTime,
            'success_count' => 0,
            'created' => $nowTime,
            'updated' => $nowTime,
        ];
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $receiveId = $this->cardPackageReceiveRepository->insertGetId($cardPackageReceive);
            foreach ($checkCardsResult as $item) {
                $cardPackageReceiveDetails = [
                    'receive_id' => $receiveId,
                    'package_id' => $packageId,
                    'company_id' => $companyId,
                    'user_id' => $userId,
                    'card_id' => $item['card_info']['card_id'],
                    'message' => $item['message'] ?? "",
                    'receive_status' => $item['success'] ? self::RECEIVES_STATUS['in_progress'] : self::RECEIVES_STATUS['fail'],
                ];
                $this->cardPackageReceiveDetailsRepository->create($cardPackageReceiveDetails);
            }
            $conn->commit();
        } catch (Exception $exception) {
            $conn->rollback();
            throw $exception;
        }

//         todo:FangAolin 同步还是异步发送优惠券
//        $gotoJob = (new ReceivesCardPackage($companyId, $userId, $packageId, $receiveId, $from, $salespersonId))->onQueue('slow');
//        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        $this->sendCouponsToUsers($companyId, $userId, $packageId, $receiveId, $from, $salespersonId);

        return true;
    }

    /**
     * 给当前用户发券
     *
     * @param int $companyId
     * @param int $userId
     * @return string
     */
    public function currentGardCardPackage(int $companyId, int $userId): string
    {
        // 获取付费会员等级
        $vipGradeService = new VipGradeOrderService();
        $vipGrade = $vipGradeService->userVipGradeGet($companyId, $userId);
        // 如果不是付费会员 读取普通会员
        if ($vipGrade['is_vip']) {
            $gradeId = $vipGrade['vip_grade_id'];
            $type = 'vip_grade';
        } else {
            $memberInfo = (new MemberService())->getMemberInfo(['user_id' => $userId, 'company_id' => $companyId]);
            $gradeId = $memberInfo['grade_id'];
            $type = 'grade';
        }
        (new PackageSetService())->triggerPackage($companyId, $userId, $gradeId, $type, false);
        return $type;
    }

    /**
     * 实际给用户发送优惠券
     *
     * @param int $companyId
     * @param int $userId
     * @param int $packageId
     * @param int $receiveId
     * @param string $from
     * @param int $salespersonId
     * @return bool
     * @throws Exception
     */
    public function sendCouponsToUsers(int $companyId, int $userId, int $packageId, int $receiveId, string $from, int $salespersonId): bool
    {
        $where = [
            'company_id' => $companyId,
            'user_id' => $userId,
            'package_id' => $packageId,
            'receive_id' => $receiveId,
            'receive_status' => self::RECEIVES_STATUS['in_progress']
        ];
        $cardPackageReceiveDetails = $this->cardPackageReceiveDetailsRepository->getLists($where, 'id,card_id');

        if (empty($cardPackageReceiveDetails)) {
            // 先更新成失败状态
            $filter = [
                'receive_id' => $receiveId,
                'company_id' => $companyId,
                'user_id' => $userId
            ];
            $updateData = [
                'receive_status' => self::RECEIVES_STATUS['fail'],
                'receive_time' => time(),
            ];
            $this->cardPackageReceiveRepository->updateBy($filter, $updateData);

            return true;
        }

        $cardIds = array_column($cardPackageReceiveDetails, 'card_id');

        $discountCardsWhere = [
            'company_id' => $companyId,
            'card_id' => array_unique($cardIds)
        ];
        $discountCardService = new KaquanService(new DiscountCardService());
        $discountCardsData = $discountCardService->getKaquanList(-1, -1, $discountCardsWhere);
        $discountCardsIndex = array_column($discountCardsData['list'], null, 'card_id');

        foreach ($cardPackageReceiveDetails as $key => $detail) {
            $temp = array_merge($detail, $discountCardsIndex[$detail['card_id']]);
            $temp['receive_id'] = $detail['id'];
            $temp['give_num'] = 1; // 这里已经是分解为一张
            $cardPackageReceiveDetails[$key] = $temp;
        }

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            // 发券
            $userGetResult = (new UserDiscountService())->userGetCardList($companyId, $userId, $cardPackageReceiveDetails, $from, $salespersonId);
            $successUpdateList = [];
            foreach ($userGetResult as $item) {
                if ($item['success']) {
                    $successUpdateList[] = $item['id'];
                } else {
                    $filter = [
                        'receive_id' => $receiveId,
                        'package_id' => $packageId,
                        'company_id' => $companyId,
                        'user_id' => $userId,
                        'card_id' => $item['card_id'],
                        'id' => $item['id']
                    ];

                    $updateData = [
                        'message' => $item['message'],
                        'receive_status' => self::RECEIVES_STATUS['fail'],
                    ];

                    $this->cardPackageReceiveDetailsRepository->updateBy($filter, $updateData);
                }
            }
            if (!empty($successUpdateList)) {
                $filter = [
                    'receive_id' => $receiveId,
                    'package_id' => $packageId,
                    'company_id' => $companyId,
                    'user_id' => $userId,
                    'id' => $successUpdateList,
                ];
                $updateData = [
                    'message' => '',
                    'receive_status' => self::RECEIVES_STATUS['success'],
                ];

                $this->cardPackageReceiveDetailsRepository->updateBy($filter, $updateData);
            }

            $filter = [
                'receive_id' => $receiveId,
                'company_id' => $companyId,
                'user_id' => $userId
            ];

            $updateData = [
                'receive_status' => self::RECEIVES_STATUS['success'],
                'receive_time' => time(),
                'success_count' => count($successUpdateList),
            ];

            $this->cardPackageReceiveRepository->updateBy($filter, $updateData);

            (new PackageEditService())->incrCardPackageGetNum($companyId, $packageId);
            $conn->commit();
        } catch (Exception $exception) {
            $conn->rollback();

            // 发券失败
            $filter = [
                'receive_id' => $receiveId,
                'company_id' => $companyId,
                'user_id' => $userId
            ];
            $updateData = [
                'receive_status' => self::RECEIVES_STATUS['fail'],
                'receive_time' => time(),
            ];
            $this->cardPackageReceiveRepository->updateBy($filter, $updateData);

            throw $exception;
        }

        return true;
    }

    /**
     * 查询未弹框的卡券发放记录
     *
     * receiveType:template 模版领取,vip_grade 会员购买升级, grade 等级升级
     * @param int $companyId
     * @param int $userId
     * @param $receiveType
     * @return array
     */
    public function showCardPackage(int $companyId, int $userId, $receiveType): array
    {
        $where = [
            'company_id' => $companyId,
            'user_id' => $userId,
            'front_show' => 0,
            'receive_type' => $receiveType,
            'receive_status' => [
                self::RECEIVES_STATUS['in_progress'],
                self::RECEIVES_STATUS['success'],
            ]
        ];

        $fields = 'receive_id,package_id,receive_type,receive_status,front_show,receive_time,success_count';
        $cardPackageReceive = $this->cardPackageReceiveRepository->getLists($where, $fields);
        if (empty($cardPackageReceive)) {
            return [
                'receive_record_list' => [],
                'all_card_list' => []
            ];
        }

        $receiveIdList = array_column($cardPackageReceive, 'receive_id');

        $detailsWhere = [
            'receive_id' => empty($receiveIdList) ? 0 : $receiveIdList,
            'company_id' => $companyId,
            'user_id' => $userId,
            'receive_status' => [
                self::RECEIVES_STATUS['in_progress'],
                self::RECEIVES_STATUS['success'],
            ]
        ];

        $fields = 'receive_id,card_id,message,receive_status';
        $details = $this->cardPackageReceiveDetailsRepository->getLists($detailsWhere, $fields);
        if (empty($details)) {
            return [
                'receive_record_list' => [],
                'all_card_list' => []
            ];
        }

        $cardIdList = array_column($details, 'card_id');
        $discountCardsWhere = [
            'company_id' => $companyId,
            'card_id' => array_unique($cardIdList)
        ];
        $discountCardService = new KaquanService(new DiscountCardService());
        $discountCardsData = $discountCardService->getKaquanList(-1, -1, $discountCardsWhere);
        $discountCardsList = $discountCardsData['list'];
        $discountCardsIndex = [];
        foreach ($discountCardsList as $card) {
            if ($card['date_type'] == "DATE_TYPE_FIX_TERM" || $card['date_type'] == DiscountNewGiftCardService::DATE_TYPE_LONG) {
                $begin = $card['begin_day_type'] == 0 ? trans('KaquanBundle.current_day') : $card['begin_day_type'];
                $takeEffect = trans('KaquanBundle.receive_after_effective', ['begin' => $begin, 'term' => $card['fixed_term']]);
            }

            $discountCardsIndex[$card['card_id']] = [
                'takeEffect' => $takeEffect ?? '',
                'card_id' => (int)$card['card_id'],
                'card_type' => (string)$card['card_type'],
                'date_type' => (string)$card['date_type'],
                'description' => (string)$card['description'],
                'begin_date' => date('Y-m-d', $card['begin_date']),
                'end_date' => date('Y-m-d', $card['end_date']),
                'fixed_term' => (int)$card['fixed_term'],
                'quantity' => (int)$card['quantity'],
                'receive' => (string)$card['receive'],
                'title' => (string)$card['title'],
                'kq_status' => (string)$card['kq_status'],
                'grade_ids' => (string)$card['grade_ids'],
                'vip_grade_ids' => (string)$card['vip_grade_ids'],
                'get_limit' => (int)$card['get_limit'],
                'gift' => (string)$card['gift'],
                'default_detail' => (string)$card['default_detail'],
                'discount' => (int)$card['discount'],
                'least_cost' => (int)$card['least_cost'],
                'reduce_cost' => (int)$card['reduce_cost'],
            ];
        }

        $indexReceivesStatus = array_flip(self::RECEIVES_STATUS);
        $detailsCardList = [];
        foreach ($details as $item) {
            $cardInfo = $discountCardsIndex[$item['card_id']];
            $cardInfo['receive_status'] = $indexReceivesStatus[$item['receive_status']];
            $detailsCardList[$item['receive_id']][] = $cardInfo;
        }

        $allCardList = [];
        foreach ($cardPackageReceive as $key => $item) {
            $cardPackageReceive[$key]['receive_time'] = date('Y-m-d H:i:s', $item['receive_time']);
            $cardPackageReceive[$key]['receive_status'] = $indexReceivesStatus[$item['receive_status']];
            $cardPackageReceive[$key]['receive_card_list'] = $detailsCardList[$item['receive_id']] ?? [];
            $allCardList = array_merge($allCardList, $cardPackageReceive[$key]['receive_card_list']);
        }

        $receiveIdList = array_column($cardPackageReceive, 'receive_id');
        $this->confirmPackageReceivesShow($companyId, $userId, $receiveIdList);

        return [
            'receive_record_list' => $cardPackageReceive,
            'all_card_list' => $allCardList
        ];
    }

    /**
     * 得到卡券包发放日志
     *
     * @param int $companyId
     * @param int $packageId
     * @param int $page
     * @param int $pageSize
     * @param bool $needEncode
     * @return array
     */
    public function getPackageReceivesLog(int $companyId, int $packageId, int $page, int $pageSize = 10, bool $needEncode = true): array
    {
        $where = [
            'company_id' => $companyId,
            'package_id' => $packageId,
            'receive_status' => self::RECEIVES_STATUS['success']
        ];

        $fields = 'user_id,receive_type,receive_time';
        $orderBy = ['receive_time' => 'DESC'];
        $packageReceivesLog = $this->cardPackageReceiveRepository->lists($where, $fields, $page, $pageSize, $orderBy);

        $list = $packageReceivesLog['list'];
        $userIdList = array_column($list, 'user_id');
        // 获取 昵称 & 手机号
        $memberService = new MemberService();
        $indexUserMobile = $memberService->getMobileByUserIds($companyId, $userIdList);
        $usernameList = $memberService->getUsernameByUserIds($companyId, $userIdList);
        $indexUsername = array_column($usernameList, 'username', 'user_id');

        $indexReceiveType = [
            'template' => trans('KaquanBundle.template_receive_package'),
            'grade' => trans('KaquanBundle.grade_member_package'),
            'vip_grade' => trans('KaquanBundle.vip_purchase_package')
        ];

        foreach ($list as $key => $item) {
            $list[$key]['username'] = $indexUsername[$item['user_id']] ?? '';
            $list[$key]['mobile'] = $indexUserMobile[$item['user_id']] ?? '';

            if ($needEncode) {
                $list[$key]['username'] = data_masking('truename', (string)$list[$key]['username']);
                $list[$key]['mobile'] = data_masking('mobile', (string)$list[$key]['mobile']);
            }

            $list[$key]['receive_time'] = date('Y-m-d H:i:s', $item['receive_time']);
            $list[$key]['receive_type'] = $indexReceiveType[$item['receive_type']] ?? trans('KaquanBundle.unknown');
        }
        $packageReceivesLog['list'] = $list;

        return $packageReceivesLog;
    }

    /**
     * 确认卡券发送前端已弹框
     *
     * @param int $companyId
     * @param int $userId
     * @param array $receiveIds
     * @return mixed
     */
    public function confirmPackageReceivesShow(int $companyId, int $userId, array $receiveIds)
    {
        $filter = [
            'user_id' => $userId,
            'company_id' => $companyId,
            'receive_id' => $receiveIds
        ];
        return $this->cardPackageReceiveRepository->updateBy($filter, ['front_show' => 1]);
    }

    /**
     * 获取接收记录
     *
     * @param int $companyId
     * @param int $userId
     * @param int $gradeId
     * @param string $triggerType
     * @return mixed
     */
    public function getReceivesRecord(int $companyId, int $userId, int $gradeId, string $triggerType)
    {
        $filter = [
            'company_id' => $companyId,
            'user_id' => $userId,
            'grade_id' => $gradeId,
            'trigger_type' => $triggerType,
        ];
        return $this->cardPackageReceiveRecordRepository->getInfo($filter);
    }

    /**
     * 记录一条接收记录
     *
     * @param int $companyId
     * @param int $userId
     * @param int $gradeId
     * @param string $triggerType
     * @return mixed
     */
    public function addReceivesRecord(int $companyId, int $userId, int $gradeId, string $triggerType)
    {
        $data = [
            'company_id' => $companyId,
            'user_id' => $userId,
            'grade_id' => $gradeId,
            'trigger_type' => $triggerType,
        ];
        return $this->cardPackageReceiveRecordRepository->create($data);
    }

    /**
     * 清除接收记录
     *
     * @param int $companyId
     * @param int $gradeId
     * @param string $triggerType
     * @return mixed
     */
    public function clearReceivesRecord(int $companyId, int $gradeId, string $triggerType)
    {
        $filter = [
            'company_id' => $companyId,
            'grade_id' => $gradeId,
            'trigger_type' => $triggerType,
        ];

        return $this->cardPackageReceiveRecordRepository->deleteBy($filter);
    }
}
