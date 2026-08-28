<?php

declare(strict_types=1);

namespace Tests\Security\Medium\Cmdi;

use PHPUnit\Framework\TestCase;

/**
 * codex-security-04-medium T34-GREEN-CMDi / F-148,F-149 / TC-04-04
 *
 * AC-04-04：CMDi 路径须不经 shell，或 coverage 矩阵 DEFERRED+理由。
 * F-148/F-149 为 tdd-guard 本地开发 hook，非 ECShopX 生产攻击面，采用 DEFERRED 契约。
 */
class TddGuardShellContractTest extends TestCase
{
    /**
     * TC-04-04 / F-148：Windows 校验路径 shell 风险须在矩阵 DEFERRED 并注明非生产攻击面。
     * #given coverage 矩阵 F-148 行
     * #when 读取 triage 状态与备注
     * #then 状态为 DEFERRED 且备注说明开发机 hook / 非生产
     */
    public function testTc0404F148WindowsValidationShellRiskDeferredWithReason(): void
    {
        #given
        $row = $this->coverageRow('F-148');

        #when / #then
        $this->assertSame('DEFERRED', $row['status']);
        $this->assertStringContainsString('tdd-guard', $row['note']);
        $this->assertMatchesRegularExpression('/开发机|非生产|本地/', $row['note']);
    }

    /**
     * TC-04-04 / F-149：ESLint hook shell 风险须在矩阵 DEFERRED 并注明仅本地 Cursor 环境。
     * #given coverage 矩阵 F-149 行
     * #when 读取 triage 状态与备注
     * #then 状态为 DEFERRED 且备注说明 ESLint hook / 本地环境
     */
    public function testTc0404F149EslintHookShellRiskDeferredWithReason(): void
    {
        #given
        $row = $this->coverageRow('F-149');

        #when / #then
        $this->assertSame('DEFERRED', $row['status']);
        $this->assertStringContainsString('tdd-guard', $row['note']);
        $this->assertMatchesRegularExpression('/ESLint|本地|Cursor/', $row['note']);
    }

    /**
     * @return array{status: string, note: string}
     */
    private function coverageRow(string $findingId): array
    {
        $root = dirname(__DIR__, 4);
        $matrixPath = $root . '/.tasks/drafts/codex-security-finding-coverage.md';
        $contents = (string) file_get_contents($matrixPath);

        foreach (explode("\n", $contents) as $line) {
            if (!str_contains($line, '| ' . $findingId . ' |')) {
                continue;
            }

            $columns = array_map('trim', explode('|', $line));
            return [
                'status' => $columns[7] ?? '',
                'note' => $columns[8] ?? '',
            ];
        }

        $this->fail('Coverage matrix row not found for ' . $findingId);
    }
}
