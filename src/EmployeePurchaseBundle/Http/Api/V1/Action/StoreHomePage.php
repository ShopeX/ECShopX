<?php
/**
 * Copyright 2019-2026 ShopeX
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 */

namespace EmployeePurchaseBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as Controller;
use Dingo\Api\Exception\ResourceException;
use EmployeePurchaseBundle\Services\StoreHomePageService;
use Illuminate\Http\Request;

class StoreHomePage extends Controller
{
    /** @var StoreHomePageService */
    private $service;

    public function __construct()
    {
        $this->service = new StoreHomePageService();
    }

    public function getList(Request $request)
    {
        $auth = app('auth')->user()->get();
        $companyId = (int) $auth['company_id'];
        $page = max(1, (int) $request->input('page', 1));
        $pageSize = max(1, min(100, (int) $request->input('pageSize', 20)));

        $authDistributorId = 0;
        $filterDistributorId = null;
        if (($auth['operator_type'] ?? '') === 'distributor') {
            $authDistributorId = (int) ($auth['distributor_id'] ?? $request->input('distributor_id', 0));
        } elseif (($filterDist = $request->input('distributor_id')) !== null && $filterDist !== '') {
            $filterDistributorId = (int) $filterDist;
        }

        $result = $this->service->getList($companyId, $authDistributorId, $page, $pageSize, $filterDistributorId);

        return $this->response->array([
            'list' => $result['list'],
            'total_count' => $result['total_count'],
        ]);
    }

    public function create(Request $request)
    {
        $auth = app('auth')->user()->get();
        $companyId = (int) $auth['company_id'];
        $authDistributorId = (int) ($auth['distributor_id'] ?? 0);

        $params = $request->all(
            'template_name',
            'page_name',
            'page_description',
            'page_share_title',
            'page_share_desc',
            'page_share_imageUrl',
            'is_open'
        );
        $row = $this->service->createRow($companyId, $authDistributorId, $params);

        return $this->response->array($row);
    }

    public function getInfo($id)
    {
        $auth = app('auth')->user()->get();
        $companyId = (int) $auth['company_id'];
        $authDistributorId = (int) ($auth['distributor_id'] ?? 0);
        if (!$id) {
            throw new ResourceException('id 必传');
        }
        $row = $this->service->getById($companyId, $authDistributorId, (int) $id);

        return $this->response->array($row);
    }

    public function update(Request $request, $id)
    {
        $auth = app('auth')->user()->get();
        $companyId = (int) $auth['company_id'];
        $authDistributorId = (int) ($auth['distributor_id'] ?? 0);
        if (!$id) {
            throw new ResourceException('id 必传');
        }
        $params = $request->all(
            'template_name',
            'page_name',
            'page_description',
            'page_share_title',
            'page_share_desc',
            'page_share_imageUrl',
            'is_open'
        );
        $row = $this->service->updateRow($companyId, $authDistributorId, (int) $id, $params);

        return $this->response->array($row);
    }

    public function delete($id)
    {
        $auth = app('auth')->user()->get();
        $companyId = (int) $auth['company_id'];
        $authDistributorId = (int) ($auth['distributor_id'] ?? 0);
        if (!$id) {
            throw new ResourceException('id 必传');
        }
        $this->service->deleteRow($companyId, $authDistributorId, (int) $id);

        return $this->response->array(['status' => true]);
    }
}
