<?php
/**
 * Copyright 2019-2026 ShopeX
 */

namespace Tests\ThemeBundle\Services;

use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;
use ThemeBundle\Entities\WebMenuItem;
use ThemeBundle\Repositories\WebMenuItemRepository;
use ThemeBundle\Repositories\WebMenuRepository;
use ThemeBundle\Services\WebMenuService;
use ThemeBundle\Entities\WebMenu;

class WebMenuServiceTest extends TestCase
{
    private function makeServiceWithRepos(
        WebMenuRepository $menuRepo,
        WebMenuItemRepository $itemRepo
    ): WebMenuService {
        $em = $this->createMock(EntityManager::class);
        $em->method('getRepository')
            ->willReturnMap([
                [WebMenu::class, $menuRepo],
                [WebMenuItem::class, $itemRepo],
            ]);

        return new WebMenuService($em);
    }

    private function itemWithId(int $id, int $parentId, string $name = ''): WebMenuItem
    {
        $item = new WebMenuItem();
        $ref = new \ReflectionProperty(WebMenuItem::class, 'id');
        $ref->setAccessible(true);
        $ref->setValue($item, $id);
        $item->setParentId($parentId);
        if ($name !== '') {
            $item->setName($name);
        }

        return $item;
    }

    public function testBuildTreeEmpty(): void
    {
        $service = $this->makeServiceWithRepos(
            $this->createMock(WebMenuRepository::class),
            $this->createMock(WebMenuItemRepository::class)
        );
        $this->assertSame([], $service->buildTree([]));
    }

    public function testBuildTreeAllRoot(): void
    {
        $service = $this->makeServiceWithRepos(
            $this->createMock(WebMenuRepository::class),
            $this->createMock(WebMenuItemRepository::class)
        );
        $items = [
            $this->itemWithId(1, 0, '首页'),
            $this->itemWithId(2, 0, '活动'),
        ];
        $tree = $service->buildTree($items);
        $this->assertCount(2, $tree);
        $this->assertCount(0, $tree[0]['children']);
        $this->assertCount(0, $tree[1]['children']);
    }

    public function testBuildTreeNoRootNodes(): void
    {
        $service = $this->makeServiceWithRepos(
            $this->createMock(WebMenuRepository::class),
            $this->createMock(WebMenuItemRepository::class)
        );
        $items = [
            $this->itemWithId(1, 99),
            $this->itemWithId(2, 99),
        ];
        $this->assertSame([], $service->buildTree($items));
    }

    public function testBuildTreeTwoRootsWithChildren(): void
    {
        $service = $this->makeServiceWithRepos(
            $this->createMock(WebMenuRepository::class),
            $this->createMock(WebMenuItemRepository::class)
        );
        $items = [
            $this->itemWithId(1, 0, '女装'),
            $this->itemWithId(2, 1, '连衣裙'),
            $this->itemWithId(3, 1, '半身裙'),
            $this->itemWithId(4, 0, '男装'),
        ];
        $tree = $service->buildTree($items);
        $this->assertCount(2, $tree);
        $this->assertCount(2, $tree[0]['children']);
        $this->assertCount(0, $tree[1]['children']);
        $this->assertSame('连衣裙', $tree[0]['children'][0]['item']->getName());
    }

    public function testBuildTreeNormal(): void
    {
        $menuRepo = $this->createMock(WebMenuRepository::class);
        $itemRepo = $this->createMock(WebMenuItemRepository::class);
        $service = $this->makeServiceWithRepos($menuRepo, $itemRepo);

        $items = [
            $this->itemWithId(1, 0),
            $this->itemWithId(2, 1),
            $this->itemWithId(3, 1),
            $this->itemWithId(4, 2),
        ];

        $tree = $service->buildTree($items);

        $this->assertCount(1, $tree);
        $this->assertSame(1, $tree[0]['item']->getId());
        $this->assertCount(2, $tree[0]['children']);
        $childIds = array_map(fn ($n) => $n['item']->getId(), $tree[0]['children']);
        $this->assertContains(2, $childIds);
        $this->assertContains(3, $childIds);

        $byId = [];
        foreach ($tree[0]['children'] as $n) {
            $byId[$n['item']->getId()] = $n;
        }
        $this->assertCount(1, $byId[2]['children']);
        $this->assertSame(4, $byId[2]['children'][0]['item']->getId());
    }

    public function testBuildTreeOrphanExcluded(): void
    {
        $menuRepo = $this->createMock(WebMenuRepository::class);
        $itemRepo = $this->createMock(WebMenuItemRepository::class);
        $service = $this->makeServiceWithRepos($menuRepo, $itemRepo);

        $items = [
            $this->itemWithId(1, 0),
            $this->itemWithId(2, 1),
            $this->itemWithId(5, 999),
        ];

        $tree = $service->buildTree($items);

        $this->assertCount(1, $tree);
        $this->assertSame(1, $tree[0]['item']->getId());
        $ids = [];
        $walk = function (array $nodes) use (&$walk, &$ids): void {
            foreach ($nodes as $n) {
                $ids[] = $n['item']->getId();
                $walk($n['children']);
            }
        };
        $walk($tree);
        $this->assertSame([1, 2], $ids);
    }
}
