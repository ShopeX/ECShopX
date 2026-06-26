<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use ShuyunOpenPlatformBundle\Entities\ShuyunOfflineBenefit;
use ShuyunOpenPlatformBundle\Entities\ShuyunOfflineBenefitSendBatch;
use ShuyunOpenPlatformBundle\Entities\ShuyunOfflineBenefitSendItem;
use TestCase;

/**
 * T3：不依赖已提交的 Version*.php，仅用内存 sqlite 连接加载注解元数据，断言表名与唯一约束（计划 §4.1）。
 */
class ShuyunOfflineBenefitEntityMappingTest extends TestCase
{
    private function createMetadataEntityManager(): EntityManager
    {
        $entityPath = realpath(__DIR__.'/../../src/ShuyunOpenPlatformBundle/Entities');
        $this->assertNotFalse($entityPath);

        $config = Setup::createAnnotationMetadataConfiguration(
            [$entityPath],
            true,
            null,
            null,
            false
        );

        return EntityManager::create(
            ['driver' => 'pdo_sqlite', 'memory' => true],
            $config
        );
    }

    public function testShuyunOfflineBenefitTableAndUniqueConstraint(): void
    {
        $em = $this->createMetadataEntityManager();
        $m = $em->getClassMetadata(ShuyunOfflineBenefit::class);
        $this->assertSame('shuyun_offline_benefit', $m->getTableName());
        $unique = $m->table['uniqueConstraints'] ?? [];
        $this->assertArrayHasKey('uk_shuyun_offline_benefit_company_benefit', $unique);
        $this->assertSame(['company_id', 'benefit_id'], $unique['uk_shuyun_offline_benefit_company_benefit']['columns']);
    }

    public function testShuyunOfflineBenefitSendBatchTableAndUniqueConstraint(): void
    {
        $em = $this->createMetadataEntityManager();
        $m = $em->getClassMetadata(ShuyunOfflineBenefitSendBatch::class);
        $this->assertSame('shuyun_offline_benefit_send_batch', $m->getTableName());
        $unique = $m->table['uniqueConstraints'] ?? [];
        $this->assertArrayHasKey('uk_shuyun_offline_benefit_batch_company_request', $unique);
        $this->assertSame(['company_id', 'request_id'], $unique['uk_shuyun_offline_benefit_batch_company_request']['columns']);
    }

    public function testShuyunOfflineBenefitSendItemTableAndUniqueConstraint(): void
    {
        $em = $this->createMetadataEntityManager();
        $m = $em->getClassMetadata(ShuyunOfflineBenefitSendItem::class);
        $this->assertSame('shuyun_offline_benefit_send_item', $m->getTableName());
        $unique = $m->table['uniqueConstraints'] ?? [];
        $this->assertArrayHasKey('uk_shuyun_offline_benefit_item_batch_customer', $unique);
        $this->assertSame(['batch_id', 'customer_id'], $unique['uk_shuyun_offline_benefit_item_batch_customer']['columns']);
    }

    public function testRepositoriesBoundInServiceProvider(): void
    {
        $r1 = $this->app->make(\ShuyunOpenPlatformBundle\Repositories\ShuyunOfflineBenefitRepository::class);
        $r2 = $this->app->make(\ShuyunOpenPlatformBundle\Repositories\ShuyunOfflineBenefitSendBatchRepository::class);
        $r3 = $this->app->make(\ShuyunOpenPlatformBundle\Repositories\ShuyunOfflineBenefitSendItemRepository::class);
        $this->assertInstanceOf(\ShuyunOpenPlatformBundle\Repositories\ShuyunOfflineBenefitRepository::class, $r1);
        $this->assertInstanceOf(\ShuyunOpenPlatformBundle\Repositories\ShuyunOfflineBenefitSendBatchRepository::class, $r2);
        $this->assertInstanceOf(\ShuyunOpenPlatformBundle\Repositories\ShuyunOfflineBenefitSendItemRepository::class, $r3);
    }
}
