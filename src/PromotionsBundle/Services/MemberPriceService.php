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

namespace PromotionsBundle\Services;

use PromotionsBundle\Entities\MemberPrice;
use GoodsBundle\Services\ItemsService;
use DistributionBundle\Services\DistributorItemsService;
use DistributionBundle\Services\DistributorService;
use Dingo\Api\Exception\ResourceException;

use KaquanBundle\Services\VipGradeService;
use KaquanBundle\Services\MemberCardService;

class MemberPriceService
{
    /**
     * memberPrice Repository类
     */
    public $memberPriceRepository = null;

    public function __construct()
    {
        $this->memberPriceRepository = app('registry')->getManager('default')->getRepository(MemberPrice::class);
    }

    public function saveMemberPrice($params)
    {
        $mprice = json_decode($params['mprice'], 1);

        if (!$mprice) {
            throw new ResourceException(trans('PromotionsBundle.member_price_data_error'));
        }

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();

        try {
            $itemIds = array_keys($mprice);
            //清除已存在的会员价信息
            $this->deleteMemberPrice(['item_id' => $itemIds, 'company_id' => $params['company_id']]);

            $memberPrice = new MemberPrice();
            $itemsService = new ItemsService();
            foreach ($mprice as $itemId => $val) {
                if (!$itemId) {
                    continue;
                }
                foreach ($val['grade'] as $gkey => $gval) {
                    if (is_numeric($gval) && $gval <= 0) {
                        throw new ResourceException(trans('PromotionsBundle.member_price_must_greater_than_zero'));
                    }

                    $val['grade'][$gkey] = bcmul($gval, 100);
                    $this->checkMemberPrice($params['company_id'], $itemId, bcmul($gval, 100));
                }
                foreach ($val['vipGrade'] as $gkey => $gval) {
                    if (is_numeric($gval) && $gval <= 0) {
                        throw new ResourceException(trans('PromotionsBundle.member_price_must_greater_than_zero'));
                    }

                    $val['vipGrade'][$gkey] = bcmul($gval, 100);
                    $this->checkMemberPrice($params['company_id'], $itemId, bcmul($gval, 100));
                }
                $saveData = [
                    'company_id' => $params['company_id'],
                    'item_id' => $itemId,
                    'mprice' => json_encode($val),
                ];

                $result = $this->memberPriceRepository->create($saveData);

                // 加入起订量
                if (isset($val['start_num']) && $val['start_num'] > 0) {
                   $res = $itemsService->updateStartNum($itemId, $val['start_num']);
                }
                
                if (!$result) {
                    throw new ResourceException(trans('PromotionsBundle.save_member_price_failed'));
                }
            }
            $conn->commit();
            return [];
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    private function checkMemberPrice($companyId, $itemId, $mprice)
    {
        $itemsService = new ItemsService();
        if ($itemsService->count(['company_id' => $companyId, 'item_id' => $itemId, 'price|lt' => $mprice])) {
            throw new ResourceException(trans('PromotionsBundle.member_price_cannot_higher_than_sale_price'));
        }

        $distributorService = new DistributorService();
        $distributor = $distributorService->getLists(['company_id' => $companyId, 'is_valid|neq' => 'delete'], 'distributor_id');
        if ($distributor) {
            $distributorItemsService = new DistributorItemsService();
            $distributorItem = $distributorItemsService->lists(['company_id' => $companyId, 'distributor_id' => array_column($distributor, 'distributor_id'), 'item_id' => $itemId, 'price|lt' => $mprice, 'is_total_store' => false], ["created" => "DESC"], 1, 1);
            if ($distributorItem['total_count'] > 0) {
                $distributor = $distributorService->getInfo(['company_id' => $companyId, 'distributor_id' => $distributorItem['list'][0]['distributor_id']]);
                throw new ResourceException('会员价格不能比门店销售价高（店铺号：'.($distributor['shop_code'] ?? '').'）');
            }
        }
    }

    //获取货品会员价列表
    public function getMemberPriceList($filter, $offset = 0, $limit = -1, $orderBy = ['item_id' => 'DESC'])
    {
        $itemsService = new ItemsService();

        $itemInfo = $itemsService->getInfo(['company_id' => $filter["company_id"], 'item_id' => $filter['item_id']]);
        if (!$itemInfo) {
            throw new ResourceException(trans('PromotionsBundle.product_get_failed'));
        }

        //获取SKU信息
        if ($itemInfo['nospec'] === false || $itemInfo['nospec'] === 'false' || $itemInfo['nospec'] === 0 || $itemInfo['nospec'] === '0') {
            $filter['default_item_id'] = $filter['item_id'];
            unset($filter['item_id']);
        }

        $itemList = $itemsService->getSkuItemsList($filter);
        $itemIds = array_column($itemList['list'], 'item_id');

        //获取会员价格
        $memberPriceList = $this->memberPriceRepository->lists(['item_id' => $itemIds, 'company_id' => $filter['company_id']]);
        $memberPriceList = array_column($memberPriceList['list'], null, 'item_id');

        //获取VIP会员等级
        $vipGradeService = new VipGradeService();
        $vipGrade = $vipGradeService->lists(['company_id' => $filter['company_id'], 'is_disabled' => false]);
        $vipGrade = array_column($vipGrade, null, 'vip_grade_id');

        //获取普通会员等级
        $kaquanService = new MemberCardService();
        $grade = $kaquanService->getGradeListByCompanyId($filter['company_id'], false);
        $grade = array_column($grade, null, 'grade_id');

        $memberGrade = [];
        foreach ($vipGrade as $vkey => $vval) {
            if (!$vval) {
                continue;
            }
            $memberGrade['vipGrade'][$vkey]['vip_grade_id'] = $vval['vip_grade_id'];
            $memberGrade['vipGrade'][$vkey]['grade_name'] = $vval['grade_name'];
            $memberGrade['vipGrade'][$vkey]['lv_type'] = $vval['lv_type'];
            $memberGrade['vipGrade'][$vkey]['mprice'] = '';
        }

        foreach ($grade as $gkey => $gval) {
            if (!$gval) {
                continue;
            }
            $memberGrade['grade'][$gkey]['vip_grade_id'] = $gval['grade_id'];
            $memberGrade['grade'][$gkey]['grade_name'] = $gval['grade_name'];
            $memberGrade['grade'][$gkey]['mprice'] = '';
        }

        foreach ($itemList['list'] as $ikey => $ival) {
            if (!isset($memberPriceList[$ival['item_id']]['mprice'])) {
                $itemList['list'][$ikey]['memberGrade'] = $memberGrade;
                continue;
            }

            $memberPriceList[$ival['item_id']]['mprice'] = json_decode($memberPriceList[$ival['item_id']]['mprice'], 1);
            foreach ($memberPriceList[$ival['item_id']]['mprice'] as $mkey => $mval) {
                foreach ($mval as $gmkey => $gmval) {
                    if (!isset($memberGrade[$mkey][$gmkey])) {
                        continue;
                    }
                    $memberGrade[$mkey][$gmkey]['mprice'] = $gmval;
                }
            }

            $itemList['list'][$ikey]['memberGrade'] = $memberGrade;
        }

        return $itemList;
    }

    // 删除会员价
    public function deleteMemberPrice($filter)
    {
        return $this->memberPriceRepository->deleteBy($filter);
    }

    /**
     * Dynamically call the shopsservice instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->memberPriceRepository->$method(...$parameters);
    }
}
