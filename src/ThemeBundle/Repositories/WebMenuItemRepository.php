<?php
/**
 * Copyright 2019-2026 ShopeX
 */

namespace ThemeBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use ThemeBundle\Entities\WebMenuItem;

class WebMenuItemRepository extends EntityRepository
{
    /**
     * 前台：仅启用项
     *
     * @return WebMenuItem[]
     */
    public function findActiveByMenu(int $menuId, int $companyId): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.menuId = :mid AND i.companyId = :cid AND i.status = 1')
            ->setParameter('mid', $menuId)
            ->setParameter('cid', $companyId)
            ->orderBy('i.parentId', 'ASC')
            ->addOrderBy('i.sort', 'ASC')
            ->addOrderBy('i.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 后台：含禁用项
     *
     * @return WebMenuItem[]
     */
    public function findAllByMenu(int $menuId, int $companyId): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.menuId = :mid AND i.companyId = :cid')
            ->setParameter('mid', $menuId)
            ->setParameter('cid', $companyId)
            ->orderBy('i.parentId', 'ASC')
            ->addOrderBy('i.sort', 'ASC')
            ->addOrderBy('i.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByIdMenuCompany(int $itemId, int $menuId, int $companyId): ?WebMenuItem
    {
        return $this->findOneBy([
            'id'        => $itemId,
            'menuId'    => $menuId,
            'companyId' => $companyId,
        ]);
    }

    /**
     * 批量统计各菜单下的菜单项数量（含子项）
     *
     * @param int[] $menuIds
     *
     * @return array<int, int> menu_id => count
     */
    public function countItemsByMenuIds(int $companyId, array $menuIds): array
    {
        $menuIds = array_values(array_unique(array_filter(array_map('intval', $menuIds))));
        if ($menuIds === []) {
            return [];
        }
        $qb = $this->createQueryBuilder('i')
            ->select('i.menuId AS menuId')
            ->addSelect('COUNT(i.id) AS cnt')
            ->where('i.companyId = :cid')
            ->andWhere('i.menuId IN (:mids)')
            ->setParameter('cid', $companyId)
            ->setParameter('mids', $menuIds)
            ->groupBy('i.menuId');

        $out = array_fill_keys($menuIds, 0);
        foreach ($qb->getQuery()->getScalarResult() as $row) {
            $mid = (int) ($row['menuId'] ?? $row['menu_id'] ?? 0);
            $cnt = (int) ($row['cnt'] ?? 0);
            if ($mid > 0) {
                $out[$mid] = $cnt;
            }
        }

        return $out;
    }

    /**
     * 批量查询各菜单下「一级」菜单项名称（parent_id = 0），按 sort、id 排序后供列表展示
     *
     * @param int[] $menuIds
     *
     * @return array<int, string> menu_id => 名称以英文逗号连接
     */
    public function findTopLevelItemNamesGroupedByMenuIds(int $companyId, array $menuIds): array
    {
        $menuIds = array_values(array_unique(array_filter(array_map('intval', $menuIds))));
        if ($menuIds === []) {
            return [];
        }
        $qb = $this->createQueryBuilder('i')
            ->select('i.menuId AS menuId')
            ->addSelect('i.name AS name')
            ->where('i.companyId = :cid')
            ->andWhere('i.menuId IN (:mids)')
            ->andWhere('i.parentId = 0')
            ->setParameter('cid', $companyId)
            ->setParameter('mids', $menuIds)
            ->orderBy('i.menuId', 'ASC')
            ->addOrderBy('i.sort', 'ASC')
            ->addOrderBy('i.id', 'ASC');

        $grouped = [];
        foreach ($menuIds as $mid) {
            $grouped[$mid] = [];
        }
        foreach ($qb->getQuery()->getScalarResult() as $row) {
            $mid = (int) ($row['menuId'] ?? $row['menu_id'] ?? 0);
            $name = isset($row['name']) ? trim((string) $row['name']) : '';
            if ($mid > 0 && $name !== '') {
                $grouped[$mid][] = $name;
            }
        }
        $out = [];
        foreach ($grouped as $mid => $names) {
            $out[$mid] = $names === [] ? '' : implode(',', $names);
        }

        return $out;
    }
}
