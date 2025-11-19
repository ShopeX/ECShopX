<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace GoodsBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;
use GoodsBundle\Services\ItemsService;
use SystemLinkBundle\Jobs\UploadItemsToJushuitanJob;
use SystemLinkBundle\Jobs\InventoryQueryFromJushuitanJob;
use CompanysBundle\Ego\CompanysActivationEgo;
use DistributionBundle\Services\DistributorItemsService;
use DistributionBundle\Services\DistributorService;
use PointsmallBundle\Services\ItemsService as PointsmallItemsService;

class Jushuitan extends BaseController
{
    public function uploadItems(request $request)
    {
        // ID: 53686f704578
        $inputData = $request->input();
        $params['company_id'] = app('auth')->user()->get('company_id');
        $operatorType = app('auth')->user()->get('operator_type');
        $distributorId = 0;
        if ($operatorType == 'distributor') {
            $distributorId = $request->get('distributor_id');
        }
        if ($distributorId > 0) {
            $distributorService = new DistributorService();
            $distributorInfo = $distributorService->getInfoSimple(['company_id' => $params['company_id'], 'distributor_id' => $distributorId]);
            if (!$distributorInfo || !$distributorInfo['jst_shop_id']) {
                throw new ResourceException(trans('GoodsBundle/Controllers/Items.shop_not_bind_jushuitan_erp'));
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
        $itemType = $inputData['item_type'] ?? 'normal';
        $company = (new CompanysActivationEgo())->check($params['company_id']);
        app('log')->info('jushuitan-add_items:company=====>'.json_encode($company));
        $page = 1;
        $pageSize = 100;
        do {
            if (in_array($company['product_model'], ['platform', 'standard']) && $operatorType == 'distributor') {
                app('log')->info('jushuitan-add_items:file:'.__FILE__.',line:'.__LINE__);
                $params['distributor_id'] = $distributorId;
                $distributorItemsService = new DistributorItemsService();
                $datalist = $distributorItemsService->getDistributorRelItemList($params, $pageSize, $page, ['item_id' => 'desc'], false);

                if ($datalist['list']) {
                    $itemIds = array_column($datalist['list'], 'item_id');
                    $gotoJob = (new UploadItemsToJushuitanJob($params['company_id'], $itemIds, $distributorId, $itemType))->onQueue('slow');
                    app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
                }
            } else {
                app('log')->info('jushuitan-add_items:item_type=====>'.$itemType);
                if ($itemType == 'pointsmall') {
                    $pointsmallItemsService = new PointsmallItemsService();
                    $datalist = $pointsmallItemsService->getItemsList($params, $page, $pageSize, ['item_id' => 'desc']);
                } else {
                    $itemsService = new ItemsService();
                    $datalist = $itemsService->getItemsList($params, $pageSize, $page, ['item_id' => 'desc']);
                }
                
                if ($datalist['list']) {
                    $itemIds = array_column($datalist['list'], 'item_id');
                    $gotoJob = (new UploadItemsToJushuitanJob($params['company_id'], $itemIds, $distributorId, $itemType))->onQueue('slow');
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

    public function queryInventory(request $request)
    {
        $inputData = $request->input();

        $params['company_id'] = app('auth')->user()->get('company_id');
        if (isset($inputData['item_id']) && $inputData['item_id']) {
            $params['default_item_id'] = $inputData['item_id'];
        }

        $params['item_type'] = 'normal';
        $params['audit_status'] = 'approved';

        $itemsService = new ItemsService();
        $page = 1;
        $pageSize = 100;
        do {
            $datalist = $itemsService->list($params, ['item_id' => 'desc'], $pageSize, $page, ['item_id']);

            if ($datalist['list']) {
                $itemIds = array_column($datalist['list'], 'item_id');
                $gotoJob = (new InventoryQueryFromJushuitanJob($params['company_id'], $itemIds))->onQueue('slow');
                app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
            }

            $page++;
        } while (($page-1) * $pageSize < $datalist['total_count']);

        $result['status'] = true;
        return response()->json($result);
    }

    private function genReidsId($companyId)
    {
        return 'JushuitanLastUploadTime:' . sha1($companyId);
    }
}
