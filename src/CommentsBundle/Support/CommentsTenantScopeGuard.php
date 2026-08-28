<?php

declare(strict_types=1);

namespace CommentsBundle\Support;

use CommentsBundle\Entities\ShopComments;
use Dingo\Api\Exception\ResourceException;

/**
 * Comments tenant-scope gate: bind auth company_id on comment reads/writes.
 */
class CommentsTenantScopeGuard
{
    public static function assertCommentIdBelongsToCompany(int $companyId, int $commentId): void
    {
        if ($companyId <= 0 || $commentId <= 0) {
            throw new ResourceException('无权操作该评论');
        }

        $repository = app('registry')->getManager('default')->getRepository(ShopComments::class);
        $row = $repository->findOneBy(['comment_id' => $commentId, 'company_id' => $companyId]);
        if (!$row) {
            throw new ResourceException('无权操作该评论');
        }
    }
}
