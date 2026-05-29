<?php
/**
 * Copyright 2019-2026 ShopeX
 */

namespace ThemeBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use ThemeBundle\Services\WebMenuService;
use ThemeBundle\Transformers\WebMenuItemFlatTransformer;

class WebMenuItemAction extends Controller
{
    /** POST /web-menus/{id}/items */
    public function store(Request $request, $id)
    {
        $companyId = (int) app('auth')->user()->get('company_id');
        $service = new WebMenuService();
        $item = $service->createItem((int) $id, $companyId, $request->only([
            'name', 'image_url', 'link_type', 'link_value', 'link_extra', 'sort', 'status', 'parent_id',
        ]));

        return $this->response->item($item, new WebMenuItemFlatTransformer());
    }

    /** PUT /web-menus/{id}/items/{itemId} */
    public function update(Request $request, $id, $itemId)
    {
        $companyId = (int) app('auth')->user()->get('company_id');
        $service = new WebMenuService();
        $item = $service->updateItem((int) $itemId, (int) $id, $companyId, $request->only([
            'name', 'image_url', 'link_type', 'link_value', 'link_extra', 'sort', 'status', 'parent_id',
        ]));

        return $this->response->item($item, new WebMenuItemFlatTransformer());
    }

    /** DELETE /web-menus/{id}/items/{itemId} */
    public function destroy(Request $request, $id, $itemId)
    {
        $companyId = (int) app('auth')->user()->get('company_id');
        $service = new WebMenuService();
        $service->deleteItem((int) $itemId, (int) $id, $companyId);

        return $this->response->noContent();
    }

    /** PUT /web-menus/{id}/items/sort */
    public function batchSort(Request $request, $id)
    {
        $companyId = (int) app('auth')->user()->get('company_id');
        $sorts = $request->input('sorts', $request->input('items', []));
        if (!is_array($sorts)) {
            $sorts = [];
        }
        $service = new WebMenuService();
        $service->batchUpdateSort((int) $id, $companyId, $sorts);

        return $this->response->noContent();
    }
}
