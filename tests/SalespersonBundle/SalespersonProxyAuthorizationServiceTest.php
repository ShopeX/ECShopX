<?php

declare(strict_types=1);

namespace Tests\SalespersonBundle;

use Dingo\Api\Exception\ResourceException;
use SalespersonBundle\Services\SalespersonProxyAuthorizationService;

/**
 * TC-PA-01～10：导购代客授权 Service 单元测试。
 * 计划：.tasks/plans/cnvd-proxy-order-auth-fix.md
 */
class SalespersonProxyAuthorizationServiceTest extends \TestCase
{
    /** @var object|null */
    private $mockSalespersonRepo;

    /** @var object|null */
    private $mockShopsRelRepo;

    /**
     * 最小 Lumen 容器，避免 bootstrap/app.php 触发 Doctrine DB 连接。
     */
    public function createApplication()
    {
        $app = new \Laravel\Lumen\Application(dirname(__DIR__, 2));
        $app->withFacades();

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockSalespersonRepo = null;
        $this->mockShopsRelRepo = null;
    }

    /**
     * TC-PA-01 / S-A1：非导购 + buy_user_id 他人 → 抛 ResourceException。
     * #given auth=100 无 salesperson 记录，buy=200
     * #when 调用 assertCanProxyAsSalesperson
     * #then 抛 ResourceException，消息含「无权代客」
     */
    public function testTcPa01NonSalespersonProxyingForOthersThrows(): void
    {
        $this->mockSalespersonRepo = $this->createMockSalespersonRepo([
            'getInfo' => [],
        ]);

        $service = $this->createService();

        try {
            $service->assertCanProxyAsSalesperson(1, 100, 200);
            $this->fail('Expected ResourceException for non-salesperson proxy attempt');
        } catch (ResourceException $e) {
            $this->assertStringContainsString('无权代客', $e->getMessage());
        }
    }

    /**
     * TC-PA-02 / S-A2：有效导购 + 匹配 distributor_id → 允许代客。
     * #given auth=100 为有效 shopping_guide，shop_id=5，buy=200，distributor=5
     * #when 调用 assertCanProxyAsSalesperson
     * #then 不抛异常
     */
    public function testTcPa02ValidSalespersonWithMatchingShopAllowsProxy(): void
    {
        $this->mockSalespersonRepo = $this->createMockSalespersonRepo([
            'getInfo' => [
                'salesperson_id' => 10,
                'user_id' => 100,
                'company_id' => 1,
                'is_valid' => 'true',
                'salesperson_type' => 'shopping_guide',
                'shop_id' => 5,
            ],
        ]);

        $service = $this->createService();
        $service->assertCanProxyAsSalesperson(1, 100, 200, 5);
        $this->assertTrue(true, 'Valid salesperson with matching shop should pass');
    }

    /**
     * TC-PA-03 / S-A3：有效导购 + 错误 distributor_id → 拒绝。
     * #given auth=100 为有效导购，绑定 shop_id=5，distributor=99
     * #when 调用 assertCanProxyAsSalesperson
     * #then 抛 ResourceException
     */
    public function testTcPa03ValidSalespersonWithWrongDistributorRejects(): void
    {
        $this->mockSalespersonRepo = $this->createMockSalespersonRepo([
            'getInfo' => [
                'salesperson_id' => 10,
                'user_id' => 100,
                'company_id' => 1,
                'is_valid' => 'true',
                'salesperson_type' => 'shopping_guide',
                'shop_id' => 5,
            ],
        ]);
        $this->mockShopsRelRepo = $this->createMockShopsRelRepo([
            'getInfo' => [],
        ]);

        $service = $this->createService();

        try {
            $service->assertCanProxyAsSalesperson(1, 100, 200, 99);
            $this->fail('Expected ResourceException for wrong distributor_id');
        } catch (ResourceException $e) {
            $this->assertStringContainsString('无权代客', $e->getMessage());
        }
    }

    /**
     * TC-PA-04 / S-A4：有效导购 + distributor_id 空/null → 公司级允许。
     * #given auth=100 为有效导购，distributor 为 null/0/'' 
     * #when 调用 assertCanProxyAsSalesperson
     * #then 不抛异常
     */
    public function testTcPa04ValidSalespersonWithoutDistributorAllowsCompanyLevelProxy(): void
    {
        $this->mockSalespersonRepo = $this->createMockSalespersonRepo([
            'getInfo' => [
                'salesperson_id' => 10,
                'user_id' => 100,
                'company_id' => 1,
                'is_valid' => 'true',
                'salesperson_type' => 'shopping_guide',
            ],
        ]);

        $service = $this->createService();

        $service->assertCanProxyAsSalesperson(1, 100, 200, null);
        $service->assertCanProxyAsSalesperson(1, 100, 200, 0);
        $service->assertCanProxyAsSalesperson(1, 100, 200, '');
        $this->assertTrue(true, 'Company-level proxy without distributor should pass');
    }

