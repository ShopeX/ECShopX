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

namespace OrdersBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use GoodsBundle\Services\MultiLang\MagicLangTrait;
use GoodsBundle\Services\MultiLang\MultiLangService;
use OrdersBundle\Entities\NormalOrdersItems;
use Doctrine\Common\Collections\Criteria;
use Dingo\Api\Exception\UpdateResourceFailedException;
use Dingo\Api\Exception\ResourceException;

class NormalOrdersItemsRepository extends EntityRepository
{
    use MagicLangTrait;
    public $table = 'orders_normal_orders_items';

    public function create($params)
    {
        $normalOrdersItemsEntity = new NormalOrdersItems();
        $normalOrdersItems = $this->setNormalOrdersItemsData($normalOrdersItemsEntity, $params);

        $em = $this->getEntityManager();
        $em->persist($normalOrdersItems);
        $em->flush();

        $result = $this->getNormalOrdersItemsData($normalOrdersItems);

        return $result;
    }

    public function update($filter, $updateInfo)
    {
        $order = $this->findOneBy($filter);
        if (!$order) {
            throw new UpdateResourceFailedException("订单商品不存在");
        }
        $normalOrdersItems = $this->setNormalOrdersItemsData($order, $updateInfo);
        $em = $this->getEntityManager();
        $em->persist($normalOrdersItems);
        $em->flush();

        return true;
    }

    /**
     * 更新多条数数据
     *
     * @param $filter 更新的条件
     * @param $data 更新的内容
     */
    public function updateBy(array $filter, array $data)
    {
        // 0x456353686f7058
        $entityList = $this->findBy($filter);
        if (!$entityList) {
            throw new ResourceException("未查询到更新数据");
        }

        $em = $this->getEntityManager();
        $result = [];
        foreach ($entityList as $entityProp) {
            $entityProp = $this->setNormalOrdersItemsData($entityProp, $data);
            $em->persist($entityProp);
            $em->flush();
            $result[] = $this->getNormalOrdersItemsData($entityProp);
        }
        return $result;
    }

    public function getList($filter, $offset = 0, $limit = -1, $orderBy = ['create_time' => 'DESC'])
    {
        $criteria = Criteria::create();
        if ($filter) {
            if (isset($filter['aftersales_status']) && $filter['aftersales_status'] == 'null') {
                $criteria = $criteria->andWhere(Criteria::expr()->isNull('aftersales_status'));
                unset($filter['aftersales_status']);
            }
            foreach ($filter as $field => $value) {
                $list = explode('|', $field);
                if (count($list) > 1) {
                    list($v, $k) = $list;
                    $criteria = $criteria->andWhere(Criteria::expr()->$k($v, $value));
                    continue;
                } elseif (is_array($value)) {
                    $criteria = $criteria->andWhere(Criteria::expr()->in($field, $value));
                } else {
                    $criteria = $criteria->andWhere(Criteria::expr()->eq($field, $value));
                }
            }
        }

        $total = $this->getEntityManager()
            ->getUnitOfWork()
            ->getEntityPersister($this->getEntityName())
            ->count($criteria);
        $res['total_count'] = intval($total);

        $prderList = [];
        if ($res['total_count']) {
            $criteria = $criteria->orderBy($orderBy);
            if ($limit > 0) {
                $criteria = $criteria->setFirstResult($offset)
                                     ->setMaxResults($limit);
            }
            $list = $this->matching($criteria);
            foreach ($list as $v) {
                $order = $this->getNormalOrdersItemsData($v);
                $prderList[] = $order;
            }
        }
        $res['list'] = $prderList;
        $multiLangService = new MultiLangService();
        $res['list'] = $multiLangService->getListAddLang($res['list'],['item_name'],'items',$this->getLang(),'item_id');
        //侵入式处理itemSpec
        $res['list'] = $this->replaceSpecDesc($res['list']);
        return $res;
    }

