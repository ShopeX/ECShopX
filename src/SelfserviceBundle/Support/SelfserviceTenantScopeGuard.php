<?php

declare(strict_types=1);

namespace SelfserviceBundle\Support;

use Dingo\Api\Exception\ResourceException;
use SelfserviceBundle\Entities\FormSetting;
use SelfserviceBundle\Entities\FormTemplate;
use SelfserviceBundle\Entities\RegistrationActivity;
use SelfserviceBundle\Entities\RegistrationRecord;

/**
 * Selfservice tenant-scope gate: bind auth company_id on form/registration reads/writes.
 */
class SelfserviceTenantScopeGuard
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
     * @param int|string $id
     */
    public static function assertFormSettingIdBelongsToCompany(int $companyId, $id): void
    {
        self::assertIdBelongsToCompany($companyId, (int) $id, FormSetting::class, 'id', '无权访问该表单元素');
    }

    /**
     * @param int|string $id
     */
    public static function assertFormTemplateIdBelongsToCompany(int $companyId, $id): void
    {
        self::assertIdBelongsToCompany($companyId, (int) $id, FormTemplate::class, 'id', '无权访问该表单模板');
    }

    /**
     * @param int|string $id
     */
    public static function assertRegistrationActivityIdBelongsToCompany(int $companyId, $id): void
    {
        self::assertIdBelongsToCompany($companyId, (int) $id, RegistrationActivity::class, 'activity_id', '无权访问该报名活动');
    }

    /**
     * @param int|string $id
     */
    public static function assertRegistrationRecordIdBelongsToCompany(int $companyId, $id): void
    {
        self::assertIdBelongsToCompany($companyId, (int) $id, RegistrationRecord::class, 'record_id', '无权访问该报名记录');
    }

    private static function assertIdBelongsToCompany(int $companyId, int $id, string $entityClass, string $idField, string $message): void
    {
        if ($companyId <= 0 || $id <= 0) {
            throw new ResourceException($message);
        }

        $repository = app('registry')->getManager('default')->getRepository($entityClass);
        $row = $repository->getInfo([$idField => $id, 'company_id' => $companyId]);
        if (!$row) {
            throw new ResourceException($message);
        }
    }
}
