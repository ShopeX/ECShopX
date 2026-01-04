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
use KaquanBundle\Entities\CardPackage;
use KaquanBundle\Entities\CardPackageItems;

class PackageQueryService
{
    // 卡券包
    private $cardPackage;
    // 卡券包关联
    private $cardPackageItems;

    public function __construct()
    {
        $this->cardPackage = app('registry')->getManager('default')->getRepository(CardPackage::class);
        $this->cardPackageItems = app('registry')->getManager('default')->getRepository(CardPackageItems::class);
    }

    /**
     * 获取卡券包列表
     *
     * @param int $companyId
     * @param int $page
     * @param int $pageSize
     * @param string $title
     * @return array
     */
    public function getList(int $companyId, int $page, int $pageSize, string $title = ''): array
    {
        $where = [
            'company_id' => $companyId,
            'row_status' => 1
        ];

        if ($title) {
            $where['title|like'] = addslashes(trim($title));
        }

        $orderBy = [
            'created' => 'DESC'
        ];

        $fields = 'package_id,title,package_describe,limit_count,get_num';

        return $this->cardPackage->lists($where, $fields, $page, $pageSize, $orderBy);
    }


    /**
     * 根据ID列表获取卡券包信息
     *
     * @param int $companyId
     * @param array $packageIdList
     * @return array
     */
    public function getListByIdList(int $companyId, array $packageIdList): array
    {
        if (empty($packageIdList)) {
            return [];
        }

        $where = [
            'company_id' => $companyId,
            'row_status' => 1,
            'package_id' => $packageIdList,
        ];

        $fields = 'package_id,title,package_describe,limit_count,get_num';
        return $this->cardPackage->getLists($where, $fields);
    }

    /**
     * 获取卡券详情
     *
     * @param int $companyId
     * @param int $packageId
     * @return array
     */
    public function getDetails(int $companyId, int $packageId): array
    {
        $where = [
            'company_id' => $companyId,
            'package_id' => $packageId,
            'row_status' => 1
        ];

        $details = $this->cardPackage->getInfo($where);
        if (empty($details)) {
            throw new ResourceException(trans('KaquanBundle.package_not_found'));
        }

        $discountCards = $this->_getDiscountCardsByPackageList($companyId, [$packageId]);

        return [
            "package_id" => (int)$details['package_id'],
            "company_id" => (int)$details['company_id'],
            "title" => (string)$details['title'],
            "package_describe" => (string)$details['package_describe'],
            "limit_count" => (int)$details['limit_count'],
            "get_num" => (int)$details['get_num'],
            "discount_cards" => $discountCards
        ];
    }


    public function checkCardPackageGradeLimit(int $companyId, int $packageId, string $setType, int $checkGradeId): array
    {
        $packageDetails = $this->getDetails($companyId, $packageId);
        $cards = $packageDetails['discount_cards'];

        if ($setType == 'grade') {
            $gradeList = (new MemberCardService())->getCompanyGradeSimpleList($companyId);
            $gradeListIndex = array_column($gradeList, null, 'grade_id');
            $gradeName = $gradeListIndex[$checkGradeId]['grade_name'] ?? '';
        } else {
            $vipGradeList = (new VipGradeService())->lists(['company_id' => $companyId]);
            $vipGradeIndex = array_column($vipGradeList, null, 'vip_grade_id');
            $gradeName = $vipGradeIndex[$checkGradeId]['grade_name'] ?? '';
        }


        $checkResult = [];
        foreach ($cards as $item) {
            $reKey = $packageDetails['title'].'_'.$item['card_id'];
            // 等级限制
            if (!empty($item['grade_ids'])) {
                $gradeIds = $item['grade_ids'] ? explode(',', trim($item['grade_ids'], ',')) : [];

                foreach ($gradeIds as $value) {
                    // 当前设置不在
                    if ($setType != 'grade' || $value != $checkGradeId) {
                        $checkResult[$reKey] = [
                            'package_title' => $packageDetails['title'],
                            'title' => $item['title'],
                            'grade_id' => $checkGradeId,
                            'grade_name' => $gradeName
                        ];
                    }
                }
            }

            // 会员等级限制
            if (!empty($item['vip_grade_ids'])) {
                $vipGradeIds = $item['vip_grade_ids'] ? explode(',', trim($item['vip_grade_ids'], ',')) : [];

                foreach ($vipGradeIds as $value) {
                    // 当前设置不在
                    if ($setType != 'vip_grade' || $value != $checkGradeId) {
                        $checkResult[$reKey] = [
                            'package_title' => $packageDetails['title'],
                            'title' => $item['title'],
                            'grade_id' => $checkGradeId,
                            'grade_name' => $gradeName
                        ];
                    }
                }
            }
        }

        return array_values($checkResult);
    }