    public function get($companyId, $orderId, $supplier_id = 0)
    {
        // 0x456353686f7058
        $filter = [
            'company_id' => $companyId,
            'order_id' => $orderId
        ];
        if ($supplier_id) {
            $filter['supplier_id'] = $supplier_id;
        }
        $data = $this->findBy($filter);
        $result = [];
        foreach ($data as $v) {
            $result[] = $this->getNormalOrdersItemsData($v);
        }
        //转换
        $multiLangService = new MultiLangService();
        $result = $multiLangService->getListAddLang($result,['item_name'],'items',$this->getLang(),'item_id');

        $result = $this->replaceSpecDesc($result);
     
        return $result;
    }

    public function getRow($filter)
    {
        $data = $this->findOneBy($filter);
        $result = [];
        if ($data) {
            $result = $this->getNormalOrdersItemsData($data);
        }
        return $result;
    }

    public function count($filter)
    {
        $criteria = Criteria::create();

        if (isset($filter['aftersales_status']) && $filter['aftersales_status'] == 'null') {
            $criteria = $criteria->andWhere(Criteria::expr()->isNull('aftersales_status'));
            unset($filter['aftersales_status']);
        }
        foreach ($filter as $field => $value) {
            $list = explode("|", $field);
            if (count($list) > 1) {
                list($v, $k) = $list;
                $criteria = $criteria->andWhere(Criteria::expr()->$k($v, $value));
                continue;
            } elseif (is_array($value)) {
                $criteria = $criteria->andWhere(Criteria::expr()->in($field, $value));
            } else {
                $criteria = $criteria->andWhere(Criteria::expr()->eq($field, $value));
            }
        }

        $total = $this->getEntityManager()
                ->getUnitOfWork()
            ->getEntityPersister($this->getEntityName())
            ->count($criteria);

        return intval($total);
    }

