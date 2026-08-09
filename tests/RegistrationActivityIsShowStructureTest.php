<?php

/**
 * registration-activity-is-show：报名活动 is_show 显示/隐藏（TC-1 ~ TC-11）
 */

use SelfserviceBundle\Entities\RegistrationActivity;
use SelfserviceBundle\Http\Api\V1\Action\RegistrationActivityController as ApiRegistrationActivityController;
use SelfserviceBundle\Http\FrontApi\V1\Action\RegistrationActivityController as FrontRegistrationActivityController;
use SelfserviceBundle\Repositories\RegistrationActivityRepository;
use SelfserviceBundle\Services\RegistrationActivityService;
use SelfserviceBundle\Services\RegistrationRecordService;

class RegistrationActivityIsShowStructureTest extends TestCase
{
    private function methodBody(string $class, string $method): string
    {
        $ref = new ReflectionMethod($class, $method);
        $file = $ref->getFileName();
        $this->assertNotFalse($file);
        $lines = file($file, FILE_IGNORE_NEW_LINES);
        $this->assertIsArray($lines);
        $slice = array_slice($lines, $ref->getStartLine() - 1, $ref->getEndLine() - $ref->getStartLine() + 1);

        return implode("\n", $slice);
    }

    private function latestMigrationBody(): string
    {
        $files = glob(__DIR__ . '/../database/migrations/Version*.php');
        $this->assertNotEmpty($files);
        usort($files, static function ($a, $b) {
            return strcmp($b, $a);
        });
        foreach ($files as $file) {
            $content = file_get_contents($file);
            if (strpos($content, 'selfservice_registration_activity') !== false
                && strpos($content, 'is_show') !== false) {
                return $content;
            }
        }

        $this->fail('未找到 selfservice_registration_activity.is_show 的 migration');
    }

    /** TC-1：Entity 含 is_show 及 getter/setter */
    public function testEntityHasIsShowGetterSetter(): void
    {
        $this->assertTrue(method_exists(RegistrationActivity::class, 'setIsShow'));
        $this->assertTrue(method_exists(RegistrationActivity::class, 'getIsShow'));

        $ref = new ReflectionClass(RegistrationActivity::class);
        $this->assertTrue($ref->hasProperty('is_show'));
    }

    /** TC-2：Repository $cols 含 is_show */
    public function testRepositoryColsContainsIsShow(): void
    {
        $content = file_get_contents(__DIR__ . '/../src/SelfserviceBundle/Repositories/RegistrationActivityRepository.php');
        $this->assertStringContainsString("'is_show'", $content);
    }

    /** TC-8：migration 存在且 DEFAULT 1 */
    public function testMigrationAddsIsShowWithDefaultOne(): void
    {
        $body = $this->latestMigrationBody();
        $this->assertStringContainsString('is_show', $body);
        $this->assertStringContainsString('DEFAULT 1', $body);
        $this->assertStringContainsString('selfservice_registration_activity', $body);
    }

    /** TC-3：saveData 对 is_show 默认 1 */
    public function testSaveDataDefaultsIsShowToOne(): void
    {
        $body = $this->methodBody(RegistrationActivityService::class, 'saveData');
        $this->assertStringContainsString("\$params['is_show'] = intval(\$params['is_show'] ?? 1)", $body);
    }

    /** TC-7：checkActivityValid 在 is_show != 1 时返回 false */
    public function testCheckActivityValidRejectsHiddenActivity(): void
    {
        $body = $this->methodBody(RegistrationActivityService::class, 'checkActivityValid');
        $this->assertStringContainsString("['is_show']", $body);
        $this->assertStringContainsString('activity_not_exist_err', $body);
    }

    /** TC-4：管理端 create/update 白名单含 is_show */
    public function testAdminCreateUpdateWhitelistContainsIsShow(): void
    {
        $createBody = $this->methodBody(ApiRegistrationActivityController::class, 'createData');
        $updateBody = $this->methodBody(ApiRegistrationActivityController::class, 'updateData');
        $this->assertStringContainsString("'is_show'", $createBody);
        $this->assertStringContainsString("'is_show'", $updateBody);
    }

    /** TC-5：C 端 list filter 含 is_show => 1 */
    public function testFrontListFilterContainsIsShow(): void
    {
        $body = $this->methodBody(FrontRegistrationActivityController::class, 'getRegistrationActivityList');
        $this->assertStringContainsString("'is_show'", $body);
        $this->assertStringContainsString('= 1', $body);
    }

    /** TC-6：C 端 detail 对 is_show=0 走不存在分支 */
    public function testFrontDetailRejectsHiddenActivity(): void
    {
        $body = $this->methodBody(FrontRegistrationActivityController::class, 'getRegistrationActivity');
        $this->assertStringContainsString("['is_show']", $body);
        $this->assertStringContainsString('activity_not_exist', $body);
    }

    /** TC-9：getRocordList 含 $onlyVisibleActivity，隐藏时跳过 record */
    public function testGetRocordListSupportsOnlyVisibleActivityFilter(): void
    {
        $ref = new ReflectionMethod(RegistrationRecordService::class, 'getRocordList');
        $this->assertTrue($ref->getNumberOfParameters() >= 5);

        $body = $this->methodBody(RegistrationRecordService::class, 'getRocordList');
        $this->assertStringContainsString('$onlyVisibleActivity', $body);
        $this->assertStringContainsString('is_show', $body);
        $this->assertStringContainsString('total_count', $body);
    }

    /** TC-10：getRocordInfo 含 $onlyVisibleActivity，隐藏时 return [] */
    public function testGetRocordInfoSupportsOnlyVisibleActivityFilter(): void
    {
        $ref = new ReflectionMethod(RegistrationRecordService::class, 'getRocordInfo');
        $this->assertTrue($ref->getNumberOfParameters() >= 2);

        $body = $this->methodBody(RegistrationRecordService::class, 'getRocordInfo');
        $this->assertStringContainsString('$onlyVisibleActivity', $body);
        $this->assertStringContainsString('is_show', $body);
        $this->assertStringContainsString('return []', $body);
    }

    /** TC-11：FrontApi getRegistrationRecordInfo 对空结果 early return */
    public function testFrontRecordInfoEarlyReturnsWhenEmpty(): void
    {
        $body = $this->methodBody(FrontRegistrationActivityController::class, 'getRegistrationRecordInfo');
        $this->assertStringContainsString('getRocordInfo', $body);
        $this->assertStringContainsString('!$result', $body);
    }

    /** TC-12：管理端 setIsShow 接口更新 is_show */
    public function testAdminSetIsShowEndpointUpdatesIsShow(): void
    {
        $routeContent = file_get_contents(__DIR__ . '/../routes/api/selfService.php');
        $this->assertStringContainsString('/selfhelp/registrationActivity/setIsShow', $routeContent);
        $this->assertStringContainsString('setIsShow', $routeContent);

        $body = $this->methodBody(ApiRegistrationActivityController::class, 'setIsShow');
        $this->assertStringContainsString('activity_id', $body);
        $this->assertStringContainsString('is_show', $body);
        $this->assertStringContainsString('updateOneBy', $body);
        $this->assertStringContainsString('company_id', $body);
    }
}
