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

namespace OrdersBundle\Services\Orders;

use SupplierBundle\Services\SupplierOrderService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use OrdersBundle\Services\OrderAssociationService;
use OrdersBundle\Entities\CompanyRelLogistics;
use OrdersBundle\Traits\GetOrderServiceTrait;

class NormalOrdersUploadService
{
    use GetOrderServiceTrait;

    public $header = [
        '订单号' => 'order_id',
        '快递单号' => 'delivery_code',
        '快递公司' => 'delivery_corp_name',

    ];

    public $headerInfo = [
        '订单号' => ['size' => 32, 'remarks' => '不得重复，订单号如果大于11位时，请关闭excel单元格的科学记数法，常用禁用方法：“单元格格式”-“自定义”-“类型”改为“0”', 'is_need' => true],
        '快递单号' => ['size' => 255, 'remarks' => '', 'is_need' => true],
        '快递公司' => ['size' => 255, 'remarks' => '', 'is_need' => true],
    ];

    public $isNeedCols = [
        '订单号' => 'order_id',
        '快递单号' => 'delivery_code',
        '快递公司' => 'delivery_corp_name',
    ];

    public $tmpTarget;

    /**
    * 获取头部标题
    */
    public function getHeaderTitle()
    {
        return ['all' => $this->header, 'is_need' => $this->isNeedCols, 'headerInfo' => $this->headerInfo];
    }


    /**
     * 验证上传的会员信息
     */
    public function check($fileObject)
    {
        $extension = $fileObject->getClientOriginalExtension();
        if ($extension != 'xlsx') {
            throw new BadRequestHttpException(trans('OrdersBundle/Order.file_upload_xlsx_only'));
        }
    }

    /**
     * getFilePath function
     *
     * @return void
     */
    public function getFilePath($filePath, $fileExt = '')
    {
        $url = $this->getFileSystem()->privateDownloadUrl($filePath);
        $content = file_get_contents($url);

        $this->tmpTarget = tempnam('/tmp', 'import-file') . $fileExt;
        file_put_contents($this->tmpTarget, $content);

        return $this->tmpTarget;
    }

    public function finishHandle()
    {
        unlink($this->tmpTarget);
        return true;
    }

    public function getFileSystem()
    {
        return app('filesystem')->disk('import-file');
    }



    // private function validatorData($row)
    // {
    //     $arr = ['order_id','item_fee', 'total_fee', 'discount_fee', 'freight_fee', 'mobile', 'user_name', 'create_time', 'order_status','receiver_name','receiver_mobile','receiver_zip','receiver_state','receiver_city','receiver_district','receiver_address','pay_type','delivery_status', 'delivery_time', 'delivery_code', 'delivery_corp', 'kunnr'];
    //     $data = [];
    //     foreach($arr as $column) {
    //         if($row[$column]) {
    //             $data[$column] = $row[$column];
    //         }
    //     }

    //     return $data;
    // }

    public function handleRow($companyId, $row)
    {
        if (!$row['order_id']) {
            throw new BadRequestHttpException(trans('OrdersBundle/Order.order_number_error'));
        }
        $row['order_id'] = trim(trim($row['order_id']), "'");
        if (!$row['delivery_code'] or !$row['delivery_corp_name']) {
            throw new BadRequestHttpException(trans('OrdersBundle/Order.no_delivery_info'));
        }

        $supplier_id = $row['supplier_id'] ?? 0;
        if ($supplier_id) {
            $supplierOrderService = new SupplierOrderService();
            $order = $supplierOrderService->repository->getInfo([
                'order_id' => $row['order_id'],
                'supplier_id' => $supplier_id,
            ]);
        } else {
            $orderAssociationService = new OrderAssociationService();
            $order = $orderAssociationService->getOrder($companyId, $row['order_id']);
        }
        if (!$order) {
            throw new BadRequestHttpException(trans('OrdersBundle/Order.order_not_exists'));
        }
        if ('CANCEL' == $order['order_status']) {
            throw new BadRequestHttpException(trans('OrdersBundle/Order.cancelled_order_cannot_ship'));
        }

        $row['delivery_corp'] = $this->getDeliveryCorpByName($companyId, $row['delivery_corp_name'], $row['supplier_id']);

        $params = [
            'type' => 'new',
            'delivery_type' => 'batch',
            'order_id' => $row['order_id'],
            'company_id' => $companyId,
            'supplier_id' => $row['supplier_id'],
            'delivery_corp' => trim($row['delivery_corp']),
            'delivery_code' => trim($row['delivery_code']),
        ];
        /** @var \OrdersBundle\Services\Orders\AbstractNormalOrder $orderService */
        $orderService = $this->getOrderServiceByOrderInfo($order);
        $result = $orderService->delivery($params);
    }

    public function getDeliveryCorpByName($company_id, $delivery_corp_name, $supplier_id = 0)
    {
        $delivery_type = app('redis')->get('kuaidiTypeOpenConfig:' . sha1($company_id));
        $company_rel_logistics_filter = [
            'company_id' => $company_id,
            'corp_name' => $delivery_corp_name,
            'supplier_id' => $supplier_id,
        ];
        $companyRelLogisticsRepository = app('registry')->getManager('default')->getRepository(CompanyRelLogistics::class);
        $company_rel_logistics = $companyRelLogisticsRepository->getInfo($company_rel_logistics_filter);
        return $company_rel_logistics ? ($delivery_type == 'kuaidi100' ? $company_rel_logistics['kuaidi_code'] : $company_rel_logistics['corp_code']) : 'OTHER';
    }
}
