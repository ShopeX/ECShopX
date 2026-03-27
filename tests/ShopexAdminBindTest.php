<?php

/**
 * 计划：.tasks/plans/admin-local-login-shopex-node.md — Shopex 本地 admin 可选绑定（§4.2、AC、TC-8/TC-9 等）。
 */

use CompanysBundle\Ego\PrismEgo;
use CompanysBundle\Entities\Operators;
use CompanysBundle\Repositories\OperatorsRepository;
use CompanysBundle\Services\ShopexAdminBindService;
use Dingo\Api\Exception\ResourceException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ShopexAdminBindTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testOperatorEntityShopexBindAccountGetterSetter(): void
    {
        $entity = new Operators();
        $entity->setShopexBindAccount('shopex-account@example.com');
        $this->assertSame('shopex-account@example.com', $entity->getShopexBindAccount());
    }

    public function testIsOperatorShopexBoundFalseWhenBindAccountMissing(): void
    {
        $row = [
            'passport_uid' => 'u1',
            'eid' => 'e1',
            'shopex_bind_account' => '',
            'company_id' => 1,
        ];
        $this->assertFalse(ShopexAdminBindService::isOperatorShopexBound($row));
    }

    public function testIsOperatorShopexBoundTrueWhenDbAndPrismCertPresent(): void
    {
        $passportUid = 'u_test';
        $companyId = 100;
        $redisKey = 'prism:'.sha1($passportUid.'_'.$companyId.'_SaasCert');

        $prismConn = $this->getMockBuilder(\stdClass::class)->addMethods(['get', 'set', 'del'])->getMock();
        $prismConn->method('get')->with($redisKey)->willReturn(json_encode([
            'cert_id' => 'c1',
            'node_id' => 'n1',
            'token' => 't1',
        ]));

        $redis = $this->getMockBuilder(\stdClass::class)->addMethods(['connection'])->getMock();
        $redis->method('connection')->with('prism')->willReturn($prismConn);
        $this->app->instance('redis', $redis);

        $row = [
            'passport_uid' => $passportUid,
            'eid' => 'e1',
            'shopex_bind_account' => 'bound@example.com',
            'company_id' => $companyId,
        ];
        $this->assertTrue(ShopexAdminBindService::isOperatorShopexBound($row));
    }

    public function testBindReturns409WhenAlreadyBound(): void
    {
        $passportUid = 'u_bound';
        $companyId = 200;
        $redisKey = 'prism:'.sha1($passportUid.'_'.$companyId.'_SaasCert');

        $prismConn = $this->getMockBuilder(\stdClass::class)->addMethods(['get', 'set', 'del'])->getMock();
        $prismConn->method('get')->with($redisKey)->willReturn(json_encode([
            'cert_id' => 'c1',
            'node_id' => 'n1',
            'token' => 't1',
        ]));
        $redis = $this->getMockBuilder(\stdClass::class)->addMethods(['connection', 'incr', 'expire', 'del', 'get'])->getMock();
        $redis->method('connection')->with('prism')->willReturn($prismConn);
        $this->app->instance('redis', $redis);

        $repo = $this->getMockBuilder(OperatorsRepository::class)->disableOriginalConstructor()->getMock();
        $repo->method('getInfo')->with(['operator_id' => 1])->willReturn([
            'operator_id' => 1,
            'operator_type' => 'admin',
            'company_id' => $companyId,
            'mobile' => '13800000001',
            'passport_uid' => $passportUid,
            'eid' => 'e1',
            'shopex_bind_account' => 'acc',
        ]);

        $service = new ShopexAdminBindService($repo);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('已绑定 Shopex');
        try {
            $service->bindForAdminOperator(1, ['username' => 'u', 'password' => 'p']);
        } catch (HttpException $e) {
            $this->assertSame(409, $e->getStatusCode());
            throw $e;
        }
    }

    public function testBindRejectsWhenPassportUidOwnedByOtherOperator(): void
    {
        $repo = $this->getMockBuilder(OperatorsRepository::class)->disableOriginalConstructor()->getMock();
        $repo->method('getInfo')->willReturnCallback(function ($filter) {
            if (isset($filter['operator_id'])) {
                return [
                    'operator_id' => 1,
                    'operator_type' => 'admin',
                    'company_id' => 100,
                    'mobile' => '13800000001',
                    'passport_uid' => null,
                    'eid' => null,
                    'shopex_bind_account' => null,
                ];
            }
            if (isset($filter['passport_uid'])) {
                return [
                    'operator_id' => 99,
                    'passport_uid' => 'pu_conflict',
                ];
            }

            return [];
        });
        $repo->expects($this->never())->method('updateOneBy');

        $redis = $this->getMockBuilder(\stdClass::class)->addMethods(['connection', 'incr', 'expire', 'del', 'get'])->getMock();
        $prismConn = $this->getMockBuilder(\stdClass::class)->addMethods(['get'])->getMock();
        $prismConn->method('get')->willReturn(false);
        $redis->method('connection')->willReturnCallback(function ($name = null) use ($prismConn) {
            if ($name === 'prism') {
                return $prismConn;
            }

            return $prismConn;
        });
        $redis->method('incr')->willReturn(1);
        $redis->method('expire')->willReturn(true);
        $this->app->instance('redis', $redis);

        $prismStub = new class() extends PrismEgo {
            public function getPrismAuth(array $credentials)
            {
                return [
                    'access_token' => 'at',
                    'expires_in' => 3600,
                    'refresh_token' => 'rt',
                    'refresh_expires' => 7200,
                    'data' => [
                        'passport_uid' => 'pu_conflict',
                        'eid' => 'e1',
                        'shopexid' => 'user@shopex.test',
                    ],
                ];
            }
        };

        $service = new ShopexAdminBindService($repo);

        $this->expectException(ResourceException::class);
        $this->expectExceptionMessage('已被其他管理员账号占用');

        $service->bindForAdminOperator(1, ['username' => 'u', 'password' => 'p'], $prismStub);
    }

    public function testRepositoryGetOperatorDataIncludesShopexBindAccount(): void
    {
        $repo = (new ReflectionClass(OperatorsRepository::class))->newInstanceWithoutConstructor();

        $entity = new class extends Operators {
            public function getOperatorId() { return 1; }
            public function getMobile() { return ''; }
            public function getLoginName() { return ''; }
            public function getPassword() { return ''; }
            public function getEid() { return ''; }
            public function getPassportUid() { return ''; }
            public function getShopexBindAccount() { return 'x@y.z'; }
            public function getOperatorType() { return 'admin'; }
            public function getShopIds() { return '[]'; }
            public function getDistributorIds() { return '[]'; }
            public function getCompanyId() { return 1; }
            public function getUsername() { return ''; }
            public function getHeadPortrait() { return ''; }
            public function getRegionauthId() { return 0; }
            public function getSplitLedgerInfo() { return ''; }
            public function getContact() { return ''; }
            public function getIsDisable() { return 0; }
            public function getAdapayOpenAccountTime() { return ''; }
            public function getDealerParentId() { return ''; }
            public function getIsDealerMain() { return 1; }
            public function getCreated() { return 0; }
            public function getUpdated() { return null; }
            public function getMerchantId() { return 0; }
            public function getIsMerchantMain() { return 0; }
            public function getIsDistributorMain() { return 0; }
        };
        $ref = new ReflectionClass(OperatorsRepository::class);
        $method = $ref->getMethod('getOperatorData');
        $data = $method->invoke($repo, $entity);
        $this->assertArrayHasKey('shopex_bind_account', $data);
        $this->assertSame('x@y.z', $data['shopex_bind_account']);
    }
}
