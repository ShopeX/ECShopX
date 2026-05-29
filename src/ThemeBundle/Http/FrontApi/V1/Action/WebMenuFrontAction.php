<?php
/**
 * Copyright 2019-2026 ShopeX
 */

namespace ThemeBundle\Http\FrontApi\V1\Action;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use ThemeBundle\Entities\WebMenu;
use ThemeBundle\Entities\WebMenuItem;
use ThemeBundle\Services\WebMenuService;
use ThemeBundle\Transformers\WebMenuItemTransformer;

class WebMenuFrontAction extends Controller
{
    /** GET /h5app/web/menus/{key} */
    public function show(Request $request, string $key)
    {
        $companyId = $this->resolveCompanyId($request);
        if ($companyId <= 0) {
            return $this->response->errorUnauthorized('缺少 company 上下文');
        }

        $em = app('registry')->getManager('default');
        /** @var \ThemeBundle\Repositories\WebMenuRepository $menuRepo */
        $menuRepo = $em->getRepository(WebMenu::class);
        $menu = $menuRepo->findActiveByCompanyAndKey($companyId, $key);
        if (!$menu) {
            return $this->response->errorNotFound('菜单不存在');
        }

        return $this->renderMenuTree($companyId, $menu);
    }

    /** GET /h5app/web/menus/id/{id} */
    public function showById(Request $request, int $id)
    {
        $companyId = $this->resolveCompanyId($request);
        if ($companyId <= 0) {
            return $this->response->errorUnauthorized('缺少 company 上下文');
        }

        $em = app('registry')->getManager('default');
        /** @var \ThemeBundle\Repositories\WebMenuRepository $menuRepo */
        $menuRepo = $em->getRepository(WebMenu::class);
        $menu = $menuRepo->findActiveByCompanyAndId($companyId, $id);
        if (!$menu) {
            return $this->response->errorNotFound('菜单不存在');
        }

        return $this->renderMenuTree($companyId, $menu);
    }

    private function resolveCompanyId(Request $request): int
    {
        $auth = $request->get('auth');
        if (!is_array($auth) || empty($auth['company_id'])) {
            $auth = $request->attributes->get('auth', []);
        }

        return (int) ($auth['company_id'] ?? 0);
    }

    private function renderMenuTree(int $companyId, WebMenu $menu)
    {
        $em = app('registry')->getManager('default');
        /** @var \ThemeBundle\Repositories\WebMenuItemRepository $itemRepo */
        $itemRepo = $em->getRepository(WebMenuItem::class);
        $items = $itemRepo->findActiveByMenu($menu->getId(), $companyId);
        $service = new WebMenuService();
        $tree = $service->buildTree($items);
        $tr = new WebMenuItemTransformer();

        return $this->response->array([
            'id'    => $menu->getId(),
            'name'  => $menu->getName(),
            'key'   => $menu->getKey(),
            'items' => array_map([$tr, 'transformFront'], $tree),
        ]);
    }
}
