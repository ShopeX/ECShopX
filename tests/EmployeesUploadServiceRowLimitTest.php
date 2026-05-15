<?php

use EmployeePurchaseBundle\Services\EmployeesUploadService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class EmployeesUploadServiceRowLimitTest extends TestCase
{
    public function testAssertImportDataRowsWithinLimitAllowsUpToMax(): void
    {
        $this->expectNotToPerformAssertions();
        EmployeesUploadService::assertImportDataRowsWithinLimit(EmployeesUploadService::MAX_IMPORT_DATA_ROWS);
        EmployeesUploadService::assertImportDataRowsWithinLimit(0);
    }

    public function testAssertImportDataRowsWithinLimitThrowsWhenOverLimit(): void
    {
        $expected = sprintf(
            '每次最多上传%d条员工数据（不含表头）...请减少后再提交',
            EmployeesUploadService::MAX_IMPORT_DATA_ROWS
        );
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage($expected);

        EmployeesUploadService::assertImportDataRowsWithinLimit(EmployeesUploadService::MAX_IMPORT_DATA_ROWS + 1);
    }
}
