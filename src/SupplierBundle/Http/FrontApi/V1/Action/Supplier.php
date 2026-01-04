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

namespace SupplierBundle\Http\FrontApi\V1\Action;

use App\Http\Controllers\Controller;
use Dingo\Api\Exception\ResourceException;
use Dingo\Api\Exception\StoreResourceFailedException;
use Illuminate\Http\Request;

use AdaPayBundle\Services\BankCodeService;
use MembersBundle\Services\MemberService;
use SupplierBundle\Services\SupplierService;

class Supplier extends Controller
{
    /**
     * @SWG\Get(
     *     path="/wxapp/supplier/get_supplier_info",
     *     summary="查询供应商信息",
     * )
     */
    public function getSupplierInfo(Request $request)
    {
        $authInfo = app('auth')->user()->get();
        $userId = $authInfo['user_id'];
        $companyId = $authInfo['company_id'];

        $operatorId = intval($request->input('supplier_id', 0));
        if (!$operatorId) {
            throw new ResourceException(trans('SupplierBundle.supplier_id_error'));
        }

        $supplierInfo = [
            'wechat_qrcode' => '',
            'service_tel' => '',
            'supplier_name' => '',
        ];
        $filter = [
            'company_id' => $companyId,
            'operator_id' => $operatorId,
        ];
        $supplierService = new SupplierService();
        $rs = $supplierService->repository->getInfo($filter);
        if ($rs) {
            $supplierInfo = [
                'wechat_qrcode' => $rs['wechat_qrcode'],
                'service_tel' => $rs['service_tel'],
                'supplier_name' => $rs['supplier_name'],
            ];
        }
        return $this->response->array($supplierInfo);
    }
}