    /**
     * TC-PA-05 / S-A5：is_valid 非 true → 拒绝。
     * #given auth=100 的导购 is_valid='false'，getInfo 查不到有效记录
     * #when 调用 assertCanProxyAsSalesperson
     * #then 抛 ResourceException
     */
    public function testTcPa05InvalidSalespersonIsValidFalseRejects(): void
    {
        $this->mockSalespersonRepo = $this->createMockSalespersonRepo([
            'getInfo' => [],
        ]);

        $service = $this->createService();

        try {
            $service->assertCanProxyAsSalesperson(1, 100, 200);
            $this->fail('Expected ResourceException for invalid salesperson');
        } catch (ResourceException $e) {
            $this->assertStringContainsString('无权代客', $e->getMessage());
        }
    }

    /**
     * TC-PA-06 / S-A6：salesperson_type=admin → 拒绝。
     * #given auth=100 仅有 admin 类型记录，getInfo 按 shopping_guide 查不到
     * #when 调用 assertCanProxyAsSalesperson
     * #then 抛 ResourceException
     */
    public function testTcPa06AdminTypeSalespersonRejects(): void
    {
        $this->mockSalespersonRepo = $this->createMockSalespersonRepo([
            'getInfo' => [],
        ]);

        $service = $this->createService();

        try {
            $service->assertCanProxyAsSalesperson(1, 100, 200);
            $this->fail('Expected ResourceException for admin type salesperson');
        } catch (ResourceException $e) {
            $this->assertStringContainsString('无权代客', $e->getMessage());
        }
    }

    /**
     * TC-PA-07 / S-A7：无 salesperson 行（仅有 promoter）→ 拒绝。
     * #given auth=100 无 shop_salesperson 记录
     * #when 调用 assertCanProxyAsSalesperson
     * #then 抛 ResourceException
     */
    public function testTcPa07NoSalespersonRecordRejects(): void
    {
        $this->mockSalespersonRepo = $this->createMockSalespersonRepo([
            'getInfo' => [],
        ]);

        $service = $this->createService();

        try {
            $service->assertCanProxyAsSalesperson(1, 100, 200);
            $this->fail('Expected ResourceException when no salesperson record exists');
        } catch (ResourceException $e) {
            $this->assertStringContainsString('无权代客', $e->getMessage());
        }
    }

    /**
     * TC-PA-08 / S-A9：buy_user_id 缺省或等于 auth → 自购放行。
     * #given auth=100，buy 为 null/0/'' 或 100
     * #when 调用 assertCanProxyAsSalesperson
     * #then 不抛异常且不查 salesperson
     */
    public function testTcPa08SelfPurchaseBypassesSalespersonCheck(): void
    {
        $getInfoCalled = false;
        $this->mockSalespersonRepo = $this->createMockSalespersonRepo([
            'getInfo' => function () use (&$getInfoCalled) {
                $getInfoCalled = true;

                return [];
            },
        ]);

        $service = $this->createService();

        $service->assertCanProxyAsSalesperson(1, 100, null);
        $service->assertCanProxyAsSalesperson(1, 100, 0);
        $service->assertCanProxyAsSalesperson(1, 100, '');
        $service->assertCanProxyAsSalesperson(1, 100, 100);
        $service->assertCanProxyAsSalesperson(1, 100, '100');

        $this->assertFalse($getInfoCalled, 'Self purchase should not query salesperson');
    }

    /**
     * TC-PA-09 / S-A10：Service 层校验 authUserId 须为有效导购（promoter 门控在 Controller）。
     * #given auth=100 非导购，buy=200（即便 200 本身是导购）
     * #when 调用 assertCanProxyAsSalesperson
     * #then 抛 ResourceException
     */
    public function testTcPa09AuthUserMustBeValidSalespersonNotBuyUser(): void
    {
        $this->mockSalespersonRepo = $this->createMockSalespersonRepo([
            'getInfo' => function (array $filter) {
                // 仅校验 authUserId=100，buyUserId=200 的导购身份 irrelevant
                if ((int) ($filter['user_id'] ?? 0) === 100) {
                    return [];
                }

                return [
                    'salesperson_id' => 20,
                    'user_id' => 200,
                    'company_id' => 1,
                    'is_valid' => 'true',
                    'salesperson_type' => 'shopping_guide',
                ];
            },
        ]);

        $service = $this->createService();

        try {
            $service->assertCanProxyAsSalesperson(1, 100, 200);
            $this->fail('Expected ResourceException when auth user is not a valid salesperson');
        } catch (ResourceException $e) {
            $this->assertStringContainsString('无权代客', $e->getMessage());
        }
    }

