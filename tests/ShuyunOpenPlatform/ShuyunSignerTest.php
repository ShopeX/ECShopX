<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use PHPUnit\Framework\TestCase;
use ShuyunOpenPlatformBundle\Gateway\ShuyunSigner;

class ShuyunSignerTest extends TestCase
{
    private ShuyunSigner $signer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->signer = new ShuyunSigner();
    }

    /** @see .tasks/plans/shuyun-open-platform-core.md TC-SIGN-01 */
    public function testPostSignWithoutQueryMatchesDocumentExample(): void
    {
        $appSecret = '24CE4C56026A';
        $params = [
            'Gateway-Request-Time' => '1657075325952',
        ];
        $this->assertSame('f4e5a8340d15d2c68f26bbeab50f835c', $this->signer->sign($appSecret, $params));
    }

    /** @see .tasks/plans/shuyun-open-platform-core.md TC-SIGN-02 */
    public function testParameterOrderIsAlwaysAsciiSorted(): void
    {
        $appSecret = '24CE4C56026A';
        $reversed = ['b' => '2', 'a' => '1'];
        $sorted = ['a' => '1', 'b' => '2'];
        $this->assertSame($this->signer->sign($appSecret, $reversed), $this->signer->sign($appSecret, $sorted));
        $this->assertSame('2b2ffdc6887e86bec27b73a36f47f6a8', $this->signer->sign($appSecret, $sorted));
    }

    /** @see .tasks/plans/shuyun-open-platform-core.md TC-SIGN-03 */
    public function testGetWithMergedQueryAndTimeMatchesDocumentExample(): void
    {
        $appSecret = '24CE4C56026A';
        $params = [
            'platCode' => 'OFFLINE',
            'shopId' => '2001',
            'id' => 'homale99',
            'Gateway-Request-Time' => '1657076005151',
        ];
        $this->assertSame('aee77500bb07764b74cbecce217bdcf7', $this->signer->sign($appSecret, $params));
    }

    /** @see .tasks/plans/shuyun-open-platform-core.md TC-SIGN-04 */
    public function testGetDifferentInputOrderYieldsSameSignature(): void
    {
        $appSecret = '24CE4C56026A';
        $ordered = [
            'Gateway-Request-Time' => '1657076005151',
            'id' => 'homale99',
            'platCode' => 'OFFLINE',
            'shopId' => '2001',
        ];
        $shuffled = [
            'shopId' => '2001',
            'platCode' => 'OFFLINE',
            'id' => 'homale99',
            'Gateway-Request-Time' => '1657076005151',
        ];
        $this->assertSame(
            $this->signer->sign($appSecret, $ordered),
            $this->signer->sign($appSecret, $shuffled)
        );
    }

    public function testEmptyAppSecretThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->signer->sign('', ['Gateway-Request-Time' => '1']);
    }
}