    private function setNormalOrdersItemsData($orderItemsEntity, $data)
    {
        /**
         * @var \OrdersBundle\Entities\NormalOrdersItems $orderItemsEntity
         */
        if (isset($data['order_id'])) {
            $orderItemsEntity->setOrderId($data['order_id']);
        }
        if (isset($data['goods_id'])) {
            $orderItemsEntity->setGoodsId($data['goods_id']);
        }
        if (isset($data['item_id'])) {
            $orderItemsEntity->setItemId($data['item_id']);
        }
        if (isset($data['item_bn'])) {
            $orderItemsEntity->setItemBn($data['item_bn']);
        }
        if (isset($data['goods_bn'])) {
            $orderItemsEntity->setGoodsBn($data['goods_bn']);
        }
        if (isset($data['item_name'])) {
            $orderItemsEntity->setItemName($data['item_name']);
        }
        if (isset($data['company_id'])) {
            $orderItemsEntity->setCompanyId($data['company_id']);
        }
        if (isset($data['user_id'])) {
            $orderItemsEntity->setUserId($data['user_id']);
        }
        if (isset($data['act_id'])) {
            $orderItemsEntity->setActId($data['act_id']);
        }
        if (isset($data['pic'])) {
            $orderItemsEntity->setPic($data['pic']);
        }
        if (isset($data['num'])) {
            $orderItemsEntity->setNum($data['num']);
        }
        if (isset($data['price'])) {
            $orderItemsEntity->setPrice($data['price']);
        }
        if (isset($data['cost_price'])) {
            $orderItemsEntity->setCostPrice($data['cost_price']);
        }
        if (isset($data['templates_id'])) {
            $orderItemsEntity->setTemplatesId($data['templates_id']);
        }
        if (isset($data['total_fee'])) {
            $orderItemsEntity->setTotalFee($data['total_fee']);
        }
        if (isset($data['market_price'])) {
            $orderItemsEntity->setMarketPrice($data['market_price']);
        }
        if (isset($data['rebate'])) {
            $orderItemsEntity->setrebate($data['rebate']);
        }
        if (isset($data['total_rebate'])) {
            $orderItemsEntity->setTotalRebate($data['total_rebate']);
        }
        if (isset($data['item_fee'])) {
            $orderItemsEntity->setItemFee($data['item_fee']);
        }
        if (isset($data['cost_fee'])) {
            $orderItemsEntity->setCostFee($data['cost_fee']);
        }
        if (isset($data['item_unit'])) {
            $orderItemsEntity->setItemUnit($data['item_unit']);
        }
        if (isset($data['member_discount'])) {
            $orderItemsEntity->setMemberDiscount($data['member_discount']);
        }
        if (isset($data['coupon_discount'])) {
            $orderItemsEntity->setCouponDiscount($data['coupon_discount']);
        }
        if (isset($data['discount_fee'])) {
            $orderItemsEntity->setDiscountFee($data['discount_fee']);
        }
        if ($data['discount_info'] ?? []) {
            $orderItemsEntity->setDiscountInfo(json_encode($data['discount_info']));
        }

        if (isset($data['add_service_info'])) {
            $orderItemsEntity->setAddServiceInfo(json_encode($data['add_service_info']));
        }

        if (isset($data['coupon_discount_desc'])) {
            $orderItemsEntity->setCouponDiscountDesc(json_encode($data['coupon_discount_desc']));
        }
        if (isset($data['member_discount_desc'])) {
            $orderItemsEntity->setMemberDiscountDesc(json_encode($data['member_discount_desc']));
        }

        if (isset($data['shop_id'])) {
            $orderItemsEntity->setShopId($data['shop_id']);
        }
        if (isset($data['is_total_store'])) {
            $orderItemsEntity->setIsTotalStore($data['is_total_store']);
        }
        if (isset($data['distributor_id'])) {
            $orderItemsEntity->setDistributorId($data['distributor_id']);
        }

        if (isset($data['delivery_corp'])) {
            $orderItemsEntity->setDeliveryCorp($data['delivery_corp']);
        }
        if (isset($data['delivery_code'])) {
            $orderItemsEntity->setDeliveryCode($data['delivery_code']);
        }
        if (isset($data['delivery_img'])) {
            $orderItemsEntity->setDeliveryImg($data['delivery_img']);
        }
        if (isset($data['delivery_time'])) {
            $orderItemsEntity->setDeliveryTime($data['delivery_time']);
        }
        if (isset($data['delivery_status'])) {
            $orderItemsEntity->setDeliveryStatus($data['delivery_status']);
        }
        if (isset($data['aftersales_status'])) {
            $orderItemsEntity->setAftersalesStatus($data['aftersales_status']);
        }
        if (isset($data['refunded_fee'])) {
            $orderItemsEntity->setRefundedFee($data['refunded_fee']);
        }
        if (isset($data['fee_type']) && $data['fee_type']) {
            $orderItemsEntity->setFeeType($data['fee_type']);
        }
        if (isset($data['fee_rate']) && $data['fee_rate']) {
            $orderItemsEntity->setFeeRate($data['fee_rate']);
        }
        if (isset($data['fee_symbol']) && $data['fee_symbol']) {
            $orderItemsEntity->setFeeSymbol($data['fee_symbol']);
        }
        if (isset($data['item_point'])) {
            $orderItemsEntity->setItemPoint($data['item_point']);
        }
        if (isset($data['point'])) {
            $orderItemsEntity->setPoint($data['point']);
        }
        if (($data['item_spec_desc'] ?? '') && $data['item_spec_desc']) {
            $orderItemsEntity->setItemSpecDesc($data['item_spec_desc']);
        }
        if (($data['order_item_type'] ?? '') && $data['order_item_type']) {
            $orderItemsEntity->setOrderItemType($data['order_item_type']);
        }
        if (isset($data['volume'])) {
            $orderItemsEntity->setVolume($data['volume']);
        }
        if (isset($data['weight'])) {
            $orderItemsEntity->setWeight($data['weight']);
        }

        if (isset($data['is_rate'])) {
            $orderItemsEntity->setIsRate($data['is_rate']);
        }
        if (isset($data['auto_close_aftersales_time'])) {
            $orderItemsEntity->setAutoCloseAftersalesTime($data['auto_close_aftersales_time']);
        }
        if (isset($data['type'])) {
            $orderItemsEntity->setType($data['type']);
        }
        if (isset($data['tax_rate'])) {
            $orderItemsEntity->setTaxRate($data['tax_rate']);
        }
        if (isset($data['cross_border_tax'])) {
            $orderItemsEntity->setCrossBorderTax($data['cross_border_tax']);
        }

        if (isset($data['origincountry_name'])) {
            $orderItemsEntity->setOrigincountryName($data['origincountry_name']);
        }

        if (isset($data['origincountry_img_url'])) {
            $orderItemsEntity->setOrigincountryImgUrl($data['origincountry_img_url']);
        }
        if (isset($data['taxable_fee'])) {
            $orderItemsEntity->setTaxableFee($data['taxable_fee']);
        }

        if (isset($data['point_fee'])) {
            $orderItemsEntity->setPointFee($data['point_fee']);
        }
        if (isset($data['share_points'])) {
            $orderItemsEntity->setSharePoints($data['share_points']);
        }
        if (isset($data['share_uppoints'])) {
            $orderItemsEntity->setShareUppoints($data['share_uppoints']);
        }

        if (isset($data['is_logistics'])) {
            $orderItemsEntity->setIsLogistics($data['is_logistics']);
        }

        if (isset($data['delivery_item_num'])) {
            $orderItemsEntity->setDeliveryItemNum($data['delivery_item_num']);
        }

        if (isset($data['cancel_item_num'])) {
            $orderItemsEntity->setCancelItemNum($data['cancel_item_num']);
        }

        if (isset($data['get_points'])) {
            $orderItemsEntity->setGetPoints($data['get_points']);
        }

        if (isset($data['supplier_id'])) {
            $orderItemsEntity->setSupplierId($data['supplier_id']);
        }

        if (isset($data['commission_fee'])) {
            $orderItemsEntity->setCommissionFee($data['commission_fee']);
        }
        if (isset($data['is_prescription'])) {
            $orderItemsEntity->setIsPrescription($data['is_prescription']);
        }
        if (isset($data['is_invoice'])) {
            $orderItemsEntity->setIsInvoice($data['is_invoice']);
        }
        return $orderItemsEntity;
    }

