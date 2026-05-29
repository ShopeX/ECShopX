<?php
/**
 * Copyright 2019-2026 ShopeX
 */

namespace ThemeBundle\Services;

use Dingo\Api\Exception\ResourceException;
use Doctrine\ORM\EntityManager;
use ThemeBundle\Entities\WebMenu;
use ThemeBundle\Entities\WebMenuItem;
use ThemeBundle\Repositories\WebMenuItemRepository;
use ThemeBundle\Repositories\WebMenuRepository;

class WebMenuService
{
    private EntityManager $em;

    private WebMenuRepository $menuRepo;

    private WebMenuItemRepository $itemRepo;

    public function __construct(?EntityManager $em = null)
    {
        $this->em = $em ?? app('registry')->getManager('default');
        $this->menuRepo = $this->em->getRepository(WebMenu::class);
        $this->itemRepo = $this->em->getRepository(WebMenuItem::class);
    }

    /**
     * @return array{list: WebMenu[], total: int, page: int, page_size: int}
     */
    public function listMenus(int $companyId, int $page = 1, int $pageSize = 20, ?string $name = null): array
    {
        $page = max(1, $page);
        $pageSize = min(100, max(1, $pageSize));
        $name = $name !== null ? trim($name) : null;
        if ($name === '') {
            $name = null;
        }
        $list = $this->menuRepo->findPageByCompany($companyId, $page, $pageSize, $name);
        $total = $this->menuRepo->countByCompany($companyId, $name);

        return [
            'list'       => $list,
            'total'      => $total,
            'page'       => $page,
            'page_size'  => $pageSize,
        ];
    }

    public function createMenu(int $companyId, array $data): WebMenu
    {
        $name = trim((string) ($data['name'] ?? ''));
        $key = trim((string) ($data['key'] ?? ''));
        if ($name === '' || $key === '') {
            throw new ResourceException('name 与 key 不能为空');
        }
        if ($this->menuRepo->existsDuplicateKey($companyId, $key)) {
            throw new ResourceException('同一店铺下 key 已存在');
        }
        $now = new \DateTimeImmutable();
        $menu = new WebMenu();
        $menu->setCompanyId($companyId);
        $menu->setName($name);
        $menu->setKey($key);
        $menu->setStatus(isset($data['status']) ? (int) $data['status'] : 1);
        $menu->setCreatedAt($now);
        $menu->setUpdatedAt($now);
        $this->em->persist($menu);
        $this->em->flush();

        return $menu;
    }

    public function updateMenu(int $id, int $companyId, array $data): WebMenu
    {
        $menu = $this->menuRepo->findOneByIdAndCompany($id, $companyId);
        if (!$menu) {
            throw new ResourceException('菜单不存在');
        }
        if (isset($data['name'])) {
            $name = trim((string) $data['name']);
            if ($name === '') {
                throw new ResourceException('name 不能为空');
            }
            $menu->setName($name);
        }
        if (isset($data['key'])) {
            $key = trim((string) $data['key']);
            if ($key === '') {
                throw new ResourceException('key 不能为空');
            }
            if ($this->menuRepo->existsDuplicateKey($companyId, $key, $id)) {
                throw new ResourceException('同一店铺下 key 已存在');
            }
            $menu->setKey($key);
        }
        if (isset($data['status'])) {
            $menu->setStatus((int) $data['status']);
        }
        $menu->setUpdatedAt(new \DateTimeImmutable());
        $this->em->flush();

        return $menu;
    }

    public function deleteMenu(int $id, int $companyId): void
    {
        $menu = $this->menuRepo->findOneByIdAndCompany($id, $companyId);
        if (!$menu) {
            throw new ResourceException('菜单不存在');
        }
        $this->em->createQuery(
            'DELETE ThemeBundle\Entities\WebMenuItem i WHERE i.menuId = :mid AND i.companyId = :cid'
        )->setParameter('mid', $id)->setParameter('cid', $companyId)->execute();
        $this->em->remove($menu);
        $this->em->flush();
    }

    public function createItem(int $menuId, int $companyId, array $data): WebMenuItem
    {
        $menu = $this->menuRepo->findOneByIdAndCompany($menuId, $companyId);
        if (!$menu) {
            throw new ResourceException('菜单不存在');
        }
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new ResourceException('菜单项名称不能为空');
        }
        $parentId = isset($data['parent_id']) ? (int) $data['parent_id'] : 0;
        if ($parentId > 0) {
            $parent = $this->itemRepo->findOneByIdMenuCompany($parentId, $menuId, $companyId);
            if (!$parent) {
                throw new ResourceException('父级菜单项不存在');
            }
        }
        $now = new \DateTimeImmutable();
        $item = new WebMenuItem();
        $item->setMenuId($menuId);
        $item->setCompanyId($companyId);
        $item->setParentId($parentId);
        $item->setName($name);
        $imageUrl = $data['image_url'] ?? null;
        $item->setImageUrl($imageUrl !== null && $imageUrl !== '' ? trim((string) $imageUrl) : null);
        $item->setLinkType(trim((string) ($data['link_type'] ?? 'url')) ?: 'url');
        $linkValue = $data['link_value'] ?? null;
        $item->setLinkValue($linkValue !== null && $linkValue !== '' ? (string) $linkValue : null);
        $linkExtra = $data['link_extra'] ?? null;
        $item->setLinkExtra($this->encodeLinkExtra($linkExtra));
        $item->setSort(isset($data['sort']) ? (int) $data['sort'] : 0);
        $item->setStatus(isset($data['status']) ? (int) $data['status'] : 1);
        $item->setCreatedAt($now);
        $item->setUpdatedAt($now);
        $this->em->persist($item);
        $this->em->flush();

