<?php
/**
 * Copyright 2019-2026 ShopeX
 */

namespace ThemeBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use ThemeBundle\Entities\WebMenu;
use ThemeBundle\Entities\WebMenuItem;
use ThemeBundle\Services\WebMenuService;
use ThemeBundle\Transformers\WebMenuItemTransformer;
use ThemeBundle\Transformers\WebMenuTransformer;

class WebMenuAction extends Controller
{
    /**
     * GET /web-menus
     * 列表接口：{ "data": { "total_count": N, "list": [...] } }（与 Wiki 接口响应格式一致）
     */
    public function index(Request $request)
    {
        $companyId = (int) app('auth')->user()->get('company_id');
        $page = (int) $request->query('page', 1);
        $pageSize = (int) $request->query('page_size', 20);
        $name = $request->query('name');
        $name = is_string($name) ? trim($name) : null;
        if ($name === '') {
            $name = null;
        }
        $service = new WebMenuService();
        $result = $service->listMenus($companyId, $page, $pageSize, $name);
        $transformer = new WebMenuTransformer();
        $em = app('registry')->getManager('default');
        $itemRepo = $em->getRepository(WebMenuItem::class);
        $menuIds = array_map(fn ($m) => $m->getId(), $result['list']);
        $counts = $itemRepo->countItemsByMenuIds($companyId, $menuIds);
        $topLevelNames = $itemRepo->findTopLevelItemNamesGroupedByMenuIds($companyId, $menuIds);
        $list = [];
        foreach ($result['list'] as $m) {
            $row = $transformer->transform($m);
            $mid = $m->getId();
            $row['items_count'] = $counts[$mid] ?? 0;
            $row['top_level_item_names'] = $topLevelNames[$mid] ?? '';
            $list[] = $row;
        }

        return $this->response->array([
            'total_count' => $result['total'],
            'list'        => $list,
        ]);
    }

    /** POST /web-menus */
    public function store(Request $request)
    {
        $companyId = (int) app('auth')->user()->get('company_id');
        $service = new WebMenuService();
        $menu = $service->createMenu($companyId, $request->only(['name', 'key', 'status']));

        return $this->response->item($menu, new WebMenuTransformer());
    }

    /** GET /web-menus/{id} */
    public function show(Request $request, $id)
    {
        $companyId = (int) app('auth')->user()->get('company_id');
        $menuId = (int) $id;
        $service = new WebMenuService();
        $em = app('registry')->getManager('default');
        $menu = $em->getRepository(WebMenu::class)->findOneByIdAndCompany($menuId, $companyId);
        if (!$menu) {
            return $this->response->errorNotFound('菜单不存在');
        }
        $items = $em->getRepository(WebMenuItem::class)->findAllByMenu($menuId, $companyId);
        $tree = $service->buildTree($items);
        $itemTr = new WebMenuItemTransformer();

        /** 详情：{ "data": { id, name, key, status, items: [...] } } */
        return $this->response->array(array_merge(
            (new WebMenuTransformer())->transform($menu),
            ['items' => array_map([$itemTr, 'transform'], $tree)]
        ));
    }

    /** PUT /web-menus/{id} */
    public function update(Request $request, $id)
    {
        $companyId = (int) app('auth')->user()->get('company_id');
        $service = new WebMenuService();
        $menu = $service->updateMenu((int) $id, $companyId, $request->only(['name', 'key', 'status']));

        return $this->response->item($menu, new WebMenuTransformer());
    }

    /** DELETE /web-menus/{id} */
    public function destroy(Request $request, $id)
    {
        $companyId = (int) app('auth')->user()->get('company_id');
        $service = new WebMenuService();
        $service->deleteMenu((int) $id, $companyId);

        return $this->response->noContent();
    }
}
