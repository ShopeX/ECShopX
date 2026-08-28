<?php

declare(strict_types=1);

namespace FormBundle\Support;

use Dingo\Api\Exception\ResourceException;
use FormBundle\Entities\TranscriptProperties;
use FormBundle\Entities\Transcripts;

/**
 * Form tenant-scope gate: bind auth company_id on transcript reads/writes.
 */
class FormTenantScopeGuard
{
    /**
     * @param int|string $transcriptId
     */
    public static function assertTranscriptIdBelongsToCompany(int $companyId, $transcriptId): void
    {
        self::assertIdBelongsToCompany($companyId, (int) $transcriptId, Transcripts::class, 'transcript_id', '无权访问该成绩单');
    }

    /**
     * @param int[] $propIds
     */
    public static function assertTranscriptPropertyIdsBelongToTranscript(int $companyId, int $transcriptId, array $propIds): void
    {
        if ($companyId <= 0 || $transcriptId <= 0) {
            throw new ResourceException('无权访问该成绩单属性');
        }

        foreach ($propIds as $propId) {
            $propId = (int) $propId;
            if ($propId <= 0) {
                continue;
            }

            $repository = app('registry')->getManager('default')->getRepository(TranscriptProperties::class);
            $row = $repository->findOneBy([
                'prop_id' => $propId,
                'company_id' => $companyId,
                'transcript_id' => $transcriptId,
            ]);
            if (!$row) {
                throw new ResourceException('无权访问该成绩单属性');
            }
        }
    }

    private static function assertIdBelongsToCompany(int $companyId, int $id, string $entityClass, string $idField, string $message): void
    {
        if ($companyId <= 0 || $id <= 0) {
            throw new ResourceException($message);
        }

        $repository = app('registry')->getManager('default')->getRepository($entityClass);
        $row = $repository->findOneBy([$idField => $id, 'company_id' => $companyId]);
        if (!$row) {
            throw new ResourceException($message);
        }
    }
}
