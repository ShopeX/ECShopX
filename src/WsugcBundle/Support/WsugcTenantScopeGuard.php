<?php

declare(strict_types=1);

namespace WsugcBundle\Support;

use Dingo\Api\Exception\ResourceException;
use WsugcBundle\Entities\Badge;
use WsugcBundle\Entities\Comment;
use WsugcBundle\Entities\Post;
use WsugcBundle\Entities\Tag;
use WsugcBundle\Entities\Topic;

/**
 * Wsugc tenant-scope gate: bind auth company_id on UGC reads/writes.
 */
class WsugcTenantScopeGuard
{
    /**
     * @param array<string, mixed> $row
     */
    public static function assertCompanyScope(array $row, int $companyId): void
    {
        if ($companyId <= 0 || empty($row) || (int) ($row['company_id'] ?? 0) !== $companyId) {
            throw new ResourceException('无权访问该内容');
        }
    }

    /**
     * @param int[]|string[]|int|string|null $ids
     */
    public static function assertBadgeIdsBelongToCompany(int $companyId, $ids): void
    {
        self::assertIdsBelongToCompany($companyId, self::normalizeIds($ids), Badge::class, 'badge_id', '无权访问该角标');
    }

    /**
     * @param int[]|string[]|int|string|null $ids
     */
    public static function assertPostIdsBelongToCompany(int $companyId, $ids): void
    {
        self::assertIdsBelongToCompany($companyId, self::normalizeIds($ids), Post::class, 'post_id', '无权访问该笔记');
    }

    /**
     * @param int[]|string[]|int|string|null $ids
     */
    public static function assertCommentIdsBelongToCompany(int $companyId, $ids): void
    {
        self::assertIdsBelongToCompany($companyId, self::normalizeIds($ids), Comment::class, 'comment_id', '无权访问该评论');
    }

    /**
     * @param int[]|string[]|int|string|null $ids
     */
    public static function assertTagIdsBelongToCompany(int $companyId, $ids): void
    {
        self::assertIdsBelongToCompany($companyId, self::normalizeIds($ids), Tag::class, 'tag_id', '无权访问该标签');
    }

    /**
     * @param int[]|string[]|int|string|null $ids
     */
    public static function assertTopicIdsBelongToCompany(int $companyId, $ids): void
    {
        self::assertIdsBelongToCompany($companyId, self::normalizeIds($ids), Topic::class, 'topic_id', '无权访问该话题');
    }

    /**
     * @param int[] $ids
     */
    private static function assertIdsBelongToCompany(int $companyId, array $ids, string $entityClass, string $idField, string $message): void
    {
        if ($companyId <= 0 || $ids === []) {
            throw new ResourceException($message);
        }

        $repository = app('registry')->getManager('default')->getRepository($entityClass);
        foreach ($ids as $id) {
            $id = (int) $id;
            if ($id <= 0) {
                throw new ResourceException($message);
            }

            $row = $repository->getInfo([$idField => $id, 'company_id' => $companyId]);
            if (!$row) {
                throw new ResourceException($message);
            }
        }
    }

    /**
     * @param int[]|string[]|int|string|null $ids
     * @return int[]
     */
    private static function normalizeIds($ids): array
    {
        if ($ids === null || $ids === '' || $ids === []) {
            return [];
        }

        if (!is_array($ids)) {
            return [(int) $ids];
        }

        return array_values(array_map('intval', $ids));
    }
}
