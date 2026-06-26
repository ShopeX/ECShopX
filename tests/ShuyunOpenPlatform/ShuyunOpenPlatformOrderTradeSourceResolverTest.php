<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use Monolog\Handler\TestHandler;
use Monolog\Logger;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformOrderTradeSourceResolver;

class ShuyunOpenPlatformOrderTradeSourceResolverTest extends \TestCase
{
    public function testResolvesMappedOrderClassCaseInsensitively(): void
    {
        config(['shuyun_open_platform.order_class_trade_source_map' => ['normal' => '11']]);
        $resolver = new ShuyunOpenPlatformOrderTradeSourceResolver($this->testLogger());

        $this->assertSame('11', $resolver->resolveTradeSourceForOrder(9, 'ORD-1', 'normal'));
        $this->assertSame('11', $resolver->resolveTradeSourceForOrder(9, 'ORD-1', ' Normal '));
    }

    public function testUnknownOrderClassReturnsNullAndLogsErrorWithKeywordAndContext(): void
    {
        config(['shuyun_open_platform.order_class_trade_source_map' => ['normal' => '11']]);
        $handler = new TestHandler();
        $logger = new Logger('test');
        $logger->pushHandler($handler);

        $resolver = new ShuyunOpenPlatformOrderTradeSourceResolver($logger);
        $this->assertNull($resolver->resolveTradeSourceForOrder(42, 'ORD-X', 'unknown_class_xyz'));

        $this->assertTrue($handler->hasRecordThatContains(
            ShuyunOpenPlatformOrderTradeSourceResolver::UNKNOWN_LOG_KEYWORD,
            Logger::ERROR
        ));
        $records = $handler->getRecords();
        $last = $records[array_key_last($records)];
        $this->assertSame(42, $last['context']['company_id'] ?? null);
        $this->assertSame('ORD-X', $last['context']['order_id'] ?? null);
        $this->assertSame('unknown_class_xyz', $last['context']['order_class'] ?? null);
        $this->assertSame('unknown_class_xyz', $last['context']['order_class_normalized'] ?? null);
    }

    public function testBlankOrderClassReturnsNullAndLogs(): void
    {
        config(['shuyun_open_platform.order_class_trade_source_map' => ['normal' => '11']]);
        $handler = new TestHandler();
        $logger = new Logger('test');
        $logger->pushHandler($handler);

        $resolver = new ShuyunOpenPlatformOrderTradeSourceResolver($logger);
        $this->assertNull($resolver->resolveTradeSourceForOrder(1, 'O1', '   '));
        $this->assertTrue($handler->hasRecordThatPasses(
            static fn (array $r) => ($r['context']['reason'] ?? '') === 'empty_order_class',
            Logger::ERROR
        ));
    }

    public function testEmptyMapValueReturnsNullAndLogs(): void
    {
        config(['shuyun_open_platform.order_class_trade_source_map' => ['normal' => '   ']]);
        $handler = new TestHandler();
        $logger = new Logger('test');
        $logger->pushHandler($handler);

        $resolver = new ShuyunOpenPlatformOrderTradeSourceResolver($logger);
        $this->assertNull($resolver->resolveTradeSourceForOrder(1, 'O1', 'normal'));
        $this->assertTrue($handler->hasRecordThatPasses(
            static fn (array $r) => ($r['context']['reason'] ?? '') === 'empty_map_value',
            Logger::ERROR
        ));
    }

    private function testLogger(): Logger
    {
        $logger = new Logger('test');
        $logger->pushHandler(new TestHandler());

        return $logger;
    }
}
