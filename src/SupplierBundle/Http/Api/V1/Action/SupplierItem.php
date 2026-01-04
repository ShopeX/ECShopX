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

namespace SupplierBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use SupplierBundle\Services\SupplierItemsService;

class SupplierItem extends Controller
{
    // 456353686f7058
    /**
     * @SWG\Post(
     *     path="/supplier/batch_review_items",
     *     summary="批量审核供应商商品",
     * )
     */
    public function batchReviewItems(Request $request)
    {
        // 456353686f7058
        $params = $request->all();

        $rules = [
            'audit_status' => ['in:rejected,approved', trans('SupplierBundle.audit_status_error')],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        
        $auth = app('auth')->user()->get();
        $params['company_id'] = $auth['company_id'];
        $params['audit_reason'] = $params['audit_reason'] ?? '';
        $itemIds = $params['item_ids'] ?? '';
        if (!$itemIds) {
            throw new ResourceException(trans('SupplierBundle.please_select_audit_items'));
        }
        if ($params['audit_status'] == 'rejected' && !$params['audit_reason']) {
            throw new ResourceException(trans('SupplierBundle.please_input_reject_reason'));
        }

        $result = [];
        $itemIds = explode(',', $itemIds);
        $SupplierItemsService = new SupplierItemsService();
        foreach ($itemIds as $itemId) {
            $result[] = $SupplierItemsService->reviewGoods($params, $itemId);
        }
        return $this->response->array($result);
    }
    
}