    public function getNormalOrdersItemsData($orderItemsEntity)
    {
        /**
         * @var \OrdersBundle\Entities\NormalOrdersItems $orderItemsEntity
         */
        $result = [
            'id' => $orderItemsEntity->getId(),
            'order_id' => $orderItemsEntity->getOrderId(),
            'company_id' => $orderItemsEntity->getCompanyId(),
            'user_id' => $orderItemsEntity->getUserId(),
            'act_id' => $orderItemsEntity->getActId(),
            'goods_id' => $orderItemsEntity->getGoodsId(),
            'item_id' => $orderItemsEntity->getItemId(),
            'item_bn' => $orderItemsEntity->getItemBn(),
            'goods_bn' => $orderItemsEntity->getGoodsBn(),
            'item_name' => $orderItemsEntity->getItemName(),
            'pic' => $orderItemsEntity->getPic(),
            'num' => $orderItemsEntity->getNum(),
            'price' => $orderItemsEntity->getPrice(),
            'cost_price' => $orderItemsEntity->getCostPrice(),
            'total_fee' => $orderItemsEntity->getTotalFee(),
            'market_price' => $orderItemsEntity->getMarketPrice(),
            'templates_id' => $orderItemsEntity->getTemplatesId(),
            'rebate' => $orderItemsEntity->getRebate(),
            'total_rebate' => $orderItemsEntity->getTotalRebate(),
            'item_fee' => $orderItemsEntity->getItemFee(),
            'cost_fee' => $orderItemsEntity->getCostFee(),
            'item_unit' => $orderItemsEntity->getItemUnit(),
            'member_discount' => $orderItemsEntity->getMemberDiscount(),
            'coupon_discount' => $orderItemsEntity->getCouponDiscount(),
            'discount_fee' => $orderItemsEntity->getDiscountFee() ?? 0,
            'discount_info' => $orderItemsEntity->getDiscountInfo(),
            'shop_id' => $orderItemsEntity->getShopId(),
            'is_total_store' => $orderItemsEntity->getIsTotalStore(),
            'distributor_id' => $orderItemsEntity->getDistributorId(),
            'create_time' => $orderItemsEntity->getCreateTime(),
            'update_time' => $orderItemsEntity->getUpdateTime(),
            'delivery_corp' => $orderItemsEntity->getDeliveryCorp(),
            'delivery_code' => $orderItemsEntity->getDeliveryCode(),
            'delivery_img' => $orderItemsEntity->getDeliveryImg(),
            'delivery_time' => $orderItemsEntity->getDeliveryTime(),
            'delivery_status' => $orderItemsEntity->getDeliveryStatus(),
            'aftersales_status' => $orderItemsEntity->getAftersalesStatus(),
            'refunded_fee' => $orderItemsEntity->getRefundedFee(),
            'fee_type' => $orderItemsEntity->getFeeType(),
            'fee_rate' => $orderItemsEntity->getFeeRate(),
            'fee_symbol' => $orderItemsEntity->getFeeSymbol(),
            'cny_fee' => round(round(floatval($orderItemsEntity->getFeeRate()), 4) * $orderItemsEntity->getTotalFee()),
            'item_point' => $orderItemsEntity->getItemPoint(),
            'point' => $orderItemsEntity->getPoint(),
            'item_spec_desc' => $orderItemsEntity->getItemSpecDesc(),
            'order_item_type' => $orderItemsEntity->getOrderItemType(),
            'volume' => $orderItemsEntity->getVolume(),
            'weight' => $orderItemsEntity->getWeight(),
            'is_rate' => $orderItemsEntity->getIsRate(),
            'auto_close_aftersales_time' => $orderItemsEntity->getAutoCloseAftersalesTime(),
            'share_points' => $orderItemsEntity->getSharePoints(),
            'point_fee' => $orderItemsEntity->getPointFee(),
            'is_logistics' => $orderItemsEntity->getIsLogistics(),
            'delivery_item_num' => $orderItemsEntity->getDeliveryItemNum(),
            'cancel_item_num' => $orderItemsEntity->getCancelItemNum(),
            'get_points' => $orderItemsEntity->getGetPoints(),
            'supplier_id' => $orderItemsEntity->getSupplierId(),
            'commission_fee' => $orderItemsEntity->getCommissionFee(),
            'is_prescription' => $orderItemsEntity->getIsPrescription(),
            'is_invoice' => $orderItemsEntity->getIsInvoice(),
        ];

        // 跨境订单
        if ($orderItemsEntity->getType() == 1) {
            $result['type'] = $orderItemsEntity->getType();
            $result['tax_rate'] = $orderItemsEntity->getTaxRate();
            $result['cross_border_tax'] = $orderItemsEntity->getCrossBorderTax();
            $result['origincountry_name'] = $orderItemsEntity->getOrigincountryName();
            $result['origincountry_img_url'] = $orderItemsEntity->getOrigincountryImgUrl();
            $result['taxable_fee'] = $orderItemsEntity->getTaxableFee();
        }

        if (!$result['discount_fee']) {
            $result['discount_fee'] = $result['member_discount'] + $result['coupon_discount'];
        }

        $result['discount_info'] = [] ;
        if ($orderItemsEntity->getDiscountInfo()) {
            $discountInfoData = json_decode($orderItemsEntity->getDiscountInfo(), true);
            foreach ($discountInfoData as $discountInfo) {
                if ($discountInfo) {
                    $result['discount_info'][] = $discountInfo;
                }
            }
        } else {
            if ($orderItemsEntity->getCouponDiscountDesc()) {
                $coupon_discount_desc = json_decode($orderItemsEntity->getCouponDiscountDesc(), true);
                $coupon_discount_desc['type'] = 'coupon_discount';
                $result['discount_info'][] = $coupon_discount_desc;
            }
            if ($orderItemsEntity->getMemberDiscountDesc()) {
                $member_discount_desc = json_decode($orderItemsEntity->getMemberDiscountDesc(), true);
                $member_discount_desc['type'] = 'member_discount';
                $result['discount_info'][] = $member_discount_desc;
            }
        }
        return $result;
    }

