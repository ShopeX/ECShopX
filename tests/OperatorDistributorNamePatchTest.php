<?php

/**
 * 计划：.tasks/plans/sync-operator-distributor-name.md — TC-J01～TC-J04
 */

use CompanysBundle\Support\OperatorDistributorNamePatch;

class OperatorDistributorNamePatchTest extends TestCase
{
    /** TC-J01 */
    public function testPatchUpdatesOnlyTargetDistributorNameAmongMultipleItems(): void
    {
        #given
        $items = [
            ['distributor_id' => 1, 'name' => '店1'],
            ['distributor_id' => 2, 'name' => '店2'],
        ];
        $targetId = 1;
        $newName = '新店1';

        #when
        $result = OperatorDistributorNamePatch::patchDistributorNameInJson($items, $targetId, $newName);

        #then
        $this->assertSame('新店1', $result[0]['name']);
        $this->assertSame('店2', $result[1]['name']);
        $this->assertSame(1, $result[0]['distributor_id']);
        $this->assertSame(2, $result[1]['distributor_id']);
    }

    /** TC-J02 */
    public function testPatchMatchesLooseIdTypesAndPreservesOriginalIdTypes(): void
    {
        #given
        $items = [
            ['distributor_id' => '1', 'name' => '字符串ID店'],
            ['distributor_id' => 2, 'name' => '整数ID店'],
        ];

        #when
        $resultFromStringTarget = OperatorDistributorNamePatch::patchDistributorNameInJson($items, 1, '新名A');
        $resultFromIntTarget = OperatorDistributorNamePatch::patchDistributorNameInJson($items, 2, '新名B');

        #then
        $this->assertSame('新名A', $resultFromStringTarget[0]['name']);
        $this->assertSame('1', $resultFromStringTarget[0]['distributor_id']);

        $this->assertSame('新名B', $resultFromIntTarget[1]['name']);
        $this->assertSame(2, $resultFromIntTarget[1]['distributor_id']);
    }

    /** TC-J03 */
    public function testPatchReturnsUnchangedItemsWhenNoMatch(): void
    {
        #given
        $items = [
            ['distributor_id' => 1, 'name' => '店1'],
            ['distributor_id' => 2, 'name' => '店2'],
        ];
        $targetId = 99;
        $newName = '不应写入';

        #when
        $result = OperatorDistributorNamePatch::patchDistributorNameInJson($items, $targetId, $newName);

        #then
        $this->assertSame($items, $result);
    }

    /** TC-J04 */
    public function testPatchWritesNewNameAndPreservesOtherKeys(): void
    {
        #given
        $items = [
            [
                'distributor_id' => 1,
                'name' => '旧店名',
                'role' => 'owner',
                'extra' => ['level' => 3],
            ],
        ];
        $newName = '更新后的店名';

        #when
        $result = OperatorDistributorNamePatch::patchDistributorNameInJson($items, 1, $newName);

        #then
        $this->assertSame($newName, $result[0]['name']);
        $this->assertSame(1, $result[0]['distributor_id']);
        $this->assertSame('owner', $result[0]['role']);
        $this->assertSame(['level' => 3], $result[0]['extra']);
    }
}
