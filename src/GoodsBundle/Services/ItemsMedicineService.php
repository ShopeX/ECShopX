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

use CompanysBundle\Services\SettingService;
use CrossBorderBundle\Entities\OriginCountry;
use CrossBorderBundle\Services\Taxstrategy as Strategy;
use DistributionBundle\Entities\Distributor;
use EspierBundle\Services\Upload\UploadService;
use EspierBundle\Services\UploadToken\UploadTokenAbstract;
use EspierBundle\Services\UploadTokenFactoryService;
use GoodsBundle\Entities\Items;
use GoodsBundle\Entities\ItemsBarcode;
use GoodsBundle\Entities\ItemRelAttributes;
use GoodsBundle\Entities\ItemsMedicine;
use GoodsBundle\Events\ItemBatchEditStatusEvent;
use GoodsBundle\Jobs\MedicineItemsSubmitAudit;
use GoodsBundle\Repositories\ItemsMedicineRepository;
use KaquanBundle\Entities\RelItems;

use Dingo\Api\Exception\ResourceException;
use GoodsBundle\Events\ItemCreateEvent;
use GoodsBundle\Events\ItemAddEvent;
use GoodsBundle\Events\ItemDeleteEvent;
use MerchantBundle\Services\MerchantService;
use WechatBundle\Services\OpenPlatform;
use OrdersBundle\Services\RightsService;
use OrdersBundle\Services\Rights\TimesCardService;
use DistributionBundle\Services\DistributorItemsService;
use DistributionBundle\Services\DistributorService;

use PromotionsBundle\Services\PromotionGroupsActivityService;
use PromotionsBundle\Services\PromotionItemTagService;

use CompanysBundle\Services\ArticleService;
use CompanysBundle\Services\SettingService as CompanysSettingService;

use WechatBundle\Services\Material as MaterialService;
use PromotionsBundle\Services\MemberPriceService;
use MembersBundle\Services\MemberService;
use KaquanBundle\Services\VipGradeService;
use KaquanBundle\Services\VipGradeOrderService;
use WechatBundle\Services\WeappService;
use OrdersBundle\Services\ShippingTemplatesService;

use PointBundle\Services\PointMemberRuleService;
use PromotionsBundle\Traits\CheckPromotionsValid;
use PromotionsBundle\Services\MarketingActivityService;
use KaquanBundle\Services\MemberCardService;

class ItemsMedicineService
{
    /**
     * @var \GoodsBundle\Repositories\ItemsRepository
     */
    public $itemsRepository;
    private $distributorRepository;
    /** @var $itemsMedicineRepository ItemsMedicineRepository */
    private $itemsMedicineRepository;

    /**
     * ItemsService 构造函数.
     */
    public function __construct()
    {
        $this->itemsRepository = app('registry')->getManager('default')->getRepository(Items::class);
        $this->distributorRepository = app('registry')->getManager('default')->getRepository(Distributor::class);
        $this->itemsMedicineRepository = app('registry')->getManager('default')->getRepository(ItemsMedicine::class);
    }

    /**
     * 获取商品药品数据
     * @param $items
     * @return mixed
     */
    public function getItemsMedicineData($items)
    {
        // Powered by ShopEx EcShopX
        $medicineItemIds = [];
        foreach ($items as $itemData) {
            if (isset($itemData['is_medicine']) && $itemData['is_medicine'] == 1) {
                if (($itemData['nospec'] === false || $itemData['nospec'] === 'false' || $itemData['nospec'] === 0 || $itemData['nospec'] === '0') && !empty($itemData['spec_items'])) { // 多规格
                    foreach ($itemData['spec_items'] as $spec_item) {
                        $medicineItemIds[] = $spec_item['item_id'];
                    }
                } else {
                    $medicineItemIds[] = $itemData['item_id'];
                }
            }
        }

        if (!empty($medicineItemIds)) {
            $medicineList = $this->itemsMedicineRepository->getLists([
                'item_id' => $medicineItemIds,
                'item_type' => 'normal',
            ], '*', 1, -1);
            $medicineList = array_column($medicineList, null, 'item_id');
            foreach ($items as &$itemData) {
                if ($itemData['is_medicine'] == 1) {
                    if (isset($medicineList[$itemData['item_id']])) {
                        $itemData['medicine_data'] = $medicineList[$itemData['item_id']];
                    }

                    if (($itemData['nospec'] === false || $itemData['nospec'] === 'false' || $itemData['nospec'] === 0 || $itemData['nospec'] === '0') && !empty($itemData['spec_items'])) { // 多规格商品 每个规格的药品数据
                        foreach ($itemData['spec_items'] as &$spec_item) {
                            $spec_item['max_num'] = $medicineList[$spec_item['item_id']]['max_num'];
                        }
                        unset($spec_item);
                    } else if (($itemData['nospec'] === true || $itemData['nospec'] === 'true' || $itemData['nospec'] === 1 || $itemData['nospec'] === '1')) { // 单规格，medicine_spec药品规格数据返回
                        $itemData['medicine_spec'] = $medicineList[$itemData['item_id']]['spec'];
                    }
                }
            }
            unset($itemData);
        }

        return $items;
    }

