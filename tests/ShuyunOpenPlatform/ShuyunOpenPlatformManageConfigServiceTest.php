<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use PHPUnit\Framework\TestCase;
use ShuyunOpenPlatformBundle\Entities\CompanyShuyunOpenPlatformConfig;
use ShuyunOpenPlatformBundle\Exception\ShuyunOpenPlatformManageConfigGateException;
use ShuyunOpenPlatformBundle\Repositories\CompanyShuyunOpenPlatformConfigRepository;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformManageConfigService;

class ShuyunOpenPlatformManageConfigServiceTest extends TestCase
{
    public function testSaveCredentialsWhenPlatCodeMissing(): void
    {
        $row = new CompanyShuyunOpenPlatformConfig();
        $row->setCompanyId(1);
        $row->setAuthValue('av');
        $row->setPlatCode(null);
        $row->setAppId('old');
        $row->setAppSecret('oldsec');

        $repo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $repo->method('findOneByCompanyId')->with(1)->willReturn($row);
        $repo->expects($this->once())->method('save')->with($this->callback(
            static function (CompanyShuyunOpenPlatformConfig $e): bool {
                return $e->getAppSecret() === 'newsec'
                    && $e->getPlatCode() === 'OFFLINE';
            }
        ));

        $svc = new ShuyunOpenPlatformManageConfigService($repo);
        $svc->saveFromAdmin(1, ['app_secret' => 'newsec']);
    }

    public function testCreatesRowWhenSavingCredentialsWithoutExistingRow(): void
    {
        $repo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $repo->method('findOneByCompanyId')->with(2)->willReturn(null);
        $repo->expects($this->once())->method('save')->with($this->callback(
            static function (CompanyShuyunOpenPlatformConfig $e): bool {
                return $e->getCompanyId() === 2
                    && $e->getAppId() === 'x'
                    && $e->getAppSecret() === 'sec'
                    && $e->getIsEnabled() === 0
                    && $e->getPlatCode() === 'OFFLINE';
            }
        ));

        $svc = new ShuyunOpenPlatformManageConfigService($repo);
        $svc->saveFromAdmin(2, ['app_id' => 'x', 'app_secret' => 'sec']);
    }

    /** @see .tasks/plans/shuyun-open-platform-core.md TC-CFG-API-02 */
    public function testGetAdminViewMasksSecret(): void
    {
        $row = new CompanyShuyunOpenPlatformConfig();
        $row->setCompanyId(3);
        $row->setAuthValue('av');
        $row->setPlatCode('P');
        $row->setAppId('aid');
        $row->setAppSecret('abcdefghijklmnop');

        $repo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $repo->method('findOneByCompanyId')->willReturn($row);

        $svc = new ShuyunOpenPlatformManageConfigService($repo);
        $v = $svc->getAdminView(3);
        $this->assertSame('****mnop', $v['app_secret_masked']);
    }

    /** @see .tasks/plans/shuyun-open-platform-core.md TC-CFG-API-01 */
    public function testSaveWhenPlatCodeExistsPersists(): void
    {
        $row = new CompanyShuyunOpenPlatformConfig();
        $row->setCompanyId(4);
        $row->setAuthValue('av');
        $row->setPlatCode('PL');
        $row->setAppId('old');
        $row->setAppSecret('oldsec');
        $row->setAccessToken('valid-tok');
        $row->setIsEnabled(0);

        $repo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $repo->method('findOneByCompanyId')->willReturn($row);
        $repo->expects($this->once())->method('save')->with($this->callback(
            static function (CompanyShuyunOpenPlatformConfig $e): bool {
                return $e->getAppId() === 'newid'
                    && $e->getAppSecret() === 'newsecret'
                    && $e->getIsEnabled() === 1;
            }
        ));

        $svc = new ShuyunOpenPlatformManageConfigService($repo);
        $svc->saveFromAdmin(4, [
            'app_id' => 'newid',
            'app_secret' => 'newsecret',
            'enabled' => true,
        ]);
    }

    public function testSaveAccessTokenOnlyWhenPlatCodeMissingButAppCredentialsPresent(): void
    {
        $row = new CompanyShuyunOpenPlatformConfig();
        $row->setCompanyId(5);
        $row->setAuthValue('av5');
        $row->setPlatCode(null);
        $row->setAppId('myapp');
        $row->setAppSecret('sec');
        $row->setIsOverDue('1');
        $row->setIsEnabled(0);

        $repo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $repo->method('findOneByCompanyId')->with(5)->willReturn($row);
        $repo->expects($this->once())->method('save')->with($this->callback(
            static function (CompanyShuyunOpenPlatformConfig $e): bool {
                return $e->getAccessToken() === 'tok-manual'
                    && $e->getIsOverDue() === '0';
            }
        ));

        $svc = new ShuyunOpenPlatformManageConfigService($repo);
        $svc->saveFromAdmin(5, ['access_token' => 'tok-manual']);
    }

    /** @see .tasks/plans/shuyun-offline-only.md TC-CFG-01 */
    public function testEnableWithoutPlatCodeWhenAccessTokenPresent(): void
    {
        $row = new CompanyShuyunOpenPlatformConfig();
        $row->setCompanyId(6);
        $row->setAuthValue('av6');
        $row->setPlatCode(null);
        $row->setAppId('a');
        $row->setAppSecret('s');
        $row->setAccessToken('valid-tok');
        $row->setIsEnabled(0);

        $repo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $repo->method('findOneByCompanyId')->willReturn($row);
        $repo->expects($this->once())->method('save')->with($this->callback(
            static function (CompanyShuyunOpenPlatformConfig $e): bool {
                return $e->getIsEnabled() === 1
                    && $e->getPlatCode() === 'OFFLINE';
            }
        ));

        $svc = new ShuyunOpenPlatformManageConfigService($repo);
        $svc->saveFromAdmin(6, ['is_enabled' => true]);
    }

    public function testRejectEnableWithoutAccessToken(): void
    {
        $row = new CompanyShuyunOpenPlatformConfig();
        $row->setCompanyId(8);
        $row->setAuthValue('av8');
        $row->setPlatCode('PLAT');
        $row->setAppId('a');
        $row->setAppSecret('s');

        $repo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $repo->method('findOneByCompanyId')->willReturn($row);
        $repo->expects($this->never())->method('save');

        $svc = new ShuyunOpenPlatformManageConfigService($repo);
        $this->expectException(ShuyunOpenPlatformManageConfigGateException::class);
        $svc->saveFromAdmin(8, ['enabled' => true]);
    }

    public function testRejectAccessTokenOnlyWhenAppSecretMissing(): void
    {
        $row = new CompanyShuyunOpenPlatformConfig();
        $row->setCompanyId(7);
        $row->setAuthValue('av7');
        $row->setPlatCode(null);
        $row->setAppId('onlyid');
        $row->setAppSecret(null);

        $repo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $repo->method('findOneByCompanyId')->willReturn($row);

        $svc = new ShuyunOpenPlatformManageConfigService($repo);
        $this->expectException(ShuyunOpenPlatformManageConfigGateException::class);
        $svc->saveFromAdmin(7, ['access_token' => 'x']);
    }
}
