<?php

declare(strict_types=1);

namespace Tests\Security\Low\Idor;

use AliyunsmsBundle\Http\Api\V1\Action\Scene;
use AliyunsmsBundle\Http\Api\V1\Action\Template;
use GoodsBundle\Http\Api\V1\Action\Items;
use GoodsBundle\Http\Api\V1\Action\ItemsGroupController;
use GoodsBundle\Http\Api\V1\Action\ItemsTags;
use PromotionsBundle\Http\Api\V1\Action\Turntable;
use PromotionsBundle\Services\TurntableService;
use PromotionsBundle\Services\UserBargainService;
use ReservationBundle\Http\FrontApi\V1\Action\ResourceLevel;
use SelfserviceBundle\Http\Api\V1\Action\FormSettingController;
use SelfserviceBundle\Http\Api\V1\Action\FormTemplateController;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformManageConfigService;
use TestCase;

/**
 * codex-security-05-low T40-RED / T41-GREEN / TC-05-04
 */
class LowIdorScopeTest extends TestCase
{
    /**
     * TC-05-04 / F-163：短信场景详情须绑定认证 company_id。
     */
    public function testTc0504AliyunsmsSceneDetailScopesByCompanyId(): void
    {
        #given
        $body = $this->methodBody(Scene::class, 'getDetail');

        #when / #then
        $this->assertStringContainsString('$companyId', $body);
        $this->assertStringContainsString('getDetail($id, $companyId)', $body);
    }

    /**
     * TC-05-04 / F-165：短信模板详情须绑定认证 company_id。
     */
    public function testTc0504AliyunsmsTemplateDetailScopesByCompanyId(): void
    {
        #given
        $body = $this->methodBody(Template::class, 'getInfo');

        #when / #then
        $this->assertStringContainsString('$companyId', $body);
        $this->assertStringContainsString("'company_id' => \$companyId", $body);
    }

    /**
     * TC-05-04 / F-166：砍价报名须校验活动租户归属。
     */
    public function testTc0504CreateUserBargainScopesByCompanyId(): void
    {
        #given
        $body = $this->methodBody(UserBargainService::class, 'createUserBargain');

        #when / #then
        $this->assertTrue(
            $this->containsAny($body, [
                'PromotionActivityTenantScopeGuard',
                'assertBargainPromotionBelongsToCompany',
                "'company_id' => \$authInfo['company_id']",
            ]),
            'createUserBargain must scope bargain by authenticated company'
        );
    }

    /**
     * TC-05-04 / F-167：转盘抽奖须校验活动租户归属。
     */
    public function testTc0504DoLuckyDrawScopesByCompanyId(): void
    {
        #given
        $body = $this->methodBody(TurntableService::class, 'doLuckyDraw');

        #when / #then
        $this->assertTrue(
            $this->containsAny($body, [
                'PromotionActivityTenantScopeGuard',
                'assertLuckyDrawActivityBelongsToCompany',
            ]),
            'doLuckyDraw must scope activity by authenticated company'
        );
    }

    /**
     * TC-05-04 / F-168：预约资源位详情须按 company_id 过滤。
     */
    public function testTc0504ReservationDetailScopesByCompanyId(): void
    {
        #given
        $body = $this->methodBody(ResourceLevel::class, 'getReservationDetail');

        #when / #then
        $this->assertStringContainsString('$companyId', $body);
        $this->assertStringContainsString("'company_id'", $body);
    }

    /**
     * TC-05-04 / F-171：转盘配置读取须绑定 company_id。
     */
    public function testTc0504GetTurntableConfigScopesByCompanyId(): void
    {
        #given
        $body = $this->methodBody(TurntableService::class, 'getTurntableConfig');

        #when / #then
        $this->assertStringContainsString('$company_id', $body);
        $this->assertStringContainsString("'company_id' => \$company_id", $body);
    }

    /**
     * TC-05-04 / F-172：转盘活动详情须校验租户归属。
     */
    public function testTc0504TurntableDetailScopesByCompanyId(): void
    {
        #given
        $body = $this->methodBody(Turntable::class, 'getLuckyDrawDetail');

        #when / #then
        $this->assertTrue(
            $this->containsAny($body, [
                'PromotionActivityTenantScopeGuard',
                'company_id',
                'assertLuckyDrawActivityBelongsToCompany',
            ]),
            'getLuckyDrawDetail must scope activity by authenticated company'
        );
    }

    /**
     * TC-05-04 / F-186：表单元素详情须校验租户归属。
     */
    public function testTc0504FormSettingDetailScopesByCompanyId(): void
    {
        #given
        $body = $this->methodBody(FormSettingController::class, 'getDataInfo');

        #when / #then
        $this->assertStringContainsString('SelfserviceTenantScopeGuard', $body);
        $this->assertStringContainsString('assertFormSettingIdBelongsToCompany', $body);
    }

    /**
     * TC-05-04 / F-187：表单模板详情须校验租户归属。
     */
    public function testTc0504FormTemplateDetailScopesByCompanyId(): void
    {
        #given
        $body = $this->methodBody(FormTemplateController::class, 'getDataInfo');

        #when / #then
        $this->assertStringContainsString('SelfserviceTenantScopeGuard', $body);
        $this->assertStringContainsString('assertFormTemplateIdBelongsToCompany', $body);
    }

    /**
     * TC-05-04 / F-188：数云管理视图不得返回明文 access_token。
     */
    public function testTc0504ShuyunAdminViewMasksAccessToken(): void
    {
        #given
        $body = $this->methodBody(ShuyunOpenPlatformManageConfigService::class, 'getAdminView');

        #when / #then
        $this->assertStringContainsString('access_token_masked', $body);
        $this->assertStringNotContainsString("'access_token' => \$row->getAccessToken()", $body);
    }

    /**
     * TC-05-04 / F-189：商品分组列表须绑定 company_id。
     */
    public function testTc0504ItemsGroupListScopesByCompanyId(): void
    {
        #given
        $body = $this->methodBody(ItemsGroupController::class, 'getGroupItems');

        #when / #then
        $this->assertStringContainsString('company_id', $body);
        $this->assertStringNotContainsString("'group_id' => \$params['group_id'],\n        ];", $body);
    }

    /**
     * TC-05-04 / F-190：关键词详情须按 company_id 查询。
     */
    public function testTc0504KeywordDetailScopesByCompanyId(): void
    {
        #given
        $body = $this->methodBody(Items::class, 'getKeyWordsDetail');

        #when / #then
        $this->assertStringContainsString('company_id', $body);
        $this->assertStringNotContainsString('getInfoById', $body);
    }

    /**
     * TC-05-04 / F-191：商品标签详情须按 company_id 查询。
     */
    public function testTc0504TagDetailScopesByCompanyId(): void
    {
        #given
        $body = $this->methodBody(ItemsTags::class, 'getTagsInfo');

        #when / #then
        $this->assertStringContainsString('company_id', $body);
    }

    /**
     * @param string[] $needles
     */
    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function methodBody(string $class, string $method): string
    {
        $ref = new \ReflectionMethod($class, $method);
        $file = $ref->getFileName();
        $this->assertNotFalse($file);
        $lines = file($file, FILE_IGNORE_NEW_LINES);
        $this->assertIsArray($lines);

        return implode("\n", array_slice($lines, $ref->getStartLine() - 1, $ref->getEndLine() - $ref->getStartLine() + 1));
    }
}
