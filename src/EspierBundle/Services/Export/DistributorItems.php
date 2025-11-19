<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Services\Export;

use DistributionBundle\Services\DistributorItemsService;
use DistributionBundle\Services\DistributorService;
use EspierBundle\Interfaces\ExportFileInterface;
use EspierBundle\Services\ExportFileService;
use KaquanBundle\Entities\MemberCardGrade;
use KaquanBundle\Entities\VipGrade;
use KaquanBundle\Services\MemberCardService;
use KaquanBundle\Services\VipGradeService;
use PromotionsBundle\Entities\MemberPrice;

class DistributorItems implements ExportFileInterface
{
    public const MEMBER_PRICE_KEY = 'member_price';//忽略的字段，不导入
    // 标题
    private $title = [
        'item_name' => '名称',
        'item_bn' => '货号',
        'store' => '库存',
        'is_can_sale' => '上下架状态',
        'is_total_store' => '店铺库存',
        'barcode' => '条码',
        'distributor_name' => '店铺名称',
        'price' => '商品价格',
        'market_price' => '市场价',
        'cost_price' => '成本价',
        'member_price' => '会员价', ##会被替换
    ];

    public function exportData($filter)
    {
        $fileName = date('YmdHis') . $filter['company_id'] . "distributorItems";
        $title = $this->getTitle($filter['company_id']);
        $orderList = $this->getLists($filter);
        if (empty($orderList)) {
            return [];
        }
        $exportService = new ExportFileService();
        $result = $exportService->exportCsv($fileName, $title, $orderList);
        return $result;
    }

    private function getLists($filter)
    {
        // 查询店铺名称
        $distributorInfofilter['distributor_id'] = $filter['distributor_id'];
        $distributorInfofilter['company_id'] = $filter['company_id'];
        $distributorService = new DistributorService();
        $distributorInfo = $distributorService->getInfo($distributorInfofilter);

        $distributorItemsService = new DistributorItemsService();
        $data = $distributorItemsService->getDistributorRelItemList($filter, -1, 1);
        if (empty($data['list'])) {
            return [];
        }
        $memberCardGradeRepository = app('registry')->getManager('default')->getRepository(MemberCardGrade::class);
        $memberCardGrade = $memberCardGradeRepository->getListByCompanyId($filter['company_id']);

        $vipGradeRepository = app('registry')->getManager('default')->getRepository(VipGrade::class);
        $vipGrade = $vipGradeRepository->lists(['company_id' => $filter['company_id'],'is_disabled' => 0]);
        $memberPriceRepository = app('registry')->getManager('default')->getRepository(MemberPrice::class);
        $orderList = [];
        foreach ($data['list'] as $k => $v) {
            $exportRow['item_name'] = $v['item_name'];
            $exportRow['item_bn'] = is_numeric($v['item_bn']) ? "\"'".$v['item_bn']."\"" : $v['item_bn'];
            $exportRow['store'] = $v['store'];
            $exportRow['is_can_sale'] = !empty($v['is_can_sale']) ? '是' : '否';
            $exportRow['is_total_store'] = !empty($v['is_total_store']) ? '否' : '是';
            $exportRow['barcode'] = is_numeric($v['barcode']) ? "\"'".$v['barcode']."\"" : $v['barcode'];
            $exportRow['distributor_name'] = $distributorInfo['name'];
            $exportRow['price'] = bcdiv($v['price'], 100, 2);
            $exportRow['market_price'] = bcdiv($v['market_price'], 100, 2);
            $exportRow['cost_price'] = bcdiv($v['cost_price'], 100, 2);
            $promotionPrice = $memberPriceRepository->getInfo(['company_id' => $filter['company_id'],'item_id' => $v['item_id']]);
            $memberCardGradePrice = [];
            $vipGradePrice = [];
            if (!empty($promotionPrice['mprice'])) {
                $arrPromotionPrice = json_decode($promotionPrice['mprice'], true);
                $memberCardGradePrice = $arrPromotionPrice['grade'];
                $vipGradePrice = $arrPromotionPrice['vipGrade'];
            }
            if (!empty($memberCardGrade)) {
                foreach ($memberCardGrade as $key => $value) {
                    $grade_key = 'grade_price'.$value['grade_id'];
                    if (!empty($memberCardGradePrice[$value['grade_id']])) {
                        $exportRow[$grade_key] = bcdiv($memberCardGradePrice[$value['grade_id']], 100, 2);
                    } else {
                        $exportRow[$grade_key] = '';
                    }
                }
            }
            if (!empty($vipGrade)) {
                foreach ($vipGrade as $key => $vipValue) {
                    $vip_grade_key = 'vip_grade_price'.$vipValue['vip_grade_id'];
                    if (!empty($vipGradePrice[$vipValue['vip_grade_id']])) {
                        $exportRow[$vip_grade_key] = bcdiv($vipGradePrice[$vipValue['vip_grade_id']], 100, 2);
                    } else {
                        $exportRow[$vip_grade_key] = '';
                    }
                }
            }
            $orderList[] = $exportRow;
        }

        yield $orderList;
    }

    /**
     * 获取title
     * @param $companyId
     * @return false|string[]
     */
    public function getTitle($companyId)
    {
        return $this->addMemberPriceHeader($companyId);
    }

    /**
     * 增加支持会员价字段导入
     * @param int $companyId
     * @return false|string[]
     */
    public function addMemberPriceHeader($companyId = 0)
    {
        if (!$companyId) {
            return false;
        }

        //获取VIP会员等级
        $vipGradeService = new VipGradeService();
        $vipGrade = $vipGradeService->lists(['company_id' => $companyId, 'is_disabled' => false]);
        if ($vipGrade) {
            $vipGrade = array_column($vipGrade, null, 'vip_grade_id');
        }


        //获取普通会员等级
        $kaquanService = new MemberCardService();
        $userGrade = $kaquanService->getGradeListByCompanyId($companyId, false);
        if ($userGrade) {
            $userGrade = array_column($userGrade, null, 'grade_id');
        }
        $this->_setHeader($userGrade, $vipGrade);

        return $this->title;
    }

    /**
     * 设置会员价导入头信息
     *
     * @param array $userGrade
     * @param array $vipGrade
     */
    private function _setHeader($userGrade = [], $vipGrade = [])
    {
        // TS: 53686f704578
        $newHeader = [];
        foreach ($this->title as $k => $v) {
            if ($k != self::MEMBER_PRICE_KEY) {
                $newHeader[$k] = $v;
                continue;
            }

            foreach ($userGrade as $grade) {
                $gradeKey = 'grade_price'.$grade['grade_id'];
                $newHeader[$gradeKey] = $grade['grade_name'];
            }

            foreach ($vipGrade as $grade) {
                $vipGradeKey = 'vip_grade_price'.$grade['vip_grade_id'];
                $newHeader[$vipGradeKey] = $grade['grade_name'];
            }
        }

        $this->title = $newHeader;
    }
}
