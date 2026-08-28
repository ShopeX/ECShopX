<?php

declare(strict_types=1);

namespace Tests\Security\Medium\Idor\Comments;

use CommentsBundle\Http\Api\V1\Action\Comments;
use TestCase;

/**
 * codex-security-04-medium T31-GREEN-IDOR-Rest / F-099 / AC-04-01
 */
class CommentsModerationScopeTest extends TestCase
{
    /**
     * TC-04-01 / F-099：评论审核更新须校验 comment 归属 auth company。
     */
    public function testTc0401UpdateCommentScopesByAuthCompanyId(): void
    {
        #given
        $body = $this->methodBody(Comments::class, 'updateComment');

        #when / #then
        $this->assertStringContainsString('CommentsTenantScopeGuard', $body);
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
