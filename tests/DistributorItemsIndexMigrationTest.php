<?php

/**
 * TC-P0-1：distribution_distributor_items 含 (distributor_id, goods_id) 非唯一索引。
 *
 * @coversNothing
 */
final class DistributorItemsIndexMigrationTest extends TestCase
{
    /**
     * TC-P0-1：Entity 与 migration 均声明 idx_distributor_id_goods_id。
     * #given DistributorItems Entity 与最新 migration 文件
     * #when 读取源码
     * #then 含 idx_distributor_id_goods_id 及 distributor_id、goods_id 列
     */
    public function testTcP01EntityAndMigrationDeclareDistributorGoodsIndex(): void
    {
        #given
        $entityPath = __DIR__ . '/../src/DistributionBundle/Entities/DistributorItems.php';
        $migrationPath = __DIR__ . '/../database/migrations/Version20260813110000.php';

        #when
        $entitySource = file_get_contents($entityPath);
        $migrationSource = file_get_contents($migrationPath);

        #then
        $this->assertStringContainsString('idx_distributor_id_goods_id', $entitySource, 'TC-P0-1: Entity should declare idx_distributor_id_goods_id');
        $this->assertStringContainsString('distributor_id', $entitySource);
        $this->assertStringContainsString('goods_id', $entitySource);

        $this->assertStringContainsString('idx_distributor_id_goods_id', $migrationSource, 'TC-P0-1: migration should create idx_distributor_id_goods_id');
        $this->assertStringContainsString('distributor_id', $migrationSource);
        $this->assertStringContainsString('goods_id', $migrationSource);
        $this->assertStringNotContainsString('UNIQUE INDEX idx_distributor_id_goods_id', $migrationSource, 'TC-P0-1: index must not be UNIQUE');
    }
}
