<?php

declare(strict_types=1);

namespace Tests\EspierBundle;

/**
 * codex-security-02-high-auth-crypto T11：Upgrade 路由 activated 门控（TC-02-06）
 */
class UpgradeRoutesActivatedMiddlewareTest extends \TestCase
{
    private function espierRoutes(): string
    {
        return (string) file_get_contents(dirname(__DIR__, 2).'/routes/api/espier.php');
    }

    private function upgradeGroupBlock(): string
    {
        $content = $this->espierRoutes();
        $needle = '/espier/system/detect_version';
        $needlePos = strpos($content, $needle);
        $this->assertNotFalse($needlePos, 'Upgrade route must exist');

        $groupStart = strrpos(substr($content, 0, $needlePos), '$api->group');
        $this->assertNotFalse($groupStart, 'Upgrade group must exist');

        $functionPos = strpos($content, 'function ($api) {', $groupStart);
        $this->assertNotFalse($functionPos);

        $openBrace = strpos($content, '{', $functionPos);
        $this->assertNotFalse($openBrace);

        $depth = 0;
        $length = strlen($content);
        for ($i = $openBrace; $i < $length; $i++) {
            $char = $content[$i];
            if ($char === '{') {
                $depth++;
            } elseif ($char === '}') {
                $depth--;
                if ($depth === 0) {
                    return substr($content, $groupStart, $i - $groupStart + 1);
                }
            }
        }

        $this->fail('Upgrade group block must be closed');
    }

    /**
     * TC-02-06 / F-047：非特权 upgrade/rollback → 403（路由组挂 activated）
     * #given routes/api/espier.php upgrade 路由组
     * #when 检查 group middleware
     * #then 须包含 activated
     */
    public function testTc0206UpgradeRoutesRequireActivatedMiddleware(): void
    {
        #given / #when
        $group = $this->upgradeGroupBlock();

        #then
        $this->assertStringContainsString("'activated'", $group);
        $this->assertStringContainsString('/espier/system/upgrade', $group);
        $this->assertStringContainsString('/espier/system/rollback', $group);
    }
}