    /**
     * TC-PA-10 / S-A8：同人兼有效 shopping_guide 与推广员 → 按导购规则允许。
     * #given auth=100 为有效 shopping_guide（可同时是 promoter）
     * #when 调用 assertCanProxyAsSalesperson 代 buy=200
     * #then 不抛异常
     */
    public function testTcPa10DualRoleShoppingGuideAllowsProxy(): void
    {
        $this->mockSalespersonRepo = $this->createMockSalespersonRepo([
            'getInfo' => [
                'salesperson_id' => 10,
                'user_id' => 100,
                'company_id' => 1,
                'is_valid' => 'true',
                'salesperson_type' => 'shopping_guide',
            ],
        ]);

        $service = $this->createService();
        $service->assertCanProxyAsSalesperson(1, 100, 200);
        $this->assertTrue(true, 'Valid shopping_guide should allow proxy regardless of promoter role');
    }

    private function createMockSalespersonRepo(array $methods): object
    {
        $mock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(array_keys($methods))
            ->getMock();

        foreach ($methods as $method => $return) {
            if (is_callable($return)) {
                $mock->method($method)->willReturnCallback($return);
            } else {
                $mock->method($method)->willReturn($return);
            }
        }

        return $mock;
    }

    private function createMockShopsRelRepo(array $methods): object
    {
        $mock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(array_keys($methods))
            ->getMock();

        foreach ($methods as $method => $return) {
            if (is_callable($return)) {
                $mock->method($method)->willReturnCallback($return);
            } else {
                $mock->method($method)->willReturn($return);
            }
        }

        return $mock;
    }

    /**
     * TC-RES-01 / S-R1：有效导购代客返回 buy_user_id。
     * #given auth=100 为有效 shopping_guide，promoter=100，buy=200，distributor=5
     * #when 调用 resolveActingUserId
     * #then 返回 200
     */
    public function testTcRes01ValidSalespersonProxyReturnsBuyUserId(): void
    {
        $this->mockSalespersonRepo = $this->createMockSalespersonRepo([
            'getInfo' => [
                'salesperson_id' => 10,
                'user_id' => 100,
                'company_id' => 1,
                'is_valid' => 'true',
                'salesperson_type' => 'shopping_guide',
                'shop_id' => 5,
            ],
        ]);

        $service = $this->createService();

        $actingUserId = $service->resolveActingUserId(
            ['user_id' => 100, 'company_id' => 1],
            ['promoter_user_id' => 100, 'buy_user_id' => 200, 'distributor_id' => 5]
        );

        $this->assertSame(200, $actingUserId);
    }

    /**
     * TC-RES-02 / S-R2：非导购代客抛 ResourceException。
     * #given auth=100 无 salesperson 记录，promoter=100，buy=200
     * #when 调用 resolveActingUserId
     * #then 抛 ResourceException，消息含「无权代客」
     */
    public function testTcRes02NonSalespersonProxyThrows(): void
    {
        $this->mockSalespersonRepo = $this->createMockSalespersonRepo([
            'getInfo' => [],
        ]);

        $service = $this->createService();

        try {
            $service->resolveActingUserId(
                ['user_id' => 100, 'company_id' => 1],
                ['promoter_user_id' => 100, 'buy_user_id' => 200]
            );
            $this->fail('Expected ResourceException for non-salesperson proxy attempt');
        } catch (ResourceException $e) {
            $this->assertStringContainsString('无权代客', $e->getMessage());
        }
    }

    /**
     * TC-RES-04 / S-R4：自购短路返回 auth，不查 salesperson。
     * #given auth=100，promoter=100，buy 为 null/0/'' 或 100
     * #when 调用 resolveActingUserId
     * #then 返回 100 且不查 salesperson
     */
    public function testTcRes04SelfPurchaseReturnsAuthWithoutSalespersonLookup(): void
    {
        $getInfoCalled = false;
        $this->mockSalespersonRepo = $this->createMockSalespersonRepo([
            'getInfo' => function () use (&$getInfoCalled) {
                $getInfoCalled = true;

                return [];
            },
        ]);

        $service = $this->createService();
        $authInfo = ['user_id' => 100, 'company_id' => 1];
        $promoterInput = ['promoter_user_id' => 100];

        $this->assertSame(100, $service->resolveActingUserId($authInfo, $promoterInput + ['buy_user_id' => null]));
        $this->assertSame(100, $service->resolveActingUserId($authInfo, $promoterInput + ['buy_user_id' => 0]));
        $this->assertSame(100, $service->resolveActingUserId($authInfo, $promoterInput + ['buy_user_id' => '']));
        $this->assertSame(100, $service->resolveActingUserId($authInfo, $promoterInput + ['buy_user_id' => 100]));
        $this->assertFalse($getInfoCalled, 'Self purchase should not query salesperson');
    }

