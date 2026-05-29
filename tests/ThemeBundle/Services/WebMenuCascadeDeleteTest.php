<?php
/**
 * Copyright 2019-2026 ShopeX
 */

namespace Tests\ThemeBundle\Services;

use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;
use ThemeBundle\Entities\WebMenu;
use ThemeBundle\Entities\WebMenuItem;
use ThemeBundle\Repositories\WebMenuItemRepository;
use ThemeBundle\Repositories\WebMenuRepository;
use ThemeBundle\Services\WebMenuService;

class WebMenuCascadeDeleteTest extends TestCase
{
    private function menuWithId(int $id): WebMenu
    {
        $m = new WebMenu();
        $r = new \ReflectionProperty(WebMenu::class, 'id');
        $r->setAccessible(true);
        $r->setValue($m, $id);

        return $m;
    }

    private function itemWithId(int $id, int $parentId): WebMenuItem
    {
        $item = new WebMenuItem();
        $r = new \ReflectionProperty(WebMenuItem::class, 'id');
        $r->setAccessible(true);
        $r->setValue($item, $id);
        $item->setParentId($parentId);

        return $item;
    }

    public function testDeleteMenuDeletesItemsThenMenu(): void
    {
        $menu = $this->menuWithId(10);

        $menuRepo = $this->createMock(WebMenuRepository::class);
        $menuRepo->expects($this->once())
            ->method('findOneByIdAndCompany')
            ->with(10, 3)
            ->willReturn($menu);

        $itemRepo = $this->createMock(WebMenuItemRepository::class);

        $query = new class () {
            public function setParameter($_name, $_value): self
            {
                return $this;
            }

            public function execute(): int
            {
                return 1;
            }
        };

        $em = $this->createMock(EntityManager::class);
        $em->method('getRepository')
            ->willReturnMap([
                [WebMenu::class, $menuRepo],
                [WebMenuItem::class, $itemRepo],
            ]);
        $em->expects($this->once())
            ->method('createQuery')
            ->with($this->stringContains('DELETE ThemeBundle\Entities\WebMenuItem'))
            ->willReturn($query);
        $em->expects($this->once())
            ->method('remove')
            ->with($menu);
        $em->expects($this->once())
            ->method('flush');

        $service = new WebMenuService($em);
        $service->deleteMenu(10, 3);
    }

    public function testDeleteItemRemovesSubtree(): void
    {
        $root = $this->itemWithId(1, 0);
        $c1 = $this->itemWithId(2, 1);
        $c2 = $this->itemWithId(3, 2);

        $menuRepo = $this->createMock(WebMenuRepository::class);
        $itemRepo = $this->createMock(WebMenuItemRepository::class);
        $itemRepo->expects($this->once())
            ->method('findOneByIdMenuCompany')
            ->with(1, 5, 3)
            ->willReturn($root);
        $itemRepo->expects($this->once())
            ->method('findAllByMenu')
            ->with(5, 3)
            ->willReturn([$root, $c1, $c2]);

        $itemRepo->expects($this->exactly(3))
            ->method('find')
            ->willReturnCallback(function ($id) use ($root, $c1, $c2) {
                return [1 => $root, 2 => $c1, 3 => $c2][$id] ?? null;
            });

        $em = $this->createMock(EntityManager::class);
        $em->method('getRepository')
            ->willReturnMap([
                [WebMenu::class, $menuRepo],
                [WebMenuItem::class, $itemRepo],
            ]);

        $removed = [];
        $em->expects($this->exactly(3))
            ->method('remove')
            ->willReturnCallback(function ($e) use (&$removed): void {
                $removed[] = $e;
            });
        $em->expects($this->once())->method('flush');

        $service = new WebMenuService($em);
        $service->deleteItem(1, 5, 3);

        $this->assertCount(3, $removed);
    }
}
