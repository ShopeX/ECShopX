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

namespace GoodsBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;
use GoodsBundle\Services\ItemsService;
use SystemLinkBundle\Jobs\UploadItemsToWdtErpJob;
use CompanysBundle\Ego\CompanysActivationEgo;
use DistributionBundle\Services\DistributorItemsService;
use DistributionBundle\Services\DistributorService;

class WdtErp extends BaseController
{
    public function uploadItems(request $request)
    {
        $inputData = $request->input();
        $companyId = app('auth')->user()->get('company_id');
        $operatorType = app('auth')->user()->get('operator_type');
        $distributorId = 0;
        if ($operatorType == 'distributor') {
            $distributorId = $request->get('distributor_id');
        }
        $params['company_id'] = $companyId;
        if ($distributorId > 0) {
            $distributorService = new DistributorService();
            $distributorInfo = $distributorService->getInfoSimple(['company_id' => $params['company_id'], 'distributor_id' => $distributorId]);
            if (!$distributorInfo || !$distributorInfo['wdt_shop_no']) {
                throw new ResourceException(trans('GoodsBundle/Controllers/Items.shop_not_bind_wdt_erp'));
            }
        }
        $isAll = false;
        if (isset($inputData['item_id']) && $inputData['item_id']) {
            $params['default_item_id'] = $inputData['item_id'];
        } else {
            $lastUploadTime = app('redis')->get($this->genReidsId($params['company_id']));
            $params['updated|gte'] = $lastUploadTime ?: 0;
            $isAll = true;
        }
        $params['item_type'] = 'normal';
        $params['is_default'] = true;
        $params['audit_status'] = 'approved';

        $company = (new CompanysActivationEgo())->check($companyId);
        $itemsService = new ItemsService();
        $distributorItemsService = new DistributorItemsService();

        $page = 1;
        $pageSize = 100;
        do {
            if ($company['product_model'] == 'standard' && $operatorType == 'distributor') {
                $params['distributor_id'] = $distributorId;
                $datalist = $distributorItemsService->getDistributorRelItemList($params, $pageSize, $page, ['item_id' => 'desc'], false);

                if ($datalist['list']) {
                    $itemIds = array_column($datalist['list'], 'item_id');
                    $gotoJob = (new UploadItemsToWdtErpJob($params['company_id'], $itemIds, $distributorId))->onQueue('slow');
                    app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
                }
            } else {
                $datalist = $itemsService->list($params, ['item_id' => 'desc'], $pageSize, $page, ['item_id']);
                if ($datalist['list']) {
                    $itemIds = array_column($datalist['list'], 'item_id');
                    $gotoJob = (new UploadItemsToWdtErpJob($params['company_id'], $itemIds, $distributorId))->onQueue('slow');
                    app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
                }
            }
            $page++;
        } while (($page-1) * $pageSize < $datalist['total_count']);

        if ($isAll) {
            app('redis')->set($this->genReidsId($params['company_id']), time());
        }

        $result['status'] = true;
        return response()->json($result);
    }

    private function genReidsId($companyId)
    {
        return 'WdtErpLastUploadTime:' . sha1($companyId);
    }
}
