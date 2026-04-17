<?php

use MembersBundle\Services\MemberSyntheticMobileService;

class MemberSyntheticMobileServiceTest extends TestCase
{
    public function testIsAllocatedSyntheticMobileMatches10PlusNineAndLegacy199(): void
    {
        $this->assertTrue(MemberSyntheticMobileService::isAllocatedSyntheticMobile('10123456789'));
        $this->assertTrue(MemberSyntheticMobileService::isAllocatedSyntheticMobile('19965271517'));
        $this->assertFalse(MemberSyntheticMobileService::isAllocatedSyntheticMobile('13812345678'));
        $this->assertFalse(MemberSyntheticMobileService::isAllocatedSyntheticMobile(''));
        $this->assertFalse(MemberSyntheticMobileService::isAllocatedSyntheticMobile(null));
    }

    public function testStripSyntheticMobileForFrontApiClearsMobileAndRegion(): void
    {
        $row = ['mobile' => '10123456789', 'region_mobile' => '10123456789', 'user_id' => 1];
        MemberSyntheticMobileService::stripSyntheticMobileForFrontApi($row);
        $this->assertSame('', $row['mobile']);
        $this->assertSame('', $row['region_mobile']);
    }

    public function testStripLeavesRealMobile(): void
    {
        $row = ['mobile' => '13812345678'];
        MemberSyntheticMobileService::stripSyntheticMobileForFrontApi($row);
        $this->assertSame('13812345678', $row['mobile']);
    }

    public function testStripPlaceholderForEmailRegisteredClearsOnlyWhenLoginEmailAndSynthetic(): void
    {
        $row = ['login_email' => 'a@b.com', 'mobile' => '10123456789', 'region_mobile' => '10123456789'];
        MemberSyntheticMobileService::stripPlaceholderMobileForEmailRegisteredMember($row);
        $this->assertSame('', $row['mobile']);
        $this->assertSame('', $row['region_mobile']);

        $row2 = ['login_email' => 'a@b.com', 'mobile' => '13812345678'];
        MemberSyntheticMobileService::stripPlaceholderMobileForEmailRegisteredMember($row2);
        $this->assertSame('13812345678', $row2['mobile']);

        $row3 = ['mobile' => '10123456789'];
        MemberSyntheticMobileService::stripPlaceholderMobileForEmailRegisteredMember($row3);
        $this->assertSame('10123456789', $row3['mobile']);
    }
}