    /**
     * TC-RES-03 / S-R3：仅 buy 无 promoter → 返回 auth，不切换。
     * #given auth=100，input 仅有 buy_user_id=200，无 promoter
     * #when 调用 resolveActingUserId
     * #then 返回 100 且不查 salesperson
     */
    public function testTcRes03BuyOnlyWithoutPromoterReturnsAuth(): void
    {
        $getInfoCalled = false;
        $this->mockSalespersonRepo = $this->createMockSalespersonRepo([
            'getInfo' => function () use (&$getInfoCalled) {
                $getInfoCalled = true;

                return [
                    'salesperson_id' => 10,
                    'user_id' => 100,
                    'company_id' => 1,
                    'is_valid' => 'true',
                    'salesperson_type' => 'shopping_guide',
                ];
            },
        ]);

        $service = $this->createService();

        $actingUserId = $service->resolveActingUserId(
            ['user_id' => 100, 'company_id' => 1],
            ['buy_user_id' => 200]
        );

        $this->assertSame(100, $actingUserId);
        $this->assertFalse($getInfoCalled, 'Buy-only without promoter should not query salesperson');
    }

    /**
     * TC-RES-05 / S-R5：promoter≠auth → 静默返回 auth，不抛错。
     * #given auth=100，promoter=999，buy=200
     * #when 调用 resolveActingUserId
     * #then 返回 100 且不查 salesperson
     */
    public function testTcRes05PromoterMismatchReturnsAuthSilently(): void
    {
        $getInfoCalled = false;
        $this->mockSalespersonRepo = $this->createMockSalespersonRepo([
            'getInfo' => function () use (&$getInfoCalled) {
                $getInfoCalled = true;

                return [];
            },
        ]);

        $service = $this->createService();

        $actingUserId = $service->resolveActingUserId(
            ['user_id' => 100, 'company_id' => 1],
            ['promoter_user_id' => 999, 'buy_user_id' => 200]
        );

        $this->assertSame(100, $actingUserId);
        $this->assertFalse($getInfoCalled, 'Promoter mismatch should not query salesperson');
    }

    /**
     * TC-RES-06 / S-R7：错误 distributor_id → 抛 ResourceException。
     * #given auth=100 为有效导购 shop_id=5，promoter=100，buy=200，distributor=99
     * #when 调用 resolveActingUserId
     * #then 抛 ResourceException
     */
    public function testTcRes06WrongDistributorThrows(): void
    {
        $this->mockSalespersonRepo = $this->createMockSalespersonRepo([
            'getInfo' => [
                'salesperson_id' => 10,
                'user_id' => 100,
                'company_id' => 1,
                'is_valid' => 'true',
                'salesperson_type' => 'shopping_guide',
                'shop_id' => 5,
            ],
        ]);
        $this->mockShopsRelRepo = $this->createMockShopsRelRepo([
            'getInfo' => [],
        ]);

        $service = $this->createService();

        try {
            $service->resolveActingUserId(
                ['user_id' => 100, 'company_id' => 1],
                ['promoter_user_id' => 100, 'buy_user_id' => 200, 'distributor_id' => 99]
            );
            $this->fail('Expected ResourceException for wrong distributor_id');
        } catch (ResourceException $e) {
            $this->assertStringContainsString('无权代客', $e->getMessage());
        }
    }

    /**
     * TC-RES-07 / S-R6：地址场景（迁移自 TC-ADDR-02）经 resolve 返回 buy。
     * #given auth=100 为有效导购，promoter=100，buy=200，无 distributor_id
     * #when 调用 resolveActingUserId
     * #then 返回 200
     */
    public function testTcRes07AddressScenarioReturnsBuyUserId(): void
    {
        $this->mockSalespersonRepo = $this->createMockSalespersonRepo([
            'getInfo' => [
                'salesperson_id' => 10,
                'user_id' => 100,
                'company_id' => 1,
                'is_valid' => 'true',
                'salesperson_type' => 'shopping_guide',
            ],
        ]);

        $service = $this->createService();

        $actingUserId = $service->resolveActingUserId(
            ['user_id' => 100, 'company_id' => 1],
            ['promoter_user_id' => 100, 'buy_user_id' => 200]
        );

        $this->assertSame(200, $actingUserId);
    }

    private function createService(): SalespersonProxyAuthorizationService
    {
        if ($this->mockShopsRelRepo === null) {
            $this->mockShopsRelRepo = $this->createMockShopsRelRepo([
                'getInfo' => [],
            ]);
        }

        return new SalespersonProxyAuthorizationService(
            $this->mockSalespersonRepo,
            $this->mockShopsRelRepo
        );
    }
}