    /**
     * 同步药品数据
     * @param $params
     * @return true[]
     */
    public function syncMedicine($params)
    {
        $items = $this->itemsRepository->getLists(['goods_id' => $params['goods_id']]);
        if (empty($items)) {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.item_not_exists'));
        }

        $itemIds = array_column($items, 'item_id');
        $medicines = $this->itemsMedicineRepository->getLists([
            'item_id' => $itemIds,
            'item_type' => 'normal'
        ]);
        $medicines = array_column($medicines, null, 'item_id');
        foreach ($items as $item) {
            if (!$item['is_medicine']) {
                throw new ResourceException(trans('GoodsBundle/Controllers/Items.item_not_medicine', ['{0}' => $item['item_name']]));
            }
            if (!isset($medicines[$item['item_id']])) {
                throw new ResourceException(trans('GoodsBundle/Controllers/Items.item_medicine_data_missing', ['{0}' => $item['item_name']]));
            }
            if ($medicines[$item['item_id']]['audit_status'] == 2) {
                throw new ResourceException(trans('GoodsBundle/Controllers/Items.item_medicine_audit_passed', ['{0}' => $item['item_name']]));
            }

            $item['medicine_data'] = $medicines[$item['item_id']];

            $gotoJob = (new MedicineItemsSubmitAudit($item))->onQueue('slow');
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        }

        $this->itemsMedicineRepository->updateBy([
            'item_id' => $itemIds,
            'item_type' => 'normal'
        ], [
            'audit_status' => 1,
        ]);

        return ['success' => true];
    }

    public function updateItemMedicineData($params, $medicineData, $itemsResult)
    {
        if ($medicineData['is_prescription'] == 1 && $itemsResult['approve_status'] != 'instock') {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.prescription_drug_requires_audit_to_shelve'));
        }

        if ($medicineData['is_prescription'] == 1 && empty($params['medicine_spec'])) {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.please_fill_medicine_spec'));
        }
        $medicineData['spec'] = $params['medicine_spec'];
        $medicineData['item_id'] = $itemsResult['item_id'];

        $medicineInfo = $this->itemsMedicineRepository->getInfo([
            'item_id' => $itemsResult['item_id'],
            'item_type' => $medicineData['item_type']
        ]);
        if (empty($medicineInfo)) {
            $medicineResult = $this->itemsMedicineRepository->create($medicineData);
        } else {
            $medicineResult = $this->itemsMedicineRepository->updateOneBy([
                'item_id' => $itemsResult['item_id'],
                'item_type' => $medicineData['item_type']
            ], $medicineData);
        }
        $itemsResult['medicine_data'] = $medicineResult;

        if ($medicineResult['audit_status'] > 0) {
            $gotoJob = (new MedicineItemsSubmitAudit($itemsResult))->onQueue('slow');
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        }

        return $itemsResult;
    }

    // 药品数据
    public function commonMedicineData($params, $itemType)
    {
        $rules = [
            'medicine_type' => ['required', '药品分类不能为空'],
            'common_name' => ['required', '通用名不能为空'],
            'manufacturer' => ['required', '生产厂家不能为空'],
            'approval_number' => ['required', '批准文号不能为空'],
            'unit' => ['required', '最小售卖单位不能为空'],
            'is_prescription' => ['required', '是否为处方药不能为空'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $params['is_prescription'] = $params['is_prescription'] > 0 ? 1 : 0;
        if ($params['is_prescription'] == 1) {
            if (empty($params['use_tip'])) {
                throw new ResourceException(trans('GoodsBundle/Controllers/Items.prescription_drug_usage_tips_required'));
            }
            if (empty($params['symptom'])) {
                throw new ResourceException(trans('GoodsBundle/Controllers/Items.prescription_drug_symptoms_required'));
            }
        }

        return [
            'company_id' => $params['company_id'],
            'medicine_type' => $params['medicine_type'],
            'common_name' => $params['common_name'],
            'dosage' => $params['dosage'] ?? '',
//            'spec' => $params['spec'], // 规格数据在sku上
            'packing_spec' => $params['packing_spec'] ?? '',
            'manufacturer' => $params['manufacturer'],
            'approval_number' => $params['approval_number'],
            'unit' => $params['unit'],
            'is_prescription' => (int)$params['is_prescription'],
            'special_common_name' => $params['special_common_name'] ?? '',
            'special_spec' => $params['special_spec'] ?? '',
            'audit_status' => $params['is_prescription'] >= 1 ? 1 : 0, // 非处方药不审核
            'item_type' => $itemType,
            'use_tip' => $params['use_tip'] ?? '',
            'symptom' => $params['symptom'] ?? '',
            'max_num' => $params['max_num'] ?? 0,
        ];
    }

    /**
     * Dynamically call the KaquanService instance.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->itemsMedicineRepository->$method(...$parameters);
    }
}