    /**
     * 获取订单的第一个商品
     */
    public function getOrderFirstItem($order_id = '')
    {
        $sql = 'SELECT id,user_id,item_id,item_bn,item_name,pic,num,price,item_fee,item_spec_desc,order_item_type
            FROM orders_normal_orders_items WHERE order_id = "'.$order_id.'" limit 1 ';
        $item = app('registry')->getConnection()->query($sql)->fetch();
        if (!empty($item)) {
            return [$item];
        }
        return [];
    }

    private function replaceSpecDesc(array $oiList)
    {
        $itemIdArr = array_column($oiList,'item_id');
        $multiLang = new MultiLangService();
        $spec = $multiLang->getItemSpec($itemIdArr);
        foreach ($oiList as $i => $item) {
            $itemId = $item['item_id'];
            if(!empty($item['item_spec_desc'])){
               if (isset($spec[$itemId]) && !empty($spec[$itemId])) {
                    // 如果有规格信息，则替换
                    $tmpArr= $spec[$itemId];
                    $result = array_map(function($item) {
                        return $item['attribute_name'] . ':' . $item['attribuattribute_valuete_name'];
                    }, $tmpArr);
                    $oiList[$i]['item_spec_desc'] = implode(',', $result);
               }
            }
        }
        return $oiList;
    }

