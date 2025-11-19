<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
