<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace SupplierBundle\Repositories;

use Dingo\Api\Exception\ResourceException;
use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;
use Dingo\Api\Exception\UpdateResourceFailedException;
use SupplierBundle\Entities\SupplierOrder;

class SupplierOrderRepository extends BaseRepository
{
    public $table = 'supplier_order';
    public $cols = ['id', 'order_id', 'title', 'company_id', 'shop_id', 'cost_fee',
        'user_id', 'act_id', 'mobile', 'commission_fee',
        'order_class', 'freight_fee', 'freight_type', 'item_fee', 'total_fee', 'market_fee', 'step_paid_fee',
        'total_rebate', 'distributor_id', 'receipt_type', 'ziti_code', 'ziti_status', 'order_status',
        'pay_status', 'order_source', 'order_type', 'is_distribution', 'source_id',
        'delivery_corp', 'delivery_corp_source', 'delivery_code', 'delivery_img', 'delivery_time',
        'end_time', 'delivery_status', 'cancel_status', 'receiver_name', 'receiver_mobile', 'receiver_zip',
        'receiver_state', 'receiver_city', 'receiver_district', 'receiver_address', 'member_discount',
        'coupon_discount', 'discount_fee', 'discount_info', 'coupon_discount_desc', 'member_discount_desc',
        'fee_type', 'fee_rate', 'fee_symbol', 'item_point', 'point', 'pay_type', 'pay_channel', 'remark',
        'invoice', 'invoice_number', 'is_invoiced', 'send_point', 'type', 'point_fee', 'point_use',
        'is_settled',
        'pack', 'operator_id', 'source_from', 'supplier_id', 'create_time', 'update_time'];

    public function create($params)
    {
        $entity = new SupplierOrder();
        $normalOrder = $this->setColumnNamesData($entity, $params);

        $em = $this->getEntityManager();
        $em->persist($normalOrder);
        $em->flush();

        $result = $this->getColumnNamesData($normalOrder);

        return $result;
    }

    public function get($companyId, $orderId, $supplier_id)
    {
        $filter = [
            'company_id' => $companyId,
            'supplier_id' => $supplier_id,
            'order_id' => $orderId
        ];
        return $this->findOneBy($filter);
    }

}
