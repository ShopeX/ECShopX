<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use ShuyunOpenPlatformBundle\Entities\CompanyShuyunOpenPlatformConfig;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformTokenRefreshService;

class ShuyunOpenPlatformTokenRefreshServiceTest extends TestCase
{
    private function configRow(string $appId = '5', ?string $isOverDue = '0'): CompanyShuyunOpenPlatformConfig
    {
        $e = new CompanyShuyunOpenPlatformConfig();
        $e->setCompanyId(1);
        $e->setAuthValue('av');
        $e->setAppId($appId);
        $e->setAppSecret('sec');
        $e->setIsEnabled(1);
        if ($isOverDue !== null) {
            $e->setIsOverDue($isOverDue);
        }

        return $e;
    }

    /** @see .tasks/plans/shuyun-open-platform-auth-automation.md TC-REFRESH-01 */
    public function testMockRefreshSuccessIssuesGetToTokenPath(): void
    {
        $container = [];
        $history = Middleware::history($container);
        $mock = new MockHandler([
            new Response(200, [], ''),
        ]);
        $stack = HandlerStack::create($mock);
        $stack->push($history);
        $client = new Client(['handler' => $stack]);

        $sut = new ShuyunOpenPlatformTokenRefreshService(
            'http://open-client.test',
            5.0,
            $client
        );

        $ok = $sut->triggerRefresh($this->configRow('99'));
        $this->assertTrue($ok);
        $this->assertCount(1, $container);
        $req = $container[0]['request'];
        $this->assertSame('GET', $req->getMethod());
        $this->assertStringEndsWith('/client/callback/token/99/v2', (string) $req->getUri());
    }

    /** @see .tasks/plans/shuyun-open-platform-auth-automation.md TC-REFRESH-02 */
    public function testMockRefreshFailureReturnsFalseAndDoesNotMutateConfig(): void
    {
        $mock = new MockHandler([
            new Response(500, [], 'err'),
        ]);
        $client = new Client(['handler' => HandlerStack::create($mock)]);

        $sut = new ShuyunOpenPlatformTokenRefreshService(
            'http://open-client.test',
            5.0,
            $client
        );

        $row = $this->configRow();
        $row->setAccessToken('OLD-TOKEN');
        $before = $row->getAccessToken();

        $ok = $sut->triggerRefresh($row);
        $this->assertFalse($ok);
        $this->assertSame($before, $row->getAccessToken());
    }

    public function testSkipsHttpWhenAppIdMissing(): void
    {
        $container = [];
        $mock = new MockHandler([new Response(200)]);
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($container));
        $client = new Client(['handler' => $stack]);

        $sut = new ShuyunOpenPlatformTokenRefreshService('http://open-client.test', 5.0, $client);
        $row = $this->configRow();
        $row->setAppId(null);

        $this->assertFalse($sut->triggerRefresh($row));
        $this->assertCount(0, $container);
    }

    public function testSkipsWhenIsOverDue(): void
    {
        $container = [];
        $mock = new MockHandler([new Response(200)]);
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($container));
        $client = new Client(['handler' => $stack]);

        $sut = new ShuyunOpenPlatformTokenRefreshService('http://open-client.test', 5.0, $client);

        $this->assertFalse($sut->triggerRefresh($this->configRow('1', '1')));
        $this->assertCount(0, $container);
    }

    public function testSkipsWhenIsEnabledIsZeroUnlessIgnoreFlagSet(): void
    {
        $container = [];
        $mock = new MockHandler([new Response(200)]);
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($container));
        $client = new Client(['handler' => $stack]);

        $sut = new ShuyunOpenPlatformTokenRefreshService('http://open-client.test', 5.0, $client);
        $row = $this->configRow('88');
        $row->setIsEnabled(0);

        $this->assertFalse($sut->triggerRefresh($row));
        $this->assertCount(0, $container);

        $this->assertTrue($sut->triggerRefresh($row, true));
        $this->assertCount(1, $container);
    }

    public function testBuildRefreshUriEncodesAppId(): void
    {
        $client = new Client(['handler' => new MockHandler([new Response(200)])]);
        $sut = new ShuyunOpenPlatformTokenRefreshService('http://open-client.test/', 1.0, $client);
        $this->assertSame(
            'http://open-client.test/client/callback/token/a%2Fb/v2',
            $sut->buildRefreshUri('a/b')
        );
    }

}
