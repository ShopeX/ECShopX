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

use DistributionBundle\Services\DistributorItemsService;
use GoodsBundle\Services\MultiLang\MultiLangService;
use PromotionsBundle\Entities\RecommendLike;
use GoodsBundle\Services\ItemsService;
use GoodsBundle\Services\ItemTaxRateService;
use CompanysBundle\Ego\CompanysActivationEgo;

class RecommendLikeService
{
    public $entityRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(RecommendLike::class);
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
        return $this->entityRepository->$method(...$parameters);
    }

    public function saveRecommendLikeData($companyId, $params)
    {
        foreach ($params['items'] as $item) {
            $insertData = [
                'item_id' => $item['item_id'],
                'sort' => $item['sort'],
                'distributor_id' => $item['distributor_id'] ?? 0,
                'company_id' => $companyId,
            ];
            $filter['company_id'] = $companyId;
            $filter['item_id'] = $item['item_id'];
            $result[] = $this->entityRepository->updateOneBy($filter, $insertData);
        }
        return $params;
    }

    public function getListData($filter, $page, $pageSize, $orderBy = [])
    {
        $company = (new CompanysActivationEgo())->check($filter['company_id']);
        $filter['distributor_id'] = $filter['distributor_id'] ?? 0;

        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('count(*) as _count')->from('promotions_recommend_like_items', 'r_items')
           ->leftJoin('r_items', 'items', 'items', 'r_items.item_id = items.item_id')
           ->where($qb->expr()->eq('r_items.company_id', $filter['company_id']));
        if ($company['product_model'] == 'standard' && $filter['distributor_id']) {
            $qb->leftJoin('r_items', 'distribution_distributor_items', 'd_items', 'r_items.item_id = d_items.item_id')
               ->andWhere($qb->expr()->eq('d_items.distributor_id', $filter['distributor_id']));
            if (isset($filter['is_can_sale']) && $filter['is_can_sale']) {
                $qb->andWhere($qb->expr()->eq('d_items.is_can_sale', 1));
            }
            $columns = 'd_items.distributor_id,d_items.price,d_items.store';
        } else {
            if (isset($filter['is_can_sale']) && $filter['is_can_sale']) {
                $qb->andWhere($qb->expr()->eq('items.approve_status', $qb->expr()->literal('onsale')));
            }
            $columns = 'items.distributor_id,items.price,items.store';
        }

        $result['total_count'] = $qb->execute()->fetchColumn();

        if ($result['total_count'] > 0) {
            // 排序规则
            foreach ($orderBy as $filed => $val) {
                $qb->addOrderBy('r_items.'.$filed, $val);
            }
            if ($pageSize > 0) {
                $qb->setFirstResult(($page - 1) * $pageSize)
                   ->setMaxResults($pageSize);
            }

            $columns .= ',r_items.company_id,r_items.id,r_items.sort,items.item_id,items.item_name,items.item_name as itemName,items.brief,items.market_price,items.nospec,items.type,items.approve_status,items.pics';
            $result['list'] = $qb->select($columns)->execute()->fetchAll();
        } else {
            $result['list'] = [];
            return $result;
        }

        $itemsService = new ItemsService();
        $result = $itemsService->getItemsListMemberPrice($result, $filter['user_id'] ?? 0, $filter['company_id']);

        //营销标签
        $result = $itemsService->getItemsListActityTag($result, $filter['company_id']);

        $ItemTaxRateService = new ItemTaxRateService($filter['company_id']);
        foreach ($result['list'] as $key => $value) {
            $result['list'][$key]['pics'] = json_decode($value['pics'], true);

            // 判断是否跨境，如果是，获取税费税率
            if ($value['type'] == '1') {
                $tax_calculation = 'price';                       // 计税
                $tax_calculation_price = $value['price'];         // 计税价格

                // 是否有会员价格，如果有覆盖计税价格
                if ($value['member_price'] ?? 0) {
                    $tax_calculation = 'member_price';                   // 计税
                    $tax_calculation_price = $value['member_price'];
                }
                // 是否有活动价格，如果有覆盖计税价格
                if ($value['activity_price'] ?? 0) {
                    $tax_calculation = 'activity_price';                   // 计税
                    $tax_calculation_price = $value['activity_price'];
                }

                $ItemTaxRate = $ItemTaxRateService->getItemTaxRate($value['item_id'], $tax_calculation_price);      // 税率信息
                $cross_border_tax = bcdiv(bcdiv(bcmul($tax_calculation_price, bcmul($ItemTaxRate['tax_rate'], 100)), 100), 100);  // 税费计算
                $result['list'][$key]['cross_border_tax'] = $cross_border_tax;  // 税费
                $result['list'][$key]['cross_border_tax_rate'] = $ItemTaxRate['tax_rate'];  // 税率
                $result['list'][$key][$tax_calculation] = bcadd($value[$tax_calculation], $cross_border_tax); // 含税价格(列表显示的价格)
                if ($tax_calculation == 'activity_price') {
                    $result['list'][$key]['promotion_activity'][count($result['list'][$key]['promotion_activity']) - 1]['activity_price'] = $result['list'][$key][$tax_calculation];
                }
            } else {
                $result['list'][$key]['cross_border_tax'] = 0;  // 税费
                $result['list'][$key]['cross_border_tax_rate'] = 0; // 税率
            }
        }

        $result['list'] = (new MultiLangService())->translateItemList($result['list']);

        return $result;
    }
}
