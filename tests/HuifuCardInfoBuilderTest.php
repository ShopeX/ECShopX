<?php

use BsPayBundle\Services\HuifuCardInfoBuilder;

class HuifuCardInfoBuilderTest extends TestCase
{
    public function testCorporateCardUsesBranchCodeWithoutCertFields(): void
    {
        $cardInfo = HuifuCardInfoBuilder::build([
            'card_type' => '0',
            'card_name' => '测试企业',
            'card_no' => '6222000000000000',
            'prov_id' => '310000',
            'area_id' => '310100',
            'branch_code' => '308290003564',
            'branch_name' => '招商银行上海漕河泾支行',
            'cert_no' => 'should-not-send',
            'cert_validity_type' => '1',
            'cert_begin_date' => '20240501',
            'cert_end_date' => '20300601',
            'mp' => '13900000000',
        ]);

        $this->assertSame('308290003564', $cardInfo['branch_code']);
        $this->assertArrayNotHasKey('bank_code', $cardInfo);
        $this->assertArrayNotHasKey('cert_type', $cardInfo);
        $this->assertArrayNotHasKey('cert_no', $cardInfo);
        $this->assertArrayNotHasKey('cert_validity_type', $cardInfo);
        $this->assertArrayNotHasKey('cert_begin_date', $cardInfo);
        $this->assertArrayNotHasKey('cert_end_date', $cardInfo);
    }

    public function testPrivateCardUsesCertFieldsWithoutBranchCode(): void
    {
        $cardInfo = HuifuCardInfoBuilder::build([
            'card_type' => '1',
            'card_name' => '张三',
            'card_no' => '6222000000000001',
            'prov_id' => '310000',
            'area_id' => '310100',
            'branch_name' => '',
            'cert_no' => '310101199001011234',
            'cert_validity_type' => '1',
            'cert_begin_date' => '20240501',
            'cert_end_date' => '20300601',
            'mp' => '13900000001',
        ]);

        $this->assertSame('00', $cardInfo['cert_type']);
        $this->assertSame('310101199001011234', $cardInfo['cert_no']);
        $this->assertArrayNotHasKey('branch_code', $cardInfo);
        $this->assertArrayNotHasKey('bank_code', $cardInfo);
    }
}
