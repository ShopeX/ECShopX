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

namespace GoodsBundle\Services;

use CrossBorderBundle\Entities\CrossBorderSet;
use CrossBorderBundle\Services\Taxstrategy as Strategy;
use GoodsBundle\Entities\Items;
use PromotionsBundle\Entities\MemberPrice;
use GoodsBundle\Entities\ItemsCategory;

class ItemTaxRateService
{
    public $company_id;
    public $item_id;

    /**
     * ItemsTagsService 构造函数.
     */
    public function __construct($company_id)
    {
        $this->company_id = $company_id;
    }

    // 获取商品税率
    public function getItemTaxRate($item_id = '', $price = 0)
    {
        $this->item_id = $item_id;
        $ItemInfo = $this->getItemInfo();
        if (empty($this->company_id)) {
            $this->company_id = $ItemInfo['companyId'];
        }

        // 判断是否为跨境商品
        if ($ItemInfo['type'] != '1') {
            return [
                'item_id' => $ItemInfo['item_id'],
                'type' => $ItemInfo['type'],
                'tax_rate' => 0
            ];
        }

        // 判断是否有规则税率
        if (!empty($ItemInfo['taxstrategy_id'])) {
            if (empty($price)) {
                $price = $ItemInfo['price'];
            }
            $Taxstrategy_tax_rate = $this->getTaxstrategy_tax_rate($ItemInfo['taxstrategy_id'], $ItemInfo['taxation_num'], $price, $ItemInfo['company_id'], 1);
            if ($Taxstrategy_tax_rate != 0) {
                return [
                    'item_id' => $ItemInfo['item_id'],
                    'type' => $ItemInfo['type'],
                    'tax_rate' => $Taxstrategy_tax_rate
                ];
            }
        }

        // 判断商品是否有税率
        if (!empty($ItemInfo['crossborder_tax_rate'])) {
            return [
                'item_id' => $ItemInfo['item_id'],
                'type' => $ItemInfo['type'],
                'tax_rate' => $ItemInfo['crossborder_tax_rate']
            ];
        }

        // 判断主类目是否有税率
        $CategoryInfo = $this->getCategoryInfo($ItemInfo['item_category']);
        if (!empty($CategoryInfo['crossborder_tax_rate'])) {
            return [
                'item_id' => $ItemInfo['item_id'],
                'type' => $ItemInfo['type'],
                'tax_rate' => $CategoryInfo['crossborder_tax_rate']
            ];
        }

        // 判断全局是否有税率
        $CrossBorder = $this->getCrossBorder();
        if (!empty($CrossBorder['tax_rate'])) {
            return [
                'item_id' => $ItemInfo['item_id'],
                'type' => $ItemInfo['type'],
                'tax_rate' => $CrossBorder['tax_rate']
            ];
        } else {
            return [
                'item_id' => $ItemInfo['item_id'],
                'type' => $ItemInfo['type'],
                'tax_rate' => 0
            ];
        }
    }

    // 获取商品的相关信息
    private function getItemInfo()
    {
        if (!empty($this->company_id)) {
            $filter['company_id'] = $this->company_id;
        }
        $filter['item_id'] = $this->item_id;
        // 商品信息
        $ItemInfo = app('registry')->getManager('default')->getRepository(Items::class)->getInfo($filter);
        // 商品会员价格(暂时无用)
//        $promotionsMemberPrice = app('registry')->getManager('default')->getRepository(MemberPrice::class)->getInfo($filter);
//        if (!empty($promotionsMemberPrice)) {
//            $ItemInfo['member_price'] = json_decode($promotionsMemberPrice['mprice'], true);
//        } else {
//            $ItemInfo['member_price'] = [];
//        }
        return $ItemInfo;
    }

    // 获取主类目信息
    private function getCategoryInfo($item_category)
    {
        $filter['company_id'] = $this->company_id;
        $filter['category_id'] = $item_category;
        return app('registry')->getManager('default')->getRepository(ItemsCategory::class)->getInfo($filter);
    }

    // 获取全局税率
    private function getCrossBorder()
    {
        $filter['company_id'] = $this->company_id;
        return app('registry')->getManager('default')->getRepository(CrossBorderSet::class)->getInfo($filter);
    }

    // 获取跨境税费规则中的税费
    public function getTaxstrategy_tax_rate($taxstrategy_id, $taxation_num, $taxable_fee, $company_id, $num)
    {
        // 单价
        $Price = bcdiv($taxable_fee, $num, 0);
        // 单位份数为0 ，税费也为0
        if (empty($taxation_num)) {
            return 0;
        }
        // 单份计税价格
        $OnePrice = bcdiv(bcdiv($Price, $taxation_num, 2), 100, 2);

        $taxstrategy_tax_rate = 0;
        $filter['id'] = $taxstrategy_id;
        $filter['company_id'] = $company_id;
//        $filter['state'] = 1;    // 不考虑策略当前状态是否删除
        $Strategy = new Strategy();
        $data = $Strategy->getInfo($filter);
        // 判断是否有规则
        if (!empty($data)) {
            $taxstrategy_content = $data['taxstrategy_content'];
            foreach ($taxstrategy_content as $k => $v) {
                // 判断是否符合当前规则
                if ($v['start'] < $OnePrice and $OnePrice <= $v['end']) {
                    $taxstrategy_tax_rate = $v['tax_rate'];
                    break;
                }
            }
        }
        return $taxstrategy_tax_rate;
    }
}
