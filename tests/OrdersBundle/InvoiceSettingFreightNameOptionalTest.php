<?php

declare(strict_types=1);

namespace Tests\OrdersBundle;

use CompanysBundle\Services\SettingService;

/**
 * ECX-10652：管理端开票配置 — 运费支持开票时名称可空（默认「运费」），
 * 与 Java OrderInvoiceSettingService 对齐。
 */
class InvoiceSettingFreightNameOptionalTest extends \TestCase
{
    public function testFreightSupportedEmptyNameValidTaxPassesValidation(): void
    {
        $data = $this->validAdminPayload();
        $data['freight_invoice'] = '2';
        $data['freight_name'] = '';
        $data['freight_tax_rate'] = '13';

        $error = validator_params($data, SettingService::getAdminInvoiceSettingRules());

        $this->assertFalse($error, '运费开票名称可空且税率合法时应允许保存');
    }

    public function testFreightSupportedEmptyNameAndTaxThrowsTaxRateMessage(): void
    {
        $data = $this->validAdminPayload();
        $data['freight_invoice'] = '2';
        $data['freight_name'] = '';
        $data['freight_tax_rate'] = '';

        $error = validator_params($data, SettingService::getAdminInvoiceSettingRules());

        $this->assertSame(
            '运费税率最小为0，最大为100的整数',
            $error,
            '名称与税率皆空时应提示运费税率规则，而非名称必填'
        );
    }

    public function testInvoiceConfigErrorMessageSubstitutesDetail(): void
    {
        $detail = '运费税率最小为0，最大为100的整数';
        // 文案为「设置开票配置出错:$errorMessage」，须传 $errorMessage 键才能替换
        $msg = trans('OrdersBundle/Order.invoice_config_error', ['$errorMessage' => $detail]);

        $this->assertStringContainsString($detail, $msg);
        $this->assertStringNotContainsString('$errorMessage', $msg);
    }

    /**
     * @return array<string, string>
     */
    private function validAdminPayload(): array
    {
        return [
            'invoice_status' => '1',
            'invoice_limit' => 'order',
            'invoice_method' => 'offline',
            'freight_invoice' => '2',
            'freight_name' => '运费',
            'apply_type' => '1',
            'invoice_open_term' => '0',
            'special_invoice' => '1',
            'apply_node' => '2',
            'invoice_seller_type' => '1',
            'freight_tax_rate' => '13',
        ];
    }
}