        return $item;
    }

    public function updateItem(int $itemId, int $menuId, int $companyId, array $data): WebMenuItem
    {
        $item = $this->itemRepo->findOneByIdMenuCompany($itemId, $menuId, $companyId);
        if (!$item) {
            throw new ResourceException('菜单项不存在');
        }
        if (isset($data['parent_id'])) {
            $parentId = (int) $data['parent_id'];
            if ($parentId === $itemId) {
                throw new ResourceException('parent_id 不能指向自身');
            }
            if ($parentId > 0) {
                $parent = $this->itemRepo->findOneByIdMenuCompany($parentId, $menuId, $companyId);
                if (!$parent) {
                    throw new ResourceException('父级菜单项不存在');
                }
            }
            $item->setParentId($parentId);
        }
        if (isset($data['name'])) {
            $name = trim((string) $data['name']);
            if ($name === '') {
                throw new ResourceException('菜单项名称不能为空');
            }
            $item->setName($name);
        }
        if (array_key_exists('image_url', $data)) {
            $imageUrl = $data['image_url'];
            $item->setImageUrl($imageUrl !== null && $imageUrl !== '' ? trim((string) $imageUrl) : null);
        }
        if (array_key_exists('link_type', $data)) {
            $item->setLinkType(trim((string) $data['link_type']) ?: 'url');
        }
        if (array_key_exists('link_value', $data)) {
            $v = $data['link_value'];
            $item->setLinkValue($v !== null && $v !== '' ? (string) $v : null);
        }
        if (array_key_exists('link_extra', $data)) {
            $item->setLinkExtra($this->encodeLinkExtra($data['link_extra']));
        }
        if (isset($data['sort'])) {
            $item->setSort((int) $data['sort']);
        }
        if (isset($data['status'])) {
            $item->setStatus((int) $data['status']);
        }
        $item->setUpdatedAt(new \DateTimeImmutable());
        $this->em->flush();

        return $item;
    }

    public function deleteItem(int $itemId, int $menuId, int $companyId): void
    {
        $item = $this->itemRepo->findOneByIdMenuCompany($itemId, $menuId, $companyId);
        if (!$item) {
            throw new ResourceException('菜单项不存在');
        }
        $all = $this->itemRepo->findAllByMenu($menuId, $companyId);
        $ids = $this->collectDescendantIds($itemId, $all);
        foreach ($ids as $id) {
            $e = $this->itemRepo->find($id);
            if ($e) {
                $this->em->remove($e);
            }
        }
        $this->em->flush();
    }

    /**
     * @param WebMenuItem[] $allItems
     *
     * @return int[]
     */
    private function collectDescendantIds(int $rootId, array $allItems): array
    {
        $byParent = [];
        foreach ($allItems as $i) {
            $pid = $i->getParentId();
            if (!isset($byParent[$pid])) {
                $byParent[$pid] = [];
            }
            $byParent[$pid][] = $i->getId();
        }
        $ids = [];
        $stack = [$rootId];
        while ($stack) {
            $id = array_pop($stack);
            $ids[] = $id;
            if (!empty($byParent[$id])) {
                foreach ($byParent[$id] as $childId) {
                    $stack[] = $childId;
                }
            }
        }

        return $ids;
    }

    private function encodeLinkExtra($linkExtra): ?string
    {
        if ($linkExtra === null || $linkExtra === '') {
            return null;
        }
        if (is_string($linkExtra)) {
            return trim($linkExtra) !== '' ? $linkExtra : null;
        }
        if (!is_array($linkExtra) && !is_object($linkExtra)) {
            return null;
        }

        $encoded = json_encode($linkExtra, JSON_UNESCAPED_UNICODE);

        return $encoded !== false ? $encoded : null;
    }

    /**
     * @param array<int, array{id:int, sort:int}>|array<int|string, int> $sorts
     */
    public function batchUpdateSort(int $menuId, int $companyId, array $sorts): void
    {
        $menu = $this->menuRepo->findOneByIdAndCompany($menuId, $companyId);
        if (!$menu) {
            throw new ResourceException('菜单不存在');
        }
        if ($sorts === []) {
            return;
        }
        $pairs = [];
        if (isset($sorts[0]) && is_array($sorts[0])) {
            foreach ($sorts as $row) {
                if (!isset($row['id'])) {
                    continue;
                }
                $pairs[(int) $row['id']] = (int) ($row['sort'] ?? 0);
            }
        } else {
            foreach ($sorts as $id => $sort) {
                if (is_int($id) || ctype_digit((string) $id)) {
                    $pairs[(int) $id] = (int) $sort;
                }
            }
        }
        foreach ($pairs as $itemId => $sort) {
            $item = $this->itemRepo->findOneByIdMenuCompany($itemId, $menuId, $companyId);
            if ($item) {
                $item->setSort($sort);
                $item->setUpdatedAt(new \DateTimeImmutable());
            }
        }
        $this->em->flush();
    }

    /**
     * @param WebMenuItem[] $items
     *
     * @return array<int, array{item: WebMenuItem, children: array}>
     */
    public function buildTree(array $items): array
    {
        $indexed = [];
        foreach ($items as $item) {
            $indexed[$item->getId()] = ['item' => $item, 'children' => []];
        }
        $roots = [];
        foreach ($indexed as $id => &$node) {
            $pid = $node['item']->getParentId();
            if ($pid === 0) {
                $roots[] = &$node;
            } elseif (isset($indexed[$pid])) {
                $indexed[$pid]['children'][] = &$node;
            }
        }
        unset($node);

        return $roots;
    }
}
