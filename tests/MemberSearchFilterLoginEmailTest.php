<?php

use MembersBundle\Traits\MemberSearchFilter;

/**
 * 店铺端会员列表筛选：login_email → login_email|like（小写 trim）
 */
class MemberSearchFilterLoginEmailTest extends TestCase
{
    public function testDataFilterMapsLoginEmailToLikeLowercase(): void
    {
        $c = new class {
            use MemberSearchFilter;
        };
        $postdata = [
            'login_email' => '  User@Example.COM  ',
        ];
        $authData = [
            'company_id' => 1,
            'operator_type' => 'staff',
        ];
        $filter = $c->dataFilter($postdata, $authData);
        $this->assertIsArray($filter);
        $this->assertArrayHasKey('login_email|like', $filter);
        $this->assertSame('user@example.com', $filter['login_email|like']);
    }

    public function testDataFilterOmitsEmptyLoginEmail(): void
    {
        $c = new class {
            use MemberSearchFilter;
        };
        $postdata = [
            'login_email' => '   ',
        ];
        $authData = [
            'company_id' => 1,
            'operator_type' => 'staff',
        ];
        $filter = $c->dataFilter($postdata, $authData);
        $this->assertIsArray($filter);
        $this->assertArrayNotHasKey('login_email|like', $filter);
    }
}
