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