    /**
     * 获取绑定上的卡券信息
     *
     * @param int $companyId
     * @param int $gradeId
     * @param string $type
     * @return array
     */
    public function getCardListByBindType(int $companyId, int $gradeId, string $type): array
    {
        $bindPackageList = (new PackageSetService())->getBindPackage($companyId, $gradeId, $type);
        return $this->_getDiscountCardsByPackageList($companyId, $bindPackageList);
    }


    /**
     * 通过卡券包id列表获取卡券信息
     *
     * @param int $companyId
     * @param array $packageList
     * @return array
     */
    private function _getDiscountCardsByPackageList(int $companyId, array $packageList): array
    {
        if (empty($packageList)) {
            return [];
        }

        $where = [
            'package_id' => $packageList,
            'row_status' => 1
        ];
        $packageIdDbList = $this->cardPackage->getLists($where, 'package_id', -1, -1);
        $packageList = array_column($packageIdDbList, 'package_id');
        if (empty($packageList)) {
            return [];
        }

        $where = [
            'company_id' => $companyId,
            'package_id' => $packageList,
        ];
        $packageItemsLists = $this->cardPackageItems->getLists($where, 'card_id,give_num', -1, -1);

        $packageItemsIndex = [];
        foreach ($packageItemsLists as $item) {
            if (isset($packageItemsIndex[$item['card_id']])) {
                $packageItemsIndex[$item['card_id']] += $item['give_num'];
            } else {
                $packageItemsIndex[$item['card_id']] = $item['give_num'];
            }
        }

        $discountCardsWhere = [
            'company_id' => $companyId,
            'card_id' => array_keys($packageItemsIndex)
        ];
        $discountCardService = new KaquanService(new DiscountCardService());
        $discountCardsData = $discountCardService->getKaquanList(-1, -1, $discountCardsWhere);
        $discountCardsList = $discountCardsData['list'];

        $discountCards = [];
        foreach ($discountCardsList as $card) {
            if ($card['date_type'] == "DATE_TYPE_FIX_TERM" || $card['date_type'] == DiscountNewGiftCardService::DATE_TYPE_LONG) {
                $begin = $card['begin_day_type'] == 0 ? trans('KaquanBundle.current_day') : $card['begin_day_type'];
                $takeEffect = trans('KaquanBundle.receive_after_effective', ['begin' => $begin, 'term' => $card['fixed_term']]);
            }


            $discountCards[] = [
                'give_num' => (int)$packageItemsIndex[$card['card_id']],
                'takeEffect' => $takeEffect ?? '',
                'card_id' => (int)$card['card_id'],
                'title' => (string)$card['title'],
                'card_type' => (string)$card['card_type'],
                'date_type' => (string)$card['date_type'],
                'description' => (string)$card['description'],
                'begin_date' => date('Y-m-d H:i:s', $card['begin_date']),
                'end_date' => date('Y-m-d H:i:s', $card['end_date']),
                'begin_time' => (string)$card['begin_date'],
                'end_time' => (string)$card['end_date'],
                'fixed_term' => (int)$card['fixed_term'],
                'quantity' => (int)$card['quantity'],
                'receive' => (string)$card['receive'],
                'kq_status' => (string)$card['kq_status'],
                'grade_ids' => (string)$card['grade_ids'],
                'vip_grade_ids' => (string)$card['vip_grade_ids'],
                'get_limit' => (int)$card['get_limit'],
                'gift' => (string)$card['gift'],
                'default_detail' => (string)$card['default_detail'],
                'discount' => (int)$card['discount'],
                'least_cost' => (int)$card['least_cost'],
                'reduce_cost' => (int)$card['reduce_cost'],
                'get_num' => (int)$card['get_num'],
                'lock_time' => (int)$card['lock_time'],
                'deal_detail' => (string)$card['deal_detail'],
                'accept_category' => (string)$card['accept_category'],
                'reject_category' => (string)$card['reject_category'],
                'object_use_for' => (string)$card['object_use_for'],
                'can_use_with_other_discount' => (string)$card['can_use_with_other_discount'],
                'use_platform' => (string)$card['use_platform'],
                'use_bound' => (int)$card['use_bound'],
                'send_begin_time' => (int)$card['send_begin_time'],
                'send_end_time' => (int)$card['send_begin_time'],
            ];
        }

        return $discountCards;
    }
}
