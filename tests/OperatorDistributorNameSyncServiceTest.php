<?php

/**
 * 计划：.tasks/plans/sync-operator-distributor-name.md — TC-S01～TC-S04
 */

use CompanysBundle\Repositories\OperatorsRepository;
use CompanysBundle\Services\OperatorDistributorNameSyncService;
use CompanysBundle\Support\OperatorDistributorIdsPatterns;

class OperatorDistributorNameSyncServiceTest extends TestCase
{
    /** TC-S01 */
    public function testSyncUpdatesOperatorWhenListsReturnsOneRowWithOldName(): void
    {
        #given
        $companyId = 100;
        $distributorId = 1;
        $oldName = '旧店名';
        $newName = '新店名';
        $patterns = OperatorDistributorIdsPatterns::forDistributorId($distributorId);

        $operatorRow = [
            'operator_id' => 10,
            'company_id' => $companyId,
            'distributor_ids' => [
                ['distributor_id' => 1, 'name' => $oldName],
                ['distributor_id' => 2, 'name' => '其他店'],
            ],
        ];

        $repo = $this->getMockBuilder(OperatorsRepository::class)->disableOriginalConstructor()->getMock();
        $repo->expects($this->once())
            ->method('lists')
            ->with(
                $this->callback(function (array $filter) use ($companyId, $patterns) {
                    return ($filter['company_id'] ?? null) === $companyId
                        && ($filter['distributor_ids'] ?? null) === $patterns;
                }),
                '*',
                1,
                100
            )
            ->willReturn([
                'total_count' => 1,
                'list' => [$operatorRow],
            ]);

        $repo->expects($this->once())
            ->method('updateOneBy')
            ->with(
                ['operator_id' => 10],
                $this->callback(function (array $data) use ($newName) {
                    $items = $data['distributor_ids'] ?? [];

                    return ($items[0]['name'] ?? null) === $newName
                        && ($items[0]['distributor_id'] ?? null) === 1
                        && ($items[1]['name'] ?? null) === '其他店'
                        && ($items[1]['distributor_id'] ?? null) === 2;
                })
            );

        $service = new OperatorDistributorNameSyncService($repo);

        #when
        $service->syncIfNameChanged($companyId, $distributorId, $oldName, $newName);

        #then — expectations on mock
    }

    /** TC-S02 */
    public function testSyncSkipsListsAndUpdateWhenOldNameEqualsNewName(): void
    {
        #given
        $repo = $this->getMockBuilder(OperatorsRepository::class)->disableOriginalConstructor()->getMock();
        $repo->expects($this->never())->method('lists');
        $repo->expects($this->never())->method('updateOneBy');

        $service = new OperatorDistributorNameSyncService($repo);
        $sameName = '未改名的店';

        #when
        $service->syncIfNameChanged(100, 1, $sameName, $sameName);

        #then — never lists / never updateOneBy
    }

    /** TC-S03 */
    public function testSyncNeverUpdatesWhenListsReturnsEmpty(): void
    {
        #given
        $companyId = 200;
        $distributorId = 5;
        $patterns = OperatorDistributorIdsPatterns::forDistributorId($distributorId);

        $repo = $this->getMockBuilder(OperatorsRepository::class)->disableOriginalConstructor()->getMock();
        $repo->expects($this->once())
            ->method('lists')
            ->with(
                $this->callback(function (array $filter) use ($companyId, $patterns) {
                    return ($filter['company_id'] ?? null) === $companyId
                        && ($filter['distributor_ids'] ?? null) === $patterns;
                }),
                '*',
                1,
                100
            )
            ->willReturn([
                'total_count' => 0,
                'list' => [],
            ]);
        $repo->expects($this->never())->method('updateOneBy');

        $service = new OperatorDistributorNameSyncService($repo);

        #when
        $service->syncIfNameChanged($companyId, $distributorId, '旧名', '新名');

        #then — never updateOneBy
    }

    /** TC-S04 */
    public function testSyncUpdatesEveryMatchedOperatorAcrossMultiplePages(): void
    {
        #given
        $companyId = 300;
        $distributorId = 7;
        $oldName = '分页旧名';
        $newName = '分页新名';
        $patterns = OperatorDistributorIdsPatterns::forDistributorId($distributorId);

        $operatorPage1A = [
            'operator_id' => 21,
            'company_id' => $companyId,
            'distributor_ids' => [
                ['distributor_id' => 7, 'name' => $oldName],
            ],
        ];
        $operatorPage1B = [
            'operator_id' => 22,
            'company_id' => $companyId,
            'distributor_ids' => [
                ['distributor_id' => 7, 'name' => $oldName],
                ['distributor_id' => 8, 'name' => '另一店'],
            ],
        ];

        $repo = $this->getMockBuilder(OperatorsRepository::class)->disableOriginalConstructor()->getMock();
        $repo->expects($this->exactly(2))
            ->method('lists')
            ->willReturnCallback(function ($filter, $cols, $page, $pageSize) use (
                $companyId,
                $patterns,
                $operatorPage1A,
                $operatorPage1B
            ) {
                $this->assertSame($companyId, $filter['company_id'] ?? null);
                $this->assertSame($patterns, $filter['distributor_ids'] ?? null);
                $this->assertSame('*', $cols);
                $this->assertSame(100, $pageSize);

                if ($page === 1) {
                    return [
                        'total_count' => 102,
                        'list' => [$operatorPage1A, $operatorPage1B],
                    ];
                }
                if ($page === 2) {
                    return [
                        'total_count' => 102,
                        'list' => [],
                    ];
                }

                $this->fail('Unexpected lists page: ' . $page);
            });

        $updatedOperatorIds = [];
        $repo->expects($this->exactly(2))
            ->method('updateOneBy')
            ->willReturnCallback(function (array $filter, array $data) use ($newName, &$updatedOperatorIds) {
                $operatorId = $filter['operator_id'] ?? null;
                $updatedOperatorIds[] = $operatorId;

                foreach ($data['distributor_ids'] as $item) {
                    if (($item['distributor_id'] ?? null) == 7) {
                        $this->assertSame($newName, $item['name']);
                    }
                }

                return [];
            });

        $service = new OperatorDistributorNameSyncService($repo);

        #when
        $service->syncIfNameChanged($companyId, $distributorId, $oldName, $newName);

        #then
        $this->assertSame([21, 22], $updatedOperatorIds);
    }
}
