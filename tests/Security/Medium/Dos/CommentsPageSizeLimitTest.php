<?php

declare(strict_types=1);

namespace Tests\Security\Medium\Dos;

use CommentsBundle\Http\FrontApi\V1\Action\Comments;
use TestCase;

/**
 * codex-security-04-medium T31-RED / F-160 / TC-04-05
 */
class CommentsPageSizeLimitTest extends TestCase
{
    /**
     * TC-04-05 / F-160：公开评论列表 page_size 须有上限。
     * #given Comments@getComments
     * #when 检查 pageSize 处理
     * #then 须含 MAX_PAGE_SIZE 或 min() 上限裁剪
     */
    public function testTc0405CommentsListCapsPageSize(): void
    {
        #given
        $body = $this->methodBody(Comments::class, 'getComments');

        #when / #then
        $this->assertTrue(
            $this->containsAny($body, ['MAX_PAGE_SIZE', 'max_page_size', 'min($pageSize', 'capPageSize']),
            'getComments must cap unbounded pageSize'
        );
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
