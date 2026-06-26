<?php

/**
 * store-ops-buy-now-cloud-stock：ShopadminFastBuyCheckoutAddressValidator 单元测试（可选工具类；checkout 不再强制调用）。
 */

use CompanysBundle\Services\ShopadminFastBuyCheckoutAddressValidator;
use Dingo\Api\Exception\ResourceException;

class ShopadminFastBuyCheckoutAddressValidatorTest extends TestCase
{
    public function testMissingReceiverThrows(): void
    {
        $this->expectException(ResourceException::class);
        ShopadminFastBuyCheckoutAddressValidator::assertLogisticsAddressPresent([
            'receiver_mobile' => '13800138000',
            'receiver_zip' => '000000',
            'receiver_state' => '浙江省',
            'receiver_city' => '杭州市',
            'receiver_district' => '西湖区',
            'receiver_address' => '文三路 1 号',
        ]);
    }

    public function testFullAddressPasses(): void
    {
        ShopadminFastBuyCheckoutAddressValidator::assertLogisticsAddressPresent([
            'receiver_name' => '张三',
            'receiver_mobile' => '13800138000',
            'receiver_zip' => '000000',
            'receiver_state' => '浙江省',
            'receiver_city' => '杭州市',
            'receiver_district' => '西湖区',
            'receiver_address' => '文三路 1 号',
        ]);
        $this->addToAssertionCount(1);
    }
}