    /**
     * 筛选条件格式化
     *
     * @param $filter
     * @param $qb
     */
    private function _filter($filter, $qb)
    {
        foreach ($filter as $field => $value) {
            $list = explode('|', $field);
            if (count($list) > 1) {
                list($v, $k) = $list;
                if ($k == 'contains') {
                    $k = 'like';
                }
                if ($k == 'like') {
                    $value = '%'.$value.'%';
                }
                if (is_array($value)) {
                    array_walk($value, function (&$colVal) use ($qb) {
                        $colVal = $qb->expr()->literal($colVal);
                    });
                    $qb = $qb->andWhere($qb->expr()->$k($field, $value));
                } else {
                    $qb = $qb->andWhere($qb->expr()->$k($v, $qb->expr()->literal($value)));
                }
                continue;
            } elseif (is_array($value)) {
                array_walk($value, function (&$colVal) use ($qb) {
                    $colVal = $qb->expr()->literal($colVal);
                });
                $qb = $qb->andWhere($qb->expr()->in($field, $value));
            } else {
                $qb = $qb->andWhere($qb->expr()->eq($field, $qb->expr()->literal($value)));
            }
        }
        return $qb;
    }

    /**
     * 根据条件获取列表数据
     *
     * @param $filter 更新的条件
     */
    public function getLists($filter, $cols = '*', $page = 1, $pageSize = -1, $orderBy = array())
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()->select($cols)->from($this->table);
        $qb = $this->_filter($filter, $qb);
        if ($orderBy) {
            foreach ($orderBy as $filed => $val) {
                $qb->addOrderBy($filed, $val);
            }
        }
        if ($pageSize > 0) {
            $qb->setFirstResult(($page - 1) * $pageSize)
                ->setMaxResults($pageSize);
        }
        $lists = $qb->execute()->fetchAll();
        return $lists;
    }
}
