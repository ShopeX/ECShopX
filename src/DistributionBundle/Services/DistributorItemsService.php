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

namespace DistributionBundle\Services;

use CrossBorderBundle\Entities\OriginCountry;
use DistributionBundle\Entities\DistributorItems;
use GoodsBundle\Entities\Items;
use GoodsBundle\Services\ItemsService;
use DistributionBundle\Jobs\AddDistributorItems;
use GoodsBundle\Services\ItemStoreService;
use Dingo\Api\Exception\ResourceException;
use SupplierBundle\Services\SupplierItemsService;
use PromotionsBundle\Traits\CheckPromotionsValid;
use CompanysBundle\Ego\CompanysActivationEgo;
use GoodsBundle\Services\MultiLang\MagicLangTrait;
use GoodsBundle\Services\MultiLang\MultiLangService;

class DistributorItemsService
{
    use CheckPromotionsValid,MagicLangTrait;

    /** @var \DistributionBundle\Repositories\DistributorItemsRepository */
    public $entityRepository;
    public $itemsRepository;
    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(DistributorItems::class);
        $this->itemsRepository = app('registry')->getManager('default')->getRepository(Items::class);
    }

    /**
     * 添加分销商关联商品
     */
    public function createDistributorItems($params, $isQueue = true)
    {
        $res = true;
        $addDistributorItems = (new AddDistributorItems($params))->onQueue('slow');
        if ($isQueue) {
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($addDistributorItems);
        } else {
            $res = app('Illuminate\Contracts\Bus\Dispatcher')->dispatchNow($addDistributorItems);
        }
        return $res;
    }

    // 上架总部商品
    public function relDistributorItem($itemList, $params, $isCanSale = true)
    {
        if (!$itemList['list']) {
            return true;
        }

        $isCanSale = $isCanSale === 'true' || $isCanSale === true;

        $findFilter = [
            'distributor_id' => $params['distributor_id'],
            'item_id|in' => array_column($itemList['list'], 'item_id'),
        ];
        $findRelList = $this->entityRepository->lists($findFilter, ['created' => 'DESC'], -1, 1);
        $findItemIds = [];
        foreach ($findRelList['list'] as $relRow) {
            $findItemIds[$relRow['item_id']] = $relRow['item_id'];
        }

        $itemsService = new ItemsService();
        foreach ($itemList['list'] as $row) {
            $supplier_id = $row['supplier_id'] ?? 0;
            if (!$findItemIds || !in_array($row['item_id'], $findItemIds)) {
                $insert = [
                    'distributor_id' => $params['distributor_id'],
                    'company_id' => $params['company_id'],
                    'shop_id' => 0, // 店铺不再关联门店
                    'item_id' => $row['item_id'],
                    'goods_id' => $row['goods_id'],
                    'price' => $row['price'],
                    'store' => 0,
                    'is_total_store' => $supplier_id ? true : false,//供应商商品必须总部库存
                    'default_item_id' => $row['default_item_id'],
                    'is_show' => $row['default_item_id'] == $row['item_id'],
                    'is_can_sale' => $isCanSale, // 关联则表示为上架
                    'is_self_delivery' => false,   //默认关闭自提配送
                    'is_express_delivery' => false, //默认关闭快递配送
                    'approve_status' => $row['approve_status'] ?? 'instock', // 更新状态
                ];
                $this->entityRepository->create($insert);
            } else {
                $updateData = [
                    'default_item_id' => $row['default_item_id'],
                    'goods_id' => $row['goods_id'],
                    'is_show' => $row['default_item_id'] == $row['item_id'],
                    'approve_status' => $row['approve_status'] ?? 'instock', // 更新状态
                ];
                $this->entityRepository->updateOneBy(['item_id' => $row['item_id'], 'distributor_id' => $params['distributor_id']], $updateData);
            }
        }

        // foreach (array_column($itemList['list'], 'goods_id') as $goodsId) {
        //     $filter = [
        //         'distributor_id' => $params['distributor_id'],
        //         'company_id' => $params['company_id'],
        //         'goods_id' => $goodsId,
        //     ];
        //     $exist = $this->entityRepository->count(array_merge($filter, ['is_can_sale' => true]));
        //     $this->entityRepository->updateBy($filter, ['goods_can_sale' => (bool)$exist]);
        // }

        return true;
    }

    public function updateDistributorItem($params)
    {
        $filter['company_id'] = $params['company_id'];
        $filter['distributor_id'] = $params['distributor_id'];
        if (isset($params['goods_id']) && $params['goods_id']) {
            if (intval($params['goods_id']) && is_numeric(intval($params['goods_id']))) {
                $filter['goods_id'] = $params['goods_id'];
            } else {
                $filter['goods_id'] = json_decode($params['goods_id'], true);
            }
        } elseif (isset($params['item_id']) && $params['item_id']) {
            if (isset($params['is_default'])) {
                $itemsService = new ItemsService();
                $item = $itemsService->getItem(['company_id' => $params['company_id'], 'item_id' => $params['item_id']]);
                $filter['goods_id'] = $item['goods_id'];
            } else {
                $filter['item_id'] = $params['item_id'];
            }
        } else {
            throw new ResourceException(trans('DistributionBundle/Services/DistributorItemsService.select_goods_first'));
        }

        if (isset($params['is_can_sale'])) {
            $params['is_can_sale'] = ($params['is_can_sale'] === 'true' || $params['is_can_sale'] === true);
        }
        if (isset($params['is_total_store'])) {
            $params['is_total_store'] = ($params['is_total_store'] === 'true' || $params['is_total_store'] === true);
        }

        /**
         * 关于停售可售：
        1.供应商商品状态，“停售”和“可售”的互相切换，需要平台审核
        2.“停售”状态审核通过，即变为“停售”，店铺端商品应该下架
        关于库存：
        供应商商品，库存只走“供应商库存”，店铺共享“供应商库存”
         */
        //如果要上架店铺商品，或者设置店铺库存
        if ((isset($params['is_total_store']) && !$params['is_total_store'])) {
            $_filter = ['supplier_id|gte' => 1];
            // 这里进行转换，因为goods_id,或者item_id是平台商品的，不是供应商商品表的
            $itemsService = new ItemsService();
            if (isset($filter['item_id'])) {
                $ImtesAll = $itemsService->getAllItems(['company_id' => $params['company_id'],'item_id' => $filter['item_id']], ['item_id','supplier_item_id']);
                $supplier_item_ids = array_column($ImtesAll, 'supplier_item_id');
                if (!empty($supplier_item_ids)) {
                    $_filter['item_id'] = $supplier_item_ids;
                }
            } else {
                $ImtesAll = $itemsService->getAllItems(['company_id' => $params['company_id'], 'goods_id' => $filter['goods_id']], ['item_id','supplier_item_id']);
                $supplier_item_ids = array_column($ImtesAll, 'supplier_item_id');
                if (!empty($supplier_item_ids)) {
                    $_filter['item_id'] = $supplier_item_ids;
                }
                // $_filter['goods_id'] = $filter['goods_id'];
            }
            $supplierItemsService = new SupplierItemsService();
            $supplierItemCount = $supplierItemsService->repository->count($_filter);
            if ($supplierItemCount) {
                throw new ResourceException(trans('DistributionBundle/Services/DistributorItemsService.supplier_goods_no_stock'));
            }
        }
        //如果要上架店铺商品，或者设置店铺库存
        if (isset($params['is_can_sale']) && $params['is_can_sale']) {
            //onsale 前台可销售，offline_sale 前端不展示，instock 不可销售, only_show 前台仅展示
            $_filter = [
                'supplier_id|gte' => 1,
                // 'approve_status' => ['offline_sale', 'instock', 'only_show'],
                'is_market' => 0,
            ];
             // 这里进行转换，因为goods_id,或者item_id是平台商品的，不是供应商商品表的
            $itemsService = new ItemsService();
            if (isset($filter['item_id'])) {
                $ImtesAll = $itemsService->getAllItems(['company_id' => $params['company_id'],'item_id' => $filter['item_id']], ['item_id','supplier_item_id']);
                $supplier_item_ids = array_column($ImtesAll, 'supplier_item_id');
                if (!empty($supplier_item_ids)) {
                    $_filter['item_id'] = $supplier_item_ids;
                }
            } else {
                $ImtesAll = $itemsService->getAllItems(['company_id' => $params['company_id'], 'goods_id' => $filter['goods_id']], ['item_id','supplier_item_id']);
                $supplier_item_ids = array_column($ImtesAll, 'supplier_item_id');
                if (!empty($supplier_item_ids)) {
                    $_filter['item_id'] = $supplier_item_ids;
                }
                // $_filter['goods_id'] = $filter['goods_id'];
            }
            $supplierItemsService = new SupplierItemsService();
            $supplierItemCount = $supplierItemsService->repository->count($_filter);
            if ($supplierItemCount) {
                throw new ResourceException(trans('DistributionBundle/Services/DistributorItemsService.supplier_goods_not_sellable'));
            }
        }

        // 店铺商品如果要上架，必须商品池是上架状态
        if (isset($params['is_can_sale']) && ($params['is_can_sale'] === true || $params['is_can_sale'] === 'true')) {
            $itemsService = new ItemsService();
            
            if (isset($filter['goods_id'])) {
                // 按SPU维度检查：如果goods_id是数组，遍历每个goods_id
                // 当其中一个goods_id中的所有item_id都是下架状态，则抛出错误
                $goodsIds = is_array($filter['goods_id']) ? $filter['goods_id'] : [$filter['goods_id']];
                
                // 优化：一次性查询所有goods_id下的所有SKU，减少数据库查询次数
                $_filter = [
                    'company_id' => $params['company_id'],
                    'goods_id' => $goodsIds, // 直接传数组，支持批量查询
                ];
                $allItems = $itemsService->getItemsList($_filter, 1, -1);
                
                // 在内存中按goods_id分组统计
                $goodsStats = [];
                foreach ($allItems['list'] as $item) {
                    $goodsId = $item['goods_id'];
                    if (!isset($goodsStats[$goodsId])) {
                        $goodsStats[$goodsId] = ['total' => 0, 'instock' => 0];
                    }
                    $goodsStats[$goodsId]['total']++;
                    if ($item['approve_status'] == 'instock') {
                        $goodsStats[$goodsId]['instock']++;
                    }
                }
                
                // 检查每个goods_id：如果全部SKU都是下架状态，抛出错误
                foreach ($goodsIds as $goodsId) {
                    if (isset($goodsStats[$goodsId]) && $goodsStats[$goodsId]['total'] > 0) {
                        // 如果该SPU下的所有SKU都是下架状态，抛出错误
                        if ($goodsStats[$goodsId]['instock'] == $goodsStats[$goodsId]['total']) {
                            throw new ResourceException("确认商品总部销售状态");
                        }
                    }
                }
            } else if (isset($filter['item_id'])) {
                // 原逻辑：按单个SKU检查
                $_filter = [ 
                    'company_id' => $params['company_id'],
                    'item_id' => $filter['item_id'],
                    'approve_status' => 'instock',
                ];
                $itemCount = $itemsService->count($_filter);
                if ($itemCount) {
                    throw new ResourceException("确认商品总部销售状态");
                }
            }
        }

        $itemList = $this->entityRepository->getList($filter, 'id,distributor_id,item_id,goods_id', 1, -1);

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            //$itemList中的id都拿到一个数组里面，根据这个ids更新 
            $ids = array_column($itemList, 'id');

            $updateData = [];
            if (isset($params['is_can_sale'])) {
                $updateData['is_can_sale'] = $params['is_can_sale'];
            }
            if (isset($params['is_total_store'])) {
                $updateData['is_total_store'] = $params['is_total_store'];
            }
            if (isset($params['store'])) {
                $updateData['store'] = $params['store'];
            }
            if (isset($params['price'])) {
                $updateData['price'] = $params['price'];
            }
            if(!empty($updateData)){
                app('log')->info('[DistributorItemsService][updateDistributorItem] 更新数据start: ' .microtime(true) . ':filter:' . json_encode($filter));
                app('log')->info('[DistributorItemsService][updateDistributorItem] 更新数据start: ' .microtime(true) . ':updatedata:' . json_encode($updateData));
                $this->entityRepository->updateBy($filter, $updateData);
                app('log')->info('[DistributorItemsService][updateDistributorItem] 更新数据DONE: ' .microtime(true) . ':filter:' . json_encode($filter));

            }
        
            foreach ($itemList as $row) {
                // $updateData = [];
                // if (isset($params['is_can_sale'])) {
                //     $updateData['is_can_sale'] = $params['is_can_sale'];
                // }
                // if (isset($params['is_total_store'])) {
                //     $updateData['is_total_store'] = $params['is_total_store'];
                // }
                // if (isset($params['store'])) {
                //     $updateData['store'] = $params['store'];
                // }
                // if (isset($params['price'])) {
                //     $updateData['price'] = $params['price'];
                // }

                // $this->entityRepository->updateOneBy(['id' => $row['id']], $updateData);
                if (isset($updateData['store'])) {
                    $itemStoreService = new ItemStoreService();
                    $itemStoreService->saveItemStore($row['item_id'], $updateData['store'], $row['distributor_id']);
                }
            }

            // foreach (array_column($itemList, 'goods_id') as $goodsId) {
            //     $filter = [
            //         'distributor_id' => $params['distributor_id'],
            //         'company_id' => $params['company_id'],
            //         'goods_id' => $goodsId,
            //     ];
            //     $exist = $this->entityRepository->count(array_merge($filter, ['is_can_sale' => true]));
            //     $this->entityRepository->updateBy($filter, ['goods_can_sale' => (bool)$exist]);
            // }
            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollback();
            throw new ResourceException(trans('DistributionBundle/Services/DistributorItemsService.update_distributor_item_failed'));
        }
    }

    /**
     * 获取店铺发布的总部商品
     *
     * @param $all 获取所有的店铺商品，包含自由商品和总部的商品
     */
    public function getDistributorRelItemList($filter, $pageSize = 1000, $page = 1, $orderBy = ["item_id" => "desc"], $all = false, $column = null)
    {
        $itemsService = new ItemsService();
        $company = (new CompanysActivationEgo())->check($filter['company_id']);
        if ($company['product_model'] == 'standard') {
            $conn = app('registry')->getConnection('default');
            $query = $conn->createQueryBuilder();
            $query->select('count(*) as _count')
            ->from('items');
            // 兼容下可能存在数组$filter['distributor_id'] 2025年5月23日14:57:36 嘉实多
            $distributorId = is_array($filter['distributor_id']) ? intval($filter['distributor_id'][0]) : intval($filter['distributor_id']);
            $filter['distributor_id'] = 0;
            // 2025年9月17日13:58:34 增加如果是虚拟店，就不进行店铺商品关联操作，取总部商品
            $distributorService = new DistributorService();
            $distributorInfo = $distributorService->getInfo(['company_id' => $filter['company_id'], 'distributor_id' => $distributorId]);
            if (!$all && $distributorId > 0 && ($distributorInfo['distributor_self'] === false || $distributorInfo['distributor_self'] === 'false' || $distributorInfo['distributor_self'] === 0 || $distributorInfo['distributor_self'] === '0')) {
                $query->leftJoin('items', 'distribution_distributor_items', 'd_items', 'items.item_id = d_items.item_id');
                $dFilter = [
                    'distributor_id' => $distributorId,
                ];
                if (isset($filter['is_can_sale'])) {
                    $dFilter['goods_can_sale'] = $filter['is_can_sale'];
                    // 店铺上架不需要管总部是否上架, 时间2025年5月23日14:57:36 修改必须店铺是上架，且总部商品也是可售状态才显示
                    // unset($filter['approve_status']);
                }

                foreach ($filter as $key => $value) {
                    list($key0) = explode('|', $key);
                    if ($key0 == 'store') {
                        $dFilter[$key] = $value;
                    }
                }
                $this->_filter($dFilter, $query, 'd_items');
            }
            $filter = $itemsService->_filter($filter);
            $query = $this->_filter($filter, $query, 'items');

            $result['total_count'] = $query->execute()->fetchColumn();
            if ($result['total_count'] > 0) {
                // 排序规则
                foreach ($orderBy as $filed => $val) {
                    $query->addOrderBy('items.'.$filed, $val);
                }
                if ($pageSize > 0) {
                    $query->setFirstResult(($page - 1) * $pageSize)
                       ->setMaxResults($pageSize);
                }

                if (!$column) {
                    $column = 'items.*';
                }
                $column .= ',(CASE items.store WHEN 0 THEN 0 ELSE 1 END) as v_store';
                $result['list'] = $query->select($column)->execute()->fetchAll();

                $result['list'] = $this->getDistributorSkuReplace($filter['company_id'], $distributorId, $result['list'], false);
                $result = $itemsService->replaceSkuSpec($result);
                $service = new MultiLangService();
                $result['list'] = $service->getListAddLang($result['list'],['item_name'],'items',$this->getLang(),'item_id');
                foreach ($result['list'] as $i => $vvv){
                    $result['list'][$i]['itemName'] = $vvv['item_name'] ?? '';
                }
                foreach ($result['list'] as $key => &$v) {
                    $v['item_main_cat_id'] = $v['item_category'] ?? '';
                    $v['item_cat_id'] = $itemsService->getCategoryByItemId($v['item_id'], $v['company_id']);
                }
            } else {
                $result['list'] = [];
                return $result;
            }
        } else {
            $filter = $itemsService->_filter($filter);
            // 只查询总部商品
            $result = $itemsService->getItemsList($filter, $page, $pageSize, $orderBy);
            if ($result['total_count'] === 0) {
                return $result;
            }
        }

        $result['list'] = $this->getorigincountry($result['list'], $filter['company_id']);
        return $result;
    }

    // 获取产地国信息
    private function getorigincountry($list, $company_id)
    {
        $filter['company_id'] = $company_id;
        // 查询内容
        $find = [
            'origincountry_id',
            'origincountry_name',
            'origincountry_img_url',
        ];

        $origincountry = app('registry')->getManager('default')->getRepository(OriginCountry::class)->lists($filter, $find);
        $origincountry_data = array_column($origincountry['list'], null, 'origincountry_id');
        $origincountry_idall = array_column($origincountry['list'], 'origincountry_id');

        foreach ($list as $k => $v) {
            if ($v['type'] != 1 or empty($v['origincountry_id']) or !in_array($v['origincountry_id'], $origincountry_idall)) {
                $list[$k]['origincountry_name'] = '';
                $list[$k]['origincountry_img_url'] = '';
            } else {
                $list[$k]['origincountry_name'] = $origincountry_data[$v['origincountry_id']]['origincountry_name'];
                $list[$k]['origincountry_img_url'] = $origincountry_data[$v['origincountry_id']]['origincountry_img_url'];
            }
        }
        return $list;
    }

    private function _filter($filter, $qb, $alias = '')
    {
        if (isset($filter['or']) && $filter['or']) {
            foreach ($filter['or'] as $key => $filterValue) {
                $list = explode('|', $key);
                if (count($list) > 1) {
                    list($v, $k) = $list;
                    if ($k == 'contains') {
                        $k = 'like';
                        $filterValue = '%'.$filterValue.'%';
                    }
                    if (is_array($filterValue)) {
                        if (!$filterValue) continue;
                        array_walk($filterValue, function (&$colVal) use ($qb) {
                            $colVal = $qb->expr()->literal($colVal);
                        });
                        $orWhere[] = $qb->expr()->$k(($alias ? $alias.'.' : '').$v, $filterValue);
                    } else {
                        if (is_string($filterValue)) {
                            $orWhere[] = $qb->expr()->$k(($alias ? $alias.'.' : '').$v, $qb->expr()->literal($filterValue));
                        } else {
                            $orWhere[] = $qb->expr()->$k(($alias ? $alias.'.' : '').$v, is_bool($filterValue) ? ($filterValue ? 1 : 0) : $filterValue);
                        }
                    }
                } else {
                    if (is_array($filterValue)) {
                        if (!$filterValue) continue;
                        array_walk($filterValue, function (&$colVal) use ($qb) {
                            $colVal = $qb->expr()->literal($colVal);
                        });
                        $orWhere[] = $qb->expr()->in(($alias ? $alias.'.' : '').$key, $filterValue);
                    } else {
                        if (is_string($filterValue)) {
                            $orWhere[] = $qb->expr()->eq(($alias ? $alias.'.' : '').$key, $qb->expr()->literal($filterValue));
                        } else {
                            $orWhere[] = $qb->expr()->eq(($alias ? $alias.'.' : '').$key, is_bool($filterValue) ? ($filterValue ? 1 : 0) : $filterValue);
                        }
                    }
                }
            }
            $qb->andWhere(
                $qb->expr()->orX(...$orWhere)
            );
            unset($filter['or']);
        }

        foreach ($filter as $field => $value) {
            $list = explode('|', $field);
            if (count($list) > 1) {
                list($v, $k) = $list;
                if ($k == 'contains') {
                    $k = 'like';
                    $value = '%'.$value.'%';
                }
                if (is_array($value)) {
                    if (!$value) continue;
                    array_walk($value, function (&$colVal) use ($qb) {
                        $colVal = $qb->expr()->literal($colVal);
                    });
                    $qb = $qb->andWhere($qb->expr()->$k(($alias ? $alias.'.' : '').$v, $value));
                } else {
                    if (is_string($value)) {
                        $qb = $qb->andWhere($qb->expr()->$k(($alias ? $alias.'.' : '').$v, $qb->expr()->literal($value)));
                    } else {
                        $qb = $qb->andWhere($qb->expr()->$k(($alias ? $alias.'.' : '').$v, is_bool($value) ? ($value ? 1 : 0) : $value));
                    }
                }
            } else {
                if (is_array($value)) {
                    if (!$value) continue;
                    array_walk($value, function (&$colVal) use ($qb) {
                        $colVal = $qb->expr()->literal($colVal);
                    });
                    $qb = $qb->andWhere($qb->expr()->in(($alias ? $alias.'.' : '').$field, $value));
                } else {
                    if (is_string($value)) {
                        $qb = $qb->andWhere($qb->expr()->eq(($alias ? $alias.'.' : '').$field, $qb->expr()->literal($value)));
                    } else {
                        $qb = $qb->andWhere($qb->expr()->eq(($alias ? $alias.'.' : '').$field, is_bool($value) ? ($value ? 1 : 0) : $value));
                    }
                }
            }
        }
        return $qb;
    }

    /**
     * 获取单个店铺商品信息
     *
     * @param $companyId 企业ID
     * @param $itemId 商品ID
     * @param $distributorId 店铺ID
     * @param $authorizerAppId 服务号ID，用户生成商品视频素材URL
     */
    public function getValidDistributorItemSkuInfo($companyId, $itemId, $distributorId, $authorizerAppId = null)
    {
        $itemsService = new ItemsService();
        $itemData = $itemsService->getItemsSkuDetail($itemId, $authorizerAppId);

        $distributorItem = $this->entityRepository->getInfo(['company_id' => $companyId, 'item_id' => $itemId, 'distributor_id' => $distributorId]);
        if ($distributorItem) {
            $itemData = $this->replaceItemInfo($itemData, $distributorItem);
            return $itemData;
        } else {
            if ($itemData['distributor_id']) {
                return $itemData['distributor_id'] == $distributorId ? $itemData : [];
            } else {
                return [];
            }
        }
    }

    /**
     * 获取店铺商品信息，如果有多规格，返回多规格信息
     *
     * @param $companyId 企业ID
     * @param $itemId 商品ID
     * @param $distributorId 店铺ID
     * @param $authorizerAppId 服务号ID，用户生成商品视频素材URL
     */
    public function getValidDistributorItemInfo($companyId, $itemId, $distributorId, $authorizerAppId = null, $limitItemIds = array())
    {
        $itemsService = new ItemsService();
        $itemData = $itemsService->getItemsDetail($itemId, $authorizerAppId, $limitItemIds, $companyId);
        if (!$itemData) {
            return [];
        }

        if ($itemData['nospec'] === 'false' || $itemData['nospec'] === false || $itemData['nospec'] === 0 || $itemData['nospec'] === '0') {
            $itemIds = array_column($itemData['spec_items'], 'item_id');
        } else {
            $itemIds = $itemId;
        }

        $distributorService = new DistributorService();
        $info = $distributorService->getInfo(['distributor_id' => $distributorId, 'company_id' => $companyId]);

        $itemData['distributor_id'] = $distributorId;
        $itemData['distributor_info'] = $info;

        $data = $this->entityRepository->lists(['company_id' => $companyId, 'item_id' => $itemIds, 'distributor_id' => $distributorId]);
        // 店铺中未查询到
        if ($data['total_count'] <= 0) {
            // 平台版就直接返回可卖
            $company = (new CompanysActivationEgo())->check($companyId);
            $itemData['distributor_sale_status'] = (($company['product_model'] == 'platform') ? true : false);
            return $itemData;
        }

        // 如果在店铺中查询到，则表示可以店铺售卖
        $itemData['distributor_sale_status'] = true;

        $distributorItems = array_column($data['list'], null, 'item_id');
        // 替换默认商品店铺自定义信息
        if (isset($distributorItems[$itemId])) {
            $itemData = $this->replaceItemInfo($itemData, $distributorItems[$itemId]);
            $itemData['item_total_store'] = $itemData['store'];
        } else {
            $itemData['item_total_store'] = $itemData['store'];
        }

        // 替换多规格店铺商品信息
        if (isset($itemData['spec_items']) && $itemData['spec_items']) {
            $totalStore = 0;
            $itemDataSpecItems = [];
            $approveStatus = [];
            foreach ($itemData['spec_items'] as $key => $row) {
                $row['distributor_id'] = $distributorId;
                if (isset($distributorItems[$row['item_id']])) {
                    $distributorItemInfo = $distributorItems[$row['item_id']] ?? [];
                    $row['item_name'] = $itemData['item_name'];
                    $row = $this->replaceItemInfo($row, $distributorItemInfo);
                } else {
                    $row['store'] = 0;
                    $row['approve_status'] = 'instock';
                }
                $itemDataSpecItems[] = $row;
                $totalStore += $row['store'];
                $approveStatus[] = $row['approve_status'];
            }
            if (in_array('onsale', $approveStatus)) {
                $itemData['approve_status'] = 'onsale';
            } elseif (in_array('only_show', $approveStatus)) {
                $itemData['approve_status'] = 'only_show';
            } elseif (in_array('offline_sale', $approveStatus)) {
                $itemData['approve_status'] = 'offline_sale';
            } else {
                $itemData['approve_status'] = 'instock';
            }
            $itemData['spec_items'] = $itemDataSpecItems;
            $itemData['item_total_store'] = $totalStore;
        }

        return $itemData;
    }

    /**
     * 通过商品表获取到的SKU数据，对店铺自定义参数进行替换
     */
    public function getDistributorSkuReplace($companyId, $distributorId, $skuList, $isReplaceApprove = true)
    {
        $itemId = array_column($skuList, 'item_id');
        $list = $this->entityRepository->lists(['item_id' => $itemId, 'distributor_id' => $distributorId], [], -1, 1);
        if ($list['total_count'] === 0) {
            return $skuList;
        }

        $distributorItemList = array_column($list['list'], null, 'item_id');
        foreach ($skuList as &$row) {
            // 获取门店库存
            $row["distributor_store"] = (int)($distributorItemList[$row['item_id']]["store"] ?? -1);
            //如果为自动发布总部商品，那么在关联表中如果查询不到，那么默认是上架的
            if (isset($distributorItemList[$row['item_id']])) {
                $row = $this->replaceItemInfo($row, $distributorItemList[$row['item_id']], $isReplaceApprove);
            } else {
                $row = $this->replaceItems($row);
                $row['is_can_sale'] = false;
                $row['goods_can_sale'] = false; //商品是否上架，已有一个sku上架那么商品则上架了
                $row['is_total_store'] = false; //自动同步总部商品默认总部发货
                if ($isReplaceApprove) {
                    $row['approve_status'] = 'instock';
                }
            }
            $row['distributor_id'] = $distributorId;
        }
        return $skuList;
    }

    /**
     * 将店铺自定义信息，替换商品基础信息
     *
     * @param array $itemInfo 商品信息
     * @param array $distributorItemInfo 店铺商品关联自定义的信息
     * @param boolean $isReplaceApprove 是否需要替换商品上下架状态
     */
    private function replaceItemInfo($itemInfo, $distributorItemInfo, $isReplaceApprove = true)
    {
        if (!$distributorItemInfo) {
            return $itemInfo;
        }
        // 替换商品库存
        // 是否总部发货 不是总部发货则使用店铺库存
        if (!$distributorItemInfo['is_total_store'] || $distributorItemInfo['is_total_store'] === 'false') {
            if ($itemInfo['approve_status'] == 'onsale') {
                $itemInfo['logistics_store'] = $itemInfo['store']; //门店库存不足从总部发货
            }

            $itemInfo['store'] = $distributorItemInfo['store'];
            $itemInfo['price'] = $distributorItemInfo['price'];
        }

        // 是否店铺将商品下架，如果商品店铺是已经上架的那么则按照商品基础信息为准
        if ((!$distributorItemInfo['is_can_sale'] || $distributorItemInfo['is_can_sale'] === 'false') && $isReplaceApprove) {
            $itemInfo['approve_status'] = 'instock';
        }

        if (($distributorItemInfo['is_can_sale'] || $distributorItemInfo['is_can_sale'] === 'true') && (!$distributorItemInfo['is_total_store'] || $distributorItemInfo['is_total_store'] === 'false') && $isReplaceApprove) {
            $itemInfo['approve_status'] = 'onsale';
        }

        $itemInfo['goods_can_sale'] = $distributorItemInfo['goods_can_sale'];
        $itemInfo['is_can_sale'] = $distributorItemInfo['is_can_sale'];
        $itemInfo['distributor_id'] = $distributorItemInfo['distributor_id'];
        $itemInfo['is_total_store'] = $distributorItemInfo['is_total_store'];

        $itemInfo = $this->replaceItems($itemInfo);
        return $itemInfo;
    }

    private function replaceItems($itemInfo)
    {
        // 兼容老数据
        $itemInfo['itemId'] = $itemInfo['item_id'];
        $itemInfo['consumeType'] = $itemInfo['consume_type'] ?? '';
        $itemInfo['itemName'] = $itemInfo['item_name'] ?? '';
        $itemInfo['itemBn'] = $itemInfo['item_bn'] ?? '';
        $itemInfo['companyId'] = $itemInfo['company_id'] ?? '';
        // 规格转成bool
        $itemInfo['nospec'] = (isset($itemInfo['nospec']) && ($itemInfo['nospec'] === 'true' || $itemInfo['nospec'] === true || $itemInfo['nospec'] === 1 || $itemInfo['nospec'] === '1')) ? true : false;
        if (isset($itemInfo['pics']) && !is_array($itemInfo['pics'])) {
            $itemInfo['pics'] = json_decode($itemInfo['pics'], true);
        }

        return $itemInfo;
    }

    public function getDistributorSkuItemsList($filter, $page = 1, $pageSize = 2000, $orderBy = ['item_id' => 'DESC'])
    {
        $companyId = $filter['company_id'];
        $distributorId = $filter['distributor_id'];

        $conn = app('registry')->getConnection('default');
        $em = $conn->createQueryBuilder();
        $em = $em->select('count(*) as _count')
                 ->from('distribution_distributor_items', 'd')
                 ->leftJoin('d', 'items', 'i', 'd.item_id = i.item_id');

        // 处理条件加上表名前缀
        foreach ($filter as $key => $val) {
            if ($key == 'or') {
                foreach ($val as $k => $v) {
                    if ($k == 'distributor_id') {
                        $filter['or']['d.' . $k] = $v;
                    } else {
                        $filter['or']['i.' . $k] = $v;
                    }
                    unset($filter['or'][$k]);
                }
            } else {
                if ($key == 'distributor_id') {
                    $filter['d.' . $key] = $val;
                } else {
                    $filter['i.' . $key] = $val;
                }
                unset($filter[$key]);
            }
        }
        $filter['d.is_can_sale'] = 1;
        unset($filter['i.approve_status']);
        $em = $this->_filter($filter, $em);
        $em = $em->andWhere($em->expr()->isNotNull('i.item_id'));
        $result['total_count'] = $em->execute()->fetchColumn();
        if ($result['total_count'] > 0) {
            // 排序规则
            foreach ($orderBy as $filed => $val) {
                $em->addOrderBy('i.' . $filed, $val);
            }
            if ($pageSize > 0) {
                $em->setFirstResult(($page - 1) * $pageSize)
                   ->setMaxResults($pageSize);
            }

            $result['list'] = $em->select('i.*')->execute()->fetchAll();
            $result['list'] = $this->getDistributorSkuReplace($companyId, $distributorId, $result['list'], true);
        } else {
            $result['list'] = [];
        }

        return $result;
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }
}
