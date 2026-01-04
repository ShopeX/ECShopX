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

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use OrdersBundle\Services\OrderAssociationService;

use OrdersBundle\Traits\GetOrderServiceTrait;

class NormalOrdersCancelUploadService
{
    use GetOrderServiceTrait;

    public $header = [
        '订单号' => 'order_id',
        '取消原因' => 'cancel_reason',
    ];

    public $headerInfo = [
        '订单号' => ['size' => 32, 'remarks' => '不得重复，订单号如果大于11位时，请关闭excel单元格的科学记数法，常用禁用方法：“单元格格式”-“自定义”-“类型”改为“0”', 'is_need' => true],
        '取消原因' => ['size' => 255, 'remarks' => '', 'is_need' => true],
    ];

    public $isNeedCols = [
        '订单号' => 'order_id',
        '取消原因' => 'cancel_reason',
    ];

    public $tmpTarget;

    /**
    * 获取头部标题
    */
    public function getHeaderTitle()
    {
        // IDX: 2367340174
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
        // IDX: 2367340174
        unlink($this->tmpTarget);
        return true;
    }

    public function getFileSystem()
    {
        return app('filesystem')->disk('import-file');
    }

    public function handleRow($companyId, $row)
    {
        $params['order_id'] = $row['order_id'];
        $params['cancel_reason'] = $row['cancel_reason'];
        $params['company_id'] = $companyId;
        $params['cancel_from'] = 'shop'; //商家取消订单

        $orderAssociationService = new OrderAssociationService();
        $order = $orderAssociationService->getOrder($companyId, $params['order_id']);
        if (!$order) {
            throw new BadRequestHttpException(trans('OrdersBundle/Order.order_not_found', ['id' => $params['order_id']]));
        }
        if ($order['order_type'] != 'normal') {
            throw new BadRequestHttpException(trans('OrdersBundle/Order.only_physical_order_can_cancel'));
        }
        //获取订单用户信息
        $params['user_id'] = $order['user_id'];
        $params['mobile'] = $order['mobile'];
        $params['operator_type'] = 'admin';
        $params['operator_id'] = $row['operator_id'];

        $orderService = $this->getOrderServiceByOrderInfo($order);
        if ($order['delivery_status'] == 'PENDING') {
            $result = $orderService->cancelOrder($params);
        } elseif ($order['delivery_status'] == 'PARTAIL') {
            $result = $orderService->partailCancelOrder($params);
        } else {
            throw new BadRequestHttpException(trans('OrdersBundle/Order.no_items_to_cancel'));
        }
    }
}
